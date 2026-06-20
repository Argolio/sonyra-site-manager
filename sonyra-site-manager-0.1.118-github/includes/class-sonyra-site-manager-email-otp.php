<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Sonyra_Site_Manager_Email_OTP {

	const NEUTRAL_MESSAGE = 'Если доступ разрешён, код будет отправлен';

	public static function generate_code(): string {
		$code = '';
		$length = class_exists( 'Sonyra_Site_Manager_OTP_Policy' ) ? Sonyra_Site_Manager_OTP_Policy::get_code_length() : 6;

		for ( $index = 0; $index < $length; $index++ ) {
			$code .= (string) random_int( 0, 9 );
		}

		return $code;
	}

	public static function hash_code( string $code, string $challenge_uuid ): string {
		$code = trim( $code );
		$challenge_uuid = trim( $challenge_uuid );

		if ( '' === $code || '' === $challenge_uuid ) {
			return '';
		}

		return password_hash( $challenge_uuid . '|' . $code, PASSWORD_DEFAULT );
	}

	public static function verify_code_hash( string $code, string $hash, string $challenge_uuid ): bool {
		$code = trim( $code );
		$hash = trim( $hash );
		$challenge_uuid = trim( $challenge_uuid );

		if ( '' === $code || '' === $hash || '' === $challenge_uuid ) {
			return false;
		}

		return password_verify( $challenge_uuid . '|' . $code, $hash );
	}

	public static function generate_challenge_uuid(): string {
		if ( function_exists( 'wp_generate_uuid4' ) ) {
			return wp_generate_uuid4();
		}

		return uniqid( 'sonyra_otp_', true );
	}

	public static function normalize_email( string $email ): string {
		if ( class_exists( 'Sonyra_Site_Manager_Auth_Identities' ) ) {
			return Sonyra_Site_Manager_Auth_Identities::normalize_email( $email );
		}

		$email = trim( strtolower( $email ) );

		if ( function_exists( 'sanitize_email' ) ) {
			$email = sanitize_email( $email );
		}

		$is_valid = function_exists( 'is_email' ) ? (bool) is_email( $email ) : (bool) filter_var( $email, FILTER_VALIDATE_EMAIL );

		return $is_valid ? $email : '';
	}

	public static function hash_email( string $email ): string {
		if ( class_exists( 'Sonyra_Site_Manager_Auth_Identities' ) ) {
			return Sonyra_Site_Manager_Auth_Identities::hash_email( $email );
		}

		$email = self::normalize_email( $email );

		if ( '' === $email ) {
			return '';
		}

		return hash( 'sha256', 'sonyra_email|' . $email );
	}

	public static function mask_email( string $email ): string {
		$email = self::normalize_email( $email );

		if ( '' === $email || false === strpos( $email, '@' ) ) {
			return '';
		}

		list( $local, $domain ) = explode( '@', $email, 2 );
		$domain_parts = explode( '.', $domain );
		$domain_name = $domain_parts[0] ?? '';
		$domain_zone = count( $domain_parts ) > 1 ? '.' . end( $domain_parts ) : '';
		$local_mask = substr( $local, 0, 1 ) . '***';
		$domain_mask = substr( $domain_name, 0, 1 ) . '***';

		return $local_mask . '@' . $domain_mask . $domain_zone;
	}

	public static function get_ip_hash(): string {
		$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? (string) $_SERVER['REMOTE_ADDR'] : '';

		if ( '' === $ip ) {
			return '';
		}

		if ( function_exists( 'wp_hash' ) ) {
			return wp_hash( 'sonyra_ip|' . $ip );
		}

		return hash( 'sha256', 'sonyra_ip|' . $ip );
	}

	public static function get_user_agent_hash(): string {
		$user_agent = isset( $_SERVER['HTTP_USER_AGENT'] ) ? (string) $_SERVER['HTTP_USER_AGENT'] : '';

		if ( '' === $user_agent ) {
			return '';
		}

		if ( function_exists( 'wp_hash' ) ) {
			return wp_hash( 'sonyra_user_agent|' . $user_agent );
		}

		return hash( 'sha256', 'sonyra_user_agent|' . $user_agent );
	}

	public static function create_challenge( string $email, string $purpose = 'login' ): array {
		global $wpdb;

		$email = self::normalize_email( $email );
		$purpose = self::normalize_purpose( $purpose );
		$channel = self::normalize_channel( 'email' );
		$masked_email = self::mask_email( $email );

		if ( '' === $email ) {
			return self::challenge_response( false, '', '', '', self::NEUTRAL_MESSAGE, 'invalid_email' );
		}

		$table_name = self::get_challenges_table_name();

		if ( '' === $table_name || ! self::table_exists( $table_name ) ) {
			return self::challenge_response( false, '', '', $masked_email, self::NEUTRAL_MESSAGE, 'table_not_ready' );
		}

		$limit = self::can_create_challenge( $email, $purpose );

		if ( empty( $limit['allowed'] ) ) {
			self::log_event(
				'auth.otp_limited',
				array(
					'purpose'             => $purpose,
					'channel'             => $channel,
					'reason'              => $limit['reason'],
					'retry_after_seconds' => $limit['retry_after_seconds'],
				),
				'warning'
			);

			return self::challenge_response( false, '', '', $masked_email, self::NEUTRAL_MESSAGE, (string) $limit['reason'] );
		}

		$challenge_uuid = self::generate_challenge_uuid();
		$code = self::generate_code();
		$code_hash = self::hash_code( $code, $challenge_uuid );
		$email_hash = self::hash_email( $email );
		$identity = class_exists( 'Sonyra_Site_Manager_Auth_Identities' ) ? Sonyra_Site_Manager_Auth_Identities::find_identity_by_email( $email ) : array();
		$identity_id = isset( $identity['id'] ) ? (int) $identity['id'] : null;
		$created_at = self::get_current_time();
		$expires_at = self::datetime_after( self::get_policy_value( 'code_ttl_seconds', 300 ) );
		$ip_hash = self::get_ip_hash();
		$user_agent_hash = self::get_user_agent_hash();

		if ( '' === $code_hash || '' === $email_hash ) {
			return self::challenge_response( false, '', '', $masked_email, self::NEUTRAL_MESSAGE, 'hash_failed' );
		}

		$result = $wpdb->insert(
			$table_name,
			array(
				'challenge_uuid' => $challenge_uuid,
				'identity_id' => $identity_id,
				'email_hash' => $email_hash,
				'code_hash' => $code_hash,
				'channel' => $channel,
				'purpose' => $purpose,
				'status' => 'pending',
				'attempts_count' => 0,
				'max_attempts' => self::get_policy_value( 'max_verify_attempts', 5 ),
				'sent_count' => 1,
				'expires_at' => $expires_at,
				'ip_hash' => '' === $ip_hash ? null : $ip_hash,
				'user_agent_hash' => '' === $user_agent_hash ? null : $user_agent_hash,
				'metadata' => self::json_encode(
					array(
						'source' => 'email_otp_foundation',
						'policy_version' => SONYRA_SITE_MANAGER_VERSION,
					)
				),
				'created_at' => $created_at,
				'updated_at' => $created_at,
			)
		);

		if ( false === $result ) {
			return self::challenge_response( false, '', '', $masked_email, self::NEUTRAL_MESSAGE, 'insert_failed' );
		}

		self::log_event(
			'auth.otp_challenge_created',
			array(
				'challenge_uuid' => $challenge_uuid,
				'purpose' => $purpose,
				'channel' => $channel,
				'identity_id' => $identity_id,
			),
			'info'
		);

		return self::challenge_response( true, $challenge_uuid, $code, $masked_email, self::NEUTRAL_MESSAGE, '', $expires_at );
	}

	public static function can_create_challenge( string $email, string $purpose = 'login' ): array {
		global $wpdb;

		$email_hash = self::hash_email( $email );
		$purpose = self::normalize_purpose( $purpose );
		$channel = self::normalize_channel( 'email' );
		$table_name = self::get_challenges_table_name();

		if ( '' === $email_hash || '' === $table_name || ! self::table_exists( $table_name ) ) {
			return self::limit_response( false, 'not_ready', 0 );
		}

		$now_timestamp = self::get_current_timestamp();
		$active_lock = $wpdb->get_var(
			$wpdb->prepare(
				'SELECT locked_until FROM ' . self::quote_identifier( $table_name ) . ' WHERE email_hash = %s AND purpose = %s AND channel = %s AND locked_until IS NOT NULL AND locked_until > %s ORDER BY locked_until DESC LIMIT 1',
				$email_hash,
				$purpose,
				$channel,
				self::get_current_time()
			)
		);

		if ( ! empty( $active_lock ) ) {
			return self::limit_response( false, 'locked', max( 0, strtotime( (string) $active_lock ) - $now_timestamp ) );
		}

		$last_created_at = $wpdb->get_var(
			$wpdb->prepare(
				'SELECT created_at FROM ' . self::quote_identifier( $table_name ) . ' WHERE email_hash = %s AND purpose = %s AND channel = %s ORDER BY created_at DESC LIMIT 1',
				$email_hash,
				$purpose,
				$channel
			)
		);

		if ( ! empty( $last_created_at ) ) {
			$cooldown_left = self::get_policy_value( 'resend_cooldown_seconds', 60 ) - ( $now_timestamp - strtotime( (string) $last_created_at ) );

			if ( $cooldown_left > 0 ) {
				return self::limit_response( false, 'cooldown', $cooldown_left );
			}
		}

		$window_start = self::datetime_before( self::get_policy_value( 'send_window_seconds', 900 ) );
		$recent_count = (int) $wpdb->get_var(
			$wpdb->prepare(
				'SELECT COUNT(*) FROM ' . self::quote_identifier( $table_name ) . ' WHERE email_hash = %s AND purpose = %s AND channel = %s AND created_at >= %s',
				$email_hash,
				$purpose,
				$channel,
				$window_start
			)
		);

		if ( $recent_count >= self::get_policy_value( 'max_sends_per_window', 3 ) ) {
			return self::limit_response( false, 'send_window_limit', self::get_policy_value( 'send_window_seconds', 900 ) );
		}

		return self::limit_response( true, '', 0 );
	}

	public static function verify_challenge( string $challenge_uuid, string $code ): array {
		global $wpdb;

		$challenge_uuid = trim( $challenge_uuid );
		$code = trim( $code );
		$table_name = self::get_challenges_table_name();

		if ( '' === $challenge_uuid || '' === $code || '' === $table_name || ! self::table_exists( $table_name ) ) {
			return self::verify_response( false, 0, 'generic' );
		}

		$challenge = self::get_challenge_by_uuid( $challenge_uuid );

		if ( empty( $challenge ) ) {
			return self::verify_response( false, 0, 'generic' );
		}

		$identity_id = isset( $challenge['identity_id'] ) ? (int) $challenge['identity_id'] : 0;
		$status = (string) $challenge['status'];
		$attempts_count = (int) $challenge['attempts_count'];
		$max_attempts = (int) $challenge['max_attempts'];
		$now = self::get_current_time();

		if ( 'pending' !== $status ) {
			return self::verify_response( false, $identity_id, 'generic' );
		}

		if ( ! empty( $challenge['expires_at'] ) && strtotime( (string) $challenge['expires_at'] ) < self::get_current_timestamp() ) {
			$wpdb->update(
				$table_name,
				array(
					'status' => 'expired',
					'updated_at' => $now,
				),
				array( 'id' => (int) $challenge['id'] )
			);
			self::log_event( 'auth.otp_expired', self::challenge_audit_context( $challenge ), 'warning' );

			return self::verify_response( false, $identity_id, 'expired' );
		}

		if ( ! empty( $challenge['locked_until'] ) && strtotime( (string) $challenge['locked_until'] ) > self::get_current_timestamp() ) {
			return self::verify_response( false, $identity_id, 'locked' );
		}

		if ( $attempts_count >= $max_attempts ) {
			self::lock_challenge( $challenge );

			return self::verify_response( false, $identity_id, 'locked' );
		}

		$is_valid = self::verify_code_hash( $code, (string) $challenge['code_hash'], $challenge_uuid );

		if ( $is_valid ) {
			$wpdb->update(
				$table_name,
				array(
					'status' => 'used',
					'used_at' => $now,
					'updated_at' => $now,
				),
				array( 'id' => (int) $challenge['id'] )
			);

			self::log_event( 'auth.otp_verified', self::challenge_audit_context( $challenge ), 'info' );

			return self::verify_response( true, $identity_id, '' );
		}

		$new_attempts_count = $attempts_count + 1;

		if ( $new_attempts_count >= $max_attempts ) {
			self::lock_challenge( $challenge, $new_attempts_count );

			return self::verify_response( false, $identity_id, 'locked' );
		}

		$wpdb->update(
			$table_name,
			array(
				'attempts_count' => $new_attempts_count,
				'updated_at' => $now,
			),
			array( 'id' => (int) $challenge['id'] )
		);

		$context = self::challenge_audit_context( $challenge );
		$context['attempts_count'] = $new_attempts_count;
		self::log_event( 'auth.otp_failed', $context, 'warning' );

		return self::verify_response( false, $identity_id, 'generic' );
	}

	public static function expire_old_challenges(): int {
		global $wpdb;

		$table_name = self::get_challenges_table_name();

		if ( '' === $table_name || ! self::table_exists( $table_name ) ) {
			return 0;
		}

		$result = $wpdb->query(
			$wpdb->prepare(
				'UPDATE ' . self::quote_identifier( $table_name ) . ' SET status = %s, updated_at = %s WHERE status = %s AND expires_at IS NOT NULL AND expires_at < %s',
				'expired',
				self::get_current_time(),
				'pending',
				self::get_current_time()
			)
		);

		if ( false === $result ) {
			return 0;
		}

		if ( (int) $result > 0 ) {
			self::log_event(
				'auth.otp_expired',
				array(
					'count' => (int) $result,
				),
				'info'
			);
		}

		return (int) $result;
	}

	public static function get_challenge_status( string $challenge_uuid ): array {
		$challenge = self::get_challenge_by_uuid( $challenge_uuid );

		if ( empty( $challenge ) ) {
			return array(
				'found' => false,
				'status' => '',
				'attempts_count' => 0,
				'max_attempts' => 0,
				'expires_at' => '',
				'locked_until' => '',
				'used_at' => '',
				'identity_id' => 0,
			);
		}

		return array(
			'found' => true,
			'status' => (string) $challenge['status'],
			'attempts_count' => (int) $challenge['attempts_count'],
			'max_attempts' => (int) $challenge['max_attempts'],
			'expires_at' => (string) $challenge['expires_at'],
			'locked_until' => (string) $challenge['locked_until'],
			'used_at' => (string) $challenge['used_at'],
			'identity_id' => isset( $challenge['identity_id'] ) ? (int) $challenge['identity_id'] : 0,
		);
	}

	private static function lock_challenge( array $challenge, ?int $attempts_count = null ): void {
		global $wpdb;

		$table_name = self::get_challenges_table_name();

		if ( '' === $table_name ) {
			return;
		}

		$attempts_count = null === $attempts_count ? (int) $challenge['attempts_count'] : $attempts_count;
		$previous_lockouts = self::count_previous_lockouts( $challenge );
		$locked_until = self::datetime_after( Sonyra_Site_Manager_OTP_Policy::get_lockout_seconds( $previous_lockouts ) );
		$now = self::get_current_time();

		$wpdb->update(
			$table_name,
			array(
				'status' => 'locked',
				'attempts_count' => $attempts_count,
				'locked_until' => $locked_until,
				'updated_at' => $now,
			),
			array( 'id' => (int) $challenge['id'] )
		);

		$context = self::challenge_audit_context( $challenge );
		$context['attempts_count'] = $attempts_count;
		$context['max_attempts'] = (int) $challenge['max_attempts'];
		self::log_event( 'auth.otp_locked', $context, 'warning' );
	}

	private static function count_previous_lockouts( array $challenge ): int {
		global $wpdb;

		$table_name = self::get_challenges_table_name();

		if ( empty( $challenge['email_hash'] ) || '' === $table_name ) {
			return 0;
		}

		return (int) $wpdb->get_var(
			$wpdb->prepare(
				'SELECT COUNT(*) FROM ' . self::quote_identifier( $table_name ) . ' WHERE email_hash = %s AND purpose = %s AND channel = %s AND status = %s',
				(string) $challenge['email_hash'],
				(string) $challenge['purpose'],
				(string) $challenge['channel'],
				'locked'
			)
		);
	}

	private static function get_challenge_by_uuid( string $challenge_uuid ): array {
		global $wpdb;

		$table_name = self::get_challenges_table_name();

		if ( '' === $table_name || ! self::table_exists( $table_name ) ) {
			return array();
		}

		$row = $wpdb->get_row(
			$wpdb->prepare(
				'SELECT * FROM ' . self::quote_identifier( $table_name ) . ' WHERE challenge_uuid = %s LIMIT 1',
				$challenge_uuid
			),
			ARRAY_A
		);

		return is_array( $row ) ? $row : array();
	}

	private static function challenge_audit_context( array $challenge ): array {
		return array(
			'challenge_uuid' => (string) $challenge['challenge_uuid'],
			'purpose' => (string) $challenge['purpose'],
			'channel' => (string) $challenge['channel'],
			'identity_id' => isset( $challenge['identity_id'] ) ? (int) $challenge['identity_id'] : 0,
			'attempts_count' => isset( $challenge['attempts_count'] ) ? (int) $challenge['attempts_count'] : 0,
			'max_attempts' => isset( $challenge['max_attempts'] ) ? (int) $challenge['max_attempts'] : 0,
		);
	}

	private static function log_event( string $event_type, array $context, string $severity ): void {
		if ( ! class_exists( 'Sonyra_Site_Manager_Audit_Log' ) ) {
			return;
		}

		Sonyra_Site_Manager_Audit_Log::log_event( $event_type, $context, $severity );
	}

	private static function challenge_response( bool $success, string $challenge_uuid, string $code, string $masked_email, string $neutral_message, string $error_code, string $expires_at = '' ): array {
		return array(
			'success' => $success,
			'challenge_uuid' => $challenge_uuid,
			'code' => $code,
			'masked_email' => $masked_email,
			'expires_at' => $expires_at,
			'neutral_message' => $neutral_message,
			'error_code' => $error_code,
		);
	}

	private static function verify_response( bool $success, int $identity_id, string $error_code ): array {
		return array(
			'success' => $success,
			'identity_id' => $identity_id,
			'error_code' => $error_code,
			'neutral_message' => self::NEUTRAL_MESSAGE,
		);
	}

	private static function limit_response( bool $allowed, string $reason, int $retry_after_seconds ): array {
		return array(
			'allowed' => $allowed,
			'reason' => $reason,
			'retry_after_seconds' => max( 0, $retry_after_seconds ),
		);
	}

	private static function normalize_purpose( string $purpose ): string {
		if ( class_exists( 'Sonyra_Site_Manager_OTP_Policy' ) ) {
			return Sonyra_Site_Manager_OTP_Policy::normalize_purpose( $purpose );
		}

		return 'login';
	}

	private static function normalize_channel( string $channel ): string {
		if ( class_exists( 'Sonyra_Site_Manager_OTP_Policy' ) ) {
			return Sonyra_Site_Manager_OTP_Policy::normalize_channel( $channel );
		}

		return 'email';
	}

	private static function get_policy_value( string $key, int $fallback ): int {
		if ( ! class_exists( 'Sonyra_Site_Manager_OTP_Policy' ) ) {
			return $fallback;
		}

		$policy = Sonyra_Site_Manager_OTP_Policy::get_policy();

		return isset( $policy[ $key ] ) ? (int) $policy[ $key ] : $fallback;
	}

	private static function get_challenges_table_name(): string {
		if ( ! class_exists( 'Sonyra_Site_Manager_Database' ) ) {
			return '';
		}

		return Sonyra_Site_Manager_Database::get_table_name( 'auth_otp_challenges' );
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

	private static function datetime_after( int $seconds ): string {
		return date( 'Y-m-d H:i:s', self::get_current_timestamp() + $seconds );
	}

	private static function datetime_before( int $seconds ): string {
		return date( 'Y-m-d H:i:s', self::get_current_timestamp() - $seconds );
	}
}
