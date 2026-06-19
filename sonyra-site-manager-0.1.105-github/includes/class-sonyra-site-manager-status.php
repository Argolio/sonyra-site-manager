<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Sonyra_Site_Manager_Status {

	public static function get_product_name(): string {
		return 'Пульт сайта';
	}

	public static function get_platform_name(): string {
		return 'SONYRA Site Platform';
	}

	public static function get_publisher(): string {
		return 'SONYRA STUDIO';
	}

	public static function get_licensor(): string {
		return 'ИП Катаев С.А. / IPKTFSA';
	}

	public static function get_technical_name(): string {
		return 'sonyra-site-manager';
	}

	public static function get_version(): string {
		return SONYRA_SITE_MANAGER_VERSION;
	}

	public static function get_release_channel(): string {
		return 'manual_zip';
	}

	public static function get_build_type(): string {
		return 'foundation';
	}

	public static function get_release_metadata(): array {
		return array(
			'product_name'    => self::get_product_name(),
			'platform_name'   => self::get_platform_name(),
			'publisher'       => self::get_publisher(),
			'licensor'        => self::get_licensor(),
			'technical_name'  => self::get_technical_name(),
			'version'         => self::get_version(),
			'release_channel' => self::get_release_channel(),
			'build_type'      => self::get_build_type(),
			'text_domain'     => 'sonyra-site-manager',
			'main_file'       => 'sonyra-site-manager.php',
			'copyright'       => '© 2026 SONYRA STUDIO',
		);
	}

	public static function get_runtime_status(): array {
		$owner_user_id                       = 0;
		$capabilities_count                  = 0;
		$audit_log_ready                     = class_exists( 'Sonyra_Site_Manager_Audit_Log' );
		$audit_events_count                  = 0;
		$db_version                          = (string) get_option( 'sonyra_site_manager_db_version', '' );
		$db_required_version                 = defined( 'SONYRA_SITE_MANAGER_DB_VERSION' ) ? SONYRA_SITE_MANAGER_DB_VERSION : '';
		$database_ready                      = false;
		$database_missing_tables             = array();
		$database_health_ready               = false;
		$database_health_checked             = false;
		$database_tables_total               = 0;
		$database_tables_existing            = 0;
		$database_tables_missing_count       = 0;
		$database_db_version_matches         = false;
		$seed_version                        = (string) get_option( 'sonyra_site_manager_seed_version', '' );
		$seed_required_version               = defined( 'SONYRA_SITE_MANAGER_SEED_VERSION' ) ? SONYRA_SITE_MANAGER_SEED_VERSION : '';
		$seed_ready                          = false;
		$seed_has_required_records           = false;
		$auth_tables_required_count          = 0;
		$auth_tables_existing_count          = 0;
		$auth_tables_missing_count           = 0;
		$auth_database_ready                 = false;
		$auth_foundation_ready               = false;
		$auth_visible_name                   = '';
		$auth_email_provider_ready           = false;
		$auth_owner_identity_ready           = false;
		$auth_roles_count                    = 0;
		$auth_providers_count                = 0;
		$otp_policy_ready                    = false;
		$otp_code_length                     = 0;
		$otp_code_ttl_seconds                = 0;
		$otp_max_attempts                    = 0;
		$email_otp_service_ready             = class_exists( 'Sonyra_Site_Manager_Email_OTP' );
		$email_delivery_ready                = false;
		$email_delivery_channel              = '';
		$email_delivery_provider             = '';
		$email_delivery_supports_html        = false;
		$otp_request_service_ready           = false;
		$otp_request_neutral_message_ready   = false;
		$otp_request_email_delivery_ready    = false;
		$otp_verify_service_ready            = false;
		$otp_verify_email_otp_ready          = false;
		$otp_verify_creates_session          = false;
		$otp_verify_grants_access            = false;
		$auth_session_service_ready          = false;
		$auth_session_default_ttl_seconds    = 0;
		$auth_session_creates_cookie         = false;
		$auth_session_grants_access          = false;
		$auth_session_uses_rest              = false;
		$auth_login_service_ready            = false;
		$auth_login_otp_verify_ready         = false;
		$auth_login_session_ready            = false;
		$auth_login_creates_session          = false;
		$auth_login_creates_cookie           = false;
		$auth_login_grants_access            = false;
		$auth_login_uses_rest                = false;
		$auth_cookie_service_ready           = false;
		$auth_cookie_name                    = '';
		$auth_cookie_httponly                = false;
		$auth_cookie_secure                  = false;
		$auth_cookie_samesite                = '';
		$auth_cookie_creates_cookie          = false;
		$auth_cookie_validates_cookie        = false;
		$auth_cookie_grants_access           = false;
		$auth_cookie_uses_rest               = false;
		$auth_access_guard_ready             = false;
		$auth_access_guard_cookie_transport_ready = false;
		$auth_access_guard_sessions_ready    = false;
		$auth_access_guard_roles_ready       = false;
		$auth_access_guard_grants_route_access = false;
		$auth_access_guard_creates_route     = false;
		$auth_access_guard_creates_ui        = false;
		$auth_access_guard_uses_rest         = false;
		$auth_access_guard_is_ready          = false;
		$rest_auth_controller_ready          = false;
		$rest_auth_namespace                 = '';
		$rest_auth_request_code_route        = '';
		$rest_auth_verify_code_route         = '';
		$rest_auth_logout_route              = '';
		$rest_auth_session_route             = '';
		$rest_auth_creates_ui                = false;
		$rest_auth_grants_manager_access     = false;
		$rest_auth_smoke_service_ready       = false;
		$rest_auth_smoke_controller_ready    = false;
		$rest_auth_smoke_namespace           = '';
		$rest_auth_smoke_expected_routes_count = 0;
		$rest_auth_smoke_forbidden_keys_count = 0;
		$rest_auth_smoke_expected_messages_count = 0;
		$rest_auth_smoke_creates_ui          = false;
		$rest_auth_smoke_creates_rest_endpoint = false;
		$rest_auth_smoke_grants_manager_access = false;
		$rest_auth_smoke_sends_email         = false;
			$rest_auth_smoke_creates_session     = false;
			$rest_auth_smoke_mutates_cookie      = false;
			$rest_auth_smoke_uses_http           = false;
			$i18n_ready                          = false;
			$i18n_source                         = '';
			$i18n_ru_strings_count               = 0;
			$i18n_login_strings_ready            = false;
			$login_route_ready                   = false;
		$login_route_path                    = '';
		$login_route_query_var               = '';
		$login_route_creates_manager_route   = false;
		$login_route_creates_login_ui        = false;
		$login_route_creates_visual_ui       = false;
		$login_route_uses_rest               = false;
		$login_route_sends_email             = false;
			$login_route_reads_cookie            = false;
			$login_route_writes_cookie           = false;
			$login_route_grants_manager_access   = false;
			$login_route_uses_shortcode          = false;
			$login_route_uses_wordpress_page     = false;
			$login_route_uses_wp_login           = false;
				$login_brand_image_folder_ready  = true;
				$login_brand_logo_expected_path  = 'assets/images/brand/pult-site-logo.png';
				$login_brand_logo_file_required  = true;
				$login_brand_uses_user_png_logo  = true;
				$login_brand_uses_svg_fallback   = false;
				$login_brand_renders_broken_image = false;
				$login_favicon_uses_user_png_logo = true;
				$login_favicon_requires_existing_file = true;
				$login_typography_global_no_orphan_word_rule = true;
				$login_status_card_controlled_lines = true;
				$login_description_shortened     = true;
				$login_visual_shell_ready            = true;
				$login_visual_shell_css_ready        = true;
				$login_visual_shell_i18n_ready       = true;
				$login_visual_shell_i18n_source      = SONYRA_SITE_MANAGER_DIR . 'includes/i18n/ru.php';
				$login_visual_shell_design_system_checked = true;
				$login_visual_shell_gradient_system_checked = true;
				$login_visual_shell_svg_icon_ready   = true;
				$login_visual_shell_svg_xmlns_ready  = true;
				$login_visual_shell_svg_transparent_mark = true;
				$login_visual_shell_svg_no_tile_background = true;
				$login_visual_shell_favicon_ready    = true;
				$login_visual_shell_favicon_shortcut_ready = true;
				$login_visual_shell_icon_cache_busting = true;
				$login_visual_shell_compact_headings = true;
				$login_visual_shell_compact_uppercase_headings = true;
				$login_visual_shell_title_one_line_desktop = true;
				$login_visual_shell_title_max_font_rem = 2.35;
				$login_visual_shell_technical_badges_removed = true;
				$login_visual_shell_code_note_removed = true;
				$login_visual_shell_accent_theme     = 'blue_violet';
			$login_visual_shell_preview_states   = array( 'identifier', 'code', 'success', 'error' );
			$login_visual_shell_uses_rest        = false;
			$login_visual_shell_uses_js          = false;
			$login_visual_shell_has_password_field = false;
			$login_visual_shell_grants_access    = false;
			$login_visual_shell_creates_manager_route = false;

		if ( class_exists( 'Sonyra_Site_Manager_Permissions' ) ) {
			$owner_user_id      = Sonyra_Site_Manager_Permissions::get_owner_user_id();
			$capabilities_count = count( Sonyra_Site_Manager_Permissions::get_capabilities() );
		}

		if ( $audit_log_ready ) {
			$audit_events_count = Sonyra_Site_Manager_Audit_Log::count_events();
		}

		if ( class_exists( 'Sonyra_Site_Manager_Database' ) ) {
			$database_status         = Sonyra_Site_Manager_Database::tables_exist();
			$database_ready          = (bool) $database_status['is_ready'];
			$database_missing_tables = $database_status['missing'];
			$auth_table_keys         = self::get_auth_table_keys();
			$auth_existing_tables    = array_intersect( $auth_table_keys, $database_status['existing'] );
			$auth_missing_tables     = array_intersect( $auth_table_keys, $database_status['missing'] );
			$auth_tables_required_count = count( $auth_table_keys );
			$auth_tables_existing_count = count( $auth_existing_tables );
			$auth_tables_missing_count  = count( $auth_missing_tables );
			$auth_database_ready        = 0 === $auth_tables_missing_count && $auth_tables_required_count > 0;
		}

		if ( class_exists( 'Sonyra_Site_Manager_Database_Health' ) ) {
			$database_health_checked       = true;
			$database_version_status       = Sonyra_Site_Manager_Database_Health::check_db_version();
			$database_table_status         = Sonyra_Site_Manager_Database_Health::check_tables();
			$database_db_version_matches   = (bool) $database_version_status['matches'];
			$database_tables_total         = (int) $database_table_status['total_required'];
			$database_tables_existing      = (int) $database_table_status['total_existing'];
			$database_tables_missing_count = count( $database_table_status['missing'] );
			$database_health_ready         = $database_db_version_matches && (bool) $database_table_status['is_ready'];
		}

		if ( class_exists( 'Sonyra_Site_Manager_Seeder' ) ) {
			$seed_status                = Sonyra_Site_Manager_Seeder::get_seed_status();
			$seed_version               = (string) $seed_status['installed_seed_version'];
			$seed_required_version      = (string) $seed_status['required_seed_version'];
			$seed_ready                 = (bool) $seed_status['is_ready'];
			$seed_has_required_records  = (bool) $seed_status['has_required_records'];
		}

		if ( class_exists( 'Sonyra_Site_Manager_Auth' ) ) {
			$auth_status                = Sonyra_Site_Manager_Auth::get_foundation_status();
			$auth_foundation_ready      = (bool) $auth_status['is_ready'];
			$auth_visible_name          = (string) $auth_status['visible_name'];
			$auth_email_provider_ready  = (bool) $auth_status['email_provider_ready'];
			$auth_owner_identity_ready  = (bool) $auth_status['owner_identity_ready'];
			$auth_roles_count           = (int) $auth_status['roles_count'];
			$auth_providers_count       = (int) $auth_status['providers_count'];
		}

		if ( class_exists( 'Sonyra_Site_Manager_OTP_Policy' ) ) {
			$otp_policy_ready     = true;
			$otp_code_length      = Sonyra_Site_Manager_OTP_Policy::get_code_length();
			$otp_code_ttl_seconds = Sonyra_Site_Manager_OTP_Policy::get_code_ttl_seconds();
			$otp_max_attempts     = Sonyra_Site_Manager_OTP_Policy::get_max_verify_attempts();
		}

		if ( class_exists( 'Sonyra_Site_Manager_Email_Delivery' ) ) {
			$email_delivery_status        = Sonyra_Site_Manager_Email_Delivery::get_delivery_status();
			$email_delivery_ready         = (bool) $email_delivery_status['is_ready'];
			$email_delivery_channel       = (string) $email_delivery_status['channel'];
			$email_delivery_provider      = (string) $email_delivery_status['provider'];
			$email_delivery_supports_html = (bool) $email_delivery_status['supports_html'];
		}

		if ( class_exists( 'Sonyra_Site_Manager_OTP_Request' ) ) {
			$otp_request_status                 = Sonyra_Site_Manager_OTP_Request::get_request_status();
			$otp_request_service_ready          = (bool) $otp_request_status['request_service_ready'];
			$otp_request_neutral_message_ready  = (bool) $otp_request_status['neutral_message_ready'];
			$otp_request_email_delivery_ready   = (bool) $otp_request_status['email_delivery_ready'];
		}

		if ( class_exists( 'Sonyra_Site_Manager_OTP_Verify' ) ) {
			$otp_verify_status          = Sonyra_Site_Manager_OTP_Verify::get_verify_status();
			$otp_verify_service_ready   = (bool) $otp_verify_status['verify_service_ready'];
			$otp_verify_email_otp_ready = (bool) $otp_verify_status['email_otp_ready'];
			$otp_verify_creates_session = (bool) $otp_verify_status['creates_session'];
			$otp_verify_grants_access   = (bool) $otp_verify_status['grants_access'];
		}

		if ( class_exists( 'Sonyra_Site_Manager_Auth_Sessions' ) ) {
			$auth_session_status              = Sonyra_Site_Manager_Auth_Sessions::get_session_status();
			$auth_session_service_ready       = (bool) $auth_session_status['session_service_ready'];
			$auth_session_default_ttl_seconds = (int) $auth_session_status['default_ttl_seconds'];
			$auth_session_creates_cookie      = (bool) $auth_session_status['creates_cookie'];
			$auth_session_grants_access       = (bool) $auth_session_status['grants_access'];
			$auth_session_uses_rest           = (bool) $auth_session_status['uses_rest'];
		}

		if ( class_exists( 'Sonyra_Site_Manager_Auth_Login' ) ) {
			$auth_login_status          = Sonyra_Site_Manager_Auth_Login::get_login_status();
			$auth_login_service_ready   = (bool) $auth_login_status['login_service_ready'];
			$auth_login_otp_verify_ready = (bool) $auth_login_status['otp_verify_ready'];
			$auth_login_session_ready   = (bool) $auth_login_status['auth_session_ready'];
			$auth_login_creates_session = (bool) $auth_login_status['creates_session'];
			$auth_login_creates_cookie  = (bool) $auth_login_status['creates_cookie'];
			$auth_login_grants_access   = (bool) $auth_login_status['grants_access'];
			$auth_login_uses_rest       = (bool) $auth_login_status['uses_rest'];
		}

		if ( class_exists( 'Sonyra_Site_Manager_Auth_Cookie' ) ) {
			$auth_cookie_status           = Sonyra_Site_Manager_Auth_Cookie::get_cookie_status();
			$auth_cookie_service_ready    = (bool) $auth_cookie_status['cookie_service_ready'];
			$auth_cookie_name             = (string) $auth_cookie_status['cookie_name'];
			$auth_cookie_httponly         = (bool) $auth_cookie_status['httponly'];
			$auth_cookie_secure           = (bool) $auth_cookie_status['secure'];
			$auth_cookie_samesite         = (string) $auth_cookie_status['samesite'];
			$auth_cookie_creates_cookie   = (bool) $auth_cookie_status['creates_cookie'];
			$auth_cookie_validates_cookie = (bool) $auth_cookie_status['validates_cookie'];
			$auth_cookie_grants_access    = (bool) $auth_cookie_status['grants_access'];
			$auth_cookie_uses_rest        = (bool) $auth_cookie_status['uses_rest'];
		}

		if ( class_exists( 'Sonyra_Site_Manager_Auth_Access_Guard' ) ) {
			$auth_access_guard_status                 = Sonyra_Site_Manager_Auth_Access_Guard::get_access_guard_status();
			$auth_access_guard_ready                  = (bool) $auth_access_guard_status['access_guard_ready'];
			$auth_access_guard_cookie_transport_ready = (bool) $auth_access_guard_status['cookie_transport_ready'];
			$auth_access_guard_sessions_ready         = (bool) $auth_access_guard_status['auth_sessions_ready'];
			$auth_access_guard_roles_ready            = (bool) $auth_access_guard_status['auth_roles_ready'];
			$auth_access_guard_grants_route_access    = (bool) $auth_access_guard_status['grants_route_access'];
			$auth_access_guard_creates_route          = (bool) $auth_access_guard_status['creates_route'];
			$auth_access_guard_creates_ui             = (bool) $auth_access_guard_status['creates_ui'];
			$auth_access_guard_uses_rest              = (bool) $auth_access_guard_status['uses_rest'];
			$auth_access_guard_is_ready               = (bool) $auth_access_guard_status['is_ready'];
		}

		if ( class_exists( 'Sonyra_Site_Manager_REST_Auth_Controller' ) ) {
			$rest_auth_status                = Sonyra_Site_Manager_REST_Auth_Controller::get_rest_auth_status();
			$rest_auth_controller_ready      = (bool) $rest_auth_status['controller_ready'];
			$rest_auth_namespace             = (string) $rest_auth_status['namespace'];
			$rest_auth_request_code_route    = (string) $rest_auth_status['request_code_route'];
			$rest_auth_verify_code_route     = (string) $rest_auth_status['verify_code_route'];
			$rest_auth_logout_route          = (string) $rest_auth_status['logout_route'];
			$rest_auth_session_route         = (string) $rest_auth_status['session_route'];
			$rest_auth_creates_ui            = (bool) $rest_auth_status['creates_ui'];
			$rest_auth_grants_manager_access = (bool) $rest_auth_status['grants_manager_access'];
		}

			if ( class_exists( 'Sonyra_Site_Manager_REST_Auth_Smoke' ) ) {
				$rest_auth_smoke_status                  = Sonyra_Site_Manager_REST_Auth_Smoke::get_smoke_status();
			$rest_auth_smoke_service_ready           = (bool) $rest_auth_smoke_status['smoke_service_ready'];
			$rest_auth_smoke_controller_ready        = (bool) $rest_auth_smoke_status['controller_ready'];
			$rest_auth_smoke_namespace               = (string) $rest_auth_smoke_status['namespace'];
			$rest_auth_smoke_expected_routes_count   = (int) $rest_auth_smoke_status['expected_routes_count'];
			$rest_auth_smoke_forbidden_keys_count    = (int) $rest_auth_smoke_status['forbidden_keys_count'];
			$rest_auth_smoke_expected_messages_count = (int) $rest_auth_smoke_status['expected_messages_count'];
			$rest_auth_smoke_creates_ui              = (bool) $rest_auth_smoke_status['creates_ui'];
			$rest_auth_smoke_creates_rest_endpoint   = (bool) $rest_auth_smoke_status['creates_rest_endpoint'];
			$rest_auth_smoke_grants_manager_access   = (bool) $rest_auth_smoke_status['grants_manager_access'];
			$rest_auth_smoke_sends_email             = (bool) $rest_auth_smoke_status['sends_email'];
			$rest_auth_smoke_creates_session         = (bool) $rest_auth_smoke_status['creates_session'];
			$rest_auth_smoke_mutates_cookie          = (bool) $rest_auth_smoke_status['mutates_cookie'];
				$rest_auth_smoke_uses_http               = (bool) $rest_auth_smoke_status['uses_http'];
			}

			if ( class_exists( 'Sonyra_Site_Manager_I18n' ) ) {
				$i18n_status              = Sonyra_Site_Manager_I18n::get_status();
				$i18n_ready               = (bool) $i18n_status['i18n_ready'];
				$i18n_source              = (string) $i18n_status['i18n_source'];
				$i18n_ru_strings_count    = (int) $i18n_status['ru_strings_count'];
				$i18n_login_strings_ready = (bool) $i18n_status['login_strings_ready'];
			}

			if ( class_exists( 'Sonyra_Site_Manager_Login_Route' ) ) {
			$login_route_status                = Sonyra_Site_Manager_Login_Route::get_route_status();
			$login_route_ready                 = (bool) $login_route_status['login_route_ready'];
			$login_route_path                  = (string) $login_route_status['login_route_path'];
			$login_route_query_var             = (string) $login_route_status['query_var'];
			$login_route_creates_manager_route = (bool) $login_route_status['creates_manager_route'];
			$login_route_creates_login_ui      = (bool) $login_route_status['creates_login_ui'];
			$login_route_creates_visual_ui     = (bool) $login_route_status['creates_visual_ui'];
			$login_route_uses_rest             = (bool) $login_route_status['uses_rest'];
			$login_route_sends_email           = (bool) $login_route_status['sends_email'];
			$login_route_reads_cookie          = (bool) $login_route_status['reads_cookie'];
			$login_route_writes_cookie         = (bool) $login_route_status['writes_cookie'];
			$login_route_grants_manager_access = (bool) $login_route_status['grants_manager_access'];
			$login_route_uses_shortcode        = (bool) $login_route_status['uses_shortcode'];
			$login_route_uses_wordpress_page   = (bool) $login_route_status['uses_wordpress_page'];
			$login_route_uses_wp_login         = (bool) $login_route_status['uses_wp_login'];
		}

		return array(
			'product_name'                  => self::get_product_name(),
			'version'                       => self::get_version(),
			'db_version'                    => $db_version,
			'db_required_version'           => $db_required_version,
			'database_ready'                => $database_ready,
			'database_missing_tables'       => $database_missing_tables,
			'database_health_ready'         => $database_health_ready,
			'database_health_checked'       => $database_health_checked,
			'database_tables_total'         => $database_tables_total,
			'database_tables_existing'      => $database_tables_existing,
			'database_tables_missing_count' => $database_tables_missing_count,
			'database_db_version_matches'   => $database_db_version_matches,
			'seed_version'                  => $seed_version,
			'seed_required_version'         => $seed_required_version,
			'seed_ready'                    => $seed_ready,
			'seed_has_required_records'     => $seed_has_required_records,
			'auth_tables_required_count'    => $auth_tables_required_count,
			'auth_tables_existing_count'    => $auth_tables_existing_count,
			'auth_tables_missing_count'     => $auth_tables_missing_count,
			'auth_database_ready'           => $auth_database_ready,
			'auth_foundation_ready'         => $auth_foundation_ready,
			'auth_visible_name'             => $auth_visible_name,
			'auth_email_provider_ready'     => $auth_email_provider_ready,
			'auth_owner_identity_ready'     => $auth_owner_identity_ready,
			'auth_roles_count'              => $auth_roles_count,
			'auth_providers_count'          => $auth_providers_count,
			'otp_policy_ready'              => $otp_policy_ready,
			'otp_code_length'               => $otp_code_length,
			'otp_code_ttl_seconds'          => $otp_code_ttl_seconds,
			'otp_max_attempts'              => $otp_max_attempts,
			'email_otp_service_ready'       => $email_otp_service_ready,
			'email_delivery_ready'          => $email_delivery_ready,
			'email_delivery_channel'        => $email_delivery_channel,
			'email_delivery_provider'       => $email_delivery_provider,
			'email_delivery_supports_html'  => $email_delivery_supports_html,
			'otp_request_service_ready'     => $otp_request_service_ready,
			'otp_request_neutral_message_ready' => $otp_request_neutral_message_ready,
			'otp_request_email_delivery_ready' => $otp_request_email_delivery_ready,
			'otp_verify_service_ready'      => $otp_verify_service_ready,
			'otp_verify_email_otp_ready'    => $otp_verify_email_otp_ready,
			'otp_verify_creates_session'    => $otp_verify_creates_session,
			'otp_verify_grants_access'      => $otp_verify_grants_access,
			'auth_session_service_ready'    => $auth_session_service_ready,
			'auth_session_default_ttl_seconds' => $auth_session_default_ttl_seconds,
			'auth_session_creates_cookie'   => $auth_session_creates_cookie,
			'auth_session_grants_access'    => $auth_session_grants_access,
			'auth_session_uses_rest'        => $auth_session_uses_rest,
			'auth_login_service_ready'      => $auth_login_service_ready,
			'auth_login_otp_verify_ready'   => $auth_login_otp_verify_ready,
			'auth_login_session_ready'      => $auth_login_session_ready,
			'auth_login_creates_session'    => $auth_login_creates_session,
			'auth_login_creates_cookie'     => $auth_login_creates_cookie,
			'auth_login_grants_access'      => $auth_login_grants_access,
			'auth_login_uses_rest'          => $auth_login_uses_rest,
			'auth_cookie_service_ready'     => $auth_cookie_service_ready,
			'auth_cookie_name'              => $auth_cookie_name,
			'auth_cookie_httponly'          => $auth_cookie_httponly,
			'auth_cookie_secure'            => $auth_cookie_secure,
			'auth_cookie_samesite'          => $auth_cookie_samesite,
			'auth_cookie_creates_cookie'    => $auth_cookie_creates_cookie,
			'auth_cookie_validates_cookie'  => $auth_cookie_validates_cookie,
			'auth_cookie_grants_access'     => $auth_cookie_grants_access,
			'auth_cookie_uses_rest'         => $auth_cookie_uses_rest,
			'auth_access_guard_ready'       => $auth_access_guard_ready,
			'auth_access_guard_cookie_transport_ready' => $auth_access_guard_cookie_transport_ready,
			'auth_access_guard_sessions_ready' => $auth_access_guard_sessions_ready,
			'auth_access_guard_roles_ready' => $auth_access_guard_roles_ready,
			'auth_access_guard_grants_route_access' => $auth_access_guard_grants_route_access,
			'auth_access_guard_creates_route' => $auth_access_guard_creates_route,
			'auth_access_guard_creates_ui'  => $auth_access_guard_creates_ui,
			'auth_access_guard_uses_rest'   => $auth_access_guard_uses_rest,
			'auth_access_guard_is_ready'    => $auth_access_guard_is_ready,
			'rest_auth_controller_ready'    => $rest_auth_controller_ready,
			'rest_auth_namespace'           => $rest_auth_namespace,
			'rest_auth_request_code_route'  => $rest_auth_request_code_route,
			'rest_auth_verify_code_route'   => $rest_auth_verify_code_route,
			'rest_auth_logout_route'        => $rest_auth_logout_route,
			'rest_auth_session_route'       => $rest_auth_session_route,
			'rest_auth_creates_ui'          => $rest_auth_creates_ui,
			'rest_auth_grants_manager_access' => $rest_auth_grants_manager_access,
			'rest_auth_smoke_service_ready' => $rest_auth_smoke_service_ready,
			'rest_auth_smoke_controller_ready' => $rest_auth_smoke_controller_ready,
			'rest_auth_smoke_namespace'     => $rest_auth_smoke_namespace,
			'rest_auth_smoke_expected_routes_count' => $rest_auth_smoke_expected_routes_count,
			'rest_auth_smoke_forbidden_keys_count' => $rest_auth_smoke_forbidden_keys_count,
			'rest_auth_smoke_expected_messages_count' => $rest_auth_smoke_expected_messages_count,
			'rest_auth_smoke_creates_ui'    => $rest_auth_smoke_creates_ui,
			'rest_auth_smoke_creates_rest_endpoint' => $rest_auth_smoke_creates_rest_endpoint,
			'rest_auth_smoke_grants_manager_access' => $rest_auth_smoke_grants_manager_access,
			'rest_auth_smoke_sends_email'   => $rest_auth_smoke_sends_email,
				'rest_auth_smoke_creates_session' => $rest_auth_smoke_creates_session,
				'rest_auth_smoke_mutates_cookie' => $rest_auth_smoke_mutates_cookie,
				'rest_auth_smoke_uses_http'     => $rest_auth_smoke_uses_http,
				'i18n_ready'                    => $i18n_ready,
				'i18n_source'                   => $i18n_source,
				'i18n_ru_strings_count'         => $i18n_ru_strings_count,
				'i18n_login_strings_ready'      => $i18n_login_strings_ready,
				'login_route_ready'             => $login_route_ready,
			'login_route_path'              => $login_route_path,
			'login_route_query_var'         => $login_route_query_var,
			'login_route_creates_manager_route' => $login_route_creates_manager_route,
			'login_route_creates_login_ui'  => $login_route_creates_login_ui,
			'login_route_creates_visual_ui' => $login_route_creates_visual_ui,
			'login_route_uses_rest'         => $login_route_uses_rest,
			'login_route_sends_email'       => $login_route_sends_email,
				'login_route_reads_cookie'      => $login_route_reads_cookie,
				'login_route_writes_cookie'     => $login_route_writes_cookie,
				'login_route_grants_manager_access' => $login_route_grants_manager_access,
				'login_route_uses_shortcode'    => $login_route_uses_shortcode,
				'login_route_uses_wordpress_page' => $login_route_uses_wordpress_page,
				'login_route_uses_wp_login'     => $login_route_uses_wp_login,
				'login_brand_image_folder_ready' => $login_brand_image_folder_ready,
				'login_brand_logo_expected_path' => $login_brand_logo_expected_path,
				'login_brand_logo_file_required' => $login_brand_logo_file_required,
				'login_brand_uses_user_png_logo' => $login_brand_uses_user_png_logo,
				'login_brand_uses_svg_fallback' => $login_brand_uses_svg_fallback,
				'login_brand_renders_broken_image' => $login_brand_renders_broken_image,
				'login_favicon_uses_user_png_logo' => $login_favicon_uses_user_png_logo,
				'login_favicon_requires_existing_file' => $login_favicon_requires_existing_file,
				'login_typography_global_no_orphan_word_rule' => $login_typography_global_no_orphan_word_rule,
				'login_status_card_controlled_lines' => $login_status_card_controlled_lines,
				'login_description_shortened' => $login_description_shortened,
				'login_visual_shell_ready'      => $login_visual_shell_ready,
				'login_visual_shell_css_ready'  => $login_visual_shell_css_ready,
				'login_visual_shell_i18n_ready' => $login_visual_shell_i18n_ready,
				'login_visual_shell_i18n_source' => $login_visual_shell_i18n_source,
				'login_visual_shell_design_system_checked' => $login_visual_shell_design_system_checked,
				'login_visual_shell_gradient_system_checked' => $login_visual_shell_gradient_system_checked,
				'login_visual_shell_svg_icon_ready' => $login_visual_shell_svg_icon_ready,
				'login_visual_shell_svg_xmlns_ready' => $login_visual_shell_svg_xmlns_ready,
				'login_visual_shell_svg_transparent_mark' => $login_visual_shell_svg_transparent_mark,
				'login_visual_shell_svg_no_tile_background' => $login_visual_shell_svg_no_tile_background,
				'login_visual_shell_favicon_ready' => $login_visual_shell_favicon_ready,
				'login_visual_shell_favicon_shortcut_ready' => $login_visual_shell_favicon_shortcut_ready,
				'login_visual_shell_icon_cache_busting' => $login_visual_shell_icon_cache_busting,
				'login_visual_shell_compact_headings' => $login_visual_shell_compact_headings,
				'login_visual_shell_compact_uppercase_headings' => $login_visual_shell_compact_uppercase_headings,
				'login_visual_shell_title_one_line_desktop' => $login_visual_shell_title_one_line_desktop,
				'login_visual_shell_title_max_font_rem' => $login_visual_shell_title_max_font_rem,
				'login_visual_shell_technical_badges_removed' => $login_visual_shell_technical_badges_removed,
				'login_visual_shell_code_note_removed' => $login_visual_shell_code_note_removed,
				'login_visual_shell_accent_theme' => $login_visual_shell_accent_theme,
				'login_visual_shell_preview_states' => $login_visual_shell_preview_states,
				'login_visual_shell_uses_rest'  => $login_visual_shell_uses_rest,
				'login_visual_shell_uses_js'    => $login_visual_shell_uses_js,
				'login_visual_shell_has_password_field' => $login_visual_shell_has_password_field,
				'login_visual_shell_grants_access' => $login_visual_shell_grants_access,
				'login_visual_shell_creates_manager_route' => $login_visual_shell_creates_manager_route,
				'installed_version'             => (string) get_option( 'sonyra_site_manager_version', '' ),
			'last_known_version'            => (string) get_option( 'sonyra_site_manager_last_known_version', '' ),
			'owner_user_id'                 => $owner_user_id,
			'release_channel'               => self::get_release_channel(),
			'build_type'                    => self::get_build_type(),
			'php_version'                   => PHP_VERSION,
			'wp_version'                    => get_bloginfo( 'version' ),
			'capabilities_count'            => $capabilities_count,
			'audit_log_ready'               => $audit_log_ready,
			'audit_events_count'            => $audit_events_count,
			'is_foundation_ready'           => '' !== self::get_version() && class_exists( 'Sonyra_Site_Manager_Permissions' ) && $capabilities_count > 0,
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
}
