<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Sonyra_Site_Manager_Activator {

	public static function activate() {
		if ( version_compare( PHP_VERSION, '7.4', '<' ) ) {
			wp_die(
				esc_html__( 'Для работы SONYRA Site Manager требуется PHP версии 7.4 или выше.', 'sonyra-site-manager' ),
				esc_html__( 'Активация остановлена', 'sonyra-site-manager' ),
				array( 'back_link' => true )
			);
		}

		if ( version_compare( get_bloginfo( 'version' ), '6.0', '<' ) ) {
			wp_die(
				esc_html__( 'Для работы SONYRA Site Manager требуется WordPress версии 6.0 или выше.', 'sonyra-site-manager' ),
				esc_html__( 'Активация остановлена', 'sonyra-site-manager' ),
				array( 'back_link' => true )
			);
		}

		Sonyra_Site_Manager_Installer::install_or_update();

		if ( class_exists( 'Sonyra_Site_Manager_Login_Route' ) ) {
			Sonyra_Site_Manager_Login_Route::activate();
		}

		if ( class_exists( 'Sonyra_Site_Manager_Audit_Log' ) ) {
			Sonyra_Site_Manager_Audit_Log::log_event(
				'core.activated',
				array(
					'version' => SONYRA_SITE_MANAGER_VERSION,
				),
				'info'
			);
		}
	}
}
