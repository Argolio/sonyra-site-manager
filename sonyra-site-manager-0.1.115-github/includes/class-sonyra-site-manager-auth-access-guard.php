<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Sonyra_Site_Manager_Auth_Access_Guard {

	public static function get_required_role_for_manager(): string {
		return 'viewer';
	}

	public static function get_allowed_roles_for_manager(): array {
		return array(
			'owner',
			'admin',
			'editor',
			'member',
			'viewer',
		);
	}

	public static function get_current_auth_context(): array {
		if ( ! class_exists( 'Sonyra_Site_Manager_Auth_Cookie' ) ) {
			return self::build_context_denial( 'cookie_transport_unavailable' );
		}

		$cookie_result = Sonyra_Site_Manager_Auth_Cookie::validate_cookie();

		if ( empty( $cookie_result['valid'] ) ) {
			return self::build_context_denial( self::get_reason_code( $cookie_result, 'auth_cookie_invalid' ) );
		}

		$identity_id = isset( $cookie_result['identity_id'] ) ? absint( $cookie_result['identity_id'] ) : 0;

		if ( $identity_id <= 0 ) {
			return self::build_context_denial( 'identity_missing' );
		}

		$identity = self::load_identity_by_id( $identity_id );

		if ( empty( $identity ) ) {
			return self::build_context_denial( 'identity_not_found' );
		}

		if ( 'active' !== (string) $identity['status'] ) {
			return self::build_context_denial( 'identity_inactive' );
		}

		$role_key = self::normalize_role_key( (string) $identity['role_key'] );

		if ( '' === $role_key || ! self::role_exists( $role_key ) ) {
			return self::build_context_denial( 'role_unknown' );
		}

		return array(
			'authenticated'        => true,
			'allowed'              => false,
			'identity_id'          => (int) $identity['id'],
			'session_uuid'         => isset( $cookie_result['session_uuid'] ) ? sanitize_text_field( (string) $cookie_result['session_uuid'] ) : '',
			'role_key'             => $role_key,
			'role'                 => self::get_role( $role_key ),
			'status'               => (string) $identity['status'],
			'capabilities'         => self::get_capabilities_for_role( $role_key ),
			'reason_code'          => 'authenticated',
			'route_access_granted' => false,
		);
	}

	public static function load_identity_by_id( int $identity_id ): array {
		global $wpdb;

		if ( $identity_id <= 0 || ! class_exists( 'Sonyra_Site_Manager_Database' ) ) {
			return array();
		}

		$table_name = Sonyra_Site_Manager_Database::get_table_name( 'auth_identities' );

		if ( '' === $table_name ) {
			return array();
		}

		$identity = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT id, identity_uuid, wp_user_id, role_key, status, last_login_at FROM {$table_name} WHERE id = %d LIMIT 1",
				$identity_id
			),
			ARRAY_A
		);

		if ( ! is_array( $identity ) ) {
			return array();
		}

		return array(
			'id'            => isset( $identity['id'] ) ? (int) $identity['id'] : 0,
			'identity_uuid' => isset( $identity['identity_uuid'] ) ? sanitize_text_field( (string) $identity['identity_uuid'] ) : '',
			'wp_user_id'    => isset( $identity['wp_user_id'] ) ? (int) $identity['wp_user_id'] : 0,
			'role_key'      => isset( $identity['role_key'] ) ? sanitize_key( (string) $identity['role_key'] ) : '',
			'status'        => isset( $identity['status'] ) ? sanitize_key( (string) $identity['status'] ) : '',
			'last_login_at' => isset( $identity['last_login_at'] ) ? sanitize_text_field( (string) $identity['last_login_at'] ) : '',
		);
	}

	public static function can_access_manager( array $context = array() ): array {
		if ( empty( $context ) ) {
			$context = self::get_current_auth_context();
		}

		$decision = self::build_access_decision( $context );
		$audit_context = self::build_audit_context( $decision );

		self::log_access_event( 'auth.access_checked', $audit_context, 'info' );
		self::log_access_event( $decision['allowed'] ? 'auth.access_allowed' : 'auth.access_denied', $audit_context, $decision['allowed'] ? 'info' : 'security' );

		return $decision;
	}

	public static function can_manage_settings( array $context = array() ): bool {
		$role_key = self::get_authorized_role_key( $context );

		return in_array( $role_key, array( 'owner', 'admin' ), true );
	}

	public static function can_manage_roles( array $context = array() ): bool {
		$role_key = self::get_authorized_role_key( $context );

		return in_array( $role_key, array( 'owner', 'admin' ), true );
	}

	public static function can_manage_content( array $context = array() ): bool {
		$role_key = self::get_authorized_role_key( $context );

		return in_array( $role_key, array( 'owner', 'admin', 'editor' ), true );
	}

	public static function can_view( array $context = array() ): bool {
		$role_key = self::get_authorized_role_key( $context );

		return in_array( $role_key, self::get_allowed_roles_for_manager(), true );
	}

	public static function build_denied_context( string $reason_code = 'access_denied' ): array {
		return array(
			'authenticated'        => false,
			'allowed'              => false,
			'identity_id'          => 0,
			'session_uuid'         => '',
			'role_key'             => '',
			'role'                 => array(),
			'status'               => '',
			'capabilities'         => array(),
			'reason_code'          => sanitize_key( $reason_code ),
			'route_access_granted' => false,
			'message'              => 'Доступ не разрешён.',
		);
	}

	public static function get_access_guard_status(): array {
		$cookie_transport_ready = class_exists( 'Sonyra_Site_Manager_Auth_Cookie' );
		$auth_sessions_ready    = class_exists( 'Sonyra_Site_Manager_Auth_Sessions' );
		$auth_roles_ready       = class_exists( 'Sonyra_Site_Manager_Auth_Roles' );
		$access_guard_ready     = true;

		return array(
			'access_guard_ready'    => $access_guard_ready,
			'cookie_transport_ready'=> $cookie_transport_ready,
			'auth_sessions_ready'   => $auth_sessions_ready,
			'auth_roles_ready'      => $auth_roles_ready,
			'grants_route_access'   => false,
			'creates_route'         => false,
			'creates_ui'            => false,
			'uses_rest'             => false,
			'is_ready'              => $access_guard_ready && $cookie_transport_ready && $auth_sessions_ready && $auth_roles_ready,
		);
	}

	private static function build_access_decision( array $context ): array {
		$identity_id  = isset( $context['identity_id'] ) ? absint( $context['identity_id'] ) : 0;
		$session_uuid = isset( $context['session_uuid'] ) ? sanitize_text_field( (string) $context['session_uuid'] ) : '';
		$role_key     = isset( $context['role_key'] ) ? self::normalize_role_key( (string) $context['role_key'] ) : '';
		$status       = isset( $context['status'] ) ? sanitize_key( (string) $context['status'] ) : '';
		$reason_code  = isset( $context['reason_code'] ) ? sanitize_key( (string) $context['reason_code'] ) : 'access_denied';
		$allowed      = true;

		if ( empty( $context['authenticated'] ) ) {
			$allowed = false;
			$reason_code = '' !== $reason_code ? $reason_code : 'not_authenticated';
		} elseif ( 'active' !== $status ) {
			$allowed = false;
			$reason_code = 'identity_inactive';
		} elseif ( '' === $role_key || ! self::role_exists( $role_key ) || ! in_array( $role_key, self::get_allowed_roles_for_manager(), true ) ) {
			$allowed = false;
			$reason_code = 'role_not_allowed';
		} else {
			$reason_code = 'access_allowed';
		}

		return array(
			'allowed'              => $allowed,
			'reason_code'          => $reason_code,
			'role_key'             => $role_key,
			'identity_id'          => $identity_id,
			'session_uuid'         => $session_uuid,
			'access_layer'         => 'internal_guard',
			'route_access_granted' => false,
		);
	}

	private static function build_context_denial( string $reason_code ): array {
		return self::build_denied_context( $reason_code );
	}

	private static function get_authorized_role_key( array $context ): string {
		if ( empty( $context ) ) {
			$context = self::get_current_auth_context();
		}

		if ( empty( $context['authenticated'] ) || 'active' !== ( $context['status'] ?? '' ) ) {
			return '';
		}

		$role_key = isset( $context['role_key'] ) ? self::normalize_role_key( (string) $context['role_key'] ) : '';

		if ( '' === $role_key || ! self::role_exists( $role_key ) ) {
			return '';
		}

		return $role_key;
	}

	private static function normalize_role_key( string $role_key ): string {
		$role_key = sanitize_key( $role_key );

		if ( '' === $role_key ) {
			return '';
		}

		if ( class_exists( 'Sonyra_Site_Manager_Auth_Roles' ) && Sonyra_Site_Manager_Auth_Roles::role_exists( $role_key ) ) {
			return Sonyra_Site_Manager_Auth_Roles::normalize_role( $role_key );
		}

		return in_array( $role_key, self::get_allowed_roles_for_manager(), true ) ? $role_key : '';
	}

	private static function role_exists( string $role_key ): bool {
		if ( class_exists( 'Sonyra_Site_Manager_Auth_Roles' ) ) {
			return Sonyra_Site_Manager_Auth_Roles::role_exists( $role_key );
		}

		return in_array( $role_key, self::get_allowed_roles_for_manager(), true );
	}

	private static function get_role( string $role_key ): array {
		if ( class_exists( 'Sonyra_Site_Manager_Auth_Roles' ) ) {
			return Sonyra_Site_Manager_Auth_Roles::get_role( $role_key );
		}

		return array();
	}

	private static function get_capabilities_for_role( string $role_key ): array {
		$role = self::get_role( $role_key );

		return array(
			'can_manage_roles'    => ! empty( $role['can_manage_roles'] ),
			'can_manage_settings' => ! empty( $role['can_manage_settings'] ),
			'can_manage_content'  => ! empty( $role['can_manage_content'] ),
			'can_view'            => ! empty( $role['can_view'] ),
		);
	}

	private static function get_reason_code( array $result, string $fallback ): string {
		if ( empty( $result['reason'] ) ) {
			return $fallback;
		}

		return sanitize_key( (string) $result['reason'] );
	}

	private static function build_audit_context( array $decision ): array {
		return array(
			'identity_id'          => isset( $decision['identity_id'] ) ? absint( $decision['identity_id'] ) : 0,
			'session_uuid'         => isset( $decision['session_uuid'] ) ? sanitize_text_field( (string) $decision['session_uuid'] ) : '',
			'role_key'             => isset( $decision['role_key'] ) ? sanitize_key( (string) $decision['role_key'] ) : '',
			'reason_code'          => isset( $decision['reason_code'] ) ? sanitize_key( (string) $decision['reason_code'] ) : '',
			'route_access_granted' => false,
		);
	}

	private static function log_access_event( string $event_type, array $context, string $severity ): void {
		if ( ! class_exists( 'Sonyra_Site_Manager_Audit_Log' ) ) {
			return;
		}

		Sonyra_Site_Manager_Audit_Log::log_event( $event_type, $context, $severity );
	}
}
