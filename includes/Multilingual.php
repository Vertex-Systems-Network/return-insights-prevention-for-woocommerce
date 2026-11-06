<?php
/**
 * Optional WPML and Polylang integration for merchant-defined reason labels.
 *
 * @package WPEssentialReturnInsights
 */
namespace WPEssential\ReturnInsights;

defined( 'ABSPATH' ) || exit;

/** Keeps reason IDs canonical while exposing optional translated labels. */
final class Multilingual {
	private const CONTEXT = 'WPEssential Return Insights';
	private const GROUP = 'WPEssential Return Insights';

	public static function register(): void {
		add_action( 'admin_init', array( self::class, 'register_reason_strings' ) );
	}

	/** Register current reason labels each admin load so late plugin activation works. */
	public static function register_reason_strings(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		foreach ( ReasonCatalog::all( true ) as $reason ) {
			$name = 'reason_' . sanitize_key( $reason['id'] );
			$label = (string) $reason['label'];
			if ( '' === $label ) {
				continue;
			}

			if ( function_exists( 'pll_register_string' ) ) {
				pll_register_string( $name, $label, self::GROUP, false );
			}
			if ( false !== has_action( 'wpml_register_single_string' ) ) {
				do_action( 'wpml_register_single_string', self::CONTEXT, $name, $label );
			}
		}
	}

	/** Resolve an integration translation, then prefer an explicit locale override. */
	public static function reason_label( array $reason, ?string $locale = null ): string {
		$source_label = (string) ( $reason['label'] ?? '' );
		$label = $source_label;
		$locale = $locale ?: get_user_locale();
		$name = 'reason_' . sanitize_key( (string) ( $reason['id'] ?? '' ) );
		if ( false !== has_filter( 'wpml_translate_single_string' ) ) {
			$translated = apply_filters( 'wpml_translate_single_string', $source_label, self::CONTEXT, $name );
			if ( is_string( $translated ) && '' !== $translated ) {
				$label = $translated;
			}
		} elseif ( function_exists( 'pll__' ) ) {
			$translated = pll__( $source_label );
			if ( is_string( $translated ) && '' !== $translated ) {
				$label = $translated;
			}
		}

		// Merchant-defined locale labels are the most specific source of truth.
		$translations = isset( $reason['translations'] ) && is_array( $reason['translations'] ) ? $reason['translations'] : array();
		if ( isset( $translations[ $locale ] ) && is_scalar( $translations[ $locale ] ) && '' !== (string) $translations[ $locale ] ) {
			$label = (string) $translations[ $locale ];
		}

		return $label;
	}
}
