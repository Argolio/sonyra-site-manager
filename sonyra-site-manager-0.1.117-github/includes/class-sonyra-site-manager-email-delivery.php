<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Sonyra_Site_Manager_Email_Delivery {

	const NEUTRAL_MESSAGE = 'Если доступ разрешён, код будет отправлен';

	public static function is_available(): bool {
		return function_exists( 'wp_mail' );
	}

	public static function normalize_recipient( string $email ): string {
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

	public static function send_otp_code( string $email, string $code, array $context = array() ): array {
		$recipient = self::normalize_recipient( $email );
		$masked_email = self::mask_email( $recipient );
		$context = self::get_safe_audit_context( $context, $masked_email );

		if ( '' === $recipient ) {
			self::log_delivery_event( 'auth.otp_email_failed', $context, 'invalid_recipient' );

			return self::delivery_response( false, $masked_email, 'invalid_recipient' );
		}

		if ( ! self::is_valid_code( $code ) ) {
			self::log_delivery_event( 'auth.otp_email_failed', $context, 'invalid_code' );

			return self::delivery_response( false, $masked_email, 'invalid_code' );
		}

		if ( ! self::is_available() ) {
			self::log_delivery_event( 'auth.otp_email_failed', $context, 'delivery_unavailable' );

			return self::delivery_response( false, $masked_email, 'delivery_unavailable' );
		}

		if ( ! class_exists( 'Sonyra_Site_Manager_Email_Template' ) ) {
			self::log_delivery_event( 'auth.otp_email_failed', $context, 'template_unavailable' );

			return self::delivery_response( false, $masked_email, 'template_unavailable' );
		}

		$subject = Sonyra_Site_Manager_Email_Template::get_otp_subject();
		$html_body = Sonyra_Site_Manager_Email_Template::render_otp_html( $code, $context );
		$headers = Sonyra_Site_Manager_Email_Template::get_otp_headers();
		$headers[] = 'From: ' . self::get_sender_name() . ' <' . self::get_sender_email() . '>';

		if ( '' === $html_body ) {
			self::log_delivery_event( 'auth.otp_email_failed', $context, 'template_render_failed' );

			return self::delivery_response( false, $masked_email, 'template_render_failed' );
		}

		if ( function_exists( 'add_filter' ) ) {
			add_filter( 'wp_mail_from_name', array( __CLASS__, 'get_sender_name' ), 999 );
			add_filter( 'wp_mail_from', array( __CLASS__, 'get_sender_email' ), 999 );
		}

		try {
			$sent = wp_mail( $recipient, $subject, $html_body, $headers );
		} finally {
			if ( function_exists( 'remove_filter' ) ) {
				remove_filter( 'wp_mail_from_name', array( __CLASS__, 'get_sender_name' ), 999 );
				remove_filter( 'wp_mail_from', array( __CLASS__, 'get_sender_email' ), 999 );
			}
		}

		if ( $sent ) {
			self::log_delivery_event( 'auth.otp_email_sent', $context, 'sent' );

			return self::delivery_response( true, $masked_email, '' );
		}

		self::log_delivery_event( 'auth.otp_email_failed', $context, 'send_failed' );

		return self::delivery_response( false, $masked_email, 'send_failed' );
	}

	public static function send_challenge_code( array $challenge_result ): array {
		if ( empty( $challenge_result['success'] ) ) {
			return self::delivery_response(
				false,
				isset( $challenge_result['masked_email'] ) ? (string) $challenge_result['masked_email'] : '',
				isset( $challenge_result['error_code'] ) ? (string) $challenge_result['error_code'] : 'challenge_not_ready'
			);
		}

		$recipient = isset( $challenge_result['recipient_email_internal'] ) ? (string) $challenge_result['recipient_email_internal'] : '';
		$code = isset( $challenge_result['code'] ) ? (string) $challenge_result['code'] : '';

		if ( '' === $recipient ) {
			return self::delivery_response(
				false,
				isset( $challenge_result['masked_email'] ) ? (string) $challenge_result['masked_email'] : '',
				'recipient_required'
			);
		}

		$context = array(
			'purpose'        => isset( $challenge_result['purpose'] ) ? (string) $challenge_result['purpose'] : 'login',
			'challenge_uuid' => isset( $challenge_result['challenge_uuid'] ) ? (string) $challenge_result['challenge_uuid'] : '',
		);
		$result = self::send_otp_code( $recipient, $code, $context );

		unset( $code );
		unset( $challenge_result['code'] );
		unset( $challenge_result['recipient_email_internal'] );

		return $result;
	}

	public static function get_delivery_status(): array {
		$is_ready = self::is_available() && class_exists( 'Sonyra_Site_Manager_Email_Template' );

		return array(
			'available'     => self::is_available(),
			'channel'       => 'email',
			'provider'      => 'wp_mail',
			'supports_html' => true,
			'is_ready'      => $is_ready,
		);
	}

	public static function get_sender_name(): string {
		return 'ПУЛЬТ САЙТА';
	}

	public static function get_sender_email(): string {
		$host = '';

		if ( function_exists( 'wp_parse_url' ) && function_exists( 'home_url' ) ) {
			$host = (string) wp_parse_url( home_url(), PHP_URL_HOST );
		}

		$host = strtolower( preg_replace( '/^www\\./', '', trim( $host ) ) );
		$host = preg_replace( '/[^a-z0-9.-]/', '', $host );

		if ( '' === $host || false === strpos( $host, '.' ) ) {
			$admin_email = function_exists( 'get_option' ) ? (string) get_option( 'admin_email', '' ) : '';
			$admin_email = self::normalize_recipient( $admin_email );

			if ( '' !== $admin_email ) {
				return $admin_email;
			}

			return 'wordpress@localhost.local';
		}

		return 'wordpress@' . $host;
	}

	private static function mask_email( string $email ): string {
		if ( class_exists( 'Sonyra_Site_Manager_Email_OTP' ) ) {
			return Sonyra_Site_Manager_Email_OTP::mask_email( $email );
		}

		$email = self::normalize_recipient( $email );

		if ( '' === $email || false === strpos( $email, '@' ) ) {
			return '';
		}

		list( $local, $domain ) = explode( '@', $email, 2 );
		$domain_parts = explode( '.', $domain );
		$domain_name = $domain_parts[0] ?? '';
		$domain_zone = count( $domain_parts ) > 1 ? '.' . end( $domain_parts ) : '';

		return substr( $local, 0, 1 ) . '***@' . substr( $domain_name, 0, 1 ) . '***' . $domain_zone;
	}

	private static function get_safe_audit_context( array $context, string $masked_email ): array {
		if ( class_exists( 'Sonyra_Site_Manager_Email_Template' ) ) {
			$context = Sonyra_Site_Manager_Email_Template::get_safe_context( $context );
		}

		$context['masked_email'] = $masked_email;
		$context['delivery_channel'] = 'email';
		$context['purpose'] = isset( $context['purpose'] ) ? (string) $context['purpose'] : 'login';
		$context['challenge_uuid'] = isset( $context['challenge_uuid'] ) ? (string) $context['challenge_uuid'] : '';

		return $context;
	}

	private static function log_delivery_event( string $event_type, array $context, string $result ): void {
		if ( ! class_exists( 'Sonyra_Site_Manager_Audit_Log' ) ) {
			return;
		}

		$context['result'] = $result;

		if ( 'sent' !== $result ) {
			$context['error_code'] = $result;
		}

		Sonyra_Site_Manager_Audit_Log::log_event( $event_type, $context, 'sent' === $result ? 'info' : 'warning' );
	}

	private static function delivery_response( bool $success, string $masked_email, string $error_code ): array {
		return array(
			'success'         => $success,
			'masked_email'    => $masked_email,
			'error_code'      => $error_code,
			'neutral_message' => self::NEUTRAL_MESSAGE,
		);
	}

	private static function is_valid_code( string $code ): bool {
		return 1 === preg_match( '/^\d{6}$/', $code );
	}
}
