<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Sonyra_Site_Manager_Database {

	public static function get_db_version(): string {
		return SONYRA_SITE_MANAGER_DB_VERSION;
	}

	public static function get_table_names(): array {
		global $wpdb;

		return array(
			'sites'             => $wpdb->prefix . 'sonyra_sites',
			'pages'             => $wpdb->prefix . 'sonyra_pages',
			'page_revisions'    => $wpdb->prefix . 'sonyra_page_revisions',
			'page_blocks'       => $wpdb->prefix . 'sonyra_page_blocks',
			'global_settings'   => $wpdb->prefix . 'sonyra_global_settings',
			'brand_assets'      => $wpdb->prefix . 'sonyra_brand_assets',
			'logo_settings'     => $wpdb->prefix . 'sonyra_logo_settings',
			'design_tokens'     => $wpdb->prefix . 'sonyra_design_tokens',
			'gradient_presets'  => $wpdb->prefix . 'sonyra_gradient_presets',
			'header_configs'    => $wpdb->prefix . 'sonyra_header_configs',
			'footer_configs'    => $wpdb->prefix . 'sonyra_footer_configs',
			'modules'           => $wpdb->prefix . 'sonyra_modules',
			'embeds'            => $wpdb->prefix . 'sonyra_embeds',
			'audit_log'         => $wpdb->prefix . 'sonyra_audit_log',
			'locks'             => $wpdb->prefix . 'sonyra_locks',
			'user_permissions'  => $wpdb->prefix . 'sonyra_user_permissions',
			'events'            => $wpdb->prefix . 'sonyra_events',
			'analytics_daily'   => $wpdb->prefix . 'sonyra_analytics_daily',
			'version_log'       => $wpdb->prefix . 'sonyra_version_log',
			'auth_identities'   => $wpdb->prefix . 'sonyra_auth_identities',
			'auth_otp_challenges' => $wpdb->prefix . 'sonyra_auth_otp_challenges',
			'auth_sessions'     => $wpdb->prefix . 'sonyra_auth_sessions',
			'auth_attempts'     => $wpdb->prefix . 'sonyra_auth_attempts',
			'auth_trusted_devices' => $wpdb->prefix . 'sonyra_auth_trusted_devices',
			'auth_providers'    => $wpdb->prefix . 'sonyra_auth_providers',
		);
	}

	public static function get_table_name( string $key ): string {
		$table_names = self::get_table_names();

		if ( ! isset( $table_names[ $key ] ) ) {
			return '';
		}

		return $table_names[ $key ];
	}

	public static function get_charset_collate(): string {
		global $wpdb;

		return $wpdb->get_charset_collate();
	}

	public static function get_required_table_keys(): array {
		return array_keys( self::get_table_names() );
	}

	public static function tables_exist(): array {
		global $wpdb;

		$required = self::get_required_table_keys();
		$existing = array();
		$missing  = array();

		foreach ( self::get_table_names() as $key => $table_name ) {
			$found_table = (string) $wpdb->get_var(
				$wpdb->prepare( 'SHOW TABLES LIKE %s', $table_name )
			);

			if ( $found_table === $table_name ) {
				$existing[] = $key;
				continue;
			}

			$missing[] = $key;
		}

		return array(
			'required' => $required,
			'existing' => $existing,
			'missing'  => $missing,
			'is_ready' => empty( $missing ),
		);
	}
}
