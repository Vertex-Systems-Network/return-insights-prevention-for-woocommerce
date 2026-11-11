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

	public static function register(): void {
		add_filter( 'wp_privacy_personal_data_exporters', array( self::class, 'exporter' ) );
		add_filter( 'wp_privacy_personal_data_erasers', array( self::class, 'eraser' ) );
		add_filter( 'woocommerce_privacy_export_order_personal_data', array( self::class, 'export_order_data' ), 10, 2 );
		add_filter( 'woocommerce_privacy_erase_order_personal_data', array( self::class, 'erase_order_data' ), 10, 2 );
	}

	/** Append return-insight records to WooCommerce's order export. */
	public static function export_order_data( array $personal_data, $order ): array {
		if ( ! $order instanceof \WC_Order ) {
			return $personal_data;
		}
		global $wpdb;
		$rows = $wpdb->get_results( $wpdb->prepare( 'SELECT event_id, reason_id, quantity, occurred_at_gmt FROM ' . Installer::events_table() . ' WHERE order_id = %d AND deleted_at_gmt IS NULL ORDER BY event_id ASC', $order->get_id() ), ARRAY_A );
		if ( ! empty( $wpdb->last_error ) ) {
			self::log_privacy_failure( 'export' );
			return $personal_data;
		}
		foreach ( $rows ?: array() as $row ) {
			$reason = ReasonCatalog::find( (string) $row['reason_id'], true );
			$personal_data[] = array(
				'name' => __( 'Return insight record', WPE_RI_TEXT_DOMAIN ),
				'value' => sprintf(
					/* translators: 1: reason label, 2: returned quantity, 3: event date. */
					__( 'Reason: %1$s; quantity: %2$s; date (UTC): %3$s', WPE_RI_TEXT_DOMAIN ),
					$reason ? Multilingual::reason_label( $reason ) : (string) $row['reason_id'],
					(string) $row['quantity'],
					(string) $row['occurred_at_gmt']
				),
			);
		}
		return $personal_data;
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

	/** Export user-attributed plugin records in bounded pages.
	 *
	 * @return array|\WP_Error
	 */
	public static function export_user_data( string $email, int $page = 1 ) {
		$user = get_user_by( 'email', sanitize_email( $email ) );
		if ( ! $user ) {
			return array( 'data' => array(), 'done' => true );
		}

		global $wpdb;
		$user_id = absint( $user->ID );
		$events = Installer::events_table();
		$actions = ActionService::table();
		$history = ActionService::history_table();
		$evidence = ActionService::evidence_table();
		$parts = array(
			"SELECT CONCAT('event-created-',event_id) AS item_id, 'Insight event created' AS label, created_at_gmt AS occurred_at, CONCAT_WS(' | ',event_id,reason_id,deletion_note) AS record_value FROM {$events} WHERE created_by = %d",
			"SELECT CONCAT('event-updated-',event_id) AS item_id, 'Insight event updated' AS label, updated_at_gmt AS occurred_at, CONCAT_WS(' | ',event_id,reason_id,deletion_note) AS record_value FROM {$events} WHERE updated_by = %d",
			"SELECT CONCAT('action-created-',action_id) AS item_id, 'Corrective action created' AS label, created_at_gmt AS occurred_at, CONCAT_WS(' | ',title,hypothesis,issue_url) AS record_value FROM {$actions} WHERE created_by = %d",
			"SELECT CONCAT('action-updated-',action_id) AS item_id, 'Corrective action updated' AS label, updated_at_gmt AS occurred_at, CONCAT_WS(' | ',title,hypothesis,issue_url) AS record_value FROM {$actions} WHERE updated_by = %d",
			"SELECT CONCAT('action-owner-',action_id) AS item_id, 'Corrective action assignment' AS label, updated_at_gmt AS occurred_at, CONCAT_WS(' | ',title,hypothesis,issue_url) AS record_value FROM {$actions} WHERE owner_id = %d",
			"SELECT CONCAT('action-history-',history_id) AS item_id, 'Corrective action status history' AS label, changed_at_gmt AS occurred_at, note AS record_value FROM {$history} WHERE actor_id = %d",
			"SELECT CONCAT('action-evidence-',evidence_id) AS item_id, 'Corrective action evidence capture' AS label, captured_at_gmt AS occurred_at, phase AS record_value FROM {$evidence} WHERE captured_by = %d",
		);
		$sql = implode( ' UNION ALL ', $parts ) . ' ORDER BY occurred_at ASC, item_id ASC LIMIT %d OFFSET %d';
		$params = array_fill( 0, count( $parts ), $user_id );
		$params[] = self::PAGE_SIZE;
		$params[] = ( max( 1, $page ) - 1 ) * self::PAGE_SIZE;
		$rows = $wpdb->get_results( $wpdb->prepare( $sql, $params ), ARRAY_A );
		if ( ! empty( $wpdb->last_error ) ) {
			self::log_privacy_failure( 'user export query' );
			return new \WP_Error(
				'return_insight_export_failed',
				__( 'Return insight activity could not be exported because its database query failed. Please retry the export.', WPE_RI_TEXT_DOMAIN )
			);
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

		return array( 'data' => $data, 'done' => count( $rows ?: array() ) < self::PAGE_SIZE );
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
