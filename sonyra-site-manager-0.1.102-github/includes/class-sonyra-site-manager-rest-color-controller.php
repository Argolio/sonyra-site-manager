<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Sonyra_Site_Manager_REST_Color_Controller {

	const REST_NAMESPACE = 'sonyra-site-manager/v1';

	public static function register_routes(): void {
		register_rest_route(
			self::REST_NAMESPACE,
			'/design/colors',
			array(
				array(
					'methods'             => 'GET',
					'callback'            => array( __CLASS__, 'get_data' ),
					'permission_callback' => array( __CLASS__, 'permission_manage_design' ),
				),
			)
		);

		register_rest_route(
			self::REST_NAMESPACE,
			'/design/colors/(?P<entity_type>colors|gradients|patterns)',
			array(
				array(
					'methods'             => 'POST',
					'callback'            => array( __CLASS__, 'create_item' ),
					'permission_callback' => array( __CLASS__, 'permission_manage_design_with_nonce' ),
				),
			)
		);

		register_rest_route(
			self::REST_NAMESPACE,
			'/design/colors/(?P<entity_type>colors|gradients|patterns)/(?P<item_id>[a-z0-9_-]+)',
			array(
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( __CLASS__, 'update_item' ),
					'permission_callback' => array( __CLASS__, 'permission_manage_design_with_nonce' ),
				),
				array(
					'methods'             => 'DELETE',
					'callback'            => array( __CLASS__, 'delete_item' ),
					'permission_callback' => array( __CLASS__, 'permission_manage_design_with_nonce' ),
				),
			)
		);

		register_rest_route(
			self::REST_NAMESPACE,
			'/design/colors/media',
			array(
				array(
					'methods'             => 'POST',
					'callback'            => array( __CLASS__, 'upload_media' ),
					'permission_callback' => array( __CLASS__, 'permission_manage_design_with_nonce' ),
				),
			)
		);

		register_rest_route(
			self::REST_NAMESPACE,
			'/design/colors/media-library',
			array(
				array(
					'methods'             => 'GET',
					'callback'            => array( __CLASS__, 'list_media_library' ),
					'permission_callback' => array( __CLASS__, 'permission_manage_design' ),
				),
			)
		);
	}

	public static function permission_manage_design( WP_REST_Request $request ) {
		unset( $request );

		$context = self::get_auth_context();

		if ( empty( $context['authenticated'] ) || ! class_exists( 'Sonyra_Site_Manager_Auth_Access_Guard' ) || ! Sonyra_Site_Manager_Auth_Access_Guard::can_manage_settings( $context ) ) {
			return new WP_Error( 'sonyra_color_controller_forbidden', self::text( 'manager.design.colors.forbidden' ), array( 'status' => 403 ) );
		}

		return true;
	}

	public static function permission_manage_design_with_nonce( WP_REST_Request $request ) {
		$permission = self::permission_manage_design( $request );

		if ( is_wp_error( $permission ) ) {
			return $permission;
		}

		$context = self::get_auth_context();
		$nonce   = (string) $request->get_header( 'x-sonyra-color-controller-nonce' );

		if ( ! class_exists( 'Sonyra_Site_Manager_Color_Controller' ) || ! Sonyra_Site_Manager_Color_Controller::verify_nonce( $nonce, $context ) ) {
			return new WP_Error( 'sonyra_color_controller_invalid_nonce', self::text( 'manager.design.colors.invalid_nonce' ), array( 'status' => 403 ) );
		}

		return true;
	}

	public static function get_data( WP_REST_Request $request ) {
		unset( $request );

		return rest_ensure_response( self::build_response() );
	}

	public static function create_item( WP_REST_Request $request ) {
		$entity_type = self::normalize_entity_type( (string) $request->get_param( 'entity_type' ) );
		$payload     = $request->get_json_params();
		$item_data   = isset( $payload['item'] ) && is_array( $payload['item'] ) ? $payload['item'] : array();
		$saved       = Sonyra_Site_Manager_Color_Controller::create_item( $entity_type, $item_data );

		if ( is_wp_error( $saved ) ) {
			return $saved;
		}

		self::log_event( 'design.colors.' . $entity_type . '.created', $saved['item'] );

		return rest_ensure_response(
			self::build_response(
				self::text( 'manager.design.colors.' . $entity_type . '_created_notice' ),
				$saved['item'],
				$saved['data'],
				$saved['summary']
			)
		);
	}

	public static function update_item( WP_REST_Request $request ) {
		$entity_type = self::normalize_entity_type( (string) $request->get_param( 'entity_type' ) );
		$item_id     = (string) $request->get_param( 'item_id' );
		$payload     = $request->get_json_params();
		$item_data   = isset( $payload['item'] ) && is_array( $payload['item'] ) ? $payload['item'] : array();
		$saved       = Sonyra_Site_Manager_Color_Controller::update_item( $entity_type, $item_id, $item_data );

		if ( is_wp_error( $saved ) ) {
			return $saved;
		}

		self::log_event( 'design.colors.' . $entity_type . '.updated', $saved['item'] );

		return rest_ensure_response(
			self::build_response(
				self::text( 'manager.design.colors.' . $entity_type . '_updated_notice' ),
				$saved['item'],
				$saved['data'],
				$saved['summary']
			)
		);
	}

	public static function delete_item( WP_REST_Request $request ) {
		$entity_type = self::normalize_entity_type( (string) $request->get_param( 'entity_type' ) );
		$item_id     = (string) $request->get_param( 'item_id' );
		$deleted     = Sonyra_Site_Manager_Color_Controller::delete_item( $entity_type, $item_id );

		if ( is_wp_error( $deleted ) ) {
			return $deleted;
		}

		self::log_event( 'design.colors.' . $entity_type . '.deleted', $deleted['item'] );

		return rest_ensure_response(
			self::build_response(
				self::text( 'manager.design.colors.' . $entity_type . '_deleted_notice' ),
				$deleted['item'],
				$deleted['data'],
				$deleted['summary']
			)
		);
	}

	public static function upload_media( WP_REST_Request $request ) {
		$file_params = $request->get_file_params();
		$file        = isset( $file_params['file'] ) && is_array( $file_params['file'] ) ? $file_params['file'] : array();

		if ( empty( $file ) || empty( $file['tmp_name'] ) ) {
			return new WP_Error( 'sonyra_color_controller_media_missing', self::text( 'manager.design.colors.media_upload_missing' ), array( 'status' => 400 ) );
		}

		if ( ! empty( $file['error'] ) ) {
			return new WP_Error( 'sonyra_color_controller_media_failed', self::text( 'manager.design.colors.media_upload_failed' ), array( 'status' => 400 ) );
		}

		if ( empty( $file['type'] ) || ! Sonyra_Site_Manager_Color_Controller::is_allowed_image_mime_type( (string) $file['type'] ) ) {
			return new WP_Error( 'sonyra_color_controller_media_type_invalid', self::text( 'manager.design.colors.validation_image_type_invalid' ), array( 'status' => 400 ) );
		}

		if ( ! empty( $file['size'] ) && (int) $file['size'] > Sonyra_Site_Manager_Color_Controller::get_max_image_upload_size_bytes() ) {
			return new WP_Error( 'sonyra_color_controller_media_too_large', self::text( 'manager.design.colors.validation_image_size_invalid' ), array( 'status' => 400 ) );
		}

		if ( ! function_exists( 'wp_handle_upload' ) || ! function_exists( 'wp_insert_attachment' ) ) {
			return new WP_Error( 'sonyra_color_controller_media_unavailable', self::text( 'manager.design.colors.media_upload_failed' ), array( 'status' => 500 ) );
		}

		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';

		$overrides = array(
			'test_form' => false,
			'mimes'     => array(
				'jpg|jpeg' => 'image/jpeg',
				'png'      => 'image/png',
				'webp'     => 'image/webp',
			),
		);
		$uploaded  = wp_handle_upload( $file, $overrides );

		if ( ! empty( $uploaded['error'] ) || empty( $uploaded['file'] ) || empty( $uploaded['url'] ) ) {
			return new WP_Error( 'sonyra_color_controller_media_failed', self::text( 'manager.design.colors.media_upload_failed' ), array( 'status' => 500 ) );
		}

		$attachment_id = wp_insert_attachment(
			array(
				'post_mime_type' => sanitize_text_field( (string) $uploaded['type'] ),
				'post_title'     => sanitize_text_field( pathinfo( (string) $file['name'], PATHINFO_FILENAME ) ),
				'post_status'    => 'inherit',
			),
			$uploaded['file']
		);

		if ( is_wp_error( $attachment_id ) || ! $attachment_id ) {
			return new WP_Error( 'sonyra_color_controller_media_failed', self::text( 'manager.design.colors.media_upload_failed' ), array( 'status' => 500 ) );
		}

		$metadata = wp_generate_attachment_metadata( $attachment_id, $uploaded['file'] );

		if ( is_array( $metadata ) ) {
			wp_update_attachment_metadata( $attachment_id, $metadata );
		}

		$item = self::build_media_item( $attachment_id );

		if ( empty( $item ) ) {
			return new WP_Error( 'sonyra_color_controller_media_failed', self::text( 'manager.design.colors.media_upload_failed' ), array( 'status' => 500 ) );
		}

		return rest_ensure_response(
			array(
				'success' => true,
				'message' => self::text( 'manager.design.colors.media_upload_success' ),
				'item'    => $item,
			)
		);
	}

	public static function list_media_library( WP_REST_Request $request ) {
		$search = sanitize_text_field( (string) $request->get_param( 'search' ) );
		$query  = new WP_Query(
			array(
				'post_type'      => 'attachment',
				'post_status'    => 'inherit',
				'post_mime_type' => Sonyra_Site_Manager_Color_Controller::get_allowed_image_mime_types(),
				'posts_per_page' => 24,
				'orderby'        => 'date',
				'order'          => 'DESC',
				's'              => $search,
				'fields'         => 'ids',
			)
		);
		$items  = array();

		foreach ( $query->posts as $attachment_id ) {
			$item = self::build_media_item( (int) $attachment_id );

			if ( ! empty( $item ) ) {
				$items[] = $item;
			}
		}

		return rest_ensure_response(
			array(
				'success' => true,
				'items'   => $items,
			)
		);
	}

	private static function build_response( string $message = '', array $item = array(), array $data = array(), array $summary = array() ): array {
		if ( empty( $data ) ) {
			$data = class_exists( 'Sonyra_Site_Manager_Color_Controller' ) ? Sonyra_Site_Manager_Color_Controller::get_data() : array();
		}

		if ( empty( $summary ) ) {
			$summary = class_exists( 'Sonyra_Site_Manager_Color_Controller' ) ? Sonyra_Site_Manager_Color_Controller::get_summary( $data ) : array();
		}

		return array(
			'success'    => true,
			'message'    => $message,
			'item'       => $item,
			'summary'    => $summary,
			'data'       => $data,
			'library'    => $data,
			'updated_at' => isset( $data['library_meta']['updated_at'] ) ? sanitize_text_field( (string) $data['library_meta']['updated_at'] ) : '',
		);
	}

	private static function normalize_entity_type( string $entity_type ): string {
		$entity_type = strtolower( trim( $entity_type ) );

		if ( 'colors' === $entity_type ) {
			return 'color';
		}

		if ( 'gradients' === $entity_type ) {
			return 'gradient';
		}

		if ( 'patterns' === $entity_type ) {
			return 'pattern';
		}

		return $entity_type;
	}

	private static function log_event( string $event_key, array $item ): void {
		if ( ! class_exists( 'Sonyra_Site_Manager_Audit_Log' ) ) {
			return;
		}

		Sonyra_Site_Manager_Audit_Log::log_event(
			$event_key,
			array(
				'id'   => isset( $item['id'] ) ? (string) $item['id'] : '',
				'role' => isset( $item['role'] ) ? (string) $item['role'] : '',
				'type' => isset( $item['type'] ) ? (string) $item['type'] : '',
			),
			'info'
		);
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

	private static function build_media_item( int $attachment_id ): array {
		$attachment_id = absint( $attachment_id );

		if ( $attachment_id <= 0 ) {
			return array();
		}

		$url       = wp_get_attachment_url( $attachment_id );
		$thumb_url = function_exists( 'wp_get_attachment_image_url' ) ? wp_get_attachment_image_url( $attachment_id, 'medium' ) : '';
		$mime_type = (string) get_post_mime_type( $attachment_id );
		$metadata  = wp_get_attachment_metadata( $attachment_id );

		if ( empty( $url ) || ! Sonyra_Site_Manager_Color_Controller::is_allowed_image_mime_type( $mime_type ) ) {
			return array();
		}

		return array(
			'attachment_id' => $attachment_id,
			'url'           => esc_url_raw( $url ),
			'thumb_url'     => esc_url_raw( $thumb_url ? $thumb_url : $url ),
			'name'          => sanitize_text_field( get_the_title( $attachment_id ) ),
			'mime_type'     => sanitize_text_field( $mime_type ),
			'width'         => isset( $metadata['width'] ) ? (int) $metadata['width'] : 0,
			'height'        => isset( $metadata['height'] ) ? (int) $metadata['height'] : 0,
		);
	}
}

if ( function_exists( 'add_action' ) ) {
	add_action( 'rest_api_init', array( 'Sonyra_Site_Manager_REST_Color_Controller', 'register_routes' ) );
}
