<?php
/**
 * Deterministic, explainable reason clusters for product patterns.
 *
 * @package WPEssentialReturnInsights
 */
namespace WPEssential\ReturnInsights;
defined( 'ABSPATH' ) || exit;

/**
 * Groups reason patterns without rewriting source events.
 *
 * Merchant-approved overrides are stored separately in a non-autoloaded option.
 * The original reason IDs always remain available as cluster membership evidence.
 */
final class ClusterService {
	private const OPTION = 'wpe_ri_cluster_overrides';

	/**
	 * Return current clusters from pattern rows.
	 *
	 * @param array $patterns Pattern rows from PatternService.
	 * @return array
	 */
	public static function build( array $patterns, array $coverage = array() ): array {
		$clusters = array();
		$overrides = get_option( self::OPTION, array() );
		if ( ! is_array( $overrides ) ) {
			$overrides = array();
		}

		foreach ( $patterns as $pattern ) {
			$reason_id = sanitize_key( (string) ( $pattern['reason_id'] ?? '' ) );
			$product_id = absint( $pattern['product_id'] ?? 0 );
			$variation_id = absint( $pattern['variation_id'] ?? 0 );
			if ( '' === $reason_id || ! $product_id ) {
				continue;
			}

			$default = self::default_cluster( $reason_id );
			$cluster_id = isset( $overrides[ $reason_id ] ) ? sanitize_key( (string) $overrides[ $reason_id ] ) : $default['id'];
			$key = $product_id . ':' . $variation_id . ':' . $cluster_id;
			if ( ! isset( $clusters[ $key ] ) ) {
				$clusters[ $key ] = array(
					'product_id' => $product_id,
					'variation_id' => $variation_id,
					'cluster_id' => $cluster_id,
					'cluster_label' => self::label_for( $cluster_id, $default['label'] ),
					'current' => array( 'sold_quantity' => (float) ( $pattern['current']['sold_quantity'] ?? 0 ), 'returned_quantity' => 0.0, 'rate_percent' => null, 'rate_status' => 'unavailable' ),
					'comparison' => array( 'sold_quantity' => (float) ( $pattern['comparison']['sold_quantity'] ?? 0 ), 'returned_quantity' => 0.0, 'rate_percent' => null, 'rate_status' => 'unavailable' ),
					'reason_ids' => array(),
					'reason_labels' => array(),
					'uses_merchant_override' => isset( $overrides[ $reason_id ] ),
				);
			}

			$clusters[ $key ]['current']['returned_quantity'] += (float) ( $pattern['current']['returned_quantity'] ?? 0 );
			$clusters[ $key ]['comparison']['returned_quantity'] += (float) ( $pattern['comparison']['returned_quantity'] ?? 0 );
			$clusters[ $key ]['current']['sold_quantity'] = max( $clusters[ $key ]['current']['sold_quantity'], (float) ( $pattern['current']['sold_quantity'] ?? 0 ) );
			$clusters[ $key ]['comparison']['sold_quantity'] = max( $clusters[ $key ]['comparison']['sold_quantity'], (float) ( $pattern['comparison']['sold_quantity'] ?? 0 ) );
			$clusters[ $key ]['reason_ids'][ $reason_id ] = $reason_id;
			$clusters[ $key ]['reason_labels'][ $reason_id ] = sanitize_text_field( (string) ( $pattern['reason_label'] ?? $reason_id ) );
		}

		foreach ( $clusters as &$cluster ) {
			foreach ( array( 'current', 'comparison' ) as $period ) {
				$sold = $cluster[ $period ]['sold_quantity'];
				$returned = $cluster[ $period ]['returned_quantity'];
				$complete = ! isset( $coverage[ $period ]['complete'] ) || (bool) $coverage[ $period ]['complete'];
				if ( $complete && $sold >= 20 && $returned <= $sold ) {
					$cluster[ $period ]['rate_percent'] = round( 100 * $returned / $sold, 2 );
					$cluster[ $period ]['rate_status'] = 'available';
				} elseif ( ! $complete ) {
					$cluster[ $period ]['rate_status'] = 'incomplete_coverage';
				} elseif ( $sold < 20 ) {
					$cluster[ $period ]['rate_status'] = 'low_sample';
				} else {
					$cluster[ $period ]['rate_status'] = 'return_quantity_exceeds_sold';
				}
			}
			$cluster['reason_ids'] = array_values( $cluster['reason_ids'] );
			$cluster['reason_labels'] = array_values( $cluster['reason_labels'] );
		}
		unset( $cluster );

		return array_values( $clusters );
	}

