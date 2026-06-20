<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Sonyra_Site_Manager_Plugin {

	/**
	 * @var string
	 */
	private $version;

	public function __construct( $version ) {
		$this->version = $version;
	}

	public function run() {
		add_action( 'plugins_loaded', array( 'Sonyra_Site_Manager_Installer', 'maybe_runtime_sync' ), 20 );
		add_action( 'rest_api_init', array( 'Sonyra_Site_Manager_REST_Auth_Controller', 'register_routes' ) );
		add_action( 'rest_api_init', array( 'Sonyra_Site_Manager_REST_Pages_Controller', 'register_routes' ) );
		Sonyra_Site_Manager_Login_Route::register_hooks();
		Sonyra_Site_Manager_Public_Page_Route::register_hooks();
	}

	public function get_version() {
		return $this->version;
	}
}
