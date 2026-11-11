<?php
/**
 * WordPress privacy exporter and eraser for plugin actor references.
 *
 * @package WPEssentialReturnInsights
 */
namespace WPEssential\ReturnInsights;

defined( 'ABSPATH' ) || exit;

/** Exports and anonymizes WordPress user references while retaining store metrics. */
final class Privacy {
	private const PAGE_SIZE = 100;
	private const ORDER_PAGE_SIZE = 10;
	private const ORDER_EVENT_LIMIT = 500;

	public static function register(): void {
		add_filter( 'wp_privacy_personal_data_exporters', array( self::class, 'exporter' ) );
		add_filter( 'wp_privacy_personal_data_erasers', array( self::class, 'eraser' ) );
		add_filter( 'woocommerce_privacy_erase_order_personal_data', array( self::class, 'erase_order_data' ), 10, 2 );
	}


	/**
	 * Erase plugin order linkage before WooCommerce anonymizes the order.
	 *
	 * Returning false makes WooCommerce retain the order data and report that
	 * erasure was not completed when this plugin cannot safely erase its rows.
	 */
	public static function erase_order_data( bool $erasure_enabled, $order ): bool {
		if ( ! $erasure_enabled ) {
			return false;
		}
		if ( ! $order instanceof \WC_Order ) {
			return true;
		}

		global $wpdb;
		$table = Installer::events_table();
		if ( false === $wpdb->query( 'START TRANSACTION' ) ) {
			self::log_privacy_failure( 'order erasure transaction start' );
			return false;
		}

		$rows = $wpdb->get_results(
			$wpdb->prepare( "SELECT event_id, event_uuid FROM {$table} WHERE order_id = %d FOR UPDATE", $order->get_id() ),
			ARRAY_A
		);
		if ( ! empty( $wpdb->last_error ) ) {
			$wpdb->query( 'ROLLBACK' );
			self::log_privacy_failure( 'order erasure lookup' );
			return false;
		}

		foreach ( $rows ?: array() as $row ) {
			$result = $wpdb->update(
				$table,
				array(
					'source_key' => hash( 'sha256', 'privacy-erased|' . $row['event_uuid'] ),
					'source_record_id' => '',
					'order_id' => 0,
					'refund_id' => 0,
					'order_item_id' => 0,
					'deletion_note' => '',
				),
				array( 'event_id' => absint( $row['event_id'] ) ),
				array( '%s', '%s', '%d', '%d', '%d', '%s' ),
				array( '%d' )
			);
			if ( false === $result ) {
				$wpdb->query( 'ROLLBACK' );
				self::log_privacy_failure( 'order erasure update' );
				return false;
			}
		}

		if ( false === $wpdb->query( 'COMMIT' ) ) {
			$wpdb->query( 'ROLLBACK' );
			self::log_privacy_failure( 'order erasure commit' );
			return false;
		}
		return true;
	}

	/** Log a privacy operation failure without recording personal or order data. */
	private static function log_privacy_failure( string $operation ): void {
		if ( ! function_exists( 'wc_get_logger' ) ) {
			return;
		}
		wc_get_logger()->error(
			sprintf( 'Return Insights personal data %s failed because a database operation failed.', sanitize_key( $operation ) ),
			array( 'source' => 'wpessential-return-insights-privacy' )
		);
	}

	public static function exporter( array $exporters ): array {
		$exporters['wpessential-return-insights'] = array(
			'exporter_friendly_name' => __( 'WPEssential Return Insights', WPE_RI_TEXT_DOMAIN ),
			'callback' => array( self::class, 'export_user_data' ),
		);
		return $exporters;
	}

	public static function eraser( array $erasers ): array {
		$erasers['wpessential-return-insights'] = array(
			'eraser_friendly_name' => __( 'WPEssential Return Insights', WPE_RI_TEXT_DOMAIN ),
			'callback' => array( self::class, 'erase_user_data' ),
		);
		return $erasers;
	}

