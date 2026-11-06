<?php
/**
 * WooCommerce admin navigation and foundation screen.
 *
 * @package WPEssentialReturnInsights
 */

namespace WPEssential\ReturnInsights;

defined( 'ABSPATH' ) || exit;

/**
 * Registers the plugin's WooCommerce admin entry point.
 */
final class Admin {
	/**
	 * Menu slug.
	 */
	private const PAGE_SLUG = 'wpessential-return-insights-prevention-woocommerce';

	/**
	 * Register admin navigation.
	 *
	 * @return void
	 */
	public static function register(): void {
		add_action( 'admin_menu', array( self::class, 'add_menu' ), 60 );
	}

	/**
	 * Add the page under WooCommerce.
	 *
	 * @return void
	 */
	public static function add_menu(): void {
		add_submenu_page(
			'woocommerce',
			__( 'Return Insights', WPE_RI_TEXT_DOMAIN ),
			__( 'Return Insights', WPE_RI_TEXT_DOMAIN ),
			Capabilities::VIEW,
			self::PAGE_SLUG,
			array( self::class, 'render_page' )
		);
	}

	/**
	 * Render the foundation screen until the insights UI is implemented.
	 *
	 * @return void
	 */
	public static function render_page(): void {
		if ( ! current_user_can( Capabilities::VIEW ) ) {
			wp_die( esc_html__( 'You do not have permission to view return insights.', WPE_RI_TEXT_DOMAIN ) );
		}

		echo '<div class="wrap">';
		echo '<h1>' . esc_html__( 'Return Insights', WPE_RI_TEXT_DOMAIN ) . '</h1>';
		echo '<p>' . esc_html__( 'Review return reasons and discover product issues that may need attention.', WPE_RI_TEXT_DOMAIN ) . '</p>';
		echo '<div class="notice notice-info inline"><p>';
		echo esc_html__( 'Return reason capture and reports are not available in this build yet.', WPE_RI_TEXT_DOMAIN );
		echo '</p></div>';
		echo '</div>';
	}
}
