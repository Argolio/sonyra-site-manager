<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Sonyra_Site_Manager_Auth_Sessions {

	const IDLE_TIMEOUT_SECONDS = 900;

	public static function generate_session_uuid(): string {
		if ( function_exists( 'wp_generate_uuid4' ) ) {
			return wp_generate_uuid4();
		}

		return uniqid( 'sonyra_session_', true );
	}

	public static function generate_session_token(): string {
		if ( function_exists( 'random_bytes' ) ) {
			return bin2hex( random_bytes( 32 ) );
		}

		if ( function_exists( 'wp_generate_password' ) ) {
			return wp_generate_password( 64, false, false );
		}

		return hash( 'sha256', uniqid( 'sonyra_session_seed_', true ) . microtime( true ) );
	}

	public static function hash_session_token( string $token ): string {
		$token = trim( $token );

		if ( '' === $token ) {
			return '';
		}

		if ( function_exists( 'wp_hash' ) ) {
			return wp_hash( 'sonyra_session|' . $token );
		}

		return hash( 'sha256', 'sonyra_session|' . $token );
	}

	public static function get_default_ttl_seconds(): int {
		return 86400;
	}

	public static function get_idle_timeout_seconds(): int {
		return self::IDLE_TIMEOUT_SECONDS;
	}

	public static function create_session( int $identity_id, array $context = array() ): array {
		global $wpdb;

		$identity_id = max( 0, $identity_id );
		$table_name = self::get_sessions_table_name();

		if ( $identity_id <= 0 ) {
			return self::create_response( false, '', '', '', 'invalid_identity' );
		}

		if ( '' === $table_name || ! self::table_exists( $table_name ) ) {
			return self::create_response( false, '', '', '', 'table_not_ready' );
		}

		$session_uuid = self::generate_session_uuid();
		$session_token = self::generate_session_token();
		$session_token_hash = self::hash_session_token( $session_token );
		$created_at = self::get_current_time();
		$expires_at = self::datetime_after( self::get_default_ttl_seconds() );
		$ip_hash = class_exists( 'Sonyra_Site_Manager_Email_OTP' ) ? Sonyra_Site_Manager_Email_OTP::get_ip_hash() : '';
		$user_agent_hash = class_exists( 'Sonyra_Site_Manager_Email_OTP' ) ? Sonyra_Site_Manager_Email_OTP::get_user_agent_hash() : '';

		if ( '' === $session_token || '' === $session_token_hash ) {
			return self::create_response( false, '', '', '', 'token_failed' );
		}

		$result = $wpdb->insert(
			$table_name,
			array(
				'session_uuid'       => $session_uuid,
				'identity_id'        => $identity_id,
				'session_token_hash' => $session_token_hash,
				'status'             => 'active',
				'ip_hash'            => '' === $ip_hash ? null : $ip_hash,
				'user_agent_hash'    => '' === $user_agent_hash ? null : $user_agent_hash,
				'created_at'         => $created_at,
				'expires_at'         => $expires_at,
				'last_seen_at'       => $created_at,
				'revoked_at'         => null,
				'metadata'           => self::json_encode( self::get_safe_metadata( $context ) ),
			)
		);

		if ( false === $result ) {
			unset( $session_token );

			return self::create_response( false, '', '', '', 'insert_failed' );
		}

		self::log_event(
			'auth.session_created',
			array(
				'session_uuid' => $session_uuid,
				'identity_id'  => $identity_id,
				'result'       => 'created',
				'expires_at'   => $expires_at,
			),
			'info'
		);

		return self::create_response( true, $session_uuid, $session_token, $expires_at, '' );
	}

	public static function find_session_by_token( string $token ): array {
		global $wpdb;

		$session_token_hash = self::hash_session_token( $token );
		$table_name = self::get_sessions_table_name();

		if ( '' === $session_token_hash || '' === $table_name || ! self::table_exists( $table_name ) ) {
			return array();
		}

		$row = $wpdb->get_row(
			$wpdb->prepare(
				'SELECT * FROM ' . self::quote_identifier( $table_name ) . ' WHERE session_token_hash = %s AND status = %s LIMIT 1',
				$session_token_hash,
				'active'
			),
			ARRAY_A
		);

		if ( ! is_array( $row ) ) {
			return array();
		}

		unset( $row['session_token_hash'] );

		return $row;
	}

	public static function validate_session_token( string $token ): array {
		global $wpdb;

		$session_token_hash = self::hash_session_token( $token );
		$table_name = self::get_sessions_table_name();

		if ( '' === $session_token_hash || '' === $table_name || ! self::table_exists( $table_name ) ) {
			self::log_event( 'auth.session_failed', array( 'result' => 'failed', 'reason_code' => 'not_ready' ), 'warning' );

			return self::validate_response( false, false, 0, '', '', '' );
		}

		$row = $wpdb->get_row(
			$wpdb->prepare(
				'SELECT * FROM ' . self::quote_identifier( $table_name ) . ' WHERE session_token_hash = %s LIMIT 1',
				$session_token_hash
			),
			ARRAY_A
		);

		if ( ! is_array( $row ) ) {
			self::log_event( 'auth.session_failed', array( 'result' => 'failed', 'reason_code' => 'not_found' ), 'warning' );

			return self::validate_response( false, false, 0, '', '', '' );
		}

		$session_uuid = (string) $row['session_uuid'];
		$identity_id = isset( $row['identity_id'] ) ? (int) $row['identity_id'] : 0;
		$status = isset( $row['status'] ) ? (string) $row['status'] : '';
		$expires_at = isset( $row['expires_at'] ) ? (string) $row['expires_at'] : '';
		$last_seen_at = isset( $row['last_seen_at'] ) ? (string) $row['last_seen_at'] : '';

		if ( 'active' !== $status ) {
			self::log_event( 'auth.session_failed', self::audit_context( $session_uuid, $identity_id, 'failed', 'inactive', $expires_at ), 'warning' );

			return self::validate_response( false, false, 0, '', $status, '' );
		}

		if ( ! empty( $row['revoked_at'] ) ) {
			self::log_event( 'auth.session_failed', self::audit_context( $session_uuid, $identity_id, 'failed', 'revoked', $expires_at ), 'warning' );

			return self::validate_response( false, false, 0, '', $status, '' );
		}

		if ( '' !== $expires_at && strtotime( $expires_at ) < self::get_current_timestamp() ) {
			$wpdb->update(
				$table_name,
				array( 'status' => 'expired' ),
				array( 'id' => (int) $row['id'] )
			);
			self::log_event( 'auth.session_expired', self::audit_context( $session_uuid, $identity_id, 'expired', 'expired', $expires_at ), 'warning' );

			return self::validate_response( false, false, 0, '', 'expired', '' );
		}

		if ( self::is_session_idle_locked( $last_seen_at ) ) {
			$wpdb->update(
				$table_name,
				array( 'status' => 'locked' ),
				array( 'id' => (int) $row['id'] )
			);
			self::log_event( 'auth.session_locked', self::audit_context( $session_uuid, $identity_id, 'locked', 'idle_timeout', $expires_at ), 'warning' );

			return self::validate_response( false, false, 0, '', 'locked', '' );
		}

		$wpdb->update(
			$table_name,
			array( 'last_seen_at' => self::get_current_time() ),
			array( 'id' => (int) $row['id'] )
		);

		self::log_event( 'auth.session_validated', self::audit_context( $session_uuid, $identity_id, 'validated', '', $expires_at ), 'info' );

		return self::validate_response( true, true, $identity_id, $session_uuid, $status, $expires_at );
	}

	public static function revoke_session_by_token( string $token ): bool {
		global $wpdb;

		$session_token_hash = self::hash_session_token( $token );
		$table_name = self::get_sessions_table_name();

		if ( '' === $session_token_hash || '' === $table_name || ! self::table_exists( $table_name ) ) {
			return false;
		}

		$row = $wpdb->get_row(
			$wpdb->prepare(
				'SELECT id, session_uuid, identity_id, expires_at FROM ' . self::quote_identifier( $table_name ) . ' WHERE session_token_hash = %s LIMIT 1',
				$session_token_hash
			),
			ARRAY_A
		);

		if ( ! is_array( $row ) ) {
			return false;
		}

		$result = $wpdb->update(
			$table_name,
			array(
				'status'     => 'revoked',
				'revoked_at' => self::get_current_time(),
			),
			array( 'id' => (int) $row['id'] )
		);

		if ( false === $result ) {
			return false;
		}

		self::log_event(
			'auth.session_revoked',
			self::audit_context( (string) $row['session_uuid'], (int) $row['identity_id'], 'revoked', '', (string) $row['expires_at'] ),
			'info'
		);

		return true;
	}

	public static function revoke_sessions_for_identity( int $identity_id ): int {
		global $wpdb;

		$identity_id = max( 0, $identity_id );
		$table_name = self::get_sessions_table_name();

		if ( $identity_id <= 0 || '' === $table_name || ! self::table_exists( $table_name ) ) {
			return 0;
		}

		$result = $wpdb->query(
			$wpdb->prepare(
				'UPDATE ' . self::quote_identifier( $table_name ) . ' SET status = %s, revoked_at = %s WHERE identity_id = %d AND status = %s',
				'revoked',
				self::get_current_time(),
				$identity_id,
				'active'
			)
		);

		$count = false === $result ? 0 : (int) $result;

		if ( $count > 0 ) {
			self::log_event(
				'auth.session_revoked',
				array(
					'identity_id' => $identity_id,
					'result'      => 'revoked',
				),
				'info'
			);
		}

		return $count;
	}

	public static function expire_old_sessions(): int {
		global $wpdb;

		$table_name = self::get_sessions_table_name();

		if ( '' === $table_name || ! self::table_exists( $table_name ) ) {
			return 0;
		}

		$result = $wpdb->query(
			$wpdb->prepare(
				'UPDATE ' . self::quote_identifier( $table_name ) . ' SET status = %s WHERE status = %s AND expires_at IS NOT NULL AND expires_at < %s',
				'expired',
				'active',
				self::get_current_time()
			)
		);

		$count = false === $result ? 0 : (int) $result;

		if ( $count > 0 ) {
			self::log_event(
				'auth.session_expired',
				array(
					'result' => 'expired',
				),
				'info'
			);
		}

		return $count;
	}

	public static function get_session_status(): array {
		return array(
			'session_service_ready' => class_exists( 'Sonyra_Site_Manager_Database' ),
			'default_ttl_seconds'   => self::get_default_ttl_seconds(),
			'idle_timeout_seconds'  => self::get_idle_timeout_seconds(),
			'creates_cookie'        => false,
			'grants_access'         => false,
			'uses_rest'             => false,
			'is_ready'              => class_exists( 'Sonyra_Site_Manager_Database' ),
		);
	}

	private static function create_response( bool $success, string $session_uuid, string $session_token, string $expires_at, string $error_code ): array {
		return array(
			'success'       => $success,
			'session_uuid'  => $session_uuid,
			'session_token' => $session_token,
			'expires_at'    => $expires_at,
			'error_code'    => $error_code,
		);
	}

	private static function validate_response( bool $success, bool $valid, int $identity_id, string $session_uuid, string $status, string $expires_at ): array {
		return array(
			'success'      => $success,
			'valid'        => $valid,
			'identity_id'  => $identity_id,
			'session_uuid' => $session_uuid,
			'status'       => $status,
			'expires_at'   => $expires_at,
		);
	}

	private static function is_session_idle_locked( string $last_seen_at ): bool {
		$last_seen_timestamp = strtotime( $last_seen_at );

		if ( false === $last_seen_timestamp ) {
			return false;
		}

		return ( self::get_current_timestamp() - $last_seen_timestamp ) > self::get_idle_timeout_seconds();
	}

	private static function audit_context( string $session_uuid, int $identity_id, string $result, string $reason_code, string $expires_at ): array {
		$context = array(
			'session_uuid' => $session_uuid,
			'identity_id'  => $identity_id,
			'result'       => $result,
			'expires_at'   => $expires_at,
		);

		if ( '' !== $reason_code ) {
			$context['reason_code'] = $reason_code;
		}

		return $context;
	}

	private static function get_safe_metadata( array $context ): array {
		$unsafe_keys = array(
			'session_token',
			'session_token_hash',
			'email',
			'code',
			'otp',
			'token',
			'secret',
			'nonce',
			'cookie',
			'authorization',
			'api_key',
		);

		foreach ( $unsafe_keys as $key ) {
			if ( array_key_exists( $key, $context ) ) {
				unset( $context[ $key ] );
			}
		}

		return $context;
	}

	private static function get_sessions_table_name(): string {
		if ( ! class_exists( 'Sonyra_Site_Manager_Database' ) ) {
			return '';
		}

		return Sonyra_Site_Manager_Database::get_table_name( 'auth_sessions' );
	}

	private static function table_exists( string $table_name ): bool {
		global $wpdb;

		$found_table = (string) $wpdb->get_var(
			$wpdb->prepare( 'SHOW TABLES LIKE %s', $table_name )
		);

		return $found_table === $table_name;
	}

	private static function quote_identifier( string $identifier ): string {
		return '`' . str_replace( '`', '``', $identifier ) . '`';
	}

	private static function json_encode( array $value ): string {
		if ( function_exists( 'wp_json_encode' ) ) {
			return (string) wp_json_encode( $value );
		}

		return (string) json_encode( $value );
	}

	private static function datetime_after( int $seconds ): string {
		return date( 'Y-m-d H:i:s', self::get_current_timestamp() + $seconds );
	}

	private static function get_current_time(): string {
		if ( function_exists( 'current_time' ) ) {
			return current_time( 'mysql' );
		}

		return date( 'Y-m-d H:i:s' );
	}

	private static function get_current_timestamp(): int {
		if ( function_exists( 'current_time' ) ) {
			return (int) current_time( 'timestamp' );
		}

		return time();
	}

	private static function log_event( string $event_type, array $context, string $severity ): void {
		if ( ! class_exists( 'Sonyra_Site_Manager_Audit_Log' ) ) {
			return;
		}

		Sonyra_Site_Manager_Audit_Log::log_event( $event_type, $context, $severity );
	}
}
