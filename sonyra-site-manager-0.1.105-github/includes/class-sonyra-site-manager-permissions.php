<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Sonyra_Site_Manager_Permissions {

	const CAP_VIEW             = 'sonyra_site_manager_view';
	const CAP_MANAGE           = 'sonyra_site_manager_manage';
	const CAP_EDIT_PAGES       = 'sonyra_site_manager_edit_pages';
	const CAP_MANAGE_SETTINGS  = 'sonyra_site_manager_manage_settings';
	const CAP_MANAGE_MODULES   = 'sonyra_site_manager_manage_modules';
	const CAP_VIEW_DIAGNOSTICS = 'sonyra_site_manager_view_diagnostics';

	public static function get_capabilities(): array {
		return array(
			self::CAP_VIEW,
			self::CAP_MANAGE,
			self::CAP_EDIT_PAGES,
			self::CAP_MANAGE_SETTINGS,
			self::CAP_MANAGE_MODULES,
			self::CAP_VIEW_DIAGNOSTICS,
		);
	}

	public static function install_capabilities(): void {
		if ( ! function_exists( 'get_role' ) ) {
			return;
		}

		$role = get_role( 'administrator' );

		if ( ! $role ) {
			return;
		}

		foreach ( self::get_capabilities() as $capability ) {
			$role->add_cap( $capability );
		}
	}

	public static function remove_capabilities(): void {
		if ( ! function_exists( 'get_role' ) ) {
			return;
		}

		$role = get_role( 'administrator' );

		if ( ! $role ) {
			return;
		}

		foreach ( self::get_capabilities() as $capability ) {
			$role->remove_cap( $capability );
		}
	}

	public static function get_owner_user_id(): int {
		return (int) get_option( 'sonyra_site_manager_owner_user_id', 0 );
	}

	public static function maybe_set_initial_owner(): void {
		if ( self::get_owner_user_id() > 0 ) {
			return;
		}

		if ( ! function_exists( 'get_current_user_id' ) || ! function_exists( 'current_user_can' ) ) {
			return;
		}

		$user_id = (int) get_current_user_id();

		if ( $user_id > 0 && current_user_can( 'manage_options' ) ) {
			update_option( 'sonyra_site_manager_owner_user_id', $user_id, false );
		}
	}

	public static function is_owner( $user_id = 0 ): bool {
		$owner_user_id = self::get_owner_user_id();

		if ( $owner_user_id <= 0 ) {
			return false;
		}

		if ( ! $user_id && function_exists( 'get_current_user_id' ) ) {
			$user_id = get_current_user_id();
		}

		return (int) $user_id === $owner_user_id;
	}

	public static function current_user_can_manage(): bool {
		if ( self::is_owner() ) {
			return true;
		}

		return function_exists( 'current_user_can' ) && current_user_can( self::CAP_MANAGE );
	}

	public static function current_user_can_view(): bool {
		if ( self::current_user_can_manage() ) {
			return true;
		}

		return function_exists( 'current_user_can' ) && current_user_can( self::CAP_VIEW );
	}
}
