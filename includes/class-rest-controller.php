<?php
/**
 * Permission-checked catalog, order lookup, and manual event API.
 *
 * @package WPEssentialReturnInsights
 */
namespace WPEssential\ReturnInsights;
defined( 'ABSPATH' ) || exit;

/** Registers the I-03 REST routes. */
final class Rest_Controller {
	private const NS = 'wpessential/return-insights/v1';

	public static function register(): void { add_action( 'rest_api_init', array( self::class, 'routes' ) ); }

	public static function routes(): void {
		register_rest_route( self::NS, '/reasons', array(
			array( 'methods' => 'GET', 'callback' => array( self::class, 'get_reasons' ), 'permission_callback' => array( self::class, 'can_view' ), 'args' => array( 'include_inactive' => array( 'type' => 'boolean', 'default' => false, 'sanitize_callback' => 'rest_sanitize_boolean' ) ) ),
			array( 'methods' => 'POST', 'callback' => array( self::class, 'create_reason' ), 'permission_callback' => array( self::class, 'can_manage' ), 'args' => array( 'label' => array( 'type' => 'string', 'required' => true, 'minLength' => 1, 'maxLength' => 100 ), 'group' => array( 'type' => 'string', 'required' => true, 'enum' => array( 'product', 'fulfillment', 'other' ) ), 'active' => array( 'type' => 'boolean' ), 'translations' => array( 'type' => 'object' ) ) ),
		) );
		register_rest_route( self::NS, '/reasons/(?P<id>[a-z0-9_-]+)', array(
			array( 'methods' => 'GET', 'callback' => array( self::class, 'get_reason' ), 'permission_callback' => array( self::class, 'can_view' ) ),
			array( 'methods' => 'PATCH', 'callback' => array( self::class, 'update_reason' ), 'permission_callback' => array( self::class, 'can_manage' ), 'args' => array( 'label' => array( 'type' => 'string', 'minLength' => 1, 'maxLength' => 100 ), 'group' => array( 'type' => 'string', 'enum' => array( 'product', 'fulfillment', 'other' ) ), 'active' => array( 'type' => 'boolean' ), 'translations' => array( 'type' => 'object' ) ) ),
		) );
		register_rest_route( self::NS, '/orders/(?P<id>\d+)', array( 'methods' => 'GET', 'callback' => array( self::class, 'get_order_items' ), 'permission_callback' => array( self::class, 'can_manage' ) ) );
		register_rest_route( self::NS, '/sources', array( 'methods' => 'GET', 'callback' => array( self::class, 'get_sources' ), 'permission_callback' => array( self::class, 'can_view' ) ) );
		register_rest_route( self::NS, '/events', array(
			array( 'methods' => 'GET', 'callback' => array( self::class, 'get_events' ), 'permission_callback' => array( self::class, 'can_view' ), 'args' => array( 'page' => array( 'type' => 'integer', 'default' => 1, 'minimum' => 1 ), 'per_page' => array( 'type' => 'integer', 'default' => 20, 'minimum' => 1, 'maximum' => 100 ), 'order_id' => array( 'type' => 'integer', 'minimum' => 1, 'sanitize_callback' => 'absint' ) ) ),
			array( 'methods' => 'POST', 'callback' => array( self::class, 'create_event' ), 'permission_callback' => array( self::class, 'can_manage' ), 'args' => array( 'event_kind' => array( 'type' => 'string', 'required' => true, 'enum' => array( 'confirmed_return', 'exchange' ) ), 'order_id' => array( 'type' => 'integer', 'required' => true, 'minimum' => 1 ), 'order_item_id' => array( 'type' => 'integer', 'required' => true, 'minimum' => 1 ), 'quantity' => array( 'type' => 'number', 'required' => true, 'minimum' => 0.0001 ), 'reason_id' => array( 'type' => 'string', 'required' => true, 'maxLength' => 64 ), 'occurred_at' => array( 'type' => 'string', 'required' => true, 'pattern' => '^\\d{4}-\\d{2}-\\d{2},
		) );
		register_rest_route( self::NS, '/events/(?P<id>\d+)', array(
			array( 'methods' => 'GET', 'callback' => array( self::class, 'get_event' ), 'permission_callback' => array( self::class, 'can_view' ) ),
			array( 'methods' => 'PATCH', 'callback' => array( self::class, 'update_event' ), 'permission_callback' => array( self::class, 'can_manage' ), 'args' => array( 'event_kind' => array( 'type' => 'string', 'required' => true, 'enum' => array( 'confirmed_return', 'exchange' ) ), 'quantity' => array( 'type' => 'number', 'required' => true, 'minimum' => 0.0001 ), 'reason_id' => array( 'type' => 'string', 'required' => true, 'maxLength' => 64 ), 'occurred_at' => array( 'type' => 'string', 'required' => true, 'pattern' => '^\\d{4}-\\d{2}-\\d{2},
			array( 'methods' => 'DELETE', 'callback' => array( self::class, 'delete_event' ), 'permission_callback' => array( self::class, 'can_manage' ), 'args' => array( 'audit_reason' => array( 'type' => 'string', 'required' => true, 'minLength' => 1, 'maxLength' => 200 ) ) ),
		) );
	}
	public static function can_view(): bool { return current_user_can( Capabilities::VIEW ); }
	public static function can_manage(): bool { return current_user_can( Capabilities::MANAGE ); }
	public static function get_reasons( \WP_REST_Request $request ): \WP_REST_Response {
		$include_inactive = rest_sanitize_boolean( $request->get_param( 'include_inactive' ) ) && self::can_manage();
		return rest_ensure_response( Reason_Catalog::all( $include_inactive ) );
	}
	public static function get_reason( \WP_REST_Request $r ) {
		$x = Reason_Catalog::find( (string) $r['id'], true );
		return $x ? rest_ensure_response( $x ) : new \WP_Error( 'reason_not_found', __( 'Reason not found.', WPE_RI_TEXT_DOMAIN ), array( 'status' => 404 ) );
	}
	public static function create_reason( \\WP_REST_Request $r ) {
		$data = $r->get_json_params() ?: array();
		$unknown = self::reject_unknown_fields( $data, array( 'label', 'group', 'active', 'translations' ) );
		if ( is_wp_error( $unknown ) ) { return $unknown; }
		$x = Reason_Catalog::save( $data );
		return is_wp_error( $x ) ? $x : new \\WP_REST_Response( $x, 201 );
	}	public static function update_reason( \\WP_REST_Request $r ) {
		$data = $r->get_json_params() ?: array();
		$unknown = self::reject_unknown_fields( $data, array( 'label', 'group', 'active', 'translations' ) );
		if ( is_wp_error( $unknown ) ) { return $unknown; }
		$x = Reason_Catalog::save( $data, (string) $r['id'] );
		return is_wp_error( $x ) ? $x : rest_ensure_response( $x );
	}	public static function get_order_items( \WP_REST_Request $r ) {
		$order = wc_get_order( absint( $r['id'] ) );
		if ( ! $order ) { return new \WP_Error( 'order_not_found', __( 'Order not found.', WPE_RI_TEXT_DOMAIN ), array( 'status' => 404 ) ); }
		$items = array();
		foreach ( $order->get_items( 'line_item' ) as $item ) {
			if ( ! $item instanceof \WC_Order_Item_Product || ! $item->get_product() ) { continue; }
			$p = $item->get_product();
			$items[] = array( 'order_item_id' => $item->get_id(), 'product_id' => $p->get_parent_id() ?: $p->get_id(), 'variation_id' => $p->get_parent_id() ? $p->get_id() : 0, 'name' => $item->get_name(), 'ordered_quantity' => (float) $item->get_quantity() );
		}
		return rest_ensure_response( array( 'order_id' => $order->get_id(), 'order_number' => $order->get_order_number(), 'items' => $items ) );
	}
	public static function get_sources(): \WP_REST_Response {
		global $wpdb;
		$table = Installer::events_table();
		$rows = $wpdb->get_results( "SELECT source_type, COUNT(*) AS event_count, MAX(occurred_at_gmt) AS latest_event_at FROM {$table} WHERE deleted_at_gmt IS NULL GROUP BY source_type ORDER BY source_type ASC", ARRAY_A );
		$sources = array_map(
			static function ( array $row ): array {
				return array(
					'source_type' => $row['source_type'],
					'event_count' => (int) $row['event_count'],
					'latest_event_at' => $row['latest_event_at'] ? gmdate( 'c', strtotime( $row['latest_event_at'] . ' UTC' ) ) : null,
					'latest_event_date' => $row['latest_event_at'] ? get_date_from_gmt( $row['latest_event_at'], 'Y-m-d' ) : null,
				);
			},
			$rows ?: array()
		);
		return rest_ensure_response( array( 'sources' => $sources ) );
	}

	public static function get_events( \WP_REST_Request $r ) {
		global $wpdb;
		$page = max( 1, absint( $r->get_param( 'page' ) ) );
		$per = min( 100, max( 1, absint( $r->get_param( 'per_page' ) ?: 20 ) ) );
		$order_id = absint( $r->get_param( 'order_id' ) );
		$table = Installer::events_table(); $where = 'deleted_at_gmt IS NULL'; $args = array();
		if ( $order_id ) { $where .= ' AND order_id = %d'; $args[] = $order_id; }
		$total = (int) $wpdb->get_var( $args ? $wpdb->prepare( "SELECT COUNT(*) FROM {$table} WHERE {$where}", $args ) : "SELECT COUNT(*) FROM {$table} WHERE {$where}" );
		$list_args = array_merge( $args, array( $per, ( $page - 1 ) * $per ) );
		$rows = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$table} WHERE {$where} ORDER BY occurred_at_gmt DESC, event_id DESC LIMIT %d OFFSET %d", $list_args ), ARRAY_A );
		$res = rest_ensure_response( array_map( array( self::class, 'serialize_event' ), $rows ?: array() ) );
		$res->header( 'X-WP-Total', (string) $total ); $res->header( 'X-WP-TotalPages', (string) ceil( $total / $per ) );
		return $res;
	}
	public static function get_event( \WP_REST_Request $r ) {
		$row = self::find_event( absint( $r['id'] ) );
		return $row ? rest_ensure_response( self::serialize_event( $row ) ) : self::missing_event();
	}
	public static function create_event( \WP_REST_Request $r ) {
		global $wpdb;
		$d = $r->get_json_params() ?: array();
		$unknown = self::reject_unknown_fields( $d, array( 'event_kind', 'order_id', 'order_item_id', 'quantity', 'reason_id', 'occurred_at', 'idempotency_key' ) );
		if ( is_wp_error( $unknown ) ) { return $unknown; }
		$v = self::validate_event( $d );
		if ( is_wp_error( $v ) ) { return $v; }
		$key = sanitize_text_field( $d['idempotency_key'] ?? '' );
		if ( ! preg_match( '/^[A-Za-z0-9_-]{16,100}$/', $key ) ) { return new \WP_Error( 'invalid_idempotency_key', __( 'A 16–100 character idempotency key is required.', WPE_RI_TEXT_DOMAIN ), array( 'status' => 400 ) ); }
		$hash = hash( 'sha256', 'manual|' . get_current_blog_id() . '|' . $key ); $table = Installer::events_table();
		$existing = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE source_key = %s", $hash ), ARRAY_A );
		if ( $existing ) { return new \WP_REST_Response( self::serialize_event( $existing ), 200 ); }
		$now = gmdate( 'Y-m-d H:i:s' );
		$ok = $wpdb->insert( $table, array(
			'event_uuid' => wp_generate_uuid4(), 'source_key' => $hash, 'source_type' => 'manual', 'source_record_id' => $key,
			'event_kind' => $v['kind'], 'order_id' => $v['order']->get_id(), 'order_item_id' => $v['item']->get_id(),
			'product_id' => $v['product_id'], 'variation_id' => $v['variation_id'], 'reason_id' => $v['reason']['id'],
			'quantity' => $v['quantity'], 'attribution_scope' => 'item', 'mapping_state' => 'mapped',
			'occurred_at_gmt' => $v['date'], 'created_at_gmt' => $now, 'updated_at_gmt' => $now,
			'created_by' => get_current_user_id(), 'updated_by' => get_current_user_id(),
		) );
		if ( false === $ok ) { return new \WP_Error( 'event_write_failed', __( 'The insight could not be saved.', WPE_RI_TEXT_DOMAIN ), array( 'status' => 500 ) ); }
		return new \WP_REST_Response( self::serialize_event( self::find_event( (int) $wpdb->insert_id ) ), 201 );
	}
	public static function update_event( \WP_REST_Request $r ) {
		global $wpdb;
		$id = absint( $r['id'] ); $row = self::find_event( $id );
		if ( ! $row ) { return self::missing_event(); }
		if ( 'manual' !== $row['source_type'] ) { return new \WP_Error( 'immutable_source', __( 'Observed source records cannot be edited here.', WPE_RI_TEXT_DOMAIN ), array( 'status' => 409 ) ); }
		$d = $r->get_json_params() ?: array();
		$unknown = self::reject_unknown_fields( $d, array( 'event_kind', 'quantity', 'reason_id', 'occurred_at' ) );
		if ( is_wp_error( $unknown ) ) { return $unknown; }
		$v = self::validate_event( array_merge( $d, array( 'order_id' => $row['order_id'], 'order_item_id' => $row['order_item_id'] ) ), $id );
		if ( is_wp_error( $v ) ) { return $v; }
		$wpdb->update( Installer::events_table(), array( 'event_kind' => $v['kind'], 'quantity' => $v['quantity'], 'reason_id' => $v['reason']['id'], 'occurred_at_gmt' => $v['date'], 'updated_at_gmt' => gmdate( 'Y-m-d H:i:s' ), 'updated_by' => get_current_user_id() ), array( 'event_id' => $id ) );
		return rest_ensure_response( self::serialize_event( self::find_event( $id ) ) );
	}
	public static function delete_event( \WP_REST_Request $r ) {
		global $wpdb;
		$id = absint( $r['id'] ); $row = self::find_event( $id );
		if ( ! $row ) { return self::missing_event(); }
		if ( 'manual' !== $row['source_type'] ) { return new \WP_Error( 'immutable_source', __( 'Observed source records cannot be deleted here.', WPE_RI_TEXT_DOMAIN ), array( 'status' => 409 ) ); }
		$data = $r->get_json_params() ?: array();
		$unknown = self::reject_unknown_fields( $data, array( 'audit_reason' ) );
		if ( is_wp_error( $unknown ) ) { return $unknown; }
		$note = sanitize_text_field( (string) ( $data['audit_reason'] ?? '' ) );
		if ( '' === $note || strlen( $note ) > 200 ) { return new \WP_Error( 'audit_reason_required', __( 'Provide an audit reason up to 200 characters.', WPE_RI_TEXT_DOMAIN ), array( 'status' => 400 ) ); }
		$wpdb->update( Installer::events_table(), array( 'deleted_at_gmt' => gmdate( 'Y-m-d H:i:s' ), 'deletion_note' => $note, 'updated_at_gmt' => gmdate( 'Y-m-d H:i:s' ), 'updated_by' => get_current_user_id() ), array( 'event_id' => $id ) );
		return new \WP_REST_Response( null, 204 );
	}
	private static function validate_event( array $d, int $ignore_event_id = 0 ) {
		$kind = sanitize_key( $d['event_kind'] ?? '' );
		if ( ! in_array( $kind, array( 'confirmed_return', 'exchange' ), true ) ) { return new \WP_Error( 'invalid_event_kind', __( 'Manual insights support confirmed returns or exchanges.', WPE_RI_TEXT_DOMAIN ), array( 'status' => 400 ) ); }
		$reason = Reason_Catalog::find( sanitize_key( $d['reason_id'] ?? '' ) );
		$q = isset( $d['quantity'] ) ? (float) $d['quantity'] : 0;
		if ( ! $reason || $q <= 0 || $q > 999999 ) { return new \WP_Error( 'invalid_event', __( 'Choose an active reason and enter a positive quantity.', WPE_RI_TEXT_DOMAIN ), array( 'status' => 400 ) ); }
		$order = wc_get_order( absint( $d['order_id'] ?? 0 ) ); $item = $order ? $order->get_item( absint( $d['order_item_id'] ?? 0 ) ) : false;
		if ( ! $order || ! $item instanceof \WC_Order_Item_Product ) { return new \WP_Error( 'invalid_order_item', __( 'The selected item does not belong to this order.', WPE_RI_TEXT_DOMAIN ), array( 'status' => 400 ) ); }
		$p = $item->get_product();
		if ( ! $p ) { return new \WP_Error( 'product_missing', __( 'The product for this order item is unavailable.', WPE_RI_TEXT_DOMAIN ), array( 'status' => 400 ) ); }
		global $wpdb;
		$table = Installer::events_table();
		$sql = "SELECT COALESCE(SUM(quantity), 0) FROM {$table} WHERE order_item_id = %d AND event_kind IN ('confirmed_return','exchange') AND deleted_at_gmt IS NULL";
		$args = array( $item->get_id() );
		if ( $ignore_event_id ) { $sql .= ' AND event_id <> %d'; $args[] = $ignore_event_id; }
		$already_recorded = (float) $wpdb->get_var( $wpdb->prepare( $sql, $args ) );
		if ( $already_recorded + $q > (float) $item->get_quantity() ) {
			return new \WP_Error( 'quantity_exceeds_ordered', __( 'Insight quantity cannot exceed the remaining quantity on this order item.', WPE_RI_TEXT_DOMAIN ), array( 'status' => 400 ) );
		}
		$date = sanitize_text_field( $d['occurred_at'] ?? '' );
		if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date ) ) { return new \WP_Error( 'invalid_date', __( 'Use a date in YYYY-MM-DD format.', WPE_RI_TEXT_DOMAIN ), array( 'status' => 400 ) ); }
		$local = \DateTimeImmutable::createFromFormat( '!Y-m-d', $date, wp_timezone() );
		if ( ! $local || $local->format( 'Y-m-d' ) !== $date ) { return new \WP_Error( 'invalid_date', __( 'Enter a valid calendar date.', WPE_RI_TEXT_DOMAIN ), array( 'status' => 400 ) ); }
		return array( 'kind' => $kind, 'reason' => $reason, 'quantity' => wc_format_decimal( $q, 4 ), 'order' => $order, 'item' => $item, 'product_id' => $p->get_parent_id() ?: $p->get_id(), 'variation_id' => $p->get_parent_id() ? $p->get_id() : 0, 'date' => $local->setTimezone( new \DateTimeZone( 'UTC' ) )->format( 'Y-m-d H:i:s' ) );
	}
	private static function find_event( int $id ) {
		global $wpdb; $table = Installer::events_table();
		return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE event_id = %d", $id ), ARRAY_A );
	}
	private static function serialize_event( array $row ): array {
		$reason = Reason_Catalog::find( $row['reason_id'], true );
		return array( 'id' => (int) $row['event_id'], 'event_kind' => $row['event_kind'], 'source_type' => $row['source_type'], 'order_id' => (int) $row['order_id'], 'refund_id' => (int) ( $row['refund_id'] ?? 0 ), 'order_item_id' => (int) $row['order_item_id'], 'attribution_scope' => $row['attribution_scope'], 'mapping_state' => $row['mapping_state'], 'product_id' => (int) $row['product_id'], 'variation_id' => (int) $row['variation_id'], 'reason_id' => $row['reason_id'], 'reason_label' => $reason ? $reason['label'] : __( 'Archived reason', WPE_RI_TEXT_DOMAIN ), 'quantity' => (float) $row['quantity'], 'occurred_at' => gmdate( 'c', strtotime( $row['occurred_at_gmt'] . ' UTC' ) ), 'created_at' => gmdate( 'c', strtotime( $row['created_at_gmt'] . ' UTC' ) ), 'updated_at' => gmdate( 'c', strtotime( $row['updated_at_gmt'] . ' UTC' ) ), 'occurred_date' => get_date_from_gmt( $row['occurred_at_gmt'], 'Y-m-d' ) );
	}
	private static function reject_unknown_fields( array $data, array $allowed ) {
		$unknown = array_diff( array_keys( $data ), $allowed );
		if ( $unknown ) {
			return new \\WP_Error( 'unknown_fields', __( 'The request contains unsupported fields.', WPE_RI_TEXT_DOMAIN ), array( 'status' => 400, 'fields' => array_values( $unknown ) ) );
		}
		return true;
	}
	private static function missing_event(): \WP_Error { return new \WP_Error( 'event_not_found', __( 'Insight event not found.', WPE_RI_TEXT_DOMAIN ), array( 'status' => 404 ) ); }
}
 ), 'idempotency_key' => array( 'type' => 'string', 'required' => true, 'minLength' => 16, 'maxLength' => 100 ) ) ),
		) );
		register_rest_route( self::NS, '/events/(?P<id>\d+)', array(
			array( 'methods' => 'GET', 'callback' => array( self::class, 'get_event' ), 'permission_callback' => array( self::class, 'can_view' ) ),
			array( 'methods' => 'PATCH', 'callback' => array( self::class, 'update_event' ), 'permission_callback' => array( self::class, 'can_manage' ) ),
			array( 'methods' => 'DELETE', 'callback' => array( self::class, 'delete_event' ), 'permission_callback' => array( self::class, 'can_manage' ) ),
		) );
	}
	public static function can_view(): bool { return current_user_can( Capabilities::VIEW ); }
	public static function can_manage(): bool { return current_user_can( Capabilities::MANAGE ); }
	public static function get_reasons( \WP_REST_Request $request ): \WP_REST_Response {
		$include_inactive = rest_sanitize_boolean( $request->get_param( 'include_inactive' ) ) && self::can_manage();
		return rest_ensure_response( Reason_Catalog::all( $include_inactive ) );
	}
	public static function get_reason( \WP_REST_Request $r ) {
		$x = Reason_Catalog::find( (string) $r['id'], true );
		return $x ? rest_ensure_response( $x ) : new \WP_Error( 'reason_not_found', __( 'Reason not found.', WPE_RI_TEXT_DOMAIN ), array( 'status' => 404 ) );
	}
	public static function create_reason( \WP_REST_Request $r ) {
		$x = Reason_Catalog::save( $r->get_json_params() ?: array() );
		return is_wp_error( $x ) ? $x : new \WP_REST_Response( $x, 201 );
	}
	public static function update_reason( \WP_REST_Request $r ) {
		$x = Reason_Catalog::save( $r->get_json_params() ?: array(), (string) $r['id'] );
		return is_wp_error( $x ) ? $x : rest_ensure_response( $x );
	}
	public static function get_order_items( \WP_REST_Request $r ) {
		$order = wc_get_order( absint( $r['id'] ) );
		if ( ! $order ) { return new \WP_Error( 'order_not_found', __( 'Order not found.', WPE_RI_TEXT_DOMAIN ), array( 'status' => 404 ) ); }
		$items = array();
		foreach ( $order->get_items( 'line_item' ) as $item ) {
			if ( ! $item instanceof \WC_Order_Item_Product || ! $item->get_product() ) { continue; }
			$p = $item->get_product();
			$items[] = array( 'order_item_id' => $item->get_id(), 'product_id' => $p->get_parent_id() ?: $p->get_id(), 'variation_id' => $p->get_parent_id() ? $p->get_id() : 0, 'name' => $item->get_name(), 'ordered_quantity' => (float) $item->get_quantity() );
		}
		return rest_ensure_response( array( 'order_id' => $order->get_id(), 'order_number' => $order->get_order_number(), 'items' => $items ) );
	}
	public static function get_sources(): \WP_REST_Response {
		global $wpdb;
		$table = Installer::events_table();
		$rows = $wpdb->get_results( "SELECT source_type, COUNT(*) AS event_count, MAX(occurred_at_gmt) AS latest_event_at FROM {$table} WHERE deleted_at_gmt IS NULL GROUP BY source_type ORDER BY source_type ASC", ARRAY_A );
		$sources = array_map(
			static function ( array $row ): array {
				return array(
					'source_type' => $row['source_type'],
					'event_count' => (int) $row['event_count'],
					'latest_event_at' => $row['latest_event_at'] ? gmdate( 'c', strtotime( $row['latest_event_at'] . ' UTC' ) ) : null,
					'latest_event_date' => $row['latest_event_at'] ? get_date_from_gmt( $row['latest_event_at'], 'Y-m-d' ) : null,
				);
			},
			$rows ?: array()
		);
		return rest_ensure_response( array( 'sources' => $sources ) );
	}

	public static function get_events( \WP_REST_Request $r ) {
		global $wpdb;
		$page = max( 1, absint( $r->get_param( 'page' ) ) );
		$per = min( 100, max( 1, absint( $r->get_param( 'per_page' ) ?: 20 ) ) );
		$order_id = absint( $r->get_param( 'order_id' ) );
		$table = Installer::events_table(); $where = 'deleted_at_gmt IS NULL'; $args = array();
		if ( $order_id ) { $where .= ' AND order_id = %d'; $args[] = $order_id; }
		$total = (int) $wpdb->get_var( $args ? $wpdb->prepare( "SELECT COUNT(*) FROM {$table} WHERE {$where}", $args ) : "SELECT COUNT(*) FROM {$table} WHERE {$where}" );
		$list_args = array_merge( $args, array( $per, ( $page - 1 ) * $per ) );
		$rows = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$table} WHERE {$where} ORDER BY occurred_at_gmt DESC, event_id DESC LIMIT %d OFFSET %d", $list_args ), ARRAY_A );
		$res = rest_ensure_response( array_map( array( self::class, 'serialize_event' ), $rows ?: array() ) );
		$res->header( 'X-WP-Total', (string) $total ); $res->header( 'X-WP-TotalPages', (string) ceil( $total / $per ) );
		return $res;
	}
	public static function get_event( \WP_REST_Request $r ) {
		$row = self::find_event( absint( $r['id'] ) );
		return $row ? rest_ensure_response( self::serialize_event( $row ) ) : self::missing_event();
	}
	public static function create_event( \WP_REST_Request $r ) {
		global $wpdb;
		$d = $r->get_json_params() ?: array(); $v = self::validate_event( $d );
		if ( is_wp_error( $v ) ) { return $v; }
		$key = sanitize_text_field( $d['idempotency_key'] ?? '' );
		if ( ! preg_match( '/^[A-Za-z0-9_-]{16,100}$/', $key ) ) { return new \WP_Error( 'invalid_idempotency_key', __( 'A 16–100 character idempotency key is required.', WPE_RI_TEXT_DOMAIN ), array( 'status' => 400 ) ); }
		$hash = hash( 'sha256', 'manual|' . get_current_blog_id() . '|' . $key ); $table = Installer::events_table();
		$existing = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE source_key = %s", $hash ), ARRAY_A );
		if ( $existing ) { return new \WP_REST_Response( self::serialize_event( $existing ), 200 ); }
		$now = gmdate( 'Y-m-d H:i:s' );
		$ok = $wpdb->insert( $table, array(
			'event_uuid' => wp_generate_uuid4(), 'source_key' => $hash, 'source_type' => 'manual', 'source_record_id' => $key,
			'event_kind' => $v['kind'], 'order_id' => $v['order']->get_id(), 'order_item_id' => $v['item']->get_id(),
			'product_id' => $v['product_id'], 'variation_id' => $v['variation_id'], 'reason_id' => $v['reason']['id'],
			'quantity' => $v['quantity'], 'attribution_scope' => 'item', 'mapping_state' => 'mapped',
			'occurred_at_gmt' => $v['date'], 'created_at_gmt' => $now, 'updated_at_gmt' => $now,
			'created_by' => get_current_user_id(), 'updated_by' => get_current_user_id(),
		) );
		if ( false === $ok ) { return new \WP_Error( 'event_write_failed', __( 'The insight could not be saved.', WPE_RI_TEXT_DOMAIN ), array( 'status' => 500 ) ); }
		return new \WP_REST_Response( self::serialize_event( self::find_event( (int) $wpdb->insert_id ) ), 201 );
	}
	public static function update_event( \WP_REST_Request $r ) {
		global $wpdb;
		$id = absint( $r['id'] ); $row = self::find_event( $id );
		if ( ! $row ) { return self::missing_event(); }
		if ( 'manual' !== $row['source_type'] ) { return new \WP_Error( 'immutable_source', __( 'Observed source records cannot be edited here.', WPE_RI_TEXT_DOMAIN ), array( 'status' => 409 ) ); }
		$d = $r->get_json_params() ?: array();
		$v = self::validate_event( array_merge( $d, array( 'order_id' => $row['order_id'], 'order_item_id' => $row['order_item_id'] ) ), $id );
		if ( is_wp_error( $v ) ) { return $v; }
		$wpdb->update( Installer::events_table(), array( 'event_kind' => $v['kind'], 'quantity' => $v['quantity'], 'reason_id' => $v['reason']['id'], 'occurred_at_gmt' => $v['date'], 'updated_at_gmt' => gmdate( 'Y-m-d H:i:s' ), 'updated_by' => get_current_user_id() ), array( 'event_id' => $id ) );
		return rest_ensure_response( self::serialize_event( self::find_event( $id ) ) );
	}
	public static function delete_event( \WP_REST_Request $r ) {
		global $wpdb;
		$id = absint( $r['id'] ); $row = self::find_event( $id );
		if ( ! $row ) { return self::missing_event(); }
		if ( 'manual' !== $row['source_type'] ) { return new \WP_Error( 'immutable_source', __( 'Observed source records cannot be deleted here.', WPE_RI_TEXT_DOMAIN ), array( 'status' => 409 ) ); }
		$note = sanitize_text_field( (string) ( $r->get_param( 'audit_reason' ) ?? '' ) );
		if ( '' === $note || strlen( $note ) > 200 ) { return new \WP_Error( 'audit_reason_required', __( 'Provide an audit reason up to 200 characters.', WPE_RI_TEXT_DOMAIN ), array( 'status' => 400 ) ); }
		$wpdb->update( Installer::events_table(), array( 'deleted_at_gmt' => gmdate( 'Y-m-d H:i:s' ), 'deletion_note' => $note, 'updated_at_gmt' => gmdate( 'Y-m-d H:i:s' ), 'updated_by' => get_current_user_id() ), array( 'event_id' => $id ) );
		return new \WP_REST_Response( null, 204 );
	}
	private static function validate_event( array $d, int $ignore_event_id = 0 ) {
		$kind = sanitize_key( $d['event_kind'] ?? '' );
		if ( ! in_array( $kind, array( 'confirmed_return', 'exchange' ), true ) ) { return new \WP_Error( 'invalid_event_kind', __( 'Manual insights support confirmed returns or exchanges.', WPE_RI_TEXT_DOMAIN ), array( 'status' => 400 ) ); }
		$reason = Reason_Catalog::find( sanitize_key( $d['reason_id'] ?? '' ) );
		$q = isset( $d['quantity'] ) ? (float) $d['quantity'] : 0;
		if ( ! $reason || $q <= 0 || $q > 999999 ) { return new \WP_Error( 'invalid_event', __( 'Choose an active reason and enter a positive quantity.', WPE_RI_TEXT_DOMAIN ), array( 'status' => 400 ) ); }
		$order = wc_get_order( absint( $d['order_id'] ?? 0 ) ); $item = $order ? $order->get_item( absint( $d['order_item_id'] ?? 0 ) ) : false;
		if ( ! $order || ! $item instanceof \WC_Order_Item_Product ) { return new \WP_Error( 'invalid_order_item', __( 'The selected item does not belong to this order.', WPE_RI_TEXT_DOMAIN ), array( 'status' => 400 ) ); }
		$p = $item->get_product();
		if ( ! $p ) { return new \WP_Error( 'product_missing', __( 'The product for this order item is unavailable.', WPE_RI_TEXT_DOMAIN ), array( 'status' => 400 ) ); }
		global $wpdb;
		$table = Installer::events_table();
		$sql = "SELECT COALESCE(SUM(quantity), 0) FROM {$table} WHERE order_item_id = %d AND event_kind IN ('confirmed_return','exchange') AND deleted_at_gmt IS NULL";
		$args = array( $item->get_id() );
		if ( $ignore_event_id ) { $sql .= ' AND event_id <> %d'; $args[] = $ignore_event_id; }
		$already_recorded = (float) $wpdb->get_var( $wpdb->prepare( $sql, $args ) );
		if ( $already_recorded + $q > (float) $item->get_quantity() ) {
			return new \WP_Error( 'quantity_exceeds_ordered', __( 'Insight quantity cannot exceed the remaining quantity on this order item.', WPE_RI_TEXT_DOMAIN ), array( 'status' => 400 ) );
		}
		$date = sanitize_text_field( $d['occurred_at'] ?? '' );
		if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date ) ) { return new \WP_Error( 'invalid_date', __( 'Use a date in YYYY-MM-DD format.', WPE_RI_TEXT_DOMAIN ), array( 'status' => 400 ) ); }
		$local = \DateTimeImmutable::createFromFormat( '!Y-m-d', $date, wp_timezone() );
		if ( ! $local || $local->format( 'Y-m-d' ) !== $date ) { return new \WP_Error( 'invalid_date', __( 'Enter a valid calendar date.', WPE_RI_TEXT_DOMAIN ), array( 'status' => 400 ) ); }
		return array( 'kind' => $kind, 'reason' => $reason, 'quantity' => wc_format_decimal( $q, 4 ), 'order' => $order, 'item' => $item, 'product_id' => $p->get_parent_id() ?: $p->get_id(), 'variation_id' => $p->get_parent_id() ? $p->get_id() : 0, 'date' => $local->setTimezone( new \DateTimeZone( 'UTC' ) )->format( 'Y-m-d H:i:s' ) );
	}
	private static function find_event( int $id ) {
		global $wpdb; $table = Installer::events_table();
		return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE event_id = %d", $id ), ARRAY_A );
	}
	private static function serialize_event( array $row ): array {
		$reason = Reason_Catalog::find( $row['reason_id'], true );
		return array( 'id' => (int) $row['event_id'], 'event_kind' => $row['event_kind'], 'source_type' => $row['source_type'], 'order_id' => (int) $row['order_id'], 'refund_id' => (int) ( $row['refund_id'] ?? 0 ), 'order_item_id' => (int) $row['order_item_id'], 'attribution_scope' => $row['attribution_scope'], 'mapping_state' => $row['mapping_state'], 'product_id' => (int) $row['product_id'], 'variation_id' => (int) $row['variation_id'], 'reason_id' => $row['reason_id'], 'reason_label' => $reason ? $reason['label'] : __( 'Archived reason', WPE_RI_TEXT_DOMAIN ), 'quantity' => (float) $row['quantity'], 'occurred_at' => gmdate( 'c', strtotime( $row['occurred_at_gmt'] . ' UTC' ) ), 'created_at' => gmdate( 'c', strtotime( $row['created_at_gmt'] . ' UTC' ) ), 'updated_at' => gmdate( 'c', strtotime( $row['updated_at_gmt'] . ' UTC' ) ), 'occurred_date' => get_date_from_gmt( $row['occurred_at_gmt'], 'Y-m-d' ) );
	}
	private static function missing_event(): \WP_Error { return new \WP_Error( 'event_not_found', __( 'Insight event not found.', WPE_RI_TEXT_DOMAIN ), array( 'status' => 404 ) ); }
}
 ) ) ),
			array( 'methods' => 'DELETE', 'callback' => array( self::class, 'delete_event' ), 'permission_callback' => array( self::class, 'can_manage' ) ),
		) );
	}
	public static function can_view(): bool { return current_user_can( Capabilities::VIEW ); }
	public static function can_manage(): bool { return current_user_can( Capabilities::MANAGE ); }
	public static function get_reasons( \WP_REST_Request $request ): \WP_REST_Response {
		$include_inactive = rest_sanitize_boolean( $request->get_param( 'include_inactive' ) ) && self::can_manage();
		return rest_ensure_response( Reason_Catalog::all( $include_inactive ) );
	}
	public static function get_reason( \WP_REST_Request $r ) {
		$x = Reason_Catalog::find( (string) $r['id'], true );
		return $x ? rest_ensure_response( $x ) : new \WP_Error( 'reason_not_found', __( 'Reason not found.', WPE_RI_TEXT_DOMAIN ), array( 'status' => 404 ) );
	}
	public static function create_reason( \WP_REST_Request $r ) {
		$x = Reason_Catalog::save( $r->get_json_params() ?: array() );
		return is_wp_error( $x ) ? $x : new \WP_REST_Response( $x, 201 );
	}
	public static function update_reason( \WP_REST_Request $r ) {
		$x = Reason_Catalog::save( $r->get_json_params() ?: array(), (string) $r['id'] );
		return is_wp_error( $x ) ? $x : rest_ensure_response( $x );
	}
	public static function get_order_items( \WP_REST_Request $r ) {
		$order = wc_get_order( absint( $r['id'] ) );
		if ( ! $order ) { return new \WP_Error( 'order_not_found', __( 'Order not found.', WPE_RI_TEXT_DOMAIN ), array( 'status' => 404 ) ); }
		$items = array();
		foreach ( $order->get_items( 'line_item' ) as $item ) {
			if ( ! $item instanceof \WC_Order_Item_Product || ! $item->get_product() ) { continue; }
			$p = $item->get_product();
			$items[] = array( 'order_item_id' => $item->get_id(), 'product_id' => $p->get_parent_id() ?: $p->get_id(), 'variation_id' => $p->get_parent_id() ? $p->get_id() : 0, 'name' => $item->get_name(), 'ordered_quantity' => (float) $item->get_quantity() );
		}
		return rest_ensure_response( array( 'order_id' => $order->get_id(), 'order_number' => $order->get_order_number(), 'items' => $items ) );
	}
	public static function get_sources(): \WP_REST_Response {
		global $wpdb;
		$table = Installer::events_table();
		$rows = $wpdb->get_results( "SELECT source_type, COUNT(*) AS event_count, MAX(occurred_at_gmt) AS latest_event_at FROM {$table} WHERE deleted_at_gmt IS NULL GROUP BY source_type ORDER BY source_type ASC", ARRAY_A );
		$sources = array_map(
			static function ( array $row ): array {
				return array(
					'source_type' => $row['source_type'],
					'event_count' => (int) $row['event_count'],
					'latest_event_at' => $row['latest_event_at'] ? gmdate( 'c', strtotime( $row['latest_event_at'] . ' UTC' ) ) : null,
					'latest_event_date' => $row['latest_event_at'] ? get_date_from_gmt( $row['latest_event_at'], 'Y-m-d' ) : null,
				);
			},
			$rows ?: array()
		);
		return rest_ensure_response( array( 'sources' => $sources ) );
	}

	public static function get_events( \WP_REST_Request $r ) {
		global $wpdb;
		$page = max( 1, absint( $r->get_param( 'page' ) ) );
		$per = min( 100, max( 1, absint( $r->get_param( 'per_page' ) ?: 20 ) ) );
		$order_id = absint( $r->get_param( 'order_id' ) );
		$table = Installer::events_table(); $where = 'deleted_at_gmt IS NULL'; $args = array();
		if ( $order_id ) { $where .= ' AND order_id = %d'; $args[] = $order_id; }
		$total = (int) $wpdb->get_var( $args ? $wpdb->prepare( "SELECT COUNT(*) FROM {$table} WHERE {$where}", $args ) : "SELECT COUNT(*) FROM {$table} WHERE {$where}" );
		$list_args = array_merge( $args, array( $per, ( $page - 1 ) * $per ) );
		$rows = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$table} WHERE {$where} ORDER BY occurred_at_gmt DESC, event_id DESC LIMIT %d OFFSET %d", $list_args ), ARRAY_A );
		$res = rest_ensure_response( array_map( array( self::class, 'serialize_event' ), $rows ?: array() ) );
		$res->header( 'X-WP-Total', (string) $total ); $res->header( 'X-WP-TotalPages', (string) ceil( $total / $per ) );
		return $res;
	}
	public static function get_event( \WP_REST_Request $r ) {
		$row = self::find_event( absint( $r['id'] ) );
		return $row ? rest_ensure_response( self::serialize_event( $row ) ) : self::missing_event();
	}
	public static function create_event( \WP_REST_Request $r ) {
		global $wpdb;
		$d = $r->get_json_params() ?: array(); $v = self::validate_event( $d );
		if ( is_wp_error( $v ) ) { return $v; }
		$key = sanitize_text_field( $d['idempotency_key'] ?? '' );
		if ( ! preg_match( '/^[A-Za-z0-9_-]{16,100}$/', $key ) ) { return new \WP_Error( 'invalid_idempotency_key', __( 'A 16–100 character idempotency key is required.', WPE_RI_TEXT_DOMAIN ), array( 'status' => 400 ) ); }
		$hash = hash( 'sha256', 'manual|' . get_current_blog_id() . '|' . $key ); $table = Installer::events_table();
		$existing = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE source_key = %s", $hash ), ARRAY_A );
		if ( $existing ) { return new \WP_REST_Response( self::serialize_event( $existing ), 200 ); }
		$now = gmdate( 'Y-m-d H:i:s' );
		$ok = $wpdb->insert( $table, array(
			'event_uuid' => wp_generate_uuid4(), 'source_key' => $hash, 'source_type' => 'manual', 'source_record_id' => $key,
			'event_kind' => $v['kind'], 'order_id' => $v['order']->get_id(), 'order_item_id' => $v['item']->get_id(),
			'product_id' => $v['product_id'], 'variation_id' => $v['variation_id'], 'reason_id' => $v['reason']['id'],
			'quantity' => $v['quantity'], 'attribution_scope' => 'item', 'mapping_state' => 'mapped',
			'occurred_at_gmt' => $v['date'], 'created_at_gmt' => $now, 'updated_at_gmt' => $now,
			'created_by' => get_current_user_id(), 'updated_by' => get_current_user_id(),
		) );
		if ( false === $ok ) { return new \WP_Error( 'event_write_failed', __( 'The insight could not be saved.', WPE_RI_TEXT_DOMAIN ), array( 'status' => 500 ) ); }
		return new \WP_REST_Response( self::serialize_event( self::find_event( (int) $wpdb->insert_id ) ), 201 );
	}
	public static function update_event( \WP_REST_Request $r ) {
		global $wpdb;
		$id = absint( $r['id'] ); $row = self::find_event( $id );
		if ( ! $row ) { return self::missing_event(); }
		if ( 'manual' !== $row['source_type'] ) { return new \WP_Error( 'immutable_source', __( 'Observed source records cannot be edited here.', WPE_RI_TEXT_DOMAIN ), array( 'status' => 409 ) ); }
		$d = $r->get_json_params() ?: array();
		$v = self::validate_event( array_merge( $d, array( 'order_id' => $row['order_id'], 'order_item_id' => $row['order_item_id'] ) ), $id );
		if ( is_wp_error( $v ) ) { return $v; }
		$wpdb->update( Installer::events_table(), array( 'event_kind' => $v['kind'], 'quantity' => $v['quantity'], 'reason_id' => $v['reason']['id'], 'occurred_at_gmt' => $v['date'], 'updated_at_gmt' => gmdate( 'Y-m-d H:i:s' ), 'updated_by' => get_current_user_id() ), array( 'event_id' => $id ) );
		return rest_ensure_response( self::serialize_event( self::find_event( $id ) ) );
	}
	public static function delete_event( \WP_REST_Request $r ) {
		global $wpdb;
		$id = absint( $r['id'] ); $row = self::find_event( $id );
		if ( ! $row ) { return self::missing_event(); }
		if ( 'manual' !== $row['source_type'] ) { return new \WP_Error( 'immutable_source', __( 'Observed source records cannot be deleted here.', WPE_RI_TEXT_DOMAIN ), array( 'status' => 409 ) ); }
		$note = sanitize_text_field( (string) ( $r->get_param( 'audit_reason' ) ?? '' ) );
		if ( '' === $note || strlen( $note ) > 200 ) { return new \WP_Error( 'audit_reason_required', __( 'Provide an audit reason up to 200 characters.', WPE_RI_TEXT_DOMAIN ), array( 'status' => 400 ) ); }
		$wpdb->update( Installer::events_table(), array( 'deleted_at_gmt' => gmdate( 'Y-m-d H:i:s' ), 'deletion_note' => $note, 'updated_at_gmt' => gmdate( 'Y-m-d H:i:s' ), 'updated_by' => get_current_user_id() ), array( 'event_id' => $id ) );
		return new \WP_REST_Response( null, 204 );
	}
	private static function validate_event( array $d, int $ignore_event_id = 0 ) {
		$kind = sanitize_key( $d['event_kind'] ?? '' );
		if ( ! in_array( $kind, array( 'confirmed_return', 'exchange' ), true ) ) { return new \WP_Error( 'invalid_event_kind', __( 'Manual insights support confirmed returns or exchanges.', WPE_RI_TEXT_DOMAIN ), array( 'status' => 400 ) ); }
		$reason = Reason_Catalog::find( sanitize_key( $d['reason_id'] ?? '' ) );
		$q = isset( $d['quantity'] ) ? (float) $d['quantity'] : 0;
		if ( ! $reason || $q <= 0 || $q > 999999 ) { return new \WP_Error( 'invalid_event', __( 'Choose an active reason and enter a positive quantity.', WPE_RI_TEXT_DOMAIN ), array( 'status' => 400 ) ); }
		$order = wc_get_order( absint( $d['order_id'] ?? 0 ) ); $item = $order ? $order->get_item( absint( $d['order_item_id'] ?? 0 ) ) : false;
		if ( ! $order || ! $item instanceof \WC_Order_Item_Product ) { return new \WP_Error( 'invalid_order_item', __( 'The selected item does not belong to this order.', WPE_RI_TEXT_DOMAIN ), array( 'status' => 400 ) ); }
		$p = $item->get_product();
		if ( ! $p ) { return new \WP_Error( 'product_missing', __( 'The product for this order item is unavailable.', WPE_RI_TEXT_DOMAIN ), array( 'status' => 400 ) ); }
		global $wpdb;
		$table = Installer::events_table();
		$sql = "SELECT COALESCE(SUM(quantity), 0) FROM {$table} WHERE order_item_id = %d AND event_kind IN ('confirmed_return','exchange') AND deleted_at_gmt IS NULL";
		$args = array( $item->get_id() );
		if ( $ignore_event_id ) { $sql .= ' AND event_id <> %d'; $args[] = $ignore_event_id; }
		$already_recorded = (float) $wpdb->get_var( $wpdb->prepare( $sql, $args ) );
		if ( $already_recorded + $q > (float) $item->get_quantity() ) {
			return new \WP_Error( 'quantity_exceeds_ordered', __( 'Insight quantity cannot exceed the remaining quantity on this order item.', WPE_RI_TEXT_DOMAIN ), array( 'status' => 400 ) );
		}
		$date = sanitize_text_field( $d['occurred_at'] ?? '' );
		if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date ) ) { return new \WP_Error( 'invalid_date', __( 'Use a date in YYYY-MM-DD format.', WPE_RI_TEXT_DOMAIN ), array( 'status' => 400 ) ); }
		$local = \DateTimeImmutable::createFromFormat( '!Y-m-d', $date, wp_timezone() );
		if ( ! $local || $local->format( 'Y-m-d' ) !== $date ) { return new \WP_Error( 'invalid_date', __( 'Enter a valid calendar date.', WPE_RI_TEXT_DOMAIN ), array( 'status' => 400 ) ); }
		return array( 'kind' => $kind, 'reason' => $reason, 'quantity' => wc_format_decimal( $q, 4 ), 'order' => $order, 'item' => $item, 'product_id' => $p->get_parent_id() ?: $p->get_id(), 'variation_id' => $p->get_parent_id() ? $p->get_id() : 0, 'date' => $local->setTimezone( new \DateTimeZone( 'UTC' ) )->format( 'Y-m-d H:i:s' ) );
	}
	private static function find_event( int $id ) {
		global $wpdb; $table = Installer::events_table();
		return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE event_id = %d", $id ), ARRAY_A );
	}
	private static function serialize_event( array $row ): array {
		$reason = Reason_Catalog::find( $row['reason_id'], true );
		return array( 'id' => (int) $row['event_id'], 'event_kind' => $row['event_kind'], 'source_type' => $row['source_type'], 'order_id' => (int) $row['order_id'], 'refund_id' => (int) ( $row['refund_id'] ?? 0 ), 'order_item_id' => (int) $row['order_item_id'], 'attribution_scope' => $row['attribution_scope'], 'mapping_state' => $row['mapping_state'], 'product_id' => (int) $row['product_id'], 'variation_id' => (int) $row['variation_id'], 'reason_id' => $row['reason_id'], 'reason_label' => $reason ? $reason['label'] : __( 'Archived reason', WPE_RI_TEXT_DOMAIN ), 'quantity' => (float) $row['quantity'], 'occurred_at' => gmdate( 'c', strtotime( $row['occurred_at_gmt'] . ' UTC' ) ), 'created_at' => gmdate( 'c', strtotime( $row['created_at_gmt'] . ' UTC' ) ), 'updated_at' => gmdate( 'c', strtotime( $row['updated_at_gmt'] . ' UTC' ) ), 'occurred_date' => get_date_from_gmt( $row['occurred_at_gmt'], 'Y-m-d' ) );
	}
	private static function missing_event(): \WP_Error { return new \WP_Error( 'event_not_found', __( 'Insight event not found.', WPE_RI_TEXT_DOMAIN ), array( 'status' => 404 ) ); }
}
 ), 'idempotency_key' => array( 'type' => 'string', 'required' => true, 'minLength' => 16, 'maxLength' => 100 ) ) ),
		) );
		register_rest_route( self::NS, '/events/(?P<id>\d+)', array(
			array( 'methods' => 'GET', 'callback' => array( self::class, 'get_event' ), 'permission_callback' => array( self::class, 'can_view' ) ),
			array( 'methods' => 'PATCH', 'callback' => array( self::class, 'update_event' ), 'permission_callback' => array( self::class, 'can_manage' ) ),
			array( 'methods' => 'DELETE', 'callback' => array( self::class, 'delete_event' ), 'permission_callback' => array( self::class, 'can_manage' ) ),
		) );
	}
	public static function can_view(): bool { return current_user_can( Capabilities::VIEW ); }
	public static function can_manage(): bool { return current_user_can( Capabilities::MANAGE ); }
	public static function get_reasons( \WP_REST_Request $request ): \WP_REST_Response {
		$include_inactive = rest_sanitize_boolean( $request->get_param( 'include_inactive' ) ) && self::can_manage();
		return rest_ensure_response( Reason_Catalog::all( $include_inactive ) );
	}
	public static function get_reason( \WP_REST_Request $r ) {
		$x = Reason_Catalog::find( (string) $r['id'], true );
		return $x ? rest_ensure_response( $x ) : new \WP_Error( 'reason_not_found', __( 'Reason not found.', WPE_RI_TEXT_DOMAIN ), array( 'status' => 404 ) );
	}
	public static function create_reason( \WP_REST_Request $r ) {
		$x = Reason_Catalog::save( $r->get_json_params() ?: array() );
		return is_wp_error( $x ) ? $x : new \WP_REST_Response( $x, 201 );
	}
	public static function update_reason( \WP_REST_Request $r ) {
		$x = Reason_Catalog::save( $r->get_json_params() ?: array(), (string) $r['id'] );
		return is_wp_error( $x ) ? $x : rest_ensure_response( $x );
	}
	public static function get_order_items( \WP_REST_Request $r ) {
		$order = wc_get_order( absint( $r['id'] ) );
		if ( ! $order ) { return new \WP_Error( 'order_not_found', __( 'Order not found.', WPE_RI_TEXT_DOMAIN ), array( 'status' => 404 ) ); }
		$items = array();
		foreach ( $order->get_items( 'line_item' ) as $item ) {
			if ( ! $item instanceof \WC_Order_Item_Product || ! $item->get_product() ) { continue; }
			$p = $item->get_product();
			$items[] = array( 'order_item_id' => $item->get_id(), 'product_id' => $p->get_parent_id() ?: $p->get_id(), 'variation_id' => $p->get_parent_id() ? $p->get_id() : 0, 'name' => $item->get_name(), 'ordered_quantity' => (float) $item->get_quantity() );
		}
		return rest_ensure_response( array( 'order_id' => $order->get_id(), 'order_number' => $order->get_order_number(), 'items' => $items ) );
	}
	public static function get_sources(): \WP_REST_Response {
		global $wpdb;
		$table = Installer::events_table();
		$rows = $wpdb->get_results( "SELECT source_type, COUNT(*) AS event_count, MAX(occurred_at_gmt) AS latest_event_at FROM {$table} WHERE deleted_at_gmt IS NULL GROUP BY source_type ORDER BY source_type ASC", ARRAY_A );
		$sources = array_map(
			static function ( array $row ): array {
				return array(
					'source_type' => $row['source_type'],
					'event_count' => (int) $row['event_count'],
					'latest_event_at' => $row['latest_event_at'] ? gmdate( 'c', strtotime( $row['latest_event_at'] . ' UTC' ) ) : null,
					'latest_event_date' => $row['latest_event_at'] ? get_date_from_gmt( $row['latest_event_at'], 'Y-m-d' ) : null,
				);
			},
			$rows ?: array()
		);
		return rest_ensure_response( array( 'sources' => $sources ) );
	}

	public static function get_events( \WP_REST_Request $r ) {
		global $wpdb;
		$page = max( 1, absint( $r->get_param( 'page' ) ) );
		$per = min( 100, max( 1, absint( $r->get_param( 'per_page' ) ?: 20 ) ) );
		$order_id = absint( $r->get_param( 'order_id' ) );
		$table = Installer::events_table(); $where = 'deleted_at_gmt IS NULL'; $args = array();
		if ( $order_id ) { $where .= ' AND order_id = %d'; $args[] = $order_id; }
		$total = (int) $wpdb->get_var( $args ? $wpdb->prepare( "SELECT COUNT(*) FROM {$table} WHERE {$where}", $args ) : "SELECT COUNT(*) FROM {$table} WHERE {$where}" );
		$list_args = array_merge( $args, array( $per, ( $page - 1 ) * $per ) );
		$rows = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$table} WHERE {$where} ORDER BY occurred_at_gmt DESC, event_id DESC LIMIT %d OFFSET %d", $list_args ), ARRAY_A );
		$res = rest_ensure_response( array_map( array( self::class, 'serialize_event' ), $rows ?: array() ) );
		$res->header( 'X-WP-Total', (string) $total ); $res->header( 'X-WP-TotalPages', (string) ceil( $total / $per ) );
		return $res;
	}
	public static function get_event( \WP_REST_Request $r ) {
		$row = self::find_event( absint( $r['id'] ) );
		return $row ? rest_ensure_response( self::serialize_event( $row ) ) : self::missing_event();
	}
	public static function create_event( \WP_REST_Request $r ) {
		global $wpdb;
		$d = $r->get_json_params() ?: array(); $v = self::validate_event( $d );
		if ( is_wp_error( $v ) ) { return $v; }
		$key = sanitize_text_field( $d['idempotency_key'] ?? '' );
		if ( ! preg_match( '/^[A-Za-z0-9_-]{16,100}$/', $key ) ) { return new \WP_Error( 'invalid_idempotency_key', __( 'A 16–100 character idempotency key is required.', WPE_RI_TEXT_DOMAIN ), array( 'status' => 400 ) ); }
		$hash = hash( 'sha256', 'manual|' . get_current_blog_id() . '|' . $key ); $table = Installer::events_table();
		$existing = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE source_key = %s", $hash ), ARRAY_A );
		if ( $existing ) { return new \WP_REST_Response( self::serialize_event( $existing ), 200 ); }
		$now = gmdate( 'Y-m-d H:i:s' );
		$ok = $wpdb->insert( $table, array(
			'event_uuid' => wp_generate_uuid4(), 'source_key' => $hash, 'source_type' => 'manual', 'source_record_id' => $key,
			'event_kind' => $v['kind'], 'order_id' => $v['order']->get_id(), 'order_item_id' => $v['item']->get_id(),
			'product_id' => $v['product_id'], 'variation_id' => $v['variation_id'], 'reason_id' => $v['reason']['id'],
			'quantity' => $v['quantity'], 'attribution_scope' => 'item', 'mapping_state' => 'mapped',
			'occurred_at_gmt' => $v['date'], 'created_at_gmt' => $now, 'updated_at_gmt' => $now,
			'created_by' => get_current_user_id(), 'updated_by' => get_current_user_id(),
		) );
		if ( false === $ok ) { return new \WP_Error( 'event_write_failed', __( 'The insight could not be saved.', WPE_RI_TEXT_DOMAIN ), array( 'status' => 500 ) ); }
		return new \WP_REST_Response( self::serialize_event( self::find_event( (int) $wpdb->insert_id ) ), 201 );
	}
	public static function update_event( \WP_REST_Request $r ) {
		global $wpdb;
		$id = absint( $r['id'] ); $row = self::find_event( $id );
		if ( ! $row ) { return self::missing_event(); }
		if ( 'manual' !== $row['source_type'] ) { return new \WP_Error( 'immutable_source', __( 'Observed source records cannot be edited here.', WPE_RI_TEXT_DOMAIN ), array( 'status' => 409 ) ); }
		$d = $r->get_json_params() ?: array();
		$v = self::validate_event( array_merge( $d, array( 'order_id' => $row['order_id'], 'order_item_id' => $row['order_item_id'] ) ), $id );
		if ( is_wp_error( $v ) ) { return $v; }
		$wpdb->update( Installer::events_table(), array( 'event_kind' => $v['kind'], 'quantity' => $v['quantity'], 'reason_id' => $v['reason']['id'], 'occurred_at_gmt' => $v['date'], 'updated_at_gmt' => gmdate( 'Y-m-d H:i:s' ), 'updated_by' => get_current_user_id() ), array( 'event_id' => $id ) );
		return rest_ensure_response( self::serialize_event( self::find_event( $id ) ) );
	}
	public static function delete_event( \WP_REST_Request $r ) {
		global $wpdb;
		$id = absint( $r['id'] ); $row = self::find_event( $id );
		if ( ! $row ) { return self::missing_event(); }
		if ( 'manual' !== $row['source_type'] ) { return new \WP_Error( 'immutable_source', __( 'Observed source records cannot be deleted here.', WPE_RI_TEXT_DOMAIN ), array( 'status' => 409 ) ); }
		$note = sanitize_text_field( (string) ( $r->get_param( 'audit_reason' ) ?? '' ) );
		if ( '' === $note || strlen( $note ) > 200 ) { return new \WP_Error( 'audit_reason_required', __( 'Provide an audit reason up to 200 characters.', WPE_RI_TEXT_DOMAIN ), array( 'status' => 400 ) ); }
		$wpdb->update( Installer::events_table(), array( 'deleted_at_gmt' => gmdate( 'Y-m-d H:i:s' ), 'deletion_note' => $note, 'updated_at_gmt' => gmdate( 'Y-m-d H:i:s' ), 'updated_by' => get_current_user_id() ), array( 'event_id' => $id ) );
		return new \WP_REST_Response( null, 204 );
	}
	private static function validate_event( array $d, int $ignore_event_id = 0 ) {
		$kind = sanitize_key( $d['event_kind'] ?? '' );
		if ( ! in_array( $kind, array( 'confirmed_return', 'exchange' ), true ) ) { return new \WP_Error( 'invalid_event_kind', __( 'Manual insights support confirmed returns or exchanges.', WPE_RI_TEXT_DOMAIN ), array( 'status' => 400 ) ); }
		$reason = Reason_Catalog::find( sanitize_key( $d['reason_id'] ?? '' ) );
		$q = isset( $d['quantity'] ) ? (float) $d['quantity'] : 0;
		if ( ! $reason || $q <= 0 || $q > 999999 ) { return new \WP_Error( 'invalid_event', __( 'Choose an active reason and enter a positive quantity.', WPE_RI_TEXT_DOMAIN ), array( 'status' => 400 ) ); }
		$order = wc_get_order( absint( $d['order_id'] ?? 0 ) ); $item = $order ? $order->get_item( absint( $d['order_item_id'] ?? 0 ) ) : false;
		if ( ! $order || ! $item instanceof \WC_Order_Item_Product ) { return new \WP_Error( 'invalid_order_item', __( 'The selected item does not belong to this order.', WPE_RI_TEXT_DOMAIN ), array( 'status' => 400 ) ); }
		$p = $item->get_product();
		if ( ! $p ) { return new \WP_Error( 'product_missing', __( 'The product for this order item is unavailable.', WPE_RI_TEXT_DOMAIN ), array( 'status' => 400 ) ); }
		global $wpdb;
		$table = Installer::events_table();
		$sql = "SELECT COALESCE(SUM(quantity), 0) FROM {$table} WHERE order_item_id = %d AND event_kind IN ('confirmed_return','exchange') AND deleted_at_gmt IS NULL";
		$args = array( $item->get_id() );
		if ( $ignore_event_id ) { $sql .= ' AND event_id <> %d'; $args[] = $ignore_event_id; }
		$already_recorded = (float) $wpdb->get_var( $wpdb->prepare( $sql, $args ) );
		if ( $already_recorded + $q > (float) $item->get_quantity() ) {
			return new \WP_Error( 'quantity_exceeds_ordered', __( 'Insight quantity cannot exceed the remaining quantity on this order item.', WPE_RI_TEXT_DOMAIN ), array( 'status' => 400 ) );
		}
		$date = sanitize_text_field( $d['occurred_at'] ?? '' );
		if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date ) ) { return new \WP_Error( 'invalid_date', __( 'Use a date in YYYY-MM-DD format.', WPE_RI_TEXT_DOMAIN ), array( 'status' => 400 ) ); }
		$local = \DateTimeImmutable::createFromFormat( '!Y-m-d', $date, wp_timezone() );
		if ( ! $local || $local->format( 'Y-m-d' ) !== $date ) { return new \WP_Error( 'invalid_date', __( 'Enter a valid calendar date.', WPE_RI_TEXT_DOMAIN ), array( 'status' => 400 ) ); }
		return array( 'kind' => $kind, 'reason' => $reason, 'quantity' => wc_format_decimal( $q, 4 ), 'order' => $order, 'item' => $item, 'product_id' => $p->get_parent_id() ?: $p->get_id(), 'variation_id' => $p->get_parent_id() ? $p->get_id() : 0, 'date' => $local->setTimezone( new \DateTimeZone( 'UTC' ) )->format( 'Y-m-d H:i:s' ) );
	}
	private static function find_event( int $id ) {
		global $wpdb; $table = Installer::events_table();
		return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE event_id = %d", $id ), ARRAY_A );
	}
	private static function serialize_event( array $row ): array {
		$reason = Reason_Catalog::find( $row['reason_id'], true );
		return array( 'id' => (int) $row['event_id'], 'event_kind' => $row['event_kind'], 'source_type' => $row['source_type'], 'order_id' => (int) $row['order_id'], 'refund_id' => (int) ( $row['refund_id'] ?? 0 ), 'order_item_id' => (int) $row['order_item_id'], 'attribution_scope' => $row['attribution_scope'], 'mapping_state' => $row['mapping_state'], 'product_id' => (int) $row['product_id'], 'variation_id' => (int) $row['variation_id'], 'reason_id' => $row['reason_id'], 'reason_label' => $reason ? $reason['label'] : __( 'Archived reason', WPE_RI_TEXT_DOMAIN ), 'quantity' => (float) $row['quantity'], 'occurred_at' => gmdate( 'c', strtotime( $row['occurred_at_gmt'] . ' UTC' ) ), 'created_at' => gmdate( 'c', strtotime( $row['created_at_gmt'] . ' UTC' ) ), 'updated_at' => gmdate( 'c', strtotime( $row['updated_at_gmt'] . ' UTC' ) ), 'occurred_date' => get_date_from_gmt( $row['occurred_at_gmt'], 'Y-m-d' ) );
	}
	private static function missing_event(): \WP_Error { return new \WP_Error( 'event_not_found', __( 'Insight event not found.', WPE_RI_TEXT_DOMAIN ), array( 'status' => 404 ) ); }
}
