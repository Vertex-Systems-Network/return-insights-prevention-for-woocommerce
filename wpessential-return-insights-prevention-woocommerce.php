<?php
/**
 * Plugin Name: WPEssential Return Insights & Prevention for WooCommerce
 * Description: Helps merchants understand repeat return reasons and identify product improvements.
 * Version: 0.1.0
 * Requires at least: 7.0
 * Requires PHP: 8.3
 * Requires Plugins: woocommerce
 * WC requires at least: 11.1
 * Text Domain: wpessential-return-insights-prevention-woocommerce
 * Domain Path: /languages
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 *
 * @package WPEssentialReturnInsights
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'WPE_RI_VERSION', '0.1.0' );
define( 'WPE_RI_FILE', __FILE__ );
define( 'WPE_RI_PATH', plugin_dir_path( __FILE__ ) );
define( 'WPE_RI_URL', plugin_dir_url( __FILE__ ) );
define( 'WPE_RI_TEXT_DOMAIN', 'wpessential-return-insights-prevention-woocommerce' );
define( 'WPE_RI_MIN_WP_VERSION', '7.0' );
define( 'WPE_RI_MIN_PHP_VERSION', '8.3' );
define( 'WPE_RI_MIN_WC_VERSION', '11.1' );

// Guard Composer's generated platform check so an unsupported PHP version cannot
// take the whole site down before the plugin can show a useful requirement notice.
if ( version_compare( PHP_VERSION, WPE_RI_MIN_PHP_VERSION, '<' ) ) {
    add_action(
        'admin_notices',
        static function (): void {
            if ( ! current_user_can( 'activate_plugins' ) ) {
                return;
            }

            echo '<div class="notice notice-error"><p>';
            echo esc_html( sprintf( 'WPEssential Return Insights requires PHP %s or newer. Upgrade PHP, then activate the plugin again.', WPE_RI_MIN_PHP_VERSION ) );
            echo '</p></div>';
        }
    );

    register_activation_hook(
        __FILE__,
        static function (): void {
            wp_die(
                esc_html( sprintf( 'WPEssential Return Insights requires PHP %s or newer.', WPE_RI_MIN_PHP_VERSION ) ),
                esc_html__( 'Plugin requirements not met', WPE_RI_TEXT_DOMAIN ),
                array( 'back_link' => true )
            );
        }
    );

    return;
}

require_once WPE_RI_PATH . 'vendor/autoload.php';

register_activation_hook(
	__FILE__,
	array( '\\WPEssential\\ReturnInsights\\Plugin', 'activate' )
);

register_deactivation_hook(
	__FILE__,
	array( '\\WPEssential\\ReturnInsights\\Plugin', 'deactivate' )
);

add_action(
	'plugins_loaded',
	array( '\\WPEssential\\ReturnInsights\\Plugin', 'boot' ),
	20
);
