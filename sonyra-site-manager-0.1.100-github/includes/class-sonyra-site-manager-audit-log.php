<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Sonyra_Site_Manager_Audit_Log {

	const OPTION_NAME = 'sonyra_site_manager_audit_log';
	const MAX_EVENTS  = 200;

	private static $allowed_severities = array(
		'info',
		'warning',
		'error',
		'security',
	);

	private static $secret_keys = array(
		'password',
		'pass',
		'token',
		'secret',
		'api_key',
		'nonce',
		'cookie',
		'authorization',
	);

	public static function log_event( string $event_type, array $context = array(), string $severity = 'info' ): bool {
		$event_type = trim( $event_type );

		if ( '' === $event_type ) {
			return false;
		}

		if ( ! in_array( $severity, self::$allowed_severities, true ) ) {
			$severity = 'info';
		}

		$events = get_option( self::OPTION_NAME, array() );

		if ( ! is_array( $events ) ) {
			$events = array();
		}

		array_unshift(
			$events,
			array(
				'id'         => self::generate_event_id(),
				'event_type' => self::normalize_string( $event_type ),
				'severity'   => $severity,
				'user_id'    => self::get_current_user_id(),
				'created_at' => self::get_current_time(),
				'version'    => SONYRA_SITE_MANAGER_VERSION,
				'source'     => 'core',
				'context'    => self::normalize_context( $context ),
			)
		);

		$events = array_slice( $events, 0, self::MAX_EVENTS );

		return update_option( self::OPTION_NAME, $events, false );
	}

	public static function get_events( int $limit = 50 ): array {
		$events = get_option( self::OPTION_NAME, array() );

		if ( ! is_array( $events ) ) {
			return array();
		}

		$limit = max( 1, min( self::MAX_EVENTS, $limit ) );

		return array_slice( $events, 0, $limit );
	}

	public static function count_events(): int {
		$events = get_option( self::OPTION_NAME, array() );

		if ( ! is_array( $events ) ) {
			return 0;
		}

		return count( $events );
	}

	public static function clear_events(): bool {
		return update_option( self::OPTION_NAME, array(), false );
	}

	public static function normalize_context( $value, int $depth = 0 ) {
		if ( $depth > 4 ) {
			return '[обрезано]';
		}

		if ( is_null( $value ) || is_bool( $value ) || is_int( $value ) || is_float( $value ) ) {
			return $value;
		}

		if ( is_string( $value ) ) {
			return self::normalize_string( $value );
		}

		if ( is_array( $value ) ) {
			$normalized = array();

			foreach ( $value as $key => $item ) {
				if ( self::is_secret_key( $key ) ) {
					$normalized[ $key ] = '[скрыто]';
					continue;
				}

				$normalized[ $key ] = self::normalize_context( $item, $depth + 1 );
			}

			return $normalized;
		}

		return '[неподдерживаемое значение]';
	}

	private static function generate_event_id(): string {
		if ( function_exists( 'wp_generate_uuid4' ) ) {
			return wp_generate_uuid4();
		}

		return uniqid( 'sonyra_', true );
	}

	private static function get_current_user_id(): int {
		if ( function_exists( 'get_current_user_id' ) ) {
			return (int) get_current_user_id();
		}

		return 0;
	}

	private static function get_current_time(): string {
		if ( function_exists( 'current_time' ) ) {
			return current_time( 'mysql' );
		}

		return date( 'Y-m-d H:i:s' );
	}

	private static function normalize_string( string $value ): string {
		$value = trim( $value );

		if ( function_exists( 'mb_substr' ) ) {
			return mb_substr( $value, 0, 500 );
		}

		return substr( $value, 0, 500 );
	}

	private static function is_secret_key( $key ): bool {
		$key = strtolower( (string) $key );

		return in_array( $key, self::$secret_keys, true );
	}
}
