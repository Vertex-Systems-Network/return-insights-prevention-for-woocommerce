<?php
/**
 * Stable, language-independent reason catalog.
 *
 * @package WPEssentialReturnInsights
 */
namespace WPEssential\ReturnInsights;
defined( 'ABSPATH' ) || exit;

/** Stores catalog labels in a non-autoloaded option. */
final class Reason_Catalog {
	private const OPTION = 'wpe_ri_reasons';
	private const DEFAULTS = array(
		'fit_size' => array( 'group' => 'product', 'label' => 'Fit or size mismatch' ),
		'quality_defect' => array( 'group' => 'product', 'label' => 'Quality or defect' ),
		'product_damage' => array( 'group' => 'product', 'label' => 'Product damaged' ),
		'appearance_color' => array( 'group' => 'product', 'label' => 'Appearance or colour differs' ),
		'not_as_described' => array( 'group' => 'product', 'label' => 'Not as described' ),
		'wrong_item_variation' => array( 'group' => 'fulfillment', 'label' => 'Wrong item or variation' ),
		'missing_part_accessory' => array( 'group' => 'fulfillment', 'label' => 'Missing part or accessory' ),
		'delivery_delay' => array( 'group' => 'fulfillment', 'label' => 'Delivery delay' ),
		'delivery_damage' => array( 'group' => 'fulfillment', 'label' => 'Delivery damage' ),
		'duplicate_order' => array( 'group' => 'other', 'label' => 'Duplicate order' ),
		'changed_mind' => array( 'group' => 'other', 'label' => 'Changed mind' ),
		'ordered_by_mistake' => array( 'group' => 'other', 'label' => 'Ordered by mistake' ),
		'other' => array( 'group' => 'other', 'label' => 'Other' ),
		'unknown' => array( 'group' => 'other', 'label' => 'Unknown' ),
	);

	public static function all( bool $include_inactive = false ): array {
		$stored = get_option( self::OPTION, array() );
		$items = array();
		foreach ( self::DEFAULTS as $id => $definition ) {
			$entry = isset( $stored[ $id ] ) ? wp_parse_args( $stored[ $id ], $definition ) : $definition;
			$entry = array_merge( array( 'id' => $id, 'active' => true, 'custom' => false, 'translations' => array() ), $entry );
			if ( $include_inactive || $entry['active'] ) {
				$items[] = $entry;
			}
		}
		foreach ( $stored as $id => $entry ) {
			if ( isset( self::DEFAULTS[ $id ] ) || ! is_array( $entry ) ) {
				continue;
			}
			$entry = array_merge( array( 'id' => $id, 'group' => 'other', 'label' => '', 'active' => true, 'custom' => true, 'translations' => array() ), $entry );
			if ( $include_inactive || $entry['active'] ) {
				$items[] = $entry;
			}
		}
		return $items;
	}

	public static function find( string $id, bool $include_inactive = false ) {
		foreach ( self::all( $include_inactive ) as $reason ) {
			if ( $reason['id'] === $id ) {
				return $reason;
			}
		}
		return null;
	}


	/**
	 * Match a WooCommerce refund phrase to a catalog label without retaining it.
	 *
	 * @param string $phrase Refund phrase, used only in memory.
	 * @return array|null
	 */
	public static function match_source_phrase( string $phrase ): ?array {
		$normalize = static function ( string $value ): string {
			$value = sanitize_text_field( $value );
			return function_exists( 'mb_strtolower' ) ? mb_strtolower( trim( $value ), 'UTF-8' ) : strtolower( trim( $value ) );
		};
		$needle = $normalize( $phrase );
		if ( '' === $needle ) {
			return null;
		}
		foreach ( self::all( true ) as $reason ) {
			$labels = array_merge( array( $reason['label'] ), array_values( $reason['translations'] ?? array() ) );
			foreach ( $labels as $label ) {
				if ( $needle === $normalize( (string) $label ) && $reason['active'] ) {
					return $reason;
				}
			}
		}
		return null;
	}

	public static function save( array $data, ?string $id = null ) {
		$items = get_option( self::OPTION, array() );
		$prior = null === $id ? null : self::find( $id, true );
		$label = isset( $data['label'] ) ? sanitize_text_field( $data['label'] ) : ( $prior['label'] ?? '' );
		$group = isset( $data['group'] ) ? sanitize_key( $data['group'] ) : ( $prior['group'] ?? 'other' );
		if ( '' === $label || strlen( $label ) > 100 || ! in_array( $group, array( 'product', 'fulfillment', 'other' ), true ) ) {
			return new \WP_Error( 'invalid_reason', __( 'Provide a label up to 100 characters and a valid group.', WPE_RI_TEXT_DOMAIN ), array( 'status' => 400 ) );
		}
		if ( null === $id ) {
			$id = 'custom_' . sanitize_title( $label );
			$id = substr( $id, 0, 64 );
			if ( self::find( $id, true ) || isset( $items[ $id ] ) ) {
				return new \WP_Error( 'duplicate_reason', __( 'A reason with this identifier already exists.', WPE_RI_TEXT_DOMAIN ), array( 'status' => 409 ) );
			}
		} elseif ( ! self::find( $id, true ) ) {
			return new \WP_Error( 'reason_not_found', __( 'Reason not found.', WPE_RI_TEXT_DOMAIN ), array( 'status' => 404 ) );
		}
		$existing = isset( $items[ $id ] ) ? $items[ $id ] : array();
		$translations = isset( $data['translations'] ) && is_array( $data['translations'] ) ? $data['translations'] : ( $existing['translations'] ?? array() );
		$clean_translations = array();
		foreach ( $translations as $locale => $translated_label ) {
			if ( preg_match( '/^[a-z]{2,3}(?:_[A-Z]{2})?$/', (string) $locale ) ) {
				$value = sanitize_text_field( $translated_label );
				if ( '' !== $value && strlen( $value ) <= 100 ) {
					$clean_translations[ $locale ] = $value;
				}
			}
		}
		$items[ $id ] = array(
			'group' => $group,
			'label' => $label,
			'active' => isset( $data['active'] ) ? rest_sanitize_boolean( $data['active'] ) : ( $existing['active'] ?? true ),
			'translations' => $clean_translations,
		);
		update_option( self::OPTION, $items, false );
		return self::find( $id, true );
	}
}
