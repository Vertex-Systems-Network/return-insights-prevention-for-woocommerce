<?php
/**
 * Bounded product/variation return-rate aggregation for the Pro add-on.
 *
 * @package WPEssentialReturnInsights
 */
namespace WPEssential\ReturnInsights;
defined( 'ABSPATH' ) || exit;

/**
 * Builds mature, comparable cohorts using WooCommerce CRUD order data.
 *
 * The service fails closed: incomplete order scans or item mapping never emit a
 * partial return rate. The Free core grants no Pro entitlement by default.
 */
final class PatternService {
	private const PAGE_SIZE = 50;
	private const MAX_ORDERS_PER_COHORT = 500;
	private const MAX_FULL_PATTERN_ROWS = 2000;
	private const ALLOWED_DAYS = array( 30, 60, 90, 120, 180 );

	public static function is_entitled(): bool {
		return (bool) apply_filters( 'wpessential_return_insights_pro_entitled', false );
	}

	/**
	 * Build reason-level patterns for two equally sized, mature purchase cohorts.
	 *
	 * @param int $maturity_days Days after purchase required before inclusion.
	 * @param int $cohort_days   Number of purchase days in each comparison cohort.
	 * @return array|\WP_Error
	 */
	public static function report( int $maturity_days = 90, int $cohort_days = 90, bool $include_all_patterns = false ) {
		if ( ! in_array( $maturity_days, self::ALLOWED_DAYS, true ) || ! in_array( $cohort_days, self::ALLOWED_DAYS, true ) ) {
			return new \WP_Error( 'invalid_pattern_window', __( 'Choose a supported maturity and comparison window.', WPE_RI_TEXT_DOMAIN ), array( 'status' => 400 ) );
		}

		$today = current_datetime()->setTime( 0, 0, 0 );
		$cutoff = $today->modify( '-' . $maturity_days . ' days' );
		$current_to = $cutoff->modify( '-1 day' );
		$current_from = $current_to->modify( '-' . ( $cohort_days - 1 ) . ' days' );
		$comparison_to = $current_from->modify( '-1 day' );
		$comparison_from = $comparison_to->modify( '-' . ( $cohort_days - 1 ) . ' days' );

		$current = self::aggregate_cohort( $current_from->format( 'Y-m-d' ), $current_to->format( 'Y-m-d' ) );
		if ( is_wp_error( $current ) ) {
			return $current;
		}
		$comparison = self::aggregate_cohort( $comparison_from->format( 'Y-m-d' ), $comparison_to->format( 'Y-m-d' ) );
		if ( is_wp_error( $comparison ) ) {
			return $comparison;
		}

		$patterns = self::build_patterns( $current, $comparison, $include_all_patterns ? self::MAX_FULL_PATTERN_ROWS : 20 );
		if ( is_wp_error( $patterns ) ) { return $patterns; }
		return array(
			'metric_definition_version' => 'confirmed-return-cohort-v1',
			'metric_definition' => __( 'Confirmed-return quantity divided by eligible sold quantity for the same product/variation and reason in a mature purchase cohort.', WPE_RI_TEXT_DOMAIN ),
			'maturity_days' => $maturity_days,
			'cohort_days' => $cohort_days,
			'periods' => array(
				'current' => array( 'date_from' => $current_from->format( 'Y-m-d' ), 'date_to' => $current_to->format( 'Y-m-d' ) ),
				'comparison' => array( 'date_from' => $comparison_from->format( 'Y-m-d' ), 'date_to' => $comparison_to->format( 'Y-m-d' ) ),
				'timezone' => wp_timezone_string(),
			),
			'coverage' => array(
				'current' => $current['coverage'],
				'comparison' => $comparison['coverage'],
			),
			'refund_observations_are_excluded' => true,
			'patterns' => $patterns,
		);
	}

