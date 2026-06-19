<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Sonyra_Site_Manager_Installer {

	public static function install_or_update(): void {
		$stored_version = (string) get_option( 'sonyra_site_manager_version', '' );
		$current_time   = current_time( 'mysql' );

		if ( false === get_option( 'sonyra_site_manager_installed_at', false ) ) {
			add_option( 'sonyra_site_manager_installed_at', $current_time, '', false );
		}

		if ( '' !== $stored_version && SONYRA_SITE_MANAGER_VERSION !== $stored_version ) {
			update_option( 'sonyra_site_manager_last_known_version', $stored_version, false );
		}

		update_option( 'sonyra_site_manager_version', SONYRA_SITE_MANAGER_VERSION, false );
		update_option( 'sonyra_site_manager_updated_at', $current_time, false );
		update_option( 'sonyra_site_manager_release_channel', Sonyra_Site_Manager_Status::get_release_channel(), false );
		update_option( 'sonyra_site_manager_build_type', Sonyra_Site_Manager_Status::get_build_type(), false );
		update_option( 'sonyra_site_manager_product_name', Sonyra_Site_Manager_Status::get_product_name(), false );
		update_option( 'sonyra_site_manager_platform_name', Sonyra_Site_Manager_Status::get_platform_name(), false );
		update_option( 'sonyra_site_manager_publisher', Sonyra_Site_Manager_Status::get_publisher(), false );
		update_option( 'sonyra_site_manager_licensor', Sonyra_Site_Manager_Status::get_licensor(), false );

		if ( class_exists( 'Sonyra_Site_Manager_Permissions' ) ) {
			Sonyra_Site_Manager_Permissions::install_capabilities();
			Sonyra_Site_Manager_Permissions::maybe_set_initial_owner();
			update_option( 'sonyra_site_manager_capabilities_synced_version', SONYRA_SITE_MANAGER_VERSION, false );
		}

		if ( class_exists( 'Sonyra_Site_Manager_Migrator' ) ) {
			Sonyra_Site_Manager_Migrator::install_or_update_schema();
		}

		if ( class_exists( 'Sonyra_Site_Manager_Seeder' ) ) {
			Sonyra_Site_Manager_Seeder::maybe_seed();
		}

		if ( class_exists( 'Sonyra_Site_Manager_Auth' ) ) {
			Sonyra_Site_Manager_Auth::maybe_sync_foundation();
		}

		if ( class_exists( 'Sonyra_Site_Manager_Audit_Log' ) ) {
			Sonyra_Site_Manager_Audit_Log::log_event(
				'core.install_or_update',
				array(
					'previous_version' => $stored_version,
					'current_version'  => SONYRA_SITE_MANAGER_VERSION,
					'release_channel'  => Sonyra_Site_Manager_Status::get_release_channel(),
					'build_type'       => Sonyra_Site_Manager_Status::get_build_type(),
				),
				'info'
			);
		}
	}

	public static function maybe_runtime_sync(): void {
		$stored_version = (string) get_option( 'sonyra_site_manager_version', '' );

		if ( SONYRA_SITE_MANAGER_VERSION !== $stored_version ) {
			self::install_or_update();
			return;
		}

		if ( class_exists( 'Sonyra_Site_Manager_Migrator' ) ) {
			Sonyra_Site_Manager_Migrator::maybe_migrate();
		}

		if ( class_exists( 'Sonyra_Site_Manager_Seeder' ) ) {
			Sonyra_Site_Manager_Seeder::maybe_seed();
		}

		if ( class_exists( 'Sonyra_Site_Manager_Auth' ) ) {
			Sonyra_Site_Manager_Auth::maybe_sync_foundation();
		}

		if ( ! class_exists( 'Sonyra_Site_Manager_Permissions' ) ) {
			return;
		}

		if ( Sonyra_Site_Manager_Permissions::get_owner_user_id() <= 0 ) {
			Sonyra_Site_Manager_Permissions::maybe_set_initial_owner();
		}

		if ( SONYRA_SITE_MANAGER_VERSION !== (string) get_option( 'sonyra_site_manager_capabilities_synced_version', '' ) ) {
			Sonyra_Site_Manager_Permissions::install_capabilities();
			update_option( 'sonyra_site_manager_capabilities_synced_version', SONYRA_SITE_MANAGER_VERSION, false );
		}
	}
}
