<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Sonyra_Site_Manager_OTP_Request {

	const NEUTRAL_MESSAGE = 'Если доступ разрешён, код будет отправлен';

	public static function get_neutral_message(): string {
		return self::NEUTRAL_MESSAGE;
	}

	public static function request_login_code( string $email, array $context = array() ): array {
		$identifier = self::normalize_identifier( $email );
		$normalized_email = self::normalize_email( $identifier );
		$masked_email = self::mask_email( $normalized_email );
		$audit_context = self::get_safe_audit_context( $context, $masked_email );

		self::log_event( 'auth.otp_request_received', $audit_context, 'info' );

		$identity = array();

		if ( '' !== $normalized_email ) {
			$identity = self::find_active_identity( $normalized_email );
		}

		if ( empty( $identity ) ) {
			$bridge_result = self::find_or_create_trusted_wp_admin_identity( $identifier );

			if ( ! empty( $bridge_result['identity'] ) && ! empty( $bridge_result['email'] ) ) {
				$identity = $bridge_result['identity'];
				$normalized_email = self::normalize_email( (string) $bridge_result['email'] );
				$masked_email = self::mask_email( $normalized_email );
				$audit_context = self::get_safe_audit_context( $context, $masked_email );
				$audit_context['identity_id'] = isset( $identity['id'] ) ? (int) $identity['id'] : 0;
				$audit_context['result'] = ! empty( $bridge_result['created'] ) ? 'wp_admin_identity_bridge_created' : 'wp_admin_identity_bridge_reused';
				self::log_event( 'auth.otp_request_identity_bridge', $audit_context, 'info' );
			}
		}

		if ( '' === $normalized_email || empty( $identity ) ) {
			$audit_context['reason_code'] = '' === $normalized_email ? 'invalid_identifier_internal' : 'identity_not_allowed_internal';
			$audit_context['result'] = 'wp_admin_identity_bridge_denied';
			self::log_event( 'auth.otp_request_ignored', $audit_context, 'warning' );

			return self::build_neutral_response(
				array(
					'delivery_attempted' => false,
					'challenge_created'  => false,
					'reason_code'        => 'neutral_no_challenge',
					'masked_email'       => $masked_email,
				)
			);
		}

		$audit_context['identity_id'] = isset( $identity['id'] ) ? (int) $identity['id'] : 0;

		if ( ! class_exists( 'Sonyra_Site_Manager_Email_OTP' ) ) {
			$audit_context['reason_code'] = 'email_otp_unavailable_internal';
			self::log_event( 'auth.otp_request_limited', $audit_context, 'warning' );

			return self::build_neutral_response(
				array(
					'delivery_attempted' => false,
					'challenge_created'  => false,
					'reason_code'        => 'technical',
					'masked_email'       => $masked_email,
				)
			);
		}

		$challenge_result = Sonyra_Site_Manager_Email_OTP::create_challenge( $normalized_email, 'login' );

		if ( empty( $challenge_result['success'] ) ) {
			$audit_context['reason_code'] = self::normalize_limit_reason( isset( $challenge_result['error_code'] ) ? (string) $challenge_result['error_code'] : '' );
			self::log_event( 'auth.otp_request_limited', $audit_context, 'warning' );

			unset( $challenge_result['code'] );

			return self::build_neutral_response(
				array(
					'delivery_attempted' => false,
					'challenge_created'  => false,
					'reason_code'        => self::normalize_request_reason_code( isset( $challenge_result['error_code'] ) ? (string) $challenge_result['error_code'] : '' ),
					'masked_email'       => $masked_email,
				)
			);
		}

		$audit_context['challenge_uuid'] = isset( $challenge_result['challenge_uuid'] ) ? (string) $challenge_result['challenge_uuid'] : '';
		$audit_context['challenge_created'] = true;
		$audit_context['delivery_attempted'] = false;

		$delivery_attempted = false;

		if ( class_exists( 'Sonyra_Site_Manager_Email_Delivery' ) ) {
			$delivery_attempted = true;
			$delivery_payload = $challenge_result;
			$delivery_payload['recipient_email_internal'] = $normalized_email;
			$delivery_result = Sonyra_Site_Manager_Email_Delivery::send_challenge_code( $delivery_payload );
			unset( $delivery_payload['code'] );
			unset( $delivery_payload['recipient_email_internal'] );
			unset( $challenge_result['code'] );

			$audit_context['delivery_attempted'] = true;
			$audit_context['result'] = ! empty( $delivery_result['success'] ) ? 'delivery_sent' : 'delivery_failed';
			self::log_event( 'auth.otp_request_delivery_attempted', $audit_context, ! empty( $delivery_result['success'] ) ? 'info' : 'warning' );

			if ( empty( $delivery_result['success'] ) ) {
				self::log_event( 'auth.otp_request_delivery_failed', $audit_context, 'warning' );
			}
		} else {
			unset( $challenge_result['code'] );
			$audit_context['result'] = 'delivery_unavailable';
			self::log_event( 'auth.otp_request_delivery_failed', $audit_context, 'warning' );
		}

		return self::build_neutral_response(
			array(
				'delivery_attempted' => $delivery_attempted,
				'challenge_created'  => true,
				'challenge_uuid'     => isset( $challenge_result['challenge_uuid'] ) ? (string) $challenge_result['challenge_uuid'] : '',
				'reason_code'        => 'challenge_created',
				'masked_email'       => $masked_email,
			)
		);
	}

	public static function can_request_login_code( string $email ): array {
		$normalized_email = self::normalize_email( $email );

		if ( '' === $normalized_email ) {
			return self::permission_response( false, 'invalid_email_internal' );
		}

		$identity = self::find_active_identity( $normalized_email );

		if ( empty( $identity ) ) {
			return self::permission_response( false, 'identity_not_allowed_internal' );
		}

		if ( ! class_exists( 'Sonyra_Site_Manager_Email_OTP' ) ) {
			return self::permission_response( false, 'rate_limited_internal' );
		}

		$limit = Sonyra_Site_Manager_Email_OTP::can_create_challenge( $normalized_email, 'login' );

		if ( empty( $limit['allowed'] ) ) {
			$reason = isset( $limit['reason'] ) && 'locked' === (string) $limit['reason'] ? 'locked_internal' : 'rate_limited_internal';

			return self::permission_response( false, $reason );
		}

		return self::permission_response( true, '' );
	}

	public static function build_neutral_response( array $internal = array() ): array {
		$response = array(
			'success'            => true,
			'neutral_message'    => self::get_neutral_message(),
			'delivery_attempted' => isset( $internal['delivery_attempted'] ) ? (bool) $internal['delivery_attempted'] : false,
			'challenge_created'  => isset( $internal['challenge_created'] ) ? (bool) $internal['challenge_created'] : false,
			'error_code'         => '',
		);

		$reason_code = self::normalize_request_reason_code( isset( $internal['reason_code'] ) ? (string) $internal['reason_code'] : '' );

		if ( '' !== $reason_code ) {
			$response['reason_code'] = $reason_code;
		}

		if ( isset( $internal['masked_email'] ) && '' !== (string) $internal['masked_email'] ) {
			$response['masked_email'] = (string) $internal['masked_email'];
		}

		if ( ! empty( $internal['challenge_created'] ) && isset( $internal['challenge_uuid'] ) && '' !== (string) $internal['challenge_uuid'] ) {
			$response['challenge_uuid'] = (string) $internal['challenge_uuid'];
		}

		return $response;
	}

	public static function get_request_status(): array {
		$email_otp_ready = class_exists( 'Sonyra_Site_Manager_Email_OTP' );
		$email_delivery_ready = false;

		if ( class_exists( 'Sonyra_Site_Manager_Email_Delivery' ) ) {
			$delivery_status = Sonyra_Site_Manager_Email_Delivery::get_delivery_status();
			$email_delivery_ready = (bool) $delivery_status['is_ready'];
		}

		return array(
			'request_service_ready' => true,
			'email_otp_ready'       => $email_otp_ready,
			'email_delivery_ready'  => $email_delivery_ready,
			'neutral_message_ready' => self::get_neutral_message() === self::NEUTRAL_MESSAGE,
			'is_ready'              => $email_otp_ready && $email_delivery_ready,
		);
	}

	private static function normalize_email( string $email ): string {
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

	private static function normalize_identifier( string $identifier ): string {
		$identifier = trim( $identifier );

		if ( function_exists( 'sanitize_text_field' ) ) {
			$identifier = sanitize_text_field( $identifier );
		}

		return substr( $identifier, 0, 191 );
	}

	private static function mask_email( string $email ): string {
		if ( '' === $email ) {
			return '';
		}

		if ( class_exists( 'Sonyra_Site_Manager_Email_OTP' ) ) {
			return Sonyra_Site_Manager_Email_OTP::mask_email( $email );
		}

		if ( false === strpos( $email, '@' ) ) {
			return '';
		}

		list( $local, $domain ) = explode( '@', $email, 2 );
		$domain_parts = explode( '.', $domain );
		$domain_name = $domain_parts[0] ?? '';
		$domain_zone = count( $domain_parts ) > 1 ? '.' . end( $domain_parts ) : '';

		return substr( $local, 0, 1 ) . '***@' . substr( $domain_name, 0, 1 ) . '***' . $domain_zone;
	}

	private static function find_active_identity( string $email ): array {
		if ( ! class_exists( 'Sonyra_Site_Manager_Auth_Identities' ) ) {
			return array();
		}

		$identity = Sonyra_Site_Manager_Auth_Identities::find_identity_by_email( $email );

		if ( empty( $identity ) || ! is_array( $identity ) ) {
			return array();
		}

		$status = isset( $identity['status'] ) ? (string) $identity['status'] : '';

		if ( 'active' !== $status ) {
			return array();
		}

		return $identity;
	}

	private static function find_or_create_trusted_wp_admin_identity( string $identifier ): array {
		$wp_user = self::find_trusted_wp_admin_user( $identifier );

		if ( empty( $wp_user ) || empty( $wp_user->ID ) || empty( $wp_user->user_email ) || ! class_exists( 'Sonyra_Site_Manager_Auth_Identities' ) ) {
			return array();
		}

		$normalized_email = self::normalize_email( (string) $wp_user->user_email );

		if ( '' === $normalized_email ) {
			return array();
		}

		$existing_identity = Sonyra_Site_Manager_Auth_Identities::find_identity_by_wp_user_id( (int) $wp_user->ID );

		if ( empty( $existing_identity ) ) {
			$existing_identity = Sonyra_Site_Manager_Auth_Identities::find_identity_by_email( $normalized_email );
		}

		$identity_id = Sonyra_Site_Manager_Auth_Identities::create_or_update_identity(
			array(
				'email'      => $normalized_email,
				'wp_user_id' => (int) $wp_user->ID,
				'role_key'   => class_exists( 'Sonyra_Site_Manager_Auth_Roles' ) ? Sonyra_Site_Manager_Auth_Roles::ROLE_ADMIN : 'admin',
				'status'     => 'active',
				'metadata'   => array(
					'source'                => 'wordpress_administrator_bridge',
					'created_by_foundation' => false,
				),
			)
		);

		if ( $identity_id <= 0 ) {
			return array();
		}

		$identity = Sonyra_Site_Manager_Auth_Identities::find_identity_by_wp_user_id( (int) $wp_user->ID );

		if ( empty( $identity ) ) {
			$identity = Sonyra_Site_Manager_Auth_Identities::find_identity_by_email( $normalized_email );
		}

		if ( empty( $identity ) || ! is_array( $identity ) || 'active' !== (string) ( $identity['status'] ?? '' ) ) {
			return array();
		}

		return array(
			'identity' => $identity,
			'email'    => $normalized_email,
			'created'  => empty( $existing_identity ),
		);
	}

	private static function find_trusted_wp_admin_user( string $identifier ) {
		$identifier = self::normalize_identifier( $identifier );

		if ( '' === $identifier || ! function_exists( 'get_user_by' ) || ! function_exists( 'user_can' ) ) {
			return null;
		}

		$wp_user = null;
		$normalized_email = self::normalize_email( $identifier );

		if ( '' !== $normalized_email ) {
			$wp_user = get_user_by( 'email', $normalized_email );
		}

		if ( ! $wp_user ) {
			$wp_user = get_user_by( 'login', $identifier );
		}

		if ( ! $wp_user || empty( $wp_user->ID ) || ! user_can( $wp_user, 'manage_options' ) ) {
			return null;
		}

		if ( function_exists( 'is_multisite' ) && is_multisite() ) {
			if ( property_exists( $wp_user, 'spam' ) && (int) $wp_user->spam > 0 ) {
				return null;
			}

			if ( property_exists( $wp_user, 'deleted' ) && (int) $wp_user->deleted > 0 ) {
				return null;
			}
		}

		return $wp_user;
	}

	private static function get_safe_audit_context( array $context, string $masked_email ): array {
		$allowed = array();
		$allowed_keys = array(
			'challenge_uuid',
			'delivery_attempted',
			'challenge_created',
			'reason_code',
			'result',
		);

		foreach ( $allowed_keys as $key ) {
			if ( array_key_exists( $key, $context ) ) {
				$allowed[ $key ] = $context[ $key ];
			}
		}

		$allowed['masked_email'] = $masked_email;

		return $allowed;
	}

	private static function normalize_limit_reason( string $reason ): string {
		return 'locked' === $reason ? 'locked_internal' : 'rate_limited_internal';
	}

	private static function normalize_request_reason_code( string $reason ): string {
		$reason = sanitize_key( $reason );

		if ( in_array( $reason, array( 'cooldown', 'send_window_limit', 'locked' ), true ) ) {
			return $reason;
		}

		if ( in_array( $reason, array( 'table_not_ready', 'hash_failed', 'insert_failed', 'not_ready', 'technical' ), true ) ) {
			return 'technical';
		}

		if ( in_array( $reason, array( 'challenge_created', 'neutral_no_challenge' ), true ) ) {
			return $reason;
		}

		return '';
	}

	private static function permission_response( bool $allowed, string $reason ): array {
		return array(
			'allowed' => $allowed,
			'reason'  => $reason,
		);
	}

	private static function log_event( string $event_type, array $context, string $severity ): void {
		if ( ! class_exists( 'Sonyra_Site_Manager_Audit_Log' ) ) {
			return;
		}

		Sonyra_Site_Manager_Audit_Log::log_event( $event_type, $context, $severity );
	}
}
