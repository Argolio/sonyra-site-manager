<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Sonyra_Site_Manager_Auth_Login {

	const SUCCESS_MESSAGE = 'Вход подтверждён.';
	const FAILURE_MESSAGE = 'Вход не подтверждён. Проверьте код и попробуйте ещё раз.';

	public static function get_success_message(): string {
		return self::SUCCESS_MESSAGE;
	}

	public static function get_failure_message(): string {
		return self::FAILURE_MESSAGE;
	}

	public static function complete_login_with_otp( string $challenge_uuid, string $code, array $context = array() ): array {
		$audit_context = self::get_safe_audit_context( $context );
		$audit_context['session_created'] = false;
		$audit_context['access_granted'] = false;
		$audit_context['result'] = 'attempted';

		self::log_event( 'auth.login_attempted', $audit_context, 'info' );

		if ( ! class_exists( 'Sonyra_Site_Manager_OTP_Verify' ) ) {
			$audit_context['result'] = 'failed';
			$audit_context['reason_code'] = 'otp_verify_unavailable_internal';
			self::log_event( 'auth.login_failed', $audit_context, 'warning' );

			return self::build_safe_login_response(
				array(
					'verified'        => false,
					'session_created' => false,
				)
			);
		}

		$verify_result = Sonyra_Site_Manager_OTP_Verify::verify_login_code( $challenge_uuid, $code, $context );
		unset( $code );

		if ( empty( $verify_result['verified'] ) ) {
			$audit_context['result'] = 'failed';
			$audit_context['reason_code'] = 'otp_not_verified_internal';
			self::log_event( 'auth.login_failed', $audit_context, 'warning' );

			return self::build_safe_login_response(
				array(
					'verified'        => false,
					'session_created' => false,
					'error_code'      => isset( $verify_result['error_code'] ) ? (string) $verify_result['error_code'] : 'verify_failed',
				)
			);
		}

		$identity_id = isset( $verify_result['internal_identity_id'] ) ? (int) $verify_result['internal_identity_id'] : 0;

		if ( $identity_id <= 0 ) {
			$audit_context['result'] = 'failed';
			$audit_context['reason_code'] = 'identity_missing_internal';
			self::log_event( 'auth.login_failed', $audit_context, 'warning' );

			return self::build_safe_login_response(
				array(
					'verified'        => false,
					'session_created' => false,
				)
			);
		}

		if ( ! class_exists( 'Sonyra_Site_Manager_Auth_Sessions' ) ) {
			$audit_context['identity_id'] = $identity_id;
			$audit_context['result'] = 'failed';
			$audit_context['reason_code'] = 'auth_sessions_unavailable_internal';
			self::log_event( 'auth.login_failed', $audit_context, 'warning' );

			return self::build_safe_login_response(
				array(
					'verified'        => false,
					'session_created' => false,
				)
			);
		}

		$session_context = self::get_safe_session_context( $context );
		$session_result = Sonyra_Site_Manager_Auth_Sessions::create_session( $identity_id, $session_context );

		if ( empty( $session_result['success'] ) ) {
			$audit_context['identity_id'] = $identity_id;
			$audit_context['result'] = 'failed';
			$audit_context['reason_code'] = 'session_not_created_internal';
			self::log_event( 'auth.login_failed', $audit_context, 'warning' );

			return self::build_safe_login_response(
				array(
					'verified'        => false,
					'session_created' => false,
				)
			);
		}

		$session_uuid = isset( $session_result['session_uuid'] ) ? (string) $session_result['session_uuid'] : '';
		$session_token = isset( $session_result['session_token'] ) ? (string) $session_result['session_token'] : '';
		$expires_at = isset( $session_result['expires_at'] ) ? (string) $session_result['expires_at'] : '';

		$audit_context['identity_id'] = $identity_id;
		$audit_context['session_uuid'] = $session_uuid;
		$audit_context['result'] = 'session_issued';
		$audit_context['session_created'] = true;
		$audit_context['expires_at'] = $expires_at;
		self::log_event( 'auth.login_session_issued', $audit_context, 'info' );

		return self::build_safe_login_response(
			array(
				'verified'        => true,
				'session_created' => true,
				'session_uuid'    => $session_uuid,
				'internal_identity_id' => $identity_id,
				'session_token'   => $session_token,
				'expires_at'      => $expires_at,
			)
		);
	}

	public static function build_safe_login_response( array $internal = array() ): array {
		$verified = isset( $internal['verified'] ) ? (bool) $internal['verified'] : false;
		$session_created = $verified && isset( $internal['session_created'] ) ? (bool) $internal['session_created'] : false;

		return array(
			'success'         => true,
			'verified'        => $verified,
			'session_created' => $session_created,
			'access_granted'  => false,
			'message'         => $verified && $session_created ? self::get_success_message() : self::get_failure_message(),
			'error_code'      => ! $verified && isset( $internal['error_code'] ) ? sanitize_key( (string) $internal['error_code'] ) : '',
			'internal_identity_id' => $verified && $session_created && isset( $internal['internal_identity_id'] ) ? (int) $internal['internal_identity_id'] : 0,
			'session_uuid'    => $verified && $session_created && isset( $internal['session_uuid'] ) ? (string) $internal['session_uuid'] : '',
			'session_token'   => $verified && $session_created && isset( $internal['session_token'] ) ? (string) $internal['session_token'] : '',
			'expires_at'      => $verified && $session_created && isset( $internal['expires_at'] ) ? (string) $internal['expires_at'] : '',
		);
	}

	public static function get_login_status(): array {
		$otp_verify_ready = class_exists( 'Sonyra_Site_Manager_OTP_Verify' );
		$auth_session_ready = class_exists( 'Sonyra_Site_Manager_Auth_Sessions' );

		return array(
			'login_service_ready' => true,
			'otp_verify_ready'    => $otp_verify_ready,
			'auth_session_ready'  => $auth_session_ready,
			'creates_session'     => true,
			'creates_cookie'      => false,
			'grants_access'       => false,
			'uses_rest'           => false,
			'is_ready'            => $otp_verify_ready && $auth_session_ready,
		);
	}

	private static function get_safe_audit_context( array $context ): array {
		$allowed = array();
		$allowed_keys = array(
			'session_uuid',
			'identity_id',
			'result',
			'reason_code',
			'session_created',
			'expires_at',
		);

		foreach ( $allowed_keys as $key ) {
			if ( array_key_exists( $key, $context ) ) {
				$allowed[ $key ] = $context[ $key ];
			}
		}

		$allowed['access_granted'] = false;

		return $allowed;
	}

	private static function get_safe_session_context( array $context ): array {
		$unsafe_keys = array(
			'email',
			'code',
			'code_hash',
			'otp',
			'session_token',
			'session_token_hash',
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

	private static function log_event( string $event_type, array $context, string $severity ): void {
		if ( ! class_exists( 'Sonyra_Site_Manager_Audit_Log' ) ) {
			return;
		}

		Sonyra_Site_Manager_Audit_Log::log_event( $event_type, $context, $severity );
	}
}
