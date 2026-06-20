<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Sonyra_Site_Manager_OTP_Verify {

	const SUCCESS_MESSAGE = 'Код подтверждён.';
	const FAILURE_MESSAGE = 'Код не подтверждён. Проверьте код и попробуйте ещё раз.';

	public static function get_success_message(): string {
		return self::SUCCESS_MESSAGE;
	}

	public static function get_failure_message(): string {
		return self::FAILURE_MESSAGE;
	}

	public static function verify_login_code( string $challenge_uuid, string $code, array $context = array() ): array {
		$challenge_uuid = trim( $challenge_uuid );
		$code = trim( $code );
		$audit_context = self::get_safe_audit_context( $context, $challenge_uuid );

		self::log_event( 'auth.otp_verify_received', $audit_context, 'info' );

		if ( '' === $challenge_uuid || ! self::is_valid_code( $code ) || ! class_exists( 'Sonyra_Site_Manager_Email_OTP' ) ) {
			$audit_context['reason_code'] = 'verify_not_allowed_internal';
			$audit_context['result'] = 'failed';
			self::log_event( 'auth.otp_verify_failed', $audit_context, 'warning' );

			return self::build_safe_verify_response(
				array(
					'verified'   => false,
					'error_code' => 'verify_failed',
				)
			);
		}

		$permission = self::can_verify_challenge( $challenge_uuid );

		if ( empty( $permission['allowed'] ) ) {
			$audit_context['reason_code'] = (string) $permission['reason'];
			$audit_context['result'] = 'blocked';
			self::log_event( 'auth.otp_verify_blocked', $audit_context, 'warning' );

			return self::build_safe_verify_response(
				array(
					'verified'   => false,
					'error_code' => self::map_permission_error_code( (string) $permission['reason'] ),
				)
			);
		}

		$verify_result = Sonyra_Site_Manager_Email_OTP::verify_challenge( $challenge_uuid, $code );
		unset( $code );

		if ( ! empty( $verify_result['success'] ) ) {
			$identity_id = isset( $verify_result['identity_id'] ) ? (int) $verify_result['identity_id'] : 0;
			$audit_context['identity_id'] = $identity_id;
			$audit_context['result'] = 'succeeded';
			self::log_event( 'auth.otp_verify_succeeded', $audit_context, 'info' );

			return self::build_safe_verify_response(
				array(
					'verified'             => true,
					'internal_identity_id' => $identity_id,
					'challenge_uuid'       => $challenge_uuid,
				)
			);
		}

		$error_code = isset( $verify_result['error_code'] ) ? (string) $verify_result['error_code'] : '';
		$audit_context['reason_code'] = self::normalize_failure_reason( $error_code );
		$audit_context['result'] = 'failed';

		if ( 'locked' === $error_code ) {
			self::log_event( 'auth.otp_verify_blocked', $audit_context, 'warning' );
		} else {
			self::log_event( 'auth.otp_verify_failed', $audit_context, 'warning' );
		}

		return self::build_safe_verify_response(
			array(
				'verified'   => false,
				'error_code' => self::map_email_otp_error_code( $error_code ),
			)
		);
	}

	public static function can_verify_challenge( string $challenge_uuid ): array {
		$challenge_uuid = trim( $challenge_uuid );

		if ( '' === $challenge_uuid || ! class_exists( 'Sonyra_Site_Manager_Email_OTP' ) ) {
			return self::permission_response( false, 'not_ready_internal', '' );
		}

		$status = Sonyra_Site_Manager_Email_OTP::get_challenge_status( $challenge_uuid );

		if ( empty( $status['found'] ) ) {
			return self::permission_response( false, 'challenge_not_allowed_internal', '' );
		}

		$challenge_status = isset( $status['status'] ) ? (string) $status['status'] : '';

		if ( 'pending' !== $challenge_status ) {
			return self::permission_response( false, 'challenge_not_pending_internal', $challenge_status );
		}

		if ( ! empty( $status['expires_at'] ) && strtotime( (string) $status['expires_at'] ) < self::get_current_timestamp() ) {
			return self::permission_response( false, 'challenge_expired_internal', $challenge_status );
		}

		if ( ! empty( $status['locked_until'] ) && strtotime( (string) $status['locked_until'] ) > self::get_current_timestamp() ) {
			return self::permission_response( false, 'challenge_locked_internal', $challenge_status );
		}

		$attempts_count = isset( $status['attempts_count'] ) ? (int) $status['attempts_count'] : 0;
		$max_attempts = isset( $status['max_attempts'] ) ? (int) $status['max_attempts'] : 0;

		if ( $max_attempts > 0 && $attempts_count >= $max_attempts ) {
			return self::permission_response( false, 'challenge_attempts_exceeded_internal', $challenge_status );
		}

		return self::permission_response( true, '', $challenge_status );
	}

	public static function build_safe_verify_response( array $internal = array() ): array {
		$verified = isset( $internal['verified'] ) ? (bool) $internal['verified'] : false;
		$response = array(
			'success'              => true,
			'verified'             => $verified,
			'message'              => $verified ? self::get_success_message() : self::get_failure_message(),
			'error_code'           => ! $verified && isset( $internal['error_code'] ) ? sanitize_key( (string) $internal['error_code'] ) : '',
			'internal_identity_id' => $verified && isset( $internal['internal_identity_id'] ) ? (int) $internal['internal_identity_id'] : 0,
			'challenge_uuid'       => $verified && isset( $internal['challenge_uuid'] ) ? (string) $internal['challenge_uuid'] : '',
			'session_created'      => false,
			'access_granted'       => false,
		);

		return $response;
	}

	public static function get_verify_status(): array {
		$email_otp_ready = class_exists( 'Sonyra_Site_Manager_Email_OTP' );

		return array(
			'verify_service_ready' => true,
			'email_otp_ready'      => $email_otp_ready,
			'success_message_ready' => self::get_success_message() === self::SUCCESS_MESSAGE,
			'failure_message_ready' => self::get_failure_message() === self::FAILURE_MESSAGE,
			'creates_session'      => false,
			'grants_access'        => false,
			'is_ready'             => $email_otp_ready,
		);
	}

	private static function is_valid_code( string $code ): bool {
		return 1 === preg_match( '/^\d{6}$/', $code );
	}

	private static function get_safe_audit_context( array $context, string $challenge_uuid ): array {
		$allowed = array();
		$allowed_keys = array(
			'identity_id',
			'result',
			'reason_code',
		);

		foreach ( $allowed_keys as $key ) {
			if ( array_key_exists( $key, $context ) ) {
				$allowed[ $key ] = $context[ $key ];
			}
		}

		$allowed['challenge_uuid'] = $challenge_uuid;
		$allowed['session_created'] = false;
		$allowed['access_granted'] = false;

		return $allowed;
	}

	private static function normalize_failure_reason( string $reason ): string {
		if ( 'locked' === $reason ) {
			return 'challenge_locked_internal';
		}

		if ( 'expired' === $reason ) {
			return 'challenge_expired_internal';
		}

		return 'challenge_failed_internal';
	}

	private static function map_permission_error_code( string $reason ): string {
		if ( 'challenge_expired_internal' === $reason ) {
			return 'expired_code';
		}

		if ( 'challenge_locked_internal' === $reason || 'challenge_attempts_exceeded_internal' === $reason ) {
			return 'too_many_attempts';
		}

		if ( 'challenge_not_allowed_internal' === $reason || 'challenge_not_pending_internal' === $reason ) {
			return 'challenge_not_found';
		}

		return 'verify_failed';
	}

	private static function map_email_otp_error_code( string $error_code ): string {
		if ( 'expired' === $error_code ) {
			return 'expired_code';
		}

		if ( 'locked' === $error_code ) {
			return 'too_many_attempts';
		}

		if ( 'generic' === $error_code ) {
			return 'invalid_code';
		}

		return 'verify_failed';
	}

	private static function permission_response( bool $allowed, string $reason, string $status ): array {
		return array(
			'allowed' => $allowed,
			'reason'  => $reason,
			'status'  => $status,
		);
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