	/** Export user activity and WooCommerce order-linked insights in bounded pages.
	 *
	 * @return array|\\WP_Error
	 */
	public static function export_user_data( string $email, int $page = 1 ) {
		$email   = sanitize_email( $email );
		$page    = max( 1, $page );
		$user    = get_user_by( 'email', $email );
		$user_id = $user ? absint( $user->ID ) : 0;

		global $wpdb;
		$events  = Installer::events_table();
		$actions = ActionService::table();
		$history = ActionService::history_table();
		$evidence = ActionService::evidence_table();
		$parts   = array();
		$params  = array();

		if ( $user_id > 0 ) {
			$parts = array(
				"SELECT CONCAT('event-created-',event_id) AS item_id, 'Insight event created' AS label, created_at_gmt AS occurred_at, CONCAT_WS(' | ',event_id,reason_id,deletion_note) AS record_value FROM {$events} WHERE created_by = %d AND (order_id = 0 OR order_id IS NULL)",
				"SELECT CONCAT('event-updated-',event_id) AS item_id, 'Insight event updated' AS label, updated_at_gmt AS occurred_at, CONCAT_WS(' | ',event_id,reason_id,deletion_note) AS record_value FROM {$events} WHERE updated_by = %d AND (order_id = 0 OR order_id IS NULL)",
				"SELECT CONCAT('action-created-',action_id) AS item_id, 'Corrective action created' AS label, created_at_gmt AS occurred_at, CONCAT_WS(' | ',title,hypothesis,issue_url) AS record_value FROM {$actions} WHERE created_by = %d",
				"SELECT CONCAT('action-updated-',action_id) AS item_id, 'Corrective action updated' AS label, updated_at_gmt AS occurred_at, CONCAT_WS(' | ',title,hypothesis,issue_url) AS record_value FROM {$actions} WHERE updated_by = %d",
				"SELECT CONCAT('action-owner-',action_id) AS item_id, 'Corrective action assignment' AS label, updated_at_gmt AS occurred_at, CONCAT_WS(' | ',title,hypothesis,issue_url) AS record_value FROM {$actions} WHERE owner_id = %d",
				"SELECT CONCAT('action-history-',history_id) AS item_id, 'Corrective action status history' AS label, changed_at_gmt AS occurred_at, note AS record_value FROM {$history} WHERE actor_id = %d",
				"SELECT CONCAT('action-evidence-',evidence_id) AS item_id, 'Corrective action evidence capture' AS label, captured_at_gmt AS occurred_at, phase AS record_value FROM {$evidence} WHERE captured_by = %d",
			);
			$params = array_fill( 0, count( $parts ), $user_id );
		}

		$rows = array();
		if ( $parts ) {
			$sql    = implode( ' UNION ALL ', $parts ) . ' ORDER BY occurred_at ASC, item_id ASC LIMIT %d OFFSET %d';
			$params[] = self::PAGE_SIZE;
			$params[] = ( $page - 1 ) * self::PAGE_SIZE;
			$rows     = $wpdb->get_results( $wpdb->prepare( $sql, $params ), ARRAY_A );
			if ( ! empty( $wpdb->last_error ) ) {
				self::log_privacy_failure( 'user export query' );
				return new \\WP_Error(
					'return_insight_export_failed',
					__( 'Return insight activity could not be exported because its database query failed. Please retry the export.', WPE_RI_TEXT_DOMAIN )
				);
			}
		}

		$order_query = array(
			'limit'    => self::ORDER_PAGE_SIZE,
			'page'     => $page,
			'paginate' => true,
			'customer' => array_filter( array( $email, $user_id ) ),
			'orderby'  => 'ID',
			'order'    => 'ASC',
		);
		try {
			$order_result = wc_get_orders( $order_query );
		} catch ( \\Throwable $error ) {
			self::log_privacy_failure( 'customer order lookup' );
			return new \\WP_Error(
				'return_insight_order_lookup_failed',
				__( 'Order-linked return insights could not be exported because the WooCommerce order lookup failed. Please retry the export.', WPE_RI_TEXT_DOMAIN )
			);
		}
		if ( is_wp_error( $order_result ) || ! is_object( $order_result ) || ! isset( $order_result->orders, $order_result->max_num_pages ) || ! is_array( $order_result->orders ) ) {
			self::log_privacy_failure( 'customer order lookup' );
			return new \\WP_Error(
				'return_insight_order_lookup_failed',
				__( 'Order-linked return insights could not be exported because the WooCommerce order lookup did not return a valid page. Please retry the export.', WPE_RI_TEXT_DOMAIN )
			);
		}

		$order_ids = array();
		foreach ( $order_result->orders as $order ) {
			if ( ! $order instanceof \\WC_Order ) {
				self::log_privacy_failure( 'customer order lookup result' );
				return new \\WP_Error(
					'return_insight_order_lookup_failed',
					__( 'Order-linked return insights could not be exported because WooCommerce returned an invalid order record. Please retry the export.', WPE_RI_TEXT_DOMAIN )
				);
			}
			$order_id = absint( $order->get_id() );
			if ( $order_id > 0 ) {
				$order_ids[ $order_id ] = $order_id;
			}
		}

		$order_rows = array();
		if ( $order_ids ) {
			$placeholders = implode( ', ', array_fill( 0, count( $order_ids ), '%d' ) );
			$order_sql    = "SELECT event_id, reason_id, quantity, occurred_at_gmt, deletion_note FROM {$events} WHERE order_id IN ({$placeholders}) ORDER BY event_id ASC LIMIT %d";
			$order_params = array_values( $order_ids );
			$order_params[] = self::ORDER_EVENT_LIMIT + 1;
			$order_rows = $wpdb->get_results( $wpdb->prepare( $order_sql, $order_params ), ARRAY_A );
			if ( ! empty( $wpdb->last_error ) ) {
				self::log_privacy_failure( 'order-linked export query' );
				return new \\WP_Error(
					'return_insight_export_failed',
					__( 'Order-linked return insights could not be exported because its database query failed. Please retry the export.', WPE_RI_TEXT_DOMAIN )
				);
			}
			if ( count( $order_rows ?: array() ) > self::ORDER_EVENT_LIMIT ) {
				self::log_privacy_failure( 'order-linked export page limit' );
				return new \\WP_Error(
					'return_insight_export_page_limit',
					__( 'Order-linked return insight data exceeds the safe export page limit. The export stopped rather than omit records; contact the site administrator for assistance.', WPE_RI_TEXT_DOMAIN )
				);
			}
		}

		$data = array();
		foreach ( $rows ?: array() as $row ) {
			$data[] = array(
				'group_id' => 'return-insight-' . sanitize_key( $row['item_id'] ),
				'group_label' => __( 'Return insight activity', WPE_RI_TEXT_DOMAIN ),
				'item_id' => sanitize_key( $row['item_id'] ),
				'data' => array(
					array( 'name' => __( 'Activity', WPE_RI_TEXT_DOMAIN ), 'value' => sanitize_text_field( $row['label'] ) ),
					array( 'name' => __( 'Record reference', WPE_RI_TEXT_DOMAIN ), 'value' => sanitize_text_field( $row['record_value'] ) ),
					array( 'name' => __( 'Date (UTC)', WPE_RI_TEXT_DOMAIN ), 'value' => sanitize_text_field( $row['occurred_at'] ) ),
				),
			);
		}

		foreach ( $order_rows ?: array() as $row ) {
			$reason = ReasonCatalog::find( (string) $row['reason_id'], true );
			$data[] = array(
				'group_id' => 'return-insight-order-event-' . absint( $row['event_id'] ),
				'group_label' => __( 'Return insight activity', WPE_RI_TEXT_DOMAIN ),
				'item_id' => 'order-insight-' . absint( $row['event_id'] ),
				'data' => array(
					array( 'name' => __( 'Activity', WPE_RI_TEXT_DOMAIN ), 'value' => __( 'Order-linked return insight', WPE_RI_TEXT_DOMAIN ) ),
					array( 'name' => __( 'Reason', WPE_RI_TEXT_DOMAIN ), 'value' => sanitize_text_field( $reason ? Multilingual::reason_label( $reason ) : (string) $row['reason_id'] ) ),
					array( 'name' => __( 'Quantity', WPE_RI_TEXT_DOMAIN ), 'value' => absint( $row['quantity'] ) ),
					array( 'name' => __( 'Date (UTC)', WPE_RI_TEXT_DOMAIN ), 'value' => sanitize_text_field( $row['occurred_at_gmt'] ) ),
					array( 'name' => __( 'Deletion note', WPE_RI_TEXT_DOMAIN ), 'value' => sanitize_text_field( $row['deletion_note'] ) ),
				),
			);
		}

		$order_lookup_done = (int) $order_result->max_num_pages < $page;
		return array(
			'data' => $data,
			'done' => count( $rows ?: array() ) < self::PAGE_SIZE && $order_lookup_done,
		);
	}

