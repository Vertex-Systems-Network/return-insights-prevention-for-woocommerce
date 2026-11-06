<?php
/**
 * Capability registration for the plugin.
 *
 * @package WPEssentialReturnInsights
 */

namespace WPEssential\ReturnInsights;

defined( 'ABSPATH' ) || exit;

/**
 * Adds the plugin's least-privilege capabilities to supported WooCommerce roles.
 */
final class Capabilities {
	public const VIEW   = 'view_return_insights';
	public const MANAGE = 'manage_return_insights';

	/**
	 * Grant capabilities to administrators and WooCommerce managers.
	 *
	 * Read-only reporting access is granted to roles that can view Woo reports.
	 * The existing role definitions remain authoritative; this plugin does not
	 * create or replace roles.
	 *
	 * @return void
	 */
	public static function grant(): void {
		if ( ! function_exists( 'wp_roles' ) ) {
			return;
		}

		$roles = wp_roles();
		if ( ! $roles ) {
			return;
		}

		foreach ( array( 'administrator', 'shop_manager' ) as $role_name ) {
			$role = $roles->get_role( $role_name );
			if ( ! $role ) {
				continue;
			}

			if ( $role->has_cap( 'manage_options' ) || $role->has_cap( 'manage_woocommerce' ) ) {
				$role->add_cap( self::VIEW );
				$role->add_cap( self::MANAGE );
				continue;
			}

			if ( $role->has_cap( 'view_woocommerce_reports' ) ) {
				$role->add_cap( self::VIEW );
			}
		}
	}

	/**
	 * Remove plugin-specific capabilities during uninstall only.
	 *
	 * @return void
	 */
	public static function revoke(): void {
		if ( ! function_exists( 'wp_roles' ) ) {
			return;
		}

		$roles = wp_roles();
		if ( ! $roles ) {
			return;
		}

		foreach ( $roles->roles as $role_name => $role_data ) {
			$role = $roles->get_role( $role_name );
			if ( ! $role ) {
				continue;
			}

			$role->remove_cap( self::VIEW );
			$role->remove_cap( self::MANAGE );
		}
	}
}
