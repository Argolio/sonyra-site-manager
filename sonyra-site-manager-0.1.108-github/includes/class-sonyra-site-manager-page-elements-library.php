<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Sonyra_Site_Manager_Page_Elements_Library {

	public static function get_core_section_definitions(): array {
		return array(
			self::build_section_definition( 'hero', 'manager.page_elements.sections.hero.label', 'manager.page_elements.sections.hero.description', 'sparkles', 'content', 'hero', 'manager.pages.defaults.hero.block_name' ),
			self::build_section_definition( 'text', 'manager.page_elements.sections.text.label', 'manager.page_elements.sections.text.description', 'file-text', 'content', 'text', 'manager.pages.defaults.text.block_name' ),
			self::build_section_definition( 'media_text', 'manager.page_elements.sections.media_text.label', 'manager.page_elements.sections.media_text.description', 'photo', 'content' ),
			self::build_section_definition( 'benefits', 'manager.page_elements.sections.benefits.label', 'manager.page_elements.sections.benefits.description', 'layers-selected', 'marketing' ),
			self::build_section_definition( 'services', 'manager.page_elements.sections.services.label', 'manager.page_elements.sections.services.description', 'components', 'marketing' ),
			self::build_section_definition( 'cards', 'manager.page_elements.sections.cards.label', 'manager.page_elements.sections.cards.description', 'grid-dots', 'content' ),
			self::build_section_definition( 'metrics', 'manager.page_elements.sections.metrics.label', 'manager.page_elements.sections.metrics.description', 'chart-bar', 'marketing' ),
			self::build_section_definition( 'steps', 'manager.page_elements.sections.steps.label', 'manager.page_elements.sections.steps.description', 'list-check', 'content' ),
			self::build_section_definition( 'pricing', 'manager.page_elements.sections.pricing.label', 'manager.page_elements.sections.pricing.description', 'receipt', 'marketing' ),
			self::build_section_definition( 'cases', 'manager.page_elements.sections.cases.label', 'manager.page_elements.sections.cases.description', 'briefcase', 'portfolio' ),
			self::build_section_definition( 'reviews', 'manager.page_elements.sections.reviews.label', 'manager.page_elements.sections.reviews.description', 'message-circle', 'trust' ),
			self::build_section_definition( 'team', 'manager.page_elements.sections.team.label', 'manager.page_elements.sections.team.description', 'users', 'trust' ),
			self::build_section_definition( 'partners', 'manager.page_elements.sections.partners.label', 'manager.page_elements.sections.partners.description', 'building', 'trust' ),
			self::build_section_definition( 'gallery', 'manager.page_elements.sections.gallery.label', 'manager.page_elements.sections.gallery.description', 'photo', 'media' ),
			self::build_section_definition( 'video', 'manager.page_elements.sections.video.label', 'manager.page_elements.sections.video.description', 'video', 'media' ),
			self::build_section_definition( 'faq', 'manager.page_elements.sections.faq.label', 'manager.page_elements.sections.faq.description', 'help-circle', 'content' ),
			self::build_section_definition( 'contacts', 'manager.page_elements.sections.contacts.label', 'manager.page_elements.sections.contacts.description', 'address-book', 'contact', 'contacts', 'manager.pages.defaults.contacts.block_name' ),
			self::build_section_definition( 'map', 'manager.page_elements.sections.map.label', 'manager.page_elements.sections.map.description', 'map-pin', 'contact' ),
			self::build_section_definition( 'form', 'manager.page_elements.sections.form.label', 'manager.page_elements.sections.form.description', 'forms', 'contact' ),
			self::build_section_definition( 'cta', 'manager.page_elements.sections.cta.label', 'manager.page_elements.sections.cta.description', 'target-arrow', 'marketing' ),
			self::build_section_definition( 'documents_links', 'manager.page_elements.sections.documents_links.label', 'manager.page_elements.sections.documents_links.description', 'file-text', 'content' ),
			self::build_section_definition( 'divider', 'manager.page_elements.sections.divider.label', 'manager.page_elements.sections.divider.description', 'minus', 'layout' ),
			self::build_section_definition( 'banner', 'manager.page_elements.sections.banner.label', 'manager.page_elements.sections.banner.description', 'speakerphone', 'marketing' ),
			self::build_section_definition( 'comparison', 'manager.page_elements.sections.comparison.label', 'manager.page_elements.sections.comparison.description', 'columns', 'content' ),
			self::build_section_definition( 'schedule', 'manager.page_elements.sections.schedule.label', 'manager.page_elements.sections.schedule.description', 'calendar', 'content' ),
			self::build_section_definition( 'showcase', 'manager.page_elements.sections.showcase.label', 'manager.page_elements.sections.showcase.description', 'layout-dashboard', 'marketing' ),
			self::build_section_definition( 'social_links', 'manager.page_elements.sections.social_links.label', 'manager.page_elements.sections.social_links.description', 'world-share', 'contact' ),
			self::build_section_definition( 'embed', 'manager.page_elements.sections.embed.label', 'manager.page_elements.sections.embed.description', 'code', 'advanced' ),
		);
	}

	public static function get_section_definitions(): array {
		$definitions = self::get_core_section_definitions();

		if ( function_exists( 'apply_filters' ) ) {
			$definitions = apply_filters( 'sonyra_site_manager_section_definitions', $definitions );
		}

		if ( ! is_array( $definitions ) ) {
			$definitions = array();
		}

		$normalized = array();

		foreach ( $definitions as $definition ) {
			if ( ! is_array( $definition ) ) {
				continue;
			}

			$next_definition = self::normalize_section_definition( $definition );

			if ( empty( $next_definition['type'] ) ) {
				continue;
			}

			$normalized[ $next_definition['type'] ] = $next_definition;
		}

		return array_values( $normalized );
	}

	public static function normalize_section_definition( array $definition ): array {
		$type                   = isset( $definition['type'] ) ? sanitize_key( (string) $definition['type'] ) : '';
		$label                  = isset( $definition['label'] ) ? sanitize_text_field( (string) $definition['label'] ) : '';
		$description            = isset( $definition['description'] ) ? sanitize_textarea_field( (string) $definition['description'] ) : '';
		$label_key              = isset( $definition['label_key'] ) ? sanitize_text_field( (string) $definition['label_key'] ) : '';
		$description_key        = isset( $definition['description_key'] ) ? sanitize_text_field( (string) $definition['description_key'] ) : '';
		$default_block_name_key = isset( $definition['default_block_name_key'] ) ? sanitize_text_field( (string) $definition['default_block_name_key'] ) : '';
		$icon_key               = isset( $definition['icon_key'] ) ? sanitize_key( (string) $definition['icon_key'] ) : '';
		$category               = isset( $definition['category'] ) ? sanitize_key( (string) $definition['category'] ) : '';
		$provider               = isset( $definition['provider'] ) ? sanitize_key( (string) $definition['provider'] ) : 'core';
		$module_key             = isset( $definition['module_key'] ) ? sanitize_key( (string) $definition['module_key'] ) : '';
		$status                 = isset( $definition['status'] ) ? sanitize_key( (string) $definition['status'] ) : 'implemented';
		$default_block          = isset( $definition['default_block_type'] ) ? sanitize_key( (string) $definition['default_block_type'] ) : 'text';
		$default_name           = isset( $definition['default_block_name'] ) ? sanitize_text_field( (string) $definition['default_block_name'] ) : $label;
		$fields                 = isset( $definition['fields'] ) && is_array( $definition['fields'] ) ? array_values( $definition['fields'] ) : array();

		if ( '' === $type || '' === $label || '' === $description || '' === $icon_key || '' === $category ) {
			return array();
		}

		if ( class_exists( 'Sonyra_Site_Manager_Icon_Registry' ) && ! Sonyra_Site_Manager_Icon_Registry::has( $icon_key ) ) {
			$icon_key = 'circle-dot';
		}

		$default_section = isset( $definition['default_section'] ) && is_array( $definition['default_section'] ) ? $definition['default_section'] : array();
		$default_blocks  = isset( $definition['default_blocks'] ) && is_array( $definition['default_blocks'] ) ? $definition['default_blocks'] : array();

		if ( empty( $default_blocks ) ) {
			$default_blocks = array(
				self::build_default_legacy_block(
					$default_block,
					$default_name,
					$label,
					'description',
					$description
				),
			);
		}

		$default_section = array_merge(
			array(
				'id'         => '',
				'type'       => $type,
				'name'       => $label,
				'blocks'     => $default_blocks,
				'provider'   => $provider,
				'module_key' => $module_key,
			),
			$default_section
		);

		return array(
			'type'                   => $type,
			'label'                  => $label,
			'description'            => $description,
			'label_key'              => $label_key,
			'description_key'        => $description_key,
			'icon_key'               => $icon_key,
			'category'               => $category,
			'provider'               => '' !== $provider ? $provider : 'core',
			'module_key'             => $module_key,
			'status'                 => '' !== $status ? $status : 'implemented',
			'default_block_name_key' => $default_block_name_key,
			'default_section'        => $default_section,
			'default_blocks'         => $default_blocks,
			'fields'                 => $fields,
		);
	}

	public static function get_section_definition( $type ): array {
		$type = sanitize_key( (string) $type );

		if ( '' === $type ) {
			return array();
		}

		foreach ( self::get_section_definitions() as $definition ) {
			if ( $type === (string) $definition['type'] ) {
				return $definition;
			}
		}

		return array();
	}

	public static function get_section_type_options(): array {
		$options = array();

		foreach ( self::get_section_definitions() as $definition ) {
			$options[] = array(
				'type'            => (string) $definition['type'],
				'label'           => (string) $definition['label'],
				'description'     => (string) $definition['description'],
				'label_key'       => isset( $definition['label_key'] ) ? (string) $definition['label_key'] : '',
				'description_key' => isset( $definition['description_key'] ) ? (string) $definition['description_key'] : '',
				'icon_key'        => (string) $definition['icon_key'],
				'category'        => (string) $definition['category'],
				'provider'        => (string) $definition['provider'],
				'module_key'      => (string) $definition['module_key'],
				'status'          => (string) $definition['status'],
			);
		}

		return $options;
	}

	public static function get_section_defaults( $type ): array {
		$definition = self::get_section_definition( $type );

		if ( empty( $definition ) ) {
			return array();
		}

		return isset( $definition['default_section'] ) && is_array( $definition['default_section'] ) ? $definition['default_section'] : array();
	}

	public static function get_core_block_definitions(): array {
		return array(
			self::build_block_definition( 'accent', 'manager.page_elements.blocks.accent.label', 'manager.page_elements.blocks.accent.description', 'sparkles', 'marketing', array( 'kicker', 'heading', 'text', 'button_label', 'button_url' ), array( 'name' => self::text( 'manager.page_elements.blocks.accent.label' ) ) ),
			self::build_block_definition( 'heading', 'manager.page_elements.blocks.heading.label', 'manager.page_elements.blocks.heading.description', 'text-size', 'content', array( 'kicker', 'heading', 'description' ) ),
			self::build_block_definition( 'text', 'manager.page_elements.blocks.text.label', 'manager.page_elements.blocks.text.description', 'file-text', 'content', array( 'heading', 'text' ) ),
			self::build_block_definition( 'button', 'manager.page_elements.blocks.button.label', 'manager.page_elements.blocks.button.description', 'arrow-right', 'marketing', array( 'heading', 'button_label', 'button_url', 'description' ) ),
			self::build_block_definition( 'image', 'manager.page_elements.blocks.image.label', 'manager.page_elements.blocks.image.description', 'photo', 'media', array( 'heading', 'image_url', 'image_alt', 'caption', 'description' ) ),
			self::build_block_definition( 'video', 'manager.page_elements.blocks.video.label', 'manager.page_elements.blocks.video.description', 'video', 'media', array( 'heading', 'video_url', 'description' ) ),
			self::build_block_definition( 'card', 'manager.page_elements.blocks.card.label', 'manager.page_elements.blocks.card.description', 'components', 'content', array( 'heading', 'text', 'button_label', 'button_url' ) ),
			self::build_block_definition( 'icon_text', 'manager.page_elements.blocks.icon_text.label', 'manager.page_elements.blocks.icon_text.description', 'info-circle', 'content', array( 'icon_key', 'heading', 'description' ), array( 'icon_key' => 'sparkles' ) ),
			self::build_block_definition( 'list', 'manager.page_elements.blocks.list.label', 'manager.page_elements.blocks.list.description', 'list', 'content', array( 'heading', 'list_text', 'description' ) ),
			self::build_block_definition( 'metric', 'manager.page_elements.blocks.metric.label', 'manager.page_elements.blocks.metric.description', 'chart-bar', 'marketing', array( 'value', 'metric_label', 'description' ) ),
			self::build_block_definition( 'step', 'manager.page_elements.blocks.step.label', 'manager.page_elements.blocks.step.description', 'list-check', 'content', array( 'step_number', 'heading', 'text' ) ),
			self::build_block_definition( 'testimonial', 'manager.page_elements.blocks.testimonial.label', 'manager.page_elements.blocks.testimonial.description', 'message-circle', 'trust', array( 'quote', 'author', 'role' ) ),
			self::build_block_definition( 'person_profile', 'manager.page_elements.blocks.person_profile.label', 'manager.page_elements.blocks.person_profile.description', 'user', 'trust', array( 'name_person', 'role', 'description', 'image_url' ) ),
			self::build_block_definition( 'logo', 'manager.page_elements.blocks.logo.label', 'manager.page_elements.blocks.logo.description', 'photo', 'trust', array( 'heading', 'image_url', 'link_url', 'description' ) ),
			self::build_block_definition( 'contact', 'manager.page_elements.blocks.contact.label', 'manager.page_elements.blocks.contact.description', 'address-book', 'contact', array( 'heading', 'email', 'phone', 'address', 'link_url' ) ),
			self::build_block_definition( 'map', 'manager.page_elements.blocks.map.label', 'manager.page_elements.blocks.map.description', 'map-pin', 'contact', array( 'address', 'map_url', 'description' ) ),
			self::build_block_definition( 'form', 'manager.page_elements.blocks.form.label', 'manager.page_elements.blocks.form.description', 'forms', 'contact', array( 'heading', 'text', 'button_label' ) ),
			self::build_block_definition( 'faq_item', 'manager.page_elements.blocks.faq_item.label', 'manager.page_elements.blocks.faq_item.description', 'help-circle', 'content', array( 'question', 'answer' ) ),
			self::build_block_definition( 'link', 'manager.page_elements.blocks.link.label', 'manager.page_elements.blocks.link.description', 'link', 'content', array( 'link_label', 'link_url', 'description' ) ),
			self::build_block_definition( 'file', 'manager.page_elements.blocks.file.label', 'manager.page_elements.blocks.file.description', 'file', 'content', array( 'file_label', 'file_url', 'description' ) ),
			self::build_block_definition( 'divider', 'manager.page_elements.blocks.divider.label', 'manager.page_elements.blocks.divider.description', 'minus', 'layout', array( 'description' ) ),
			self::build_block_definition( 'social_link', 'manager.page_elements.blocks.social_link.label', 'manager.page_elements.blocks.social_link.description', 'world-share', 'contact', array( 'platform', 'link_url' ) ),
			self::build_block_definition( 'price', 'manager.page_elements.blocks.price.label', 'manager.page_elements.blocks.price.description', 'receipt', 'marketing', array( 'value', 'metric_label', 'description' ) ),
			self::build_block_definition( 'tag', 'manager.page_elements.blocks.tag.label', 'manager.page_elements.blocks.tag.description', 'tag', 'content', array( 'tag_label', 'description' ) ),
			self::build_block_definition( 'embed', 'manager.page_elements.blocks.embed.label', 'manager.page_elements.blocks.embed.description', 'code', 'advanced', array( 'heading', 'embed_code', 'description' ) ),
		);
	}

	public static function get_block_definitions(): array {
		$definitions = self::get_core_block_definitions();

		if ( function_exists( 'apply_filters' ) ) {
			$definitions = apply_filters( 'sonyra_site_manager_block_definitions', $definitions );
		}

		if ( ! is_array( $definitions ) ) {
			$definitions = array();
		}

		$normalized = array();

		foreach ( $definitions as $definition ) {
			if ( ! is_array( $definition ) ) {
				continue;
			}

			$next_definition = self::normalize_block_definition( $definition );

			if ( empty( $next_definition['type'] ) || empty( $next_definition['enabled'] ) ) {
				continue;
			}

			$normalized[ $next_definition['type'] ] = $next_definition;
		}

		return array_values( $normalized );
	}

	public static function get_block_definition( $type ): array {
		$type = sanitize_key( (string) $type );

		if ( '' === $type ) {
			return array();
		}

		foreach ( self::get_block_definitions() as $definition ) {
			if ( $type === (string) $definition['type'] ) {
				return $definition;
			}
		}

		return array();
	}

	public static function normalize_block_definition( array $definition ): array {
		$type            = isset( $definition['type'] ) ? sanitize_key( (string) $definition['type'] ) : '';
		$label           = isset( $definition['label'] ) ? sanitize_text_field( (string) $definition['label'] ) : '';
		$description     = isset( $definition['description'] ) ? sanitize_textarea_field( (string) $definition['description'] ) : '';
		$label_key       = isset( $definition['label_key'] ) ? sanitize_text_field( (string) $definition['label_key'] ) : '';
		$description_key = isset( $definition['description_key'] ) ? sanitize_text_field( (string) $definition['description_key'] ) : '';
		$icon            = isset( $definition['icon'] ) ? sanitize_key( (string) $definition['icon'] ) : ( isset( $definition['icon_key'] ) ? sanitize_key( (string) $definition['icon_key'] ) : '' );
		$category        = isset( $definition['category'] ) ? sanitize_key( (string) $definition['category'] ) : 'content';
		$source          = isset( $definition['source'] ) ? sanitize_key( (string) $definition['source'] ) : 'core';
		$module_key      = isset( $definition['module_key'] ) ? sanitize_key( (string) $definition['module_key'] ) : '';
		$enabled         = ! isset( $definition['enabled'] ) || false !== $definition['enabled'];
		$fields          = isset( $definition['fields'] ) && is_array( $definition['fields'] ) ? array_values( array_filter( array_map( 'sanitize_key', $definition['fields'] ) ) ) : array();
		$defaults        = isset( $definition['defaults'] ) && is_array( $definition['defaults'] ) ? self::preserve_mixed_value( $definition['defaults'] ) : array();

		if ( '' === $type || '' === $label || '' === $description || '' === $label_key || '' === $description_key ) {
			return array();
		}

		if ( '' === $icon ) {
			$icon = 'circle-dot';
		}

		if ( class_exists( 'Sonyra_Site_Manager_Icon_Registry' ) && ! Sonyra_Site_Manager_Icon_Registry::has( $icon ) ) {
			$icon = 'circle-dot';
		}

		$defaults = array_merge(
			array(
				'type' => $type,
				'name' => $label,
			),
			$defaults
		);

		return array(
			'type'            => $type,
			'label'           => $label,
			'description'     => $description,
			'label_key'       => $label_key,
			'description_key' => $description_key,
			'icon'            => $icon,
			'icon_key'        => $icon,
			'category'        => $category,
			'source'          => '' !== $source ? $source : 'core',
			'module_key'      => $module_key,
			'enabled'         => $enabled,
			'fields'          => $fields,
			'defaults'        => $defaults,
		);
	}

	public static function get_core_widget_definitions(): array {
		return array(
			self::build_widget_definition( 'call', 'manager.page_elements.widgets.call.label', 'manager.page_elements.widgets.call.description', 'phone', 'contact', 'core', array( 'label', 'phone' ) ),
			self::build_widget_definition( 'email', 'manager.page_elements.widgets.email.label', 'manager.page_elements.widgets.email.description', 'mail', 'contact', 'core', array( 'label', 'email' ) ),
			self::build_widget_definition( 'message', 'manager.page_elements.widgets.message.label', 'manager.page_elements.widgets.message.description', 'message-circle', 'contact', 'core', array( 'label', 'url', 'message' ) ),
			self::build_widget_definition( 'messenger', 'manager.page_elements.widgets.messenger.label', 'manager.page_elements.widgets.messenger.description', 'send', 'contact', 'core', array( 'label', 'platform', 'url' ) ),
			self::build_widget_definition( 'back_to_top', 'manager.page_elements.widgets.back_to_top.label', 'manager.page_elements.widgets.back_to_top.description', 'arrow-up', 'layout', 'core', array( 'label' ) ),
			self::build_widget_definition( 'social_button', 'manager.page_elements.widgets.social_button.label', 'manager.page_elements.widgets.social_button.description', 'world-share', 'contact', 'core', array( 'label', 'platform', 'url' ) ),
			self::build_widget_definition( 'quick_request', 'manager.page_elements.widgets.quick_request.label', 'manager.page_elements.widgets.quick_request.description', 'forms', 'contact', 'core', array( 'label', 'url' ) ),
			self::build_widget_definition( 'route_map', 'manager.page_elements.widgets.route_map.label', 'manager.page_elements.widgets.route_map.description', 'map-pin', 'contact', 'core', array( 'label', 'address', 'url' ) ),
			self::build_widget_definition( 'callback', 'manager.page_elements.widgets.callback.label', 'manager.page_elements.widgets.callback.description', 'phone', 'contact', 'core', array( 'label', 'phone' ) ),
			self::build_widget_definition( 'floating_button', 'manager.page_elements.widgets.floating_button.label', 'manager.page_elements.widgets.floating_button.description', 'circle-dot', 'layout', 'core', array( 'label', 'url' ) ),
			self::build_widget_definition( 'external_widget', 'manager.page_elements.widgets.external_widget.label', 'manager.page_elements.widgets.external_widget.description', 'code', 'advanced', 'external_embed', array( 'embed_code', 'description' ) ),
		);
	}

	public static function get_core_popup_definitions(): array {
		return array(
			self::build_popup_definition( 'info', 'manager.page_elements.popups.info.label', 'manager.page_elements.popups.info.description', 'info-circle', 'content', 'core', array( 'heading', 'text', 'button_label', 'button_url' ) ),
			self::build_popup_definition( 'request_form', 'manager.page_elements.popups.request_form.label', 'manager.page_elements.popups.request_form.description', 'forms', 'contact', 'core', array( 'heading', 'text', 'button_label' ) ),
			self::build_popup_definition( 'callback', 'manager.page_elements.popups.callback.label', 'manager.page_elements.popups.callback.description', 'phone', 'contact', 'core', array( 'heading', 'text', 'phone', 'button_label' ) ),
			self::build_popup_definition( 'offer', 'manager.page_elements.popups.offer.label', 'manager.page_elements.popups.offer.description', 'speakerphone', 'marketing', 'core', array( 'heading', 'text', 'button_label', 'button_url' ) ),
			self::build_popup_definition( 'subscription', 'manager.page_elements.popups.subscription.label', 'manager.page_elements.popups.subscription.description', 'mail', 'contact', 'core', array( 'heading', 'text', 'email', 'button_label' ) ),
			self::build_popup_definition( 'notice', 'manager.page_elements.popups.notice.label', 'manager.page_elements.popups.notice.description', 'alert-circle', 'content', 'core', array( 'heading', 'text' ) ),
			self::build_popup_definition( 'confirmation', 'manager.page_elements.popups.confirmation.label', 'manager.page_elements.popups.confirmation.description', 'circle-check', 'content', 'core', array( 'heading', 'text', 'button_label' ) ),
			self::build_popup_definition( 'document_text', 'manager.page_elements.popups.document_text.label', 'manager.page_elements.popups.document_text.description', 'file-text', 'content', 'core', array( 'heading', 'text', 'button_label', 'button_url' ) ),
			self::build_popup_definition( 'video_popup', 'manager.page_elements.popups.video_popup.label', 'manager.page_elements.popups.video_popup.description', 'video', 'media', 'core', array( 'heading', 'text', 'video_url' ) ),
			self::build_popup_definition( 'contacts', 'manager.page_elements.popups.contacts.label', 'manager.page_elements.popups.contacts.description', 'address-book', 'contact', 'core', array( 'heading', 'text', 'email', 'phone', 'address', 'button_url' ) ),
			self::build_popup_definition( 'external_popup', 'manager.page_elements.popups.external_popup.label', 'manager.page_elements.popups.external_popup.description', 'code', 'advanced', 'external_embed', array( 'embed_code', 'description' ) ),
		);
	}

	public static function get_widget_definitions(): array {
		$definitions = self::get_core_widget_definitions();

		if ( function_exists( 'apply_filters' ) ) {
			$definitions = apply_filters( 'sonyra_site_manager_widget_definitions', $definitions );
		}

		if ( ! is_array( $definitions ) ) {
			$definitions = array();
		}

		$normalized = array();

		foreach ( $definitions as $definition ) {
			if ( ! is_array( $definition ) ) {
				continue;
			}

			$next_definition = self::normalize_widget_definition( $definition );

			if ( empty( $next_definition['type'] ) || empty( $next_definition['enabled'] ) ) {
				continue;
			}

			$normalized[ $next_definition['type'] ] = $next_definition;
		}

		return array_values( $normalized );
	}

	public static function get_popup_definitions(): array {
		$definitions = self::get_core_popup_definitions();

		if ( function_exists( 'apply_filters' ) ) {
			$definitions = apply_filters( 'sonyra_site_manager_popup_definitions', $definitions );
		}

		if ( ! is_array( $definitions ) ) {
			$definitions = array();
		}

		$normalized = array();

		foreach ( $definitions as $definition ) {
			if ( ! is_array( $definition ) ) {
				continue;
			}

			$next_definition = self::normalize_popup_definition( $definition );

			if ( empty( $next_definition['type'] ) || empty( $next_definition['enabled'] ) ) {
				continue;
			}

			$normalized[ $next_definition['type'] ] = $next_definition;
		}

		return array_values( $normalized );
	}

	public static function get_widget_definition( $type ): array {
		$type = sanitize_key( (string) $type );

		if ( '' === $type ) {
			return array();
		}

		foreach ( self::get_widget_definitions() as $definition ) {
			if ( $type === (string) $definition['type'] ) {
				return $definition;
			}
		}

		return array();
	}

	public static function get_popup_definition( $type ): array {
		$type = sanitize_key( (string) $type );

		if ( '' === $type ) {
			return array();
		}

		foreach ( self::get_popup_definitions() as $definition ) {
			if ( $type === (string) $definition['type'] ) {
				return $definition;
			}
		}

		return array();
	}

	public static function normalize_widget_definition( array $definition ): array {
		$type             = isset( $definition['type'] ) ? sanitize_key( (string) $definition['type'] ) : '';
		$label            = isset( $definition['label'] ) ? sanitize_text_field( (string) $definition['label'] ) : '';
		$description      = isset( $definition['description'] ) ? sanitize_textarea_field( (string) $definition['description'] ) : '';
		$label_key        = isset( $definition['label_key'] ) ? sanitize_text_field( (string) $definition['label_key'] ) : '';
		$description_key  = isset( $definition['description_key'] ) ? sanitize_text_field( (string) $definition['description_key'] ) : '';
		$icon             = isset( $definition['icon'] ) ? sanitize_key( (string) $definition['icon'] ) : ( isset( $definition['icon_key'] ) ? sanitize_key( (string) $definition['icon_key'] ) : '' );
		$category         = isset( $definition['category'] ) ? sanitize_key( (string) $definition['category'] ) : 'layout';
		$source           = isset( $definition['source'] ) ? sanitize_key( (string) $definition['source'] ) : 'core';
		$module_key       = isset( $definition['module_key'] ) ? sanitize_key( (string) $definition['module_key'] ) : '';
		$integration_mode = isset( $definition['integration_mode'] ) ? sanitize_key( (string) $definition['integration_mode'] ) : 'core';
		$enabled          = ! isset( $definition['enabled'] ) || false !== $definition['enabled'];
		$fields           = isset( $definition['fields'] ) && is_array( $definition['fields'] ) ? array_values( array_filter( array_map( 'sanitize_key', $definition['fields'] ) ) ) : array();
		$defaults         = isset( $definition['defaults'] ) && is_array( $definition['defaults'] ) ? self::preserve_mixed_value( $definition['defaults'] ) : array();

		if ( '' === $type || '' === $label || '' === $description || '' === $label_key || '' === $description_key ) {
			return array();
		}

		if ( ! in_array( $integration_mode, array( 'core', 'external_embed', 'module_native' ), true ) ) {
			$integration_mode = 'core';
		}

		if ( '' === $icon ) {
			$icon = 'circle-dot';
		}

		if ( class_exists( 'Sonyra_Site_Manager_Icon_Registry' ) && ! Sonyra_Site_Manager_Icon_Registry::has( $icon ) ) {
			$icon = 'circle-dot';
		}

		$defaults = array_merge(
			array(
				'type'     => $type,
				'name'     => $label,
				'label'    => $label,
				'enabled'  => true,
				'position' => 'bottom_right',
			),
			$defaults
		);

		return array(
			'type'             => $type,
			'label'            => $label,
			'description'      => $description,
			'label_key'        => $label_key,
			'description_key'  => $description_key,
			'icon'             => $icon,
			'icon_key'         => $icon,
			'category'         => $category,
			'source'           => '' !== $source ? $source : 'core',
			'module_key'       => $module_key,
			'integration_mode' => $integration_mode,
			'enabled'          => $enabled,
			'fields'           => $fields,
			'defaults'         => $defaults,
		);
	}

	public static function normalize_popup_definition( array $definition ): array {
		$type             = isset( $definition['type'] ) ? sanitize_key( (string) $definition['type'] ) : '';
		$label            = isset( $definition['label'] ) ? sanitize_text_field( (string) $definition['label'] ) : '';
		$description      = isset( $definition['description'] ) ? sanitize_textarea_field( (string) $definition['description'] ) : '';
		$label_key        = isset( $definition['label_key'] ) ? sanitize_text_field( (string) $definition['label_key'] ) : '';
		$description_key  = isset( $definition['description_key'] ) ? sanitize_text_field( (string) $definition['description_key'] ) : '';
		$icon             = isset( $definition['icon'] ) ? sanitize_key( (string) $definition['icon'] ) : ( isset( $definition['icon_key'] ) ? sanitize_key( (string) $definition['icon_key'] ) : '' );
		$category         = isset( $definition['category'] ) ? sanitize_key( (string) $definition['category'] ) : 'content';
		$source           = isset( $definition['source'] ) ? sanitize_key( (string) $definition['source'] ) : 'core';
		$module_key       = isset( $definition['module_key'] ) ? sanitize_key( (string) $definition['module_key'] ) : '';
		$integration_mode = isset( $definition['integration_mode'] ) ? sanitize_key( (string) $definition['integration_mode'] ) : 'core';
		$enabled          = ! isset( $definition['enabled'] ) || false !== $definition['enabled'];
		$fields           = isset( $definition['fields'] ) && is_array( $definition['fields'] ) ? array_values( array_filter( array_map( 'sanitize_key', $definition['fields'] ) ) ) : array();
		$defaults         = isset( $definition['defaults'] ) && is_array( $definition['defaults'] ) ? self::preserve_mixed_value( $definition['defaults'] ) : array();

		if ( '' === $type || '' === $label || '' === $description || '' === $label_key || '' === $description_key ) {
			return array();
		}

		if ( ! in_array( $integration_mode, array( 'core', 'external_embed', 'module_native' ), true ) ) {
			$integration_mode = 'core';
		}

		if ( '' === $icon ) {
			$icon = 'circle-dot';
		}

		if ( class_exists( 'Sonyra_Site_Manager_Icon_Registry' ) && ! Sonyra_Site_Manager_Icon_Registry::has( $icon ) ) {
			$icon = 'circle-dot';
		}

		$defaults = array_merge(
			array(
				'type'    => $type,
				'name'    => $label,
				'heading' => $label,
				'enabled' => true,
				'trigger' => 'manual',
			),
			$defaults
		);

		return array(
			'type'             => $type,
			'label'            => $label,
			'description'      => $description,
			'label_key'        => $label_key,
			'description_key'  => $description_key,
			'icon'             => $icon,
			'icon_key'         => $icon,
			'category'         => $category,
			'source'           => '' !== $source ? $source : 'core',
			'module_key'       => $module_key,
			'integration_mode' => $integration_mode,
			'enabled'          => $enabled,
			'fields'           => $fields,
			'defaults'         => $defaults,
		);
	}

	private static function build_section_definition( string $type, string $label_key, string $description_key, string $icon_key, string $category, string $default_block_type = 'text', string $default_block_name_key = '' ): array {
		$label       = self::text( $label_key );
		$description = self::text( $description_key );
		$block_name  = '' !== $default_block_name_key ? self::text( $default_block_name_key ) : $label;

		if ( 'hero' === $default_block_type ) {
			$default_blocks = array(
				array(
					'id'           => '',
					'type'         => 'hero',
					'name'         => $block_name,
					'heading'      => $label,
					'text'         => self::text( 'manager.pages.defaults.hero.text' ),
					'button_label' => '',
					'button_url'   => '',
				),
			);
		} elseif ( 'contacts' === $default_block_type ) {
			$default_blocks = array(
				array(
					'id'          => '',
					'type'        => 'contacts',
					'name'        => $block_name,
					'heading'     => $label,
					'description' => self::text( 'manager.pages.defaults.contacts.description' ),
					'email'       => '',
					'phone'       => '',
					'address'     => '',
				),
			);
		} else {
			$default_blocks = array(
				self::build_default_legacy_block( 'text', $block_name, $label, 'text', $description ),
			);
		}

		return array(
			'type'                   => $type,
			'label'                  => $label,
			'description'            => $description,
			'label_key'              => $label_key,
			'description_key'        => $description_key,
			'icon_key'               => $icon_key,
			'category'               => $category,
			'provider'               => 'core',
			'module_key'             => '',
			'status'                 => 'implemented',
			'default_block_type'     => $default_block_type,
			'default_block_name'     => $block_name,
			'default_block_name_key' => $default_block_name_key,
			'default_section'        => array(
				'id'     => '',
				'type'   => $type,
				'name'   => $label,
				'blocks' => $default_blocks,
			),
			'default_blocks'         => $default_blocks,
			'fields'                 => array(),
		);
	}

	private static function build_block_definition( string $type, string $label_key, string $description_key, string $icon, string $category, array $fields, array $defaults = array() ): array {
		$label       = self::text( $label_key );
		$description = self::text( $description_key );

		return array(
			'type'            => $type,
			'label'           => $label,
			'description'     => $description,
			'label_key'       => $label_key,
			'description_key' => $description_key,
			'icon'            => $icon,
			'category'        => $category,
			'source'          => 'core',
			'module_key'      => '',
			'enabled'         => true,
			'fields'          => $fields,
			'defaults'        => array_merge(
				array(
					'type' => $type,
					'name' => $label,
				),
				$defaults
			),
		);
	}

	private static function build_widget_definition( string $type, string $label_key, string $description_key, string $icon, string $category, string $integration_mode, array $fields, array $defaults = array() ): array {
		$label       = self::text( $label_key );
		$description = self::text( $description_key );

		return array(
			'type'             => $type,
			'label'            => $label,
			'description'      => $description,
			'label_key'        => $label_key,
			'description_key'  => $description_key,
			'icon'             => $icon,
			'category'         => $category,
			'source'           => 'core',
			'module_key'       => '',
			'integration_mode' => $integration_mode,
			'enabled'          => true,
			'fields'           => $fields,
			'defaults'         => array_merge(
				array(
					'type'     => $type,
					'name'     => $label,
					'label'    => $label,
					'enabled'  => true,
					'position' => 'bottom_right',
				),
				$defaults
			),
		);
	}

	private static function build_popup_definition( string $type, string $label_key, string $description_key, string $icon, string $category, string $integration_mode, array $fields, array $defaults = array() ): array {
		$label       = self::text( $label_key );
		$description = self::text( $description_key );

		return array(
			'type'             => $type,
			'label'            => $label,
			'description'      => $description,
			'label_key'        => $label_key,
			'description_key'  => $description_key,
			'icon'             => $icon,
			'category'         => $category,
			'source'           => 'core',
			'module_key'       => '',
			'integration_mode' => $integration_mode,
			'enabled'          => true,
			'fields'           => $fields,
			'defaults'         => array_merge(
				array(
					'type'    => $type,
					'name'    => $label,
					'heading' => $label,
					'enabled' => true,
					'trigger' => 'manual',
				),
				$defaults
			),
		);
	}

	private static function build_default_legacy_block( string $type, string $name, string $heading, string $text_key, string $text_value ): array {
		return array(
			'id'      => '',
			'type'    => $type,
			'name'    => $name,
			'heading' => $heading,
			'text'    => 'text' === $text_key ? $text_value : '',
		);
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

	private static function text( string $key ): string {
		if ( class_exists( 'Sonyra_Site_Manager_I18n' ) ) {
			return Sonyra_Site_Manager_I18n::t( $key );
		}

		return '';
	}
}
