<?php
/**
 * PSR-4 autoloader for the plugin namespace.
 *
 * @package WPEssentialReturnInsights
 */
defined( 'ABSPATH' ) || exit;

$composer_autoload = dirname( __DIR__ ) . '/vendor/autoload.php';
if ( is_readable( $composer_autoload ) ) {
	require_once $composer_autoload;
	return;
}

// Keep source checkouts and unbuilt WordPress installs usable before packaging.
spl_autoload_register(
	static function ( string $class ): void {
		$prefix = 'WPEssential\\ReturnInsights\\';
		$prefix_length = strlen( $prefix );
		if ( 0 !== strncmp( $prefix, $class, $prefix_length ) ) {
			return;
		}

		$relative_class = substr( $class, $prefix_length );
		if ( '' === $relative_class || preg_match( '/[^A-Za-z0-9_\\\\]/', $relative_class ) ) {
			return;
		}

		$file = dirname( __DIR__ ) . '/src/' . str_replace( '\\', '/', $relative_class ) . '.php';
		if ( is_file( $file ) ) {
			require_once $file;
		}
	}
);
