<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Sonyra_Site_Manager_Pages_Store {

	const OPTION_NAME = 'sonyra_site_manager_pages';
	const OPTION_VERSION = 1;
	const NONCE_ACTION = 'sonyra_pages';

	public static function get_default_data(): array {
		return array(
			'version' => self::OPTION_VERSION,
			'items'   => array(),
		);
	}

	public static function get_data(): array {
		$data = get_option( self::OPTION_NAME, self::get_default_data() );

		if ( ! is_array( $data ) ) {
			return self::get_default_data();
		}

		$items = isset( $data['items'] ) && is_array( $data['items'] ) ? $data['items'] : array();

		return array(
			'version' => self::OPTION_VERSION,
			'items'   => self::normalize_items( $items ),
		);
	}

	public static function get_items(): array {
		$data = self::get_data();

		return $data['items'];
	}

	public static function get_pages(): array {
		return self::get_items();
	}

	public static function save_items( array $items ) {
		$sanitized = self::sanitize_items( $items );

		if ( is_wp_error( $sanitized ) ) {
			return $sanitized;
		}

		$data = array(
			'version' => self::OPTION_VERSION,
			'items'   => $sanitized,
		);

		$updated = update_option( self::OPTION_NAME, $data, false );

		if ( ! $updated && self::get_data() !== $data ) {
			return new WP_Error( 'sonyra_pages_save_failed', self::text( 'manager.pages.errors.save_failed' ), array( 'status' => 500 ) );
		}

		return $data;
	}

	public static function get_public_items(): array {
		return array_values(
			array_filter(
				self::get_items(),
				static function ( array $item ): bool {
					return in_array( (string) $item['status'], array( 'published', 'hidden' ), true );
				}
			)
		);
	}

	public static function get_public_pages(): array {
		return self::get_public_items();
	}

	public static function get_menu_items(): array {
		$items = array_values(
			array_filter(
				self::get_items(),
				static function ( array $item ): bool {
					return 'published' === (string) $item['status'] && ! empty( $item['show_in_menu'] );
				}
			)
		);

		usort(
			$items,
			static function ( array $left, array $right ): int {
				$left_order = isset( $left['menu_order'] ) ? (int) $left['menu_order'] : 10;
				$right_order = isset( $right['menu_order'] ) ? (int) $right['menu_order'] : 10;

				if ( $left_order === $right_order ) {
					return strcmp( (string) $left['title'], (string) $right['title'] );
				}

				return $left_order < $right_order ? -1 : 1;
			}
		);

		return $items;
	}

	public static function get_menu_pages(): array {
		return self::get_menu_items();
	}

	public static function find_home_page(): array {
		foreach ( self::get_public_items() as $item ) {
			if ( ! empty( $item['is_home'] ) ) {
				return $item;
			}
		}

		return array();
	}

	public static function get_home_page(): ?array {
		foreach ( self::get_pages() as $item ) {
			if ( ! empty( $item['is_home'] ) ) {
				return $item;
			}
		}

		return null;
	}

	public static function get_summary(): array {
		$items = self::get_pages();
		$summary = array(
			'total'     => count( $items ),
			'published' => 0,
			'draft'     => 0,
			'hidden'    => 0,
			'has_home'  => false,
		);

		foreach ( $items as $item ) {
			$status = isset( $item['status'] ) ? (string) $item['status'] : 'draft';

			if ( isset( $summary[ $status ] ) ) {
				$summary[ $status ]++;
			}

			if ( ! empty( $item['is_home'] ) ) {
				$summary['has_home'] = true;
			}
		}

		return $summary;
	}

	public static function find_public_page_by_slug( string $slug ): array {
		$slug = self::sanitize_slug_value( $slug );

		if ( '' === $slug ) {
			return self::find_home_page();
		}

		foreach ( self::get_public_items() as $item ) {
			if ( empty( $item['is_home'] ) && $slug === (string) $item['slug'] ) {
				return $item;
			}
		}

		return array();
	}

	public static function create_nonce( array $context = array() ): string {
		$session_uuid = self::get_session_uuid_from_context( $context );

		if ( '' === $session_uuid ) {
			return '';
		}

		return self::build_nonce_for_tick( $session_uuid, self::get_nonce_tick() );
	}

	public static function verify_nonce( string $nonce, array $context = array() ): bool {
		$nonce = sanitize_text_field( $nonce );
		$session_uuid = self::get_session_uuid_from_context( $context );

		if ( '' === $nonce || '' === $session_uuid ) {
			return false;
		}

		$current_tick = self::get_nonce_tick();

		return hash_equals( self::build_nonce_for_tick( $session_uuid, $current_tick ), $nonce )
			|| hash_equals( self::build_nonce_for_tick( $session_uuid, $current_tick - 1 ), $nonce );
	}

	public static function get_page_url( array $item ): string {
		if ( ! empty( $item['is_home'] ) ) {
			return home_url( '/' );
		}

		$slug = isset( $item['slug'] ) ? (string) $item['slug'] : '';

		return '' === $slug ? home_url( '/' ) : home_url( '/' . $slug . '/' );
	}

	public static function get_preview_url( array $item ): string {
		$slug = isset( $item['slug'] ) ? self::sanitize_slug_value( (string) $item['slug'] ) : '';

		if ( '' !== $slug ) {
			return home_url( '/sonyra-preview/' . $slug . '/' );
		}

		$page_id = isset( $item['id'] ) ? sanitize_key( (string) $item['id'] ) : '';

		return '' === $page_id ? home_url( '/sonyra-preview/' ) : home_url( '/sonyra-preview/id/' . $page_id . '/' );
	}

	public static function find_page_by_id( string $page_id ): array {
		$page_id = sanitize_key( $page_id );

		if ( '' === $page_id ) {
			return array();
		}

		foreach ( self::get_items() as $item ) {
			if ( $page_id === (string) $item['id'] ) {
				return $item;
			}
		}

		return array();
	}

	public static function find_page_for_preview( string $identifier, string $mode = 'slug' ): array {
		if ( 'id' === $mode ) {
			return self::find_page_by_id( $identifier );
		}

		$identifier = self::sanitize_slug_value( $identifier );

		if ( '' === $identifier ) {
			return array();
		}

		foreach ( self::get_items() as $item ) {
			if ( $identifier === (string) $item['slug'] ) {
				return $item;
			}
		}

		return array();
	}

	private static function normalize_items( array $items ): array {
		$normalized = array();

		foreach ( $items as $item ) {
			if ( is_array( $item ) ) {
				$normalized[] = self::normalize_item( $item );
			}
		}

		return $normalized;
	}

	private static function sanitize_items( array $items ) {
		$sanitized = array();
		$used_ids = array();
		$used_slugs = array();
		$home_id = '';

		foreach ( $items as $item ) {
			if ( ! is_array( $item ) ) {
				continue;
			}

			$next_item = self::sanitize_item( $item );

			if ( is_wp_error( $next_item ) ) {
				return $next_item;
			}

			if ( isset( $used_ids[ $next_item['id'] ] ) ) {
				$next_item['id'] = self::create_id();
			}

			$used_ids[ $next_item['id'] ] = true;

				if ( ! empty( $next_item['is_home'] ) ) {
					if ( '' !== $home_id ) {
						$next_item['is_home'] = false;
					} else {
						$home_id = $next_item['id'];
					}
				}

			if ( empty( $next_item['is_home'] ) ) {
				if ( '' === $next_item['slug'] ) {
					return new WP_Error( 'sonyra_pages_slug_required', self::text( 'manager.pages.errors.slug_required' ), array( 'status' => 400 ) );
				}

				if ( isset( $used_slugs[ $next_item['slug'] ] ) ) {
					return new WP_Error( 'sonyra_pages_slug_duplicate', self::text( 'manager.pages.errors.slug_duplicate' ), array( 'status' => 400 ) );
				}

				$slug_error = self::validate_slug( $next_item['slug'] );

				if ( is_wp_error( $slug_error ) ) {
					return $slug_error;
				}

				$used_slugs[ $next_item['slug'] ] = true;
			}

			$sanitized[] = $next_item;
		}

		return $sanitized;
	}

	private static function normalize_item( array $item ): array {
		$created_at = isset( $item['created_at'] ) ? sanitize_text_field( (string) $item['created_at'] ) : '';
		$timestamp  = self::current_time_iso8601_utc();

			return array(
				'id'              => isset( $item['id'] ) && '' !== (string) $item['id'] ? sanitize_key( (string) $item['id'] ) : self::create_id(),
			'title'           => isset( $item['title'] ) ? sanitize_text_field( (string) $item['title'] ) : '',
			'slug'            => isset( $item['slug'] ) ? self::sanitize_slug_value( (string) $item['slug'] ) : '',
			'status'          => self::sanitize_status( isset( $item['status'] ) ? (string) $item['status'] : 'draft' ),
			'is_home'         => ! empty( $item['is_home'] ),
			'show_in_menu'    => ! empty( $item['show_in_menu'] ),
			'menu_title'      => isset( $item['menu_title'] ) ? sanitize_text_field( (string) $item['menu_title'] ) : '',
			'menu_order'      => isset( $item['menu_order'] ) ? max( 0, min( 999, absint( $item['menu_order'] ) ) ) : 10,
				'seo_title'       => isset( $item['seo_title'] ) ? sanitize_text_field( (string) $item['seo_title'] ) : '',
				'seo_description' => isset( $item['seo_description'] ) ? sanitize_textarea_field( (string) $item['seo_description'] ) : '',
				'sections'        => isset( $item['sections'] ) && is_array( $item['sections'] ) ? self::normalize_sections( $item['sections'] ) : array(),
				'widgets'         => isset( $item['widgets'] ) && is_array( $item['widgets'] ) ? self::normalize_widgets( $item['widgets'] ) : array(),
				'popups'          => isset( $item['popups'] ) && is_array( $item['popups'] ) ? self::normalize_popups( $item['popups'] ) : array(),
				'history'         => isset( $item['history'] ) && is_array( $item['history'] ) ? self::normalize_history( $item['history'] ) : array(),
				'created_at'      => '' !== $created_at ? $created_at : $timestamp,
				'updated_at'      => $timestamp,
			);
		}

	private static function sanitize_item( array $item ) {
		$next_item = self::normalize_item( $item );

		if ( '' === $next_item['title'] ) {
			return new WP_Error( 'sonyra_pages_title_required', self::text( 'manager.pages.errors.title_required' ), array( 'status' => 400 ) );
		}

		if ( ! in_array( $next_item['status'], array( 'draft', 'published', 'hidden' ), true ) ) {
			return new WP_Error( 'sonyra_pages_status_invalid', self::text( 'manager.pages.errors.status_invalid' ), array( 'status' => 400 ) );
		}

		if ( 'published' !== $next_item['status'] ) {
			$next_item['show_in_menu'] = false;
		}

		$next_item['updated_at'] = self::current_time_iso8601_utc();

		return $next_item;
	}

	private static function normalize_sections( array $sections ): array {
		$normalized = array();
		$used_names = array();

		foreach ( $sections as $section ) {
			if ( ! is_array( $section ) ) {
				continue;
			}

			$normalized_section = self::normalize_section( $section );
			$normalized_section['name'] = self::build_unique_title(
				'' !== $normalized_section['name'] ? $normalized_section['name'] : self::get_section_default_name( $normalized_section ),
				$used_names
			);
			$used_names[] = $normalized_section['name'];
			$normalized[] = $normalized_section;
		}

		return $normalized;
	}

	private static function normalize_history( array $history ): array {
		$normalized = array();

		foreach ( $history as $event ) {
			if ( ! is_array( $event ) ) {
				continue;
			}

			$normalized[] = array(
				'at'      => isset( $event['at'] ) ? sanitize_text_field( (string) $event['at'] ) : self::current_time_iso8601_utc(),
				'action'  => isset( $event['action'] ) ? sanitize_key( (string) $event['action'] ) : '',
				'label'   => isset( $event['label'] ) ? sanitize_text_field( (string) $event['label'] ) : '',
				'details' => isset( $event['details'] ) ? sanitize_text_field( (string) $event['details'] ) : '',
			);
		}

		return array_slice( $normalized, 0, 50 );
	}

	private static function normalize_widgets( array $widgets ): array {
		$normalized = array();
		$used_names = array();

		foreach ( $widgets as $widget ) {
			if ( ! is_array( $widget ) ) {
				continue;
			}

			$normalized_widget = self::normalize_widget( $widget );
			$normalized_widget['name'] = self::build_unique_title(
				'' !== $normalized_widget['name'] ? $normalized_widget['name'] : self::get_widget_default_name( $normalized_widget['type'] ),
				$used_names
			);
			$used_names[] = $normalized_widget['name'];
			$normalized[] = $normalized_widget;
		}

		return $normalized;
	}

	private static function normalize_popups( array $popups ): array {
		$normalized = array();
		$used_names = array();

		foreach ( $popups as $popup ) {
			if ( ! is_array( $popup ) ) {
				continue;
			}

			$normalized_popup = self::normalize_popup( $popup );
			$normalized_popup['name'] = self::build_unique_title(
				'' !== $normalized_popup['name'] ? $normalized_popup['name'] : self::get_popup_default_name( $normalized_popup['type'] ),
				$used_names
			);
			$used_names[] = $normalized_popup['name'];
			$normalized[] = $normalized_popup;
		}

		return $normalized;
	}

	private static function normalize_section( array $section ): array {
		$type            = isset( $section['type'] ) ? sanitize_key( (string) $section['type'] ) : 'text';
		$definition      = self::get_section_definition( $type );
		$is_known_type   = ! empty( $definition );
		$blocks          = isset( $section['blocks'] ) && is_array( $section['blocks'] ) ? self::normalize_blocks( $section['blocks'], $type ) : array();
		$default_section = $is_known_type ? self::get_section_defaults( $type ) : array();
		$passthrough     = self::extract_passthrough_fields(
			$section,
			array(
				'id',
				'type',
				'name',
				'blocks',
				'title',
				'kicker',
				'text',
				'button_label',
				'button_url',
				'email',
				'phone',
				'address',
				'description',
				'provider',
				'module_key',
				'unavailable',
			)
		);

		if ( empty( $blocks ) && self::has_legacy_section_content( $section ) ) {
			$blocks[] = self::normalize_block(
				array(
					'type'         => self::get_default_block_type( $type ),
					'heading'      => isset( $section['title'] ) ? $section['title'] : '',
					'kicker'       => isset( $section['kicker'] ) ? $section['kicker'] : '',
					'text'         => isset( $section['text'] ) ? $section['text'] : '',
					'description'  => isset( $section['text'] ) ? $section['text'] : '',
					'button_label' => isset( $section['button_label'] ) ? $section['button_label'] : '',
					'button_url'   => isset( $section['button_url'] ) ? $section['button_url'] : '',
					'email'        => isset( $section['email'] ) ? $section['email'] : '',
					'phone'        => isset( $section['phone'] ) ? $section['phone'] : '',
					'address'      => isset( $section['address'] ) ? $section['address'] : '',
				),
				$type
			);
		}

		if ( empty( $blocks ) && ! empty( $default_section['blocks'] ) && is_array( $default_section['blocks'] ) ) {
			$blocks = self::normalize_blocks( $default_section['blocks'], $type );
		}

		$normalized = array(
			'id'          => isset( $section['id'] ) && '' !== (string) $section['id'] ? sanitize_key( (string) $section['id'] ) : self::create_id(),
			'type'        => $type,
			'name'        => isset( $section['name'] ) ? sanitize_text_field( (string) $section['name'] ) : '',
			'blocks'      => $blocks,
			'provider'    => isset( $section['provider'] ) ? sanitize_key( (string) $section['provider'] ) : ( isset( $default_section['provider'] ) ? sanitize_key( (string) $default_section['provider'] ) : 'core' ),
			'module_key'  => isset( $section['module_key'] ) ? sanitize_key( (string) $section['module_key'] ) : ( isset( $default_section['module_key'] ) ? sanitize_key( (string) $default_section['module_key'] ) : '' ),
			'unavailable' => ! $is_known_type || ! empty( $section['unavailable'] ),
		);

		return array_merge( $passthrough, $normalized );
	}

	private static function normalize_blocks( array $blocks, string $fallback_type ): array {
		$normalized = array();
		$used_names = array();

		foreach ( $blocks as $block ) {
			if ( ! is_array( $block ) ) {
				continue;
			}

			$normalized_block = self::normalize_block( $block, $fallback_type );
			$normalized_block['name'] = self::build_unique_title(
				'' !== $normalized_block['name'] ? $normalized_block['name'] : self::get_block_default_name( $normalized_block['type'] ),
				$used_names
			);
			$used_names[] = $normalized_block['name'];
			$normalized[] = $normalized_block;
		}

		return $normalized;
	}

	private static function normalize_widget( array $widget ): array {
		$type        = isset( $widget['type'] ) ? sanitize_key( (string) $widget['type'] ) : '';
		$definition  = self::get_widget_definition( $type );
		$is_unknown  = empty( $definition );
		$passthrough = self::extract_passthrough_fields(
			$widget,
			array(
				'id',
				'type',
				'name',
				'enabled',
				'position',
				'created_at',
				'updated_at',
				'label',
				'url',
				'phone',
				'email',
				'message',
				'platform',
				'address',
				'embed_code',
				'description',
				'unavailable',
			)
		);

		$created_at = isset( $widget['created_at'] ) ? sanitize_text_field( (string) $widget['created_at'] ) : self::current_time_iso8601_utc();
		$updated_at = isset( $widget['updated_at'] ) ? sanitize_text_field( (string) $widget['updated_at'] ) : self::current_time_iso8601_utc();

		return array_merge(
			$passthrough,
			array(
				'id'          => isset( $widget['id'] ) && '' !== (string) $widget['id'] ? sanitize_key( (string) $widget['id'] ) : self::create_id(),
				'type'        => $type,
				'name'        => isset( $widget['name'] ) ? sanitize_text_field( (string) $widget['name'] ) : '',
				'enabled'     => ! isset( $widget['enabled'] ) || ! empty( $widget['enabled'] ),
				'position'    => self::sanitize_widget_position( isset( $widget['position'] ) ? (string) $widget['position'] : 'bottom_right' ),
				'created_at'  => $created_at,
				'updated_at'  => $updated_at,
				'label'       => isset( $widget['label'] ) ? sanitize_text_field( (string) $widget['label'] ) : '',
				'url'         => isset( $widget['url'] ) ? esc_url_raw( (string) $widget['url'] ) : '',
				'phone'       => isset( $widget['phone'] ) ? sanitize_text_field( (string) $widget['phone'] ) : '',
				'email'       => isset( $widget['email'] ) ? sanitize_email( (string) $widget['email'] ) : '',
				'message'     => isset( $widget['message'] ) ? sanitize_textarea_field( (string) $widget['message'] ) : '',
				'platform'    => isset( $widget['platform'] ) ? sanitize_text_field( (string) $widget['platform'] ) : '',
				'address'     => isset( $widget['address'] ) ? sanitize_text_field( (string) $widget['address'] ) : '',
				'embed_code'  => isset( $widget['embed_code'] ) ? sanitize_textarea_field( (string) $widget['embed_code'] ) : '',
				'description' => isset( $widget['description'] ) ? sanitize_textarea_field( (string) $widget['description'] ) : '',
				'unavailable' => $is_unknown || ! empty( $widget['unavailable'] ),
			)
		);
	}

	private static function normalize_popup( array $popup ): array {
		$type        = isset( $popup['type'] ) ? sanitize_key( (string) $popup['type'] ) : '';
		$definition  = self::get_popup_definition( $type );
		$is_unknown  = empty( $definition );
		$passthrough = self::extract_passthrough_fields(
			$popup,
			array(
				'id',
				'type',
				'name',
				'enabled',
				'trigger',
				'created_at',
				'updated_at',
				'heading',
				'text',
				'button_label',
				'button_url',
				'email',
				'phone',
				'address',
				'video_url',
				'embed_code',
				'description',
				'unavailable',
			)
		);

		$created_at = isset( $popup['created_at'] ) ? sanitize_text_field( (string) $popup['created_at'] ) : self::current_time_iso8601_utc();
		$updated_at = isset( $popup['updated_at'] ) ? sanitize_text_field( (string) $popup['updated_at'] ) : self::current_time_iso8601_utc();

		return array_merge(
			$passthrough,
			array(
				'id'          => isset( $popup['id'] ) && '' !== (string) $popup['id'] ? sanitize_key( (string) $popup['id'] ) : self::create_id(),
				'type'        => $type,
				'name'        => isset( $popup['name'] ) ? sanitize_text_field( (string) $popup['name'] ) : '',
				'enabled'     => ! isset( $popup['enabled'] ) || ! empty( $popup['enabled'] ),
				'trigger'     => self::sanitize_popup_trigger( isset( $popup['trigger'] ) ? (string) $popup['trigger'] : 'manual' ),
				'created_at'  => $created_at,
				'updated_at'  => $updated_at,
				'heading'     => isset( $popup['heading'] ) ? sanitize_text_field( (string) $popup['heading'] ) : '',
				'text'        => isset( $popup['text'] ) ? sanitize_textarea_field( (string) $popup['text'] ) : '',
				'button_label'=> isset( $popup['button_label'] ) ? sanitize_text_field( (string) $popup['button_label'] ) : '',
				'button_url'  => isset( $popup['button_url'] ) ? esc_url_raw( (string) $popup['button_url'] ) : '',
				'email'       => isset( $popup['email'] ) ? sanitize_email( (string) $popup['email'] ) : '',
				'phone'       => isset( $popup['phone'] ) ? sanitize_text_field( (string) $popup['phone'] ) : '',
				'address'     => isset( $popup['address'] ) ? sanitize_text_field( (string) $popup['address'] ) : '',
				'video_url'   => isset( $popup['video_url'] ) ? esc_url_raw( (string) $popup['video_url'] ) : '',
				'embed_code'  => isset( $popup['embed_code'] ) ? sanitize_textarea_field( (string) $popup['embed_code'] ) : '',
				'description' => isset( $popup['description'] ) ? sanitize_textarea_field( (string) $popup['description'] ) : '',
				'unavailable' => $is_unknown || ! empty( $popup['unavailable'] ),
			)
		);
	}

	private static function normalize_block( array $block, string $fallback_type ): array {
		$type        = isset( $block['type'] ) ? sanitize_key( (string) $block['type'] ) : self::get_default_block_type( $fallback_type );
		$definition  = self::get_block_definition( $type );
		$is_unknown  = empty( $definition );
		$passthrough = self::extract_passthrough_fields(
			$block,
			array(
				'id',
				'type',
				'name',
				'kicker',
				'heading',
				'title',
				'text',
				'description',
				'button_label',
				'button_url',
				'email',
				'phone',
				'address',
				'image_url',
				'image_alt',
				'caption',
				'video_url',
				'icon_key',
				'list_text',
				'value',
				'metric_label',
				'step_number',
				'quote',
				'author',
				'role',
				'name_person',
				'link_url',
				'map_url',
				'question',
				'answer',
				'link_label',
				'file_label',
				'file_url',
				'platform',
				'embed_code',
				'tag_label',
				'unavailable',
			)
		);

		$description = isset( $block['description'] ) ? sanitize_textarea_field( (string) $block['description'] ) : '';

		if ( '' === $description && isset( $block['text'] ) && 'contacts' === $type ) {
			$description = sanitize_textarea_field( (string) $block['text'] );
		}

		return array_merge(
			$passthrough,
			array(
				'id'           => isset( $block['id'] ) && '' !== (string) $block['id'] ? sanitize_key( (string) $block['id'] ) : self::create_id(),
				'type'         => $type,
				'name'         => isset( $block['name'] ) ? sanitize_text_field( (string) $block['name'] ) : '',
				'kicker'       => isset( $block['kicker'] ) ? sanitize_text_field( (string) $block['kicker'] ) : '',
				'heading'      => isset( $block['heading'] ) ? sanitize_text_field( (string) $block['heading'] ) : ( isset( $block['title'] ) ? sanitize_text_field( (string) $block['title'] ) : '' ),
				'text'         => isset( $block['text'] ) ? sanitize_textarea_field( (string) $block['text'] ) : '',
				'description'  => $description,
				'button_label' => isset( $block['button_label'] ) ? sanitize_text_field( (string) $block['button_label'] ) : '',
				'button_url'   => isset( $block['button_url'] ) ? esc_url_raw( (string) $block['button_url'] ) : '',
				'email'        => isset( $block['email'] ) ? sanitize_email( (string) $block['email'] ) : '',
				'phone'        => isset( $block['phone'] ) ? sanitize_text_field( (string) $block['phone'] ) : '',
				'address'      => isset( $block['address'] ) ? sanitize_text_field( (string) $block['address'] ) : '',
				'image_url'    => isset( $block['image_url'] ) ? esc_url_raw( (string) $block['image_url'] ) : '',
				'image_alt'    => isset( $block['image_alt'] ) ? sanitize_text_field( (string) $block['image_alt'] ) : '',
				'caption'      => isset( $block['caption'] ) ? sanitize_text_field( (string) $block['caption'] ) : '',
				'video_url'    => isset( $block['video_url'] ) ? esc_url_raw( (string) $block['video_url'] ) : '',
				'icon_key'     => isset( $block['icon_key'] ) ? sanitize_key( (string) $block['icon_key'] ) : '',
				'list_text'    => isset( $block['list_text'] ) ? sanitize_textarea_field( (string) $block['list_text'] ) : '',
				'value'        => isset( $block['value'] ) ? sanitize_text_field( (string) $block['value'] ) : '',
				'metric_label' => isset( $block['metric_label'] ) ? sanitize_text_field( (string) $block['metric_label'] ) : '',
				'step_number'  => isset( $block['step_number'] ) ? sanitize_text_field( (string) $block['step_number'] ) : '',
				'quote'        => isset( $block['quote'] ) ? sanitize_textarea_field( (string) $block['quote'] ) : '',
				'author'       => isset( $block['author'] ) ? sanitize_text_field( (string) $block['author'] ) : '',
				'role'         => isset( $block['role'] ) ? sanitize_text_field( (string) $block['role'] ) : '',
				'name_person'  => isset( $block['name_person'] ) ? sanitize_text_field( (string) $block['name_person'] ) : '',
				'link_url'     => isset( $block['link_url'] ) ? esc_url_raw( (string) $block['link_url'] ) : '',
				'map_url'      => isset( $block['map_url'] ) ? esc_url_raw( (string) $block['map_url'] ) : '',
				'question'     => isset( $block['question'] ) ? sanitize_text_field( (string) $block['question'] ) : '',
				'answer'       => isset( $block['answer'] ) ? sanitize_textarea_field( (string) $block['answer'] ) : '',
				'link_label'   => isset( $block['link_label'] ) ? sanitize_text_field( (string) $block['link_label'] ) : '',
				'file_label'   => isset( $block['file_label'] ) ? sanitize_text_field( (string) $block['file_label'] ) : '',
				'file_url'     => isset( $block['file_url'] ) ? esc_url_raw( (string) $block['file_url'] ) : '',
				'platform'     => isset( $block['platform'] ) ? sanitize_text_field( (string) $block['platform'] ) : '',
				'embed_code'   => isset( $block['embed_code'] ) ? sanitize_textarea_field( (string) $block['embed_code'] ) : '',
				'tag_label'    => isset( $block['tag_label'] ) ? sanitize_text_field( (string) $block['tag_label'] ) : '',
				'unavailable'  => $is_unknown || ! empty( $block['unavailable'] ),
			)
		);
	}

	private static function has_legacy_section_content( array $section ): bool {
		foreach ( array( 'kicker', 'title', 'text', 'button_label', 'button_url', 'email', 'phone', 'address' ) as $key ) {
			if ( ! empty( $section[ $key ] ) ) {
				return true;
			}
		}

		return false;
	}

	private static function build_unique_title( string $base, array $used_titles ): string {
		$base = sanitize_text_field( $base );

		if ( '' === $base ) {
			return '';
		}

		if ( ! in_array( $base, $used_titles, true ) ) {
			return $base;
		}

		$suffix = 1;

		while ( in_array( $base . ' ' . $suffix, $used_titles, true ) ) {
			$suffix++;
		}

		return $base . ' ' . $suffix;
	}

	private static function get_section_default_name( array $section ): string {
		if ( ! empty( $section['name'] ) ) {
			return (string) $section['name'];
		}

		$type = isset( $section['type'] ) ? sanitize_key( (string) $section['type'] ) : '';
		$definition = self::get_section_definition( $type );

		if ( ! empty( $definition['label'] ) ) {
			return (string) $definition['label'];
		}

		return self::text( 'manager.pages.sections.module_unavailable' );
	}

	private static function get_block_default_name( string $type ): string {
		$definition = self::get_block_definition( $type );

		if ( ! empty( $definition['label'] ) ) {
			return (string) $definition['label'];
		}

		return self::text( 'manager.pages.blocks.unavailable_type' ) ?: '';
	}

	private static function get_section_definition( string $type ): array {
		if ( class_exists( 'Sonyra_Site_Manager_Page_Elements_Library' ) ) {
			return Sonyra_Site_Manager_Page_Elements_Library::get_section_definition( $type );
		}

		return array();
	}

	private static function get_section_defaults( string $type ): array {
		if ( class_exists( 'Sonyra_Site_Manager_Page_Elements_Library' ) ) {
			return Sonyra_Site_Manager_Page_Elements_Library::get_section_defaults( $type );
		}

		return array();
	}

	private static function get_default_block_type( string $section_type ): string {
		$defaults = self::get_section_defaults( $section_type );

		if ( isset( $defaults['blocks'][0]['type'] ) && is_string( $defaults['blocks'][0]['type'] ) ) {
			$type = sanitize_key( $defaults['blocks'][0]['type'] );
			if ( '' !== $type ) {
				return $type;
			}
		}

		return in_array( $section_type, array( 'hero', 'text', 'contacts' ), true ) ? $section_type : 'text';
	}

	private static function get_block_definition( string $type ): array {
		if ( class_exists( 'Sonyra_Site_Manager_Page_Elements_Library' ) ) {
			return Sonyra_Site_Manager_Page_Elements_Library::get_block_definition( $type );
		}

		return array();
	}

	private static function get_widget_definition( string $type ): array {
		if ( class_exists( 'Sonyra_Site_Manager_Page_Elements_Library' ) ) {
			return Sonyra_Site_Manager_Page_Elements_Library::get_widget_definition( $type );
		}

		return array();
	}

	private static function get_popup_definition( string $type ): array {
		if ( class_exists( 'Sonyra_Site_Manager_Page_Elements_Library' ) ) {
			return Sonyra_Site_Manager_Page_Elements_Library::get_popup_definition( $type );
		}

		return array();
	}

	private static function get_widget_default_name( string $type ): string {
		$definition = self::get_widget_definition( $type );

		if ( ! empty( $definition['label'] ) ) {
			return (string) $definition['label'];
		}

		return self::text( 'manager.pages.widgets.unavailable_type' ) ?: '';
	}

	private static function get_popup_default_name( string $type ): string {
		$definition = self::get_popup_definition( $type );

		if ( ! empty( $definition['label'] ) ) {
			return (string) $definition['label'];
		}

		return self::text( 'manager.pages.popups.unavailable_type' ) ?: '';
	}

	private static function extract_passthrough_fields( array $source, array $known_keys ): array {
		$passthrough = array();

		foreach ( $source as $key => $value ) {
			if ( ! is_string( $key ) || in_array( $key, $known_keys, true ) ) {
				continue;
			}

			$passthrough[ sanitize_key( $key ) ] = self::preserve_mixed_value( $value );
		}

		return $passthrough;
	}

	private static function preserve_mixed_value( $value ) {
		if ( is_array( $value ) ) {
			$normalized = array();

			foreach ( $value as $key => $item ) {
				$normalized[ is_string( $key ) ? sanitize_key( $key ) : $key ] = self::preserve_mixed_value( $item );
			}

			return $normalized;
		}

		if ( is_bool( $value ) || is_int( $value ) || is_float( $value ) || null === $value ) {
			return $value;
		}

		return is_scalar( $value ) ? (string) $value : '';
	}

	private static function sanitize_status( string $status ): string {
		return in_array( $status, array( 'draft', 'published', 'hidden' ), true ) ? $status : 'draft';
	}

	private static function sanitize_widget_position( string $position ): string {
		$position = sanitize_key( $position );

		if ( in_array( $position, array( 'bottom_right', 'bottom_left', 'top_right', 'top_left' ), true ) ) {
			return $position;
		}

		return 'bottom_right';
	}

	private static function sanitize_popup_trigger( string $trigger ): string {
		$trigger = sanitize_key( $trigger );

		if ( in_array( $trigger, array( 'manual', 'page_load', 'delay', 'scroll', 'exit_intent', 'first_visit' ), true ) ) {
			return $trigger;
		}

		return 'manual';
	}

	private static function sanitize_slug_value( string $slug ): string {
		$slug = trim( $slug, " \t\n\r\0\x0B/" );

		return sanitize_title( $slug );
	}

	private static function validate_slug( string $slug ) {
		if ( in_array( $slug, self::get_reserved_slugs(), true ) ) {
			return new WP_Error( 'sonyra_pages_slug_reserved', self::text( 'manager.pages.errors.slug_reserved' ), array( 'status' => 400 ) );
		}

		if ( self::wordpress_permalink_exists( $slug ) ) {
			return new WP_Error( 'sonyra_pages_slug_conflict', self::text( 'manager.pages.errors.slug_conflict' ), array( 'status' => 400 ) );
		}

		return true;
	}

	private static function wordpress_permalink_exists( string $slug ): bool {
		if ( function_exists( 'url_to_postid' ) && url_to_postid( home_url( '/' . $slug . '/' ) ) > 0 ) {
			return true;
		}

		if ( function_exists( 'get_page_by_path' ) ) {
			$post = get_page_by_path( $slug, OBJECT, array( 'page', 'post' ) );

			return is_object( $post );
		}

		return false;
	}

	private static function get_reserved_slugs(): array {
			return array(
				'manager',
				'sonyra-preview',
				'wp-admin',
			'wp-login-php',
			'wp-json',
			'feed',
			'sitemap-xml',
			'robots-txt',
			'favicon-ico',
			'xmlrpc-php',
			'wp-content',
			'wp-includes',
			'author',
			'category',
			'tag',
			'search',
		);
	}

	private static function create_id(): string {
		if ( function_exists( 'wp_generate_uuid4' ) ) {
			return sanitize_key( wp_generate_uuid4() );
		}

		return sanitize_key( uniqid( 'sonyra-page-', true ) );
	}

	private static function current_time_iso8601_utc(): string {
		return gmdate( 'c' );
	}

	private static function get_session_uuid_from_context( array $context ): string {
		if ( empty( $context ) && class_exists( 'Sonyra_Site_Manager_Auth_Access_Guard' ) ) {
			$context = Sonyra_Site_Manager_Auth_Access_Guard::get_current_auth_context();
		}

		return isset( $context['session_uuid'] ) ? sanitize_text_field( (string) $context['session_uuid'] ) : '';
	}

	private static function get_nonce_tick(): int {
		return (int) ceil( time() / ( 12 * HOUR_IN_SECONDS ) );
	}

	private static function build_nonce_for_tick( string $session_uuid, int $tick ): string {
		$data = $session_uuid . '|' . self::NONCE_ACTION . '|' . (string) $tick;

		return substr( wp_hash( $data, 'nonce' ), -12, 10 );
	}

	private static function text( string $key ): string {
		if ( class_exists( 'Sonyra_Site_Manager_I18n' ) ) {
			return Sonyra_Site_Manager_I18n::t( $key );
		}

		return '';
	}
}
