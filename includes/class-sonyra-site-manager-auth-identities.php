<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Sonyra_Site_Manager_Auth_Identities {

	private static $allowed_statuses = array(
		'active',
		'disabled',
		'pending',
		'blocked',
	);

	public static function normalize_email( string $email ): string {
		$email = trim( strtolower( $email ) );

		if ( function_exists( 'sanitize_email' ) ) {
			$email = sanitize_email( $email );
		}

		$is_valid = function_exists( 'is_email' ) ? (bool) is_email( $email ) : (bool) filter_var( $email, FILTER_VALIDATE_EMAIL );

		if ( ! $is_valid ) {
			return '';
		}

		return $email;
	}

	public static function hash_email( string $email ): string {
		$email = self::normalize_email( $email );

		if ( '' === $email ) {
			return '';
		}

		if ( function_exists( 'wp_hash' ) ) {
			return wp_hash( 'sonyra_email|' . $email );
		}

		return hash( 'sha256', 'sonyra_email|' . $email );
	}

	public static function generate_identity_uuid(): string {
		if ( function_exists( 'wp_generate_uuid4' ) ) {
			return wp_generate_uuid4();
		}

		return uniqid( 'sonyra_identity_', true );
	}

	public static function find_identity_by_email( string $email ): array {
		global $wpdb;

		$email_hash = self::hash_email( $email );
		$table_name = self::get_table_name();

		if ( '' === $email_hash || '' === $table_name || ! self::table_exists( $table_name ) ) {
			return array();
		}

		$row = $wpdb->get_row(
			$wpdb->prepare(
				'SELECT * FROM ' . self::quote_identifier( $table_name ) . ' WHERE email_hash = %s LIMIT 1',
				$email_hash
			),
			ARRAY_A
		);

		return is_array( $row ) ? $row : array();
	}

	public static function find_identity_by_wp_user_id( int $wp_user_id ): array {
		global $wpdb;

		$table_name = self::get_table_name();
		$wp_user_id = max( 0, $wp_user_id );

		if ( $wp_user_id <= 0 || '' === $table_name || ! self::table_exists( $table_name ) ) {
			return array();
		}

		$row = $wpdb->get_row(
			$wpdb->prepare(
				'SELECT * FROM ' . self::quote_identifier( $table_name ) . ' WHERE wp_user_id = %d LIMIT 1',
				$wp_user_id
			),
			ARRAY_A
		);

		return is_array( $row ) ? $row : array();
	}

	public static function create_or_update_identity( array $data ): int {
		global $wpdb;

		$table_name = self::get_table_name();

		if ( '' === $table_name || ! self::table_exists( $table_name ) ) {
			return 0;
		}

		$email            = self::normalize_email( isset( $data['email'] ) ? (string) $data['email'] : '' );
		$email_hash       = self::hash_email( $email );
		$wp_user_id       = isset( $data['wp_user_id'] ) ? max( 0, (int) $data['wp_user_id'] ) : 0;
		$role_key         = class_exists( 'Sonyra_Site_Manager_Auth_Roles' ) ? Sonyra_Site_Manager_Auth_Roles::normalize_role( (string) ( $data['role_key'] ?? '' ) ) : 'viewer';
		$status           = self::normalize_status( (string) ( $data['status'] ?? 'active' ) );
		$provider_links   = isset( $data['provider_links'] ) && is_array( $data['provider_links'] ) ? $data['provider_links'] : array();
		$metadata         = isset( $data['metadata'] ) && is_array( $data['metadata'] ) ? $data['metadata'] : array();
		$current_time     = self::get_current_time();
		$existing_identity = $wp_user_id > 0 ? self::find_identity_by_wp_user_id( $wp_user_id ) : array();

		if ( empty( $existing_identity ) && '' !== $email_hash ) {
			$existing_identity = self::find_identity_by_email( $email );
		}

		$record = array(
			'email_hash'       => '' === $email_hash ? null : $email_hash,
			'email_normalized' => '' === $email ? null : $email,
			'wp_user_id'       => $wp_user_id > 0 ? $wp_user_id : null,
			'role_key'         => $role_key,
			'status'           => $status,
			'provider_links'   => self::json_encode( $provider_links ),
			'metadata'         => self::json_encode( $metadata ),
			'updated_at'       => $current_time,
		);

		if ( ! empty( $existing_identity['id'] ) ) {
			$result = $wpdb->update(
				$table_name,
				$record,
				array( 'id' => (int) $existing_identity['id'] )
			);

			return false === $result ? 0 : (int) $existing_identity['id'];
		}

		$record['identity_uuid'] = self::generate_identity_uuid();
		$record['created_at']    = $current_time;

		$result = $wpdb->insert( $table_name, $record );

		if ( false === $result ) {
			return 0;
		}

		return (int) $wpdb->insert_id;
	}

	public static function ensure_owner_identity(): int {
		if ( ! class_exists( 'Sonyra_Site_Manager_Permissions' ) ) {
			return 0;
		}

		$owner_user_id = Sonyra_Site_Manager_Permissions::get_owner_user_id();

		if ( $owner_user_id <= 0 || ! function_exists( 'get_userdata' ) ) {
			return 0;
		}

		$wp_user = get_userdata( $owner_user_id );

		if ( ! $wp_user || empty( $wp_user->user_email ) ) {
			return 0;
		}

		return self::create_or_update_identity(
			array(
				'email'      => (string) $wp_user->user_email,
				'wp_user_id' => $owner_user_id,
				'role_key'   => class_exists( 'Sonyra_Site_Manager_Auth_Roles' ) ? Sonyra_Site_Manager_Auth_Roles::ROLE_OWNER : 'owner',
				'status'     => 'active',
				'metadata'   => array(
					'source'                => 'wordpress_owner',
					'created_by_foundation' => true,
				),
			)
		);
	}

	public static function has_owner_identity(): bool {
		global $wpdb;

		$table_name = self::get_table_name();

		if ( '' === $table_name || ! self::table_exists( $table_name ) ) {
			return false;
		}

		$identity_id = (int) $wpdb->get_var(
			$wpdb->prepare(
				'SELECT id FROM ' . self::quote_identifier( $table_name ) . ' WHERE role_key = %s AND status = %s LIMIT 1',
				'owner',
				'active'
			)
		);

		return $identity_id > 0;
	}

	private static function normalize_status( string $status ): string {
		$status = trim( strtolower( $status ) );

		if ( in_array( $status, self::$allowed_statuses, true ) ) {
			return $status;
		}

		return 'pending';
	}

	private static function get_table_name(): string {
		if ( ! class_exists( 'Sonyra_Site_Manager_Database' ) ) {
			return '';
		}

		return Sonyra_Site_Manager_Database::get_table_name( 'auth_identities' );
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
