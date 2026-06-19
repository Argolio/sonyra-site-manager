<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Sonyra_Site_Manager_I18n {

	/**
	 * @var array|null
	 */
	private static $ru_strings = null;

	public static function get_current_locale(): string {
		if ( class_exists( 'Sonyra_Site_Manager_Language_Packs' ) ) {
			return Sonyra_Site_Manager_Language_Packs::get_default_locale();
		}

		return 'ru_RU';
	}

	public static function get_fallback_locale(): string {
		if ( class_exists( 'Sonyra_Site_Manager_Language_Packs' ) ) {
			return Sonyra_Site_Manager_Language_Packs::get_fallback_locale();
		}

		return 'ru_RU';
	}

	public static function normalize_locale( $locale ): string {
		if ( class_exists( 'Sonyra_Site_Manager_Language_Packs' ) ) {
			return Sonyra_Site_Manager_Language_Packs::normalize_locale( $locale );
		}

		return 'ru_RU';
	}

	public static function get_ru_source_path(): string {
		return SONYRA_SITE_MANAGER_DIR . 'includes/i18n/ru.php';
	}

	public static function get_ru_strings(): array {
		if ( null !== self::$ru_strings ) {
			return self::$ru_strings;
		}

		$source_path = self::get_ru_source_path();

		if ( ! file_exists( $source_path ) ) {
			self::$ru_strings = array();

			return self::$ru_strings;
		}

		$strings = require $source_path;

		if ( ! is_array( $strings ) ) {
			self::$ru_strings = array();

			return self::$ru_strings;
		}

		self::$ru_strings = $strings;

		return self::$ru_strings;
	}

	public static function get_strings( string $locale = '' ): array {
		$locale = self::normalize_locale( $locale );

		if ( 'ru_RU' === $locale || 'ru' === $locale ) {
			return self::get_ru_strings();
		}

		return self::get_ru_strings();
	}

	public static function get_payload( string $locale = '' ): array {
		$current_locale  = self::normalize_locale( $locale );
		$fallback_locale = self::get_fallback_locale();

		return array(
			'currentLocale'  => $current_locale,
			'fallbackLocale' => $fallback_locale,
			'dictionary'     => self::get_strings( $current_locale ),
		);
	}

	public static function t( string $key, string $fallback = '', string $locale = '' ): string {
		$strings = self::get_strings( $locale );

		if ( array_key_exists( $key, $strings ) && is_string( $strings[ $key ] ) ) {
			return $strings[ $key ];
		}

		$fallback_strings = self::get_strings( self::get_fallback_locale() );

		if ( array_key_exists( $key, $fallback_strings ) && is_string( $fallback_strings[ $key ] ) ) {
			return $fallback_strings[ $key ];
		}

		return $fallback;
	}

	public static function has( string $key, string $locale = '' ): bool {
		$strings = self::get_strings( $locale );

		return array_key_exists( $key, $strings ) && is_string( $strings[ $key ] );
	}

	public static function get_status(): array {
		$required_login_keys = array(
			'login.title_identifier',
			'login.title_code',
			'login.title_success',
			'login.title_error',
			'login.identifier_label',
			'login.request_button',
		);

		$login_strings_ready = true;

		foreach ( $required_login_keys as $key ) {
			if ( ! self::has( $key ) ) {
				$login_strings_ready = false;
				break;
			}
		}

		return array(
			'i18n_ready'          => file_exists( self::get_ru_source_path() ) && count( self::get_ru_strings() ) > 0,
			'i18n_source'         => self::get_ru_source_path(),
			'current_locale'      => self::get_current_locale(),
			'fallback_locale'     => self::get_fallback_locale(),
			'ru_strings_count'    => count( self::get_ru_strings() ),
			'login_strings_ready' => $login_strings_ready,
		);
	}
}