	/** Remove user references and free-form text attributed to the requester. */
	public static function erase_user_data( string $email, int $page = 1 ): array {
		$user = get_user_by( 'email', sanitize_email( $email ) );
		if ( ! $user ) {
			return array( 'items_removed' => false, 'items_retained' => false, 'messages' => array(), 'done' => true );
		}
		global $wpdb;
		$user_id = absint( $user->ID );
		$events = Installer::events_table();
		$actions = ActionService::table();
		$history = ActionService::history_table();
		$evidence = ActionService::evidence_table();
		// Each affected row stops matching after anonymization, so repeated pages can
		// safely process the next batch without OFFSET skipping rows.
		$queries = array(
			$wpdb->prepare( "UPDATE {$events} SET deletion_note = IF(created_by = %d OR updated_by = %d, '', deletion_note), created_by = IF(created_by = %d, 0, created_by), updated_by = IF(updated_by = %d, 0, updated_by) WHERE created_by = %d OR updated_by = %d LIMIT %d", $user_id, $user_id, $user_id, $user_id, $user_id, $user_id, self::PAGE_SIZE ),
			$wpdb->prepare( "UPDATE {$actions} SET title = IF(created_by = %d OR owner_id = %d OR updated_by = %d, 'Anonymized corrective action', title), hypothesis = IF(created_by = %d OR owner_id = %d OR updated_by = %d, '', hypothesis), issue_url = IF(created_by = %d OR owner_id = %d OR updated_by = %d, '', issue_url), owner_id = IF(owner_id = %d, 0, owner_id), created_by = IF(created_by = %d, 0, created_by), updated_by = IF(updated_by = %d, 0, updated_by) WHERE owner_id = %d OR created_by = %d OR updated_by = %d LIMIT %d", $user_id, $user_id, $user_id, $user_id, $user_id, $user_id, $user_id, $user_id, $user_id, $user_id, $user_id, $user_id, $user_id, $user_id, $user_id, self::PAGE_SIZE ),
			$wpdb->prepare( "UPDATE {$history} SET actor_id = 0, note = '' WHERE actor_id = %d LIMIT %d", $user_id, self::PAGE_SIZE ),
			$wpdb->prepare( "UPDATE {$evidence} SET captured_by = 0 WHERE captured_by = %d LIMIT %d", $user_id, self::PAGE_SIZE ),
		);
		$removed = false;
		foreach ( $queries as $query ) {
			$result = $wpdb->query( $query );
			if ( false === $result ) {
				self::log_privacy_failure( 'user erasure update' );
				return array(
					'items_removed' => $removed,
					'items_retained' => true,
					'messages' => array( __( 'Some activity could not be anonymized because a database update failed. An administrator should retry this request.', WPE_RI_TEXT_DOMAIN ) ),
					'done' => false,
				);
			}
			$removed = $removed || $result > 0;
		}

		$remaining_queries = array(
			$wpdb->prepare( "SELECT COUNT(*) FROM {$events} WHERE created_by = %d OR updated_by = %d", $user_id, $user_id ),
			$wpdb->prepare( "SELECT COUNT(*) FROM {$actions} WHERE owner_id = %d OR created_by = %d OR updated_by = %d", $user_id, $user_id, $user_id ),
			$wpdb->prepare( "SELECT COUNT(*) FROM {$history} WHERE actor_id = %d", $user_id ),
			$wpdb->prepare( "SELECT COUNT(*) FROM {$evidence} WHERE captured_by = %d", $user_id ),
		);
		$remaining = 0;
		foreach ( $remaining_queries as $query ) {
			$count = $wpdb->get_var( $query );
			if ( ! empty( $wpdb->last_error ) ) {
				self::log_privacy_failure( 'user erasure verification' );
				return array(
					'items_removed' => $removed,
					'items_retained' => true,
					'messages' => array( __( 'The erasure status could not be verified. An administrator should retry this request.', WPE_RI_TEXT_DOMAIN ) ),
					'done' => false,
				);
			}
			$remaining += absint( $count );
		}

		if ( $remaining > 0 ) {
			return array(
				'items_removed' => $removed,
				'items_retained' => true,
				'messages' => array(),
				'done' => false,
			);
		}

		return array( 'items_removed' => $removed, 'items_retained' => false, 'messages' => array(), 'done' => true );
	}
}
