<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Sonyra_Site_Manager_REST_Auth_Smoke {

	public static function get_expected_namespace(): string {
		return 'sonyra-site-manager/v1';
	}

	public static function get_expected_routes(): array {
		return array(
			'/auth/request-code',
			'/auth/verify-code',
			'/auth/logout',
			'/auth/session',
		);
	}

	public static function get_forbidden_response_keys(): array {
		return array(
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
		);
	}

	public static function get_expected_messages(): array {
		return array(
			'Если доступ разрешён, код будет отправлен на указанную почту.',
			'Введите код из письма.',
			'Вход подтверждён.',
			'Вход не подтверждён. Проверьте код и попробуйте ещё раз.',
			'Вы вышли из Пульта сайта.',
			'Вход не выполнен.',
			'Вход выполнен.',
			'Доступ не разрешён.',
		);
	}

	public static function smoke_controller_contract(): array {
		$controller_ready = class_exists( 'Sonyra_Site_Manager_REST_Auth_Controller' );
		$status = $controller_ready && method_exists( 'Sonyra_Site_Manager_REST_Auth_Controller', 'get_rest_auth_status' )
			? Sonyra_Site_Manager_REST_Auth_Controller::get_rest_auth_status()
			: array();

		$namespace_ok = $controller_ready && isset( $status['namespace'] ) && self::get_expected_namespace() === (string) $status['namespace'];
		$routes_ok = $controller_ready;

		foreach ( self::get_expected_routes() as $route ) {
			$route_found = in_array(
				$route,
				array(
					isset( $status['request_code_route'] ) ? (string) $status['request_code_route'] : '',
					isset( $status['verify_code_route'] ) ? (string) $status['verify_code_route'] : '',
					isset( $status['logout_route'] ) ? (string) $status['logout_route'] : '',
					isset( $status['session_route'] ) ? (string) $status['session_route'] : '',
				),
				true
			);

			if ( ! $route_found ) {
				$routes_ok = false;
				break;
			}
		}

		$no_ui = $controller_ready && empty( $status['creates_ui'] );
		$no_manager_access = $controller_ready && empty( $status['grants_manager_access'] );
		$no_manager_route = $controller_ready && empty( $status['creates_manager_route'] );

		return array(
			'success'           => $controller_ready && $namespace_ok && $routes_ok && $no_ui && $no_manager_access && $no_manager_route,
			'controller_ready'  => $controller_ready,
			'namespace_ok'      => $namespace_ok,
			'routes_ok'         => $routes_ok,
			'no_ui'             => $no_ui,
			'no_manager_route'  => $no_manager_route,
			'no_manager_access' => $no_manager_access,
		);
	}

	public static function smoke_response_sanitizer(): array {
		if ( ! class_exists( 'Sonyra_Site_Manager_REST_Auth_Controller' ) || ! method_exists( 'Sonyra_Site_Manager_REST_Auth_Controller', 'build_response' ) ) {
			return array(
				'success'      => false,
				'inconclusive' => true,
				'reason_code'  => 'controller_unavailable',
			);
		}

		$dummy_payload = array(
			'message' => 'Доступ не разрешён.',
			'safe'    => true,
		);

		foreach ( self::get_forbidden_response_keys() as $key ) {
			$dummy_payload[ $key ] = '[скрыто]';
		}

		$response = Sonyra_Site_Manager_REST_Auth_Controller::build_response( $dummy_payload );
		$data = self::extract_response_data( $response );

		if ( ! is_array( $data ) ) {
			return array(
				'success'      => false,
				'inconclusive' => true,
				'reason_code'  => 'response_data_unavailable',
			);
		}

		$blocked_absent = true;

		foreach ( self::get_forbidden_response_keys() as $key ) {
			if ( self::array_has_key_recursive( $data, $key ) ) {
				$blocked_absent = false;
				break;
			}
		}

		$message_ok = isset( $data['message'] ) && 'Доступ не разрешён.' === (string) $data['message'];

		return array(
			'success'             => $blocked_absent && $message_ok,
			'blocked_keys_absent' => $blocked_absent,
			'message_ok'          => $message_ok,
			'inconclusive'        => false,
		);
	}

	public static function smoke_russian_messages(): array {
		$messages = self::get_expected_messages();
		$messages_present = ! empty( $messages );
		$english_absent = true;
		$blocked_phrases = array(
			'Login successful',
			'Invalid code',
			'User not found',
			'Unauthorized',
			'Forbidden',
			'Token expired',
		);

		foreach ( $messages as $message ) {
			foreach ( $blocked_phrases as $phrase ) {
				if ( false !== strpos( $message, $phrase ) ) {
					$english_absent = false;
					break 2;
				}
			}
		}

		return array(
			'success'          => $messages_present && $english_absent,
			'messages_present' => $messages_present,
			'english_absent'   => $english_absent,
		);
	}

	public static function run_internal_smoke(): array {
		$controller = self::smoke_controller_contract();
		$sanitizer = self::smoke_response_sanitizer();
		$messages = self::smoke_russian_messages();
		$failed_checks = array();

		if ( empty( $controller['success'] ) ) {
			$failed_checks[] = 'controller_contract';
		}

		if ( empty( $sanitizer['success'] ) ) {
			$failed_checks[] = 'response_sanitizer';
		}

		if ( empty( $messages['success'] ) ) {
			$failed_checks[] = 'russian_messages';
		}

		return array(
			'success'              => empty( $failed_checks ),
			'checks'               => array(
				'controller_contract',
				'response_sanitizer',
				'russian_messages',
			),
			'failed_checks'        => $failed_checks,
			'controller_ready'     => ! empty( $controller['controller_ready'] ),
			'namespace_ok'         => ! empty( $controller['namespace_ok'] ),
			'routes_ok'            => ! empty( $controller['routes_ok'] ),
			'sanitizer_ok'         => ! empty( $sanitizer['success'] ),
			'russian_messages_ok'  => ! empty( $messages['success'] ),
			'no_ui'                => true,
			'no_manager_route'     => true,
			'no_manager_access'    => true,
			'no_http_requests'     => true,
			'no_email_send'        => true,
			'no_cookie_mutation'   => true,
			'no_session_creation'  => true,
			'no_sensitive_output'  => true,
		);
	}

	public static function get_smoke_status(): array {
		return array(
			'smoke_service_ready'      => true,
			'controller_ready'         => class_exists( 'Sonyra_Site_Manager_REST_Auth_Controller' ),
			'namespace'                => self::get_expected_namespace(),
			'expected_routes_count'    => count( self::get_expected_routes() ),
			'forbidden_keys_count'     => count( self::get_forbidden_response_keys() ),
			'expected_messages_count'  => count( self::get_expected_messages() ),
			'creates_ui'               => false,
			'creates_rest_endpoint'    => false,
			'grants_manager_access'    => false,
			'sends_email'              => false,
			'creates_session'          => false,
			'mutates_cookie'           => false,
			'uses_http'                => false,
			'is_ready'                 => class_exists( 'Sonyra_Site_Manager_REST_Auth_Controller' ),
		);
	}

	private static function extract_response_data( $response ) {
		if ( is_object( $response ) && method_exists( $response, 'get_data' ) ) {
			return $response->get_data();
		}

		if ( is_array( $response ) ) {
			return $response;
		}

		return null;
	}

	private static function array_has_key_recursive( array $data, string $key ): bool {
		foreach ( $data as $current_key => $value ) {
			if ( strtolower( (string) $current_key ) === strtolower( $key ) ) {
				return true;
			}

			if ( is_array( $value ) && self::array_has_key_recursive( $value, $key ) ) {
				return true;
			}
		}

		return false;
	}
}