	/**
	 * Save or reset one reason's cluster assignment.
	 *
	 * @param string      $reason_id Stable catalog reason ID.
	 * @param string|null $cluster_id Null restores deterministic default.
	 * @return true|\WP_Error
	 */
	public static function set_override( string $reason_id, ?string $cluster_id ) {
		$reason = ReasonCatalog::find( $reason_id, true );
		if ( ! $reason ) {
			return new \WP_Error( 'reason_not_found', __( 'Reason not found.', WPE_RI_TEXT_DOMAIN ), array( 'status' => 404 ) );
		}

		$overrides = get_option( self::OPTION, array() );
		if ( ! is_array( $overrides ) ) {
			$overrides = array();
		}

		if ( null === $cluster_id || '' === trim( $cluster_id ) ) {
			unset( $overrides[ $reason_id ] );
		} else {
			$cluster_id = sanitize_key( $cluster_id );
			if ( '' === $cluster_id || strlen( $cluster_id ) > 48 ) {
				return new \WP_Error( 'invalid_cluster', __( 'Provide a valid cluster key.', WPE_RI_TEXT_DOMAIN ), array( 'status' => 400 ) );
			}
			$overrides[ $reason_id ] = $cluster_id;
		}

		update_option( self::OPTION, $overrides, false );
		return true;
	}

	/**
	 * Map known stable reason IDs to transparent deterministic groups.
	 *
	 * @param string $reason_id Stable reason ID.
	 * @return array{id:string,label:string}
	 */
	private static function default_cluster( string $reason_id ): array {
		$groups = array(
			'fit_size' => array( 'fit_size', 'Fit and sizing' ),
			'product_condition' => array( 'product_condition', 'Product quality and condition' ),
			'product_description' => array( 'product_description', 'Product appearance and description' ),
			'fulfillment' => array( 'fulfillment', 'Fulfillment and delivery' ),
			'order_intent' => array( 'order_intent', 'Order intent' ),
		);
		$map = array(
			'fit_size' => 'fit_size',
			'quality_defect' => 'product_condition',
			'product_damage' => 'product_condition',
			'appearance_color' => 'product_description',
			'not_as_described' => 'product_description',
			'wrong_item_variation' => 'fulfillment',
			'missing_part_accessory' => 'fulfillment',
			'delivery_delay' => 'fulfillment',
			'delivery_damage' => 'fulfillment',
			'duplicate_order' => 'order_intent',
			'changed_mind' => 'order_intent',
			'ordered_by_mistake' => 'order_intent',
		);
		$id = $map[ $reason_id ] ?? 'reason_' . substr( sanitize_key( $reason_id ), 0, 40 );
		return isset( $groups[ $id ] ) ? array( 'id' => $groups[ $id ][0], 'label' => $groups[ $id ][1] ) : array( 'id' => $id, 'label' => sprintf( __( 'Reason: %s', WPE_RI_TEXT_DOMAIN ), $reason_id ) );
	}

	private static function label_for( string $cluster_id, string $fallback ): string {
		$labels = array(
			'fit_size' => __( 'Fit and sizing', WPE_RI_TEXT_DOMAIN ),
			'product_condition' => __( 'Product quality and condition', WPE_RI_TEXT_DOMAIN ),
			'product_description' => __( 'Product appearance and description', WPE_RI_TEXT_DOMAIN ),
			'fulfillment' => __( 'Fulfillment and delivery', WPE_RI_TEXT_DOMAIN ),
			'order_intent' => __( 'Order intent', WPE_RI_TEXT_DOMAIN ),
		);
		return $labels[ $cluster_id ] ?? $fallback;
	}
}
