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
