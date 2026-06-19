<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Sonyra_Site_Manager_Auth_Roles {

	const ROLE_OWNER  = 'owner';
	const ROLE_ADMIN  = 'admin';
	const ROLE_EDITOR = 'editor';
	const ROLE_MEMBER = 'member';
	const ROLE_VIEWER = 'viewer';

	public static function get_roles(): array {
		return array(
			self::ROLE_OWNER  => array(
				'title'               => 'Главный администратор',
				'level'               => 100,
				'can_manage_roles'    => true,
				'can_manage_settings' => true,
				'can_manage_content'  => true,
				'can_view'            => true,
			),
			self::ROLE_ADMIN  => array(
				'title'               => 'Администратор',
				'level'               => 80,
				'can_manage_roles'    => true,
				'can_manage_settings' => true,
				'can_manage_content'  => true,
				'can_view'            => true,
			),
			self::ROLE_EDITOR => array(
				'title'               => 'Редактор',
				'level'               => 60,
				'can_manage_roles'    => false,
				'can_manage_settings' => false,
				'can_manage_content'  => true,
				'can_view'            => true,
			),
			self::ROLE_MEMBER => array(
				'title'               => 'Участник',
				'level'               => 40,
				'can_manage_roles'    => false,
				'can_manage_settings' => false,
				'can_manage_content'  => false,
				'can_view'            => true,
			),
			self::ROLE_VIEWER => array(
				'title'               => 'Наблюдатель',
				'level'               => 20,
				'can_manage_roles'    => false,
				'can_manage_settings' => false,
				'can_manage_content'  => false,
				'can_view'            => true,
			),
		);
	}

	public static function role_exists( string $role_key ): bool {
		return isset( self::get_roles()[ $role_key ] );
	}

	public static function get_role( string $role_key ): array {
		$roles = self::get_roles();

		if ( ! isset( $roles[ $role_key ] ) ) {
			return array();
		}

		return $roles[ $role_key ];
	}

	public static function normalize_role( string $role_key ): string {
		$role_key = trim( strtolower( $role_key ) );

		if ( self::role_exists( $role_key ) ) {
			return $role_key;
		}

		return self::ROLE_VIEWER;
	}

	public static function can_assign_role( string $actor_role, string $target_role ): bool {
		$actor_role = self::normalize_role( $actor_role );
		$target_role = self::normalize_role( $target_role );

		if ( self::ROLE_OWNER === $actor_role ) {
			return true;
		}

		if ( self::ROLE_ADMIN !== $actor_role ) {
			return false;
		}

		return in_array(
			$target_role,
			array(
				self::ROLE_EDITOR,
				self::ROLE_MEMBER,
				self::ROLE_VIEWER,
			),
			true
		);
	}
}
