<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Sonyra_Site_Manager_Manager_Renderer {

	private static function text( string $key ): string {
		if ( class_exists( 'Sonyra_Site_Manager_I18n' ) ) {
			return Sonyra_Site_Manager_I18n::t( $key );
		}

		return '';
	}

	private static function versioned_asset_url( string $url, string $path, string $query_arg = 'ver' ): string {
		if ( ! file_exists( $path ) ) {
			return '';
		}

		$asset_mtime = filemtime( $path );
		$asset_version = SONYRA_SITE_MANAGER_VERSION;

		if ( is_int( $asset_mtime ) ) {
			$asset_version .= '-' . $asset_mtime;
		}

		if ( function_exists( 'add_query_arg' ) ) {
			return add_query_arg( $query_arg, $asset_version, $url );
		}

		return $url . '?' . rawurlencode( $query_arg ) . '=' . rawurlencode( $asset_version );
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

	private static function get_sections(): array {
		return array(
			'dashboard' => array(
				'route'       => 'dashboard',
				'nav_label'   => 'manager.nav_dashboard',
				'title'       => 'manager.section_dashboard_title',
				'kicker'      => 'manager.section_dashboard_kicker',
				'description' => 'manager.section_dashboard_description',
				'icon_key'    => 'layout-dashboard',
				'badges'      => array(),
				'enabled'     => true,
			),
			'setup'     => array(
				'route'       => 'setup',
				'nav_label'   => 'manager.nav_site',
				'title'       => 'manager.section_setup_title',
				'kicker'      => 'manager.section_setup_kicker',
				'description' => 'manager.section_setup_description',
				'icon_key'    => 'app-window',
				'badges'      => array(),
				'enabled'     => true,
			),
			'pages'     => array(
				'route'       => 'pages',
				'nav_label'   => 'manager.nav_pages',
				'title'       => 'manager.section_pages_title',
				'kicker'      => 'manager.section_pages_kicker',
				'description' => 'manager.section_pages_description',
				'icon_key'    => 'file-text',
				'badges'      => array(),
				'enabled'     => true,
			),
			'design'    => array(
				'route'       => 'design',
				'nav_label'   => 'manager.nav_design',
				'title'       => 'manager.section_design_title',
				'kicker'      => 'manager.section_design_kicker',
				'description' => 'manager.section_design_description',
				'icon_key'    => 'brush',
				'badges'      => array(),
				'enabled'     => true,
			),
			'modules'   => array(
				'route'       => 'modules',
				'nav_label'   => 'manager.nav_modules',
				'title'       => 'manager.section_modules_title',
				'kicker'      => 'manager.section_modules_kicker',
				'description' => 'manager.section_modules_description',
				'icon_key'    => 'components',
				'badges'      => array(),
				'enabled'     => true,
			),
			'security'  => array(
				'route'       => 'security',
				'nav_label'   => 'manager.nav_security',
				'title'       => 'manager.section_security_title',
				'kicker'      => 'manager.section_security_kicker',
				'description' => 'manager.section_security_description',
				'icon_key'    => 'shield-check',
				'badges'      => array(),
				'enabled'     => true,
			),
			'settings'  => array(
				'route'       => 'settings',
				'nav_label'   => 'manager.nav_settings',
				'title'       => 'manager.section_settings_title',
				'kicker'      => 'manager.section_settings_kicker',
				'description' => 'manager.section_settings_description',
				'icon_key'    => 'settings',
				'badges'      => array(),
				'enabled'     => true,
			),
		);
	}

	private static function get_nav_items(): array {
		return array_values( self::get_sections() );
	}

	private static function get_views(): array {
		return self::get_sections();
	}

	private static function render_page_badges( array $badges, string $attr_name = '' ): void {
		$badges = array_slice( $badges, 0, 4 );
		$hidden = empty( $badges ) ? ' hidden' : '';
		?>
		<div class="sonyra-manager-page-badges"<?php echo '' !== $attr_name ? ' ' . esc_attr( $attr_name ) : ''; ?><?php echo $hidden; ?>>
			<?php foreach ( $badges as $badge ) : ?>
				<?php
				$badge_icon = isset( $badge['icon_key'] ) ? (string) $badge['icon_key'] : '';
				$badge_tone = isset( $badge['tone'] ) ? (string) $badge['tone'] : 'neutral';
				?>
				<span class="sonyra-manager-page-badge sonyra-manager-page-badge-<?php echo esc_attr( $badge_tone ); ?>">
					<?php if ( '' !== $badge_icon ) : ?>
						<?php self::render_icon( $badge_icon, 'sonyra-manager-icon sonyra-manager-page-badge-icon' ); ?>
					<?php endif; ?>
					<span><?php echo esc_html( self::text( (string) $badge['label'] ) ); ?></span>
				</span>
			<?php endforeach; ?>
		</div>
		<?php
	}

	private static function render_page_header( array $section ): void {
		$actions_hidden = 'pages' === (string) $section['route'] ? '' : ' hidden';
		?>
		<header class="sonyra-manager-topbar sonyra-manager-page-header">
			<div class="sonyra-manager-page-header-icon" data-manager-header-icon>
				<?php self::render_icon( (string) $section['icon_key'], 'sonyra-manager-icon sonyra-manager-page-header-svg' ); ?>
			</div>
			<div class="sonyra-manager-page-header-copy">
				<p class="sonyra-manager-page-kicker" data-manager-kicker><?php echo esc_html( self::text( (string) $section['kicker'] ) ); ?></p>
				<h1 class="sonyra-manager-page-title" data-manager-title><?php echo esc_html( self::text( (string) $section['title'] ) ); ?></h1>
				<p class="sonyra-manager-page-description" data-manager-description><?php echo esc_html( self::text( (string) $section['description'] ) ); ?></p>
				<?php self::render_page_badges( isset( $section['badges'] ) ? (array) $section['badges'] : array(), 'data-manager-badges' ); ?>
			</div>
			<div class="sonyra-manager-page-header-actions" data-manager-header-actions="pages"<?php echo $actions_hidden; ?>>
				<button type="button" class="sonyra-manager-pages-secondary" data-pages-open-site disabled>
					<span data-pages-button-icon="external-link"><?php self::render_icon( 'external-link', 'sonyra-manager-icon sonyra-manager-pages-button-icon' ); ?></span>
					<span><?php echo esc_html( self::text( 'manager.pages.actions.open_site' ) ); ?></span>
				</button>
				<button type="button" class="sonyra-manager-pages-primary" data-pages-create>
					<span data-pages-button-icon="plus"><?php self::render_icon( 'plus', 'sonyra-manager-icon sonyra-manager-pages-button-icon' ); ?></span>
					<span><?php echo esc_html( self::text( 'manager.pages.actions.create' ) ); ?></span>
				</button>
			</div>
		</header>
		<?php
	}

	private static function render_page_header_templates( array $sections ): void {
		?>
		<div class="sonyra-manager-header-templates" hidden>
			<?php foreach ( $sections as $section ) : ?>
				<span data-manager-icon-template="<?php echo esc_attr( $section['route'] ); ?>"><?php self::render_icon( (string) $section['icon_key'], 'sonyra-manager-icon sonyra-manager-page-header-svg' ); ?></span>
				<span data-manager-badge-template="<?php echo esc_attr( $section['route'] ); ?>"><?php self::render_page_badges( isset( $section['badges'] ) ? (array) $section['badges'] : array(), 'data-manager-badges' ); ?></span>
			<?php endforeach; ?>
		</div>
		<?php
	}

	private static function get_notice_sources(): array {
		return array( 'core', 'extension', 'repository', 'support', 'system' );
	}

	private static function get_notice_types(): array {
		$types = array( 'error', 'warning', 'info', 'promo', 'success' );

		if ( function_exists( 'apply_filters' ) ) {
			$filtered_types = apply_filters( 'sonyra_site_manager_notice_types', $types );

			if ( is_array( $filtered_types ) ) {
				$types = array_values( array_intersect( array_map( 'strval', $filtered_types ), $types ) );
			}
		}

		return empty( $types ) ? array( 'error', 'warning', 'info', 'promo', 'success' ) : $types;
	}

	private static function get_notice_severities(): array {
		return array( 'critical', 'high', 'medium', 'low' );
	}

	private static function get_notice_action_types(): array {
		$action_types = array( 'route', 'reload', 'retry', 'open_modal', 'external_later', 'support_later', 'dismiss' );

		if ( function_exists( 'apply_filters' ) ) {
			$filtered_action_types = apply_filters( 'sonyra_site_manager_notice_actions', $action_types );

			if ( is_array( $filtered_action_types ) ) {
				$action_types = array_values( array_intersect( array_map( 'strval', $filtered_action_types ), $action_types ) );
			}
		}

		return empty( $action_types ) ? array( 'route', 'reload', 'retry', 'open_modal', 'external_later', 'support_later', 'dismiss' ) : $action_types;
	}

	private static function get_notice_default_icon( string $type ): string {
		$icons = array(
			'error'   => 'alert-circle',
			'warning' => 'alert-triangle',
			'info'    => 'info-circle',
			'promo'   => 'components',
			'success' => 'circle-check',
		);

		return isset( $icons[ $type ] ) ? $icons[ $type ] : 'info-circle';
	}

	private static function get_core_notices(): array {
		return array(
			array(
				'id'              => 'manager_logout_error',
				'source'          => 'core',
				'type'            => 'error',
				'severity'        => 'high',
				'title'           => 'manager.notice_logout_error_title',
				'description'     => 'manager.notice_logout_error_description',
				'icon_key'        => 'alert-circle',
				'primary_action'  => array(
					'type'  => 'reload',
					'label' => 'manager.notice_action_reload',
				),
				'secondary_action' => array(),
				'dismissible'     => false,
				'route'           => '',
				'extension_slug'  => '',
				'error_code'      => 'manager_logout_failed',
				'support_allowed' => false,
				'support_payload' => array(),
				'hidden'          => true,
			),
		);
	}

	private static function get_manager_notices(): array {
		$notices = self::get_core_notices();
		$context = array(
			'route'   => 'manager',
			'version' => SONYRA_SITE_MANAGER_VERSION,
		);

		if ( function_exists( 'apply_filters' ) ) {
			$filtered_notices = apply_filters( 'sonyra_site_manager_notices', $notices, $context );

			if ( is_array( $filtered_notices ) ) {
				$notices = $filtered_notices;
			}
		}

		$normalized_notices = array();

		foreach ( $notices as $notice ) {
			if ( ! is_array( $notice ) ) {
				continue;
			}

			$normalized_notice = self::normalize_notice( $notice );

			if ( null !== $normalized_notice ) {
				$normalized_notices[] = $normalized_notice;
			}
		}

		return self::sort_notices_by_priority( $normalized_notices );
	}

	private static function normalize_notice( array $notice ): ?array {
		$id = isset( $notice['id'] ) ? self::sanitize_notice_key( (string) $notice['id'] ) : '';
		$title = isset( $notice['title'] ) ? self::sanitize_notice_text( (string) $notice['title'] ) : '';
		$description = isset( $notice['description'] ) ? self::sanitize_notice_text( (string) $notice['description'] ) : '';

		if ( '' === $id || '' === $title || '' === $description ) {
			return null;
		}

		$type = isset( $notice['type'] ) ? (string) $notice['type'] : 'info';
		$severity = isset( $notice['severity'] ) ? (string) $notice['severity'] : 'low';
		$source = isset( $notice['source'] ) ? (string) $notice['source'] : 'system';

		if ( ! in_array( $type, self::get_notice_types(), true ) ) {
			$type = 'info';
		}

		if ( ! in_array( $severity, self::get_notice_severities(), true ) ) {
			$severity = 'low';
		}

		if ( ! in_array( $source, self::get_notice_sources(), true ) ) {
			$source = 'system';
		}

		$icon_key = isset( $notice['icon_key'] ) ? self::sanitize_icon_key( (string) $notice['icon_key'] ) : '';

		return array(
			'id'               => $id,
			'source'           => $source,
			'type'             => $type,
			'severity'         => $severity,
			'title'            => $title,
			'description'      => $description,
			'icon_key'         => '' !== $icon_key ? $icon_key : self::get_notice_default_icon( $type ),
			'primary_action'   => self::normalize_notice_action( isset( $notice['primary_action'] ) && is_array( $notice['primary_action'] ) ? $notice['primary_action'] : array() ),
			'secondary_action' => self::normalize_notice_action( isset( $notice['secondary_action'] ) && is_array( $notice['secondary_action'] ) ? $notice['secondary_action'] : array() ),
			'dismissible'      => ! empty( $notice['dismissible'] ) && in_array( $type, array( 'info', 'promo', 'success' ), true ),
			'route'            => isset( $notice['route'] ) ? self::sanitize_notice_key( (string) $notice['route'] ) : '',
			'extension_slug'   => isset( $notice['extension_slug'] ) ? self::sanitize_notice_key( (string) $notice['extension_slug'] ) : '',
			'error_code'       => isset( $notice['error_code'] ) ? self::sanitize_notice_key( (string) $notice['error_code'] ) : '',
			'created_at'       => isset( $notice['created_at'] ) ? self::sanitize_notice_text( (string) $notice['created_at'] ) : '',
			'expires_at'       => isset( $notice['expires_at'] ) ? self::sanitize_notice_text( (string) $notice['expires_at'] ) : '',
			'support_allowed'  => ! empty( $notice['support_allowed'] ),
			'support_payload'  => isset( $notice['support_payload'] ) && is_array( $notice['support_payload'] ) ? array() : array(),
			'hidden'           => ! empty( $notice['hidden'] ),
		);
	}

	private static function normalize_notice_action( array $action ): array {
		$type = isset( $action['type'] ) ? (string) $action['type'] : '';
		$label = isset( $action['label'] ) ? self::sanitize_notice_text( (string) $action['label'] ) : '';

		if ( '' === $type || '' === $label || ! in_array( $type, self::get_notice_action_types(), true ) ) {
			return array();
		}

		return array(
			'type'        => $type,
			'label'       => $label,
			'route'       => isset( $action['route'] ) ? self::sanitize_notice_key( (string) $action['route'] ) : '',
			'payload'     => array(),
			'disabled'    => ! empty( $action['disabled'] ),
			'future_only' => ! empty( $action['future_only'] ),
		);
	}

	private static function sanitize_notice_key( string $value ): string {
		$value = strtolower( $value );
		$value = preg_replace( '/[^a-z0-9_-]/', '', $value );

		return is_string( $value ) ? $value : '';
	}

	private static function sanitize_icon_key( string $value ): string {
		$value = strtolower( $value );
		$value = preg_replace( '/[^a-z0-9-]/', '', $value );

		return is_string( $value ) ? $value : '';
	}

	private static function sanitize_notice_text( string $value ): string {
		if ( function_exists( 'sanitize_text_field' ) ) {
			return sanitize_text_field( $value );
		}

		return trim( strip_tags( $value ) );
	}

	private static function sort_notices_by_priority( array $notices ): array {
		usort(
			$notices,
			static function ( array $left, array $right ): int {
				$left_priority = self::get_notice_priority( $left );
				$right_priority = self::get_notice_priority( $right );

				if ( $left_priority === $right_priority ) {
					return strcmp( (string) $left['id'], (string) $right['id'] );
				}

				return $left_priority < $right_priority ? -1 : 1;
			}
		);

		return $notices;
	}

	private static function get_notice_priority( array $notice ): int {
		$type = isset( $notice['type'] ) ? (string) $notice['type'] : 'info';
		$severity = isset( $notice['severity'] ) ? (string) $notice['severity'] : 'low';

		if ( 'error' === $type && 'critical' === $severity ) {
			return 10;
		}

		if ( 'error' === $type && 'high' === $severity ) {
			return 20;
		}

		if ( 'warning' === $type ) {
			return 30;
		}

		if ( in_array( $type, array( 'info' ), true ) && in_array( $severity, array( 'high', 'medium' ), true ) ) {
			return 40;
		}

		if ( 'info' === $type ) {
			return 50;
		}

		if ( 'promo' === $type ) {
			return 60;
		}

		if ( 'success' === $type ) {
			return 70;
		}

		return 80;
	}

	private static function render_notice_center(): void {
		$notices = self::get_manager_notices();
		$has_visible_notice = false;

		foreach ( $notices as $notice ) {
			if ( empty( $notice['hidden'] ) ) {
				$has_visible_notice = true;
				break;
			}
		}

		?>
		<section class="sonyra-manager-notice-center" data-manager-notice-center aria-label="<?php echo esc_attr( self::text( 'manager.notice_center_label' ) ); ?>"<?php echo $has_visible_notice ? '' : ' hidden'; ?>>
			<?php foreach ( $notices as $notice ) : ?>
				<?php self::render_notice( $notice ); ?>
			<?php endforeach; ?>
		</section>
		<?php
	}

	private static function render_notice( array $notice ): void {
		$type = (string) $notice['type'];
		$severity = (string) $notice['severity'];
		$hidden = ! empty( $notice['hidden'] ) ? ' hidden' : '';
		$logout_error_attr = 'manager_logout_error' === $notice['id'] ? ' data-manager-logout-error' : '';
		?>
		<article class="sonyra-manager-notice-card sonyra-manager-notice sonyra-manager-notice-<?php echo esc_attr( $type ); ?> sonyra-manager-notice-severity-<?php echo esc_attr( $severity ); ?>" data-manager-notice-id="<?php echo esc_attr( (string) $notice['id'] ); ?>" data-manager-notice-type="<?php echo esc_attr( $type ); ?>" data-manager-notice-source="<?php echo esc_attr( (string) $notice['source'] ); ?>"<?php echo $logout_error_attr; ?><?php echo $hidden; ?>>
			<div class="sonyra-manager-notice__icon"><?php self::render_icon( (string) $notice['icon_key'], 'sonyra-manager-icon sonyra-manager-notice-icon' ); ?></div>
			<div class="sonyra-manager-notice__text sonyra-manager-notice-copy">
				<strong><?php echo esc_html( self::text( (string) $notice['title'] ) ); ?></strong>
				<p><?php echo esc_html( self::text( (string) $notice['description'] ) ); ?></p>
			</div>
			<div class="sonyra-manager-notice-actions">
				<?php self::render_notice_action( $notice['primary_action'], $notice ); ?>
				<?php self::render_notice_action( $notice['secondary_action'], $notice ); ?>
				<button type="button" class="sonyra-manager-notice__close" data-manager-notice-action="dismiss" data-manager-notice-target="<?php echo esc_attr( (string) $notice['id'] ); ?>" aria-label="<?php echo esc_attr( self::text( 'manager.notice_close_label' ) ); ?>">
					<?php self::render_icon( 'x', 'sonyra-manager-icon sonyra-manager-notice-modal-close-icon' ); ?>
				</button>
			</div>
		</article>
		<?php
	}

	private static function render_notice_action( array $action, array $notice ): void {
		if ( empty( $action['type'] ) || empty( $action['label'] ) ) {
			return;
		}

		$type = (string) $action['type'];
		$disabled = ! empty( $action['disabled'] ) || ! empty( $action['future_only'] );
		$disabled_attr = $disabled ? ' disabled aria-disabled="true"' : '';
		$class_name = 'sonyra-manager-notice-action';

		if ( 'route' === $type && ! empty( $action['route'] ) ) {
			?>
			<button type="button" class="<?php echo esc_attr( $class_name ); ?>" data-manager-route="<?php echo esc_attr( (string) $action['route'] ); ?>"<?php echo $disabled_attr; ?>><?php echo esc_html( self::text( (string) $action['label'] ) ); ?></button>
			<?php
			return;
		}

		?>
		<button type="button" class="<?php echo esc_attr( $class_name ); ?>" data-manager-notice-action="<?php echo esc_attr( $type ); ?>" data-manager-notice-target="<?php echo esc_attr( (string) $notice['id'] ); ?>"<?php echo $disabled_attr; ?>><?php echo esc_html( self::text( (string) $action['label'] ) ); ?></button>
		<?php
	}

	private static function render_notice_support_modal_foundation(): void {
		?>
		<div class="sonyra-manager-notice-modal" data-manager-notice-support-modal hidden>
			<div class="sonyra-manager-notice-modal-card" role="dialog" aria-modal="true" aria-labelledby="sonyra-manager-notice-support-title">
				<button type="button" class="sonyra-manager-notice-modal-close" data-manager-notice-modal-close aria-label="<?php echo esc_attr( self::text( 'manager.notice_action_close' ) ); ?>">
					<?php self::render_icon( 'x', 'sonyra-manager-icon sonyra-manager-notice-modal-close-icon' ); ?>
				</button>
				<div class="sonyra-manager-notice-icon-tile"><?php self::render_icon( 'message-circle', 'sonyra-manager-icon sonyra-manager-notice-icon' ); ?></div>
				<h2 id="sonyra-manager-notice-support-title"><?php echo esc_html( self::text( 'manager.notice_support_title' ) ); ?></h2>
				<p><?php echo esc_html( self::text( 'manager.notice_support_future_hint' ) ); ?></p>
				<label class="sonyra-manager-notice-modal-field">
					<span><?php echo esc_html( self::text( 'manager.notice_support_comment_label' ) ); ?></span>
					<textarea disabled rows="4"></textarea>
				</label>
				<button type="button" class="sonyra-manager-notice-action" data-manager-notice-modal-close><?php echo esc_html( self::text( 'manager.notice_action_close' ) ); ?></button>
			</div>
		</div>
		<?php
	}

	private static function get_dashboard_state(): array {
		$state = array(
			'mode'       => 'setup',
			'completion' => false,
		);

		if ( function_exists( 'apply_filters' ) ) {
			$filtered_state = apply_filters(
				'sonyra_site_manager_dashboard_mode',
				$state,
				array(
					'route' => 'dashboard',
				)
			);

			if ( is_array( $filtered_state ) ) {
				$state = array_merge( $state, $filtered_state );
			}
		}

		$state['mode'] = 'working' === (string) $state['mode'] ? 'working' : 'setup';
		$state['completion'] = true === $state['completion'];

		if ( function_exists( 'apply_filters' ) ) {
			$state['completion'] = true === apply_filters(
				'sonyra_site_manager_dashboard_completion',
				$state['completion'],
				array(
					'mode' => $state['mode'],
				)
			);
		}

		return $state;
	}

	private static function get_dashboard_setup_steps(): array {
		$steps = array(
			array( 'key' => 'site', 'title' => 'manager.dashboard_step_site_title', 'description' => 'manager.dashboard_step_site_description', 'section_route' => 'setup', 'icon_key' => 'app-window', 'status' => 'needs_setup', 'required' => true, 'action_label' => 'manager.dashboard_action_open_section', 'action_route' => 'setup' ),
			array( 'key' => 'pages', 'title' => 'manager.dashboard_step_pages_title', 'description' => 'manager.dashboard_step_pages_description', 'section_route' => 'pages', 'icon_key' => 'file-text', 'status' => 'needs_setup', 'required' => true, 'action_label' => 'manager.dashboard_action_open_section', 'action_route' => 'pages' ),
			array( 'key' => 'design', 'title' => 'manager.dashboard_step_design_title', 'description' => 'manager.dashboard_step_design_description', 'section_route' => 'design', 'icon_key' => 'brush', 'status' => 'needs_setup', 'required' => true, 'action_label' => 'manager.dashboard_action_open_section', 'action_route' => 'design' ),
			array( 'key' => 'documents', 'title' => 'manager.dashboard_step_documents_title', 'description' => 'manager.dashboard_step_documents_description', 'section_route' => 'setup', 'icon_key' => 'file-check', 'status' => 'needs_setup', 'required' => true, 'action_label' => 'manager.dashboard_action_open_section', 'action_route' => 'setup' ),
			array( 'key' => 'cookie', 'title' => 'manager.dashboard_step_cookie_title', 'description' => 'manager.dashboard_step_cookie_description', 'section_route' => 'setup', 'icon_key' => 'cookie', 'status' => 'needs_setup', 'required' => true, 'action_label' => 'manager.dashboard_action_open_section', 'action_route' => 'setup' ),
			array( 'key' => 'form', 'title' => 'manager.dashboard_step_form_title', 'description' => 'manager.dashboard_step_form_description', 'section_route' => 'pages', 'icon_key' => 'forms', 'status' => 'not_checked', 'required' => false, 'action_label' => 'manager.dashboard_action_open_section', 'action_route' => 'pages' ),
			array( 'key' => 'security', 'title' => 'manager.dashboard_step_security_title', 'description' => 'manager.dashboard_step_security_description', 'section_route' => 'security', 'icon_key' => 'shield-check', 'status' => 'not_checked', 'required' => true, 'action_label' => 'manager.dashboard_action_open_section', 'action_route' => 'security' ),
			array( 'key' => 'publication', 'title' => 'manager.dashboard_step_publication_title', 'description' => 'manager.dashboard_step_publication_description', 'section_route' => 'setup', 'icon_key' => 'world-www', 'status' => 'not_checked', 'required' => true, 'action_label' => 'manager.dashboard_action_open_section', 'action_route' => 'setup' ),
		);

		return self::filter_dashboard_items( 'sonyra_site_manager_dashboard_setup_steps', $steps );
	}

	private static function get_dashboard_status_cards(): array {
		$cards = array(
			array( 'key' => 'site', 'title' => 'manager.dashboard_card_site_title', 'description' => 'manager.dashboard_card_site_description', 'icon_key' => 'app-window', 'status' => 'needs_setup' ),
			array( 'key' => 'documents_cookie', 'title' => 'manager.dashboard_card_documents_cookie_title', 'description' => 'manager.dashboard_card_documents_cookie_description', 'icon_key' => 'file-check', 'status' => 'needs_setup' ),
			array( 'key' => 'forms', 'title' => 'manager.dashboard_card_forms_title', 'description' => 'manager.dashboard_card_forms_description', 'icon_key' => 'forms', 'status' => 'not_checked' ),
			array( 'key' => 'security', 'title' => 'manager.dashboard_card_security_title', 'description' => 'manager.dashboard_card_security_description', 'icon_key' => 'shield-check', 'status' => 'not_checked' ),
			array( 'key' => 'modules', 'title' => 'manager.dashboard_card_modules_title', 'description' => 'manager.dashboard_card_modules_description', 'icon_key' => 'components', 'status' => 'optional' ),
			array( 'key' => 'updates_connection', 'title' => 'manager.dashboard_card_updates_title', 'description' => 'manager.dashboard_card_updates_description', 'icon_key' => 'cloud-check', 'status' => 'not_checked' ),
		);

		return self::filter_dashboard_items( 'sonyra_site_manager_dashboard_status_cards', $cards );
	}

	private static function get_dashboard_activity_items(): array {
		$items = array();

		return self::filter_dashboard_items( 'sonyra_site_manager_dashboard_activity', $items );
	}

	private static function get_dashboard_extension_slots(): array {
		return array(
			'alerts'  => self::filter_dashboard_items( 'sonyra_site_manager_dashboard_alerts', array() ),
			'metrics' => self::filter_dashboard_items( 'sonyra_site_manager_dashboard_metrics', array() ),
		);
	}

	private static function filter_dashboard_items( string $filter_name, array $items ): array {
		if ( function_exists( 'apply_filters' ) ) {
			$filtered_items = apply_filters(
				$filter_name,
				$items,
				array(
					'route' => 'dashboard',
				)
			);

			if ( is_array( $filtered_items ) ) {
				$items = $filtered_items;
			}
		}

		return array_values( array_filter( $items, 'is_array' ) );
	}

	private static function get_dashboard_status_label_key( string $status ): string {
		$status_map = array(
			'not_checked' => 'manager.dashboard_status_not_checked',
			'needs_setup' => 'manager.dashboard_status_needs_setup',
			'ready'       => 'manager.dashboard_status_ready',
			'optional'    => 'manager.dashboard_status_optional',
		);

		return isset( $status_map[ $status ] ) ? $status_map[ $status ] : $status_map['not_checked'];
	}

	private static function render_dashboard_view(): void {
		$state = self::get_dashboard_state();
		$setup_steps = self::get_dashboard_setup_steps();
		$status_cards = self::get_dashboard_status_cards();
		$activity_items = self::get_dashboard_activity_items();
		$extension_slots = self::get_dashboard_extension_slots();
		?>
		<section class="sonyra-manager-view sonyra-manager-view-dashboard" data-manager-view="dashboard">
			<div class="sonyra-manager-dashboard-foundation">
				<?php self::render_dashboard_setup_panel( $setup_steps, $state ); ?>
				<?php self::render_dashboard_working_panel( $status_cards, $activity_items, $extension_slots ); ?>
			</div>
			<?php self::render_dashboard_completion_modal( $state ); ?>
		</section>
		<?php
	}

	private static function render_dashboard_setup_panel( array $steps, array $state ): void {
		$required_steps = array_filter(
			$steps,
			static function ( array $step ): bool {
				return ! empty( $step['required'] );
			}
		);
		?>
		<section class="sonyra-manager-dashboard-panel sonyra-manager-dashboard-setup-panel" data-dashboard-mode="<?php echo esc_attr( (string) $state['mode'] ); ?>">
			<div class="sonyra-manager-dashboard-panel-head">
				<div>
					<p class="sonyra-manager-kicker"><?php echo esc_html( self::text( 'manager.dashboard_setup_kicker' ) ); ?></p>
					<h2><?php echo esc_html( self::text( 'manager.dashboard_setup_title' ) ); ?></h2>
					<p><?php echo esc_html( self::text( 'manager.dashboard_setup_description' ) ); ?></p>
				</div>
				<span class="sonyra-manager-dashboard-status-badge sonyra-manager-dashboard-status-badge-needs_setup"><?php echo esc_html( self::text( 'manager.dashboard_setup_not_complete' ) ); ?></span>
			</div>
			<div class="sonyra-manager-dashboard-summary-grid">
				<div class="sonyra-manager-dashboard-summary-card">
					<span><?php echo esc_html( self::text( 'manager.dashboard_required_steps_label' ) ); ?></span>
					<strong><?php echo esc_html( count( $required_steps ) ); ?></strong>
					<small><?php echo esc_html( self::text( 'manager.dashboard_no_auto_ready' ) ); ?></small>
				</div>
				<div class="sonyra-manager-dashboard-summary-card">
					<span><?php echo esc_html( self::text( 'manager.dashboard_next_step_label' ) ); ?></span>
					<strong><?php echo esc_html( self::text( 'manager.dashboard_next_step_value' ) ); ?></strong>
					<small><?php echo esc_html( self::text( 'manager.dashboard_next_step_hint' ) ); ?></small>
				</div>
			</div>
			<div class="sonyra-manager-dashboard-steps">
				<?php foreach ( $steps as $step ) : ?>
					<?php self::render_dashboard_setup_step( $step ); ?>
				<?php endforeach; ?>
			</div>
		</section>
		<?php
	}

	private static function render_dashboard_working_panel( array $cards, array $activity_items, array $extension_slots ): void {
		?>
		<section class="sonyra-manager-dashboard-panel sonyra-manager-dashboard-working-panel">
			<div class="sonyra-manager-dashboard-panel-head">
				<div>
					<p class="sonyra-manager-kicker"><?php echo esc_html( self::text( 'manager.dashboard_working_kicker' ) ); ?></p>
					<h2><?php echo esc_html( self::text( 'manager.dashboard_working_title' ) ); ?></h2>
					<p><?php echo esc_html( self::text( 'manager.dashboard_working_description' ) ); ?></p>
				</div>
				<span class="sonyra-manager-dashboard-status-badge sonyra-manager-dashboard-status-badge-not_checked"><?php echo esc_html( self::text( 'manager.dashboard_data_pending_label' ) ); ?></span>
			</div>
			<div class="sonyra-manager-dashboard-card-grid">
				<?php foreach ( $cards as $card ) : ?>
					<?php self::render_dashboard_card( $card ); ?>
				<?php endforeach; ?>
			</div>
			<div class="sonyra-manager-dashboard-secondary-grid">
				<div class="sonyra-manager-dashboard-placeholder-card">
					<?php self::render_icon( 'alert-circle', 'sonyra-manager-icon sonyra-manager-card-icon' ); ?>
					<h3><?php echo esc_html( self::text( 'manager.dashboard_activity_title' ) ); ?></h3>
					<p><?php echo esc_html( empty( $activity_items ) ? self::text( 'manager.dashboard_activity_empty' ) : self::text( 'manager.dashboard_extension_data_ready' ) ); ?></p>
				</div>
				<div class="sonyra-manager-dashboard-placeholder-card" <?php echo empty( $extension_slots['alerts'] ) && empty( $extension_slots['metrics'] ) ? 'hidden' : ''; ?>>
					<?php self::render_icon( 'components', 'sonyra-manager-icon sonyra-manager-card-icon' ); ?>
					<h3><?php echo esc_html( self::text( 'manager.dashboard_extension_slots_title' ) ); ?></h3>
					<p><?php echo esc_html( self::text( 'manager.dashboard_extension_slots_description' ) ); ?></p>
				</div>
			</div>
		</section>
		<?php
	}

	private static function render_dashboard_card( array $card ): void {
		if ( empty( $card['title'] ) || empty( $card['description'] ) ) {
			return;
		}

		$icon_key = isset( $card['icon_key'] ) ? (string) $card['icon_key'] : 'info-circle';
		$status = isset( $card['status'] ) ? (string) $card['status'] : 'not_checked';
		?>
		<article class="sonyra-manager-dashboard-card">
			<div class="sonyra-manager-dashboard-card-icon"><?php self::render_icon( $icon_key, 'sonyra-manager-icon sonyra-manager-card-icon' ); ?></div>
			<div>
				<h3><?php echo esc_html( self::text( (string) $card['title'] ) ); ?></h3>
				<p><?php echo esc_html( self::text( (string) $card['description'] ) ); ?></p>
				<span class="sonyra-manager-dashboard-status-badge sonyra-manager-dashboard-status-badge-<?php echo esc_attr( $status ); ?>"><?php echo esc_html( self::text( self::get_dashboard_status_label_key( $status ) ) ); ?></span>
			</div>
		</article>
		<?php
	}

	private static function render_dashboard_setup_step( array $step ): void {
		if ( empty( $step['title'] ) || empty( $step['description'] ) || empty( $step['icon_key'] ) ) {
			return;
		}

		$status = isset( $step['status'] ) ? (string) $step['status'] : 'not_checked';
		$action_route = isset( $step['action_route'] ) ? (string) $step['action_route'] : 'dashboard';
		$action_label = isset( $step['action_label'] ) ? (string) $step['action_label'] : 'manager.dashboard_action_open_section';
		?>
		<article class="sonyra-manager-dashboard-step">
			<div class="sonyra-manager-dashboard-step-icon"><?php self::render_icon( (string) $step['icon_key'], 'sonyra-manager-icon sonyra-manager-card-icon' ); ?></div>
			<div class="sonyra-manager-dashboard-step-copy">
				<strong><?php echo esc_html( self::text( (string) $step['title'] ) ); ?></strong>
				<p><?php echo esc_html( self::text( (string) $step['description'] ) ); ?></p>
			</div>
			<div class="sonyra-manager-dashboard-step-actions">
				<span class="sonyra-manager-dashboard-status-badge sonyra-manager-dashboard-status-badge-<?php echo esc_attr( $status ); ?>"><?php echo esc_html( self::text( self::get_dashboard_status_label_key( $status ) ) ); ?></span>
				<button type="button" class="sonyra-manager-dashboard-link" data-manager-route="<?php echo esc_attr( $action_route ); ?>"><?php echo esc_html( self::text( $action_label ) ); ?></button>
			</div>
		</article>
		<?php
	}

	private static function render_dashboard_completion_modal( array $state ): void {
		$hidden = ' hidden';
		?>
		<div class="sonyra-manager-dashboard-completion-modal" data-manager-dashboard-completion-modal<?php echo $hidden; ?>>
			<div class="sonyra-manager-dashboard-completion-card" role="dialog" aria-modal="true" aria-labelledby="sonyra-manager-dashboard-completion-title">
				<?php self::render_icon( 'circle-check', 'sonyra-manager-icon sonyra-manager-card-icon' ); ?>
				<h2 id="sonyra-manager-dashboard-completion-title"><?php echo esc_html( self::text( 'manager.dashboard_completion_title' ) ); ?></h2>
				<p><?php echo esc_html( self::text( 'manager.dashboard_completion_description' ) ); ?></p>
				<button type="button" class="sonyra-manager-dashboard-link"><?php echo esc_html( self::text( 'manager.dashboard_completion_button' ) ); ?></button>
			</div>
		</div>
		<?php
	}

	private static function get_site_status_items(): array {
		return array(
			array( 'key' => 'site_foundation', 'title' => 'manager.site_status_site_foundation_title', 'description' => 'manager.site_status_site_foundation_description', 'icon_key' => 'progress', 'status' => 'needs_setup' ),
			array( 'key' => 'publication', 'title' => 'manager.site_status_publication_title', 'description' => 'manager.site_status_publication_description', 'icon_key' => 'world-www', 'status' => 'not_checked' ),
			array( 'key' => 'documents', 'title' => 'manager.site_status_documents_title', 'description' => 'manager.site_status_documents_description', 'icon_key' => 'file-check', 'status' => 'needs_setup' ),
			array( 'key' => 'cookie', 'title' => 'manager.site_status_cookie_title', 'description' => 'manager.site_status_cookie_description', 'icon_key' => 'cookie', 'status' => 'needs_setup' ),
			array( 'key' => 'header_footer', 'title' => 'manager.site_status_header_footer_title', 'description' => 'manager.site_status_header_footer_description', 'icon_key' => 'browser', 'status' => 'needs_setup' ),
			array( 'key' => 'seo', 'title' => 'manager.site_status_seo_title', 'description' => 'manager.site_status_seo_description', 'icon_key' => 'seo', 'status' => 'not_checked' ),
		);
	}

	private static function get_site_public_data_items(): array {
		return array(
			array( 'title' => 'manager.site_public_data_name_title', 'description' => 'manager.site_public_data_name_description', 'icon_key' => 'app-window', 'status' => 'future' ),
			array( 'title' => 'manager.site_public_data_description_title', 'description' => 'manager.site_public_data_description_description', 'icon_key' => 'file-text', 'status' => 'future' ),
			array( 'title' => 'manager.site_public_data_logo_title', 'description' => 'manager.site_public_data_logo_description', 'icon_key' => 'photo', 'status' => 'future' ),
			array( 'title' => 'manager.site_public_data_icon_title', 'description' => 'manager.site_public_data_icon_description', 'icon_key' => 'device-desktop', 'status' => 'future' ),
			array( 'title' => 'manager.site_public_data_email_title', 'description' => 'manager.site_public_data_email_description', 'icon_key' => 'mail', 'status' => 'future' ),
			array( 'title' => 'manager.site_public_data_phone_title', 'description' => 'manager.site_public_data_phone_description', 'icon_key' => 'phone', 'status' => 'future' ),
			array( 'title' => 'manager.site_public_data_address_title', 'description' => 'manager.site_public_data_address_description', 'icon_key' => 'map-pin', 'status' => 'future' ),
			array( 'title' => 'manager.site_public_data_domain_title', 'description' => 'manager.site_public_data_domain_description', 'icon_key' => 'link', 'status' => 'future' ),
		);
	}

	private static function get_site_header_footer_items(): array {
		return array(
			array( 'title' => 'manager.site_header_logo_title', 'description' => 'manager.site_header_logo_description', 'icon_key' => 'photo', 'status' => 'needs_setup' ),
			array( 'title' => 'manager.site_header_menu_title', 'description' => 'manager.site_header_menu_description', 'icon_key' => 'list-check', 'status' => 'needs_setup' ),
			array( 'title' => 'manager.site_header_contacts_title', 'description' => 'manager.site_header_contacts_description', 'icon_key' => 'phone', 'status' => 'needs_setup' ),
			array( 'title' => 'manager.site_footer_title', 'description' => 'manager.site_footer_description', 'icon_key' => 'browser', 'status' => 'needs_setup' ),
			array( 'title' => 'manager.site_footer_legal_title', 'description' => 'manager.site_footer_legal_description', 'icon_key' => 'file-check', 'status' => 'needs_setup' ),
			array( 'title' => 'manager.site_footer_copyright_title', 'description' => 'manager.site_footer_copyright_description', 'icon_key' => 'files', 'status' => 'future' ),
		);
	}

	private static function get_site_document_items(): array {
		return array(
			array( 'title' => 'manager.site_document_privacy_title', 'description' => 'manager.site_document_privacy_description', 'icon_key' => 'file-check', 'status' => 'needs_setup' ),
			array( 'title' => 'manager.site_document_terms_title', 'description' => 'manager.site_document_terms_description', 'icon_key' => 'file-text', 'status' => 'needs_setup' ),
			array( 'title' => 'manager.site_document_offer_title', 'description' => 'manager.site_document_offer_description', 'icon_key' => 'files', 'status' => 'needs_setup' ),
			array( 'title' => 'manager.site_document_consent_title', 'description' => 'manager.site_document_consent_description', 'icon_key' => 'circle-check', 'status' => 'needs_setup' ),
			array( 'title' => 'manager.site_document_cookie_title', 'description' => 'manager.site_document_cookie_description', 'icon_key' => 'cookie', 'status' => 'needs_setup' ),
		);
	}

	private static function get_site_cookie_items(): array {
		return array(
			array( 'title' => 'manager.site_cookie_notice_title', 'description' => 'manager.site_cookie_notice_description', 'icon_key' => 'cookie', 'status' => 'needs_setup' ),
			array( 'title' => 'manager.site_cookie_policy_title', 'description' => 'manager.site_cookie_policy_description', 'icon_key' => 'file-check', 'status' => 'needs_setup' ),
			array( 'title' => 'manager.site_cookie_documents_title', 'description' => 'manager.site_cookie_documents_description', 'icon_key' => 'link', 'status' => 'needs_setup' ),
			array( 'title' => 'manager.site_cookie_consent_title', 'description' => 'manager.site_cookie_consent_description', 'icon_key' => 'circle-check', 'status' => 'future' ),
		);
	}

	private static function get_site_service_page_items(): array {
		return array(
			array( 'title' => 'manager.site_service_privacy_title', 'description' => 'manager.site_service_privacy_description', 'icon_key' => 'file-check', 'status' => 'needs_setup' ),
			array( 'title' => 'manager.site_service_offer_title', 'description' => 'manager.site_service_offer_description', 'icon_key' => 'files', 'status' => 'needs_setup' ),
			array( 'title' => 'manager.site_service_consent_title', 'description' => 'manager.site_service_consent_description', 'icon_key' => 'circle-check', 'status' => 'needs_setup' ),
			array( 'title' => 'manager.site_service_cookie_title', 'description' => 'manager.site_service_cookie_description', 'icon_key' => 'cookie', 'status' => 'needs_setup' ),
			array( 'title' => 'manager.site_service_preparing_title', 'description' => 'manager.site_service_preparing_description', 'icon_key' => 'progress', 'status' => 'needs_setup' ),
			array( 'title' => 'manager.site_service_error_title', 'description' => 'manager.site_service_error_description', 'icon_key' => 'alert-triangle', 'status' => 'not_checked' ),
		);
	}

	private static function get_site_seo_items(): array {
		return array(
			array( 'title' => 'manager.site_seo_title_title', 'description' => 'manager.site_seo_title_description', 'icon_key' => 'seo', 'status' => 'not_checked' ),
			array( 'title' => 'manager.site_seo_description_title', 'description' => 'manager.site_seo_description_description', 'icon_key' => 'file-text', 'status' => 'not_checked' ),
			array( 'title' => 'manager.site_seo_social_title', 'description' => 'manager.site_seo_social_description', 'icon_key' => 'photo', 'status' => 'not_checked' ),
			array( 'title' => 'manager.site_seo_icon_title', 'description' => 'manager.site_seo_icon_description', 'icon_key' => 'device-desktop', 'status' => 'not_checked' ),
			array( 'title' => 'manager.site_seo_indexing_title', 'description' => 'manager.site_seo_indexing_description', 'icon_key' => 'world-search', 'status' => 'future' ),
		);
	}

	private static function get_site_readiness_items(): array {
		return array(
			array( 'title' => 'manager.site_readiness_public_data_title', 'description' => 'manager.site_readiness_public_data_description', 'icon_key' => 'app-window', 'status' => 'needs_setup' ),
			array( 'title' => 'manager.site_readiness_header_title', 'description' => 'manager.site_readiness_header_description', 'icon_key' => 'browser', 'status' => 'needs_setup' ),
			array( 'title' => 'manager.site_readiness_footer_title', 'description' => 'manager.site_readiness_footer_description', 'icon_key' => 'browser', 'status' => 'needs_setup' ),
			array( 'title' => 'manager.site_readiness_documents_title', 'description' => 'manager.site_readiness_documents_description', 'icon_key' => 'file-check', 'status' => 'needs_setup' ),
			array( 'title' => 'manager.site_readiness_cookie_title', 'description' => 'manager.site_readiness_cookie_description', 'icon_key' => 'cookie', 'status' => 'needs_setup' ),
			array( 'title' => 'manager.site_readiness_service_pages_title', 'description' => 'manager.site_readiness_service_pages_description', 'icon_key' => 'files', 'status' => 'needs_setup' ),
			array( 'title' => 'manager.site_readiness_seo_title', 'description' => 'manager.site_readiness_seo_description', 'icon_key' => 'seo', 'status' => 'not_checked' ),
			array( 'title' => 'manager.site_readiness_forms_title', 'description' => 'manager.site_readiness_forms_description', 'icon_key' => 'list-check', 'status' => 'optional' ),
			array( 'title' => 'manager.site_readiness_security_title', 'description' => 'manager.site_readiness_security_description', 'icon_key' => 'settings', 'status' => 'not_checked' ),
		);
	}

	private static function get_site_status_label_key( string $status ): string {
		$status_map = array(
			'needs_setup' => 'manager.site_status_needs_setup',
			'not_checked' => 'manager.site_status_not_checked',
			'optional'    => 'manager.site_status_optional',
			'future'      => 'manager.site_status_future',
		);

		return isset( $status_map[ $status ] ) ? $status_map[ $status ] : $status_map['not_checked'];
	}

	private static function render_site_view(): void {
		?>
		<section class="sonyra-manager-view sonyra-manager-view-site" data-manager-view="setup" hidden>
			<div class="sonyra-manager-site-foundation">
				<?php self::render_site_status_panel(); ?>
				<div class="sonyra-manager-site-grid sonyra-manager-site-grid-two">
					<?php self::render_site_collection_panel( 'manager.site_public_data_title', 'manager.site_public_data_description', self::get_site_public_data_items() ); ?>
					<?php self::render_site_collection_panel( 'manager.site_header_footer_title', 'manager.site_header_footer_description', self::get_site_header_footer_items() ); ?>
				</div>
				<div class="sonyra-manager-site-grid sonyra-manager-site-grid-two">
					<?php self::render_site_collection_panel( 'manager.site_documents_title', 'manager.site_documents_description', self::get_site_document_items() ); ?>
					<?php self::render_site_collection_panel( 'manager.site_cookie_title', 'manager.site_cookie_description', self::get_site_cookie_items() ); ?>
				</div>
				<div class="sonyra-manager-site-grid sonyra-manager-site-grid-two">
					<?php self::render_site_collection_panel( 'manager.site_service_pages_title', 'manager.site_service_pages_description', self::get_site_service_page_items() ); ?>
					<?php self::render_site_collection_panel( 'manager.site_seo_panel_title', 'manager.site_seo_panel_description', self::get_site_seo_items() ); ?>
				</div>
				<?php self::render_site_readiness_panel(); ?>
			</div>
		</section>
		<?php
	}

	private static function render_site_status_panel(): void {
		?>
		<section class="sonyra-manager-site-panel">
			<div class="sonyra-manager-site-panel-head">
				<div>
					<p class="sonyra-manager-kicker"><?php echo esc_html( self::text( 'manager.site_status_panel_kicker' ) ); ?></p>
					<h3><?php echo esc_html( self::text( 'manager.site_status_panel_title' ) ); ?></h3>
					<p><?php echo esc_html( self::text( 'manager.site_status_panel_description' ) ); ?></p>
				</div>
				<div class="sonyra-manager-site-actions">
					<button type="button" class="sonyra-manager-site-link" data-manager-route="pages"><?php echo esc_html( self::text( 'manager.site_action_pages' ) ); ?></button>
					<button type="button" class="sonyra-manager-site-link" data-manager-route="design"><?php echo esc_html( self::text( 'manager.site_action_design' ) ); ?></button>
					<button type="button" class="sonyra-manager-site-link" data-manager-route="security"><?php echo esc_html( self::text( 'manager.site_action_security' ) ); ?></button>
				</div>
			</div>
			<div class="sonyra-manager-site-card-grid sonyra-manager-site-card-grid-status">
				<?php foreach ( self::get_site_status_items() as $item ) : ?>
					<?php self::render_site_item_card( $item ); ?>
				<?php endforeach; ?>
			</div>
		</section>
		<?php
	}

	private static function render_site_collection_panel( string $title_key, string $description_key, array $items ): void {
		?>
		<section class="sonyra-manager-site-panel">
			<div class="sonyra-manager-site-panel-head sonyra-manager-site-panel-head-compact">
				<div>
					<h3><?php echo esc_html( self::text( $title_key ) ); ?></h3>
					<p><?php echo esc_html( self::text( $description_key ) ); ?></p>
				</div>
			</div>
			<div class="sonyra-manager-site-card-grid">
				<?php foreach ( $items as $item ) : ?>
					<?php self::render_site_item_card( $item ); ?>
				<?php endforeach; ?>
			</div>
		</section>
		<?php
	}

	private static function render_site_item_card( array $item ): void {
		if ( empty( $item['title'] ) || empty( $item['description'] ) ) {
			return;
		}

		$icon_key = isset( $item['icon_key'] ) ? (string) $item['icon_key'] : 'app-window';
		$status = isset( $item['status'] ) ? (string) $item['status'] : 'not_checked';
		?>
		<article class="sonyra-manager-site-card">
			<div class="sonyra-manager-site-card-icon"><?php self::render_icon( $icon_key, 'sonyra-manager-icon sonyra-manager-card-icon' ); ?></div>
			<div class="sonyra-manager-site-card-copy">
				<strong><?php echo esc_html( self::text( (string) $item['title'] ) ); ?></strong>
				<p><?php echo esc_html( self::text( (string) $item['description'] ) ); ?></p>
			</div>
			<span class="sonyra-manager-site-badge sonyra-manager-site-badge-<?php echo esc_attr( $status ); ?>"><?php echo esc_html( self::text( self::get_site_status_label_key( $status ) ) ); ?></span>
		</article>
		<?php
	}

	private static function render_site_readiness_panel(): void {
		?>
		<section class="sonyra-manager-site-panel sonyra-manager-site-readiness-panel">
			<div class="sonyra-manager-site-panel-head">
				<div>
					<p class="sonyra-manager-kicker"><?php echo esc_html( self::text( 'manager.site_readiness_kicker' ) ); ?></p>
					<h3><?php echo esc_html( self::text( 'manager.site_readiness_title' ) ); ?></h3>
					<p><?php echo esc_html( self::text( 'manager.site_readiness_description' ) ); ?></p>
				</div>
				<span class="sonyra-manager-site-badge sonyra-manager-site-badge-not_checked"><?php echo esc_html( self::text( 'manager.site_readiness_badge' ) ); ?></span>
			</div>
			<div class="sonyra-manager-site-card-grid sonyra-manager-site-card-grid-readiness">
				<?php foreach ( self::get_site_readiness_items() as $item ) : ?>
					<?php self::render_site_item_card( $item ); ?>
				<?php endforeach; ?>
			</div>
		</section>
		<?php
	}

	private static function get_pages_i18n_payload(): array {
		$dictionary = class_exists( 'Sonyra_Site_Manager_Pages_Section' ) ? Sonyra_Site_Manager_Pages_Section::get_i18n() : array();
		$payload    = class_exists( 'Sonyra_Site_Manager_I18n' ) ? Sonyra_Site_Manager_I18n::get_payload() : array(
			'currentLocale'  => 'ru_RU',
			'fallbackLocale' => 'ru_RU',
			'dictionary'     => array(),
		);

		$payload['dictionary'] = is_array( $dictionary ) ? $dictionary : array();
		$payload['messages']   = array(
			'destructiveModalSubtitle' => isset( $payload['dictionary']['manager.pages.modal.delete_line_one'] ) ? (string) $payload['dictionary']['manager.pages.modal.delete_line_one'] : '',
		);

		return $payload;
	}

	private static function render_pages_view(): void {
		if ( class_exists( 'Sonyra_Site_Manager_Pages_Section' ) ) {
			Sonyra_Site_Manager_Pages_Section::render();
		}
	}

	private static function render_design_view(): void {
		?>
		<section class="sonyra-manager-view sonyra-manager-view-design" data-manager-view="design" hidden>
			<div class="sonyra-color-controller sonyra-pages-section" data-color-controller-root>
				<div class="sonyra-color-controller__app" data-color-controller-app></div>
			</div>
		</section>
		<?php
	}

	private static function render_settings_view(): void {
		?>
		<section class="sonyra-manager-view sonyra-manager-view-settings" data-manager-view="settings" hidden>
			<div class="sonyra-manager-dashboard-grid sonyra-manager-dashboard-grid-wide">
				<section class="sonyra-manager-card">
					<div class="sonyra-manager-card-head">
						<span class="sonyra-manager-hero-icon"><?php self::render_icon( 'help-circle', 'sonyra-manager-icon sonyra-manager-hero-svg' ); ?></span>
						<div>
							<h3><?php echo esc_html( self::text( 'manager.settings.knowledge_base.title' ) ); ?></h3>
							<p><?php echo esc_html( self::text( 'manager.settings.knowledge_base.description' ) ); ?></p>
						</div>
					</div>
					<div class="sonyra-manager-site-card-grid">
						<article class="sonyra-manager-site-card">
							<div class="sonyra-manager-site-card-icon"><?php self::render_icon( 'info-circle', 'sonyra-manager-icon sonyra-manager-card-icon' ); ?></div>
							<div class="sonyra-manager-site-card-copy">
								<strong><?php echo esc_html( self::text( 'manager.settings.knowledge_base.title' ) ); ?></strong>
								<p><?php echo esc_html( self::text( 'manager.settings.knowledge_base.description' ) ); ?></p>
							</div>
						</article>
					</div>
				</section>
			</div>
		</section>
		<?php
	}

	private static function get_help_entries_payload(): array {
		return class_exists( 'Sonyra_Site_Manager_Help_Library' ) ? Sonyra_Site_Manager_Help_Library::get_entries() : array();
	}

	private static function render_shared_icon_templates(): void {
		$icon_keys = array(
			'settings',
			'help-circle',
			'arrow-left',
			'x',
			'plus',
			'trash',
			'photo',
			'file',
		);
		?>
		<div class="sonyra-manager-shared-icon-templates" hidden aria-hidden="true">
			<?php foreach ( $icon_keys as $icon_key ) : ?>
				<span data-pages-icon-template="<?php echo esc_attr( $icon_key ); ?>"><?php self::render_icon( $icon_key, 'sonyra-manager-icon sonyra-manager-pages-button-icon' ); ?></span>
			<?php endforeach; ?>
		</div>
		<?php
	}

	public static function render_shell(): void {
		$css_path = plugin_dir_path( SONYRA_SITE_MANAGER_FILE ) . 'assets/css/sonyra-manager.css';
		$css_url = plugins_url( 'assets/css/sonyra-manager.css', SONYRA_SITE_MANAGER_FILE );
		$color_css_path = plugin_dir_path( SONYRA_SITE_MANAGER_FILE ) . 'assets/css/sonyra-color-controller.css';
		$color_css_url = plugins_url( 'assets/css/sonyra-color-controller.css', SONYRA_SITE_MANAGER_FILE );
		$js_path = plugin_dir_path( SONYRA_SITE_MANAGER_FILE ) . 'assets/js/sonyra-manager.js';
		$js_url = plugins_url( 'assets/js/sonyra-manager.js', SONYRA_SITE_MANAGER_FILE );
		$ui_js_path = plugin_dir_path( SONYRA_SITE_MANAGER_FILE ) . 'assets/js/sonyra-manager-ui.js';
		$ui_js_url = plugins_url( 'assets/js/sonyra-manager-ui.js', SONYRA_SITE_MANAGER_FILE );
		$color_js_path = plugin_dir_path( SONYRA_SITE_MANAGER_FILE ) . 'assets/js/sonyra-color-controller.js';
		$color_js_url = plugins_url( 'assets/js/sonyra-color-controller.js', SONYRA_SITE_MANAGER_FILE );
		$logo_path = plugin_dir_path( SONYRA_SITE_MANAGER_FILE ) . 'assets/images/brand/pult-site-logo.png';
		$logo_url = plugins_url( 'assets/images/brand/pult-site-logo.png', SONYRA_SITE_MANAGER_FILE );
		$favicon_ico_path = plugin_dir_path( SONYRA_SITE_MANAGER_FILE ) . 'assets/images/brand/pult-site-favicon.ico';
		$favicon_ico_url = plugins_url( 'assets/images/brand/pult-site-favicon.ico', SONYRA_SITE_MANAGER_FILE );
		$favicon_32_path = plugin_dir_path( SONYRA_SITE_MANAGER_FILE ) . 'assets/images/brand/pult-site-favicon-32.png';
		$favicon_32_url = plugins_url( 'assets/images/brand/pult-site-favicon-32.png', SONYRA_SITE_MANAGER_FILE );
		$favicon_192_path = plugin_dir_path( SONYRA_SITE_MANAGER_FILE ) . 'assets/images/brand/pult-site-favicon-192.png';
		$favicon_192_url = plugins_url( 'assets/images/brand/pult-site-favicon-192.png', SONYRA_SITE_MANAGER_FILE );
		$apple_touch_icon_path = plugin_dir_path( SONYRA_SITE_MANAGER_FILE ) . 'assets/images/brand/pult-site-apple-touch-icon-180.png';
		$apple_touch_icon_url = plugins_url( 'assets/images/brand/pult-site-apple-touch-icon-180.png', SONYRA_SITE_MANAGER_FILE );
		$logout_url = function_exists( 'rest_url' ) ? rest_url( 'sonyra-site-manager/v1/auth/logout' ) : '';
		$session_url = function_exists( 'rest_url' ) ? rest_url( 'sonyra-site-manager/v1/auth/session' ) : '';
		$pages_url = function_exists( 'rest_url' ) ? rest_url( 'sonyra-site-manager/v1/pages' ) : '';
		$color_controller_url = function_exists( 'rest_url' ) ? rest_url( 'sonyra-site-manager/v1/design/colors' ) : '';
		$login_url = function_exists( 'home_url' ) ? home_url( '/manager/login' ) : '/manager/login';
		$idle_timeout_seconds = class_exists( 'Sonyra_Site_Manager_Auth_Sessions' ) ? Sonyra_Site_Manager_Auth_Sessions::get_idle_timeout_seconds() : 900;
		$nonce = function_exists( 'wp_create_nonce' ) ? wp_create_nonce( 'wp_rest' ) : '';
		$pages_nonce = class_exists( 'Sonyra_Site_Manager_Pages_Store' ) ? Sonyra_Site_Manager_Pages_Store::create_nonce() : '';
		$color_controller_nonce = class_exists( 'Sonyra_Site_Manager_Color_Controller' ) ? Sonyra_Site_Manager_Color_Controller::create_nonce() : '';
		$color_controller_payload = class_exists( 'Sonyra_Site_Manager_Color_Controller' ) ? Sonyra_Site_Manager_Color_Controller::get_manager_payload( $color_controller_url, $color_controller_nonce ) : array();
		$site_url = function_exists( 'home_url' ) ? home_url( '/' ) : '/';
		$year = function_exists( 'date_i18n' ) ? date_i18n( 'Y' ) : date( 'Y' );
		$sections = self::get_sections();
		$views = self::get_views();
		$active_section = $sections['dashboard'];
		?>
<!doctype html>
<html lang="ru">
	<head>
		<meta charset="UTF-8">
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<meta name="robots" content="noindex,nofollow">
		<script>
			(function () {
				try {
					if (window.localStorage.getItem('sonyra_manager_sidebar_state') === 'collapsed') {
						document.documentElement.classList.add('sonyra-sidebar-collapsed');
					}
				} catch (error) {}
			}());
		</script>
			<title><?php echo esc_html( self::text( (string) $active_section['title'] ) ); ?></title>
	<?php if ( '' !== self::versioned_asset_url( $favicon_ico_url, $favicon_ico_path, 'sonyra_favicon' ) ) : ?>
	<link rel="icon" href="<?php echo esc_url( self::versioned_asset_url( $favicon_ico_url, $favicon_ico_path, 'sonyra_favicon' ) ); ?>" sizes="any">
	<link rel="shortcut icon" href="<?php echo esc_url( self::versioned_asset_url( $favicon_ico_url, $favicon_ico_path, 'sonyra_favicon' ) ); ?>" type="image/x-icon">
	<?php endif; ?>
	<?php if ( '' !== self::versioned_asset_url( $favicon_32_url, $favicon_32_path, 'sonyra_favicon' ) ) : ?>
	<link rel="icon" type="image/png" sizes="32x32" href="<?php echo esc_url( self::versioned_asset_url( $favicon_32_url, $favicon_32_path, 'sonyra_favicon' ) ); ?>">
	<?php endif; ?>
	<?php if ( '' !== self::versioned_asset_url( $favicon_192_url, $favicon_192_path, 'sonyra_favicon' ) ) : ?>
	<link rel="icon" type="image/png" sizes="192x192" href="<?php echo esc_url( self::versioned_asset_url( $favicon_192_url, $favicon_192_path, 'sonyra_favicon' ) ); ?>">
	<?php endif; ?>
	<?php if ( '' !== self::versioned_asset_url( $apple_touch_icon_url, $apple_touch_icon_path, 'sonyra_favicon' ) ) : ?>
	<link rel="apple-touch-icon" sizes="180x180" href="<?php echo esc_url( self::versioned_asset_url( $apple_touch_icon_url, $apple_touch_icon_path, 'sonyra_favicon' ) ); ?>">
	<?php endif; ?>
	<link rel="stylesheet" href="<?php echo esc_url( self::versioned_asset_url( $css_url, $css_path ) ); ?>">
	<?php if ( '' !== self::versioned_asset_url( $color_css_url, $color_css_path ) ) : ?>
	<link rel="stylesheet" href="<?php echo esc_url( self::versioned_asset_url( $color_css_url, $color_css_path ) ); ?>">
	<?php endif; ?>
</head>
	<body class="sonyra-manager-page">
	<div class="sonyra-manager-shell" data-sidebar-state="expanded" data-manager-booting="true" data-manager-shell data-logout-url="<?php echo esc_url( $logout_url ); ?>" data-login-url="<?php echo esc_url( $login_url ); ?>" data-session-url="<?php echo esc_url( $session_url ); ?>" data-idle-timeout-seconds="<?php echo esc_attr( (string) $idle_timeout_seconds ); ?>" data-nonce="<?php echo esc_attr( $nonce ); ?>" data-pages-url="<?php echo esc_url( $pages_url ); ?>" data-pages-nonce="<?php echo esc_attr( $pages_nonce ); ?>" data-site-url="<?php echo esc_url( $site_url ); ?>">
		<div class="sonyra-manager-backdrop" data-manager-backdrop hidden></div>

			<main class="sonyra-manager-main">
				<?php self::render_page_header( $active_section ); ?>
				<?php self::render_page_header_templates( $sections ); ?>
				<?php self::render_notice_center(); ?>

				<?php self::render_dashboard_view(); ?>
				<?php self::render_site_view(); ?>
				<?php self::render_pages_view(); ?>
				<?php self::render_design_view(); ?>
				<?php self::render_settings_view(); ?>

			<?php foreach ( $views as $route => $view ) : ?>
				<?php if ( in_array( $route, array( 'dashboard', 'setup', 'pages', 'design', 'settings' ), true ) ) : ?>
					<?php continue; ?>
				<?php endif; ?>
					<section class="sonyra-manager-view sonyra-manager-view-placeholder" data-manager-view="<?php echo esc_attr( $route ); ?>" hidden></section>
			<?php endforeach; ?>

					<footer class="sonyra-login-footer sonyra-manager-footer">
						<span class="sonyra-login-footer-item sonyra-manager-footer-item"><?php echo esc_html( self::text( 'login.publisher' ) ); ?></span>
						<span class="sonyra-login-footer-separator sonyra-manager-footer-separator" aria-hidden="true">·</span>
						<span class="sonyra-login-footer-item sonyra-manager-footer-item"><?php echo esc_html( '© ' . $year . ' ' . self::text( 'login.footer_rights_holder' ) ); ?></span>
						<span class="sonyra-login-footer-separator sonyra-manager-footer-separator" aria-hidden="true">·</span>
						<span class="sonyra-login-footer-item sonyra-login-footer-version sonyra-manager-footer-item"><?php echo esc_html( self::text( 'login.version_label' ) . ' ' . SONYRA_SITE_MANAGER_VERSION ); ?></span>
					</footer>
		</main>
		<?php self::render_notice_support_modal_foundation(); ?>

		<aside class="sonyra-manager-sidebar" aria-label="<?php echo esc_attr( self::text( 'manager.sidebar_label' ) ); ?>">
			<div class="sonyra-manager-brand-row">
				<div class="sonyra-manager-brand">
					<button type="button" class="sonyra-manager-logo-tile" data-manager-logo-toggle aria-expanded="true" aria-label="<?php echo esc_attr( self::text( 'manager.sidebar_collapse' ) ); ?>" data-label-expanded="<?php echo esc_attr( self::text( 'manager.sidebar_collapse' ) ); ?>" data-label-collapsed="<?php echo esc_attr( self::text( 'manager.sidebar_expand' ) ); ?>" data-tooltip="<?php echo esc_attr( self::text( 'manager.sidebar_expand_hint' ) ); ?>">
						<?php if ( '' !== self::versioned_asset_url( $logo_url, $logo_path ) ) : ?>
							<img src="<?php echo esc_url( self::versioned_asset_url( $logo_url, $logo_path ) ); ?>" alt="" aria-hidden="true" decoding="async">
						<?php endif; ?>
					</button>
					<span class="sonyra-manager-brand-copy">
						<strong><?php echo esc_html( self::text( 'manager.brand_name' ) ); ?></strong>
						<small><?php echo esc_html( self::text( 'manager.publisher' ) ); ?></small>
					</span>
				</div>
				<button type="button" class="sonyra-manager-sidebar-toggle" data-manager-sidebar-toggle aria-expanded="true" aria-label="<?php echo esc_attr( self::text( 'manager.sidebar_collapse' ) ); ?>" data-label-expanded="<?php echo esc_attr( self::text( 'manager.sidebar_collapse' ) ); ?>" data-label-collapsed="<?php echo esc_attr( self::text( 'manager.sidebar_expand' ) ); ?>">
					<span class="sonyra-manager-toggle-icon"><?php self::render_icon( 'layout-sidebar-left-collapse' ); ?></span>
				</button>
			</div>

			<nav class="sonyra-manager-nav">
					<ul>
						<?php foreach ( self::get_nav_items() as $item ) : ?>
							<li>
								<button type="button" class="sonyra-manager-nav-item" data-manager-route="<?php echo esc_attr( $item['route'] ); ?>" data-kicker="<?php echo esc_attr( self::text( (string) $item['kicker'] ) ); ?>" data-title="<?php echo esc_attr( self::text( (string) $item['title'] ) ); ?>" data-description="<?php echo esc_attr( self::text( (string) $item['description'] ) ); ?>" data-tooltip="<?php echo esc_attr( self::text( (string) $item['nav_label'] ) ); ?>" <?php echo 'dashboard' === $item['route'] ? 'aria-current="page"' : ''; ?>>
									<span class="sonyra-manager-nav-icon-slot"><?php self::render_icon( (string) $item['icon_key'], 'sonyra-manager-icon sonyra-manager-nav-icon' ); ?></span>
									<span class="sonyra-manager-nav-label"><?php echo esc_html( self::text( (string) $item['nav_label'] ) ); ?></span>
								</button>
							</li>
						<?php endforeach; ?>
						<li class="sonyra-manager-nav-logout-row">
							<button type="button" class="sonyra-manager-nav-item sonyra-manager-logout" data-manager-logout data-tooltip="<?php echo esc_attr( self::text( 'manager.nav_logout' ) ); ?>">
								<span class="sonyra-manager-nav-icon-slot"><?php self::render_icon( 'logout', 'sonyra-manager-icon sonyra-manager-nav-icon' ); ?></span>
								<span class="sonyra-manager-nav-label"><?php echo esc_html( self::text( 'manager.nav_logout' ) ); ?></span>
							</button>
						</li>
					</ul>
				</nav>
			</aside>
	</div>
	<div class="sonyra-manager-help-layer" data-manager-help-layer></div>
	<?php self::render_shared_icon_templates(); ?>
	<script type="application/json" id="sonyra-manager-pages-i18n"><?php echo wp_json_encode( self::get_pages_i18n_payload() ); ?></script>
	<script type="application/json" id="sonyra-manager-help-entries"><?php echo wp_json_encode( self::get_help_entries_payload() ); ?></script>
	<script type="application/json" id="sonyra-manager-color-controller-payload"><?php echo wp_json_encode( $color_controller_payload ); ?></script>
	<?php if ( '' !== self::versioned_asset_url( $ui_js_url, $ui_js_path ) ) : ?>
	<script src="<?php echo esc_url( self::versioned_asset_url( $ui_js_url, $ui_js_path ) ); ?>" defer></script>
	<?php endif; ?>
	<?php if ( '' !== self::versioned_asset_url( $color_js_url, $color_js_path ) ) : ?>
	<script src="<?php echo esc_url( self::versioned_asset_url( $color_js_url, $color_js_path ) ); ?>" defer></script>
	<?php endif; ?>
	<script src="<?php echo esc_url( self::versioned_asset_url( $js_url, $js_path ) ); ?>" defer></script>
</body>
</html>
		<?php
	}

	private static function render_metric( string $label_key ): void {
		?>
		<div class="sonyra-manager-metric">
			<span><?php echo esc_html( self::text( $label_key ) ); ?></span>
			<strong><?php echo esc_html( self::text( 'manager.metric_empty' ) ); ?></strong>
			<small><?php echo esc_html( self::text( 'manager.metric_pending' ) ); ?></small>
		</div>
		<?php
	}

	private static function render_step( string $icon, string $title_key, string $hint_key, string $status_key, string $state ): void {
		?>
		<div class="sonyra-manager-step sonyra-manager-step-<?php echo esc_attr( $state ); ?>">
			<span class="sonyra-manager-step-icon"><?php self::render_icon( $icon ); ?></span>
			<span>
				<strong><?php echo esc_html( self::text( $title_key ) ); ?></strong>
				<small><?php echo esc_html( self::text( $hint_key ) ); ?></small>
			</span>
			<em><?php echo esc_html( self::text( $status_key ) ); ?></em>
		</div>
		<?php
	}
}
