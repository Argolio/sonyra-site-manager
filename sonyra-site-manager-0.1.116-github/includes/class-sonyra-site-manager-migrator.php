<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Sonyra_Site_Manager_Migrator {

	public static function install_or_update_schema(): bool {
		if ( ! class_exists( 'Sonyra_Site_Manager_Database' ) ) {
			return false;
		}

		if ( ! function_exists( 'dbDelta' ) ) {
			require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		}

		$previous_db_version = (string) get_option( 'sonyra_site_manager_db_version', '' );
		$required_db_version = Sonyra_Site_Manager_Database::get_db_version();
		$before_status       = Sonyra_Site_Manager_Database::tables_exist();

		if ( $required_db_version === $previous_db_version && ! empty( $before_status['is_ready'] ) ) {
			return true;
		}

		foreach ( self::get_schema_sql() as $sql ) {
			dbDelta( $sql );
		}

		$after_status = Sonyra_Site_Manager_Database::tables_exist();

		if ( ! empty( $after_status['is_ready'] ) ) {
			update_option( 'sonyra_site_manager_db_version', $required_db_version, false );

			self::insert_version_log(
				$previous_db_version,
				$required_db_version,
				array(
					'missing_before' => $before_status['missing'],
					'missing_after'  => $after_status['missing'],
				)
			);

			self::log_audit_event(
				'core.database_migrated',
				array(
					'previous_db_version' => $previous_db_version,
					'new_db_version'      => $required_db_version,
					'missing_before'      => $before_status['missing'],
					'missing_after'       => $after_status['missing'],
				),
				'info'
			);

			return true;
		}

		self::log_audit_event(
			'core.database_migration_failed',
			array(
				'previous_db_version' => $previous_db_version,
				'required_db_version' => $required_db_version,
				'missing_tables'      => $after_status['missing'],
			),
			'error'
		);

		return false;
	}

	public static function get_schema_sql(): array {
		$tables          = Sonyra_Site_Manager_Database::get_table_names();
		$charset_collate = Sonyra_Site_Manager_Database::get_charset_collate();

		return array(
			'sonyra_sites'             => "CREATE TABLE {$tables['sites']} (
				id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
				site_key VARCHAR(100) NOT NULL,
				title VARCHAR(255) NOT NULL DEFAULT '',
				status VARCHAR(50) NOT NULL DEFAULT 'active',
				settings LONGTEXT NULL,
				created_by BIGINT UNSIGNED NULL,
				created_at DATETIME NULL,
				updated_at DATETIME NULL,
				PRIMARY KEY  (id),
				KEY site_key (site_key),
				KEY status (status)
			) {$charset_collate};",
			'sonyra_pages'             => "CREATE TABLE {$tables['pages']} (
				id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
				site_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
				slug VARCHAR(200) NOT NULL,
				title VARCHAR(255) NOT NULL DEFAULT '',
				status VARCHAR(50) NOT NULL DEFAULT 'draft',
				author_id BIGINT UNSIGNED NULL,
				settings LONGTEXT NULL,
				published_at DATETIME NULL,
				created_at DATETIME NULL,
				updated_at DATETIME NULL,
				PRIMARY KEY  (id),
				KEY site_id (site_id),
				KEY slug (slug),
				KEY status (status),
				KEY updated_at (updated_at)
			) {$charset_collate};",
			'sonyra_page_revisions'    => "CREATE TABLE {$tables['page_revisions']} (
				id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
				page_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
				revision_number BIGINT UNSIGNED NOT NULL DEFAULT 0,
				snapshot LONGTEXT NULL,
				created_by BIGINT UNSIGNED NULL,
				created_at DATETIME NULL,
				PRIMARY KEY  (id),
				KEY page_id (page_id),
				KEY created_at (created_at)
			) {$charset_collate};",
			'sonyra_page_blocks'       => "CREATE TABLE {$tables['page_blocks']} (
				id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
				page_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
				block_type VARCHAR(100) NOT NULL,
				sort_order INT UNSIGNED NOT NULL DEFAULT 0,
				settings LONGTEXT NULL,
				content LONGTEXT NULL,
				status VARCHAR(50) NOT NULL DEFAULT 'active',
				created_at DATETIME NULL,
				updated_at DATETIME NULL,
				PRIMARY KEY  (id),
				KEY page_id (page_id),
				KEY block_type (block_type),
				KEY sort_order (sort_order),
				KEY status (status)
			) {$charset_collate};",
			'sonyra_global_settings'   => "CREATE TABLE {$tables['global_settings']} (
				id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
				setting_key VARCHAR(191) NOT NULL,
				setting_value LONGTEXT NULL,
				autoload VARCHAR(20) NOT NULL DEFAULT 'no',
				created_at DATETIME NULL,
				updated_at DATETIME NULL,
				PRIMARY KEY  (id),
				UNIQUE KEY setting_key (setting_key),
				KEY autoload (autoload)
			) {$charset_collate};",
			'sonyra_brand_assets'      => "CREATE TABLE {$tables['brand_assets']} (
				id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
				asset_type VARCHAR(80) NOT NULL,
				title VARCHAR(255) NOT NULL DEFAULT '',
				attachment_id BIGINT UNSIGNED NULL,
				url TEXT NULL,
				alt_text VARCHAR(255) NOT NULL DEFAULT '',
				settings LONGTEXT NULL,
				status VARCHAR(50) NOT NULL DEFAULT 'active',
				created_at DATETIME NULL,
				updated_at DATETIME NULL,
				PRIMARY KEY  (id),
				KEY asset_type (asset_type),
				KEY status (status)
			) {$charset_collate};",
			'sonyra_logo_settings'     => "CREATE TABLE {$tables['logo_settings']} (
				id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
				logo_key VARCHAR(100) NOT NULL,
				asset_id BIGINT UNSIGNED NULL,
				settings LONGTEXT NULL,
				status VARCHAR(50) NOT NULL DEFAULT 'active',
				created_at DATETIME NULL,
				updated_at DATETIME NULL,
				PRIMARY KEY  (id),
				UNIQUE KEY logo_key (logo_key),
				KEY asset_id (asset_id),
				KEY status (status)
			) {$charset_collate};",
			'sonyra_design_tokens'     => "CREATE TABLE {$tables['design_tokens']} (
				id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
				token_key VARCHAR(191) NOT NULL,
				token_value TEXT NULL,
				token_group VARCHAR(100) NOT NULL DEFAULT '',
				status VARCHAR(50) NOT NULL DEFAULT 'active',
				created_at DATETIME NULL,
				updated_at DATETIME NULL,
				PRIMARY KEY  (id),
				UNIQUE KEY token_key (token_key),
				KEY token_group (token_group),
				KEY status (status)
			) {$charset_collate};",
			'sonyra_gradient_presets'  => "CREATE TABLE {$tables['gradient_presets']} (
				id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
				preset_key VARCHAR(191) NOT NULL,
				title VARCHAR(255) NOT NULL DEFAULT '',
				gradient_type VARCHAR(80) NOT NULL DEFAULT 'linear',
				config LONGTEXT NULL,
				status VARCHAR(50) NOT NULL DEFAULT 'active',
				created_at DATETIME NULL,
				updated_at DATETIME NULL,
				PRIMARY KEY  (id),
				UNIQUE KEY preset_key (preset_key),
				KEY gradient_type (gradient_type),
				KEY status (status)
			) {$charset_collate};",
			'sonyra_header_configs'    => "CREATE TABLE {$tables['header_configs']} (
				id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
				config_key VARCHAR(191) NOT NULL,
				title VARCHAR(255) NOT NULL DEFAULT '',
				config LONGTEXT NULL,
				status VARCHAR(50) NOT NULL DEFAULT 'active',
				created_at DATETIME NULL,
				updated_at DATETIME NULL,
				PRIMARY KEY  (id),
				UNIQUE KEY config_key (config_key),
				KEY status (status)
			) {$charset_collate};",
			'sonyra_footer_configs'    => "CREATE TABLE {$tables['footer_configs']} (
				id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
				config_key VARCHAR(191) NOT NULL,
				title VARCHAR(255) NOT NULL DEFAULT '',
				config LONGTEXT NULL,
				status VARCHAR(50) NOT NULL DEFAULT 'active',
				created_at DATETIME NULL,
				updated_at DATETIME NULL,
				PRIMARY KEY  (id),
				UNIQUE KEY config_key (config_key),
				KEY status (status)
			) {$charset_collate};",
			'sonyra_modules'           => "CREATE TABLE {$tables['modules']} (
				id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
				module_id VARCHAR(191) NOT NULL,
				title VARCHAR(255) NOT NULL DEFAULT '',
				version VARCHAR(50) NOT NULL DEFAULT '',
				status VARCHAR(50) NOT NULL DEFAULT 'disabled',
				manifest LONGTEXT NULL,
				created_at DATETIME NULL,
				updated_at DATETIME NULL,
				PRIMARY KEY  (id),
				UNIQUE KEY module_id (module_id),
				KEY status (status)
			) {$charset_collate};",
			'sonyra_embeds'            => "CREATE TABLE {$tables['embeds']} (
				id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
				object_type VARCHAR(100) NOT NULL,
				object_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
				embed_type VARCHAR(80) NOT NULL,
				embed_token_hash VARCHAR(191) NULL,
				config LONGTEXT NULL,
				status VARCHAR(50) NOT NULL DEFAULT 'active',
				created_at DATETIME NULL,
				updated_at DATETIME NULL,
				PRIMARY KEY  (id),
				KEY object_lookup (object_type, object_id),
				KEY embed_type (embed_type),
				KEY embed_token_hash (embed_token_hash),
				KEY status (status)
			) {$charset_collate};",
			'sonyra_audit_log'         => "CREATE TABLE {$tables['audit_log']} (
				id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
				event_id VARCHAR(100) NOT NULL,
				event_type VARCHAR(150) NOT NULL,
				severity VARCHAR(50) NOT NULL DEFAULT 'info',
				user_id BIGINT UNSIGNED NULL,
				source VARCHAR(80) NOT NULL DEFAULT 'core',
				context LONGTEXT NULL,
				created_at DATETIME NULL,
				PRIMARY KEY  (id),
				UNIQUE KEY event_id (event_id),
				KEY event_type (event_type),
				KEY severity (severity),
				KEY user_id (user_id),
				KEY created_at (created_at)
			) {$charset_collate};",
			'sonyra_locks'             => "CREATE TABLE {$tables['locks']} (
				id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
				object_type VARCHAR(100) NOT NULL,
				object_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
				user_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
				lock_token_hash VARCHAR(191) NULL,
				expires_at DATETIME NULL,
				created_at DATETIME NULL,
				updated_at DATETIME NULL,
				PRIMARY KEY  (id),
				KEY object_lookup (object_type, object_id),
				KEY user_id (user_id),
				KEY expires_at (expires_at)
			) {$charset_collate};",
			'sonyra_user_permissions'  => "CREATE TABLE {$tables['user_permissions']} (
				id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
				user_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
				permission_key VARCHAR(191) NOT NULL,
				permission_value VARCHAR(50) NOT NULL DEFAULT 'allow',
				scope_type VARCHAR(100) NULL,
				scope_id BIGINT UNSIGNED NULL,
				created_at DATETIME NULL,
				updated_at DATETIME NULL,
				PRIMARY KEY  (id),
				KEY user_id (user_id),
				KEY permission_key (permission_key),
				KEY scope_lookup (scope_type, scope_id)
			) {$charset_collate};",
			'sonyra_events'            => "CREATE TABLE {$tables['events']} (
				id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
				event_type VARCHAR(150) NOT NULL,
				object_type VARCHAR(100) NULL,
				object_id BIGINT UNSIGNED NULL,
				user_id BIGINT UNSIGNED NULL,
				metadata LONGTEXT NULL,
				created_at DATETIME NULL,
				PRIMARY KEY  (id),
				KEY event_type (event_type),
				KEY object_lookup (object_type, object_id),
				KEY user_id (user_id),
				KEY created_at (created_at)
			) {$charset_collate};",
			'sonyra_analytics_daily'   => "CREATE TABLE {$tables['analytics_daily']} (
				id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
				stat_date DATE NOT NULL,
				metric_key VARCHAR(150) NOT NULL,
				object_type VARCHAR(100) NULL,
				object_id BIGINT UNSIGNED NULL,
				metric_value BIGINT UNSIGNED NOT NULL DEFAULT 0,
				metadata LONGTEXT NULL,
				created_at DATETIME NULL,
				updated_at DATETIME NULL,
				PRIMARY KEY  (id),
				KEY stat_date (stat_date),
				KEY metric_key (metric_key),
				KEY object_lookup (object_type, object_id)
			) {$charset_collate};",
			'sonyra_version_log'       => "CREATE TABLE {$tables['version_log']} (
				id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
				version_type VARCHAR(50) NOT NULL DEFAULT 'db',
				previous_version VARCHAR(50) NULL,
				new_version VARCHAR(50) NOT NULL,
				context LONGTEXT NULL,
				created_at DATETIME NULL,
				PRIMARY KEY  (id),
				KEY version_type (version_type),
				KEY new_version (new_version),
				KEY created_at (created_at)
			) {$charset_collate};",
			'sonyra_auth_identities'   => "CREATE TABLE {$tables['auth_identities']} (
				id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
				identity_uuid VARCHAR(100) NOT NULL,
				email_hash VARCHAR(191) NULL,
				email_normalized VARCHAR(191) NULL,
				wp_user_id BIGINT UNSIGNED NULL,
				role_key VARCHAR(100) NOT NULL DEFAULT 'viewer',
				status VARCHAR(50) NOT NULL DEFAULT 'active',
				provider_links LONGTEXT NULL,
				metadata LONGTEXT NULL,
				last_login_at DATETIME NULL,
				created_at DATETIME NULL,
				updated_at DATETIME NULL,
				PRIMARY KEY  (id),
				UNIQUE KEY identity_uuid (identity_uuid),
				KEY email_hash (email_hash),
				KEY email_normalized (email_normalized),
				KEY wp_user_id (wp_user_id),
				KEY role_key (role_key),
				KEY status (status)
			) {$charset_collate};",
			'sonyra_auth_otp_challenges' => "CREATE TABLE {$tables['auth_otp_challenges']} (
				id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
				challenge_uuid VARCHAR(100) NOT NULL,
				identity_id BIGINT UNSIGNED NULL,
				email_hash VARCHAR(191) NULL,
				code_hash VARCHAR(255) NOT NULL,
				channel VARCHAR(50) NOT NULL DEFAULT 'email',
				purpose VARCHAR(80) NOT NULL DEFAULT 'login',
				status VARCHAR(50) NOT NULL DEFAULT 'pending',
				attempts_count INT UNSIGNED NOT NULL DEFAULT 0,
				max_attempts INT UNSIGNED NOT NULL DEFAULT 5,
				sent_count INT UNSIGNED NOT NULL DEFAULT 1,
				expires_at DATETIME NULL,
				used_at DATETIME NULL,
				locked_until DATETIME NULL,
				ip_hash VARCHAR(191) NULL,
				user_agent_hash VARCHAR(191) NULL,
				metadata LONGTEXT NULL,
				created_at DATETIME NULL,
				updated_at DATETIME NULL,
				PRIMARY KEY  (id),
				UNIQUE KEY challenge_uuid (challenge_uuid),
				KEY identity_id (identity_id),
				KEY email_hash (email_hash),
				KEY channel (channel),
				KEY purpose (purpose),
				KEY status (status),
				KEY expires_at (expires_at),
				KEY locked_until (locked_until),
				KEY created_at (created_at)
			) {$charset_collate};",
			'sonyra_auth_sessions'     => "CREATE TABLE {$tables['auth_sessions']} (
				id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
				session_uuid VARCHAR(100) NOT NULL,
				identity_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
				session_token_hash VARCHAR(255) NOT NULL,
				status VARCHAR(50) NOT NULL DEFAULT 'active',
				ip_hash VARCHAR(191) NULL,
				user_agent_hash VARCHAR(191) NULL,
				created_at DATETIME NULL,
				expires_at DATETIME NULL,
				last_seen_at DATETIME NULL,
				revoked_at DATETIME NULL,
				metadata LONGTEXT NULL,
				PRIMARY KEY  (id),
				UNIQUE KEY session_uuid (session_uuid),
				KEY identity_id (identity_id),
				KEY session_token_hash (session_token_hash),
				KEY status (status),
				KEY expires_at (expires_at),
				KEY last_seen_at (last_seen_at)
			) {$charset_collate};",
			'sonyra_auth_attempts'     => "CREATE TABLE {$tables['auth_attempts']} (
				id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
				attempt_type VARCHAR(100) NOT NULL,
				identity_id BIGINT UNSIGNED NULL,
				email_hash VARCHAR(191) NULL,
				ip_hash VARCHAR(191) NULL,
				user_agent_hash VARCHAR(191) NULL,
				result VARCHAR(50) NOT NULL DEFAULT 'unknown',
				reason VARCHAR(150) NULL,
				metadata LONGTEXT NULL,
				created_at DATETIME NULL,
				PRIMARY KEY  (id),
				KEY attempt_type (attempt_type),
				KEY identity_id (identity_id),
				KEY email_hash (email_hash),
				KEY ip_hash (ip_hash),
				KEY result (result),
				KEY created_at (created_at)
			) {$charset_collate};",
			'sonyra_auth_trusted_devices' => "CREATE TABLE {$tables['auth_trusted_devices']} (
				id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
				device_uuid VARCHAR(100) NOT NULL,
				identity_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
				device_token_hash VARCHAR(255) NOT NULL,
				status VARCHAR(50) NOT NULL DEFAULT 'active',
				ip_hash VARCHAR(191) NULL,
				user_agent_hash VARCHAR(191) NULL,
				created_at DATETIME NULL,
				expires_at DATETIME NULL,
				last_used_at DATETIME NULL,
				revoked_at DATETIME NULL,
				metadata LONGTEXT NULL,
				PRIMARY KEY  (id),
				UNIQUE KEY device_uuid (device_uuid),
				KEY identity_id (identity_id),
				KEY device_token_hash (device_token_hash),
				KEY status (status),
				KEY expires_at (expires_at),
				KEY last_used_at (last_used_at)
			) {$charset_collate};",
			'sonyra_auth_providers'    => "CREATE TABLE {$tables['auth_providers']} (
				id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
				provider_key VARCHAR(100) NOT NULL,
				title VARCHAR(255) NOT NULL DEFAULT '',
				provider_type VARCHAR(80) NOT NULL DEFAULT 'otp',
				status VARCHAR(50) NOT NULL DEFAULT 'disabled',
				settings LONGTEXT NULL,
				created_at DATETIME NULL,
				updated_at DATETIME NULL,
				PRIMARY KEY  (id),
				UNIQUE KEY provider_key (provider_key),
				KEY provider_type (provider_type),
				KEY status (status)
			) {$charset_collate};",
		);
	}

	public static function insert_version_log( string $previous_db_version, string $new_db_version, array $context = array() ): void {
		global $wpdb;

		$table_name = Sonyra_Site_Manager_Database::get_table_name( 'version_log' );

		if ( '' === $table_name ) {
			return;
		}

		$found_table = (string) $wpdb->get_var(
			$wpdb->prepare( 'SHOW TABLES LIKE %s', $table_name )
		);

		if ( $found_table !== $table_name ) {
			return;
		}

		$wpdb->insert(
			$table_name,
			array(
				'version_type'     => 'db',
				'previous_version' => '' === $previous_db_version ? null : $previous_db_version,
				'new_version'      => $new_db_version,
				'context'          => wp_json_encode( self::normalize_context( $context ) ),
				'created_at'       => current_time( 'mysql' ),
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

	public static function maybe_migrate(): void {
		if ( ! class_exists( 'Sonyra_Site_Manager_Database' ) ) {
			return;
		}

		$current_db_version  = (string) get_option( 'sonyra_site_manager_db_version', '' );
		$required_db_version = Sonyra_Site_Manager_Database::get_db_version();
		$table_status        = Sonyra_Site_Manager_Database::tables_exist();

		if ( $required_db_version !== $current_db_version || empty( $table_status['is_ready'] ) ) {
			self::install_or_update_schema();
		}
	}

	private static function log_audit_event( string $event_type, array $context, string $severity ): void {
		if ( ! class_exists( 'Sonyra_Site_Manager_Audit_Log' ) ) {
			return;
		}

		Sonyra_Site_Manager_Audit_Log::log_event(
			$event_type,
			self::normalize_context( $context ),
			$severity
		);
	}

	private static function normalize_context( array $context ): array {
		if ( class_exists( 'Sonyra_Site_Manager_Audit_Log' ) ) {
			$normalized = Sonyra_Site_Manager_Audit_Log::normalize_context( $context );

			if ( is_array( $normalized ) ) {
				return $normalized;
			}
		}

		return $context;
	}
}
