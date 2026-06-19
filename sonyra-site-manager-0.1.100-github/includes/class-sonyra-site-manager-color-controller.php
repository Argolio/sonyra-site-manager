<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Sonyra_Site_Manager_Color_Controller {

	const OPTION_NAME    = 'sonyra_site_manager_color_controller';
	const OPTION_VERSION = '0.1.0';
	const NONCE_ACTION   = 'sonyra_color_controller';

	private static function text( string $key ): string {
		if ( class_exists( 'Sonyra_Site_Manager_I18n' ) ) {
			return Sonyra_Site_Manager_I18n::t( $key );
		}

		return '';
	}

	public static function get_option_name() {
		return self::OPTION_NAME;
	}

	public static function get_default_data() {
		$defaults = array(
			'version'      => self::OPTION_VERSION,
			'colors'       => array(
				array(
					'id'         => 'color-brand-primary',
					'name'       => 'Основной фиолетовый',
					'slug'       => 'brand-primary',
					'type'       => 'color',
					'value_hex'  => '#7C3AED',
					'value_rgb'  => array( 124, 58, 237 ),
					'role'       => 'brand.primary',
					'description' => '',
					'source'     => 'core',
					'created_at' => '',
					'updated_at' => '',
				),
				array(
					'id'         => 'color-brand-deep',
					'name'       => 'Глубокий фиолетовый',
					'slug'       => 'brand-deep',
					'type'       => 'color',
					'value_hex'  => '#4C1D95',
					'value_rgb'  => array( 76, 29, 149 ),
					'role'       => 'brand.deep',
					'description' => '',
					'source'     => 'core',
					'created_at' => '',
					'updated_at' => '',
				),
				array(
					'id'         => 'color-surface-light',
					'name'       => 'Светлый фон',
					'slug'       => 'surface-light',
					'type'       => 'color',
					'value_hex'  => '#F8FAFC',
					'value_rgb'  => array( 248, 250, 252 ),
					'role'       => 'surface.light',
					'description' => '',
					'source'     => 'core',
					'created_at' => '',
					'updated_at' => '',
				),
				array(
					'id'         => 'color-text-primary',
					'name'       => 'Тёмный текст',
					'slug'       => 'text-primary',
					'type'       => 'color',
					'value_hex'  => '#111827',
					'value_rgb'  => array( 17, 24, 39 ),
					'role'       => 'text.primary',
					'description' => '',
					'source'     => 'core',
					'created_at' => '',
					'updated_at' => '',
				),
				array(
					'id'         => 'color-text-secondary',
					'name'       => 'Вторичный текст',
					'slug'       => 'text-secondary',
					'type'       => 'color',
					'value_hex'  => '#64748B',
					'value_rgb'  => array( 100, 116, 139 ),
					'role'       => 'text.secondary',
					'description' => '',
					'source'     => 'core',
					'created_at' => '',
					'updated_at' => '',
				),
				array(
					'id'         => 'color-brand-accent',
					'name'       => 'Акцентный розовый',
					'slug'       => 'brand-accent',
					'type'       => 'color',
					'value_hex'  => '#EC4899',
					'value_rgb'  => array( 236, 72, 153 ),
					'role'       => 'brand.accent',
					'description' => '',
					'source'     => 'core',
					'created_at' => '',
					'updated_at' => '',
				),
			),
			'gradients'    => array(
				array(
					'id'            => 'gradient-violet-accent',
					'name'          => 'Фиолетовый акцент',
					'slug'          => 'violet-accent',
					'type'          => 'gradient',
					'gradient_type' => 'linear',
					'angle'         => 135,
					'role'          => 'gradient.brand-accent',
					'description'   => '',
					'stops'         => array(
						array(
							'color_hex' => '#7C3AED',
							'position'  => 0,
						),
						array(
							'color_hex' => '#EC4899',
							'position'  => 55,
						),
						array(
							'color_hex' => '#F97316',
							'position'  => 100,
						),
					),
					'source'        => 'core',
					'created_at'    => '',
					'updated_at'    => '',
				),
			),
			'patterns'     => array(
				array(
					'id'           => 'pattern-soft-glow',
					'name'         => 'Мягкое свечение',
					'slug'         => 'soft-glow',
					'type'         => 'pattern',
					'pattern_type' => 'soft_glow',
					'role'         => 'pattern.soft-glow',
					'description'  => '',
					'colors'       => array(
						'base'     => '#FFFFFF',
						'accent_a' => '#EDE9FE',
						'accent_b' => '#FCE7F3',
						'accent_c' => '#DBEAFE',
					),
					'settings'     => array(
						'intensity' => 72,
						'blur'      => 24,
					),
					'source'       => 'core',
					'created_at'   => '',
					'updated_at'   => '',
				),
			),
			'presets'      => array(),
			'library_meta' => array(
				'updated_at' => '',
				'created_by' => 'core',
			),
		);

		return self::normalize_data( $defaults );
	}

	public static function get_data() {
		$stored = get_option( self::OPTION_NAME, null );

		if ( ! is_array( $stored ) ) {
			$default_data = self::get_default_data();
			self::persist_data( $default_data );
			return $default_data;
		}

		$normalized = self::normalize_data( $stored );
		$recovered  = self::recover_missing_default_collections( $normalized, $stored );

		if ( $recovered !== $stored ) {
			self::persist_data( $recovered );
		}

		return $recovered;
	}

	public static function save_data( array $data ) {
		$normalized = self::normalize_data( $data );
		$persisted  = self::persist_data( $normalized );

		if ( ! $persisted ) {
			return new WP_Error( 'sonyra_color_controller_save_failed', self::text( 'manager.design.colors.save_failed' ), array( 'status' => 500 ) );
		}

		return $normalized;
	}

	public static function normalize_data( array $data ) {
		$normalized_colors = array();
		$normalized_gradients = array();
		$normalized_patterns = array();
		$normalized_presets = array();
		$seen = array(
			'colors'    => array(),
			'gradients' => array(),
			'patterns'  => array(),
			'presets'   => array(),
		);

		foreach ( isset( $data['colors'] ) && is_array( $data['colors'] ) ? $data['colors'] : array() as $color ) {
			if ( ! is_array( $color ) ) {
				continue;
			}

			$normalized_color = self::normalize_color( $color );

			if ( empty( $normalized_color ) ) {
				continue;
			}

			$key = $normalized_color['slug'];

			if ( isset( $seen['colors'][ $key ] ) ) {
				continue;
			}

			$seen['colors'][ $key ] = true;
			$normalized_colors[]    = $normalized_color;
		}

		foreach ( isset( $data['gradients'] ) && is_array( $data['gradients'] ) ? $data['gradients'] : array() as $gradient ) {
			if ( ! is_array( $gradient ) ) {
				continue;
			}

			$normalized_gradient = self::normalize_gradient( $gradient );

			if ( empty( $normalized_gradient ) ) {
				continue;
			}

			$key = $normalized_gradient['slug'];

			if ( isset( $seen['gradients'][ $key ] ) ) {
				continue;
			}

			$seen['gradients'][ $key ] = true;
			$normalized_gradients[]    = $normalized_gradient;
		}

		foreach ( isset( $data['patterns'] ) && is_array( $data['patterns'] ) ? $data['patterns'] : array() as $pattern ) {
			if ( ! is_array( $pattern ) ) {
				continue;
			}

			$normalized_pattern = self::normalize_pattern( $pattern );

			if ( empty( $normalized_pattern ) ) {
				continue;
			}

			$key = $normalized_pattern['slug'];

			if ( isset( $seen['patterns'][ $key ] ) ) {
				continue;
			}

			$seen['patterns'][ $key ] = true;
			$normalized_patterns[]    = $normalized_pattern;
		}

		foreach ( isset( $data['presets'] ) && is_array( $data['presets'] ) ? $data['presets'] : array() as $preset ) {
			if ( ! is_array( $preset ) ) {
				continue;
			}

			$normalized_preset = self::normalize_preset( $preset );

			if ( empty( $normalized_preset ) ) {
				continue;
			}

			$key = $normalized_preset['slug'];

			if ( isset( $seen['presets'][ $key ] ) ) {
				continue;
			}

			$seen['presets'][ $key ] = true;
			$normalized_presets[]    = $normalized_preset;
		}

		$updated_at = isset( $data['library_meta']['updated_at'] ) ? sanitize_text_field( (string) $data['library_meta']['updated_at'] ) : '';
		$created_by = isset( $data['library_meta']['created_by'] ) ? self::sanitize_source( (string) $data['library_meta']['created_by'] ) : 'core';

		return array(
			'version'      => self::OPTION_VERSION,
			'colors'       => $normalized_colors,
			'gradients'    => $normalized_gradients,
			'patterns'     => $normalized_patterns,
			'presets'      => $normalized_presets,
			'library_meta' => array(
				'updated_at' => $updated_at,
				'created_by' => '' !== $created_by ? $created_by : 'core',
			),
		);
	}

	private static function recover_missing_default_collections( array $normalized, array $stored ): array {
		$defaults       = self::get_default_data();
		$recovered      = $normalized;
		$did_recover    = false;
		$collection_keys = array( 'colors', 'gradients', 'patterns' );

		foreach ( $collection_keys as $collection_key ) {
			$stored_has_collection = array_key_exists( $collection_key, $stored ) && is_array( $stored[ $collection_key ] );
			$current_items         = isset( $normalized[ $collection_key ] ) && is_array( $normalized[ $collection_key ] ) ? $normalized[ $collection_key ] : array();

			if ( ! $stored_has_collection || empty( $current_items ) ) {
				$default_items = isset( $defaults[ $collection_key ] ) && is_array( $defaults[ $collection_key ] ) ? $defaults[ $collection_key ] : array();

				if ( ! empty( $default_items ) ) {
					$recovered[ $collection_key ] = $default_items;
					$did_recover                  = true;
				}
			}
		}

		if ( $did_recover ) {
			$recovered['library_meta']['updated_at'] = self::now_iso8601();
		}

		return $recovered;
	}

	public static function normalize_color( array $color ) {
		$name       = isset( $color['name'] ) ? sanitize_text_field( (string) $color['name'] ) : '';
		$hex        = self::normalize_hex( isset( $color['value_hex'] ) ? $color['value_hex'] : '' );
		$rgb        = self::normalize_rgb( isset( $color['value_rgb'] ) ? $color['value_rgb'] : array() );
		$role       = self::sanitize_role( isset( $color['role'] ) ? (string) $color['role'] : '' );
		$source     = self::sanitize_source( isset( $color['source'] ) ? (string) $color['source'] : 'core' );
		$slug       = self::sanitize_slug( isset( $color['slug'] ) ? (string) $color['slug'] : $name );
		$description = isset( $color['description'] ) ? sanitize_textarea_field( (string) $color['description'] ) : '';
		$created_at = isset( $color['created_at'] ) ? sanitize_text_field( (string) $color['created_at'] ) : '';
		$updated_at = isset( $color['updated_at'] ) ? sanitize_text_field( (string) $color['updated_at'] ) : '';

		if ( '' === $name || '' === $hex || empty( $rgb ) || '' === $slug ) {
			return array();
		}

		return array(
			'id'         => self::sanitize_id( isset( $color['id'] ) ? (string) $color['id'] : 'color-' . $slug ),
			'name'       => $name,
			'slug'       => $slug,
			'type'       => 'color',
			'value_hex'  => $hex,
			'value_rgb'  => $rgb,
			'role'       => $role,
			'description' => $description,
			'source'     => '' !== $source ? $source : 'core',
			'created_at' => $created_at,
			'updated_at' => $updated_at,
		);
	}

	public static function normalize_gradient( array $gradient ) {
		$name          = isset( $gradient['name'] ) ? sanitize_text_field( (string) $gradient['name'] ) : '';
		$slug          = self::sanitize_slug( isset( $gradient['slug'] ) ? (string) $gradient['slug'] : $name );
		$gradient_type = isset( $gradient['gradient_type'] ) ? sanitize_key( (string) $gradient['gradient_type'] ) : 'linear';
		$angle         = isset( $gradient['angle'] ) ? (int) $gradient['angle'] : 135;
		$source         = self::sanitize_source( isset( $gradient['source'] ) ? (string) $gradient['source'] : 'core' );
		$role           = self::sanitize_role( isset( $gradient['role'] ) ? (string) $gradient['role'] : '' );
		$description    = isset( $gradient['description'] ) ? sanitize_textarea_field( (string) $gradient['description'] ) : '';
		$created_at     = isset( $gradient['created_at'] ) ? sanitize_text_field( (string) $gradient['created_at'] ) : '';
		$updated_at     = isset( $gradient['updated_at'] ) ? sanitize_text_field( (string) $gradient['updated_at'] ) : '';
		$stops          = array();

		if ( 'linear' !== $gradient_type || '' === $name || '' === $slug ) {
			return array();
		}

		foreach ( isset( $gradient['stops'] ) && is_array( $gradient['stops'] ) ? $gradient['stops'] : array() as $stop ) {
			if ( ! is_array( $stop ) ) {
				continue;
			}

			$color_hex = self::normalize_hex( isset( $stop['color_hex'] ) ? $stop['color_hex'] : '' );
			$position  = isset( $stop['position'] ) ? (int) $stop['position'] : -1;

			if ( '' === $color_hex || $position < 0 || $position > 100 ) {
				continue;
			}

			$stops[] = array(
				'color_hex' => $color_hex,
				'position'  => $position,
			);
		}

		if ( count( $stops ) < 2 || count( $stops ) > 5 || $angle < 0 || $angle > 360 ) {
			return array();
		}

		usort(
			$stops,
			static function ( array $left, array $right ): int {
				return (int) $left['position'] <=> (int) $right['position'];
			}
		);

		return array(
			'id'            => self::sanitize_id( isset( $gradient['id'] ) ? (string) $gradient['id'] : 'gradient-' . $slug ),
			'name'          => $name,
			'slug'          => $slug,
			'type'          => 'gradient',
			'gradient_type' => 'linear',
			'angle'         => $angle,
			'role'          => $role,
			'description'   => $description,
			'stops'         => $stops,
			'source'        => '' !== $source ? $source : 'core',
			'created_at'    => $created_at,
			'updated_at'    => $updated_at,
		);
	}

	public static function normalize_pattern( array $pattern ) {
		$name         = isset( $pattern['name'] ) ? sanitize_text_field( (string) $pattern['name'] ) : '';
		$slug         = self::sanitize_slug( isset( $pattern['slug'] ) ? (string) $pattern['slug'] : $name );
		$pattern_type = self::normalize_pattern_type( isset( $pattern['pattern_type'] ) ? (string) $pattern['pattern_type'] : '' );
		$source        = self::sanitize_source( isset( $pattern['source'] ) ? (string) $pattern['source'] : 'core' );
		$role          = self::sanitize_role( isset( $pattern['role'] ) ? (string) $pattern['role'] : '' );
		$description   = isset( $pattern['description'] ) ? sanitize_textarea_field( (string) $pattern['description'] ) : '';
		$created_at    = isset( $pattern['created_at'] ) ? sanitize_text_field( (string) $pattern['created_at'] ) : '';
		$updated_at    = isset( $pattern['updated_at'] ) ? sanitize_text_field( (string) $pattern['updated_at'] ) : '';
		$normalized_pattern = self::normalize_pattern_compatibility_payload(
			$pattern_type,
			isset( $pattern['colors'] ) && is_array( $pattern['colors'] ) ? $pattern['colors'] : array(),
			isset( $pattern['settings'] ) && is_array( $pattern['settings'] ) ? $pattern['settings'] : array()
		);
		$colors        = self::sanitize_pattern_colors( $pattern_type, $normalized_pattern['colors'] );
		$settings      = self::sanitize_pattern_settings( $pattern_type, $normalized_pattern['settings'] );
		$media         = self::normalize_pattern_media( isset( $pattern['media'] ) && is_array( $pattern['media'] ) ? $pattern['media'] : array() );

		if ( '' === $name || '' === $slug || ! in_array( $pattern_type, self::get_pattern_allowlist(), true ) ) {
			return array();
		}

		if ( empty( $colors ) && 'image_pattern' !== $pattern_type ) {
			return array();
		}

		if ( 'image_pattern' === $pattern_type && empty( $media ) ) {
			return array();
		}

		return array(
			'id'           => self::sanitize_id( isset( $pattern['id'] ) ? (string) $pattern['id'] : 'pattern-' . $slug ),
			'name'         => $name,
			'slug'         => $slug,
			'type'         => 'pattern',
			'pattern_type' => $pattern_type,
			'role'         => $role,
			'description'  => $description,
			'colors'       => $colors,
			'settings'     => $settings,
			'media'        => $media,
			'source'       => '' !== $source ? $source : 'core',
			'created_at'   => $created_at,
			'updated_at'   => $updated_at,
		);
	}

	public static function validate_hex( $value ) {
		return '' !== self::normalize_hex( $value );
	}

	public static function normalize_hex( $value ) {
		if ( ! is_string( $value ) && ! is_numeric( $value ) ) {
			return '';
		}

		$value = trim( (string) $value );

		if ( '' === $value || preg_match( '/javascript:|expression|url\(|var\(|rgb\(|hsl\(|calc\(/i', $value ) ) {
			return '';
		}

		$value = strtoupper( ltrim( $value, '#' ) );

		if ( preg_match( '/^[A-F0-9]{3}$/', $value ) ) {
			return '#' . $value[0] . $value[0] . $value[1] . $value[1] . $value[2] . $value[2];
		}

		if ( preg_match( '/^[A-F0-9]{6}$/', $value ) ) {
			return '#' . $value;
		}

		return '';
	}

	public static function normalize_rgb( $value ) {
		$components = array();

		if ( is_array( $value ) ) {
			if ( isset( $value['r'], $value['g'], $value['b'] ) ) {
				$components = array( $value['r'], $value['g'], $value['b'] );
			} else {
				$components = array_values( $value );
			}
		} elseif ( is_string( $value ) ) {
			if ( preg_match( '/javascript:|expression|url\(|var\(|rgb\(/i', $value ) ) {
				return array();
			}

			$components = preg_split( '/\s*,\s*/', trim( $value ) );
		}

		if ( 3 !== count( $components ) ) {
			return array();
		}

		$normalized = array();

		foreach ( $components as $component ) {
			if ( '' === (string) $component || ! is_numeric( $component ) ) {
				return array();
			}

			$channel = (int) $component;

			if ( $channel < 0 || $channel > 255 ) {
				return array();
			}

			$normalized[] = $channel;
		}

		return $normalized;
	}

	public static function hex_to_rgb( $hex ) {
		$normalized = self::normalize_hex( $hex );

		if ( '' === $normalized ) {
			return array();
		}

		return array(
			hexdec( substr( $normalized, 1, 2 ) ),
			hexdec( substr( $normalized, 3, 2 ) ),
			hexdec( substr( $normalized, 5, 2 ) ),
		);
	}

	public static function rgb_to_hex( $rgb ) {
		$normalized = self::normalize_rgb( $rgb );

		if ( empty( $normalized ) ) {
			return '';
		}

		return sprintf( '#%02X%02X%02X', $normalized[0], $normalized[1], $normalized[2] );
	}

	public static function create_nonce( array $context = array() ): string {
		$session_uuid = self::get_session_uuid_from_context( $context );

		if ( '' === $session_uuid ) {
			return '';
		}

		return self::build_nonce_for_tick( $session_uuid, self::get_nonce_tick() );
	}

	public static function verify_nonce( string $nonce, array $context = array() ): bool {
		$nonce        = sanitize_text_field( $nonce );
		$session_uuid = self::get_session_uuid_from_context( $context );

		if ( '' === $nonce || '' === $session_uuid ) {
			return false;
		}

		$current_tick = self::get_nonce_tick();

		return hash_equals( self::build_nonce_for_tick( $session_uuid, $current_tick ), $nonce )
			|| hash_equals( self::build_nonce_for_tick( $session_uuid, $current_tick - 1 ), $nonce );
	}

	public static function get_summary( array $data = array() ): array {
		if ( empty( $data ) ) {
			$data = self::get_data();
		}

		return array(
			'colors'    => isset( $data['colors'] ) && is_array( $data['colors'] ) ? count( $data['colors'] ) : 0,
			'gradients' => isset( $data['gradients'] ) && is_array( $data['gradients'] ) ? count( $data['gradients'] ) : 0,
			'patterns'  => isset( $data['patterns'] ) && is_array( $data['patterns'] ) ? count( $data['patterns'] ) : 0,
			'presets'   => isset( $data['presets'] ) && is_array( $data['presets'] ) ? count( $data['presets'] ) : 0,
		);
	}

	public static function create_item( string $entity_type, array $payload ) {
		return self::upsert_item( $entity_type, '', $payload );
	}

	public static function update_item( string $entity_type, string $item_id, array $payload ) {
		return self::upsert_item( $entity_type, $item_id, $payload );
	}

	public static function delete_item( string $entity_type, string $item_id ) {
		$data           = self::get_data();
		$collection_key = self::get_collection_key( $entity_type );
		$item_id        = self::sanitize_id( $item_id );

		if ( '' === $collection_key || '' === $item_id ) {
			return new WP_Error( 'sonyra_color_controller_item_invalid', self::text( 'manager.design.colors.item_not_found' ), array( 'status' => 404 ) );
		}

		$items       = isset( $data[ $collection_key ] ) && is_array( $data[ $collection_key ] ) ? $data[ $collection_key ] : array();
		$target_item = null;
		$next_items  = array();

		foreach ( $items as $item ) {
			if ( ! is_array( $item ) ) {
				continue;
			}

			if ( isset( $item['id'] ) && $item_id === self::sanitize_id( (string) $item['id'] ) ) {
				$target_item = $item;
				continue;
			}

			$next_items[] = $item;
		}

		if ( null === $target_item ) {
			return new WP_Error( 'sonyra_color_controller_item_not_found', self::text( 'manager.design.colors.item_not_found' ), array( 'status' => 404 ) );
		}

		$data[ $collection_key ]          = $next_items;
		$data['library_meta']['updated_at'] = self::now_iso8601();

		$saved = self::save_data( $data );

		if ( is_wp_error( $saved ) ) {
			return $saved;
		}

		return array(
			'item'    => $target_item,
			'data'    => $saved,
			'summary' => self::get_summary( $saved ),
		);
	}

	public static function get_i18n_keys(): array {
		$keys = array(
			'manager.design.eyebrow',
			'manager.design.title',
			'manager.design.description',
			'manager.design.tools.color_library.title',
			'manager.design.tools.color_library.description',
			'manager.design.tools.color_library.action',
			'manager.design.colors.title',
			'manager.design.colors.description',
			'manager.design.colors.context_title',
			'manager.design.colors.context_subtitle',
			'manager.design.colors.back_to_design',
			'manager.design.colors.tab_colors',
			'manager.design.colors.tab_gradients',
			'manager.design.colors.tab_patterns',
			'manager.design.colors.create_color',
			'manager.design.colors.create_gradient',
			'manager.design.colors.create_pattern',
			'manager.design.colors.edit_color',
			'manager.design.colors.edit_gradient',
			'manager.design.colors.edit_pattern',
			'manager.design.colors.edit_action',
			'manager.design.colors.color_card_label',
			'manager.design.colors.gradient_card_label',
			'manager.design.colors.pattern_card_label',
			'manager.design.colors.empty_title',
			'manager.design.colors.empty_description',
			'manager.design.colors.preview_color',
			'manager.design.colors.preview_gradient',
			'manager.design.colors.preview_pattern',
			'manager.design.colors.hex_label',
			'manager.design.colors.color_picker_label',
			'manager.design.colors.color_picker_hint',
			'manager.design.colors.color_hex_hint',
			'manager.design.colors.updated_label',
			'manager.design.colors.gradient_angle_label',
			'manager.design.colors.gradient_preview_label',
			'manager.design.colors.gradient_direction_label',
			'manager.design.colors.pattern_type_label',
			'manager.design.colors.pattern_gallery_label',
			'manager.design.colors.pattern_preview_label',
			'manager.design.colors.pattern_media_label',
			'manager.design.colors.pattern_image_mode_label',
			'manager.design.colors.pattern_type_choice_description',
			'manager.design.colors.pattern_editor_graphic_title',
			'manager.design.colors.pattern_editor_graphic_description',
			'manager.design.colors.pattern_editor_image_title',
			'manager.design.colors.pattern_editor_image_description',
			'manager.design.colors.pattern_generator_label',
			'manager.design.colors.pattern_generator_hint',
			'manager.design.colors.pattern_live_preview_hint',
			'manager.design.colors.pattern_back_to_choice',
			'manager.design.colors.field_name',
			'manager.design.colors.field_hex',
			'manager.design.colors.field_color',
			'manager.design.colors.field_first_color',
			'manager.design.colors.field_second_color',
			'manager.design.colors.field_angle',
			'manager.design.colors.field_pattern_type',
			'manager.design.colors.field_density',
			'manager.design.colors.field_softness',
			'manager.design.colors.field_opacity',
			'manager.design.colors.field_dimness',
			'manager.design.colors.field_blur',
			'manager.design.colors.field_spacing',
			'manager.design.colors.field_dot_size',
			'manager.design.colors.field_layer_count',
			'manager.design.colors.field_layer_offset',
			'manager.design.colors.field_random_offset',
			'manager.design.colors.field_edge_style',
			'manager.design.colors.field_cell_size',
			'manager.design.colors.field_line_thickness',
			'manager.design.colors.field_contrast',
			'manager.design.colors.field_second_layer',
			'manager.design.colors.field_line_softness',
			'manager.design.colors.field_line_spacing',
			'manager.design.colors.field_edge_softness',
			'manager.design.colors.field_alternate_colors',
			'manager.design.colors.field_circle_size',
			'manager.design.colors.field_ring_count',
			'manager.design.colors.field_center_position',
			'manager.design.colors.field_rotation',
			'manager.design.colors.field_orbit_gap',
			'manager.design.colors.field_shape_type',
			'manager.design.colors.field_shape_size',
			'manager.design.colors.field_use_third_color',
			'manager.design.colors.field_randomize',
			'manager.design.colors.field_blob_count',
			'manager.design.colors.field_blob_size',
			'manager.design.colors.field_layout',
			'manager.design.colors.field_transition_softness',
			'manager.design.colors.field_source_position',
			'manager.design.colors.field_beam_length',
			'manager.design.colors.field_beam_width',
			'manager.design.colors.field_intensity',
			'manager.design.colors.field_beam_count',
			'manager.design.colors.field_amplitude',
			'manager.design.colors.field_frequency',
			'manager.design.colors.field_thickness',
			'manager.design.colors.field_wave_count',
			'manager.design.colors.field_stroke_count',
			'manager.design.colors.field_length',
			'manager.design.colors.field_roughness',
			'manager.design.colors.field_brush_mode',
			'manager.design.colors.field_drips',
			'manager.design.colors.field_blot_count',
			'manager.design.colors.field_blot_size',
			'manager.design.colors.field_scatter',
			'manager.design.colors.field_edge_irregularity',
			'manager.design.colors.field_edge_sharpness',
			'manager.design.colors.field_line_count',
			'manager.design.colors.field_curve',
			'manager.design.colors.field_particle_size',
			'manager.design.colors.field_monochrome',
			'manager.design.colors.field_element_type',
			'manager.design.colors.field_element_size',
			'manager.design.colors.field_random_rotation',
			'manager.design.colors.field_row_offset',
			'manager.design.colors.field_scale',
			'manager.design.colors.field_position',
			'manager.design.colors.field_brightness',
			'manager.design.colors.field_saturation',
			'manager.design.colors.field_monochrome_transition',
			'manager.design.colors.field_warmth',
			'manager.design.colors.field_red_channel',
			'manager.design.colors.field_green_channel',
			'manager.design.colors.field_blue_channel',
			'manager.design.colors.field_highlight',
			'manager.design.colors.field_noise',
			'manager.design.colors.field_halftone',
			'manager.design.colors.field_comic',
			'manager.design.colors.field_photo_paper',
			'manager.design.colors.field_canvas_effect',
			'manager.design.colors.field_faded_photo',
			'manager.design.colors.field_vignette',
			'manager.design.colors.field_gloss',
			'manager.design.colors.field_pixelate',
			'manager.design.colors.modal_color_create_description',
			'manager.design.colors.modal_color_edit_description',
			'manager.design.colors.modal_gradient_create_description',
			'manager.design.colors.modal_gradient_edit_description',
			'manager.design.colors.modal_pattern_create_description',
			'manager.design.colors.modal_pattern_edit_description',
			'manager.design.colors.delete_modal_description',
			'manager.design.colors.delete_color_title',
			'manager.design.colors.delete_gradient_title',
			'manager.design.colors.delete_pattern_title',
			'manager.design.colors.delete_color_body',
			'manager.design.colors.delete_gradient_body',
			'manager.design.colors.delete_pattern_body',
			'manager.design.colors.deleting',
			'manager.design.colors.direction_0',
			'manager.design.colors.direction_45',
			'manager.design.colors.direction_90',
			'manager.design.colors.direction_135',
			'manager.design.colors.direction_180',
			'manager.design.colors.size_small',
			'manager.design.colors.size_medium',
			'manager.design.colors.size_large',
			'manager.design.colors.density_low',
			'manager.design.colors.density_medium',
			'manager.design.colors.density_dense',
			'manager.design.colors.softness_crisp',
			'manager.design.colors.softness_medium',
			'manager.design.colors.softness_soft',
			'manager.design.colors.image_mode_cover',
			'manager.design.colors.image_mode_contain',
			'manager.design.colors.image_mode_repeat',
			'manager.design.colors.image_mode_center',
			'manager.design.colors.image_mode_stretch',
			'manager.design.colors.media_upload',
			'manager.design.colors.media_select',
			'manager.design.colors.media_replace',
			'manager.design.colors.media_remove',
			'manager.design.colors.media_empty',
			'manager.design.colors.media_loading',
			'manager.design.colors.media_library_title',
			'manager.design.colors.media_library_empty',
			'manager.design.colors.media_upload_success',
			'manager.design.colors.media_upload_missing',
			'manager.design.colors.media_upload_failed',
			'manager.design.colors.image_requirement_hint',
			'manager.pages.modal.delete_line_one',
			'manager.pages.modal.deleting',
			'manager.pages.modal.close',
			'manager.pages.actions.help_tooltip',
			'manager.design.colors.item_not_found',
			'manager.design.colors.item_type_invalid',
			'manager.design.colors.color_created_notice',
			'manager.design.colors.color_updated_notice',
			'manager.design.colors.color_deleted_notice',
			'manager.design.colors.gradient_created_notice',
			'manager.design.colors.gradient_updated_notice',
			'manager.design.colors.gradient_deleted_notice',
			'manager.design.colors.pattern_created_notice',
			'manager.design.colors.pattern_updated_notice',
			'manager.design.colors.pattern_deleted_notice',
			'manager.design.colors.validation_name_required',
			'manager.design.colors.validation_hex_invalid',
			'manager.design.colors.validation_gradient_colors_required',
			'manager.design.colors.validation_angle_invalid',
			'manager.design.colors.validation_pattern_colors_required',
				'manager.design.colors.validation_pattern_type_invalid',
				'manager.design.colors.validation_image_required',
			'manager.design.colors.validation_image_type_invalid',
				'manager.design.colors.validation_image_size_invalid',
				'manager.design.colors.validation_name_duplicate',
				'manager.design.colors.validation_name_duplicate_hint',
			'manager.design.colors.pattern_group_graphic',
			'manager.design.colors.pattern_group_image',
			'manager.design.colors.count_color_one',
			'manager.design.colors.count_color_few',
			'manager.design.colors.count_color_many',
			'manager.design.colors.count_gradient_one',
			'manager.design.colors.count_gradient_few',
			'manager.design.colors.count_gradient_many',
			'manager.design.colors.count_pattern_one',
			'manager.design.colors.count_pattern_few',
			'manager.design.colors.count_pattern_many',
			'manager.pages.actions.help_tooltip',
			'manager.pages.actions.save',
			'manager.pages.actions.saving',
			'manager.pages.actions.delete',
			'manager.pages.actions.cancel',
			'manager.pages.modal.close',
			'manager.design.colors.group_basic',
			'manager.design.colors.group_colors',
			'manager.design.colors.group_shape',
			'manager.design.colors.group_effects',
			'manager.design.colors.group_extra',
			'manager.design.colors.group_image',
			'manager.design.colors.group_placement',
			'manager.design.colors.group_light',
			'manager.design.colors.group_texture',
			'manager.design.colors.switch_on',
			'manager.design.colors.switch_off',
			'manager.design.colors.slot_background',
			'manager.design.colors.slot_layer_one',
			'manager.design.colors.slot_layer_two',
			'manager.design.colors.slot_layer_three',
			'manager.design.colors.slot_line_color',
			'manager.design.colors.slot_second_layer_color',
			'manager.design.colors.slot_alternate_color',
			'manager.design.colors.slot_ring_color',
			'manager.design.colors.slot_orbit_color',
			'manager.design.colors.slot_color_one',
			'manager.design.colors.slot_color_two',
			'manager.design.colors.slot_color_three',
			'manager.design.colors.slot_blob_one',
			'manager.design.colors.slot_blob_two',
			'manager.design.colors.slot_blob_three',
			'manager.design.colors.slot_blob_four',
			'manager.design.colors.slot_blob_five',
			'manager.design.colors.slot_blob_six',
			'manager.design.colors.slot_blob_seven',
			'manager.design.colors.slot_beam_color',
			'manager.design.colors.slot_wave_one',
			'manager.design.colors.slot_wave_two',
			'manager.design.colors.slot_stroke_one',
			'manager.design.colors.slot_stroke_two',
			'manager.design.colors.slot_stroke_three',
			'manager.design.colors.slot_stroke_four',
			'manager.design.colors.slot_stroke_five',
			'manager.design.colors.slot_blot_one',
			'manager.design.colors.slot_blot_two',
			'manager.design.colors.slot_blot_three',
			'manager.design.colors.slot_blot_four',
			'manager.design.colors.slot_blot_five',
			'manager.design.colors.slot_blot_six',
			'manager.design.colors.slot_blot_seven',
			'manager.design.colors.slot_base_color',
			'manager.design.colors.slot_accent_color',
			'manager.design.colors.slot_noise_color',
			'manager.design.colors.slot_element_color',
			'manager.design.colors.slot_overlay_color',
			'manager.design.colors.option_soft',
			'manager.design.colors.option_crisp',
			'manager.design.colors.option_triangles',
			'manager.design.colors.option_diamonds',
			'manager.design.colors.option_hexagons',
			'manager.design.colors.option_polygons',
			'manager.design.colors.option_mixed',
			'manager.design.colors.option_random',
			'manager.design.colors.option_center',
			'manager.design.colors.option_cover',
			'manager.design.colors.option_contain',
			'manager.design.colors.option_repeat',
			'manager.design.colors.option_stretch',
			'manager.design.colors.option_diagonal',
			'manager.design.colors.option_edges',
			'manager.design.colors.option_dry',
			'manager.design.colors.option_dense',
			'manager.design.colors.option_warm',
			'manager.design.colors.option_neutral',
			'manager.design.colors.option_cold',
			'manager.design.colors.option_star',
			'manager.design.colors.option_sparkle',
			'manager.design.colors.option_drop',
			'manager.design.colors.option_leaf',
			'manager.design.colors.option_wave',
			'manager.design.colors.option_arc',
			'manager.design.colors.option_heart',
			'manager.design.colors.option_abstract',
			'manager.design.colors.option_geo',
			'manager.design.colors.option_top-left',
			'manager.design.colors.option_top',
			'manager.design.colors.option_top-right',
			'manager.design.colors.option_left',
			'manager.design.colors.option_right',
			'manager.design.colors.option_bottom-left',
			'manager.design.colors.option_bottom',
			'manager.design.colors.option_bottom-right',
		);

		foreach ( self::get_pattern_registry() as $definition ) {
			if ( ! empty( $definition['label_key'] ) ) {
				$keys[] = (string) $definition['label_key'];
			}
			if ( ! empty( $definition['description_key'] ) ) {
				$keys[] = (string) $definition['description_key'];
			}
		}

		return array_values( array_unique( $keys ) );
	}

	public static function get_manager_payload( string $api_url = '', string $nonce = '' ): array {
		$payload = class_exists( 'Sonyra_Site_Manager_I18n' ) ? Sonyra_Site_Manager_I18n::get_payload() : array(
			'currentLocale'  => 'ru_RU',
			'fallbackLocale' => 'ru_RU',
			'dictionary'     => array(),
		);
		$dictionary = array();

		foreach ( self::get_i18n_keys() as $key ) {
			$dictionary[ $key ] = self::text( $key );
		}

		$payload['dictionary'] = $dictionary;
		$payload['messages']   = array(
			'destructiveModalSubtitle' => isset( $dictionary['manager.pages.modal.delete_line_one'] ) ? (string) $dictionary['manager.pages.modal.delete_line_one'] : '',
		);
		$payload['data']              = self::get_data();
		$payload['summary']           = self::get_summary( $payload['data'] );
		$payload['patternRegistry']   = self::get_pattern_registry_payload();
		$payload['apiUrl']            = esc_url_raw( $api_url );
		$payload['mediaUploadUrl']    = function_exists( 'rest_url' ) ? esc_url_raw( rest_url( 'sonyra-site-manager/v1/design/colors/media' ) ) : '';
		$payload['mediaLibraryUrl']   = function_exists( 'rest_url' ) ? esc_url_raw( rest_url( 'sonyra-site-manager/v1/design/colors/media-library' ) ) : '';
		$payload['maxImageUploadSize'] = self::get_max_image_upload_size_bytes();
		$payload['allowedImageMimeTypes'] = self::get_allowed_image_mime_types();
		$payload['nonce']             = sanitize_text_field( $nonce );
		$payload['helpKey']           = 'design.colors.controller';

		return $payload;
	}

	private static function upsert_item( string $entity_type, string $item_id, array $payload ) {
		$data           = self::get_data();
		$collection_key = self::get_collection_key( $entity_type );
		$item_id        = '' !== $item_id ? self::sanitize_id( $item_id ) : '';

		if ( '' === $collection_key ) {
			return new WP_Error( 'sonyra_color_controller_item_type_invalid', self::text( 'manager.design.colors.item_type_invalid' ), array( 'status' => 400 ) );
		}

		$existing_items = isset( $data[ $collection_key ] ) && is_array( $data[ $collection_key ] ) ? $data[ $collection_key ] : array();
		$current_item   = null;
		$next_items     = array();

		foreach ( $existing_items as $existing_item ) {
			if ( ! is_array( $existing_item ) ) {
				continue;
			}

			if ( '' !== $item_id && isset( $existing_item['id'] ) && $item_id === self::sanitize_id( (string) $existing_item['id'] ) ) {
				$current_item = $existing_item;
				continue;
			}

			$next_items[] = $existing_item;
		}

		if ( '' !== $item_id && null === $current_item ) {
			return new WP_Error( 'sonyra_color_controller_item_not_found', self::text( 'manager.design.colors.item_not_found' ), array( 'status' => 404 ) );
		}

		$prepared = self::prepare_item_for_save( $entity_type, $payload, $current_item, $data );

		if ( is_wp_error( $prepared ) ) {
			return $prepared;
		}

		$next_items[] = $prepared;
		$data[ $collection_key ]            = array_values( $next_items );
		$data['library_meta']['updated_at'] = self::now_iso8601();

		$saved = self::save_data( $data );

		if ( is_wp_error( $saved ) ) {
			return $saved;
		}

		return array(
			'item'    => $prepared,
			'data'    => $saved,
			'summary' => self::get_summary( $saved ),
		);
	}

	private static function prepare_item_for_save( string $entity_type, array $payload, ?array $current_item, array $full_data ) {
		switch ( $entity_type ) {
			case 'color':
				return self::prepare_color_item( $payload, $current_item, $full_data );
			case 'gradient':
				return self::prepare_gradient_item( $payload, $current_item, $full_data );
			case 'pattern':
				return self::prepare_pattern_item( $payload, $current_item, $full_data );
			default:
				return new WP_Error( 'sonyra_color_controller_item_type_invalid', self::text( 'manager.design.colors.item_type_invalid' ), array( 'status' => 400 ) );
		}
	}

	private static function prepare_color_item( array $payload, ?array $current_item, array $full_data ) {
		$name = isset( $payload['name'] ) ? sanitize_text_field( (string) $payload['name'] ) : '';
		$hex  = self::normalize_hex( isset( $payload['value_hex'] ) ? $payload['value_hex'] : '' );

		if ( '' === $name ) {
			return self::validation_error( 'name', 'manager.design.colors.validation_name_required' );
		}

		if ( '' === $hex ) {
			return self::validation_error( 'value_hex', 'manager.design.colors.validation_hex_invalid' );
		}

		if ( self::is_name_taken( isset( $full_data['colors'] ) && is_array( $full_data['colors'] ) ? $full_data['colors'] : array(), $name, $current_item ) ) {
			return self::duplicate_name_error();
		}

		$slug       = self::build_unique_slug( isset( $payload['slug'] ) ? (string) $payload['slug'] : $name, $full_data['colors'], $current_item );
		$role       = self::resolve_role( isset( $payload['role'] ) ? (string) $payload['role'] : '', 'color', $slug, $full_data, $current_item );

		if ( is_wp_error( $role ) ) {
			return $role;
		}
		$created_at = isset( $current_item['created_at'] ) ? sanitize_text_field( (string) $current_item['created_at'] ) : self::now_iso8601();
		$item       = self::normalize_color(
			array(
				'id'          => isset( $current_item['id'] ) ? (string) $current_item['id'] : 'color-' . $slug,
				'name'        => $name,
				'slug'        => $slug,
				'type'        => 'color',
				'value_hex'   => $hex,
				'value_rgb'   => self::hex_to_rgb( $hex ),
				'role'        => $role,
				'description' => isset( $payload['description'] ) ? sanitize_textarea_field( (string) $payload['description'] ) : '',
				'source'      => isset( $current_item['source'] ) ? (string) $current_item['source'] : 'custom',
				'created_at'  => $created_at,
				'updated_at'  => self::now_iso8601(),
			)
		);

		if ( empty( $item ) ) {
			return new WP_Error( 'sonyra_color_controller_item_invalid', self::text( 'manager.design.colors.payload_invalid' ), array( 'status' => 400 ) );
		}

		return $item;
	}

	private static function prepare_gradient_item( array $payload, ?array $current_item, array $full_data ) {
		$name      = isset( $payload['name'] ) ? sanitize_text_field( (string) $payload['name'] ) : '';
		$first_hex = self::normalize_hex( isset( $payload['first_color_hex'] ) ? $payload['first_color_hex'] : '' );
		$second_hex = self::normalize_hex( isset( $payload['second_color_hex'] ) ? $payload['second_color_hex'] : '' );
		$angle     = isset( $payload['angle'] ) ? (int) $payload['angle'] : 135;

		if ( '' === $name ) {
			return self::validation_error( 'name', 'manager.design.colors.validation_name_required' );
		}

		if ( '' === $first_hex || '' === $second_hex ) {
			return self::validation_error( 'stops', 'manager.design.colors.validation_gradient_colors_required' );
		}

		if ( $angle < 0 || $angle > 360 ) {
			return self::validation_error( 'angle', 'manager.design.colors.validation_angle_invalid' );
		}

		if ( self::is_name_taken( isset( $full_data['gradients'] ) && is_array( $full_data['gradients'] ) ? $full_data['gradients'] : array(), $name, $current_item ) ) {
			return self::duplicate_name_error();
		}

		$slug       = self::build_unique_slug( isset( $payload['slug'] ) ? (string) $payload['slug'] : $name, $full_data['gradients'], $current_item );
		$role       = self::resolve_role( isset( $payload['role'] ) ? (string) $payload['role'] : '', 'gradient', $slug, $full_data, $current_item );

		if ( is_wp_error( $role ) ) {
			return $role;
		}
		$created_at = isset( $current_item['created_at'] ) ? sanitize_text_field( (string) $current_item['created_at'] ) : self::now_iso8601();
		$item       = self::normalize_gradient(
			array(
				'id'            => isset( $current_item['id'] ) ? (string) $current_item['id'] : 'gradient-' . $slug,
				'name'          => $name,
				'slug'          => $slug,
				'type'          => 'gradient',
				'gradient_type' => 'linear',
				'angle'         => $angle,
				'role'          => $role,
				'description'   => isset( $payload['description'] ) ? sanitize_textarea_field( (string) $payload['description'] ) : '',
				'stops'         => array(
					array(
						'color_hex' => $first_hex,
						'position'  => 0,
					),
					array(
						'color_hex' => $second_hex,
						'position'  => 100,
					),
				),
				'source'        => isset( $current_item['source'] ) ? (string) $current_item['source'] : 'custom',
				'created_at'    => $created_at,
				'updated_at'    => self::now_iso8601(),
			)
		);

		if ( empty( $item ) ) {
			return new WP_Error( 'sonyra_color_controller_item_invalid', self::text( 'manager.design.colors.payload_invalid' ), array( 'status' => 400 ) );
		}

		return $item;
	}

	private static function prepare_pattern_item( array $payload, ?array $current_item, array $full_data ) {
		$name          = isset( $payload['name'] ) ? sanitize_text_field( (string) $payload['name'] ) : '';
		$pattern_type  = self::normalize_pattern_type( isset( $payload['pattern_type'] ) ? (string) $payload['pattern_type'] : '' );
		$pattern_data  = self::normalize_pattern_compatibility_payload(
			$pattern_type,
			isset( $payload['colors'] ) && is_array( $payload['colors'] ) ? $payload['colors'] : array(
				'primary'    => isset( $payload['primary_hex'] ) ? $payload['primary_hex'] : '',
				'background' => isset( $payload['background_hex'] ) ? $payload['background_hex'] : '',
				'accent'     => isset( $payload['accent_hex'] ) ? $payload['accent_hex'] : '',
				'overlay'    => isset( $payload['overlay_hex'] ) ? $payload['overlay_hex'] : '',
			),
			isset( $payload['settings'] ) && is_array( $payload['settings'] ) ? $payload['settings'] : array(
				'size'       => isset( $payload['size'] ) ? $payload['size'] : '',
				'density'    => isset( $payload['density'] ) ? $payload['density'] : '',
				'softness'   => isset( $payload['softness'] ) ? $payload['softness'] : '',
				'opacity'    => isset( $payload['opacity'] ) ? $payload['opacity'] : 72,
				'angle'      => isset( $payload['angle'] ) ? $payload['angle'] : 135,
				'image_mode' => isset( $payload['image_mode'] ) ? $payload['image_mode'] : '',
				'dimness'    => isset( $payload['dimness'] ) ? $payload['dimness'] : 0,
				'blur'       => isset( $payload['blur'] ) ? $payload['blur'] : 0,
			)
		);
		$media         = self::normalize_pattern_media(
			array(
				'attachment_id' => isset( $payload['media']['attachment_id'] ) ? (int) $payload['media']['attachment_id'] : ( isset( $payload['image_attachment_id'] ) ? (int) $payload['image_attachment_id'] : 0 ),
				'url'           => isset( $payload['media']['url'] ) ? (string) $payload['media']['url'] : ( isset( $payload['image_url'] ) ? (string) $payload['image_url'] : '' ),
				'thumb_url'     => isset( $payload['media']['thumb_url'] ) ? (string) $payload['media']['thumb_url'] : ( isset( $payload['image_thumb_url'] ) ? (string) $payload['image_thumb_url'] : '' ),
				'name'          => isset( $payload['media']['name'] ) ? (string) $payload['media']['name'] : ( isset( $payload['image_name'] ) ? (string) $payload['image_name'] : '' ),
				'mime_type'     => isset( $payload['media']['mime_type'] ) ? (string) $payload['media']['mime_type'] : ( isset( $payload['image_mime_type'] ) ? (string) $payload['image_mime_type'] : '' ),
				'width'         => isset( $payload['media']['width'] ) ? (int) $payload['media']['width'] : ( isset( $payload['image_width'] ) ? (int) $payload['image_width'] : 0 ),
				'height'        => isset( $payload['media']['height'] ) ? (int) $payload['media']['height'] : ( isset( $payload['image_height'] ) ? (int) $payload['image_height'] : 0 ),
			)
		);
		$settings      = self::sanitize_pattern_settings( $pattern_type, $pattern_data['settings'] );
		$colors        = self::sanitize_pattern_colors( $pattern_type, $pattern_data['colors'] );

		if ( '' === $name ) {
			return self::validation_error( 'name', 'manager.design.colors.validation_name_required' );
		}

		if ( 'image_pattern' !== $pattern_type && empty( $colors ) ) {
			return self::validation_error( 'colors', 'manager.design.colors.validation_pattern_colors_required' );
		}

		if ( ! in_array( $pattern_type, self::get_pattern_allowlist(), true ) ) {
			return self::validation_error( 'pattern_type', 'manager.design.colors.validation_pattern_type_invalid' );
		}

		if ( 'image_pattern' === $pattern_type && empty( $media ) ) {
			return self::validation_error( 'image_attachment_id', 'manager.design.colors.validation_image_required' );
		}

		if ( self::is_name_taken( isset( $full_data['patterns'] ) && is_array( $full_data['patterns'] ) ? $full_data['patterns'] : array(), $name, $current_item ) ) {
			return self::duplicate_name_error();
		}

		$slug       = self::build_unique_slug( isset( $payload['slug'] ) ? (string) $payload['slug'] : $name, $full_data['patterns'], $current_item );
		$role       = self::resolve_role( isset( $payload['role'] ) ? (string) $payload['role'] : '', 'pattern', $slug, $full_data, $current_item );

		if ( is_wp_error( $role ) ) {
			return $role;
		}
		$created_at = isset( $current_item['created_at'] ) ? sanitize_text_field( (string) $current_item['created_at'] ) : self::now_iso8601();
		$item       = self::normalize_pattern(
			array(
				'id'           => isset( $current_item['id'] ) ? (string) $current_item['id'] : 'pattern-' . $slug,
				'name'         => $name,
				'slug'         => $slug,
				'type'         => 'pattern',
				'pattern_type' => $pattern_type,
				'role'         => $role,
				'description'  => isset( $payload['description'] ) ? sanitize_textarea_field( (string) $payload['description'] ) : '',
				'colors'       => $colors,
				'settings'     => $settings,
				'media'        => $media,
				'source'       => isset( $current_item['source'] ) ? (string) $current_item['source'] : 'custom',
				'created_at'   => $created_at,
				'updated_at'   => self::now_iso8601(),
			)
		);

		if ( empty( $item ) ) {
			return new WP_Error( 'sonyra_color_controller_item_invalid', self::text( 'manager.design.colors.payload_invalid' ), array( 'status' => 400 ) );
		}

		return $item;
	}

	private static function normalize_preset( array $preset ) {
		$name       = isset( $preset['name'] ) ? sanitize_text_field( (string) $preset['name'] ) : '';
		$slug       = self::sanitize_slug( isset( $preset['slug'] ) ? (string) $preset['slug'] : $name );
		$source     = self::sanitize_source( isset( $preset['source'] ) ? (string) $preset['source'] : 'core' );
		$created_at = isset( $preset['created_at'] ) ? sanitize_text_field( (string) $preset['created_at'] ) : '';
		$updated_at = isset( $preset['updated_at'] ) ? sanitize_text_field( (string) $preset['updated_at'] ) : '';

		if ( '' === $name || '' === $slug ) {
			return array();
		}

		return array(
			'id'         => self::sanitize_id( isset( $preset['id'] ) ? (string) $preset['id'] : 'preset-' . $slug ),
			'name'       => $name,
			'slug'       => $slug,
			'type'       => 'preset',
			'colors'     => self::sanitize_reference_list( isset( $preset['colors'] ) && is_array( $preset['colors'] ) ? $preset['colors'] : array() ),
			'gradients'  => self::sanitize_reference_list( isset( $preset['gradients'] ) && is_array( $preset['gradients'] ) ? $preset['gradients'] : array() ),
			'patterns'   => self::sanitize_reference_list( isset( $preset['patterns'] ) && is_array( $preset['patterns'] ) ? $preset['patterns'] : array() ),
			'source'     => '' !== $source ? $source : 'core',
			'created_at' => $created_at,
			'updated_at' => $updated_at,
		);
	}

	private static function sanitize_reference_list( array $references ): array {
		$normalized = array();

		foreach ( $references as $reference ) {
			$reference = self::sanitize_slug( (string) $reference );

			if ( '' === $reference || in_array( $reference, $normalized, true ) ) {
				continue;
			}

			$normalized[] = $reference;
		}

		return $normalized;
	}

	private static function get_collection_key( string $entity_type ): string {
		switch ( strtolower( trim( $entity_type ) ) ) {
			case 'color':
			case 'colors':
				return 'colors';
			case 'gradient':
			case 'gradients':
				return 'gradients';
			case 'pattern':
			case 'patterns':
				return 'patterns';
			default:
				return '';
		}
	}

	private static function validation_error( string $field, string $message_key, string $helper_key = '' ) {
		$error_data = array(
			'status' => 400,
			'errors' => array(
				$field => self::text( $message_key ),
			),
		);

		if ( '' !== $helper_key ) {
			$error_data['helper_texts'] = array(
				$field => self::text( $helper_key ),
			);
		}

		return new WP_Error(
			'sonyra_color_controller_validation_failed',
			self::text( $message_key ),
			$error_data
		);
	}

	private static function build_unique_slug( string $candidate, array $collection, ?array $current_item ): string {
		$base_slug = self::sanitize_slug( $candidate );
		$base_slug = '' !== $base_slug ? $base_slug : sanitize_key( uniqid( 'item-', false ) );
		$slug      = $base_slug;
		$index     = 2;

		while ( self::is_slug_taken( $collection, $slug, $current_item ) ) {
			$slug = $base_slug . '-' . $index;
			$index++;
		}

		return $slug;
	}

	private static function is_slug_taken( array $collection, string $slug, ?array $current_item ): bool {
		$current_id = isset( $current_item['id'] ) ? self::sanitize_id( (string) $current_item['id'] ) : '';

		foreach ( $collection as $item ) {
			if ( ! is_array( $item ) ) {
				continue;
			}

			if ( $current_id && isset( $item['id'] ) && $current_id === self::sanitize_id( (string) $item['id'] ) ) {
				continue;
			}

			if ( isset( $item['slug'] ) && $slug === self::sanitize_slug( (string) $item['slug'] ) ) {
				return true;
			}
		}

		return false;
	}

	private static function normalize_scope_name( string $name ): string {
		$normalized = preg_replace( '/\s+/u', ' ', trim( wp_strip_all_tags( $name ) ) );
		$normalized = is_string( $normalized ) ? $normalized : '';

		if ( '' === $normalized ) {
			return '';
		}

		if ( function_exists( 'mb_strtolower' ) ) {
			return mb_strtolower( $normalized, 'UTF-8' );
		}

		return strtolower( $normalized );
	}

	private static function is_name_taken( array $collection, string $candidate_name, ?array $current_item ): bool {
		$normalized_candidate = self::normalize_scope_name( $candidate_name );
		$current_id           = isset( $current_item['id'] ) ? self::sanitize_id( (string) $current_item['id'] ) : '';

		if ( '' === $normalized_candidate ) {
			return false;
		}

		foreach ( $collection as $item ) {
			if ( ! is_array( $item ) ) {
				continue;
			}

			if ( $current_id && isset( $item['id'] ) && $current_id === self::sanitize_id( (string) $item['id'] ) ) {
				continue;
			}

			if ( isset( $item['name'] ) && $normalized_candidate === self::normalize_scope_name( (string) $item['name'] ) ) {
				return true;
			}
		}

		return false;
	}

	private static function duplicate_name_error(): WP_Error {
		return self::validation_error(
			'name',
			'manager.design.colors.validation_name_duplicate',
			'manager.design.colors.validation_name_duplicate_hint'
		);
	}

	private static function resolve_role( string $candidate_role, string $entity_type, string $slug, array $full_data, ?array $current_item ) {
		$role = self::sanitize_role( $candidate_role );

		if ( '' === $role ) {
			$role = self::sanitize_role( $entity_type . '.' . str_replace( '-', '.', $slug ) );
		}

		if ( '' === $role ) {
			return self::validation_error( 'role', 'manager.design.colors.validation_role_invalid' );
		}

		if ( self::is_role_taken( $full_data, $role, $current_item ) ) {
			return self::validation_error( 'role', 'manager.design.colors.validation_role_duplicate' );
		}

		return $role;
	}

	private static function is_role_taken( array $full_data, string $role, ?array $current_item ): bool {
		$current_id = isset( $current_item['id'] ) ? self::sanitize_id( (string) $current_item['id'] ) : '';

		foreach ( array( 'colors', 'gradients', 'patterns' ) as $collection_key ) {
			foreach ( isset( $full_data[ $collection_key ] ) && is_array( $full_data[ $collection_key ] ) ? $full_data[ $collection_key ] : array() as $item ) {
				if ( ! is_array( $item ) ) {
					continue;
				}

				if ( $current_id && isset( $item['id'] ) && $current_id === self::sanitize_id( (string) $item['id'] ) ) {
					continue;
				}

				if ( isset( $item['role'] ) && $role === self::sanitize_role( (string) $item['role'] ) ) {
					return true;
				}
			}
		}

		return false;
	}

	private static function now_iso8601(): string {
		return gmdate( 'c' );
	}

	private static function get_pattern_allowlist(): array {
		return array_keys( self::get_pattern_registry() );
	}

	private static function normalize_pattern_type( string $pattern_type ): string {
		$pattern_type = sanitize_key( $pattern_type );
		$legacy_map   = array(
			'soft_grid'         => 'grid',
			'diagonal_lines'    => 'lines',
			'diagonal_wave'     => 'waves',
			'thin_stripes'      => 'lines',
			'checkerboard'      => 'geometric_mosaic',
			'isometric_grid'    => 'grid',
			'orbits'            => 'circles_orbits',
			'radial_lines'      => 'light_beam',
			'radial_aura'       => 'soft_blobs',
			'light_burst'       => 'light_beam',
			'deep_background'   => 'soft_blobs',
			'accent_orbit'      => 'circles_orbits',
			'gradient_mist'     => 'soft_blobs',
			'aurora'            => 'soft_blobs',
			'soft_glow'         => 'soft_blobs',
			'airy_haze'         => 'soft_blobs',
			'light_spots'       => 'soft_blobs',
			'ink_blots'         => 'ink_blots',
			'paint_strokes'     => 'paint_strokes',
			'watercolor_spots'  => 'soft_blobs',
			'liquid_gradient'   => 'soft_blobs',
			'marble_flow'       => 'marble',
			'abstract_waves'    => 'waves',
			'noisy_texture'     => 'noise',
			'paper_grain'       => 'noise',
			'glass_glow'        => 'light_beam',
			'plastic_gloss'     => 'light_beam',
			'soft_noise'        => 'noise',
			'stardust'          => 'decorative_elements',
			'pixel_sprinkle'    => 'dots',
			'motion_lines'      => 'lines',
			'light_rings'       => 'circles_orbits',
			'bubbles'           => 'circles_orbits',
			'micro_dots'        => 'dots',
			'fine_texture'      => 'noise',
		);

		return isset( $legacy_map[ $pattern_type ] ) ? $legacy_map[ $pattern_type ] : $pattern_type;
	}

	public static function get_pattern_registry(): array {
		return array(
			'dots' => array(
				'group'            => 'graphic',
				'editor_kind'      => 'graphic',
				'label_key'        => 'manager.design.colors.pattern_type_dots',
				'description_key'  => 'manager.design.colors.pattern_desc_dots',
				'preview_style'    => 'dots',
				'default_colors'   => array(
					'background' => '#FFFFFF',
					'layer_1'    => '#7C3AED',
					'layer_2'    => '#EC4899',
					'layer_3'    => '#38BDF8',
				),
				'default_settings' => array(
					'dot_size'         => 12,
					'spacing'          => 28,
					'density'          => 72,
					'opacity'          => 82,
					'layer_count'      => 2,
					'layer_offset'     => 14,
					'random_offset'    => false,
					'edge_style'       => 'soft',
				),
				'color_slots'      => array(
					array( 'key' => 'background', 'label_key' => 'manager.design.colors.slot_background' ),
					array( 'key' => 'layer_1', 'label_key' => 'manager.design.colors.slot_layer_one' ),
					array( 'key' => 'layer_2', 'label_key' => 'manager.design.colors.slot_layer_two', 'visible_when' => array( 'setting' => 'layer_count', 'min' => 2 ) ),
					array( 'key' => 'layer_3', 'label_key' => 'manager.design.colors.slot_layer_three', 'visible_when' => array( 'setting' => 'layer_count', 'min' => 3 ) ),
				),
				'controls'         => array(
					array( 'key' => 'dot_size', 'type' => 'slider', 'group' => 'shape', 'label_key' => 'manager.design.colors.field_dot_size', 'min' => 4, 'max' => 48, 'step' => 1, 'unit' => 'px' ),
					array( 'key' => 'spacing', 'type' => 'slider', 'group' => 'shape', 'label_key' => 'manager.design.colors.field_spacing', 'min' => 8, 'max' => 72, 'step' => 1, 'unit' => 'px' ),
					array( 'key' => 'density', 'type' => 'slider', 'group' => 'basic', 'label_key' => 'manager.design.colors.field_density', 'min' => 10, 'max' => 100, 'step' => 1, 'unit' => '%' ),
					array( 'key' => 'opacity', 'type' => 'slider', 'group' => 'effects', 'label_key' => 'manager.design.colors.field_opacity', 'min' => 0, 'max' => 100, 'step' => 1, 'unit' => '%' ),
					array( 'key' => 'layer_count', 'type' => 'stepper', 'group' => 'colors', 'label_key' => 'manager.design.colors.field_layer_count', 'min' => 1, 'max' => 3, 'step' => 1 ),
					array( 'key' => 'layer_offset', 'type' => 'slider', 'group' => 'shape', 'label_key' => 'manager.design.colors.field_layer_offset', 'min' => 0, 'max' => 40, 'step' => 1, 'unit' => 'px' ),
					array( 'key' => 'random_offset', 'type' => 'switch', 'group' => 'effects', 'label_key' => 'manager.design.colors.field_random_offset' ),
					array( 'key' => 'edge_style', 'type' => 'segmented', 'group' => 'effects', 'label_key' => 'manager.design.colors.field_edge_style', 'options' => array( 'soft', 'crisp' ) ),
				),
			),
			'grid' => array(
				'group'            => 'graphic',
				'editor_kind'      => 'graphic',
				'label_key'        => 'manager.design.colors.pattern_type_grid',
				'description_key'  => 'manager.design.colors.pattern_desc_grid',
				'preview_style'    => 'grid',
				'default_colors'   => array(
					'background' => '#F8FAFC',
					'line_color' => '#7C3AED',
					'layer_2'    => '#EC4899',
				),
				'default_settings' => array(
					'cell_size'        => 28,
					'line_thickness'   => 2,
					'opacity'          => 62,
					'contrast'         => 56,
					'angle'            => 0,
					'second_layer'     => false,
					'layer_offset'     => 10,
					'line_softness'    => 2,
				),
				'color_slots'      => array(
					array( 'key' => 'background', 'label_key' => 'manager.design.colors.slot_background' ),
					array( 'key' => 'line_color', 'label_key' => 'manager.design.colors.slot_line_color' ),
					array( 'key' => 'layer_2', 'label_key' => 'manager.design.colors.slot_second_layer_color', 'visible_when' => array( 'setting' => 'second_layer', 'equals' => true ) ),
				),
				'controls'         => array(
					array( 'key' => 'cell_size', 'type' => 'slider', 'group' => 'shape', 'label_key' => 'manager.design.colors.field_cell_size', 'min' => 12, 'max' => 80, 'step' => 1, 'unit' => 'px' ),
					array( 'key' => 'line_thickness', 'type' => 'slider', 'group' => 'shape', 'label_key' => 'manager.design.colors.field_line_thickness', 'min' => 1, 'max' => 8, 'step' => 1, 'unit' => 'px' ),
					array( 'key' => 'opacity', 'type' => 'slider', 'group' => 'effects', 'label_key' => 'manager.design.colors.field_opacity', 'min' => 0, 'max' => 100, 'step' => 1, 'unit' => '%' ),
					array( 'key' => 'contrast', 'type' => 'slider', 'group' => 'effects', 'label_key' => 'manager.design.colors.field_contrast', 'min' => 0, 'max' => 100, 'step' => 1, 'unit' => '%' ),
					array( 'key' => 'angle', 'type' => 'angle', 'group' => 'shape', 'label_key' => 'manager.design.colors.field_angle', 'min' => 0, 'max' => 180, 'step' => 5 ),
					array( 'key' => 'second_layer', 'type' => 'switch', 'group' => 'effects', 'label_key' => 'manager.design.colors.field_second_layer' ),
					array( 'key' => 'layer_offset', 'type' => 'slider', 'group' => 'shape', 'label_key' => 'manager.design.colors.field_layer_offset', 'min' => 0, 'max' => 36, 'step' => 1, 'unit' => 'px' ),
					array( 'key' => 'line_softness', 'type' => 'stepper', 'group' => 'effects', 'label_key' => 'manager.design.colors.field_line_softness', 'min' => 1, 'max' => 5, 'step' => 1 ),
				),
			),
			'lines' => array(
				'group'            => 'graphic',
				'editor_kind'      => 'graphic',
				'label_key'        => 'manager.design.colors.pattern_type_lines',
				'description_key'  => 'manager.design.colors.pattern_desc_lines',
				'preview_style'    => 'lines',
				'default_colors'   => array(
					'background'   => '#FFFFFF',
					'line_color'   => '#4C1D95',
					'second_color' => '#EC4899',
				),
				'default_settings' => array(
					'angle'            => 135,
					'line_thickness'   => 4,
					'line_spacing'     => 22,
					'opacity'          => 72,
					'edge_softness'    => 2,
					'second_layer'     => false,
					'layer_offset'     => 10,
					'alternate_colors' => false,
				),
				'color_slots'      => array(
					array( 'key' => 'background', 'label_key' => 'manager.design.colors.slot_background' ),
					array( 'key' => 'line_color', 'label_key' => 'manager.design.colors.slot_line_color' ),
					array( 'key' => 'second_color', 'label_key' => 'manager.design.colors.slot_alternate_color', 'visible_when' => array( 'setting' => 'alternate_colors', 'equals' => true ) ),
				),
				'controls'         => array(
					array( 'key' => 'angle', 'type' => 'angle', 'group' => 'shape', 'label_key' => 'manager.design.colors.field_angle', 'min' => 0, 'max' => 180, 'step' => 5 ),
					array( 'key' => 'line_thickness', 'type' => 'slider', 'group' => 'shape', 'label_key' => 'manager.design.colors.field_line_thickness', 'min' => 1, 'max' => 14, 'step' => 1, 'unit' => 'px' ),
					array( 'key' => 'line_spacing', 'type' => 'slider', 'group' => 'shape', 'label_key' => 'manager.design.colors.field_line_spacing', 'min' => 6, 'max' => 48, 'step' => 1, 'unit' => 'px' ),
					array( 'key' => 'opacity', 'type' => 'slider', 'group' => 'effects', 'label_key' => 'manager.design.colors.field_opacity', 'min' => 0, 'max' => 100, 'step' => 1, 'unit' => '%' ),
					array( 'key' => 'edge_softness', 'type' => 'stepper', 'group' => 'effects', 'label_key' => 'manager.design.colors.field_edge_softness', 'min' => 1, 'max' => 5, 'step' => 1 ),
					array( 'key' => 'second_layer', 'type' => 'switch', 'group' => 'effects', 'label_key' => 'manager.design.colors.field_second_layer' ),
					array( 'key' => 'layer_offset', 'type' => 'slider', 'group' => 'shape', 'label_key' => 'manager.design.colors.field_layer_offset', 'min' => 0, 'max' => 30, 'step' => 1, 'unit' => 'px' ),
					array( 'key' => 'alternate_colors', 'type' => 'switch', 'group' => 'colors', 'label_key' => 'manager.design.colors.field_alternate_colors' ),
				),
			),
			'circles_orbits' => array(
				'group'            => 'graphic',
				'editor_kind'      => 'graphic',
				'label_key'        => 'manager.design.colors.pattern_type_circles_orbits',
				'description_key'  => 'manager.design.colors.pattern_desc_circles_orbits',
				'preview_style'    => 'circles_orbits',
				'default_colors'   => array(
					'background' => '#FFFFFF',
					'ring_color' => '#7C3AED',
					'orbit_color' => '#EC4899',
				),
				'default_settings' => array(
					'circle_size'      => 42,
					'line_thickness'   => 3,
					'ring_count'       => 3,
					'spacing'          => 14,
					'center_position'  => 'center',
					'opacity'          => 72,
					'second_layer'     => false,
					'rotation'         => 45,
					'orbit_gap'        => 2,
				),
				'color_slots'      => array(
					array( 'key' => 'background', 'label_key' => 'manager.design.colors.slot_background' ),
					array( 'key' => 'ring_color', 'label_key' => 'manager.design.colors.slot_ring_color' ),
					array( 'key' => 'orbit_color', 'label_key' => 'manager.design.colors.slot_orbit_color' ),
				),
				'controls'         => array(
					array( 'key' => 'circle_size', 'type' => 'slider', 'group' => 'shape', 'label_key' => 'manager.design.colors.field_circle_size', 'min' => 16, 'max' => 96, 'step' => 1, 'unit' => 'px' ),
					array( 'key' => 'line_thickness', 'type' => 'slider', 'group' => 'shape', 'label_key' => 'manager.design.colors.field_line_thickness', 'min' => 1, 'max' => 10, 'step' => 1, 'unit' => 'px' ),
					array( 'key' => 'ring_count', 'type' => 'stepper', 'group' => 'basic', 'label_key' => 'manager.design.colors.field_ring_count', 'min' => 1, 'max' => 5, 'step' => 1 ),
					array( 'key' => 'spacing', 'type' => 'slider', 'group' => 'shape', 'label_key' => 'manager.design.colors.field_spacing', 'min' => 4, 'max' => 40, 'step' => 1, 'unit' => 'px' ),
					array( 'key' => 'center_position', 'type' => 'position', 'group' => 'shape', 'label_key' => 'manager.design.colors.field_center_position' ),
					array( 'key' => 'opacity', 'type' => 'slider', 'group' => 'effects', 'label_key' => 'manager.design.colors.field_opacity', 'min' => 0, 'max' => 100, 'step' => 1, 'unit' => '%' ),
					array( 'key' => 'second_layer', 'type' => 'switch', 'group' => 'effects', 'label_key' => 'manager.design.colors.field_second_layer' ),
					array( 'key' => 'rotation', 'type' => 'angle', 'group' => 'shape', 'label_key' => 'manager.design.colors.field_rotation', 'min' => 0, 'max' => 180, 'step' => 5 ),
					array( 'key' => 'orbit_gap', 'type' => 'stepper', 'group' => 'effects', 'label_key' => 'manager.design.colors.field_orbit_gap', 'min' => 0, 'max' => 5, 'step' => 1 ),
				),
			),
			'geometric_mosaic' => array(
				'group'            => 'graphic',
				'editor_kind'      => 'graphic',
				'label_key'        => 'manager.design.colors.pattern_type_geometric_mosaic',
				'description_key'  => 'manager.design.colors.pattern_desc_geometric_mosaic',
				'preview_style'    => 'geometric_mosaic',
				'default_colors'   => array(
					'background' => '#FFFFFF',
					'color_1'    => '#7C3AED',
					'color_2'    => '#EC4899',
					'color_3'    => '#38BDF8',
				),
				'default_settings' => array(
					'shape_type'       => 'triangles',
					'shape_size'       => 28,
					'density'          => 72,
					'use_third_color'  => true,
					'contrast'         => 62,
					'randomize'        => true,
					'opacity'          => 82,
					'rotation'         => 15,
				),
				'color_slots'      => array(
					array( 'key' => 'background', 'label_key' => 'manager.design.colors.slot_background' ),
					array( 'key' => 'color_1', 'label_key' => 'manager.design.colors.slot_color_one' ),
					array( 'key' => 'color_2', 'label_key' => 'manager.design.colors.slot_color_two' ),
					array( 'key' => 'color_3', 'label_key' => 'manager.design.colors.slot_color_three', 'visible_when' => array( 'setting' => 'use_third_color', 'equals' => true ) ),
				),
				'controls'         => array(
					array( 'key' => 'shape_type', 'type' => 'segmented', 'group' => 'shape', 'label_key' => 'manager.design.colors.field_shape_type', 'options' => array( 'triangles', 'diamonds', 'hexagons', 'polygons', 'mixed' ) ),
					array( 'key' => 'shape_size', 'type' => 'slider', 'group' => 'shape', 'label_key' => 'manager.design.colors.field_shape_size', 'min' => 12, 'max' => 64, 'step' => 1, 'unit' => 'px' ),
					array( 'key' => 'density', 'type' => 'slider', 'group' => 'basic', 'label_key' => 'manager.design.colors.field_density', 'min' => 10, 'max' => 100, 'step' => 1, 'unit' => '%' ),
					array( 'key' => 'use_third_color', 'type' => 'switch', 'group' => 'colors', 'label_key' => 'manager.design.colors.field_use_third_color' ),
					array( 'key' => 'contrast', 'type' => 'slider', 'group' => 'effects', 'label_key' => 'manager.design.colors.field_contrast', 'min' => 0, 'max' => 100, 'step' => 1, 'unit' => '%' ),
					array( 'key' => 'randomize', 'type' => 'switch', 'group' => 'effects', 'label_key' => 'manager.design.colors.field_randomize' ),
					array( 'key' => 'opacity', 'type' => 'slider', 'group' => 'effects', 'label_key' => 'manager.design.colors.field_opacity', 'min' => 0, 'max' => 100, 'step' => 1, 'unit' => '%' ),
					array( 'key' => 'rotation', 'type' => 'angle', 'group' => 'shape', 'label_key' => 'manager.design.colors.field_rotation', 'min' => 0, 'max' => 180, 'step' => 5 ),
				),
			),
			'soft_blobs' => array(
				'group'            => 'graphic',
				'editor_kind'      => 'graphic',
				'label_key'        => 'manager.design.colors.pattern_type_soft_blobs',
				'description_key'  => 'manager.design.colors.pattern_desc_soft_blobs',
				'preview_style'    => 'soft_blobs',
				'default_colors'   => array(
					'background' => '#FFFFFF',
					'blob_1'     => '#EDE9FE',
					'blob_2'     => '#FCE7F3',
					'blob_3'     => '#DBEAFE',
					'blob_4'     => '#FDE68A',
					'blob_5'     => '#C7D2FE',
					'blob_6'     => '#A7F3D0',
					'blob_7'     => '#FBCFE8',
				),
				'default_settings' => array(
					'blob_count'       => 4,
					'blob_size'        => 42,
					'blur'             => 34,
					'opacity'          => 86,
					'contrast'         => 52,
					'layout'           => 'random',
					'transition_softness' => 4,
				),
				'color_slots'      => array(
					array( 'key' => 'background', 'label_key' => 'manager.design.colors.slot_background' ),
					array( 'key' => 'blob_1', 'label_key' => 'manager.design.colors.slot_blob_one' ),
					array( 'key' => 'blob_2', 'label_key' => 'manager.design.colors.slot_blob_two' ),
					array( 'key' => 'blob_3', 'label_key' => 'manager.design.colors.slot_blob_three' ),
					array( 'key' => 'blob_4', 'label_key' => 'manager.design.colors.slot_blob_four', 'visible_when' => array( 'setting' => 'blob_count', 'min' => 4 ) ),
					array( 'key' => 'blob_5', 'label_key' => 'manager.design.colors.slot_blob_five', 'visible_when' => array( 'setting' => 'blob_count', 'min' => 5 ) ),
					array( 'key' => 'blob_6', 'label_key' => 'manager.design.colors.slot_blob_six', 'visible_when' => array( 'setting' => 'blob_count', 'min' => 6 ) ),
					array( 'key' => 'blob_7', 'label_key' => 'manager.design.colors.slot_blob_seven', 'visible_when' => array( 'setting' => 'blob_count', 'min' => 7 ) ),
				),
				'controls'         => array(
					array( 'key' => 'blob_count', 'type' => 'stepper', 'group' => 'basic', 'label_key' => 'manager.design.colors.field_blob_count', 'min' => 3, 'max' => 7, 'step' => 1 ),
					array( 'key' => 'blob_size', 'type' => 'slider', 'group' => 'shape', 'label_key' => 'manager.design.colors.field_blob_size', 'min' => 18, 'max' => 80, 'step' => 1, 'unit' => 'px' ),
					array( 'key' => 'blur', 'type' => 'slider', 'group' => 'effects', 'label_key' => 'manager.design.colors.field_blur', 'min' => 0, 'max' => 60, 'step' => 1, 'unit' => 'px' ),
					array( 'key' => 'opacity', 'type' => 'slider', 'group' => 'effects', 'label_key' => 'manager.design.colors.field_opacity', 'min' => 0, 'max' => 100, 'step' => 1, 'unit' => '%' ),
					array( 'key' => 'contrast', 'type' => 'slider', 'group' => 'effects', 'label_key' => 'manager.design.colors.field_contrast', 'min' => 0, 'max' => 100, 'step' => 1, 'unit' => '%' ),
					array( 'key' => 'layout', 'type' => 'segmented', 'group' => 'basic', 'label_key' => 'manager.design.colors.field_layout', 'options' => array( 'random', 'center', 'diagonal', 'edges' ) ),
					array( 'key' => 'transition_softness', 'type' => 'stepper', 'group' => 'effects', 'label_key' => 'manager.design.colors.field_transition_softness', 'min' => 1, 'max' => 5, 'step' => 1 ),
				),
			),
			'light_beam' => array(
				'group'            => 'graphic',
				'editor_kind'      => 'graphic',
				'label_key'        => 'manager.design.colors.pattern_type_light_beam',
				'description_key'  => 'manager.design.colors.pattern_desc_light_beam',
				'preview_style'    => 'light_beam',
				'default_colors'   => array(
					'background' => '#0F172A',
					'beam_color' => '#FDE68A',
				),
				'default_settings' => array(
					'source_position'  => 'top-left',
					'angle'            => 135,
					'beam_length'      => 72,
					'beam_width'       => 38,
					'intensity'        => 74,
					'opacity'          => 82,
					'softness'         => 4,
					'beam_count'       => 2,
				),
				'color_slots'      => array(
					array( 'key' => 'background', 'label_key' => 'manager.design.colors.slot_background' ),
					array( 'key' => 'beam_color', 'label_key' => 'manager.design.colors.slot_beam_color' ),
				),
				'controls'         => array(
					array( 'key' => 'source_position', 'type' => 'position', 'group' => 'shape', 'label_key' => 'manager.design.colors.field_source_position' ),
					array( 'key' => 'angle', 'type' => 'angle', 'group' => 'shape', 'label_key' => 'manager.design.colors.field_angle', 'min' => 0, 'max' => 180, 'step' => 5 ),
					array( 'key' => 'beam_length', 'type' => 'slider', 'group' => 'shape', 'label_key' => 'manager.design.colors.field_beam_length', 'min' => 20, 'max' => 100, 'step' => 1, 'unit' => '%' ),
					array( 'key' => 'beam_width', 'type' => 'slider', 'group' => 'shape', 'label_key' => 'manager.design.colors.field_beam_width', 'min' => 8, 'max' => 80, 'step' => 1, 'unit' => '%' ),
					array( 'key' => 'intensity', 'type' => 'slider', 'group' => 'effects', 'label_key' => 'manager.design.colors.field_intensity', 'min' => 0, 'max' => 100, 'step' => 1, 'unit' => '%' ),
					array( 'key' => 'opacity', 'type' => 'slider', 'group' => 'effects', 'label_key' => 'manager.design.colors.field_opacity', 'min' => 0, 'max' => 100, 'step' => 1, 'unit' => '%' ),
					array( 'key' => 'softness', 'type' => 'stepper', 'group' => 'effects', 'label_key' => 'manager.design.colors.field_softness', 'min' => 1, 'max' => 5, 'step' => 1 ),
					array( 'key' => 'beam_count', 'type' => 'stepper', 'group' => 'basic', 'label_key' => 'manager.design.colors.field_beam_count', 'min' => 1, 'max' => 5, 'step' => 1 ),
				),
			),
			'waves' => array(
				'group'            => 'graphic',
				'editor_kind'      => 'graphic',
				'label_key'        => 'manager.design.colors.pattern_type_waves',
				'description_key'  => 'manager.design.colors.pattern_desc_waves',
				'preview_style'    => 'waves',
				'default_colors'   => array(
					'background' => '#FFFFFF',
					'wave_1'     => '#7C3AED',
					'wave_2'     => '#38BDF8',
				),
				'default_settings' => array(
					'amplitude'        => 32,
					'frequency'        => 58,
					'thickness'        => 10,
					'angle'            => 0,
					'wave_count'       => 3,
					'opacity'          => 82,
					'softness'         => 3,
				),
				'color_slots'      => array(
					array( 'key' => 'background', 'label_key' => 'manager.design.colors.slot_background' ),
					array( 'key' => 'wave_1', 'label_key' => 'manager.design.colors.slot_wave_one' ),
					array( 'key' => 'wave_2', 'label_key' => 'manager.design.colors.slot_wave_two' ),
				),
				'controls'         => array(
					array( 'key' => 'amplitude', 'type' => 'slider', 'group' => 'shape', 'label_key' => 'manager.design.colors.field_amplitude', 'min' => 4, 'max' => 60, 'step' => 1, 'unit' => 'px' ),
					array( 'key' => 'frequency', 'type' => 'slider', 'group' => 'shape', 'label_key' => 'manager.design.colors.field_frequency', 'min' => 10, 'max' => 100, 'step' => 1, 'unit' => '%' ),
					array( 'key' => 'thickness', 'type' => 'slider', 'group' => 'shape', 'label_key' => 'manager.design.colors.field_thickness', 'min' => 2, 'max' => 24, 'step' => 1, 'unit' => 'px' ),
					array( 'key' => 'angle', 'type' => 'angle', 'group' => 'shape', 'label_key' => 'manager.design.colors.field_angle', 'min' => 0, 'max' => 180, 'step' => 5 ),
					array( 'key' => 'wave_count', 'type' => 'stepper', 'group' => 'basic', 'label_key' => 'manager.design.colors.field_wave_count', 'min' => 1, 'max' => 5, 'step' => 1 ),
					array( 'key' => 'opacity', 'type' => 'slider', 'group' => 'effects', 'label_key' => 'manager.design.colors.field_opacity', 'min' => 0, 'max' => 100, 'step' => 1, 'unit' => '%' ),
					array( 'key' => 'softness', 'type' => 'stepper', 'group' => 'effects', 'label_key' => 'manager.design.colors.field_softness', 'min' => 1, 'max' => 5, 'step' => 1 ),
				),
			),
			'paint_strokes' => array(
				'group'            => 'graphic',
				'editor_kind'      => 'graphic',
				'label_key'        => 'manager.design.colors.pattern_type_paint_strokes',
				'description_key'  => 'manager.design.colors.pattern_desc_paint_strokes',
				'preview_style'    => 'paint_strokes',
				'default_colors'   => array(
					'background' => '#FFFFFF',
					'stroke_1'   => '#7C3AED',
					'stroke_2'   => '#EC4899',
					'stroke_3'   => '#F97316',
					'stroke_4'   => '#38BDF8',
					'stroke_5'   => '#0F172A',
				),
				'default_settings' => array(
					'stroke_count'     => 3,
					'angle'            => 45,
					'thickness'        => 18,
					'length'           => 64,
					'roughness'        => 3,
					'opacity'          => 82,
					'brush_mode'       => 'dense',
					'drips'            => false,
				),
				'color_slots'      => array(
					array( 'key' => 'background', 'label_key' => 'manager.design.colors.slot_background' ),
					array( 'key' => 'stroke_1', 'label_key' => 'manager.design.colors.slot_stroke_one' ),
					array( 'key' => 'stroke_2', 'label_key' => 'manager.design.colors.slot_stroke_two', 'visible_when' => array( 'setting' => 'stroke_count', 'min' => 2 ) ),
					array( 'key' => 'stroke_3', 'label_key' => 'manager.design.colors.slot_stroke_three', 'visible_when' => array( 'setting' => 'stroke_count', 'min' => 3 ) ),
					array( 'key' => 'stroke_4', 'label_key' => 'manager.design.colors.slot_stroke_four', 'visible_when' => array( 'setting' => 'stroke_count', 'min' => 4 ) ),
					array( 'key' => 'stroke_5', 'label_key' => 'manager.design.colors.slot_stroke_five', 'visible_when' => array( 'setting' => 'stroke_count', 'min' => 5 ) ),
				),
				'controls'         => array(
					array( 'key' => 'stroke_count', 'type' => 'stepper', 'group' => 'basic', 'label_key' => 'manager.design.colors.field_stroke_count', 'min' => 1, 'max' => 5, 'step' => 1 ),
					array( 'key' => 'angle', 'type' => 'angle', 'group' => 'shape', 'label_key' => 'manager.design.colors.field_angle', 'min' => 0, 'max' => 180, 'step' => 5 ),
					array( 'key' => 'thickness', 'type' => 'slider', 'group' => 'shape', 'label_key' => 'manager.design.colors.field_thickness', 'min' => 4, 'max' => 36, 'step' => 1, 'unit' => 'px' ),
					array( 'key' => 'length', 'type' => 'slider', 'group' => 'shape', 'label_key' => 'manager.design.colors.field_length', 'min' => 20, 'max' => 100, 'step' => 1, 'unit' => '%' ),
					array( 'key' => 'roughness', 'type' => 'stepper', 'group' => 'effects', 'label_key' => 'manager.design.colors.field_roughness', 'min' => 1, 'max' => 5, 'step' => 1 ),
					array( 'key' => 'opacity', 'type' => 'slider', 'group' => 'effects', 'label_key' => 'manager.design.colors.field_opacity', 'min' => 0, 'max' => 100, 'step' => 1, 'unit' => '%' ),
					array( 'key' => 'brush_mode', 'type' => 'segmented', 'group' => 'effects', 'label_key' => 'manager.design.colors.field_brush_mode', 'options' => array( 'dry', 'dense' ) ),
					array( 'key' => 'drips', 'type' => 'switch', 'group' => 'effects', 'label_key' => 'manager.design.colors.field_drips' ),
				),
			),
			'ink_blots' => array(
				'group'            => 'graphic',
				'editor_kind'      => 'graphic',
				'label_key'        => 'manager.design.colors.pattern_type_ink_blots',
				'description_key'  => 'manager.design.colors.pattern_desc_ink_blots',
				'preview_style'    => 'ink_blots',
				'default_colors'   => array(
					'background' => '#F8FAFC',
					'blot_1'     => '#312E81',
					'blot_2'     => '#7C3AED',
					'blot_3'     => '#EC4899',
					'blot_4'     => '#F97316',
					'blot_5'     => '#38BDF8',
					'blot_6'     => '#0F172A',
					'blot_7'     => '#A855F7',
				),
				'default_settings' => array(
					'blot_count'       => 3,
					'blot_size'        => 34,
					'scatter'          => 46,
					'edge_irregularity' => 3,
					'opacity'          => 82,
					'edge_sharpness'   => 3,
				),
				'color_slots'      => array(
					array( 'key' => 'background', 'label_key' => 'manager.design.colors.slot_background' ),
					array( 'key' => 'blot_1', 'label_key' => 'manager.design.colors.slot_blot_one' ),
					array( 'key' => 'blot_2', 'label_key' => 'manager.design.colors.slot_blot_two', 'visible_when' => array( 'setting' => 'blot_count', 'min' => 2 ) ),
					array( 'key' => 'blot_3', 'label_key' => 'manager.design.colors.slot_blot_three', 'visible_when' => array( 'setting' => 'blot_count', 'min' => 3 ) ),
					array( 'key' => 'blot_4', 'label_key' => 'manager.design.colors.slot_blot_four', 'visible_when' => array( 'setting' => 'blot_count', 'min' => 4 ) ),
					array( 'key' => 'blot_5', 'label_key' => 'manager.design.colors.slot_blot_five', 'visible_when' => array( 'setting' => 'blot_count', 'min' => 5 ) ),
					array( 'key' => 'blot_6', 'label_key' => 'manager.design.colors.slot_blot_six', 'visible_when' => array( 'setting' => 'blot_count', 'min' => 6 ) ),
					array( 'key' => 'blot_7', 'label_key' => 'manager.design.colors.slot_blot_seven', 'visible_when' => array( 'setting' => 'blot_count', 'min' => 7 ) ),
				),
				'controls'         => array(
					array( 'key' => 'blot_count', 'type' => 'stepper', 'group' => 'basic', 'label_key' => 'manager.design.colors.field_blot_count', 'min' => 1, 'max' => 7, 'step' => 1 ),
					array( 'key' => 'blot_size', 'type' => 'slider', 'group' => 'shape', 'label_key' => 'manager.design.colors.field_blot_size', 'min' => 12, 'max' => 72, 'step' => 1, 'unit' => 'px' ),
					array( 'key' => 'scatter', 'type' => 'slider', 'group' => 'shape', 'label_key' => 'manager.design.colors.field_scatter', 'min' => 0, 'max' => 100, 'step' => 1, 'unit' => '%' ),
					array( 'key' => 'edge_irregularity', 'type' => 'stepper', 'group' => 'effects', 'label_key' => 'manager.design.colors.field_edge_irregularity', 'min' => 1, 'max' => 5, 'step' => 1 ),
					array( 'key' => 'opacity', 'type' => 'slider', 'group' => 'effects', 'label_key' => 'manager.design.colors.field_opacity', 'min' => 0, 'max' => 100, 'step' => 1, 'unit' => '%' ),
					array( 'key' => 'edge_sharpness', 'type' => 'stepper', 'group' => 'effects', 'label_key' => 'manager.design.colors.field_edge_sharpness', 'min' => 1, 'max' => 5, 'step' => 1 ),
				),
			),
			'marble' => array(
				'group'            => 'graphic',
				'editor_kind'      => 'graphic',
				'label_key'        => 'manager.design.colors.pattern_type_marble',
				'description_key'  => 'manager.design.colors.pattern_desc_marble',
				'preview_style'    => 'marble',
				'default_colors'   => array(
					'base_color'   => '#FFFFFF',
					'line_color'   => '#C4B5FD',
					'accent_color' => '#7C3AED',
				),
				'default_settings' => array(
					'line_count'       => 36,
					'line_thickness'   => 3,
					'curve'            => 52,
					'contrast'         => 56,
					'softness'         => 3,
					'opacity'          => 78,
				),
				'color_slots'      => array(
					array( 'key' => 'base_color', 'label_key' => 'manager.design.colors.slot_base_color' ),
					array( 'key' => 'line_color', 'label_key' => 'manager.design.colors.slot_line_color' ),
					array( 'key' => 'accent_color', 'label_key' => 'manager.design.colors.slot_accent_color' ),
				),
				'controls'         => array(
					array( 'key' => 'line_count', 'type' => 'slider', 'group' => 'basic', 'label_key' => 'manager.design.colors.field_line_count', 'min' => 8, 'max' => 72, 'step' => 1, 'unit' => '' ),
					array( 'key' => 'line_thickness', 'type' => 'slider', 'group' => 'shape', 'label_key' => 'manager.design.colors.field_line_thickness', 'min' => 1, 'max' => 8, 'step' => 1, 'unit' => 'px' ),
					array( 'key' => 'curve', 'type' => 'slider', 'group' => 'shape', 'label_key' => 'manager.design.colors.field_curve', 'min' => 0, 'max' => 100, 'step' => 1, 'unit' => '%' ),
					array( 'key' => 'contrast', 'type' => 'slider', 'group' => 'effects', 'label_key' => 'manager.design.colors.field_contrast', 'min' => 0, 'max' => 100, 'step' => 1, 'unit' => '%' ),
					array( 'key' => 'softness', 'type' => 'stepper', 'group' => 'effects', 'label_key' => 'manager.design.colors.field_softness', 'min' => 1, 'max' => 5, 'step' => 1 ),
					array( 'key' => 'opacity', 'type' => 'slider', 'group' => 'effects', 'label_key' => 'manager.design.colors.field_opacity', 'min' => 0, 'max' => 100, 'step' => 1, 'unit' => '%' ),
				),
			),
			'noise' => array(
				'group'            => 'graphic',
				'editor_kind'      => 'graphic',
				'label_key'        => 'manager.design.colors.pattern_type_noise',
				'description_key'  => 'manager.design.colors.pattern_desc_noise',
				'preview_style'    => 'noise',
				'default_colors'   => array(
					'background'  => '#FFFFFF',
					'noise_color' => '#475569',
				),
				'default_settings' => array(
					'intensity'        => 42,
					'particle_size'    => 6,
					'opacity'          => 38,
					'monochrome'       => true,
					'edge_style'       => 'soft',
				),
				'color_slots'      => array(
					array( 'key' => 'background', 'label_key' => 'manager.design.colors.slot_background' ),
					array( 'key' => 'noise_color', 'label_key' => 'manager.design.colors.slot_noise_color' ),
				),
				'controls'         => array(
					array( 'key' => 'intensity', 'type' => 'slider', 'group' => 'basic', 'label_key' => 'manager.design.colors.field_intensity', 'min' => 0, 'max' => 100, 'step' => 1, 'unit' => '%' ),
					array( 'key' => 'particle_size', 'type' => 'slider', 'group' => 'shape', 'label_key' => 'manager.design.colors.field_particle_size', 'min' => 1, 'max' => 18, 'step' => 1, 'unit' => 'px' ),
					array( 'key' => 'opacity', 'type' => 'slider', 'group' => 'effects', 'label_key' => 'manager.design.colors.field_opacity', 'min' => 0, 'max' => 100, 'step' => 1, 'unit' => '%' ),
					array( 'key' => 'monochrome', 'type' => 'switch', 'group' => 'effects', 'label_key' => 'manager.design.colors.field_monochrome' ),
					array( 'key' => 'edge_style', 'type' => 'segmented', 'group' => 'effects', 'label_key' => 'manager.design.colors.field_edge_style', 'options' => array( 'soft', 'crisp' ) ),
				),
			),
			'decorative_elements' => array(
				'group'            => 'graphic',
				'editor_kind'      => 'graphic',
				'label_key'        => 'manager.design.colors.pattern_type_decorative_elements',
				'description_key'  => 'manager.design.colors.pattern_desc_decorative_elements',
				'preview_style'    => 'decorative_elements',
				'default_colors'   => array(
					'background'    => '#FFFFFF',
					'element_color' => '#7C3AED',
					'layer_2_color' => '#EC4899',
				),
				'default_settings' => array(
					'element_type'     => 'sparkle',
					'element_size'     => 22,
					'spacing'          => 30,
					'opacity'          => 82,
					'rotation'         => 0,
					'random_rotation'  => true,
					'density'          => 66,
					'row_offset'       => 14,
					'second_layer'     => false,
				),
				'color_slots'      => array(
					array( 'key' => 'background', 'label_key' => 'manager.design.colors.slot_background' ),
					array( 'key' => 'element_color', 'label_key' => 'manager.design.colors.slot_element_color' ),
					array( 'key' => 'layer_2_color', 'label_key' => 'manager.design.colors.slot_second_layer_color', 'visible_when' => array( 'setting' => 'second_layer', 'equals' => true ) ),
				),
				'controls'         => array(
					array( 'key' => 'element_type', 'type' => 'segmented', 'group' => 'shape', 'label_key' => 'manager.design.colors.field_element_type', 'options' => array( 'star', 'sparkle', 'drop', 'leaf', 'wave', 'arc', 'heart', 'abstract', 'geo' ) ),
					array( 'key' => 'element_size', 'type' => 'slider', 'group' => 'shape', 'label_key' => 'manager.design.colors.field_element_size', 'min' => 8, 'max' => 42, 'step' => 1, 'unit' => 'px' ),
					array( 'key' => 'spacing', 'type' => 'slider', 'group' => 'shape', 'label_key' => 'manager.design.colors.field_spacing', 'min' => 8, 'max' => 64, 'step' => 1, 'unit' => 'px' ),
					array( 'key' => 'opacity', 'type' => 'slider', 'group' => 'effects', 'label_key' => 'manager.design.colors.field_opacity', 'min' => 0, 'max' => 100, 'step' => 1, 'unit' => '%' ),
					array( 'key' => 'rotation', 'type' => 'angle', 'group' => 'shape', 'label_key' => 'manager.design.colors.field_rotation', 'min' => 0, 'max' => 180, 'step' => 5 ),
					array( 'key' => 'random_rotation', 'type' => 'switch', 'group' => 'effects', 'label_key' => 'manager.design.colors.field_random_rotation' ),
					array( 'key' => 'density', 'type' => 'slider', 'group' => 'basic', 'label_key' => 'manager.design.colors.field_density', 'min' => 10, 'max' => 100, 'step' => 1, 'unit' => '%' ),
					array( 'key' => 'row_offset', 'type' => 'slider', 'group' => 'shape', 'label_key' => 'manager.design.colors.field_row_offset', 'min' => 0, 'max' => 32, 'step' => 1, 'unit' => 'px' ),
					array( 'key' => 'second_layer', 'type' => 'switch', 'group' => 'effects', 'label_key' => 'manager.design.colors.field_second_layer' ),
				),
			),
			'image_pattern' => array(
				'group'            => 'image',
				'editor_kind'      => 'image',
				'label_key'        => 'manager.design.colors.pattern_type_image_pattern',
				'description_key'  => 'manager.design.colors.pattern_desc_image_pattern',
				'preview_style'    => 'image_pattern',
				'default_colors'   => array(
					'overlay_color' => '#0F172A',
					'background'    => '#FFFFFF',
				),
				'default_settings' => array(
					'image_mode'        => 'cover',
					'opacity'           => 100,
					'scale'             => 100,
					'position'          => 'center',
					'rotation'          => 0,
					'brightness'        => 0,
					'contrast'          => 0,
					'saturation'        => 0,
					'monochrome'        => 0,
					'warmth'            => 0,
					'red_channel'       => 0,
					'green_channel'     => 0,
					'blue_channel'      => 0,
					'dimness'           => 0,
					'highlight'         => 0,
					'blur'              => 0,
					'noise'             => 0,
					'halftone'          => 0,
					'comic'             => 0,
					'photo_paper'       => 0,
					'canvas'            => 0,
					'faded'             => 0,
					'vignette'          => 0,
					'gloss'             => 0,
					'pixelate'          => 0,
				),
				'color_slots'      => array(
					array( 'key' => 'overlay_color', 'label_key' => 'manager.design.colors.slot_overlay_color' ),
					array( 'key' => 'background', 'label_key' => 'manager.design.colors.slot_background' ),
				),
				'controls'         => array(
					array( 'key' => 'image_mode', 'type' => 'segmented', 'group' => 'placement', 'label_key' => 'manager.design.colors.pattern_image_mode_label', 'options' => array( 'cover', 'contain', 'repeat', 'center', 'stretch' ) ),
					array( 'key' => 'scale', 'type' => 'slider', 'group' => 'placement', 'label_key' => 'manager.design.colors.field_scale', 'min' => 40, 'max' => 200, 'step' => 1, 'unit' => '%' ),
					array( 'key' => 'position', 'type' => 'position', 'group' => 'placement', 'label_key' => 'manager.design.colors.field_position' ),
					array( 'key' => 'rotation', 'type' => 'angle', 'group' => 'placement', 'label_key' => 'manager.design.colors.field_rotation', 'min' => 0, 'max' => 180, 'step' => 5 ),
					array( 'key' => 'opacity', 'type' => 'slider', 'group' => 'light', 'label_key' => 'manager.design.colors.field_opacity', 'min' => 0, 'max' => 100, 'step' => 1, 'unit' => '%' ),
					array( 'key' => 'brightness', 'type' => 'slider', 'group' => 'light', 'label_key' => 'manager.design.colors.field_brightness', 'min' => -100, 'max' => 100, 'step' => 1, 'unit' => '%' ),
					array( 'key' => 'contrast', 'type' => 'slider', 'group' => 'light', 'label_key' => 'manager.design.colors.field_contrast', 'min' => -100, 'max' => 100, 'step' => 1, 'unit' => '%' ),
					array( 'key' => 'saturation', 'type' => 'slider', 'group' => 'light', 'label_key' => 'manager.design.colors.field_saturation', 'min' => -100, 'max' => 100, 'step' => 1, 'unit' => '%' ),
					array( 'key' => 'monochrome', 'type' => 'slider', 'group' => 'light', 'label_key' => 'manager.design.colors.field_monochrome_transition', 'min' => 0, 'max' => 100, 'step' => 1, 'unit' => '%' ),
					array( 'key' => 'warmth', 'type' => 'segmented', 'group' => 'light', 'label_key' => 'manager.design.colors.field_warmth', 'options' => array( 'warm', 'neutral', 'cold' ) ),
					array( 'key' => 'red_channel', 'type' => 'slider', 'group' => 'light', 'label_key' => 'manager.design.colors.field_red_channel', 'min' => -100, 'max' => 100, 'step' => 1, 'unit' => '%' ),
					array( 'key' => 'green_channel', 'type' => 'slider', 'group' => 'light', 'label_key' => 'manager.design.colors.field_green_channel', 'min' => -100, 'max' => 100, 'step' => 1, 'unit' => '%' ),
					array( 'key' => 'blue_channel', 'type' => 'slider', 'group' => 'light', 'label_key' => 'manager.design.colors.field_blue_channel', 'min' => -100, 'max' => 100, 'step' => 1, 'unit' => '%' ),
					array( 'key' => 'dimness', 'type' => 'slider', 'group' => 'light', 'label_key' => 'manager.design.colors.field_dimness', 'min' => 0, 'max' => 100, 'step' => 1, 'unit' => '%' ),
					array( 'key' => 'highlight', 'type' => 'slider', 'group' => 'light', 'label_key' => 'manager.design.colors.field_highlight', 'min' => 0, 'max' => 100, 'step' => 1, 'unit' => '%' ),
					array( 'key' => 'blur', 'type' => 'slider', 'group' => 'texture', 'label_key' => 'manager.design.colors.field_blur', 'min' => 0, 'max' => 24, 'step' => 1, 'unit' => 'px' ),
					array( 'key' => 'noise', 'type' => 'slider', 'group' => 'texture', 'label_key' => 'manager.design.colors.field_noise', 'min' => 0, 'max' => 100, 'step' => 1, 'unit' => '%' ),
					array( 'key' => 'halftone', 'type' => 'stepper', 'group' => 'effects', 'label_key' => 'manager.design.colors.field_halftone', 'min' => 0, 'max' => 5, 'step' => 1 ),
					array( 'key' => 'comic', 'type' => 'stepper', 'group' => 'effects', 'label_key' => 'manager.design.colors.field_comic', 'min' => 0, 'max' => 5, 'step' => 1 ),
					array( 'key' => 'photo_paper', 'type' => 'stepper', 'group' => 'effects', 'label_key' => 'manager.design.colors.field_photo_paper', 'min' => 0, 'max' => 5, 'step' => 1 ),
					array( 'key' => 'canvas', 'type' => 'stepper', 'group' => 'effects', 'label_key' => 'manager.design.colors.field_canvas_effect', 'min' => 0, 'max' => 5, 'step' => 1 ),
					array( 'key' => 'faded', 'type' => 'stepper', 'group' => 'effects', 'label_key' => 'manager.design.colors.field_faded_photo', 'min' => 0, 'max' => 5, 'step' => 1 ),
					array( 'key' => 'vignette', 'type' => 'stepper', 'group' => 'effects', 'label_key' => 'manager.design.colors.field_vignette', 'min' => 0, 'max' => 5, 'step' => 1 ),
					array( 'key' => 'gloss', 'type' => 'stepper', 'group' => 'effects', 'label_key' => 'manager.design.colors.field_gloss', 'min' => 0, 'max' => 5, 'step' => 1 ),
					array( 'key' => 'pixelate', 'type' => 'stepper', 'group' => 'effects', 'label_key' => 'manager.design.colors.field_pixelate', 'min' => 0, 'max' => 5, 'step' => 1 ),
				),
				'requires_media'   => true,
			),
		);
	}

	public static function get_pattern_registry_payload(): array {
		$payload = array();

		foreach ( self::get_pattern_registry() as $key => $definition ) {
			$payload[] = array(
				'key'              => $key,
				'group'            => isset( $definition['group'] ) ? (string) $definition['group'] : 'decorative',
				'group_label'      => self::text( 'manager.design.colors.pattern_group_' . ( isset( $definition['group'] ) ? (string) $definition['group'] : 'decorative' ) ),
				'editor_kind'      => isset( $definition['editor_kind'] ) ? (string) $definition['editor_kind'] : 'graphic',
				'label'            => self::text( isset( $definition['label_key'] ) ? (string) $definition['label_key'] : '' ),
				'description'      => self::text( isset( $definition['description_key'] ) ? (string) $definition['description_key'] : '' ),
				'preview_style'    => isset( $definition['preview_style'] ) ? (string) $definition['preview_style'] : $key,
				'default_colors'   => isset( $definition['default_colors'] ) && is_array( $definition['default_colors'] ) ? $definition['default_colors'] : array(),
				'default_settings' => isset( $definition['default_settings'] ) && is_array( $definition['default_settings'] ) ? $definition['default_settings'] : array(),
				'color_slots'      => self::get_pattern_color_slots_payload( isset( $definition['color_slots'] ) && is_array( $definition['color_slots'] ) ? $definition['color_slots'] : array() ),
				'controls'         => self::get_pattern_controls_payload( isset( $definition['controls'] ) && is_array( $definition['controls'] ) ? $definition['controls'] : array() ),
				'requires_media'   => ! empty( $definition['requires_media'] ),
			);
		}

		return $payload;
	}

	private static function get_pattern_color_slots_payload( array $slots ): array {
		$payload = array();

		foreach ( $slots as $slot ) {
			if ( empty( $slot['key'] ) ) {
				continue;
			}

			$payload[] = array(
				'key'          => sanitize_key( (string) $slot['key'] ),
				'label'        => self::text( isset( $slot['label_key'] ) ? (string) $slot['label_key'] : '' ),
				'visible_when' => isset( $slot['visible_when'] ) && is_array( $slot['visible_when'] ) ? $slot['visible_when'] : null,
			);
		}

		return $payload;
	}

	private static function get_pattern_controls_payload( array $controls ): array {
		$payload = array();

		foreach ( $controls as $control ) {
			if ( empty( $control['key'] ) || empty( $control['type'] ) ) {
				continue;
			}

			$item = array(
				'key'       => sanitize_key( (string) $control['key'] ),
				'type'      => sanitize_key( (string) $control['type'] ),
				'group'     => sanitize_key( isset( $control['group'] ) ? (string) $control['group'] : 'basic' ),
				'label'     => self::text( isset( $control['label_key'] ) ? (string) $control['label_key'] : '' ),
				'min'       => isset( $control['min'] ) ? $control['min'] : null,
				'max'       => isset( $control['max'] ) ? $control['max'] : null,
				'step'      => isset( $control['step'] ) ? $control['step'] : null,
				'unit'      => isset( $control['unit'] ) ? (string) $control['unit'] : '',
				'visible_when' => isset( $control['visible_when'] ) && is_array( $control['visible_when'] ) ? $control['visible_when'] : null,
			);

			if ( isset( $control['options'] ) && is_array( $control['options'] ) ) {
				$item['options'] = array_values( array_map( 'strval', $control['options'] ) );
			}

			$payload[] = $item;
		}

		return $payload;
	}

	private static function normalize_pattern_compatibility_payload( string $pattern_type, array $colors, array $settings ): array {
		$pattern_type       = self::normalize_pattern_type( $pattern_type );
		$normalized_colors  = $colors;
		$normalized_settings = $settings;

		if ( isset( $colors['base'] ) && empty( $colors['background'] ) ) {
			$normalized_colors['background'] = $colors['base'];
		}

		if ( isset( $colors['accent_a'] ) && empty( $colors['layer_1'] ) ) {
			$normalized_colors['layer_1'] = $colors['accent_a'];
		}

		if ( isset( $colors['accent_b'] ) && empty( $colors['layer_2'] ) ) {
			$normalized_colors['layer_2'] = $colors['accent_b'];
		}

		if ( isset( $colors['accent_c'] ) && empty( $colors['layer_3'] ) ) {
			$normalized_colors['layer_3'] = $colors['accent_c'];
		}

		switch ( $pattern_type ) {
			case 'grid':
				$normalized_colors['line_color'] = isset( $normalized_colors['line_color'] ) ? $normalized_colors['line_color'] : ( isset( $colors['primary'] ) ? $colors['primary'] : ( isset( $normalized_colors['layer_1'] ) ? $normalized_colors['layer_1'] : '' ) );
				break;
			case 'lines':
				$normalized_colors['line_color']   = isset( $normalized_colors['line_color'] ) ? $normalized_colors['line_color'] : ( isset( $colors['primary'] ) ? $colors['primary'] : '' );
				$normalized_colors['second_color'] = isset( $normalized_colors['second_color'] ) ? $normalized_colors['second_color'] : ( isset( $colors['accent'] ) ? $colors['accent'] : '' );
				break;
			case 'circles_orbits':
				$normalized_colors['ring_color']  = isset( $normalized_colors['ring_color'] ) ? $normalized_colors['ring_color'] : ( isset( $colors['primary'] ) ? $colors['primary'] : '' );
				$normalized_colors['orbit_color'] = isset( $normalized_colors['orbit_color'] ) ? $normalized_colors['orbit_color'] : ( isset( $colors['accent'] ) ? $colors['accent'] : '' );
				break;
			case 'soft_blobs':
				$normalized_colors['blob_1'] = isset( $normalized_colors['blob_1'] ) ? $normalized_colors['blob_1'] : ( isset( $colors['primary'] ) ? $colors['primary'] : ( isset( $normalized_colors['layer_1'] ) ? $normalized_colors['layer_1'] : '' ) );
				$normalized_colors['blob_2'] = isset( $normalized_colors['blob_2'] ) ? $normalized_colors['blob_2'] : ( isset( $colors['accent'] ) ? $colors['accent'] : ( isset( $normalized_colors['layer_2'] ) ? $normalized_colors['layer_2'] : '' ) );
				$normalized_colors['blob_3'] = isset( $normalized_colors['blob_3'] ) ? $normalized_colors['blob_3'] : ( isset( $colors['accent_c'] ) ? $colors['accent_c'] : '' );
				break;
			case 'light_beam':
				$normalized_colors['beam_color'] = isset( $normalized_colors['beam_color'] ) ? $normalized_colors['beam_color'] : ( isset( $colors['accent'] ) ? $colors['accent'] : ( isset( $colors['primary'] ) ? $colors['primary'] : '' ) );
				break;
			case 'waves':
				$normalized_colors['wave_1'] = isset( $normalized_colors['wave_1'] ) ? $normalized_colors['wave_1'] : ( isset( $colors['primary'] ) ? $colors['primary'] : '' );
				$normalized_colors['wave_2'] = isset( $normalized_colors['wave_2'] ) ? $normalized_colors['wave_2'] : ( isset( $colors['accent'] ) ? $colors['accent'] : '' );
				break;
			case 'paint_strokes':
				$normalized_colors['stroke_1'] = isset( $normalized_colors['stroke_1'] ) ? $normalized_colors['stroke_1'] : ( isset( $colors['primary'] ) ? $colors['primary'] : '' );
				$normalized_colors['stroke_2'] = isset( $normalized_colors['stroke_2'] ) ? $normalized_colors['stroke_2'] : ( isset( $colors['accent'] ) ? $colors['accent'] : '' );
				break;
			case 'ink_blots':
				$normalized_colors['blot_1'] = isset( $normalized_colors['blot_1'] ) ? $normalized_colors['blot_1'] : ( isset( $colors['primary'] ) ? $colors['primary'] : '' );
				$normalized_colors['blot_2'] = isset( $normalized_colors['blot_2'] ) ? $normalized_colors['blot_2'] : ( isset( $colors['accent'] ) ? $colors['accent'] : '' );
				break;
			case 'marble':
				$normalized_colors['base_color']   = isset( $normalized_colors['base_color'] ) ? $normalized_colors['base_color'] : ( isset( $normalized_colors['background'] ) ? $normalized_colors['background'] : '' );
				$normalized_colors['line_color']   = isset( $normalized_colors['line_color'] ) ? $normalized_colors['line_color'] : ( isset( $colors['primary'] ) ? $colors['primary'] : '' );
				$normalized_colors['accent_color'] = isset( $normalized_colors['accent_color'] ) ? $normalized_colors['accent_color'] : ( isset( $colors['accent'] ) ? $colors['accent'] : '' );
				break;
			case 'noise':
				$normalized_colors['noise_color'] = isset( $normalized_colors['noise_color'] ) ? $normalized_colors['noise_color'] : ( isset( $colors['primary'] ) ? $colors['primary'] : '' );
				break;
			case 'decorative_elements':
				$normalized_colors['element_color'] = isset( $normalized_colors['element_color'] ) ? $normalized_colors['element_color'] : ( isset( $colors['primary'] ) ? $colors['primary'] : '' );
				$normalized_colors['layer_2_color'] = isset( $normalized_colors['layer_2_color'] ) ? $normalized_colors['layer_2_color'] : ( isset( $colors['accent'] ) ? $colors['accent'] : '' );
				break;
			case 'image_pattern':
				$normalized_colors['overlay_color'] = isset( $normalized_colors['overlay_color'] ) ? $normalized_colors['overlay_color'] : ( isset( $colors['overlay'] ) ? $colors['overlay'] : '' );
				break;
		}

		return array(
			'colors'   => $normalized_colors,
			'settings' => $normalized_settings,
		);
	}

	private static function sanitize_pattern_colors( string $pattern_type, array $colors ): array {
		$allowed = self::get_pattern_color_keys( $pattern_type );
		$normalized = array();

		foreach ( $colors as $key => $value ) {
			$key = sanitize_key( (string) $key );
			$hex = self::normalize_hex( $value );

			if ( '' === $key || '' === $hex || ! in_array( $key, $allowed, true ) ) {
				continue;
			}

			$normalized[ $key ] = $hex;
		}

		if ( 'image_pattern' === $pattern_type && empty( $normalized['overlay_color'] ) ) {
			$normalized['overlay_color'] = '#0F172A';
		}

		return $normalized;
	}

	private static function sanitize_pattern_settings( string $pattern_type, array $settings ): array {
		$definition = self::get_pattern_registry();
		$controls   = isset( $definition[ $pattern_type ]['controls'] ) && is_array( $definition[ $pattern_type ]['controls'] ) ? $definition[ $pattern_type ]['controls'] : array();
		$defaults   = isset( $definition[ $pattern_type ]['default_settings'] ) && is_array( $definition[ $pattern_type ]['default_settings'] ) ? $definition[ $pattern_type ]['default_settings'] : array();
		$normalized = $defaults;

		foreach ( $controls as $control ) {
			$key = isset( $control['key'] ) ? sanitize_key( (string) $control['key'] ) : '';

			if ( '' === $key ) {
				continue;
			}

			$normalized[ $key ] = self::sanitize_pattern_control_value(
				$control,
				array_key_exists( $key, $settings ) ? $settings[ $key ] : ( array_key_exists( $key, $defaults ) ? $defaults[ $key ] : null ),
				array_key_exists( $key, $defaults ) ? $defaults[ $key ] : null
			);
		}

		return $normalized;
	}

	private static function get_pattern_color_keys( string $pattern_type ): array {
		$definition = self::get_pattern_registry();
		$slots      = isset( $definition[ $pattern_type ]['color_slots'] ) && is_array( $definition[ $pattern_type ]['color_slots'] ) ? $definition[ $pattern_type ]['color_slots'] : array();
		$keys       = array();

		foreach ( $slots as $slot ) {
			if ( empty( $slot['key'] ) ) {
				continue;
			}

			$keys[] = sanitize_key( (string) $slot['key'] );
		}

		return array_values( array_unique( $keys ) );
	}

	private static function sanitize_pattern_control_value( array $control, $value, $fallback ) {
		$type = isset( $control['type'] ) ? sanitize_key( (string) $control['type'] ) : 'slider';
		$min  = isset( $control['min'] ) && is_numeric( $control['min'] ) ? (float) $control['min'] : null;
		$max  = isset( $control['max'] ) && is_numeric( $control['max'] ) ? (float) $control['max'] : null;
		$step = isset( $control['step'] ) && is_numeric( $control['step'] ) ? (float) $control['step'] : 1;

		switch ( $type ) {
			case 'switch':
				return rest_sanitize_boolean( $value );
			case 'segmented':
				return self::sanitize_enum_value( is_scalar( $value ) ? (string) $value : ( is_scalar( $fallback ) ? (string) $fallback : '' ), isset( $control['options'] ) && is_array( $control['options'] ) ? array_values( array_map( 'sanitize_key', $control['options'] ) ) : array(), is_scalar( $fallback ) ? sanitize_key( (string) $fallback ) : '' );
			case 'position':
				return self::sanitize_enum_value( is_scalar( $value ) ? (string) $value : ( is_scalar( $fallback ) ? (string) $fallback : 'center' ), array( 'top-left', 'top', 'top-right', 'left', 'center', 'right', 'bottom-left', 'bottom', 'bottom-right' ), is_scalar( $fallback ) ? sanitize_key( (string) $fallback ) : 'center' );
			case 'angle':
				return self::sanitize_numeric_range_value( $value, $min, $max, is_numeric( $fallback ) ? (float) $fallback : 0, $step );
			case 'stepper':
				return (int) self::sanitize_numeric_range_value( $value, $min, $max, is_numeric( $fallback ) ? (float) $fallback : 0, $step );
			case 'slider':
			default:
				return self::sanitize_numeric_range_value( $value, $min, $max, is_numeric( $fallback ) ? (float) $fallback : 0, $step );
		}
	}

	private static function sanitize_numeric_range_value( $value, ?float $min, ?float $max, float $fallback, float $step ) {
		if ( ! is_numeric( $value ) ) {
			$value = $fallback;
		}

		$number = (float) $value;

		if ( null !== $min ) {
			$number = max( $min, $number );
		}

		if ( null !== $max ) {
			$number = min( $max, $number );
		}

		if ( $step > 0 ) {
			$number = round( $number / $step ) * $step;
		}

		if ( abs( $number - round( $number ) ) < 0.00001 ) {
			return (int) round( $number );
		}

		return $number;
	}

	private static function normalize_pattern_media( array $media ): array {
		$attachment_id = isset( $media['attachment_id'] ) ? (int) $media['attachment_id'] : 0;

		if ( $attachment_id <= 0 || ! function_exists( 'wp_get_attachment_url' ) ) {
			return array();
		}

		$url  = wp_get_attachment_url( $attachment_id );
		$mime = function_exists( 'get_post_mime_type' ) ? (string) get_post_mime_type( $attachment_id ) : '';
		$post = function_exists( 'get_post' ) ? get_post( $attachment_id ) : null;

		if ( empty( $url ) || ! self::is_allowed_image_mime_type( $mime ) || ! $post || 'attachment' !== $post->post_type ) {
			return array();
		}

		$metadata  = function_exists( 'wp_get_attachment_metadata' ) ? wp_get_attachment_metadata( $attachment_id ) : array();
		$thumb_url = function_exists( 'wp_get_attachment_image_url' ) ? wp_get_attachment_image_url( $attachment_id, 'medium' ) : '';

		return array(
			'attachment_id' => $attachment_id,
			'url'           => esc_url_raw( $url ),
			'thumb_url'     => esc_url_raw( $thumb_url ? $thumb_url : $url ),
			'name'          => sanitize_text_field( get_the_title( $attachment_id ) ),
			'mime_type'     => sanitize_text_field( $mime ),
			'width'         => isset( $metadata['width'] ) ? (int) $metadata['width'] : 0,
			'height'        => isset( $metadata['height'] ) ? (int) $metadata['height'] : 0,
		);
	}

	public static function get_allowed_image_mime_types(): array {
		return array(
			'image/jpeg',
			'image/png',
			'image/webp',
		);
	}

	public static function is_allowed_image_mime_type( string $mime_type ): bool {
		return in_array( strtolower( trim( $mime_type ) ), self::get_allowed_image_mime_types(), true );
	}

	public static function get_max_image_upload_size_bytes(): int {
		$max = function_exists( 'wp_max_upload_size' ) ? (int) wp_max_upload_size() : 8 * 1024 * 1024;

		return max( 1024 * 1024, $max );
	}

	private static function sanitize_enum_value( string $value, array $allowed, string $fallback ): string {
		$value = sanitize_key( $value );

		return in_array( $value, $allowed, true ) ? $value : $fallback;
	}

	private static function sanitize_percentage_value( $value, int $fallback ): int {
		if ( ! is_numeric( $value ) ) {
			return $fallback;
		}

		return max( 0, min( 100, (int) $value ) );
	}

	private static function sanitize_angle_value( $value, int $fallback ): int {
		if ( ! is_numeric( $value ) ) {
			return $fallback;
		}

		$angle = (int) $value;

		return $angle >= 0 && $angle <= 360 ? $angle : $fallback;
	}

	private static function persist_data( array $data ): bool {
		if ( false === get_option( self::OPTION_NAME, false ) ) {
			return add_option( self::OPTION_NAME, $data, '', false );
		}

		return update_option( self::OPTION_NAME, $data, false );
	}

	private static function sanitize_slug( string $value ): string {
		$slug = sanitize_title( $value );

		return sanitize_key( $slug );
	}

	private static function sanitize_id( string $value ): string {
		$value = strtolower( trim( $value ) );
		$value = (string) preg_replace( '/[^a-z0-9_-]/', '-', $value );
		$value = trim( $value, '-' );

		return '' !== $value ? $value : sanitize_key( uniqid( 'sonyra-color-', true ) );
	}

	private static function sanitize_role( string $value ): string {
		$value = strtolower( trim( $value ) );

		return (string) preg_replace( '/[^a-z0-9._-]/', '', $value );
	}

	private static function sanitize_source( string $value ): string {
		$value = strtolower( trim( $value ) );

		return (string) preg_replace( '/[^a-z0-9._-]/', '', $value );
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
}
