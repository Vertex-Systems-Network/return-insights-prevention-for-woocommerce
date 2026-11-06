<?php
/**
 * Main plugin lifecycle and dependency checks.
 *
 * @package WPEssentialReturnInsights
 */

namespace WPEssential\ReturnInsights;

defined( 'ABSPATH' ) || exit;

/**
 * Boots the plugin when WordPress and WooCommerce are ready.
 */
final class Plugin {
	/**
	 * Validate requirements during activation and grant role capabilities.
	 *
	 * @param bool $network_wide Whether activation was requested network-wide.
	 * @return void
	 */
	public static function activate( bool $network_wide = false ): void {
		if ( is_multisite() && $network_wide ) {
			wp_die(
				esc_html__( 'WPEssential Return Insights must be activated on each site separately.', WPE_RI_TEXT_DOMAIN ),
				esc_html__( 'Network activation is not supported', WPE_RI_TEXT_DOMAIN ),
				array( 'back_link' => true )
			);
		}

		$errors = self::get_requirement_errors();
		if ( $errors ) {
			wp_die(
				esc_html( implode( ' ', $errors ) ),
				esc_html__( 'Plugin requirements not met', WPE_RI_TEXT_DOMAIN ),
				array( 'back_link' => true )
			);
		}

		if ( ! Installer::install() ) {
			wp_die( esc_html__( 'The Return Insights data table could not be created.', WPE_RI_TEXT_DOMAIN ), esc_html__( 'Database setup failed', WPE_RI_TEXT_DOMAIN ), array( 'back_link' => true ) );
		}

		Capabilities::grant();
	}

	/**
	 * Deactivation intentionally preserves role capabilities and merchant data.
	 * No scheduled jobs or rewrite rules exist in this foundation package.
	 *
	 * @return void
	 */
	public static function deactivate(): void {
		// Preserve access configuration and merchant data on deactivation.
	}

	/**
	 * Start plugin services after dependencies have loaded.
	 *
	 * @return void
	 */
	public static function boot(): void {
		$errors = self::get_requirement_errors();
		if ( $errors ) {
			add_action( 'admin_notices', array( self::class, 'render_requirement_notice' ) );
			return;
		}

		Installer::maybe_upgrade();
		Admin::register();
		Rest_Controller::register();
		Refund_Observer::register();
		Alert_Service::register();
	}

	/**
	 * Display a dependency/runtime notice to administrators.
	 *
	 * @return void
	 */
	public static function render_requirement_notice(): void {
		if ( ! current_user_can( 'activate_plugins' ) ) {
			return;
		}

		$errors = self::get_requirement_errors();
		if ( ! $errors ) {
			return;
		}

		echo '<div class="notice notice-error"><p>';
		echo esc_html( implode( ' ', $errors ) );
		echo '</p></div>';
	}

	/**
	 * Collect requirement errors without accessing WooCommerce internals.
	 *
	 * @return string[]
	 */
	private static function get_requirement_errors(): array {
		$errors = array();

		if ( version_compare( PHP_VERSION, WPE_RI_MIN_PHP_VERSION, '<' ) ) {
			$errors[] = sprintf(
				/* translators: %s: required PHP version. */
				__( 'WPEssential Return Insights requires PHP %s or newer.', WPE_RI_TEXT_DOMAIN ),
				WPE_RI_MIN_PHP_VERSION
			);
		}

		$wp_version = get_bloginfo( 'version' );
		if ( version_compare( $wp_version, WPE_RI_MIN_WP_VERSION, '<' ) ) {
			$errors[] = sprintf(
				/* translators: %s: required WordPress version. */
				__( 'WPEssential Return Insights requires WordPress %s or newer.', WPE_RI_TEXT_DOMAIN ),
				WPE_RI_MIN_WP_VERSION
			);
		}

		if ( ! class_exists( 'WooCommerce' ) || ! defined( 'WC_VERSION' ) ) {
			$errors[] = __( 'WPEssential Return Insights requires WooCommerce to be active.', WPE_RI_TEXT_DOMAIN );
		} elseif ( version_compare( WC_VERSION, WPE_RI_MIN_WC_VERSION, '<' ) ) {
			$errors[] = sprintf(
				/* translators: %s: required WooCommerce version. */
				__( 'WPEssential Return Insights requires WooCommerce %s or newer.', WPE_RI_TEXT_DOMAIN ),
				WPE_RI_MIN_WC_VERSION
			);
		}

		return $errors;
	}
}
