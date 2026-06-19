<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Sonyra_Site_Manager_REST_Pages_Controller {

	const REST_NAMESPACE = 'sonyra-site-manager/v1';

	public static function register_routes(): void {
		register_rest_route(
			self::REST_NAMESPACE,
			'/pages',
			array(
				array(
					'methods'             => 'GET',
					'callback'            => array( __CLASS__, 'get_pages' ),
					'permission_callback' => array( __CLASS__, 'permission_manage_pages' ),
				),
				array(
					'methods'             => 'POST',
					'callback'            => array( __CLASS__, 'save_pages' ),
					'permission_callback' => array( __CLASS__, 'permission_manage_pages_with_nonce' ),
				),
			)
		);
	}

	public static function permission_manage_pages( WP_REST_Request $request ) {
		unset( $request );

		$context = self::get_auth_context();

		if ( empty( $context['authenticated'] ) || ! class_exists( 'Sonyra_Site_Manager_Auth_Access_Guard' ) || ! Sonyra_Site_Manager_Auth_Access_Guard::can_manage_settings( $context ) ) {
			return new WP_Error( 'sonyra_pages_forbidden', self::text( 'manager.pages.errors.forbidden' ), array( 'status' => 403 ) );
		}

		return true;
	}

	public static function permission_manage_pages_with_nonce( WP_REST_Request $request ) {
		$permission = self::permission_manage_pages( $request );

		if ( is_wp_error( $permission ) ) {
			return $permission;
		}

		$context = self::get_auth_context();
		$nonce = (string) $request->get_header( 'x-sonyra-pages-nonce' );

		if ( ! class_exists( 'Sonyra_Site_Manager_Pages_Store' ) || ! Sonyra_Site_Manager_Pages_Store::verify_nonce( $nonce, $context ) ) {
			return new WP_Error( 'sonyra_pages_invalid_nonce', self::text( 'manager.pages.errors.invalid_nonce' ), array( 'status' => 403 ) );
		}

		return true;
	}

	public static function get_pages( WP_REST_Request $request ) {
		unset( $request );

		return rest_ensure_response( self::build_response_data() );
	}

	public static function save_pages( WP_REST_Request $request ) {
		$payload = $request->get_json_params();

		if ( ! is_array( $payload ) || ! isset( $payload['items'] ) || ! is_array( $payload['items'] ) ) {
			return new WP_Error( 'sonyra_pages_payload_invalid', self::text( 'manager.pages.errors.payload_invalid' ), array( 'status' => 400 ) );
		}

		$saved = Sonyra_Site_Manager_Pages_Store::save_items( $payload['items'] );

		if ( is_wp_error( $saved ) ) {
			return $saved;
		}

		if ( class_exists( 'Sonyra_Site_Manager_Audit_Log' ) ) {
			Sonyra_Site_Manager_Audit_Log::log_event(
				'pages.saved',
				array(
					'items_count' => count( $saved['items'] ),
				),
				'info'
			);
		}

		return rest_ensure_response( self::build_response_data( self::text( 'manager.pages.success.saved' ) ) );
	}

	private static function build_response_data( string $message = '' ): array {
		$items = class_exists( 'Sonyra_Site_Manager_Pages_Store' ) ? Sonyra_Site_Manager_Pages_Store::get_items() : array();

		return array(
			'success' => true,
			'message' => $message,
			'summary' => class_exists( 'Sonyra_Site_Manager_Pages_Store' ) ? Sonyra_Site_Manager_Pages_Store::get_summary() : array(),
			'items'   => self::prepare_items_for_response( $items ),
		);
	}

	private static function prepare_items_for_response( array $items ): array {
		$prepared = array();

		foreach ( $items as $item ) {
			$item['public_url'] = Sonyra_Site_Manager_Pages_Store::get_page_url( $item );
			$item['preview_url'] = Sonyra_Site_Manager_Pages_Store::get_preview_url( $item );
			$item['can_open'] = in_array( (string) $item['status'], array( 'published', 'hidden' ), true );
			$prepared[] = $item;
		}

		return $prepared;
	}

	private static function get_auth_context(): array {
		if ( ! class_exists( 'Sonyra_Site_Manager_Auth_Access_Guard' ) ) {
			return array();
		}

		return Sonyra_Site_Manager_Auth_Access_Guard::get_current_auth_context();
	}

	private static function text( string $key ): string {
		if ( class_exists( 'Sonyra_Site_Manager_I18n' ) ) {
			return Sonyra_Site_Manager_I18n::t( $key );
		}

		return '';
	}
}
