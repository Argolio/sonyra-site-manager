<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Sonyra_Site_Manager_Auth_Cookie {

	public static function get_cookie_name(): string {
		return 'sonyra_site_manager_auth';
	}

	public static function get_cookie_ttl_seconds(): int {
		return 86400;
	}

	public static function get_cookie_path(): string {
		return '/';
	}

	public static function should_use_secure_cookie(): bool {
		if ( function_exists( 'is_ssl' ) && is_ssl() ) {
			return true;
		}

		$https = isset( $_SERVER['HTTPS'] ) ? strtolower( (string) $_SERVER['HTTPS'] ) : '';

		return in_array( $https, array( 'on', '1', 'true' ), true );
	}

	public static function get_cookie_options( $expires_at = null ): array {
		$expires = self::normalize_expires_at( $expires_at );

		return array(
			'expires'  => $expires,
			'path'     => self::get_cookie_path(),
			'secure'   => self::should_use_secure_cookie(),
			'httponly' => true,
			'samesite' => 'Lax',
		);
	}

	public static function set_auth_cookie( string $session_token, $expires_at = null ): bool {
		$session_token = trim( $session_token );

		if ( '' === $session_token ) {
			self::log_event( 'auth.cookie_invalid', array( 'result' => 'failed', 'reason_code' => 'empty_session_token', 'access_granted' => false ), 'warning' );

			return false;
		}

		$result = setcookie( self::get_cookie_name(), $session_token, self::get_cookie_options( $expires_at ) );

		self::log_event(
			$result ? 'auth.cookie_set' : 'auth.cookie_invalid',
			array(
				'result'         => $result ? 'set' : 'failed',
				'reason_code'    => $result ? '' : 'setcookie_failed',
				'access_granted' => false,
				'expires_at'     => self::format_expires_at( $expires_at ),
			),
			$result ? 'info' : 'warning'
		);

		return (bool) $result;
	}

	public static function clear_auth_cookie(): bool {
		$options = self::get_cookie_options( time() - self::get_cookie_ttl_seconds() );
		$result = setcookie( self::get_cookie_name(), '', $options );

		self::log_event(
			'auth.cookie_cleared',
			array(
				'result'         => $result ? 'cleared' : 'failed',
				'reason_code'    => $result ? '' : 'clear_failed',
				'access_granted' => false,
			),
			$result ? 'info' : 'warning'
		);

		return (bool) $result;
	}

	public static function get_cookie_token(): string {
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

	public static function validate_cookie(): array {
		$session_token = self::get_cookie_token();

		if ( '' === $session_token ) {
			self::log_event( 'auth.cookie_invalid', array( 'result' => 'invalid', 'reason_code' => 'no_cookie', 'access_granted' => false ), 'warning' );

			return self::validate_response( false, 0, '', '', '', 'no_cookie' );
		}

		if ( ! class_exists( 'Sonyra_Site_Manager_Auth_Sessions' ) ) {
			self::log_event( 'auth.cookie_invalid', array( 'result' => 'invalid', 'reason_code' => 'auth_sessions_unavailable', 'access_granted' => false ), 'warning' );

			return self::validate_response( false, 0, '', '', '', 'auth_sessions_unavailable' );
		}

		$session_result = Sonyra_Site_Manager_Auth_Sessions::validate_session_token( $session_token );
		unset( $session_token );

		if ( empty( $session_result['valid'] ) ) {
			self::log_event( 'auth.cookie_invalid', self::audit_context_from_session_result( $session_result, 'invalid' ), 'warning' );
			$session_status = isset( $session_result['status'] ) ? sanitize_key( (string) $session_result['status'] ) : '';

			return self::validate_response(
				false,
				0,
				isset( $session_result['session_uuid'] ) ? (string) $session_result['session_uuid'] : '',
				$session_status,
				isset( $session_result['expires_at'] ) ? (string) $session_result['expires_at'] : '',
				'' !== $session_status ? $session_status : 'invalid_session'
			);
		}

		self::log_event( 'auth.cookie_validated', self::audit_context_from_session_result( $session_result, 'validated' ), 'info' );

		return self::validate_response(
			true,
			isset( $session_result['identity_id'] ) ? (int) $session_result['identity_id'] : 0,
			isset( $session_result['session_uuid'] ) ? (string) $session_result['session_uuid'] : '',
			isset( $session_result['status'] ) ? (string) $session_result['status'] : '',
			isset( $session_result['expires_at'] ) ? (string) $session_result['expires_at'] : '',
			''
		);
	}

	public static function revoke_cookie_session(): bool {
		$session_token = self::get_cookie_token();
		$revoked = false;

		if ( '' !== $session_token && class_exists( 'Sonyra_Site_Manager_Auth_Sessions' ) ) {
			$revoked = Sonyra_Site_Manager_Auth_Sessions::revoke_session_by_token( $session_token );
		}

		unset( $session_token );

		$cleared = self::clear_auth_cookie();
		$result = $revoked || $cleared;

		self::log_event(
			'auth.logout_completed',
			array(
				'result'         => $result ? 'completed' : 'failed',
				'reason_code'    => $result ? '' : 'logout_failed',
				'access_granted' => false,
			),
			$result ? 'info' : 'warning'
		);

		return $result;
	}

	public static function get_cookie_status(): array {
		$options = self::get_cookie_options();

		return array(
			'cookie_service_ready' => class_exists( 'Sonyra_Site_Manager_Auth_Sessions' ),
			'cookie_name'          => self::get_cookie_name(),
			'ttl_seconds'          => self::get_cookie_ttl_seconds(),
			'httponly'             => (bool) $options['httponly'],
			'secure'               => (bool) $options['secure'],
			'samesite'             => (string) $options['samesite'],
			'path'                 => (string) $options['path'],
			'creates_cookie'       => true,
			'validates_cookie'     => true,
			'grants_access'        => false,
			'uses_rest'            => false,
			'is_ready'             => class_exists( 'Sonyra_Site_Manager_Auth_Sessions' ),
		);
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

		return time() + self::get_cookie_ttl_seconds();
	}

	private static function format_expires_at( $expires_at ): string {
		$timestamp = self::normalize_expires_at( $expires_at );

		return date( 'Y-m-d H:i:s', $timestamp );
	}

	private static function validate_response( bool $valid, int $identity_id, string $session_uuid, string $status, string $expires_at, string $reason ): array {
		return array(
			'valid'          => $valid,
			'identity_id'    => $identity_id,
			'session_uuid'   => $session_uuid,
			'status'         => $status,
			'expires_at'     => $expires_at,
			'reason'         => $reason,
			'access_granted' => false,
		);
	}

	private static function audit_context_from_session_result( array $session_result, string $result ): array {
		$context = array(
			'identity_id'    => isset( $session_result['identity_id'] ) ? (int) $session_result['identity_id'] : 0,
			'session_uuid'   => isset( $session_result['session_uuid'] ) ? (string) $session_result['session_uuid'] : '',
			'result'         => $result,
			'reason_code'    => 'validated' === $result ? '' : 'invalid_session',
			'access_granted' => false,
			'expires_at'     => isset( $session_result['expires_at'] ) ? (string) $session_result['expires_at'] : '',
		);

		return $context;
	}

	private static function log_event( string $event_type, array $context, string $severity ): void {
		if ( ! class_exists( 'Sonyra_Site_Manager_Audit_Log' ) ) {
			return;
		}

		Sonyra_Site_Manager_Audit_Log::log_event( $event_type, $context, $severity );
	}
}
