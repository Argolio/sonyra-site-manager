<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Sonyra_Site_Manager_Icon_Renderer {

	private static $svg_cache = array();

	public static function render( string $icon_key, array $args = array() ): string {
		if ( ! class_exists( 'Sonyra_Site_Manager_Icon_Registry' ) ) {
			return '';
		}

		$path = Sonyra_Site_Manager_Icon_Registry::get_svg_path( $icon_key, true );

		if ( '' === $path ) {
			return '';
		}

		$actual_key = basename( $path, '.svg' );
		$inner_svg = self::get_sanitized_inner_svg( $actual_key, $path );

		if ( '' === $inner_svg ) {
			return '';
		}

		$class = isset( $args['class'] ) && is_string( $args['class'] ) ? $args['class'] : 'sonyra-icon';
		$class = self::sanitize_class_list( $class );
		$aria_hidden = array_key_exists( 'aria_hidden', $args ) ? (bool) $args['aria_hidden'] : true;
		$aria_label = isset( $args['aria_label'] ) && is_string( $args['aria_label'] ) ? trim( $args['aria_label'] ) : '';
		$size = isset( $args['size'] ) ? absint( $args['size'] ) : 0;

		$attributes = array(
			'class'               => $class,
			'viewBox'             => '0 0 24 24',
			'fill'                => 'none',
			'stroke'              => 'currentColor',
			'stroke-width'        => '2',
			'stroke-linecap'      => 'round',
			'stroke-linejoin'     => 'round',
			'focusable'           => 'false',
			'xmlns'               => 'http://www.w3.org/2000/svg',
		);

		if ( $size > 0 ) {
			$attributes['width'] = (string) $size;
			$attributes['height'] = (string) $size;
		}

		if ( $aria_hidden || '' === $aria_label ) {
			$attributes['aria-hidden'] = 'true';
		} else {
			$attributes['role'] = 'img';
			$attributes['aria-label'] = $aria_label;
		}

		$attribute_html = '';

		foreach ( $attributes as $name => $value ) {
			$attribute_html .= ' ' . $name . '="' . esc_attr( $value ) . '"';
		}

		return '<svg' . $attribute_html . '>' . $inner_svg . '</svg>';
	}

	public static function display( string $icon_key, array $args = array() ): void {
		echo self::render( $icon_key, $args );
	}

	private static function get_sanitized_inner_svg( string $icon_key, string $path ): string {
		if ( isset( self::$svg_cache[ $icon_key ] ) ) {
			return self::$svg_cache[ $icon_key ];
		}

		$raw_svg = (string) file_get_contents( $path );
		$inner_svg = self::extract_inner_svg( $raw_svg );

		if ( '' === $inner_svg ) {
			self::$svg_cache[ $icon_key ] = '';

			return '';
		}

		$inner_svg = self::remove_unsafe_svg_content( $inner_svg );

		if ( function_exists( 'wp_kses' ) ) {
			$inner_svg = wp_kses( $inner_svg, self::get_allowed_svg_tags() );
		} else {
			$inner_svg = strip_tags( $inner_svg, '<g><path><rect><circle><line><polyline><polygon><ellipse>' );
		}

		self::$svg_cache[ $icon_key ] = trim( $inner_svg );

		return self::$svg_cache[ $icon_key ];
	}

	private static function extract_inner_svg( string $raw_svg ): string {
		if ( 1 !== preg_match( '/<svg\\b[^>]*>(.*)<\\/svg>/is', $raw_svg, $matches ) ) {
			return '';
		}

		return trim( (string) $matches[1] );
	}

	private static function remove_unsafe_svg_content( string $svg ): string {
		$svg = preg_replace( '/<\\s*(script|style|foreignObject|iframe|object|embed|image|use)\\b[^>]*>.*?<\\s*\\/\\s*\\1\\s*>/is', '', $svg );
		$svg = preg_replace( '/<\\s*(script|style|foreignObject|iframe|object|embed|image|use)\\b[^>]*\\/?>/is', '', $svg );
		$svg = preg_replace( "/\\s+on[a-z0-9_-]+\\s*=\\s*(\"[^\"]*\"|'[^']*'|[^\\s>]+)/i", '', $svg );
		$svg = preg_replace( "/\\s+(href|xlink:href)\\s*=\\s*(\"[^\"]*\"|'[^']*'|[^\\s>]+)/i", '', $svg );

		return is_string( $svg ) ? $svg : '';
	}

	private static function get_allowed_svg_tags(): array {
		$shape_attributes = array(
			'd'                 => true,
			'x'                 => true,
			'y'                 => true,
			'x1'                => true,
			'y1'                => true,
			'x2'                => true,
			'y2'                => true,
			'cx'                => true,
			'cy'                => true,
			'r'                 => true,
			'rx'                => true,
			'ry'                => true,
			'width'             => true,
			'height'            => true,
			'points'            => true,
			'fill'              => true,
			'stroke'            => true,
			'stroke-width'      => true,
			'stroke-linecap'    => true,
			'stroke-linejoin'   => true,
			'stroke-dasharray'  => true,
			'stroke-dashoffset' => true,
			'opacity'           => true,
			'transform'         => true,
		);

		return array(
			'g'        => $shape_attributes,
			'path'     => $shape_attributes,
			'rect'     => $shape_attributes,
			'circle'   => $shape_attributes,
			'line'     => $shape_attributes,
			'polyline' => $shape_attributes,
			'polygon'  => $shape_attributes,
			'ellipse'  => $shape_attributes,
		);
	}

	private static function sanitize_class_list( string $class_list ): string {
		$classes = preg_split( '/\\s+/', trim( $class_list ) );
		$clean = array();

		if ( ! is_array( $classes ) ) {
			return 'sonyra-icon';
		}

		foreach ( $classes as $class ) {
			if ( '' === $class ) {
				continue;
			}

			$clean[] = function_exists( 'sanitize_html_class' ) ? sanitize_html_class( $class ) : preg_replace( '/[^A-Za-z0-9_-]/', '', $class );
		}

		$clean = array_filter( array_unique( $clean ) );

		return empty( $clean ) ? 'sonyra-icon' : implode( ' ', $clean );
	}
}
