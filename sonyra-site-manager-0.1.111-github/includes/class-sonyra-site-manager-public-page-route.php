<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Sonyra_Site_Manager_Public_Page_Route {

	public static function register_hooks(): void {
		add_action( 'template_redirect', array( __CLASS__, 'maybe_render_public_page' ), 8 );
	}

	public static function maybe_render_public_page(): void {
		if ( self::should_skip_request() || ! class_exists( 'Sonyra_Site_Manager_Pages_Store' ) ) {
			return;
		}

		$preview_request = self::get_preview_request();

		if ( null !== $preview_request ) {
			if ( ! self::can_access_preview() ) {
				self::render_safe_404();
				exit;
			}

			$page = Sonyra_Site_Manager_Pages_Store::find_page_for_preview( $preview_request['identifier'], $preview_request['mode'] );

			if ( empty( $page ) ) {
				self::render_safe_404();
				exit;
			}

			self::render_page( $page, true );
			exit;
		}

		$slug = self::get_request_slug();

		if ( null === $slug ) {
			return;
		}

		if ( '' !== $slug && ! function_exists( 'is_404' ) ) {
			return;
		}

		if ( '' !== $slug && ! is_404() ) {
			return;
		}

		$page = Sonyra_Site_Manager_Pages_Store::find_public_page_by_slug( $slug );

		if ( empty( $page ) ) {
			return;
		}

		self::render_page( $page, false );
		exit;
	}

	private static function should_skip_request(): bool {
		if ( is_admin() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) || ( defined( 'XMLRPC_REQUEST' ) && XMLRPC_REQUEST ) ) {
			return true;
		}

		$path = self::get_request_path();

		if ( preg_match( '#\.(?:css|js|json|xml|txt|ico|png|jpg|jpeg|gif|webp|svg|woff|woff2|ttf|map)$#i', $path ) ) {
			return true;
		}

		foreach ( array( 'manager', 'wp-admin', 'wp-json', 'wp-content', 'wp-includes' ) as $prefix ) {
			if ( $prefix === $path || 0 === strpos( $path, $prefix . '/' ) ) {
				return true;
			}
		}

		return false;
	}

	private static function get_request_slug(): ?string {
		$path = self::get_request_path();

		if ( '' === $path ) {
			return '';
		}

		if ( false !== strpos( $path, '/' ) ) {
			return null;
		}

		return sanitize_title( $path );
	}

	private static function get_preview_request(): ?array {
		$path = self::get_request_path();

		if ( 0 !== strpos( $path, 'sonyra-preview/' ) ) {
			return null;
		}

		$parts = array_values( array_filter( explode( '/', $path ) ) );

		if ( 3 === count( $parts ) && 'id' === $parts[1] ) {
			return array(
				'mode'       => 'id',
				'identifier' => sanitize_key( $parts[2] ),
			);
		}

		if ( 2 === count( $parts ) ) {
			return array(
				'mode'       => 'slug',
				'identifier' => sanitize_title( $parts[1] ),
			);
		}

		return null;
	}

	private static function get_request_path(): string {
		$uri = isset( $_SERVER['REQUEST_URI'] ) ? (string) wp_unslash( $_SERVER['REQUEST_URI'] ) : '';
		$path = (string) wp_parse_url( $uri, PHP_URL_PATH );
		$home_path = (string) wp_parse_url( home_url( '/' ), PHP_URL_PATH );

		if ( '' !== $home_path && '/' !== $home_path && 0 === strpos( $path, $home_path ) ) {
			$path = substr( $path, strlen( $home_path ) );
		}

		return trim( $path, '/' );
	}

	private static function render_page( array $page, bool $is_preview = false ): void {
		$title = '' !== (string) $page['seo_title'] ? (string) $page['seo_title'] : (string) $page['title'];
		$description = isset( $page['seo_description'] ) ? (string) $page['seo_description'] : '';

		if ( function_exists( 'status_header' ) ) {
			status_header( 200 );
		}

		if ( function_exists( 'nocache_headers' ) ) {
			nocache_headers();
		}

		if ( $is_preview ) {
			header( 'X-Robots-Tag: noindex, nofollow', true );
		}

			?>
	<!doctype html>
	<html <?php language_attributes(); ?>>
	<head>
	<meta charset="<?php echo esc_attr( get_bloginfo( 'charset' ) ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
		<title><?php echo esc_html( $title ); ?></title>
		<?php if ( '' !== $description ) : ?>
		<meta name="description" content="<?php echo esc_attr( $description ); ?>">
		<?php endif; ?>
		<?php if ( $is_preview ) : ?>
		<meta name="robots" content="noindex,nofollow">
		<?php endif; ?>
		<?php self::render_stylesheet_link(); ?>
		<?php wp_head(); ?>
	</head>
	<body class="sonyra-public-site<?php echo $is_preview ? ' sonyra-public-site-preview' : ''; ?>">
		<main id="sonyra-public-page-top" class="sonyra-public-site-main sonyra-public-site-shell">
			<header class="sonyra-public-page-header">
				<h1><?php echo esc_html( (string) $page['title'] ); ?></h1>
			</header>
			<?php self::render_sections( isset( $page['sections'] ) && is_array( $page['sections'] ) ? $page['sections'] : array(), $page ); ?>
		</main>
		<?php self::render_widgets( isset( $page['widgets'] ) && is_array( $page['widgets'] ) ? $page['widgets'] : array(), $is_preview ); ?>
		<?php self::render_popups( isset( $page['popups'] ) && is_array( $page['popups'] ) ? $page['popups'] : array(), $is_preview ); ?>
		<?php wp_footer(); ?>
	</body>
	</html>
			<?php
	}

	private static function render_sections( array $sections, array $page ): void {
		unset( $page );
		$has_renderable_blocks = false;

		foreach ( $sections as $section ) {
			if ( ! is_array( $section ) || self::should_skip_unavailable_section( $section ) ) {
				continue;
			}

			$blocks = self::get_section_blocks( $section );

			if ( ! empty( $blocks ) ) {
				$has_renderable_blocks = true;
				break;
			}
		}

		if ( ! $has_renderable_blocks ) {
			$legacy_blocks = array();

			foreach ( $sections as $section ) {
				if ( is_array( $section ) && self::has_legacy_section_content( $section ) ) {
					$legacy_blocks[] = $section;
				}
			}

			$has_renderable_blocks = ! empty( $legacy_blocks );
		}

		if ( empty( $sections ) || ! $has_renderable_blocks ) {
			?>
				<section class="sonyra-public-section sonyra-public-section-text sonyra-public-section-empty">
					<p><?php echo esc_html( self::text( 'manager.pages.preview.empty_sections' ) ); ?></p>
				</section>
				<?php
			return;
		}

		foreach ( $sections as $section ) {
			if ( ! is_array( $section ) || self::should_skip_unavailable_section( $section ) ) {
				continue;
			}

			self::render_section( $section );
		}
	}

	private static function render_section( array $section ): void {
		$type   = isset( $section['type'] ) ? sanitize_key( (string) $section['type'] ) : 'text';
		$blocks = self::get_section_blocks( $section );

		if ( empty( $blocks ) ) {
			return;
		}

		if ( in_array( $type, array( 'hero', 'text', 'contacts' ), true ) && 1 === count( $blocks ) ) {
			$block = $blocks[0];
			$block_type = isset( $block['type'] ) ? sanitize_key( (string) $block['type'] ) : 'text';

			if ( 'hero' === $block_type ) {
				self::render_hero_section( $block );
				return;
			}

			if ( 'contacts' === $block_type ) {
				self::render_contacts_section( $block );
				return;
			}

			self::render_text_section( $block );
			return;
		}

		self::render_generic_section( $section, $blocks );
	}

	private static function render_hero_section( array $section ): void {
		?>
		<section class="sonyra-public-section sonyra-public-section-hero">
			<?php if ( ! empty( $section['kicker'] ) ) : ?>
				<p class="sonyra-public-kicker"><?php echo esc_html( (string) $section['kicker'] ); ?></p>
			<?php endif; ?>
				<?php if ( ! empty( $section['heading'] ) ) : ?>
					<h1><?php echo esc_html( (string) $section['heading'] ); ?></h1>
				<?php endif; ?>
			<?php if ( ! empty( $section['text'] ) ) : ?>
				<p><?php echo nl2br( esc_html( (string) $section['text'] ) ); ?></p>
			<?php endif; ?>
			<?php if ( ! empty( $section['button_label'] ) && ! empty( $section['button_url'] ) ) : ?>
				<a class="sonyra-public-button" href="<?php echo esc_url( (string) $section['button_url'] ); ?>"><?php echo esc_html( (string) $section['button_label'] ); ?></a>
			<?php endif; ?>
		</section>
		<?php
	}

	private static function render_text_section( array $section ): void {
		?>
		<section class="sonyra-public-section sonyra-public-section-text">
				<?php if ( ! empty( $section['heading'] ) ) : ?>
					<h2><?php echo esc_html( (string) $section['heading'] ); ?></h2>
				<?php endif; ?>
			<?php if ( ! empty( $section['text'] ) ) : ?>
				<p><?php echo nl2br( esc_html( (string) $section['text'] ) ); ?></p>
			<?php endif; ?>
		</section>
		<?php
	}

	private static function render_contacts_section( array $section ): void {
		?>
		<section class="sonyra-public-section sonyra-public-section-contacts">
				<?php if ( ! empty( $section['heading'] ) ) : ?>
					<h2><?php echo esc_html( (string) $section['heading'] ); ?></h2>
				<?php endif; ?>
				<?php if ( ! empty( $section['description'] ) ) : ?>
					<p><?php echo nl2br( esc_html( (string) $section['description'] ) ); ?></p>
				<?php elseif ( ! empty( $section['text'] ) ) : ?>
					<p><?php echo nl2br( esc_html( (string) $section['text'] ) ); ?></p>
				<?php endif; ?>
			<div class="sonyra-public-contact-list">
				<?php if ( ! empty( $section['email'] ) ) : ?>
					<a href="<?php echo esc_url( 'mailto:' . sanitize_email( (string) $section['email'] ) ); ?>"><?php echo esc_html( (string) $section['email'] ); ?></a>
				<?php endif; ?>
				<?php if ( ! empty( $section['phone'] ) ) : ?>
					<span><?php echo esc_html( (string) $section['phone'] ); ?></span>
				<?php endif; ?>
				<?php if ( ! empty( $section['address'] ) ) : ?>
					<span><?php echo esc_html( (string) $section['address'] ); ?></span>
				<?php endif; ?>
			</div>
		</section>
		<?php
	}

	private static function render_generic_section( array $section, array $blocks ): void {
		$type = isset( $section['type'] ) ? sanitize_key( (string) $section['type'] ) : 'text';
		$heading = self::get_section_heading( $section, $blocks );
		?>
		<section class="sonyra-public-section sonyra-public-section-generic sonyra-public-section-<?php echo esc_attr( $type ); ?>">
			<?php if ( '' !== $heading ) : ?>
				<h2><?php echo esc_html( $heading ); ?></h2>
			<?php endif; ?>
			<div class="sonyra-public-section-stack">
				<?php foreach ( $blocks as $block ) : ?>
					<?php self::render_generic_block( is_array( $block ) ? $block : array() ); ?>
				<?php endforeach; ?>
			</div>
		</section>
		<?php
	}

	private static function render_generic_block( array $block ): void {
		$type        = isset( $block['type'] ) ? sanitize_key( (string) $block['type'] ) : 'text';
		$heading     = isset( $block['heading'] ) ? (string) $block['heading'] : '';
		$text        = isset( $block['text'] ) ? (string) $block['text'] : '';
		$description = isset( $block['description'] ) ? (string) $block['description'] : '';
		$url         = self::get_safe_url( isset( $block['link_url'] ) ? (string) $block['link_url'] : ( isset( $block['button_url'] ) ? (string) $block['button_url'] : '' ) );
		?>
		<article class="sonyra-public-generic-block sonyra-public-block sonyra-public-block--<?php echo esc_attr( $type ); ?>">
			<?php if ( ! empty( $block['kicker'] ) ) : ?>
				<p class="sonyra-public-kicker"><?php echo esc_html( (string) $block['kicker'] ); ?></p>
			<?php endif; ?>
			<?php if ( '' !== $heading ) : ?>
				<h3><?php echo esc_html( $heading ); ?></h3>
			<?php endif; ?>
			<?php if ( in_array( $type, array( 'image', 'logo', 'person_profile' ), true ) && ! empty( $block['image_url'] ) ) : ?>
				<p class="sonyra-public-block-media"><img src="<?php echo esc_url( self::get_safe_url( (string) $block['image_url'] ) ); ?>" alt="<?php echo esc_attr( isset( $block['image_alt'] ) ? (string) $block['image_alt'] : '' ); ?>"></p>
			<?php endif; ?>
			<?php if ( 'icon_text' === $type && ! empty( $block['icon_key'] ) && class_exists( 'Sonyra_Site_Manager_Icon_Renderer' ) ) : ?>
				<span class="sonyra-public-block-inline-icon"><?php Sonyra_Site_Manager_Icon_Renderer::display( sanitize_key( (string) $block['icon_key'] ), array( 'class' => 'sonyra-icon sonyra-manager-icon', 'aria_hidden' => true ) ); ?></span>
			<?php endif; ?>
			<?php if ( 'testimonial' === $type && ! empty( $block['quote'] ) ) : ?>
				<blockquote><?php echo nl2br( esc_html( (string) $block['quote'] ) ); ?></blockquote>
			<?php endif; ?>
			<?php if ( 'faq_item' === $type && ! empty( $block['question'] ) ) : ?>
				<h4><?php echo esc_html( (string) $block['question'] ); ?></h4>
			<?php endif; ?>
			<?php if ( 'faq_item' === $type && ! empty( $block['answer'] ) ) : ?>
				<p><?php echo nl2br( esc_html( (string) $block['answer'] ) ); ?></p>
			<?php elseif ( 'list' === $type && ! empty( $block['list_text'] ) ) : ?>
				<ul><?php foreach ( preg_split( '/\r\n|\r|\n/', (string) $block['list_text'] ) as $item ) : ?><?php if ( '' !== trim( $item ) ) : ?><li><?php echo esc_html( trim( $item ) ); ?></li><?php endif; ?><?php endforeach; ?></ul>
			<?php elseif ( '' !== $description ) : ?>
				<p><?php echo nl2br( esc_html( $description ) ); ?></p>
			<?php elseif ( '' !== $text ) : ?>
				<p><?php echo nl2br( esc_html( $text ) ); ?></p>
			<?php endif; ?>
			<?php if ( in_array( $type, array( 'metric', 'price' ), true ) && ! empty( $block['value'] ) ) : ?>
				<strong><?php echo esc_html( (string) $block['value'] ); ?></strong>
			<?php endif; ?>
			<?php if ( in_array( $type, array( 'metric', 'price' ), true ) && ! empty( $block['metric_label'] ) ) : ?>
				<p><?php echo esc_html( (string) $block['metric_label'] ); ?></p>
			<?php endif; ?>
			<?php if ( 'step' === $type && ! empty( $block['step_number'] ) ) : ?>
				<p><?php echo esc_html( (string) $block['step_number'] ); ?></p>
			<?php endif; ?>
			<?php if ( 'contact' === $type || 'contacts' === $type ) : ?>
				<div class="sonyra-public-contact-list">
					<?php if ( ! empty( $block['email'] ) ) : ?><a href="<?php echo esc_url( 'mailto:' . sanitize_email( (string) $block['email'] ) ); ?>"><?php echo esc_html( (string) $block['email'] ); ?></a><?php endif; ?>
					<?php if ( ! empty( $block['phone'] ) ) : ?><span><?php echo esc_html( (string) $block['phone'] ); ?></span><?php endif; ?>
					<?php if ( ! empty( $block['address'] ) ) : ?><span><?php echo esc_html( (string) $block['address'] ); ?></span><?php endif; ?>
				</div>
			<?php endif; ?>
			<?php if ( 'social_link' === $type && ! empty( $block['platform'] ) ) : ?>
				<p><?php echo esc_html( (string) $block['platform'] ); ?></p>
			<?php endif; ?>
			<?php if ( 'embed' === $type && ! empty( $block['embed_code'] ) ) : ?>
				<pre><?php echo esc_html( (string) $block['embed_code'] ); ?></pre>
			<?php endif; ?>
			<?php if ( ! empty( $block['caption'] ) ) : ?>
				<p><?php echo esc_html( (string) $block['caption'] ); ?></p>
			<?php endif; ?>
			<?php if ( ! empty( $block['author'] ) ) : ?>
				<p><?php echo esc_html( (string) $block['author'] ); ?></p>
			<?php endif; ?>
			<?php if ( ! empty( $block['role'] ) ) : ?>
				<p><?php echo esc_html( (string) $block['role'] ); ?></p>
			<?php endif; ?>
			<?php if ( ! empty( $block['name_person'] ) ) : ?>
				<p><?php echo esc_html( (string) $block['name_person'] ); ?></p>
			<?php endif; ?>
			<?php if ( ! empty( $block['tag_label'] ) ) : ?>
				<p><?php echo esc_html( (string) $block['tag_label'] ); ?></p>
			<?php endif; ?>
			<?php if ( ! empty( $block['button_label'] ) && ! empty( $block['button_url'] ) && self::get_safe_url( (string) $block['button_url'] ) ) : ?>
				<a class="sonyra-public-button" href="<?php echo esc_url( self::get_safe_url( (string) $block['button_url'] ) ); ?>"><?php echo esc_html( (string) $block['button_label'] ); ?></a>
			<?php elseif ( in_array( $type, array( 'button', 'link', 'file', 'social_link', 'logo', 'map', 'video' ), true ) && $url ) : ?>
				<a class="sonyra-public-button" href="<?php echo esc_url( $url ); ?>"><?php echo esc_html( self::get_block_link_label( $block ) ); ?></a>
			<?php elseif ( 'form' === $type && ! empty( $block['button_label'] ) ) : ?>
				<span class="sonyra-public-button"><?php echo esc_html( (string) $block['button_label'] ); ?></span>
			<?php endif; ?>
		</article>
		<?php
	}

	private static function can_access_preview(): bool {
		if ( ! class_exists( 'Sonyra_Site_Manager_Auth_Access_Guard' ) ) {
			return false;
		}

		$context = Sonyra_Site_Manager_Auth_Access_Guard::get_current_auth_context();

		return ! empty( $context['authenticated'] ) && Sonyra_Site_Manager_Auth_Access_Guard::can_manage_content( $context );
	}

	private static function render_widgets( array $widgets, bool $is_preview ): void {
		if ( empty( $widgets ) ) {
			return;
		}

		$rendered = array();

		foreach ( $widgets as $widget ) {
			if ( ! is_array( $widget ) || ! self::is_widget_enabled( $widget ) ) {
				continue;
			}

			$markup = self::get_widget_markup( $widget, $is_preview );

			if ( '' === $markup ) {
				continue;
			}

			$position = self::sanitize_widget_position( isset( $widget['position'] ) ? (string) $widget['position'] : 'bottom_right' );

			if ( ! isset( $rendered[ $position ] ) ) {
				$rendered[ $position ] = array();
			}

			$rendered[ $position ][] = $markup;
		}

		if ( empty( $rendered ) ) {
			return;
		}

		foreach ( $rendered as $position => $items ) {
			?>
			<div class="sonyra-public-widgets sonyra-public-widgets--<?php echo esc_attr( $position ); ?>">
				<?php
				foreach ( $items as $markup ) {
					echo $markup; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				}
				?>
			</div>
			<?php
		}
	}

	private static function get_widget_markup( array $widget, bool $is_preview ): string {
		$type        = isset( $widget['type'] ) ? sanitize_key( (string) $widget['type'] ) : '';
		$name        = isset( $widget['name'] ) ? sanitize_text_field( (string) $widget['name'] ) : '';
		$label       = isset( $widget['label'] ) && '' !== (string) $widget['label'] ? sanitize_text_field( (string) $widget['label'] ) : $name;
		$url         = self::get_safe_url( isset( $widget['url'] ) ? (string) $widget['url'] : '' );
		$phone       = isset( $widget['phone'] ) ? sanitize_text_field( (string) $widget['phone'] ) : '';
		$email       = isset( $widget['email'] ) ? sanitize_email( (string) $widget['email'] ) : '';
		$platform    = isset( $widget['platform'] ) ? sanitize_text_field( (string) $widget['platform'] ) : '';
		$address     = isset( $widget['address'] ) ? sanitize_text_field( (string) $widget['address'] ) : '';
		$description = isset( $widget['description'] ) ? sanitize_textarea_field( (string) $widget['description'] ) : '';
		$embed_code  = isset( $widget['embed_code'] ) ? sanitize_textarea_field( (string) $widget['embed_code'] ) : '';
		$classes     = 'sonyra-public-widget sonyra-public-widget--' . $type;
		$meta        = '';

		if ( '' !== $platform ) {
			$meta = '<span class="sonyra-public-widget__meta">' . esc_html( $platform ) . '</span>';
		} elseif ( '' !== $address ) {
			$meta = '<span class="sonyra-public-widget__meta">' . esc_html( $address ) . '</span>';
		}

		if ( 'call' === $type || 'callback' === $type ) {
			$tel_href = self::get_tel_href( $phone );

			if ( '' === $tel_href ) {
				return self::get_widget_placeholder_markup( $classes, $label, $meta );
			}

			return '<a class="' . esc_attr( $classes ) . '" href="' . esc_url( $tel_href ) . '"><span class="sonyra-public-widget__label">' . esc_html( $label ) . '</span></a>';
		}

		if ( 'email' === $type ) {
			if ( '' === $email ) {
				return self::get_widget_placeholder_markup( $classes, $label, $meta );
			}

			return '<a class="' . esc_attr( $classes ) . '" href="' . esc_url( 'mailto:' . $email ) . '"><span class="sonyra-public-widget__label">' . esc_html( $label ) . '</span></a>';
		}

		if ( 'back_to_top' === $type ) {
			return '<a class="' . esc_attr( $classes ) . '" href="#sonyra-public-page-top"><span class="sonyra-public-widget__label">' . esc_html( $label ) . '</span></a>';
		}

		if ( in_array( $type, array( 'message', 'messenger', 'social_button', 'quick_request', 'route_map', 'floating_button' ), true ) ) {
			if ( '' === $url ) {
				if ( 'route_map' === $type && '' !== $address ) {
					return '<span class="' . esc_attr( $classes ) . ' sonyra-public-widget--text-only"><span class="sonyra-public-widget__label">' . esc_html( $label ) . '</span><span class="sonyra-public-widget__meta">' . esc_html( $address ) . '</span></span>';
				}

				return self::get_widget_placeholder_markup( $classes, $label, $meta );
			}

			return '<a class="' . esc_attr( $classes ) . '" href="' . esc_url( $url ) . '"><span class="sonyra-public-widget__label">' . esc_html( $label ) . '</span>' . $meta . '</a>';
		}

		if ( 'external_widget' === $type ) {
			if ( ! $is_preview && '' === $description ) {
				return '<div class="' . esc_attr( $classes ) . ' sonyra-public-widget--external"><span class="sonyra-public-widget__label">' . esc_html( $label ) . '</span></div>';
			}

			$content = '' !== $description ? $description : $embed_code;

			if ( '' === $content ) {
				return '<div class="' . esc_attr( $classes ) . ' sonyra-public-widget--external"><span class="sonyra-public-widget__label">' . esc_html( $label ) . '</span></div>';
			}

			return '<div class="' . esc_attr( $classes ) . ' sonyra-public-widget--external"><span class="sonyra-public-widget__label">' . esc_html( $label ) . '</span><pre class="sonyra-public-widget__code">' . esc_html( $content ) . '</pre></div>';
		}

		if ( ! empty( $widget['unavailable'] ) || '' === $type ) {
			return '';
		}

		if ( '' !== $url ) {
			return '<a class="' . esc_attr( $classes ) . '" href="' . esc_url( $url ) . '"><span class="sonyra-public-widget__label">' . esc_html( $label ) . '</span></a>';
		}

		return '<span class="' . esc_attr( $classes ) . ' sonyra-public-widget--text-only"><span class="sonyra-public-widget__label">' . esc_html( $label ) . '</span></span>';
	}

	private static function get_widget_placeholder_markup( string $classes, string $label, string $meta = '' ): string {
		return '<span class="' . esc_attr( $classes ) . ' sonyra-public-widget--text-only"><span class="sonyra-public-widget__label">' . esc_html( $label ) . '</span>' . $meta . '</span>';
	}

	private static function render_popups( array $popups, bool $is_preview ): void {
		if ( empty( $popups ) ) {
			return;
		}

		$items = array();

		foreach ( $popups as $popup ) {
			if ( ! is_array( $popup ) || ! self::is_widget_enabled( $popup ) ) {
				continue;
			}

			$markup = self::get_popup_markup( $popup, $is_preview );

			if ( '' !== $markup ) {
				$items[] = $markup;
			}
		}

		if ( empty( $items ) ) {
			return;
		}
		?>
		<div class="sonyra-public-popups" data-sonyra-public-popups aria-label="<?php echo esc_attr( self::text( 'manager.pages.popups.panel_title' ) ); ?>">
			<?php foreach ( $items as $markup ) : ?>
				<?php echo $markup; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<?php endforeach; ?>
		</div>
		<?php
		self::render_popup_runtime_script();
	}

	private static function get_popup_markup( array $popup, bool $is_preview ): string {
		$type        = isset( $popup['type'] ) ? sanitize_key( (string) $popup['type'] ) : '';
		$id          = isset( $popup['id'] ) ? sanitize_key( (string) $popup['id'] ) : '';
		$name        = isset( $popup['name'] ) ? sanitize_text_field( (string) $popup['name'] ) : '';
		$heading     = isset( $popup['heading'] ) ? sanitize_text_field( (string) $popup['heading'] ) : $name;
		$text        = isset( $popup['text'] ) ? sanitize_textarea_field( (string) $popup['text'] ) : '';
		$button_label= isset( $popup['button_label'] ) ? sanitize_text_field( (string) $popup['button_label'] ) : '';
		$button_url  = self::get_safe_url( isset( $popup['button_url'] ) ? (string) $popup['button_url'] : '' );
		$email       = isset( $popup['email'] ) ? sanitize_email( (string) $popup['email'] ) : '';
		$phone       = isset( $popup['phone'] ) ? sanitize_text_field( (string) $popup['phone'] ) : '';
		$address     = isset( $popup['address'] ) ? sanitize_text_field( (string) $popup['address'] ) : '';
		$video_url   = self::get_safe_url( isset( $popup['video_url'] ) ? (string) $popup['video_url'] : '' );
		$embed_code  = isset( $popup['embed_code'] ) ? sanitize_textarea_field( (string) $popup['embed_code'] ) : '';
		$description = isset( $popup['description'] ) ? sanitize_textarea_field( (string) $popup['description'] ) : '';
		$trigger     = isset( $popup['trigger'] ) ? sanitize_key( (string) $popup['trigger'] ) : 'manual';
		$classes     = 'sonyra-public-popup sonyra-public-popup--' . $type;
		$dialog_id   = 'sonyra-popup-title-' . ( '' !== $id ? $id : md5( $type . '|' . $heading ) );

		if ( ! $is_preview && ! empty( $popup['unavailable'] ) && '' === $type ) {
			return '';
		}

		$meta = '<div class="sonyra-public-popup__meta"><span>' . esc_html( self::text( 'manager.pages.popups.trigger_' . $trigger ) ?: self::text( 'manager.pages.popups.trigger_manual' ) ) . '</span></div>';
		$body = '';

		if ( '' !== $text ) {
			$body .= '<p>' . nl2br( esc_html( $text ) ) . '</p>';
		}

		if ( in_array( $type, array( 'request_form', 'subscription' ), true ) && '' !== $button_label ) {
			$body .= '<span class="sonyra-public-button sonyra-public-button-inert" aria-disabled="true">' . esc_html( $button_label ) . '</span>';
		} elseif ( 'callback' === $type ) {
			$tel_href = self::get_tel_href( $phone );
			if ( '' !== $tel_href ) {
				$body .= '<a class="sonyra-public-button" href="' . esc_url( $tel_href ) . '">' . esc_html( $button_label ?: $phone ) . '</a>';
			}
		} elseif ( 'confirmation' === $type && '' !== $button_label && '' === $button_url ) {
			$body .= '<button type="button" class="sonyra-public-button sonyra-public-popup__action-close" data-popup-close>' . esc_html( $button_label ) . '</button>';
		} elseif ( 'video_popup' === $type && '' !== $video_url ) {
			$body .= '<a class="sonyra-public-button" href="' . esc_url( $video_url ) . '">' . esc_html( $button_label ?: $heading ) . '</a>';
		} elseif ( 'contacts' === $type ) {
			$body .= '<div class="sonyra-public-contact-list">';
			if ( '' !== $email ) {
				$body .= '<a href="' . esc_url( 'mailto:' . $email ) . '">' . esc_html( $email ) . '</a>';
			}
			if ( '' !== $phone ) {
				$body .= '<span>' . esc_html( $phone ) . '</span>';
			}
			if ( '' !== $address ) {
				$body .= '<span>' . esc_html( $address ) . '</span>';
			}
			$body .= '</div>';
			if ( '' !== $button_url ) {
				$body .= '<a class="sonyra-public-button" href="' . esc_url( $button_url ) . '">' . esc_html( $button_label ?: $heading ) . '</a>';
			}
		} elseif ( 'external_popup' === $type ) {
			$content = '' !== $description ? $description : $embed_code;
			if ( '' !== $content ) {
				$body .= '<pre class="sonyra-public-popup__code">' . esc_html( $content ) . '</pre>';
			}
		} elseif ( '' !== $button_label ) {
			if ( '' !== $button_url ) {
				$body .= '<a class="sonyra-public-button" href="' . esc_url( $button_url ) . '">' . esc_html( $button_label ) . '</a>';
			} else {
				$body .= '<span class="sonyra-public-button">' . esc_html( $button_label ) . '</span>';
			}
		}

		if ( '' === $heading && '' === $body ) {
			return '';
		}

		return '<div class="' . esc_attr( $classes ) . '" data-popup-id="' . esc_attr( $id ) . '" data-popup-trigger="' . esc_attr( $trigger ) . '" hidden aria-hidden="true"><div class="sonyra-public-popup__backdrop" data-popup-close></div><div class="sonyra-public-popup__dialog" role="dialog" aria-modal="true" aria-labelledby="' . esc_attr( $dialog_id ) . '"><button type="button" class="sonyra-public-popup__close" data-popup-close aria-label="' . esc_attr( self::text( 'manager.pages.popups.close' ) ) . '">' . esc_html( self::text( 'manager.pages.popups.close' ) ) . '</button><div class="sonyra-public-popup__content">' . $meta . ( '' !== $heading ? '<h2 id="' . esc_attr( $dialog_id ) . '">' . esc_html( $heading ) . '</h2>' : '' ) . $body . '</div></div></div>';
	}

	private static function render_popup_runtime_script(): void {
		?>
		<script>
		document.addEventListener('DOMContentLoaded', function () {
			var root = document.querySelector('[data-sonyra-public-popups]');
			if (!root) {
				return;
			}

			var popups = Array.prototype.slice.call(root.querySelectorAll('.sonyra-public-popup'));
			if (!popups.length) {
				return;
			}

			var scrollOpened = {};
			var timers = [];

			function openPopup(popup) {
				if (!popup) {
					return;
				}

				popup.hidden = false;
				popup.setAttribute('aria-hidden', 'false');
				popup.classList.add('is-open');
			}

			function closePopup(popup) {
				if (!popup) {
					return;
				}

				popup.classList.remove('is-open');
				popup.setAttribute('aria-hidden', 'true');
				popup.hidden = true;
			}

			function bindPopup(popup) {
				Array.prototype.slice.call(popup.querySelectorAll('[data-popup-close]')).forEach(function (closeNode) {
					closeNode.addEventListener('click', function () {
						closePopup(popup);
					});
				});
			}

			function scheduleByTrigger(popup) {
				var trigger = popup.getAttribute('data-popup-trigger') || 'manual';
				var openDelay = trigger === 'delay' ? 3000 : 400;

				if (trigger === 'manual') {
					return;
				}

				if (trigger === 'page_load' || trigger === 'first_visit') {
					timers.push(window.setTimeout(function () {
						openPopup(popup);
					}, 400));
					return;
				}

				if (trigger === 'delay') {
					timers.push(window.setTimeout(function () {
						openPopup(popup);
					}, openDelay));
					return;
				}

				if (trigger === 'scroll') {
					var onScroll = function () {
						var threshold = Math.max(300, Math.round((document.documentElement.scrollHeight - window.innerHeight) * 0.35));
						var current = window.scrollY || window.pageYOffset || 0;

						if (current >= threshold && !scrollOpened[popup.getAttribute('data-popup-id') || '']) {
							scrollOpened[popup.getAttribute('data-popup-id') || ''] = true;
							openPopup(popup);
							window.removeEventListener('scroll', onScroll);
						}
					};

					window.addEventListener('scroll', onScroll, { passive: true });
					onScroll();
					return;
				}

				if (trigger === 'exit_intent') {
					var onMouseOut = function (event) {
						if (event.clientY <= 0 && window.innerWidth > 1024) {
							openPopup(popup);
							document.removeEventListener('mouseout', onMouseOut);
						}
					};

					document.addEventListener('mouseout', onMouseOut);
				}
			}

			popups.forEach(function (popup) {
				bindPopup(popup);
				scheduleByTrigger(popup);
			});

			document.addEventListener('keydown', function (event) {
				if (event.key !== 'Escape') {
					return;
				}

				popups.forEach(function (popup) {
					if (popup.classList.contains('is-open')) {
						closePopup(popup);
					}
				});
			});
		});
		</script>
		<?php
	}

	private static function render_safe_404(): void {
		if ( function_exists( 'status_header' ) ) {
			status_header( 404 );
		}

		if ( function_exists( 'nocache_headers' ) ) {
			nocache_headers();
		}
	}

	private static function render_stylesheet_link(): void {
		$version = defined( 'SONYRA_SITE_MANAGER_VERSION' ) ? SONYRA_SITE_MANAGER_VERSION : '1';

		if ( defined( 'SONYRA_SITE_MANAGER_FILE' ) ) {
			$css_url = plugins_url( 'assets/css/sonyra-manager.css', SONYRA_SITE_MANAGER_FILE );
			?>
	<link rel="stylesheet" href="<?php echo esc_url( $css_url ); ?>?ver=<?php echo esc_attr( $version ); ?>">
			<?php
		}
	}

	private static function text( string $key ): string {
		if ( class_exists( 'Sonyra_Site_Manager_I18n' ) ) {
			return Sonyra_Site_Manager_I18n::t( $key );
		}

		return '';
	}

	private static function get_safe_url( string $url ): string {
		$url = trim( $url );

		if ( '' === $url ) {
			return '';
		}

		if ( preg_match( '/^\s*javascript:/i', $url ) ) {
			return '';
		}

		return esc_url_raw( $url );
	}

	private static function get_tel_href( string $phone ): string {
		$phone = trim( $phone );

		if ( '' === $phone ) {
			return '';
		}

		$normalized = preg_replace( '/[^0-9+]/', '', $phone );

		if ( '' === $normalized ) {
			return '';
		}

		return 'tel:' . $normalized;
	}

	private static function is_widget_enabled( array $widget ): bool {
		if ( ! array_key_exists( 'enabled', $widget ) ) {
			return true;
		}

		$enabled = $widget['enabled'];

		if ( is_bool( $enabled ) ) {
			return $enabled;
		}

		if ( is_numeric( $enabled ) ) {
			return 1 === (int) $enabled;
		}

		if ( is_string( $enabled ) ) {
			$enabled = strtolower( trim( $enabled ) );

			if ( in_array( $enabled, array( '1', 'true', 'yes', 'on' ), true ) ) {
				return true;
			}

			if ( in_array( $enabled, array( '0', 'false', 'no', 'off', '' ), true ) ) {
				return false;
			}
		}

		return ! empty( $enabled );
	}

	private static function sanitize_widget_position( string $position ): string {
		$position = sanitize_key( $position );

		if ( in_array( $position, array( 'bottom_right', 'bottom_left', 'top_right', 'top_left' ), true ) ) {
			return $position;
		}

		return 'bottom_right';
	}

	private static function get_block_link_label( array $block ): string {
		foreach ( array( 'link_label', 'file_label', 'button_label', 'heading', 'name', 'platform' ) as $key ) {
			if ( ! empty( $block[ $key ] ) ) {
				return (string) $block[ $key ];
			}
		}

		return self::text( 'manager.pages.blocks.unavailable_type' );
	}

	private static function get_section_blocks( array $section ): array {
		if ( isset( $section['blocks'] ) && is_array( $section['blocks'] ) && ! empty( $section['blocks'] ) ) {
			return $section['blocks'];
		}

		if ( self::has_legacy_section_content( $section ) ) {
			return array(
				array(
					'type'         => isset( $section['type'] ) ? (string) $section['type'] : 'text',
					'kicker'       => isset( $section['kicker'] ) ? (string) $section['kicker'] : '',
					'heading'      => isset( $section['title'] ) ? (string) $section['title'] : '',
					'text'         => isset( $section['text'] ) ? (string) $section['text'] : '',
					'description'  => isset( $section['text'] ) ? (string) $section['text'] : '',
					'button_label' => isset( $section['button_label'] ) ? (string) $section['button_label'] : '',
					'button_url'   => isset( $section['button_url'] ) ? (string) $section['button_url'] : '',
					'email'        => isset( $section['email'] ) ? (string) $section['email'] : '',
					'phone'        => isset( $section['phone'] ) ? (string) $section['phone'] : '',
					'address'      => isset( $section['address'] ) ? (string) $section['address'] : '',
				),
			);
		}

		return array();
	}

	private static function get_section_heading( array $section, array $blocks ): string {
		if ( isset( $blocks[0]['heading'] ) && '' !== (string) $blocks[0]['heading'] ) {
			return (string) $blocks[0]['heading'];
		}

		if ( ! empty( $section['name'] ) ) {
			return (string) $section['name'];
		}

		$definition = self::get_section_definition( isset( $section['type'] ) ? (string) $section['type'] : '' );

		return isset( $definition['label'] ) ? (string) $definition['label'] : '';
	}

	private static function get_section_definition( string $type ): array {
		if ( class_exists( 'Sonyra_Site_Manager_Page_Elements_Library' ) ) {
			return Sonyra_Site_Manager_Page_Elements_Library::get_section_definition( $type );
		}

		return array();
	}

	private static function should_skip_unavailable_section( array $section ): bool {
		$type = isset( $section['type'] ) ? sanitize_key( (string) $section['type'] ) : '';

		if ( empty( $section['unavailable'] ) ) {
			return false;
		}

		return empty( self::get_section_definition( $type ) );
	}

	private static function has_legacy_section_content( array $section ): bool {
		foreach ( array( 'kicker', 'title', 'text', 'button_label', 'button_url', 'email', 'phone', 'address' ) as $key ) {
			if ( ! empty( $section[ $key ] ) ) {
				return true;
			}
		}

		return false;
	}
}
