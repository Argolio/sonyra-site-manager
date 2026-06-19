<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Sonyra_Site_Manager_Auth {

	public static function get_module_key(): string {
		return 'auth';
	}

	public static function get_visible_name(): string {
		return 'Вход и доступ';
	}

	public static function get_architecture_name(): string {
		return 'SONYRA Auth';
	}

	public static function get_foundation_status(): array {
		$auth_tables_ready     = self::are_auth_tables_ready();
		$email_provider_ready = class_exists( 'Sonyra_Site_Manager_Auth_Providers' ) ? Sonyra_Site_Manager_Auth_Providers::is_email_provider_ready() : false;
		$owner_identity_ready = class_exists( 'Sonyra_Site_Manager_Auth_Identities' ) ? Sonyra_Site_Manager_Auth_Identities::has_owner_identity() : false;
		$roles_count          = class_exists( 'Sonyra_Site_Manager_Auth_Roles' ) ? count( Sonyra_Site_Manager_Auth_Roles::get_roles() ) : 0;
		$providers_count      = class_exists( 'Sonyra_Site_Manager_Auth_Providers' ) ? count( Sonyra_Site_Manager_Auth_Providers::get_default_providers() ) : 0;

		return array(
			'module_key'            => self::get_module_key(),
			'visible_name'          => self::get_visible_name(),
			'architecture_name'     => self::get_architecture_name(),
			'email_provider_ready'  => $email_provider_ready,
			'owner_identity_ready'  => $owner_identity_ready,
			'auth_tables_ready'     => $auth_tables_ready,
			'roles_count'           => $roles_count,
			'providers_count'       => $providers_count,
			'is_ready'              => $auth_tables_ready && $email_provider_ready && $owner_identity_ready && $roles_count > 0 && $providers_count > 0,
		);
	}

	public static function install_or_update_foundation(): bool {
		$table_status = self::get_auth_table_status();

		if ( ! $table_status['is_ready'] ) {
			self::log_foundation_event(
				'auth.foundation_skipped',
				array(
					'reason'         => 'auth_tables_not_ready',
					'missing_tables' => $table_status['missing'],
				),
				'warning',
				true
			);

			return false;
		}

		$providers_synced = class_exists( 'Sonyra_Site_Manager_Auth_Providers' ) ? Sonyra_Site_Manager_Auth_Providers::sync_default_providers() : false;
		$owner_identity_id = class_exists( 'Sonyra_Site_Manager_Auth_Identities' ) ? Sonyra_Site_Manager_Auth_Identities::ensure_owner_identity() : 0;
		$status           = self::get_foundation_status();
		$is_ready         = $providers_synced && $owner_identity_id > 0 && ! empty( $status['is_ready'] );

		update_option( 'sonyra_site_manager_auth_foundation_version', SONYRA_SITE_MANAGER_VERSION, false );
		update_option( 'sonyra_site_manager_auth_foundation_updated_at', self::get_current_time(), false );

		self::log_foundation_event(
			'auth.foundation_synced',
			array(
				'email_provider_ready' => $status['email_provider_ready'],
				'owner_identity_ready' => $status['owner_identity_ready'],
				'roles_count'          => $status['roles_count'],
				'providers_count'      => $status['providers_count'],
			),
			'info',
			true
		);

		return $is_ready;
	}

	public static function maybe_sync_foundation(): void {
		$synced_version = (string) get_option( 'sonyra_site_manager_auth_foundation_version', '' );
		$status         = self::get_foundation_status();

		if ( SONYRA_SITE_MANAGER_VERSION === $synced_version && ! empty( $status['is_ready'] ) ) {
			return;
		}

		self::install_or_update_foundation();
	}

	private static function are_auth_tables_ready(): bool {
		return self::get_auth_table_status()['is_ready'];
	}

	private static function get_auth_table_status(): array {
		$required = self::get_auth_table_keys();
		$existing = array();
		$missing  = $required;

		if ( class_exists( 'Sonyra_Site_Manager_Database' ) ) {
			$table_status = Sonyra_Site_Manager_Database::tables_exist();
			$existing     = array_values( array_intersect( $required, $table_status['existing'] ) );
			$missing      = array_values( array_intersect( $required, $table_status['missing'] ) );
		}

		return array(
			'required' => $required,
			'existing' => $existing,
			'missing'  => $missing,
			'is_ready' => empty( $missing ) && count( $existing ) === count( $required ),
		);
	}

	private static function get_auth_table_keys(): array {
		return array(
			'auth_identities',
			'auth_otp_challenges',
			'auth_sessions',
			'auth_attempts',
			'auth_trusted_devices',
			'auth_providers',
		);
	}

	private static function log_foundation_event( string $event_type, array $context, string $severity, bool $dedupe ): void {
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
