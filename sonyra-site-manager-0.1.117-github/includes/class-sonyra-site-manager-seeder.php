<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Sonyra_Site_Manager_Seeder {

	public static function get_seed_version(): string {
		return SONYRA_SITE_MANAGER_SEED_VERSION;
	}

	public static function seed_defaults(): bool {
		if ( ! class_exists( 'Sonyra_Site_Manager_Database' ) ) {
			self::log_seed_event(
				'core.seed_failed',
				array(
					'reason' => 'database_service_unavailable',
				),
				'error',
				true
			);

			return false;
		}

		$table_status = Sonyra_Site_Manager_Database::tables_exist();

		if ( empty( $table_status['is_ready'] ) ) {
			self::log_seed_event(
				'core.seed_skipped',
				array(
					'reason'         => 'database_tables_not_ready',
					'missing_tables' => $table_status['missing'],
				),
				'warning',
				true
			);

			return false;
		}

		$previous_seed_version = (string) get_option( 'sonyra_site_manager_seed_version', '' );
		$required_seed_version = self::get_seed_version();

		if ( $required_seed_version === $previous_seed_version && self::has_required_seed_records() ) {
			return true;
		}

		$records = self::get_default_records();
		$success = self::seed_site( $records['site'] );
		$success = self::seed_global_settings( $records['global_settings'] ) && $success;
		$success = self::seed_module( $records['module'] ) && $success;
		$success = self::seed_design_tokens( $records['design_tokens'] ) && $success;
		$success = self::seed_gradient_preset( $records['gradient_preset'] ) && $success;
		$success = self::seed_header_config( $records['header_config'] ) && $success;
		$success = self::seed_footer_config( $records['footer_config'] ) && $success;
		$success = self::seed_logo_setting( $records['logo_setting'] ) && $success;

		if ( ! $success || ! self::has_required_seed_records() ) {
			self::log_seed_event(
				'core.seed_failed',
				array(
					'reason' => 'seed_records_not_ready',
				),
				'error',
				true
			);

			return false;
		}

		update_option( 'sonyra_site_manager_seed_version', $required_seed_version, false );

		if ( false === get_option( 'sonyra_site_manager_seeded_at', false ) ) {
			add_option( 'sonyra_site_manager_seeded_at', self::get_current_time(), '', false );
		}

		update_option( 'sonyra_site_manager_seed_updated_at', self::get_current_time(), false );

		self::insert_seed_version_log( $previous_seed_version, $required_seed_version, self::count_default_records( $records ) );

		self::log_seed_event(
			'core.seed_completed',
			array(
				'previous_seed_version' => $previous_seed_version,
				'new_seed_version'      => $required_seed_version,
				'records_count'         => self::count_default_records( $records ),
			),
			'info',
			false
		);

		return true;
	}

	public static function maybe_seed(): void {
		$seed_status = self::get_seed_status();

		if ( ! empty( $seed_status['is_ready'] ) ) {
			return;
		}

		self::seed_defaults();
	}

	public static function has_required_seed_records(): bool {
		if ( ! class_exists( 'Sonyra_Site_Manager_Database' ) ) {
			return false;
		}

		return self::record_exists( 'sites', 'site_key', 'default' )
			&& self::record_exists( 'global_settings', 'setting_key', 'product_name' )
			&& self::record_exists( 'global_settings', 'setting_key', 'platform_name' )
			&& self::record_exists( 'global_settings', 'setting_key', 'publisher' )
			&& self::record_exists( 'global_settings', 'setting_key', 'licensor' )
			&& self::record_exists( 'modules', 'module_id', 'core' )
			&& self::record_exists( 'design_tokens', 'token_key', 'color.brand.primary' )
			&& self::record_exists( 'design_tokens', 'token_key', 'radius.card' )
			&& self::record_exists( 'design_tokens', 'token_key', 'shadow.card' )
			&& self::record_exists( 'design_tokens', 'token_key', 'space.section' )
			&& self::record_exists( 'gradient_presets', 'preset_key', 'sonyra_soft_light' )
			&& self::record_exists( 'header_configs', 'config_key', 'default' )
			&& self::record_exists( 'footer_configs', 'config_key', 'default' )
			&& self::record_exists( 'logo_settings', 'logo_key', 'main' );
	}

	public static function get_seed_status(): array {
		$required_seed_version = self::get_seed_version();
		$installed_seed_version = (string) get_option( 'sonyra_site_manager_seed_version', '' );
		$has_required_records = self::has_required_seed_records();
		$matches = '' !== $required_seed_version && $required_seed_version === $installed_seed_version;

		return array(
			'required_seed_version' => $required_seed_version,
			'installed_seed_version' => $installed_seed_version,
			'matches' => $matches,
			'has_required_records' => $has_required_records,
			'seeded_at' => (string) get_option( 'sonyra_site_manager_seeded_at', '' ),
			'seed_updated_at' => (string) get_option( 'sonyra_site_manager_seed_updated_at', '' ),
			'is_ready' => $matches && $has_required_records,
		);
	}

	public static function get_default_records(): array {
		return array(
			'site' => array(
				'site_key' => 'default',
				'title'    => 'Основной сайт',
				'status'   => 'active',
				'settings' => array(
					'scenario' => 'ngo',
					'language' => 'ru',
					'platform' => 'SONYRA Site Platform',
					'product'  => 'Пульт сайта',
				),
			),
			'global_settings' => array(
				'product_name'             => 'Пульт сайта',
				'platform_name'            => 'SONYRA Site Platform',
				'publisher'                => 'SONYRA STUDIO',
				'licensor'                 => 'ИП Катаев С.А. / IPKTFSA',
				'technical_name'           => 'sonyra-site-manager',
				'release_channel'          => 'manual_zip',
				'build_type'               => 'foundation',
				'default_language'         => 'ru',
				'visible_ui_language'      => 'ru',
				'private_updater_endpoint' => 'https://updates.dobromap.ru/public/index.php?action=check',
				'manual_zip_workflow'      => 'enabled',
			),
			'module' => array(
				'module_id' => 'core',
				'title'     => 'Ядро платформы',
				'version'   => SONYRA_SITE_MANAGER_VERSION,
				'status'    => 'active',
				'manifest'  => array(
					'type'                => 'core',
					'provides'            => array(
						'database',
						'status',
						'audit',
						'permissions',
						'seed',
					),
					'visible_title'       => 'Ядро платформы',
					'visible_description' => 'Базовые возможности платформы без пользовательского интерфейса.',
				),
			),
			'design_tokens' => array(
				array(
					'token_key'   => 'color.brand.primary',
					'token_value' => '#6d5dfc',
					'token_group' => 'brand',
				),
				array(
					'token_key'   => 'radius.card',
					'token_value' => '24px',
					'token_group' => 'shape',
				),
				array(
					'token_key'   => 'shadow.card',
					'token_value' => '0 18px 50px rgba(15, 23, 42, 0.10)',
					'token_group' => 'shadow',
				),
				array(
					'token_key'   => 'space.section',
					'token_value' => '32px',
					'token_group' => 'space',
				),
			),
			'gradient_preset' => array(
				'preset_key'    => 'sonyra_soft_light',
				'title'         => 'Мягкий светлый фон',
				'gradient_type' => 'linear',
				'config'        => array(
					'angle'       => 135,
					'stops'       => array(
						'#f8fafc',
						'#eef2ff',
						'#faf5ff',
					),
					'usage_scope' => 'manager_foundation',
				),
				'status'        => 'active',
			),
			'header_config' => array(
				'config_key' => 'default',
				'title'      => 'Базовая шапка',
				'config'     => array(
					'layout'   => 'logo_left',
					'sticky'   => false,
					'logo_key' => 'main',
					'language' => 'ru',
				),
				'status'     => 'active',
			),
			'footer_config' => array(
				'config_key' => 'default',
				'title'      => 'Базовый подвал',
				'config'     => array(
					'layout'    => 'compact',
					'copyright' => '© 2026 SONYRA STUDIO',
					'publisher' => 'SONYRA STUDIO',
					'language'  => 'ru',
				),
				'status'     => 'active',
			),
			'logo_setting' => array(
				'logo_key' => 'main',
				'asset_id' => null,
				'settings' => array(
					'width'         => 160,
					'height'        => null,
					'max_width'     => 220,
					'desktop_width' => 160,
					'tablet_width'  => 140,
					'mobile_width'  => 120,
				),
				'status'   => 'active',
			),
		);
	}

	private static function seed_site( array $record ): bool {
		return self::upsert(
			'sites',
			array( 'site_key' => $record['site_key'] ),
			array(
				'site_key'   => $record['site_key'],
				'title'      => $record['title'],
				'status'     => $record['status'],
				'settings'   => self::json_encode( $record['settings'] ),
				'created_by' => self::get_current_user_id(),
			),
			array(
				'title'    => $record['title'],
				'status'   => $record['status'],
				'settings' => self::json_encode( $record['settings'] ),
			)
		);
	}

	private static function seed_global_settings( array $records ): bool {
		$success = true;

		foreach ( $records as $setting_key => $setting_value ) {
			$success = self::upsert(
				'global_settings',
				array( 'setting_key' => $setting_key ),
				array(
					'setting_key'   => $setting_key,
					'setting_value' => $setting_value,
					'autoload'      => 'no',
				),
				array(
					'setting_value' => $setting_value,
					'autoload'      => 'no',
				)
			) && $success;
		}

		return $success;
	}

	private static function seed_module( array $record ): bool {
		return self::upsert(
			'modules',
			array( 'module_id' => $record['module_id'] ),
			array(
				'module_id' => $record['module_id'],
				'title'     => $record['title'],
				'version'   => $record['version'],
				'status'    => $record['status'],
				'manifest'  => self::json_encode( $record['manifest'] ),
			),
			array(
				'title'    => $record['title'],
				'version'  => $record['version'],
				'status'   => $record['status'],
				'manifest' => self::json_encode( $record['manifest'] ),
			)
		);
	}

	private static function seed_design_tokens( array $records ): bool {
		$success = true;

		foreach ( $records as $record ) {
			$success = self::upsert(
				'design_tokens',
				array( 'token_key' => $record['token_key'] ),
				array(
					'token_key'   => $record['token_key'],
					'token_value' => $record['token_value'],
					'token_group' => $record['token_group'],
					'status'      => 'active',
				),
				array(
					'token_value' => $record['token_value'],
					'token_group' => $record['token_group'],
					'status'      => 'active',
				)
			) && $success;
		}

		return $success;
	}

	private static function seed_gradient_preset( array $record ): bool {
		return self::upsert(
			'gradient_presets',
			array( 'preset_key' => $record['preset_key'] ),
			array(
				'preset_key'    => $record['preset_key'],
				'title'         => $record['title'],
				'gradient_type' => $record['gradient_type'],
				'config'        => self::json_encode( $record['config'] ),
				'status'        => $record['status'],
			),
			array(
				'title'         => $record['title'],
				'gradient_type' => $record['gradient_type'],
				'config'        => self::json_encode( $record['config'] ),
				'status'        => $record['status'],
			)
		);
	}

	private static function seed_header_config( array $record ): bool {
		return self::seed_config_record( 'header_configs', $record );
	}

	private static function seed_footer_config( array $record ): bool {
		return self::seed_config_record( 'footer_configs', $record );
	}

	private static function seed_config_record( string $table_key, array $record ): bool {
		return self::upsert(
			$table_key,
			array( 'config_key' => $record['config_key'] ),
			array(
				'config_key' => $record['config_key'],
				'title'      => $record['title'],
				'config'     => self::json_encode( $record['config'] ),
				'status'     => $record['status'],
			),
			array(
				'title'  => $record['title'],
				'config' => self::json_encode( $record['config'] ),
				'status' => $record['status'],
			)
		);
	}

	private static function seed_logo_setting( array $record ): bool {
		return self::upsert(
			'logo_settings',
			array( 'logo_key' => $record['logo_key'] ),
			array(
				'logo_key' => $record['logo_key'],
				'asset_id' => $record['asset_id'],
				'settings' => self::json_encode( $record['settings'] ),
				'status'   => $record['status'],
			),
			array(
				'asset_id' => $record['asset_id'],
				'settings' => self::json_encode( $record['settings'] ),
				'status'   => $record['status'],
			)
		);
	}

	private static function insert_seed_version_log( string $previous_seed_version, string $new_seed_version, int $records_count ): void {
		global $wpdb;

		$table_name = Sonyra_Site_Manager_Database::get_table_name( 'version_log' );

		if ( '' === $table_name || ! self::table_exists( $table_name ) ) {
			return;
		}

		$wpdb->insert(
			$table_name,
			array(
				'version_type'     => 'seed',
				'previous_version' => '' === $previous_seed_version ? null : $previous_seed_version,
				'new_version'      => $new_seed_version,
				'context'          => self::json_encode(
					array(
						'plugin_version' => SONYRA_SITE_MANAGER_VERSION,
						'records'        => $records_count,
						'source'         => 'seeder',
					)
				),
				'created_at'       => self::get_current_time(),
			),
			array(
				'%s',
				'%s',
				'%s',
				'%s',
				'%s',
			)
		);
	}

	private static function upsert( string $table_key, array $lookup, array $insert_data, array $update_data ): bool {
		global $wpdb;

		$table_name = Sonyra_Site_Manager_Database::get_table_name( $table_key );

		if ( '' === $table_name || empty( $lookup ) || ! self::table_exists( $table_name ) ) {
			return false;
		}

		$current_time = self::get_current_time();
		$existing_id  = self::get_existing_id( $table_name, $lookup );

		if ( $existing_id > 0 ) {
			$update_data['updated_at'] = $current_time;
			$result = $wpdb->update(
				$table_name,
				$update_data,
				array( 'id' => $existing_id )
			);

			return false !== $result;
		}

		$insert_data['created_at'] = $current_time;
		$insert_data['updated_at'] = $current_time;
		$result = $wpdb->insert( $table_name, $insert_data );

		return false !== $result;
	}

	private static function get_existing_id( string $table_name, array $lookup ): int {
		global $wpdb;

		$where_sql = array();
		$values    = array();

		foreach ( $lookup as $field => $value ) {
			$where_sql[] = self::quote_identifier( (string) $field ) . ' = %s';
			$values[]    = (string) $value;
		}

		if ( empty( $where_sql ) ) {
			return 0;
		}

		return (int) $wpdb->get_var(
			$wpdb->prepare(
				'SELECT id FROM ' . self::quote_identifier( $table_name ) . ' WHERE ' . implode( ' AND ', $where_sql ) . ' LIMIT 1',
				$values
			)
		);
	}

	private static function record_exists( string $table_key, string $field, string $value ): bool {
		global $wpdb;

		$table_name = Sonyra_Site_Manager_Database::get_table_name( $table_key );

		if ( '' === $table_name || ! self::table_exists( $table_name ) ) {
			return false;
		}

		$record_id = (int) $wpdb->get_var(
			$wpdb->prepare(
				'SELECT id FROM ' . self::quote_identifier( $table_name ) . ' WHERE ' . self::quote_identifier( $field ) . ' = %s LIMIT 1',
				$value
			)
		);

		return $record_id > 0;
	}

	private static function table_exists( string $table_name ): bool {
		global $wpdb;

		$found_table = (string) $wpdb->get_var(
			$wpdb->prepare( 'SHOW TABLES LIKE %s', $table_name )
		);

		return $found_table === $table_name;
	}

	private static function log_seed_event( string $event_type, array $context, string $severity, bool $dedupe ): void {
		if ( ! class_exists( 'Sonyra_Site_Manager_Audit_Log' ) ) {
			return;
		}

		if ( $dedupe ) {
			$fingerprint = md5( self::json_encode( array( $event_type, $context ) ) );
			$option_name = 'sonyra_site_manager_last_' . str_replace( '.', '_', $event_type ) . '_hash';

			if ( $fingerprint === (string) get_option( $option_name, '' ) ) {
				return;
			}

			update_option( $option_name, $fingerprint, false );
		}

		Sonyra_Site_Manager_Audit_Log::log_event( $event_type, $context, $severity );
	}

	private static function count_default_records( array $records ): int {
		return 1 + count( $records['global_settings'] ) + 1 + count( $records['design_tokens'] ) + 1 + 1 + 1 + 1 + 1;
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

	private static function get_current_user_id(): int {
		if ( function_exists( 'get_current_user_id' ) ) {
			return (int) get_current_user_id();
		}

		return 0;
	}
}
