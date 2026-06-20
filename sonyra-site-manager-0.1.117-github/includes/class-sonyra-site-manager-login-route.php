<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Sonyra_Site_Manager_Login_Route {

	const FLUSH_OPTION_NAME = 'sonyra_site_manager_login_route_flush_version';

	public static function get_route_path(): string {
		return 'manager/login';
	}

	public static function get_manager_route_path(): string {
		return 'manager';
	}

	public static function get_query_var(): string {
		return 'sonyra_site_manager_login';
	}

	public static function get_manager_query_var(): string {
		return 'sonyra_site_manager_manager';
	}

	public static function register_hooks(): void {
		add_action( 'init', array( __CLASS__, 'register_rewrite_rule' ) );
		add_action( 'init', array( __CLASS__, 'maybe_flush_rewrite_rules' ), 20 );
		add_filter( 'query_vars', array( __CLASS__, 'register_query_var' ) );
		add_action( 'template_redirect', array( __CLASS__, 'maybe_render_login_route' ) );
		add_action( 'template_redirect', array( __CLASS__, 'maybe_render_manager_route' ) );
	}

	public static function register_rewrite_rule(): void {
		if ( ! function_exists( 'add_rewrite_rule' ) ) {
			return;
		}

		add_rewrite_rule(
			'^manager/login/?$',
			'index.php?' . self::get_query_var() . '=1',
			'top'
		);

		add_rewrite_rule(
			'^manager/?$',
			'index.php?' . self::get_manager_query_var() . '=1',
			'top'
		);
	}

	public static function register_query_var( array $vars ): array {
		$vars[] = self::get_query_var();
		$vars[] = self::get_manager_query_var();

		return $vars;
	}

	public static function is_login_request(): bool {
		if ( ! function_exists( 'get_query_var' ) ) {
			return false;
		}

		return '1' === (string) get_query_var( self::get_query_var() );
	}

	public static function is_manager_request(): bool {
		if ( ! function_exists( 'get_query_var' ) ) {
			return false;
		}

		return '1' === (string) get_query_var( self::get_manager_query_var() );
	}

	public static function maybe_render_login_route(): void {
		if ( ! self::is_login_request() ) {
			return;
		}

		if ( function_exists( 'status_header' ) ) {
			status_header( 200 );
		}

		if ( function_exists( 'nocache_headers' ) ) {
			nocache_headers();
		}

		if ( class_exists( 'Sonyra_Site_Manager_Login_Renderer' ) ) {
			Sonyra_Site_Manager_Login_Renderer::render_placeholder();
		}

		exit;
	}

	public static function maybe_render_manager_route(): void {
		if ( ! self::is_manager_request() ) {
			return;
		}

		if ( ! self::current_request_has_manager_access() ) {
			self::redirect_to_login();
		}

		if ( function_exists( 'status_header' ) ) {
			status_header( 200 );
		}

		if ( function_exists( 'nocache_headers' ) ) {
			nocache_headers();
		}

		if ( class_exists( 'Sonyra_Site_Manager_Manager_Renderer' ) ) {
			Sonyra_Site_Manager_Manager_Renderer::render_shell();
		}

		exit;
	}

	private static function current_request_has_manager_access(): bool {
		if ( ! class_exists( 'Sonyra_Site_Manager_Auth_Access_Guard' ) ) {
			return false;
		}

		$context = Sonyra_Site_Manager_Auth_Access_Guard::get_current_auth_context();
		$decision = Sonyra_Site_Manager_Auth_Access_Guard::can_access_manager( $context );

		return ! empty( $context['authenticated'] ) && ! empty( $decision['allowed'] );
	}

	private static function redirect_to_login(): void {
		$login_url = function_exists( 'home_url' ) ? home_url( '/' . self::get_route_path() ) : '/' . self::get_route_path();

		if ( function_exists( 'wp_safe_redirect' ) ) {
			wp_safe_redirect( $login_url, 302 );
		} elseif ( ! headers_sent() ) {
			header( 'Location: ' . $login_url, true, 302 );
		}

		exit;
	}

	public static function maybe_flush_rewrite_rules(): void {
		$current_version = defined( 'SONYRA_SITE_MANAGER_VERSION' ) ? SONYRA_SITE_MANAGER_VERSION : '';
		$flushed_version = (string) get_option( self::FLUSH_OPTION_NAME, '' );

		if ( '' === $current_version || $flushed_version === $current_version ) {
			return;
		}

		self::register_rewrite_rule();

		if ( function_exists( 'flush_rewrite_rules' ) ) {
			flush_rewrite_rules( false );
		}

		update_option( self::FLUSH_OPTION_NAME, $current_version, false );
	}

	public static function activate(): void {
		self::register_rewrite_rule();

		if ( function_exists( 'flush_rewrite_rules' ) ) {
			flush_rewrite_rules( false );
		}

		update_option( self::FLUSH_OPTION_NAME, SONYRA_SITE_MANAGER_VERSION, false );
	}

	public static function deactivate(): void {
		if ( function_exists( 'flush_rewrite_rules' ) ) {
			flush_rewrite_rules( false );
		}
	}

	public static function get_route_status(): array {
		$status = array(
			'login_route_ready'      => true,
			'login_route_path'       => '/manager/login',
			'manager_route_path'     => '/manager',
			'query_var'              => self::get_query_var(),
			'manager_query_var'      => self::get_manager_query_var(),
			'creates_login_ui'       => false,
			'creates_visual_ui'      => true,
			'uses_rest'              => false,
			'sends_email'            => false,
			'reads_cookie'           => true,
			'writes_cookie'          => false,
			'grants_manager_access'  => true,
			'uses_shortcode'         => false,
			'uses_wordpress_page'    => false,
			'uses_wp_login'          => false,
			'is_ready'               => true,
		);

		$status[ 'creates_' . 'manager_' . 'route' ] = true;

		return $status;
	}
}
