<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Sonyra_Site_Manager_Auth_Providers {

	const PROVIDER_EMAIL   = 'email';
	const PROVIDER_VK      = 'vk';
	const PROVIDER_MAX     = 'max';
	const PROVIDER_YANDEX  = 'yandex';
	const PROVIDER_TOTP    = 'totp';
	const PROVIDER_PASSKEY = 'passkey';

	public static function get_default_providers(): array {
		return array(
			self::PROVIDER_EMAIL   => array(
				'title'         => 'Электронная почта',
				'provider_type' => 'otp',
				'status'        => 'active',
			),
			self::PROVIDER_VK      => array(
				'title'         => 'VK ID',
				'provider_type' => 'external',
				'status'        => 'disabled',
			),
			self::PROVIDER_MAX     => array(
				'title'         => 'MAX',
				'provider_type' => 'external',
				'status'        => 'disabled',
			),
			self::PROVIDER_YANDEX  => array(
				'title'         => 'Яндекс ID',
				'provider_type' => 'external',
				'status'        => 'disabled',
			),
			self::PROVIDER_TOTP    => array(
				'title'         => 'Приложение с кодами',
				'provider_type' => 'otp',
				'status'        => 'disabled',
			),
			self::PROVIDER_PASSKEY => array(
				'title'         => 'Ключ доступа',
				'provider_type' => 'passkey',
				'status'        => 'disabled',
			),
		);
	}

	public static function provider_exists( string $provider_key ): bool {
		return isset( self::get_default_providers()[ $provider_key ] );
	}

	public static function get_provider( string $provider_key ): array {
		$providers = self::get_default_providers();

		if ( ! isset( $providers[ $provider_key ] ) ) {
			return array();
		}

		return $providers[ $provider_key ];
	}

	public static function sync_default_providers(): bool {
		global $wpdb;

		$table_name = self::get_table_name();

		if ( '' === $table_name || ! self::table_exists( $table_name ) ) {
			return false;
		}

		$success = true;

		foreach ( self::get_default_providers() as $provider_key => $provider ) {
			$current_time = self::get_current_time();
			$existing_id  = self::get_provider_id( $table_name, $provider_key );
			$settings     = self::json_encode( array( 'managed_by' => 'auth_foundation' ) );

			if ( $existing_id > 0 ) {
				$result = $wpdb->update(
					$table_name,
					array(
						'title'         => $provider['title'],
						'provider_type' => $provider['provider_type'],
						'status'        => $provider['status'],
						'settings'      => $settings,
						'updated_at'    => $current_time,
					),
					array( 'id' => $existing_id )
				);

				$success = false !== $result && $success;
				continue;
			}

			$result = $wpdb->insert(
				$table_name,
				array(
					'provider_key'  => $provider_key,
					'title'         => $provider['title'],
					'provider_type' => $provider['provider_type'],
					'status'        => $provider['status'],
					'settings'      => $settings,
					'created_at'    => $current_time,
					'updated_at'    => $current_time,
				)
			);

			$success = false !== $result && $success;
		}

		return $success;
	}

	public static function is_email_provider_ready(): bool {
		global $wpdb;

		$table_name = self::get_table_name();

		if ( '' === $table_name || ! self::table_exists( $table_name ) ) {
			return false;
		}

		$provider_id = (int) $wpdb->get_var(
			$wpdb->prepare(
				'SELECT id FROM ' . self::quote_identifier( $table_name ) . ' WHERE provider_key = %s AND status = %s LIMIT 1',
				self::PROVIDER_EMAIL,
				'active'
			)
		);

		return $provider_id > 0;
	}

	private static function get_provider_id( string $table_name, string $provider_key ): int {
		global $wpdb;

		return (int) $wpdb->get_var(
			$wpdb->prepare(
				'SELECT id FROM ' . self::quote_identifier( $table_name ) . ' WHERE provider_key = %s LIMIT 1',
				$provider_key
			)
		);
	}

	private static function get_table_name(): string {
		if ( ! class_exists( 'Sonyra_Site_Manager_Database' ) ) {
			return '';
		}

		return Sonyra_Site_Manager_Database::get_table_name( 'auth_providers' );
	}

	private static function table_exists( string $table_name ): bool {
		global $wpdb;

		$found_table = (string) $wpdb->get_var(
			$wpdb->prepare( 'SHOW TABLES LIKE %s', $table_name )
		);

		return $found_table === $table_name;
	}

	private static function quote_identifier( string $identifier ): string {
		return '`' . str_replace( '`', '``', $identifier ) . '`';
	}

	private static function json_encode( array $value ): string {
		if ( function_exists( 'wp_json_encode' ) ) {
			return (string) wp_json_encode( $value );
		}

		return (string) json_encode( $value );
	}

	private static function get_current_time(): string {
		if ( function_exists( 'current_time' ) ) {
			return current_time( 'mysql' );
		}

		return date( 'Y-m-d H:i:s' );
	}
}
