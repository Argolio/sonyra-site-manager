<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Sonyra_Site_Manager_Pages_Section {

	public static function get_section_key(): string {
		return 'pages';
	}

	public static function render( array $context = array() ): void {
		unset( $context );
		$section_library = wp_json_encode( self::get_section_library_payload() );
		$block_library   = wp_json_encode( self::get_block_library_payload() );
		$widget_library  = wp_json_encode( self::get_widget_library_payload() );
		$popup_library   = wp_json_encode( self::get_popup_library_payload() );
		?>
			<section class="sonyra-manager-view sonyra-manager-view-pages sonyra-pages-section" data-manager-view="pages" data-manager-pages-root hidden>
				<div class="sonyra-manager-pages-app">
					<div class="sonyra-manager-pages-message" data-pages-message hidden></div>
					<div class="sonyra-manager-pages-table-screen" data-pages-table-screen></div>

					<section class="sonyra-manager-pages-sections-mode" data-pages-sections-mode hidden>
						<div class="sonyra-pages-sections-workspace" data-pages-workspace></div>
					</section>

				<div class="sonyra-manager-pages-modal-root" data-pages-modal-root></div>
				<div class="sonyra-manager-pages-help-root" data-pages-help-popover-root></div>

				<div class="sonyra-manager-pages-icon-templates" hidden>
						<?php foreach ( array( 'file-text', 'settings', 'external-link', 'search', 'dots-vertical', 'dots-grip', 'menu-header', 'menu-footer', 'circle-check', 'progress', 'info-circle', 'alert-circle', 'eye', 'eye-off', 'section', 'trash', 'alert-triangle', 'arrow-left', 'arrow-right', 'arrow-up', 'x', 'filter', 'arrows-sort', 'browser-preview', 'link', 'home', 'world-search', 'history', 'copy', 'app-window', 'address-book', 'align-left', 'mail', 'photo', 'layers-selected', 'components', 'grid-dots', 'chart-bar', 'list-check', 'receipt', 'briefcase', 'message-circle', 'users', 'user', 'building', 'video', 'help-circle', 'map-pin', 'forms', 'target-arrow', 'minus', 'speakerphone', 'columns', 'calendar', 'layout-dashboard', 'world-share', 'code', 'circle-dot', 'sparkles', 'text-size', 'phone', 'file', 'tag', 'send', 'plus' ) as $icon_key ) : ?>
							<span data-pages-icon-template="<?php echo esc_attr( $icon_key ); ?>"><?php self::render_icon( $icon_key, 'sonyra-manager-icon sonyra-manager-pages-button-icon' ); ?></span>
						<?php endforeach; ?>
					</div>
					<script type="application/json" id="sonyra-manager-pages-sections-library"><?php echo $section_library; ?></script>
					<script type="application/json" id="sonyra-manager-pages-blocks-library"><?php echo $block_library; ?></script>
					<script type="application/json" id="sonyra-manager-pages-widgets-library"><?php echo $widget_library; ?></script>
					<script type="application/json" id="sonyra-manager-pages-popups-library"><?php echo $popup_library; ?></script>
			</div>
		</section>
		<?php
	}

	public static function get_i18n(): array {
		$keys = array(
			'manager.pages.loading',
			'manager.pages.empty_title',
			'manager.pages.empty_description',
			'manager.pages.empty_reset_title',
			'manager.pages.empty_reset_description',
			'manager.pages.empty_reset_action',
			'manager.pages.open_site_disabled',
			'manager.pages.actions.create',
			'manager.pages.actions.open_site',
			'manager.pages.actions.parameters',
			'manager.pages.actions.sections',
			'manager.pages.actions.open',
			'manager.pages.actions.preview',
			'manager.pages.actions.copy_link',
			'manager.pages.actions.make_home',
			'manager.pages.actions.hide',
			'manager.pages.actions.publish',
			'manager.pages.actions.seo',
			'manager.pages.actions.history',
			'manager.pages.actions.duplicate',
			'manager.pages.actions.delete',
			'manager.pages.actions.page_actions',
				'manager.pages.actions.save',
				'manager.pages.actions.saving',
				'manager.pages.actions.cancel',
				'manager.pages.actions.back_to_table',
				'manager.pages.actions.add_section',
				'manager.pages.actions.add_block',
				'manager.pages.actions.help_tooltip',
				'manager.pages.actions.drag_section',
				'manager.pages.blocks.drag_label',
				'manager.pages.status.draft',
				'manager.pages.status.published',
				'manager.pages.status.hidden',
			'manager.pages.status.menu_yes',
			'manager.pages.table.column_page',
			'manager.pages.table.column_menu',
			'manager.pages.table.column_updated',
			'manager.pages.table.column_actions',
			'manager.pages.table.filter_all',
			'manager.pages.table.filter_published',
			'manager.pages.table.filter_draft',
			'manager.pages.table.filter_hidden',
			'manager.pages.table.filter_menu',
			'manager.pages.table.search_prompt',
			'manager.pages.table.sort_label',
			'manager.pages.table.sort_recent',
			'manager.pages.table.sort_created',
			'manager.pages.table.sort_az',
			'manager.pages.table.sort_za',
			'manager.pages.table.sort_menu',
			'manager.pages.table.not_in_menu',
			'manager.pages.table.actions',
				'manager.pages.table.link_copied',
				'manager.pages.table.link_copy_error',
				'manager.pages.badges.home_selected',
				'manager.pages.badges.total',
				'manager.pages.badges.published_total',
				'manager.pages.badges.draft_total',
				'manager.pages.badges.hidden_total',
				'manager.pages.badges.home_ready',
				'manager.pages.fields.title',
			'manager.pages.fields.slug',
			'manager.pages.fields.status',
			'manager.pages.fields.is_home',
			'manager.pages.fields.show_in_menu',
			'manager.pages.fields.menu_title',
			'manager.pages.fields.menu_order',
			'manager.pages.fields.seo_title',
			'manager.pages.fields.seo_description',
				'manager.pages.sections.hero',
				'manager.pages.sections.panel_title',
				'manager.pages.sections.text',
				'manager.pages.sections.contacts',
				'manager.pages.sections.blocks_title',
				'manager.help.aria.pages.sections.panel',
				'manager.help.aria.pages.sections.blocks',
				'manager.pages.sections.current_page',
				'manager.pages.sections.choose_section',
				'manager.pages.sections.empty_title',
				'manager.pages.sections.empty_description',
				'manager.pages.sections.section_name',
				'manager.pages.sections.section_type',
				'manager.pages.sections.section_visibility',
				'manager.pages.sections.section_modal_title',
				'manager.pages.sections.section_modal_description',
				'manager.pages.sections.type_readonly',
				'manager.pages.sections.block_name',
				'manager.pages.sections.block_modal_title',
				'manager.pages.sections.block_modal_description',
				'manager.pages.sections.block_text',
				'manager.pages.sections.block_contacts',
				'manager.pages.sections.description_field',
				'manager.pages.sections.empty_blocks_title',
				'manager.pages.sections.empty_blocks_description',
				'manager.pages.sections.delete_section_title',
				'manager.pages.sections.delete_section_title_named',
				'manager.pages.sections.delete_section_line_two',
				'manager.pages.sections.delete_section_line_two_named',
				'manager.pages.sections.delete_section_blocks_warning',
				'manager.pages.sections.delete_block_title',
				'manager.pages.sections.delete_block_line_two',
				'manager.pages.blocks.add_modal.title',
				'manager.pages.blocks.add_modal.description',
				'manager.pages.blocks.add_button',
				'manager.pages.blocks.empty.title',
				'manager.pages.blocks.empty.description',
				'manager.pages.blocks.settings.title',
				'manager.pages.blocks.settings.description',
				'manager.pages.blocks.delete.title',
				'manager.pages.blocks.delete.body',
				'manager.pages.blocks.type_label',
				'manager.pages.blocks.name_label',
				'manager.pages.blocks.saved_notice',
				'manager.pages.blocks.added_notice',
				'manager.pages.blocks.deleted_notice',
				'manager.pages.blocks.unavailable_type',
				'manager.pages.blocks.field.kicker',
				'manager.pages.blocks.field.heading',
				'manager.pages.blocks.field.description',
				'manager.pages.blocks.field.text',
				'manager.pages.blocks.field.button_label',
				'manager.pages.blocks.field.button_url',
				'manager.pages.blocks.field.image_url',
				'manager.pages.blocks.field.image_alt',
				'manager.pages.blocks.field.caption',
				'manager.pages.blocks.field.video_url',
				'manager.pages.blocks.field.icon_key',
				'manager.pages.blocks.field.list_text',
				'manager.pages.blocks.field.value',
				'manager.pages.blocks.field.metric_label',
				'manager.pages.blocks.field.step_number',
				'manager.pages.blocks.field.quote',
				'manager.pages.blocks.field.author',
				'manager.pages.blocks.field.role',
				'manager.pages.blocks.field.name_person',
				'manager.pages.blocks.field.link_url',
				'manager.pages.blocks.field.email',
				'manager.pages.blocks.field.phone',
				'manager.pages.blocks.field.address',
				'manager.pages.blocks.field.map_url',
				'manager.pages.blocks.field.question',
				'manager.pages.blocks.field.answer',
				'manager.pages.blocks.field.link_label',
				'manager.pages.blocks.field.file_url',
				'manager.pages.blocks.field.file_label',
				'manager.pages.blocks.field.platform',
				'manager.pages.blocks.field.embed_code',
				'manager.pages.blocks.field.tag_label',
				'manager.pages.widgets.action',
				'manager.pages.widgets.context_title',
				'manager.pages.widgets.panel_title',
				'manager.pages.widgets.panel_tooltip',
				'manager.help.aria.pages.widgets.panel',
				'manager.pages.widgets.add_button',
				'manager.pages.widgets.add_modal.title',
				'manager.pages.widgets.add_modal.description',
				'manager.pages.widgets.empty.title',
				'manager.pages.widgets.empty.description',
				'manager.pages.widgets.settings.title',
				'manager.pages.widgets.settings.description',
				'manager.pages.widgets.delete.title',
				'manager.pages.widgets.delete.body',
				'manager.pages.widgets.drag_label',
				'manager.pages.widgets.type_label',
				'manager.pages.widgets.name_label',
				'manager.pages.widgets.enabled_label',
				'manager.pages.widgets.status_enabled',
				'manager.pages.widgets.status_disabled',
				'manager.pages.widgets.position_label',
				'manager.pages.widgets.position_bottom_right',
				'manager.pages.widgets.position_bottom_left',
				'manager.pages.widgets.position_top_right',
				'manager.pages.widgets.position_top_left',
				'manager.pages.widgets.label_label',
				'manager.pages.widgets.url_label',
				'manager.pages.widgets.phone_label',
				'manager.pages.widgets.email_label',
				'manager.pages.widgets.message_label',
				'manager.pages.widgets.platform_label',
				'manager.pages.widgets.address_label',
				'manager.pages.widgets.description_label',
				'manager.pages.widgets.embed_code_label',
				'manager.pages.widgets.added_notice',
				'manager.pages.widgets.saved_notice',
				'manager.pages.widgets.deleted_notice',
				'manager.pages.widgets.unavailable_type',
				'manager.pages.popups.action',
				'manager.pages.popups.context_title',
				'manager.pages.popups.panel_title',
				'manager.pages.popups.panel_tooltip',
				'manager.help.aria.pages.popups.panel',
				'manager.pages.popups.add_button',
				'manager.pages.popups.add_modal.title',
				'manager.pages.popups.add_modal.description',
				'manager.pages.popups.empty.title',
				'manager.pages.popups.empty.description',
				'manager.pages.popups.settings.title',
				'manager.pages.popups.settings.description',
				'manager.pages.popups.delete.title',
				'manager.pages.popups.delete.body',
				'manager.pages.popups.drag_label',
				'manager.pages.popups.type_label',
				'manager.pages.popups.name_label',
				'manager.pages.popups.enabled_label',
				'manager.pages.popups.status_enabled',
				'manager.pages.popups.status_disabled',
				'manager.pages.popups.trigger_label',
				'manager.pages.popups.trigger_manual',
				'manager.pages.popups.trigger_page_load',
				'manager.pages.popups.trigger_delay',
				'manager.pages.popups.trigger_scroll',
				'manager.pages.popups.trigger_exit_intent',
				'manager.pages.popups.trigger_first_visit',
				'manager.pages.popups.heading_label',
				'manager.pages.popups.text_label',
				'manager.pages.popups.button_label_label',
				'manager.pages.popups.button_url_label',
				'manager.pages.popups.email_label',
				'manager.pages.popups.phone_label',
				'manager.pages.popups.address_label',
				'manager.pages.popups.video_url_label',
				'manager.pages.popups.embed_code_label',
				'manager.pages.popups.description_label',
				'manager.pages.popups.added_notice',
				'manager.pages.popups.saved_notice',
				'manager.pages.popups.deleted_notice',
				'manager.pages.popups.unavailable_type',
				'manager.pages.sections.add_modal_title',
				'manager.pages.sections.add_modal_description',
				'manager.pages.sections.library_unavailable',
				'manager.pages.sections.module_unavailable',
				'manager.pages.section_types.hero',
				'manager.pages.section_types.text',
				'manager.pages.section_types.contacts',
				'manager.pages.block_types.hero',
				'manager.pages.block_types.text',
				'manager.pages.block_types.contacts',
				'manager.pages.sections.type',
				'manager.pages.sections.kicker',
				'manager.pages.sections.title',
			'manager.pages.sections.text_field',
			'manager.pages.sections.button_label',
			'manager.pages.sections.button_url',
			'manager.pages.sections.email',
				'manager.pages.sections.phone',
				'manager.pages.sections.address',
			'manager.page_elements.categories.content',
			'manager.page_elements.categories.marketing',
			'manager.page_elements.categories.portfolio',
			'manager.page_elements.categories.trust',
			'manager.page_elements.categories.media',
			'manager.page_elements.categories.contact',
			'manager.page_elements.categories.layout',
			'manager.page_elements.categories.advanced',
			'manager.pages.library.sections.hero.label',
			'manager.pages.library.sections.hero.description',
			'manager.pages.library.sections.text.label',
			'manager.pages.library.sections.text.description',
			'manager.pages.library.sections.media_text.label',
			'manager.pages.library.sections.media_text.description',
			'manager.pages.library.sections.benefits.label',
			'manager.pages.library.sections.benefits.description',
			'manager.pages.library.sections.services.label',
			'manager.pages.library.sections.services.description',
			'manager.pages.library.sections.cards.label',
			'manager.pages.library.sections.cards.description',
			'manager.pages.library.sections.metrics.label',
			'manager.pages.library.sections.metrics.description',
			'manager.pages.library.sections.steps.label',
			'manager.pages.library.sections.steps.description',
			'manager.pages.library.sections.pricing.label',
			'manager.pages.library.sections.pricing.description',
			'manager.pages.library.sections.cases.label',
			'manager.pages.library.sections.cases.description',
			'manager.pages.library.sections.reviews.label',
			'manager.pages.library.sections.reviews.description',
			'manager.pages.library.sections.team.label',
			'manager.pages.library.sections.team.description',
			'manager.pages.library.sections.partners.label',
			'manager.pages.library.sections.partners.description',
			'manager.pages.library.sections.gallery.label',
			'manager.pages.library.sections.gallery.description',
			'manager.pages.library.sections.video.label',
			'manager.pages.library.sections.video.description',
			'manager.pages.library.sections.faq.label',
			'manager.pages.library.sections.faq.description',
			'manager.pages.library.sections.contacts.label',
			'manager.pages.library.sections.contacts.description',
			'manager.pages.library.sections.map.label',
			'manager.pages.library.sections.map.description',
			'manager.pages.library.sections.form.label',
			'manager.pages.library.sections.form.description',
			'manager.pages.library.sections.cta.label',
			'manager.pages.library.sections.cta.description',
			'manager.pages.library.sections.documents_links.label',
			'manager.pages.library.sections.documents_links.description',
			'manager.pages.library.sections.divider.label',
			'manager.pages.library.sections.divider.description',
			'manager.pages.library.sections.banner.label',
			'manager.pages.library.sections.banner.description',
			'manager.pages.library.sections.comparison.label',
			'manager.pages.library.sections.comparison.description',
			'manager.pages.library.sections.schedule.label',
			'manager.pages.library.sections.schedule.description',
			'manager.pages.library.sections.showcase.label',
			'manager.pages.library.sections.showcase.description',
			'manager.pages.library.sections.social_links.label',
			'manager.pages.library.sections.social_links.description',
			'manager.pages.library.sections.embed.label',
			'manager.pages.library.sections.embed.description',
			'manager.pages.defaults.hero.block_name',
			'manager.pages.defaults.hero.text',
			'manager.pages.defaults.text.block_name',
			'manager.pages.defaults.contacts.block_name',
			'manager.pages.defaults.contacts.description',
			'manager.pages.success.saved',
			'manager.pages.success.section_added',
			'manager.pages.errors.load_failed',
			'manager.pages.errors.save_failed',
				'manager.pages.errors.title_required',
				'manager.pages.errors.slug_required',
				'manager.pages.errors.confirm_remove_section',
				'manager.pages.errors.page_not_openable',
				'manager.pages.success.created',
				'manager.pages.success.duplicated',
				'manager.pages.success.published',
				'manager.pages.success.hidden',
				'manager.pages.success.home_updated',
				'manager.pages.pending.saving',
				'manager.pages.pending.creating',
				'manager.pages.pending.publishing',
				'manager.pages.pending.hiding',
				'manager.pages.pending.home',
				'manager.pages.pending.duplicating',
				'manager.pages.history.title',
				'manager.pages.history.description',
				'manager.pages.history.empty_title',
				'manager.pages.history.empty_description',
				'manager.pages.history.event.created',
				'manager.pages.history.event.updated',
				'manager.pages.history.event.published',
				'manager.pages.history.event.hidden',
				'manager.pages.history.event.home',
				'manager.pages.history.event.duplicated',
				'manager.pages.history.event.slug_changed',
				'manager.pages.duplicate.suffix',
				'manager.pages.preview.empty_sections',
				'manager.pages.modal.page_title',
			'manager.pages.modal.page_description',
			'manager.pages.modal.delete_page_title',
			'manager.pages.modal.delete_line_one',
			'manager.pages.modal.delete_page_line_two',
			'manager.pages.modal.close',
			'manager.notice_close_label',
		);
		$texts = array();

		foreach ( $keys as $key ) {
			$texts[ $key ] = self::text( $key );
		}

		if ( class_exists( 'Sonyra_Site_Manager_Page_Elements_Library' ) ) {
			foreach ( Sonyra_Site_Manager_Page_Elements_Library::get_block_definitions() as $definition ) {
				if ( ! empty( $definition['label_key'] ) ) {
					$texts[ (string) $definition['label_key'] ] = self::text( (string) $definition['label_key'] );
				}

				if ( ! empty( $definition['description_key'] ) ) {
					$texts[ (string) $definition['description_key'] ] = self::text( (string) $definition['description_key'] );
				}
			}

			foreach ( Sonyra_Site_Manager_Page_Elements_Library::get_widget_definitions() as $definition ) {
				if ( ! empty( $definition['label_key'] ) ) {
					$texts[ (string) $definition['label_key'] ] = self::text( (string) $definition['label_key'] );
				}

				if ( ! empty( $definition['description_key'] ) ) {
					$texts[ (string) $definition['description_key'] ] = self::text( (string) $definition['description_key'] );
				}
			}

			foreach ( Sonyra_Site_Manager_Page_Elements_Library::get_popup_definitions() as $definition ) {
				if ( ! empty( $definition['label_key'] ) ) {
					$texts[ (string) $definition['label_key'] ] = self::text( (string) $definition['label_key'] );
				}

				if ( ! empty( $definition['description_key'] ) ) {
					$texts[ (string) $definition['description_key'] ] = self::text( (string) $definition['description_key'] );
				}
			}
		}

		return $texts;
	}

	public static function get_summary(): array {
		if ( class_exists( 'Sonyra_Site_Manager_Pages_Store' ) ) {
			return Sonyra_Site_Manager_Pages_Store::get_summary();
		}

		return array(
			'total'     => 0,
			'published' => 0,
			'draft'     => 0,
			'hidden'    => 0,
			'has_home'  => false,
		);
	}

	private static function render_icon( string $name, string $class_name = 'sonyra-manager-icon' ): void {
		if ( class_exists( 'Sonyra_Site_Manager_Icon_Renderer' ) ) {
			Sonyra_Site_Manager_Icon_Renderer::display(
				$name,
				array(
					'class'       => 'sonyra-icon ' . $class_name,
					'aria_hidden' => true,
				)
			);
		}
	}

	private static function text( string $key ): string {
		if ( class_exists( 'Sonyra_Site_Manager_I18n' ) ) {
			return Sonyra_Site_Manager_I18n::t( $key );
		}

		return '';
	}

	private static function get_section_library_payload(): array {
		if ( class_exists( 'Sonyra_Site_Manager_Page_Elements_Library' ) ) {
			return Sonyra_Site_Manager_Page_Elements_Library::get_section_definitions();
		}

		return array();
	}

	private static function get_block_library_payload(): array {
		if ( class_exists( 'Sonyra_Site_Manager_Page_Elements_Library' ) ) {
			return Sonyra_Site_Manager_Page_Elements_Library::get_block_definitions();
		}

		return array();
	}

	private static function get_widget_library_payload(): array {
		if ( class_exists( 'Sonyra_Site_Manager_Page_Elements_Library' ) ) {
			return Sonyra_Site_Manager_Page_Elements_Library::get_widget_definitions();
		}

		return array();
	}

	private static function get_popup_library_payload(): array {
		if ( class_exists( 'Sonyra_Site_Manager_Page_Elements_Library' ) ) {
			return Sonyra_Site_Manager_Page_Elements_Library::get_popup_definitions();
		}

		return array();
	}
}