	/**
	 * Aggregate one bounded purchase cohort through WooCommerce CRUD APIs.
	 *
	 * @param string $date_from Inclusive site-local purchase date.
	 * @param string $date_to   Inclusive site-local purchase date.
	 * @return array|\WP_Error
	 */
	private static function aggregate_cohort( string $date_from, string $date_to ) {
		$args = array(
			'type' => 'shop_order',
			'status' => 'any',
			'date_created' => $date_from . '...' . $date_to,
			'limit' => self::PAGE_SIZE,
			'paged' => 1,
			'paginate' => true,
			'return' => 'objects',
			'orderby' => 'date',
			'order' => 'ASC',
		);
		$page = wc_get_orders( $args );
		if ( ! is_object( $page ) || ! isset( $page->orders, $page->total, $page->max_num_pages ) ) {
			return new \WP_Error( 'pattern_order_query_failed', __( 'Product insights could not read the order cohort.', WPE_RI_TEXT_DOMAIN ), array( 'status' => 500 ) );
		}

		$total_orders = (int) $page->total;
		$pages = max( 1, (int) $page->max_num_pages );
		if ( $total_orders > self::MAX_ORDERS_PER_COHORT || $pages > (int) ceil( self::MAX_ORDERS_PER_COHORT / self::PAGE_SIZE ) ) {
			return self::incomplete_cohort( $date_from, $date_to, $total_orders, 'order_scan_limit' );
		}

		$order_ids = array();
		$eligible_items = array();
		$products = array();
		$eligible_orders = 0;
		$excluded_orders = 0;
		$total_line_items = 0;
		$mapped_line_items = 0;
		$unmapped_sales_quantity = 0.0;
		$unmapped_sales_items = 0;

		for ( $current_page = 1; $current_page <= $pages; $current_page++ ) {
			if ( 1 === $current_page ) {
				$orders = $page->orders;
			} else {
				$args['paged'] = $current_page;
				$next_page = wc_get_orders( $args );
				if ( ! is_object( $next_page ) || ! isset( $next_page->orders ) || ! is_array( $next_page->orders ) ) {
					return new \WP_Error( 'pattern_order_query_failed', __( 'Product insights could not complete the order scan.', WPE_RI_TEXT_DOMAIN ), array( 'status' => 500 ) );
				}
				$orders = $next_page->orders;
			}

			foreach ( $orders as $order ) {
				if ( ! is_a( $order, 'WC_Order' ) ) {
					continue;
				}
				$order_id = absint( $order->get_id() );
				if ( ! $order_id ) {
					continue;
				}
				$order_ids[ $order_id ] = $order_id;
				$status = (string) $order->get_status();
				$date_completed = $order->get_date_completed();
				$is_eligible = in_array( $status, array( 'completed', 'refunded' ), true ) && $date_completed;
				if ( ! $is_eligible ) {
					++$excluded_orders;
					continue;
				}

				++$eligible_orders;
				foreach ( $order->get_items( 'line_item' ) as $item ) {
					if ( ! $item instanceof \WC_Order_Item_Product ) {
						continue;
					}
					++$total_line_items;
					$item_id = absint( $item->get_id() );
					$product_id = absint( $item->get_product_id() );
					$variation_id = absint( $item->get_variation_id() );
					$quantity = (float) $item->get_quantity();
					if ( ! $item_id || ! $product_id || $quantity <= 0 ) {
						$unmapped_sales_quantity += max( 0, $quantity );
						++$unmapped_sales_items;
						continue;
					}
					++$mapped_line_items;
					$key = self::product_key( $product_id, $variation_id );
					if ( ! isset( $products[ $key ] ) ) {
						$products[ $key ] = self::empty_product( $product_id, $variation_id );
					}
					$products[ $key ]['sold_quantity'] += $quantity;
					$eligible_items[ $item_id ] = array(
						'order_id' => $order_id,
						'product_id' => $product_id,
						'variation_id' => $variation_id,
					);
				}
			}
		}

		$return_rows = self::return_rows_for_orders( array_values( $order_ids ) );
		if ( is_wp_error( $return_rows ) ) {
			return $return_rows;
		}
		$total_return_quantity = 0.0;
		$mapped_return_quantity = 0.0;
		$unmapped_return_quantity = 0.0;
		foreach ( $return_rows as $row ) {
			$quantity = (float) $row['quantity_total'];
			$total_return_quantity += $quantity;
			$item_id = absint( $row['order_item_id'] );
			$item = $eligible_items[ $item_id ] ?? null;
			if (
				! $item ||
				(int) $item['order_id'] !== (int) $row['order_id'] ||
				(int) $item['product_id'] !== (int) $row['product_id'] ||
				(int) $item['variation_id'] !== (int) $row['variation_id']
			) {
				$unmapped_return_quantity += $quantity;
				continue;
			}

			$mapped_return_quantity += $quantity;
			$key = self::product_key( (int) $item['product_id'], (int) $item['variation_id'] );
			if ( ! isset( $products[ $key ] ) ) {
				$products[ $key ] = self::empty_product( (int) $item['product_id'], (int) $item['variation_id'] );
			}
			$products[ $key ]['return_quantity'] += $quantity;
			$products[ $key ]['return_events'] += (int) $row['event_count'];
			$reason_id = (string) $row['reason_id'];
			if ( ! isset( $products[ $key ]['reasons'][ $reason_id ] ) ) {
				$products[ $key ]['reasons'][ $reason_id ] = array( 'quantity' => 0.0, 'events' => 0 );
			}
			$products[ $key ]['reasons'][ $reason_id ]['quantity'] += $quantity;
			$products[ $key ]['reasons'][ $reason_id ]['events'] += (int) $row['event_count'];
		}

		foreach ( $products as &$product ) {
			$product['sold_quantity'] = round( (float) $product['sold_quantity'], 4 );
			$product['return_quantity'] = round( (float) $product['return_quantity'], 4 );
		}
		unset( $product );

		$sales_complete = 0 === $unmapped_sales_items;
		$returns_complete = 0.0 === $unmapped_return_quantity;
		return array(
			'products' => $products,
			'coverage' => array(
				'date_from' => $date_from,
				'date_to' => $date_to,
				'complete' => $sales_complete && $returns_complete,
				'unavailable_reason' => $sales_complete && $returns_complete ? '' : 'item_mapping_incomplete',
				'order_count' => $total_orders,
				'orders_scanned' => $total_orders,
				'eligible_order_count' => $eligible_orders,
				'excluded_order_count' => $excluded_orders,
				'eligible_line_item_count' => $total_line_items,
				'mapped_line_item_count' => $mapped_line_items,
				'unmapped_sales_quantity' => round( $unmapped_sales_quantity, 4 ),
				'unmapped_sales_item_count' => $unmapped_sales_items,
				'sales_item_mapping_percent' => $total_line_items ? round( 100 * $mapped_line_items / $total_line_items, 1 ) : null,
				'confirmed_return_quantity' => round( $total_return_quantity, 4 ),
				'mapped_return_quantity' => round( $mapped_return_quantity, 4 ),
				'unmapped_return_quantity' => round( $unmapped_return_quantity, 4 ),
				'return_mapping_percent' => $total_return_quantity > 0 ? round( 100 * $mapped_return_quantity / $total_return_quantity, 1 ) : null,
			),
		);
	}

