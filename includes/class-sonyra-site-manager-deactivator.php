<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Sonyra_Site_Manager_Deactivator {

	public static function deactivate() {
		if ( class_exists( 'Sonyra_Site_Manager_Login_Route' ) ) {
			Sonyra_Site_Manager_Login_Route::deactivate();
		}

		if ( class_exists( 'Sonyra_Site_Manager_Audit_Log' ) ) {
			Sonyra_Site_Manager_Audit_Log::log_event(
				'core.deactivated',
				array(
					'version' => SONYRA_SITE_MANAGER_VERSION,
				),
				'info'
			);
		}

		// Права и настройки не удаляются при деактивации, чтобы сохранить безопасный откат.
	}
}
