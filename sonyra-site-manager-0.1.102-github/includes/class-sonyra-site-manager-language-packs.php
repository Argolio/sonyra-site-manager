<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Sonyra_Site_Manager_Language_Packs {

	public static function get_default_locale(): string {
		return 'ru_RU';
	}

	public static function get_fallback_locale(): string {
		return 'ru_RU';
	}

	public static function normalize_locale( $locale ): string {
		$locale = is_string( $locale ) ? trim( $locale ) : '';

		if ( '' === $locale ) {
			return self::get_default_locale();
		}

		$locale = preg_replace( '/[^A-Za-z_]/', '', $locale );

		if ( ! is_string( $locale ) || '' === $locale ) {
			return self::get_default_locale();
		}

		$parts = explode( '_', $locale );
		$parts = array_values( array_filter( $parts, 'strlen' ) );

		if ( empty( $parts ) ) {
			return self::get_default_locale();
		}

		$language = strtolower( $parts[0] );
		$region   = isset( $parts[1] ) ? strtoupper( $parts[1] ) : '';

		return '' !== $region ? $language . '_' . $region : $language;
	}

	public static function get_available_language_packs(): array {
		return array(
			array(
				'locale'      => 'ru_RU',
				'label'       => 'ru_RU',
				'is_default'  => true,
				'is_fallback' => true,
				'is_active'   => true,
				'source'      => 'core',
				'file'        => 'includes/i18n/ru.php',
			),
		);
	}

	public static function get_language_pack_contract(): array {
		return array(
			'defaultLocale' => self::get_default_locale(),
			'fallbackLocale' => self::get_fallback_locale(),
			'activeLocale' => self::get_default_locale(),
			'availablePacks' => self::get_available_language_packs(),
		);
	}
}
