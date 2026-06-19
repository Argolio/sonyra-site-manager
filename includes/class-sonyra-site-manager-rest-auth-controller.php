<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Sonyra_Site_Manager_REST_Auth_Controller {

	const REST_NAMESPACE = 'sonyra-site-manager/v1';

	private static $blocked_response_keys = array(
		'session_token',
		'session_token_hash',
		'token',
		'raw_token',
		'code',
		'code_hash',
		'email',
		'email_hash',
		'raw_email',
		'otp',
		'secret',
		'nonce',
		'cookie',
		'authorization',
		'debug',
		'attempts',
		'locked_until',
		'internal_reason',
		'internal_identity_id',
		'access_code_hash',
		'device_token_hash',
		'metadata',
	);

	public static function register_routes(): void {
		register_rest_route(
			self::REST_NAMESPACE,
			'/auth/request-code',
			array(
				'methods'             => 'POST',
				'callback'            => array( __CLASS__, 'request_code' ),
				'permission_callback' => array( __CLASS__, 'permission_public' ),
			)
		);

		register_rest_route(
			self::REST_NAMESPACE,
			'/auth/verify-code',
			array(
				'methods'             => 'POST',
				'callback'            => array( __CLASS__, 'verify_code' ),
				'permission_callback' => array( __CLASS__, 'permission_public' ),
			)
		);

		register_rest_route(
			self::REST_NAMESPACE,
			'/auth/setup-device-code',
			array(
				'methods'             => 'POST',
				'callback'            => array( __CLASS__, 'setup_device_code' ),
				'permission_callback' => array( __CLASS__, 'permission_public' ),
			)
		);

		register_rest_route(
			self::REST_NAMESPACE,
			'/auth/device-code-login',
			array(
				'methods'             => 'POST',
				'callback'            => array( __CLASS__, 'device_code_login' ),
				'permission_callback' => array( __CLASS__, 'permission_public' ),
			)
		);

		register_rest_route(
			self::REST_NAMESPACE,
			'/auth/logout',
			array(
				'methods'             => 'POST',
				'callback'            => array( __CLASS__, 'logout' ),
				'permission_callback' => array( __CLASS__, 'permission_logout' ),
			)
		);

		register_rest_route(
			self::REST_NAMESPACE,
			'/auth/session',
			array(
				'methods'             => 'GET',
				'callback'            => array( __CLASS__, 'session' ),
				'permission_callback' => array( __CLASS__, 'permission_public' ),
			)
		);
	}

	public static function permission_public(): bool {
		return true;
	}

	public static function permission_logout(): bool {
		// Logout на этом этапе idempotent; CSRF/nonce для UI-flow будет подключён отдельным этапом, метод не раскрывает детали токена или сессии.
		return true;
	}

	public static function request_code( WP_REST_Request $request ) {
		$email = self::sanitize_email_input( $request->get_param( 'email' ) );
		$identifier = self::sanitize_identifier_input( $request->get_param( 'identifier' ) );
		$request_identifier = '' !== $email ? $email : $identifier;
		$request_result = array();
		$context = array(
			'source' => 'rest_auth_request_code',
			'route'  => 'auth/request-code',
		);

		if ( class_exists( 'Sonyra_Site_Manager_OTP_Request' ) ) {
			$request_result = Sonyra_Site_Manager_OTP_Request::request_login_code( $request_identifier, $context );
			self::log_event( 'auth.rest_request_code', array( 'route' => 'auth/request-code', 'result' => 'neutral_response', 'reason_code' => '', 'access_granted' => false ), 'info' );
		} else {
			self::log_event( 'auth.rest_error', array( 'route' => 'auth/request-code', 'result' => 'service_unavailable', 'reason_code' => 'otp_request_unavailable', 'access_granted' => false ), 'warning' );
			$request_result = array( 'reason_code' => 'technical' );
		}

		$challenge_uuid = self::extract_challenge_uuid_from_result( $request_result );
		$reason_code = self::extract_request_reason_code_from_result( $request_result );
		$response_data = self::build_request_code_response_data( $challenge_uuid, $reason_code );

		if ( '' !== $challenge_uuid ) {
			$response_data['challenge_uuid'] = $challenge_uuid;
		}

		return self::build_response( $response_data );
	}

	public static function verify_code( WP_REST_Request $request ) {
		$challenge_uuid = self::sanitize_challenge_uuid( $request->get_param( 'challenge_uuid' ) );
		$code = self::sanitize_code_input( $request->get_param( 'code' ) );
		$remember_device = self::sanitize_bool_input( $request->get_param( 'remember_device' ) );
		$context = array(
			'source' => 'rest_auth_verify_code',
			'route'  => 'auth/verify-code',
		);

		if ( '' === $challenge_uuid || 6 !== strlen( $code ) ) {
			unset( $code );
			self::log_event( 'auth.rest_verify_code', array( 'route' => 'auth/verify-code', 'result' => 'failed', 'authenticated' => false, 'access_granted' => false, 'reason_code' => 'invalid_input' ), 'warning' );

			return self::build_verify_failure_response( 'verify_failed' );
		}

		if ( ! class_exists( 'Sonyra_Site_Manager_Auth_Login' ) || ! class_exists( 'Sonyra_Site_Manager_Auth_Cookie' ) ) {
			unset( $code );
			self::log_event( 'auth.rest_error', array( 'route' => 'auth/verify-code', 'result' => 'service_unavailable', 'authenticated' => false, 'access_granted' => false, 'reason_code' => 'auth_service_unavailable' ), 'warning' );

			return self::build_verify_failure_response( 'verify_failed' );
		}

		$login_result = Sonyra_Site_Manager_Auth_Login::complete_login_with_otp( $challenge_uuid, $code, $context );
		unset( $code );

		$verified = ! empty( $login_result['verified'] );
		$session_created = ! empty( $login_result['session_created'] );
		$session_token = isset( $login_result['session_token'] ) ? (string) $login_result['session_token'] : '';
		$expires_at = isset( $login_result['expires_at'] ) ? (string) $login_result['expires_at'] : '';
		$identity_id = isset( $login_result['internal_identity_id'] ) ? (int) $login_result['internal_identity_id'] : 0;

		unset( $login_result['session_token'] );

		if ( $verified && $session_created && '' !== $session_token ) {
			$cookie_set = Sonyra_Site_Manager_Auth_Cookie::set_auth_cookie( $session_token, $expires_at );
			unset( $session_token );

			if ( $cookie_set ) {
				$device_result = array( 'created' => false, 'cookie_set' => false, 'error_code' => '' );

				if ( $remember_device && $identity_id > 0 && class_exists( 'Sonyra_Site_Manager_Auth_Trusted_Devices' ) ) {
					$device_result = Sonyra_Site_Manager_Auth_Trusted_Devices::remember_current_device( $identity_id );
				}

				self::log_event( 'auth.rest_verify_code', array( 'route' => 'auth/verify-code', 'result' => 'authenticated', 'authenticated' => true, 'access_granted' => true, 'reason_code' => 'session_ready' ), 'info' );

				if ( $remember_device ) {
					if ( ! empty( $device_result['created'] ) && ! empty( $device_result['cookie_set'] ) ) {
						return self::build_response(
							array(
								'success'                    => true,
								'authenticated'              => true,
								'access_granted'             => true,
								'message'                    => self::text( 'login.verify_success', '' ),
								'next_step'                  => 'setup_device_code',
								'device_code_setup_required' => true,
							)
						);
					}

					return self::build_response(
						array(
							'success'                => true,
							'authenticated'          => true,
							'access_granted'         => true,
							'message'                => self::text( 'login.remember_device_failed_title', '' ),
							'hint'                   => self::text( 'login.remember_device_failed_hint', '' ),
							'next_step'              => 'session_ready',
							'device_remember_failed' => true,
						)
					);
				}

				return self::build_response(
					array(
						'success'        => true,
						'authenticated'  => true,
						'access_granted' => true,
						'message'        => self::text( 'login.success_status', '' ),
						'next_step'      => 'session_ready',
					)
				);
			}
		}

		unset( $session_token );
		$error_code = self::normalize_verify_error_code( isset( $login_result['error_code'] ) ? (string) $login_result['error_code'] : '' );
		self::log_event( 'auth.rest_verify_code', array( 'route' => 'auth/verify-code', 'result' => 'failed', 'authenticated' => false, 'access_granted' => false, 'reason_code' => $error_code ), 'warning' );

		return self::build_verify_failure_response( $error_code );
	}

	public static function setup_device_code( WP_REST_Request $request ) {
		$code = self::sanitize_device_code_input( $request->get_param( 'code' ) );
		$code_confirm = self::sanitize_device_code_input( $request->get_param( 'code_confirm' ) );

		if ( ! self::is_valid_device_code( $code ) || ! self::is_valid_device_code( $code_confirm ) ) {
			unset( $code, $code_confirm );
			return self::build_device_code_failure_response( 'invalid_format', 'setup' );
		}

		if ( $code !== $code_confirm ) {
			unset( $code, $code_confirm );
			return self::build_device_code_failure_response( 'mismatch', 'setup' );
		}

		if ( ! class_exists( 'Sonyra_Site_Manager_Auth_Cookie' ) || ! class_exists( 'Sonyra_Site_Manager_Auth_Trusted_Devices' ) ) {
			unset( $code, $code_confirm );
			return self::build_device_code_failure_response( 'technical', 'setup' );
		}

		$cookie_result = Sonyra_Site_Manager_Auth_Cookie::validate_cookie();

		if ( empty( $cookie_result['valid'] ) ) {
			unset( $code, $code_confirm );
			return self::build_device_code_failure_response( 'device_unavailable', 'setup' );
		}

		$identity_id = isset( $cookie_result['identity_id'] ) ? (int) $cookie_result['identity_id'] : 0;
		$setup_result = Sonyra_Site_Manager_Auth_Trusted_Devices::setup_access_code_for_current_device( $identity_id, $code );
		unset( $code, $code_confirm );

		if ( ! empty( $setup_result['success'] ) ) {
			self::log_event( 'auth.rest_device_code_setup', array( 'route' => 'auth/setup-device-code', 'result' => 'saved', 'authenticated' => true, 'access_granted' => false, 'reason_code' => '' ), 'info' );

			return self::build_response(
				array(
					'success'   => true,
					'message'   => self::text( 'login.device_code_setup_saved', '' ),
					'hint'      => self::text( 'login.device_code_setup_saved_hint', '' ),
					'next_step' => 'device_code_ready',
				)
			);
		}

		$reason = isset( $setup_result['reason'] ) ? (string) $setup_result['reason'] : 'technical';
		self::log_event( 'auth.rest_device_code_setup', array( 'route' => 'auth/setup-device-code', 'result' => 'failed', 'authenticated' => true, 'access_granted' => false, 'reason_code' => $reason ), 'warning' );

		return self::build_device_code_failure_response( in_array( $reason, array( 'invalid_format', 'device_unavailable' ), true ) ? $reason : 'technical', 'setup' );
	}

	public static function device_code_login( WP_REST_Request $request ) {
		$code = self::sanitize_device_code_input( $request->get_param( 'code' ) );

		if ( ! self::is_valid_device_code( $code ) ) {
			unset( $code );
			return self::build_device_code_failure_response( 'invalid_format', 'login' );
		}

		if ( ! class_exists( 'Sonyra_Site_Manager_Auth_Trusted_Devices' ) || ! class_exists( 'Sonyra_Site_Manager_Auth_Sessions' ) || ! class_exists( 'Sonyra_Site_Manager_Auth_Cookie' ) ) {
			unset( $code );
			return self::build_device_code_failure_response( 'device_unavailable', 'login' );
		}

		$verify_result = Sonyra_Site_Manager_Auth_Trusted_Devices::verify_access_code_for_current_device( $code );
		unset( $code );

		if ( empty( $verify_result['success'] ) ) {
			$reason = isset( $verify_result['reason'] ) ? (string) $verify_result['reason'] : 'device_unavailable';
			self::log_event( 'auth.rest_device_code_login', array( 'route' => 'auth/device-code-login', 'result' => 'failed', 'authenticated' => false, 'access_granted' => false, 'reason_code' => $reason ), 'warning' );

			return self::build_device_code_failure_response( in_array( $reason, array( 'invalid_code', 'locked', 'invalid_format', 'device_unavailable' ), true ) ? $reason : 'device_unavailable', 'login' );
		}

		$identity_id = isset( $verify_result['identity_id'] ) ? (int) $verify_result['identity_id'] : 0;
		$session_result = Sonyra_Site_Manager_Auth_Sessions::create_session(
			$identity_id,
			array(
				'source' => 'device_code_login',
				'route'  => 'auth/device-code-login',
			)
		);

		$session_created = ! empty( $session_result['success'] );
		$session_token = isset( $session_result['session_token'] ) ? (string) $session_result['session_token'] : '';
		$expires_at = isset( $session_result['expires_at'] ) ? (string) $session_result['expires_at'] : '';
		unset( $session_result['session_token'] );

		if ( $session_created && '' !== $session_token && Sonyra_Site_Manager_Auth_Cookie::set_auth_cookie( $session_token, $expires_at ) ) {
			unset( $session_token );
			self::log_event( 'auth.rest_device_code_login', array( 'route' => 'auth/device-code-login', 'result' => 'authenticated', 'authenticated' => true, 'access_granted' => true, 'reason_code' => 'session_ready' ), 'info' );

			return self::build_response(
				array(
					'success'        => true,
					'authenticated'  => true,
					'access_granted' => true,
					'message'        => self::text( 'login.success_status', '' ),
					'hint'           => self::text( 'login.device_code_login_success_hint', '' ),
					'next_step'      => 'session_ready',
				)
			);
		}

		unset( $session_token );
		self::log_event( 'auth.rest_device_code_login', array( 'route' => 'auth/device-code-login', 'result' => 'failed', 'authenticated' => false, 'access_granted' => false, 'reason_code' => 'session_failed' ), 'warning' );

		return self::build_device_code_failure_response( 'device_unavailable', 'login' );
	}

	public static function logout( WP_REST_Request $request ) {
		unset( $request );

		if ( class_exists( 'Sonyra_Site_Manager_Auth_Cookie' ) ) {
			Sonyra_Site_Manager_Auth_Cookie::revoke_cookie_session();
		}

		self::log_event( 'auth.rest_logout', array( 'route' => 'auth/logout', 'result' => 'completed', 'authenticated' => false, 'access_granted' => false, 'reason_code' => '' ), 'info' );

		return self::build_response(
			array(
				'success' => true,
				'message' => self::text( 'login.logout_success', '' ),
			)
		);
	}

	public static function session( WP_REST_Request $request ) {
		unset( $request );

		if ( ! class_exists( 'Sonyra_Site_Manager_Auth_Cookie' ) ) {
			self::log_event( 'auth.rest_session_checked', array( 'route' => 'auth/session', 'result' => 'unauthenticated', 'authenticated' => false, 'access_granted' => false, 'reason_code' => 'cookie_service_unavailable' ), 'info' );

			return self::build_session_unauthenticated_response();
		}

		$cookie_result = Sonyra_Site_Manager_Auth_Cookie::validate_cookie();

		if ( empty( $cookie_result['valid'] ) ) {
			self::log_event( 'auth.rest_session_checked', array( 'route' => 'auth/session', 'result' => 'unauthenticated', 'authenticated' => false, 'access_granted' => false, 'reason_code' => 'session_invalid' ), 'info' );

			return self::build_session_unauthenticated_response();
		}

		$role_key = '';
		$access_granted = true;

		if ( class_exists( 'Sonyra_Site_Manager_Auth_Access_Guard' ) ) {
			$context = Sonyra_Site_Manager_Auth_Access_Guard::get_current_auth_context();
			$decision = Sonyra_Site_Manager_Auth_Access_Guard::can_access_manager( $context );
			$role_key = isset( $decision['role_key'] ) ? sanitize_key( (string) $decision['role_key'] ) : '';
			$access_granted = ! empty( $decision['allowed'] );
		}

		self::log_event( 'auth.rest_session_checked', array( 'route' => 'auth/session', 'result' => 'authenticated', 'authenticated' => true, 'access_granted' => $access_granted, 'reason_code' => '' ), 'info' );

		return self::build_response(
			array(
				'success'        => true,
				'authenticated'  => true,
				'access_granted' => $access_granted,
				'message'        => self::text( 'login.session_active', '' ),
				'next_step'      => 'session_ready',
				'role'           => $role_key,
			)
		);
	}

	public static function sanitize_email_input( $value ): string {
		$value = trim( strtolower( (string) $value ) );

		if ( function_exists( 'sanitize_email' ) ) {
			$value = sanitize_email( $value );
		}

		return (string) $value;
	}

	public static function sanitize_identifier_input( $value ): string {
		$value = trim( (string) $value );

		if ( function_exists( 'sanitize_text_field' ) ) {
			$value = sanitize_text_field( $value );
		}

		return substr( (string) $value, 0, 191 );
	}

	public static function sanitize_code_input( $value ): string {
		$value = preg_replace( '/\\D+/', '', (string) $value );

		return substr( (string) $value, 0, 6 );
	}

	public static function sanitize_device_code_input( $value ): string {
		$value = trim( (string) $value );

		if ( function_exists( 'sanitize_text_field' ) ) {
			$value = sanitize_text_field( $value );
		}

		return substr( (string) $value, 0, 16 );
	}

	private static function is_valid_device_code( string $code ): bool {
		return 1 === preg_match( '/^[0-9]{4}$/', $code );
	}

	public static function sanitize_bool_input( $value ): bool {
		if ( is_bool( $value ) ) {
			return $value;
		}

		if ( is_string( $value ) ) {
			return in_array( strtolower( trim( $value ) ), array( '1', 'true', 'yes', 'on' ), true );
		}

		return 1 === (int) $value;
	}

	public static function sanitize_challenge_uuid( $value ): string {
		$value = trim( (string) $value );

		if ( function_exists( 'sanitize_text_field' ) ) {
			$value = sanitize_text_field( $value );
		}

		return substr( (string) $value, 0, 100 );
	}

	private static function extract_challenge_uuid_from_result( array $result ): string {
		$paths = array(
			array( 'challenge_uuid' ),
			array( 'data', 'challenge_uuid' ),
			array( 'result', 'challenge_uuid' ),
		);

		foreach ( $paths as $path ) {
			$value = $result;

			foreach ( $path as $key ) {
				if ( ! is_array( $value ) || ! array_key_exists( $key, $value ) ) {
					$value = '';
					break;
				}

				$value = $value[ $key ];
			}

			$challenge_uuid = self::sanitize_challenge_uuid( $value );

			if ( '' !== $challenge_uuid ) {
				return $challenge_uuid;
			}
		}

		return '';
	}

	private static function extract_request_reason_code_from_result( array $result ): string {
		$paths = array(
			array( 'reason_code' ),
			array( 'data', 'reason_code' ),
			array( 'result', 'reason_code' ),
		);

		foreach ( $paths as $path ) {
			$value = $result;

			foreach ( $path as $key ) {
				if ( ! is_array( $value ) || ! array_key_exists( $key, $value ) ) {
					$value = '';
					break;
				}

				$value = $value[ $key ];
			}

			$reason_code = self::normalize_request_reason_code( $value );

			if ( '' !== $reason_code ) {
				return $reason_code;
			}
		}

		return '';
	}

	private static function build_request_code_response_data( string $challenge_uuid, string $reason_code ): array {
		if ( '' !== $challenge_uuid ) {
			return array(
				'success'   => true,
				'message'   => self::text( 'login.request_success', '' ),
				'hint'      => self::text( 'login.request_success_hint', '' ),
				'next_step' => 'enter_code',
			);
		}

		$reason_code = self::normalize_request_reason_code( $reason_code );

		if ( 'cooldown' === $reason_code ) {
			return array(
				'success'     => true,
				'message'     => self::text( 'login.request_cooldown_title', '' ),
				'hint'        => self::text( 'login.request_cooldown_hint', '' ),
				'next_step'   => 'wait',
				'reason_code' => 'cooldown',
			);
		}

		if ( 'send_window_limit' === $reason_code ) {
			return array(
				'success'     => true,
				'message'     => self::text( 'login.request_limit_title', '' ),
				'hint'        => self::text( 'login.request_limit_hint', '' ),
				'next_step'   => 'wait',
				'reason_code' => 'send_window_limit',
			);
		}

		if ( 'locked' === $reason_code ) {
			return array(
				'success'     => true,
				'message'     => self::text( 'login.request_locked_title', '' ),
				'hint'        => self::text( 'login.request_locked_hint', '' ),
				'next_step'   => 'wait',
				'reason_code' => 'locked',
			);
		}

		if ( 'technical' === $reason_code ) {
			return array(
				'success'     => true,
				'message'     => self::text( 'login.request_error', '' ),
				'hint'        => self::text( 'login.request_error_hint', '' ),
				'next_step'   => 'retry',
				'reason_code' => 'technical',
			);
		}

		return array(
			'success'   => true,
			'message'   => self::text( 'login.request_neutral_title', '' ),
			'hint'      => self::text( 'login.request_neutral_hint', '' ),
			'next_step' => 'check_mail',
		);
	}

	private static function normalize_request_reason_code( $value ): string {
		$value = sanitize_key( (string) $value );

		if ( in_array( $value, array( 'cooldown', 'send_window_limit', 'locked', 'technical' ), true ) ) {
			return $value;
		}

		return '';
	}

	public static function build_response( array $data = array(), int $status = 200 ) {
		$safe_data = self::remove_blocked_response_keys( $data );
		$response = function_exists( 'rest_ensure_response' ) ? rest_ensure_response( $safe_data ) : $safe_data;

		if ( is_object( $response ) && method_exists( $response, 'set_status' ) ) {
			$response->set_status( $status );
		}

		return $response;
	}

	public static function get_rest_auth_status(): array {
		return array(
			'controller_ready'      => true,
			'namespace'             => self::REST_NAMESPACE,
			'request_code_route'    => '/auth/request-code',
			'verify_code_route'     => '/auth/verify-code',
			'setup_device_code_route' => '/auth/setup-device-code',
			'device_code_login_route' => '/auth/device-code-login',
			'logout_route'          => '/auth/logout',
			'session_route'         => '/auth/session',
			'creates_ui'            => false,
			'grants_manager_access' => false,
			'creates_manager_route' => false,
		);
	}

	private static function build_verify_failure_response( string $error_code = 'verify_failed' ) {
		$error_code = self::normalize_verify_error_code( $error_code );

		return self::build_response(
			array(
				'success'        => false,
				'authenticated'  => false,
				'access_granted' => false,
				'error_code'     => $error_code,
				'message'        => self::text( 'login.verify_error', '' ),
				'hint'           => self::text( 'login.verify_error_hint', '' ),
			)
		);
	}

	private static function normalize_verify_error_code( string $error_code ): string {
		$error_code = sanitize_key( $error_code );
		$allowed = array(
			'invalid_code',
			'expired_code',
			'too_many_attempts',
			'challenge_not_found',
			'verify_failed',
		);

		return in_array( $error_code, $allowed, true ) ? $error_code : 'verify_failed';
	}

	private static function build_session_unauthenticated_response() {
		return self::build_response(
			array(
				'success'        => true,
				'authenticated'  => false,
				'access_granted' => false,
				'message'        => self::text( 'login.session_inactive', '' ),
			)
		);
	}

	private static function build_device_code_failure_response( string $error_code, string $mode ) {
		$error_code = self::normalize_device_code_error_code( $error_code );
		$mode = sanitize_key( $mode );
		$text = self::get_device_code_error_text( $error_code, $mode );

		return self::build_response(
			array(
				'success'        => false,
				'authenticated'  => false,
				'access_granted' => false,
				'error_code'     => $error_code,
				'message'        => $text['message'],
				'hint'           => $text['hint'],
			)
		);
	}

	private static function normalize_device_code_error_code( string $error_code ): string {
		$error_code = sanitize_key( $error_code );
		$allowed = array(
			'invalid_format',
			'mismatch',
			'invalid_code',
			'locked',
			'device_unavailable',
			'technical',
		);

		return in_array( $error_code, $allowed, true ) ? $error_code : 'technical';
	}

	private static function get_device_code_error_text( string $error_code, string $mode ): array {
		if ( 'invalid_format' === $error_code ) {
			return array(
				'message' => self::text( 'login.device_code_invalid_format', '' ),
				'hint'    => self::text( 'login.device_code_invalid_format_hint', '' ),
			);
		}

		if ( 'mismatch' === $error_code ) {
			return array(
				'message' => self::text( 'login.device_code_mismatch', '' ),
				'hint'    => self::text( 'login.device_code_mismatch_hint', '' ),
			);
		}

		if ( 'invalid_code' === $error_code ) {
			return array(
				'message' => self::text( 'login.device_code_invalid', '' ),
				'hint'    => self::text( 'login.device_code_invalid_hint', '' ),
			);
		}

		if ( 'locked' === $error_code ) {
			return array(
				'message' => self::text( 'login.device_code_locked', '' ),
				'hint'    => self::text( 'login.device_code_locked_hint', '' ),
			);
		}

		if ( 'device_unavailable' === $error_code ) {
			return 'setup' === $mode
				? array(
					'message' => self::text( 'login.remember_device_failed_title', '' ),
					'hint'    => self::text( 'login.remember_device_failed_hint_setup', '' ),
				)
				: array(
					'message' => self::text( 'login.device_code_unavailable', '' ),
					'hint'    => self::text( 'login.device_code_unavailable_hint', '' ),
				);
		}

		return array(
			'message' => self::text( 'login.device_code_setup_failed', '' ),
			'hint'    => self::text( 'login.device_code_setup_failed_hint', '' ),
		);
	}

	private static function text( string $key, string $fallback ): string {
		if ( class_exists( 'Sonyra_Site_Manager_I18n' ) ) {
			return Sonyra_Site_Manager_I18n::t( $key, $fallback );
		}

		return $fallback;
	}

	private static function remove_blocked_response_keys( array $data ): array {
		$safe_data = array();

		foreach ( $data as $key => $value ) {
			$key_string = strtolower( (string) $key );

			if ( in_array( $key_string, self::$blocked_response_keys, true ) ) {
				continue;
			}

			$safe_data[ $key ] = is_array( $value ) ? self::remove_blocked_response_keys( $value ) : $value;
		}

		return $safe_data;
	}

	private static function log_event( string $event_type, array $context, string $severity ): void {
		if ( ! class_exists( 'Sonyra_Site_Manager_Audit_Log' ) ) {
			return;
		}

		$allowed_events = array(
			'auth.rest_request_code',
			'auth.rest_verify_code',
			'auth.rest_device_code_setup',
			'auth.rest_device_code_login',
			'auth.rest_logout',
			'auth.rest_session_checked',
			'auth.rest_denied',
			'auth.rest_error',
		);

		if ( ! in_array( $event_type, $allowed_events, true ) ) {
			$event_type = 'auth.rest_error';
		}

		Sonyra_Site_Manager_Audit_Log::log_event( $event_type, self::get_safe_audit_context( $context ), $severity );
	}

	private static function get_safe_audit_context( array $context ): array {
		$allowed = array();
		$allowed_keys = array(
			'route',
			'result',
			'authenticated',
			'access_granted',
			'reason_code',
		);

		foreach ( $allowed_keys as $key ) {
			if ( array_key_exists( $key, $context ) ) {
				$allowed[ $key ] = $context[ $key ];
			}
		}

		$allowed['access_granted'] = false;

		return $allowed;
	}
}
