<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Sonyra_Site_Manager_Icon_Registry {

	const MANIFEST_PATH = 'assets/icons/sonyra-icons-manifest.json';
	const ICON_BASE_PATH = 'assets/icons/tabler/outline';
	const FALLBACK_ICON_KEY = 'circle-dot';

	private static $icons = null;

	public static function get_icons(): array {
		if ( null !== self::$icons ) {
			return self::$icons;
		}

		$manifest_path = SONYRA_SITE_MANAGER_DIR . self::MANIFEST_PATH;

		if ( ! file_exists( $manifest_path ) ) {
			self::$icons = array();

			return self::$icons;
		}

		$decoded = json_decode( (string) file_get_contents( $manifest_path ), true );

		if ( ! is_array( $decoded ) ) {
			self::$icons = array();

			return self::$icons;
		}

		$icons = array();

		foreach ( $decoded as $item ) {
			if ( ! is_array( $item ) || empty( $item['key'] ) || ! is_string( $item['key'] ) ) {
				continue;
			}

			$key = $item['key'];

			if ( ! self::is_valid_key( $key ) || isset( $icons[ $key ] ) ) {
				continue;
			}

			$icons[ $key ] = $item;
		}

		self::$icons = $icons;

		return self::$icons;
	}

	public static function is_valid_key( string $icon_key ): bool {
		return 1 === preg_match( '/\\A[a-z0-9]+(?:-[a-z0-9]+)*\\z/', $icon_key );
	}

	public static function has( string $icon_key ): bool {
		$icons = self::get_icons();

		return self::is_valid_key( $icon_key ) && isset( $icons[ $icon_key ] );
	}

	public static function get( string $icon_key, bool $allow_fallback = true ): array {
		$icons = self::get_icons();

		if ( self::is_valid_key( $icon_key ) && isset( $icons[ $icon_key ] ) ) {
			return $icons[ $icon_key ];
		}

		if ( $allow_fallback && isset( $icons[ self::FALLBACK_ICON_KEY ] ) ) {
			return $icons[ self::FALLBACK_ICON_KEY ];
		}

		return array();
	}

	public static function get_svg_path( string $icon_key, bool $allow_fallback = true ): string {
		$icon = self::get( $icon_key, $allow_fallback );

		if ( empty( $icon['file'] ) || ! is_string( $icon['file'] ) ) {
			return '';
		}

		$file = $icon['file'];

		if (
			false !== strpos( $file, '../' ) ||
			false !== strpos( $file, '..\\' ) ||
			false !== strpos( $file, "\0" ) ||
			false !== strpos( $file, '://' ) ||
			false !== strpos( $file, '?' ) ||
			0 !== strpos( $file, 'tabler/outline/' )
		) {
			return '';
		}

		$base_dir = realpath( SONYRA_SITE_MANAGER_DIR . self::ICON_BASE_PATH );
		$path = realpath( SONYRA_SITE_MANAGER_DIR . 'assets/icons/' . $file );

		if ( false === $base_dir || false === $path ) {
			return '';
		}

		$base_dir = rtrim( str_replace( '\\', '/', $base_dir ), '/' ) . '/';
		$path_normalized = str_replace( '\\', '/', $path );

		if ( 0 !== strpos( $path_normalized, $base_dir ) || 'svg' !== strtolower( pathinfo( $path_normalized, PATHINFO_EXTENSION ) ) ) {
			return '';
		}

		return $path;
	}
}