	private static function return_rows_for_orders( array $order_ids ) {
		if ( ! $order_ids ) {
			return array();
		}
		global $wpdb;
		$table = Installer::events_table();
		$in = implode( ',', array_fill( 0, count( $order_ids ), '%d' ) );
		$sql = "SELECT order_id, order_item_id, product_id, variation_id, reason_id, COUNT(*) AS event_count, COALESCE(SUM(quantity), 0) AS quantity_total FROM {$table} WHERE deleted_at_gmt IS NULL AND event_kind = %s AND attribution_scope = %s AND order_id IN ({$in}) GROUP BY order_id, order_item_id, product_id, variation_id, reason_id";
		$params = array_merge( array( 'confirmed_return', 'item' ), array_map( 'absint', $order_ids ) );
		$rows = $wpdb->get_results( $wpdb->prepare( $sql, $params ), ARRAY_A );
		if ( ! empty( $wpdb->last_error ) ) {
			return new \WP_Error( 'pattern_event_query_failed', __( 'Product insights could not read confirmed-return records.', WPE_RI_TEXT_DOMAIN ), array( 'status' => 500 ) );
		}
		return $rows ?: array();
	}

	private static function build_patterns( array $current, array $comparison, int $limit = 20 ) {
		$keys = array_unique( array_merge( array_keys( $current['products'] ), array_keys( $comparison['products'] ) ) );
		$rows = array();
		foreach ( $keys as $key ) {
			$now = $current['products'][ $key ] ?? self::empty_product_from_key( $key );
			$before = $comparison['products'][ $key ] ?? self::empty_product_from_key( $key );
			$reason_ids = array_unique( array_merge( array_keys( $now['reasons'] ), array_keys( $before['reasons'] ) ) );
			if ( ! $reason_ids ) {
				continue;
			}
			$product_id = (int) $now['product_id'];
			$variation_id = (int) $now['variation_id'];
			foreach ( $reason_ids as $reason_id ) {
				$current_reason_quantity = (float) ( $now['reasons'][ $reason_id ]['quantity'] ?? 0 );
				$comparison_reason_quantity = (float) ( $before['reasons'][ $reason_id ]['quantity'] ?? 0 );
				$current_rate = self::rate( $current_reason_quantity, (float) $now['sold_quantity'], (bool) $current['coverage']['complete'] );
				$comparison_rate = self::rate( $comparison_reason_quantity, (float) $before['sold_quantity'], (bool) $comparison['coverage']['complete'] );
				$reason = ReasonCatalog::find( (string) $reason_id, true );
				$rows[] = array(
					'product_id' => $product_id,
					'variation_id' => $variation_id,
					'product_label' => '',
					'variation_label' => '',
					'reason_id' => (string) $reason_id,
					'reason_label' => $reason ? ( $reason['translations'][ get_user_locale() ] ?? $reason['label'] ) : (string) $reason_id,
					'current' => array(
						'sold_quantity' => (float) $now['sold_quantity'],
						'returned_quantity' => (float) ( $now['reasons'][ $reason_id ]['quantity'] ?? 0 ),
						'event_count' => (int) ( $now['reasons'][ $reason_id ]['events'] ?? 0 ),
						'rate_percent' => $current_rate['value'],
						'rate_status' => $current_rate['status'],
						'quality_flags' => self::quality_flags( (float) $now['sold_quantity'], $current['coverage'] ),
					),
					'comparison' => array(
						'sold_quantity' => (float) $before['sold_quantity'],
						'returned_quantity' => (float) ( $before['reasons'][ $reason_id ]['quantity'] ?? 0 ),
						'event_count' => (int) ( $before['reasons'][ $reason_id ]['events'] ?? 0 ),
						'rate_percent' => $comparison_rate['value'],
						'rate_status' => $comparison_rate['status'],
						'quality_flags' => self::quality_flags( (float) $before['sold_quantity'], $comparison['coverage'] ),
					),
					'change_percentage_points' => null !== $current_rate['value'] && null !== $comparison_rate['value'] ? round( $current_rate['value'] - $comparison_rate['value'], 2 ) : null,
				);
			}
		}
		usort( $rows, static function ( array $a, array $b ): int {
			$by_qty = (float) $b['current']['returned_quantity'] <=> (float) $a['current']['returned_quantity'];
			if ( 0 !== $by_qty ) { return $by_qty; }
			return strcmp( $a['reason_id'], $b['reason_id'] );
		} );
		if ( count( $rows ) > $limit ) {
			if ( $limit > 20 ) {
				return new \WP_Error( 'pattern_candidate_limit', __( 'The cohort contains too many product/reason candidates for a complete alert scan.', WPE_RI_TEXT_DOMAIN ), array( 'status' => 503 ) );
			}
			$rows = array_slice( $rows, 0, $limit );
		}
		$product_cache = array();
		$variation_cache = array();
		foreach ( $rows as &$row ) {
			$product_id = (int) $row['product_id'];
			$variation_id = (int) $row['variation_id'];
			if ( ! array_key_exists( $product_id, $product_cache ) ) {
				$product_cache[ $product_id ] = wc_get_product( $product_id );
			}
			if ( $variation_id && ! array_key_exists( $variation_id, $variation_cache ) ) {
				$variation_cache[ $variation_id ] = wc_get_product( $variation_id );
			}
			$product = $product_cache[ $product_id ];
			$variation = $variation_id ? $variation_cache[ $variation_id ] : false;
			$row['product_label'] = $product ? $product->get_name() : sprintf( __( 'Product #%d', WPE_RI_TEXT_DOMAIN ), $product_id );
			$row['variation_label'] = $variation ? $variation->get_name() : '';
		}
		unset( $row );
		return $rows;
	}

