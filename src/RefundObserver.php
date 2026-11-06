<?php
/**
 * Observes native WooCommerce refund create/delete lifecycle events.
 *
 * @package WPEssentialReturnInsights
 */
namespace WPEssential\ReturnInsights;
defined( 'ABSPATH' ) || exit;

/**
 * Stores refund-scope evidence without interpreting it as a physical return.
 */
final class RefundObserver {
	/** Register supported WooCommerce lifecycle hooks. */
	public static function register(): void {
		add_action( 'woocommerce_refund_created', array( self::class, 'created' ), 20, 2 );
		add_action( 'woocommerce_refund_deleted', array( self::class, 'deleted' ), 20, 2 );
	}

	/**
	 * Capture a refund after WooCommerce has saved it.
	 *
	 * @param int   $refund_id Refund post/object ID.
	 * @param array $args      Refund creation arguments.
	 * @return void
	 */
	public static function created( int $refund_id, array $args = array() ): void {
		global $wpdb;

		$refund = wc_get_order( $refund_id );
		if ( ! $refund || ! is_a( $refund, 'WC_Order_Refund' ) ) {
			return;
		}
		$order_id = absint( $refund->get_parent_id() );
		if ( ! $order_id || ! wc_get_order( $order_id ) ) {
			return;
		}

		$quantity = 0.0;
		foreach ( $refund->get_items( 'line_item' ) as $refund_item ) {
			if ( is_callable( array( $refund_item, 'get_quantity' ) ) ) {
				$quantity += abs( (float) $refund_item->get_quantity() );
			}
		}
		$matched_reason = ReasonCatalog::match_source_phrase( (string) $refund->get_reason() );
		$date = $refund->get_date_created();
		$occurred = $date ? gmdate( 'Y-m-d H:i:s', $date->getTimestamp() ) : gmdate( 'Y-m-d H:i:s' );
		$now = gmdate( 'Y-m-d H:i:s' );
		$table = Installer::events_table();
		$source_type = 'woocommerce_refund';
		$source_key = hash( 'sha256', $source_type . '|' . get_current_blog_id() . '|' . $refund_id );
		$existing = $wpdb->get_row( $wpdb->prepare( "SELECT event_id FROM {$table} WHERE source_key = %s", $source_key ), ARRAY_A );

		$record = array(
			'refund_id' => $refund_id,
			'source_type' => $source_type,
			'source_record_id' => (string) $refund_id,
			'event_kind' => 'refund_only',
			'order_id' => $order_id,
			'order_item_id' => 0,
			'product_id' => 0,
			'variation_id' => 0,
			'reason_id' => $matched_reason ? $matched_reason['id'] : 'unknown',
			'quantity' => wc_format_decimal( $quantity, 4 ),
			'attribution_scope' => 'refund',
			'mapping_state' => $matched_reason ? 'mapped' : 'unmapped',
			'occurred_at_gmt' => $occurred,
			'updated_at_gmt' => $now,
			'updated_by' => 0,
			'deleted_at_gmt' => null,
			'deletion_note' => '',
		);

		if ( $existing ) {
			$wpdb->update( $table, $record, array( 'event_id' => absint( $existing['event_id'] ) ) );
			return;
		}

		$record['event_uuid'] = wp_generate_uuid4();
		$record['source_key'] = $source_key;
		$record['created_at_gmt'] = $now;
		$record['created_by'] = 0;
		$wpdb->insert( $table, $record );
	}

	/**
	 * Soft-delete events tied to a removed WooCommerce refund.
	 *
	 * @param int $refund_id Refund ID.
	 * @param int $order_id  Parent order ID.
	 * @return void
	 */
	public static function deleted( int $refund_id, int $order_id = 0 ): void {
		global $wpdb;

		if ( ! $refund_id ) {
			return;
		}
		$now = gmdate( 'Y-m-d H:i:s' );
		$wpdb->update(
			Installer::events_table(),
			array(
				'deleted_at_gmt' => $now,
				'updated_at_gmt' => $now,
				'updated_by' => 0,
				'deletion_note' => __( 'Source WooCommerce refund was deleted.', WPE_RI_TEXT_DOMAIN ),
			),
			array(
				'source_type' => 'woocommerce_refund',
				'source_record_id' => (string) absint( $refund_id ),
			)
		);
	}
}
