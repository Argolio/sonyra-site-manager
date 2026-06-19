<?php
/**
 * Plugin Name: Пульт сайта
 * Description: Базовый плагин платформы SONYRA Site Platform для управления самостоятельными страницами, брендом, модулями и кодами вставки.
 * Version: 0.1.105
 * Author: SONYRA STUDIO
 * Text Domain: sonyra-site-manager
 * Requires at least: 6.0
 * Requires PHP: 7.4
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'SONYRA_SITE_MANAGER_VERSION', '0.1.105' );
define( 'SONYRA_SITE_MANAGER_DB_VERSION', '0.1.1' );
define( 'SONYRA_SITE_MANAGER_SEED_VERSION', '0.1.0' );
define( 'SONYRA_SITE_MANAGER_FILE', __FILE__ );
define( 'SONYRA_SITE_MANAGER_DIR', plugin_dir_path( __FILE__ ) );
define( 'SONYRA_SITE_MANAGER_URL', plugin_dir_url( __FILE__ ) );
define( 'SONYRA_SITE_MANAGER_BASENAME', plugin_basename( __FILE__ ) );

require_once SONYRA_SITE_MANAGER_DIR . 'includes/class-sonyra-site-manager-plugin.php';
require_once SONYRA_SITE_MANAGER_DIR . 'includes/class-sonyra-site-manager-permissions.php';
require_once SONYRA_SITE_MANAGER_DIR . 'includes/class-sonyra-site-manager-status.php';
require_once SONYRA_SITE_MANAGER_DIR . 'includes/class-sonyra-site-manager-audit-log.php';
require_once SONYRA_SITE_MANAGER_DIR . 'includes/class-sonyra-site-manager-database.php';
require_once SONYRA_SITE_MANAGER_DIR . 'includes/class-sonyra-site-manager-database-health.php';
require_once SONYRA_SITE_MANAGER_DIR . 'includes/class-sonyra-site-manager-migrator.php';
require_once SONYRA_SITE_MANAGER_DIR . 'includes/class-sonyra-site-manager-seeder.php';
require_once SONYRA_SITE_MANAGER_DIR . 'includes/class-sonyra-site-manager-auth-roles.php';
require_once SONYRA_SITE_MANAGER_DIR . 'includes/class-sonyra-site-manager-auth-providers.php';
require_once SONYRA_SITE_MANAGER_DIR . 'includes/class-sonyra-site-manager-auth-identities.php';
require_once SONYRA_SITE_MANAGER_DIR . 'includes/class-sonyra-site-manager-auth.php';
require_once SONYRA_SITE_MANAGER_DIR . 'includes/class-sonyra-site-manager-otp-policy.php';
require_once SONYRA_SITE_MANAGER_DIR . 'includes/class-sonyra-site-manager-email-otp.php';
require_once SONYRA_SITE_MANAGER_DIR . 'includes/class-sonyra-site-manager-email-template.php';
require_once SONYRA_SITE_MANAGER_DIR . 'includes/class-sonyra-site-manager-email-delivery.php';
require_once SONYRA_SITE_MANAGER_DIR . 'includes/class-sonyra-site-manager-otp-request.php';
require_once SONYRA_SITE_MANAGER_DIR . 'includes/class-sonyra-site-manager-otp-verify.php';
require_once SONYRA_SITE_MANAGER_DIR . 'includes/class-sonyra-site-manager-auth-sessions.php';
require_once SONYRA_SITE_MANAGER_DIR . 'includes/class-sonyra-site-manager-auth-login.php';
require_once SONYRA_SITE_MANAGER_DIR . 'includes/class-sonyra-site-manager-auth-cookie.php';
require_once SONYRA_SITE_MANAGER_DIR . 'includes/class-sonyra-site-manager-auth-trusted-devices.php';
require_once SONYRA_SITE_MANAGER_DIR . 'includes/class-sonyra-site-manager-auth-access-guard.php';
require_once SONYRA_SITE_MANAGER_DIR . 'includes/class-sonyra-site-manager-rest-auth-controller.php';
require_once SONYRA_SITE_MANAGER_DIR . 'includes/class-sonyra-site-manager-rest-auth-smoke.php';
require_once SONYRA_SITE_MANAGER_DIR . 'includes/class-sonyra-site-manager-language-packs.php';
require_once SONYRA_SITE_MANAGER_DIR . 'includes/class-sonyra-site-manager-i18n.php';
require_once SONYRA_SITE_MANAGER_DIR . 'includes/class-sonyra-site-manager-help-library.php';
require_once SONYRA_SITE_MANAGER_DIR . 'includes/class-sonyra-site-manager-color-controller.php';
require_once SONYRA_SITE_MANAGER_DIR . 'includes/icons/class-sonyra-site-manager-icon-registry.php';
require_once SONYRA_SITE_MANAGER_DIR . 'includes/icons/class-sonyra-site-manager-icon-renderer.php';
require_once SONYRA_SITE_MANAGER_DIR . 'includes/class-sonyra-site-manager-page-elements-library.php';
require_once SONYRA_SITE_MANAGER_DIR . 'includes/class-sonyra-site-manager-pages-store.php';
require_once SONYRA_SITE_MANAGER_DIR . 'includes/class-sonyra-site-manager-rest-pages-controller.php';
require_once SONYRA_SITE_MANAGER_DIR . 'includes/class-sonyra-site-manager-rest-color-controller.php';
require_once SONYRA_SITE_MANAGER_DIR . 'includes/class-sonyra-site-manager-public-page-route.php';
require_once SONYRA_SITE_MANAGER_DIR . 'includes/class-sonyra-site-manager-pages-section.php';
require_once SONYRA_SITE_MANAGER_DIR . 'includes/class-sonyra-site-manager-login-renderer.php';
require_once SONYRA_SITE_MANAGER_DIR . 'includes/class-sonyra-site-manager-manager-renderer.php';
require_once SONYRA_SITE_MANAGER_DIR . 'includes/class-sonyra-site-manager-login-route.php';
require_once SONYRA_SITE_MANAGER_DIR . 'includes/class-sonyra-site-manager-installer.php';
require_once SONYRA_SITE_MANAGER_DIR . 'includes/class-sonyra-site-manager-activator.php';
require_once SONYRA_SITE_MANAGER_DIR . 'includes/class-sonyra-site-manager-deactivator.php';

register_activation_hook( __FILE__, array( 'Sonyra_Site_Manager_Activator', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'Sonyra_Site_Manager_Deactivator', 'deactivate' ) );

$sonyra_site_manager_plugin = new Sonyra_Site_Manager_Plugin( SONYRA_SITE_MANAGER_VERSION );
$sonyra_site_manager_plugin->run();