	private static function rate( float $returned, float $sold, bool $complete ): array {
		if ( ! $complete ) {
			return array( 'value' => null, 'status' => 'incomplete_coverage' );
		}
		if ( $sold <= 0 ) {
			return array( 'value' => null, 'status' => 'insufficient_denominator' );
		}
		if ( $sold < 20 ) {
			return array( 'value' => null, 'status' => 'low_sample' );
		}
		if ( $returned > $sold ) {
			return array( 'value' => null, 'status' => 'return_quantity_exceeds_sold' );
		}
		return array( 'value' => round( 100 * $returned / $sold, 2 ), 'status' => 'available' );
	}

	private static function quality_flags( float $sold, array $coverage ): array {
		$flags = array();
		if ( ! $coverage['complete'] ) {
			$flags[] = 'incomplete_coverage';
		}
		if ( $sold < 20 ) {
			$flags[] = 'low_sample';
		}
		return $flags;
	}

	private static function empty_product( int $product_id, int $variation_id ): array {
		return array( 'product_id' => $product_id, 'variation_id' => $variation_id, 'sold_quantity' => 0.0, 'return_quantity' => 0.0, 'return_events' => 0, 'reasons' => array() );
	}

	private static function empty_product_from_key( string $key ): array {
		$ids = array_map( 'absint', explode( ':', $key ) );
		return self::empty_product( (int) ( $ids[0] ?? 0 ), (int) ( $ids[1] ?? 0 ) );
	}

	private static function product_key( int $product_id, int $variation_id ): string {
		return $product_id . ':' . $variation_id;
	}

	private static function incomplete_cohort( string $date_from, string $date_to, int $total, string $reason ): array {
		return array(
			'products' => array(),
			'coverage' => array(
				'date_from' => $date_from,
				'date_to' => $date_to,
				'complete' => false,
				'unavailable_reason' => $reason,
				'order_count' => $total,
				'orders_scanned' => 0,
				'eligible_order_count' => 0,
				'excluded_order_count' => 0,
				'eligible_line_item_count' => 0,
				'mapped_line_item_count' => 0,
				'unmapped_sales_quantity' => null,
				'unmapped_sales_item_count' => null,
				'sales_item_mapping_percent' => null,
				'confirmed_return_quantity' => null,
				'mapped_return_quantity' => null,
				'unmapped_return_quantity' => null,
				'return_mapping_percent' => null,
			),
		);
	}
}
