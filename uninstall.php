<?php
/**
 * Uninstall handler.
 *
 * Merchant event data is retained by default. This file removes only the
 * plugin-owned role capabilities. A future explicit data deletion setting
 * will be implemented with the data lifecycle service.
 *
 * @package WPEssentialReturnInsights
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

require_once __DIR__ . '/includes/class-capabilities.php';

\WPEssential\ReturnInsights\Capabilities::revoke();
