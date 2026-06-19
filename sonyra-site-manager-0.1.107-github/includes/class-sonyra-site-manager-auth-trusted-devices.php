<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Sonyra_Site_Manager_Auth_Trusted_Devices {

	const COOKIE_NAME = 'sonyra_site_manager_device';
	const TOKEN_BYTES = 32;
	const TOKEN_TTL_SECONDS = 2592000;
	const ACCESS_CODE_VERSION = '1';
	const ACCESS_CODE_MAX_ATTEMPTS = 5;
	const ACCESS_CODE_LOCK_SECONDS = 900;

	public static function remember_current_device( int $identity_id ): array {
		global $wpdb;

		$identity_id = max( 0, $identity_id );
		$table_name = self::get_table_name();

		if ( $identity_id <= 0 || '' === $table_name || ! self::table_exists( $table_name ) ) {
			return self::response( false, false, 'not_ready' );
		}

		$device_uuid = self::generate_device_uuid();
		$token = self::generate_device_token();
		$token_hash = self::hash_device_token( $token );
		$now = self::get_current_time();
		$expires_at = self::datetime_after( self::TOKEN_TTL_SECONDS );
		$ip_hash = class_exists( 'Sonyra_Site_Manager_Email_OTP' ) ? Sonyra_Site_Manager_Email_OTP::get_ip_hash() : '';
		$user_agent_hash = class_exists( 'Sonyra_Site_Manager_Email_OTP' ) ? Sonyra_Site_Manager_Email_OTP::get_user_agent_hash() : '';

		if ( '' === $device_uuid || '' === $token || '' === $token_hash ) {
			unset( $token );
			return self::response( false, false, 'token_failed' );
		}

		$inserted = $wpdb->insert(
			$table_name,
			array(
				'device_uuid'       => $device_uuid,
				'identity_id'       => $identity_id,
				'device_token_hash' => $token_hash,
				'status'            => 'active',
				'ip_hash'           => '' === $ip_hash ? null : $ip_hash,
				'user_agent_hash'   => '' === $user_agent_hash ? null : $user_agent_hash,
				'created_at'        => $now,
				'expires_at'        => $expires_at,
				'last_used_at'      => $now,
				'revoked_at'        => null,
				'metadata'          => self::json_encode(
					array(
						'source'                      => 'email_otp_verify',
						'access_code_failed_attempts' => 0,
						'access_code_locked_until'    => '',
					)
				),
			)
		);

		if ( false === $inserted ) {
			unset( $token );
			return self::response( false, false, 'insert_failed' );
		}

		$cookie_set = self::set_device_cookie( $token, $expires_at );
		unset( $token );

		self::log_event(
			$cookie_set ? 'auth.trusted_device_created' : 'auth.trusted_device_cookie_failed',
			array(
				'identity_id' => $identity_id,
				'device_uuid' => $device_uuid,
				'result'      => $cookie_set ? 'created' : 'cookie_failed',
				'expires_at'  => $expires_at,
			),
			$cookie_set ? 'info' : 'warning'
		);

		return self::response( true, $cookie_set, $cookie_set ? '' : 'cookie_failed' );
	}

	public static function validate_device_cookie(): array {
		$device = self::load_current_active_device();

		if ( empty( $device['valid'] ) ) {
			return self::validate_response(
				false,
				isset( $device['identity_id'] ) ? (int) $device['identity_id'] : 0,
				isset( $device['device_uuid'] ) ? (string) $device['device_uuid'] : '',
				isset( $device['reason'] ) ? (string) $device['reason'] : 'not_found'
			);
		}

		self::touch_device( (int) $device['id'] );

		return self::validate_response( true, (int) $device['identity_id'], (string) $device['device_uuid'], '' );
	}

	public static function get_current_device_login_status(): array {
		$device = self::load_current_active_device();

		if ( empty( $device['valid'] ) ) {
			return array(
				'available' => false,
				'locked'    => false,
				'reason'    => isset( $device['reason'] ) ? (string) $device['reason'] : 'not_found',
			);
		}

		$metadata = self::decode_metadata( isset( $device['metadata'] ) ? (string) $device['metadata'] : '' );
		$has_hash = self::has_access_code_hash( $metadata );
		$locked = self::is_access_code_locked( $metadata );

		return array(
			'available' => $has_hash && ! $locked,
			'locked'    => $locked,
			'reason'    => $has_hash ? ( $locked ? 'locked' : '' ) : 'code_not_set',
		);
	}

	public static function setup_access_code_for_current_device( int $identity_id, string $code ): array {
		$identity_id = max( 0, $identity_id );
		$code = trim( $code );

		if ( $identity_id <= 0 ) {
			return self::device_code_response( false, 0, '', 'invalid_identity' );
		}

		if ( ! preg_match( '/^[0-9]{4}$/', $code ) ) {
			return self::device_code_response( false, $identity_id, '', 'invalid_format' );
		}

		$device = self::load_current_active_device();

		if ( empty( $device['valid'] ) || (int) $device['identity_id'] !== $identity_id ) {
			return self::device_code_response( false, $identity_id, isset( $device['device_uuid'] ) ? (string) $device['device_uuid'] : '', 'device_unavailable' );
		}

		$hash = password_hash( $code, PASSWORD_DEFAULT );
		unset( $code );

		if ( ! is_string( $hash ) || '' === $hash ) {
			return self::device_code_response( false, $identity_id, (string) $device['device_uuid'], 'technical' );
		}

		$metadata = self::decode_metadata( isset( $device['metadata'] ) ? (string) $device['metadata'] : '' );
		$metadata['access_code_hash'] = $hash;
		$metadata['access_code_set_at'] = self::get_current_time();
		$metadata['access_code_version'] = self::ACCESS_CODE_VERSION;
		$metadata['access_code_failed_attempts'] = 0;
		$metadata['access_code_locked_until'] = '';
		unset( $hash );

		$updated = self::update_device_metadata( (int) $device['id'], $metadata );

		if ( ! $updated ) {
			return self::device_code_response( false, $identity_id, (string) $device['device_uuid'], 'technical' );
		}

		self::touch_device( (int) $device['id'] );
		self::log_event(
			'auth.trusted_device_code_setup',
			array(
				'identity_id' => $identity_id,
				'device_uuid' => (string) $device['device_uuid'],
				'result'      => 'saved',
			),
			'info'
		);

		return self::device_code_response( true, $identity_id, (string) $device['device_uuid'], '' );
	}

	public static function verify_access_code_for_current_device( string $code ): array {
		$code = trim( $code );

		if ( ! preg_match( '/^[0-9]{4}$/', $code ) ) {
			unset( $code );
			return self::device_code_response( false, 0, '', 'invalid_format' );
		}

		$device = self::load_current_active_device();

		if ( empty( $device['valid'] ) ) {
			unset( $code );
			return self::device_code_response( false, 0, isset( $device['device_uuid'] ) ? (string) $device['device_uuid'] : '', 'device_unavailable' );
		}

		$metadata = self::decode_metadata( isset( $device['metadata'] ) ? (string) $device['metadata'] : '' );
		$identity_id = (int) $device['identity_id'];
		$device_uuid = (string) $device['device_uuid'];

		if ( ! self::has_access_code_hash( $metadata ) ) {
			unset( $code );
			return self::device_code_response( false, $identity_id, $device_uuid, 'device_unavailable' );
		}

		if ( self::is_access_code_locked( $metadata ) ) {
			unset( $code );
			self::log_event(
				'auth.trusted_device_quick_login_locked',
				array(
					'identity_id' => $identity_id,
					'device_uuid' => $device_uuid,
					'result'      => 'locked',
					'reason_code' => 'locked',
				),
				'warning'
			);

			return self::device_code_response( false, $identity_id, $device_uuid, 'locked' );
		}

		$hash = (string) $metadata['access_code_hash'];
		$verified = password_verify( $code, $hash );
		unset( $code, $hash );

		if ( $verified ) {
			$metadata['access_code_failed_attempts'] = 0;
			$metadata['access_code_locked_until'] = '';
			self::update_device_metadata( (int) $device['id'], $metadata );
			self::touch_device( (int) $device['id'] );
			self::log_event(
				'auth.trusted_device_quick_login_success',
				array(
					'identity_id' => $identity_id,
					'device_uuid' => $device_uuid,
					'result'      => 'authenticated',
				),
				'info'
			);

			return self::device_code_response( true, $identity_id, $device_uuid, '' );
		}

		$attempts = max( 0, isset( $metadata['access_code_failed_attempts'] ) ? (int) $metadata['access_code_failed_attempts'] : 0 ) + 1;
		$metadata['access_code_failed_attempts'] = $attempts;
		$reason = 'invalid_code';

		self::log_event(
			'auth.trusted_device_quick_login_failed',
			array(
				'identity_id' => $identity_id,
				'device_uuid' => $device_uuid,
				'result'      => 'failed',
				'reason_code' => 'invalid_code',
			),
			'warning'
		);

		if ( $attempts >= self::ACCESS_CODE_MAX_ATTEMPTS ) {
			$metadata['access_code_locked_until'] = self::datetime_after( self::ACCESS_CODE_LOCK_SECONDS );
			$reason = 'locked';
			self::log_event(
				'auth.trusted_device_quick_login_locked',
				array(
					'identity_id' => $identity_id,
					'device_uuid' => $device_uuid,
					'result'      => 'locked',
					'reason_code' => 'too_many_attempts',
				),
				'warning'
			);
		}

		self::update_device_metadata( (int) $device['id'], $metadata );

		return self::device_code_response( false, $identity_id, $device_uuid, $reason );
	}

	public static function get_cookie_name(): string {
		return self::COOKIE_NAME;
	}

	public static function get_cookie_options( $expires_at = null ): array {
		return array(
			'expires'  => self::normalize_expires_at( $expires_at ),
			'path'     => '/',
			'secure'   => self::should_use_secure_cookie(),
			'httponly' => true,
			'samesite' => 'Lax',
		);
	}

	private static function set_device_cookie( string $token, string $expires_at ): bool {
		return (bool) setcookie( self::get_cookie_name(), $token, self::get_cookie_options( $expires_at ) );
	}

	private static function get_device_cookie_token(): string {
		$name = self::get_cookie_name();

		if ( ! isset( $_COOKIE[ $name ] ) ) {
			return '';
		}

		$value = (string) $_COOKIE[ $name ];

		if ( function_exists( 'sanitize_text_field' ) ) {
			return sanitize_text_field( $value );
		}

		return trim( preg_replace( '/[^A-Za-z0-9_\\-\\.]/', '', $value ) );
	}

	private static function load_current_active_device(): array {
		global $wpdb;

		$token = self::get_device_cookie_token();
		$table_name = self::get_table_name();

		if ( '' === $token || '' === $table_name || ! self::table_exists( $table_name ) ) {
			unset( $token );
			return array( 'valid' => false, 'reason' => 'not_found' );
		}

		$token_hash = self::hash_device_token( $token );
		unset( $token );

		if ( '' === $token_hash ) {
			return array( 'valid' => false, 'reason' => 'invalid_token' );
		}

		$row = $wpdb->get_row(
			$wpdb->prepare(
				'SELECT id, device_uuid, identity_id, status, expires_at, metadata FROM ' . self::quote_identifier( $table_name ) . ' WHERE device_token_hash = %s LIMIT 1',
				$token_hash
			),
			ARRAY_A
		);

		if ( ! is_array( $row ) ) {
			return array( 'valid' => false, 'reason' => 'not_found' );
		}

		$device_uuid = isset( $row['device_uuid'] ) ? (string) $row['device_uuid'] : '';
		$identity_id = isset( $row['identity_id'] ) ? (int) $row['identity_id'] : 0;
		$status = isset( $row['status'] ) ? (string) $row['status'] : '';
		$expires_at = isset( $row['expires_at'] ) ? (string) $row['expires_at'] : '';

		if ( 'active' !== $status ) {
			return array(
				'valid'       => false,
				'identity_id' => $identity_id,
				'device_uuid' => $device_uuid,
				'reason'      => 'inactive',
			);
		}

		if ( '' !== $expires_at && strtotime( $expires_at ) < self::get_current_timestamp() ) {
			$wpdb->update(
				$table_name,
				array( 'status' => 'expired' ),
				array( 'id' => (int) $row['id'] )
			);

			return array(
				'valid'       => false,
				'identity_id' => $identity_id,
				'device_uuid' => $device_uuid,
				'reason'      => 'expired',
			);
		}

		$row['valid'] = true;

		return $row;
	}

	private static function touch_device( int $device_id ): bool {
		global $wpdb;

		$table_name = self::get_table_name();

		if ( $device_id <= 0 || '' === $table_name || ! self::table_exists( $table_name ) ) {
			return false;
		}

		return false !== $wpdb->update(
			$table_name,
			array( 'last_used_at' => self::get_current_time() ),
			array( 'id' => $device_id )
		);
	}

	private static function update_device_metadata( int $device_id, array $metadata ): bool {
		global $wpdb;

		$table_name = self::get_table_name();

		if ( $device_id <= 0 || '' === $table_name || ! self::table_exists( $table_name ) ) {
			return false;
		}

		return false !== $wpdb->update(
			$table_name,
			array(
				'metadata'     => self::json_encode( $metadata ),
				'last_used_at' => self::get_current_time(),
			),
			array( 'id' => $device_id )
		);
	}

	private static function has_access_code_hash( array $metadata ): bool {
		return isset( $metadata['access_code_hash'] ) && is_string( $metadata['access_code_hash'] ) && '' !== $metadata['access_code_hash'];
	}

	private static function is_access_code_locked( array $metadata ): bool {
		$locked_until = isset( $metadata['access_code_locked_until'] ) ? (string) $metadata['access_code_locked_until'] : '';

		if ( '' === $locked_until ) {
			return false;
		}

		$timestamp = strtotime( $locked_until );

		return false !== $timestamp && $timestamp > self::get_current_timestamp();
	}

	private static function decode_metadata( string $metadata ): array {
		if ( '' === trim( $metadata ) ) {
			return array();
		}

		$decoded = function_exists( 'wp_json_decode' ) ? wp_json_decode( $metadata, true ) : json_decode( $metadata, true );

		return is_array( $decoded ) ? $decoded : array();
	}

	private static function generate_device_uuid(): string {
		if ( function_exists( 'wp_generate_uuid4' ) ) {
			return wp_generate_uuid4();
		}

		return uniqid( 'sonyra_device_', true );
	}

	private static function generate_device_token(): string {
		if ( function_exists( 'random_bytes' ) ) {
			return bin2hex( random_bytes( self::TOKEN_BYTES ) );
		}

		if ( function_exists( 'wp_generate_password' ) ) {
			return wp_generate_password( self::TOKEN_BYTES * 2, false, false );
		}

		return hash( 'sha256', uniqid( 'sonyra_device_seed_', true ) . microtime( true ) );
	}

	private static function hash_device_token( string $token ): string {
		$token = trim( $token );

		if ( '' === $token ) {
			return '';
		}

		if ( function_exists( 'wp_hash' ) ) {
			return wp_hash( 'sonyra_trusted_device|' . $token );
		}

		return hash( 'sha256', 'sonyra_trusted_device|' . $token );
	}

	private static function response( bool $created, bool $cookie_set, string $error_code ): array {
		return array(
			'created'    => $created,
			'cookie_set' => $cookie_set,
			'error_code' => $error_code,
		);
	}

	private static function validate_response( bool $valid, int $identity_id, string $device_uuid, string $reason ): array {
		return array(
			'valid'       => $valid,
			'identity_id' => $identity_id,
			'device_uuid' => $device_uuid,
			'reason'      => $reason,
		);
	}

	private static function device_code_response( bool $success, int $identity_id, string $device_uuid, string $reason ): array {
		return array(
			'success'     => $success,
			'identity_id' => $identity_id,
			'device_uuid' => $device_uuid,
			'reason'      => $reason,
		);
	}

	private static function should_use_secure_cookie(): bool {
		if ( function_exists( 'is_ssl' ) && is_ssl() ) {
			return true;
		}

		$https = isset( $_SERVER['HTTPS'] ) ? strtolower( (string) $_SERVER['HTTPS'] ) : '';

		return in_array( $https, array( 'on', '1', 'true' ), true );
	}

	private static function normalize_expires_at( $expires_at ): int {
		if ( is_int( $expires_at ) && $expires_at > 0 ) {
			return $expires_at;
		}

		if ( is_string( $expires_at ) && '' !== trim( $expires_at ) ) {
			$timestamp = strtotime( $expires_at );

			if ( false !== $timestamp ) {
				return $timestamp;
			}
		}

		return time() + self::TOKEN_TTL_SECONDS;
	}

	private static function datetime_after( int $seconds ): string {
		$timestamp = self::get_current_timestamp() + max( 0, $seconds );

		if ( function_exists( 'wp_date' ) ) {
			return wp_date( 'Y-m-d H:i:s', $timestamp );
		}

		return date( 'Y-m-d H:i:s', $timestamp );
	}

	private static function get_current_time(): string {
		if ( function_exists( 'current_time' ) ) {
			return (string) current_time( 'mysql' );
		}

		return date( 'Y-m-d H:i:s' );
	}

	private static function get_current_timestamp(): int {
		if ( function_exists( 'current_time' ) ) {
			return (int) current_time( 'timestamp' );
		}

		return time();
	}

	private static function get_table_name(): string {
		if ( ! class_exists( 'Sonyra_Site_Manager_Database' ) ) {
			return '';
		}

		return Sonyra_Site_Manager_Database::get_table_name( 'auth_trusted_devices' );
	}

	private static function table_exists( string $table_name ): bool {
		global $wpdb;

		if ( '' === $table_name ) {
			return false;
		}

		$found = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table_name ) );

		return $found === $table_name;
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

	private static function log_event( string $event_type, array $context, string $severity ): void {
		if ( ! class_exists( 'Sonyra_Site_Manager_Audit_Log' ) ) {
			return;
		}

		Sonyra_Site_Manager_Audit_Log::log_event( $event_type, $context, $severity );
	}
}
