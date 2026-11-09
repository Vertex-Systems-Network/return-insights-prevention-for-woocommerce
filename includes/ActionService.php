<?php
/**
 * Pro corrective-action records, status audit, and evidence snapshots.
 *
 * @package WPEssentialReturnInsights
 */
namespace WPEssential\ReturnInsights;
defined( 'ABSPATH' ) || exit;

/** Persists bounded actions separately from source events and WooCommerce data. */
final class ActionService {
	private const MAX_ACTIONS = 1000;
	private const MAX_TARGETS = 10;
	private const MAX_HISTORY = 50;
	private const MAX_EVIDENCE = 20;
	private const STATUSES = array( 'proposed', 'in_progress', 'monitoring', 'completed', 'paused' );

	public static function table(): string {
		global $wpdb;
		return $wpdb->prefix . 'wpessential_return_insights_actions';
	}

	public static function targets_table(): string {
		global $wpdb;
		return $wpdb->prefix . 'wpessential_return_insights_action_targets';
	}

	public static function history_table(): string {
		global $wpdb;
		return $wpdb->prefix . 'wpessential_return_insights_action_history';
	}

	public static function evidence_table(): string {
		global $wpdb;
		return $wpdb->prefix . 'wpessential_return_insights_action_evidence';
	}

	public static function all() {
		global $wpdb;
		$rows = $wpdb->get_results( 'SELECT * FROM ' . self::table() . ' ORDER BY updated_at_gmt DESC, action_id DESC LIMIT ' . self::MAX_ACTIONS, ARRAY_A );
		if ( ! empty( $wpdb->last_error ) ) { return new \WP_Error( 'actions_query_failed', __( 'Corrective actions could not be loaded.', WPE_RI_TEXT_DOMAIN ), array( 'status' => 500 ) ); }
		return self::hydrate_many( is_array( $rows ) ? $rows : array() );
	}

	public static function get( int $id ) {
		global $wpdb;
		$row = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . self::table() . ' WHERE action_id = %d', $id ), ARRAY_A );
		if ( ! empty( $wpdb->last_error ) ) { return new \WP_Error( 'action_query_failed', __( 'Corrective action could not be loaded.', WPE_RI_TEXT_DOMAIN ), array( 'status' => 500 ) ); }
		if ( ! $row ) {
			return new \WP_Error( 'action_not_found', __( 'Corrective action not found.', WPE_RI_TEXT_DOMAIN ), array( 'status' => 404 ) );
		}
		return self::hydrate( $row );
	}

	public static function create( array $data ) {
		global $wpdb;
		$count = self::count_actions();
		if ( is_wp_error( $count ) ) { return $count; }
		if ( $count >= self::MAX_ACTIONS ) {
			return new \WP_Error( 'action_limit_reached', __( 'The action storage limit has been reached.', WPE_RI_TEXT_DOMAIN ), array( 'status' => 409 ) );
		}
		$valid = self::validate_fields( $data, true );
		if ( is_wp_error( $valid ) ) { return $valid; }
		$targets = self::validate_targets( $data['targets'] ?? array() );
		if ( is_wp_error( $targets ) ) { return $targets; }

		if ( false === $wpdb->query( 'START TRANSACTION' ) ) { return new \WP_Error( 'action_transaction_failed', __( 'The corrective action could not be started safely.', WPE_RI_TEXT_DOMAIN ), array( 'status' => 500 ) ); }
		$now = gmdate( 'Y-m-d H:i:s' );
		$ok = $wpdb->insert( self::table(), array(
			'action_uuid' => wp_generate_uuid4(),
			'title' => sanitize_text_field( $data['title'] ),
			'hypothesis' => sanitize_textarea_field( $data['hypothesis'] ),
			'issue_url' => esc_url_raw( $data['issue_url'] ?? '' ),
			'owner_id' => absint( $data['owner_id'] ?? 0 ),
			'status' => 'proposed',
			'measurement_days' => absint( $data['measurement_days'] ?? 90 ),
			'planned_review_date' => $data['planned_review_date'] ?? null,
			'created_at_gmt' => $now,
			'updated_at_gmt' => $now,
			'created_by' => get_current_user_id(),
			'updated_by' => get_current_user_id(),
		), array( '%s', '%s', '%s', '%s', '%d', '%s', '%d', '%s', '%s', '%s', '%d', '%d' ) );
		if ( false === $ok ) {
			$wpdb->query( 'ROLLBACK' );
			return new \WP_Error( 'action_create_failed', __( 'The corrective action could not be saved.', WPE_RI_TEXT_DOMAIN ), array( 'status' => 500 ) );
		}
		$id = (int) $wpdb->insert_id;
		foreach ( $targets as $target ) {
			$inserted = $wpdb->insert( self::targets_table(), array_merge( array( 'action_id' => $id ), $target ), array( '%d', '%d', '%d', '%s' ) );
			if ( false === $inserted ) {
				$wpdb->query( 'ROLLBACK' );
				return new \WP_Error( 'action_target_failed', __( 'One or more action targets could not be saved.', WPE_RI_TEXT_DOMAIN ), array( 'status' => 500 ) );
			}
		}
		if ( ! self::record_status( $id, '', 'proposed', __( 'Action created.', WPE_RI_TEXT_DOMAIN ) ) ) {
			$wpdb->query( 'ROLLBACK' );
			return new \WP_Error( 'action_audit_failed', __( 'The action could not be saved to its audit history.', WPE_RI_TEXT_DOMAIN ), array( 'status' => 500 ) );
		}
		if ( false === $wpdb->query( 'COMMIT' ) ) {
			$wpdb->query( 'ROLLBACK' );
			return new \WP_Error( 'action_commit_failed', __( 'The corrective action could not be committed. Refresh before retrying.', WPE_RI_TEXT_DOMAIN ), array( 'status' => 500 ) );
		}
		return self::get( $id );
	}

	public static function update( int $id, array $data ) {
		global $wpdb;
		$current = self::get( $id );
		if ( is_wp_error( $current ) ) { return $current; }
		$valid = self::validate_fields( $data, false );
		if ( is_wp_error( $valid ) ) { return $valid; }
		$updates = array();
		$formats = array();
		foreach ( array( 'title', 'hypothesis', 'issue_url', 'owner_id', 'measurement_days', 'planned_review_date' ) as $field ) {
			if ( ! array_key_exists( $field, $data ) ) { continue; }
			$value = $data[ $field ];
			if ( 'title' === $field ) { $value = sanitize_text_field( $value ); $format = '%s'; }
			elseif ( 'hypothesis' === $field ) { $value = sanitize_textarea_field( $value ); $format = '%s'; }
			elseif ( 'issue_url' === $field ) { $value = esc_url_raw( $value ); $format = '%s'; }
			elseif ( 'owner_id' === $field || 'measurement_days' === $field ) { $value = absint( $value ); $format = '%d'; }
			else { $value = '' === $value ? null : $value; $format = '%s'; }
			$updates[ $field ] = $value;
			$formats[] = $format;
		}
		$replace_targets = isset( $data['targets'] );
		$targets = array();
		if ( $replace_targets ) {
			$targets = self::validate_targets( $data['targets'] );
			if ( is_wp_error( $targets ) ) { return $targets; }
		}
		if ( false === $wpdb->query( 'START TRANSACTION' ) ) { return new \WP_Error( 'action_transaction_failed', __( 'The corrective action could not be started safely.', WPE_RI_TEXT_DOMAIN ), array( 'status' => 500 ) ); }
		$locked = $wpdb->get_row( $wpdb->prepare( 'SELECT action_id FROM ' . self::table() . ' WHERE action_id = %d FOR UPDATE', $id ), ARRAY_A );
		if ( ! empty( $wpdb->last_error ) ) {
			$wpdb->query( 'ROLLBACK' );
			return new \WP_Error( 'action_lock_failed', __( 'The corrective action could not be locked for update.', WPE_RI_TEXT_DOMAIN ), array( 'status' => 500 ) );
		}
		if ( ! $locked ) {
			$wpdb->query( 'ROLLBACK' );
			return new \WP_Error( 'action_not_found', __( 'Corrective action not found.', WPE_RI_TEXT_DOMAIN ), array( 'status' => 404 ) );
		}
		$current = self::get( $id );
		if ( is_wp_error( $current ) ) {
			$wpdb->query( 'ROLLBACK' );
			return $current;
		}
		$status_changed = isset( $data['status'] ) && $data['status'] !== $current['status'];
		if ( $status_changed ) { $updates['status'] = $data['status']; $formats[] = '%s'; }
		if ( $updates || $replace_targets ) {
			$updates['updated_at_gmt'] = gmdate( 'Y-m-d H:i:s' );
			$updates['updated_by'] = get_current_user_id();
			$formats[] = '%s'; $formats[] = '%d';
			if ( false === $wpdb->update( self::table(), $updates, array( 'action_id' => $id ), $formats, array( '%d' ) ) ) {
				$wpdb->query( 'ROLLBACK' );
				return new \WP_Error( 'action_update_failed', __( 'The corrective action could not be updated.', WPE_RI_TEXT_DOMAIN ), array( 'status' => 500 ) );
			}
		}
		if ( $replace_targets ) {
			$deleted = $wpdb->delete( self::targets_table(), array( 'action_id' => $id ), array( '%d' ) );
			if ( false === $deleted ) {
				$wpdb->query( 'ROLLBACK' );
				return new \WP_Error( 'action_target_failed', __( 'Existing action targets could not be replaced.', WPE_RI_TEXT_DOMAIN ), array( 'status' => 500 ) );
			}
			foreach ( $targets as $target ) {
				if ( false === $wpdb->insert( self::targets_table(), array_merge( array( 'action_id' => $id ), $target ), array( '%d', '%d', '%d', '%s' ) ) ) {
					$wpdb->query( 'ROLLBACK' );
					return new \WP_Error( 'action_target_failed', __( 'Action targets could not be updated.', WPE_RI_TEXT_DOMAIN ), array( 'status' => 500 ) );
				}
			}
		}
		if ( $status_changed && ! self::record_status( $id, $current['status'], $data['status'], $data['audit_note'] ?? '' ) ) {
			$wpdb->query( 'ROLLBACK' );
			return new \WP_Error( 'action_audit_failed', __( 'The status change could not be audited, so it was reverted.', WPE_RI_TEXT_DOMAIN ), array( 'status' => 500 ) );
		}
		if ( false === $wpdb->query( 'COMMIT' ) ) {
			$wpdb->query( 'ROLLBACK' );
			return new \WP_Error( 'action_commit_failed', __( 'The corrective action could not be committed. Refresh before retrying.', WPE_RI_TEXT_DOMAIN ), array( 'status' => 500 ) );
		}
		return self::get( $id );
	}

	public static function capture_evidence( int $id, string $phase ) {
		global $wpdb;
		$action = self::get( $id );
		if ( is_wp_error( $action ) ) { return $action; }
		if ( ! in_array( $phase, array( 'baseline', 'followup' ), true ) ) { return new \WP_Error( 'invalid_action_evidence_phase', __( 'Choose baseline or follow-up evidence.', WPE_RI_TEXT_DOMAIN ), array( 'status' => 400 ) ); }
		$report = PatternService::report( 90, (int) $action['measurement_days'], true );
		if ( is_wp_error( $report ) ) { return $report; }
		$targets = $action['targets'];
		$has_baseline = false; $has_followup = false;
		foreach ( $action['evidence'] as $snapshot ) { if ( 'baseline' === $snapshot['phase'] ) { $has_baseline = true; } if ( 'followup' === $snapshot['phase'] ) { $has_followup = true; } }
		if ( 'followup' === $phase && ! $has_baseline ) { return new \WP_Error( 'action_baseline_required', __( 'Capture baseline evidence before follow-up evidence.', WPE_RI_TEXT_DOMAIN ), array( 'status' => 409 ) ); }
		if ( 'baseline' === $phase && ( $has_baseline || $has_followup ) ) { return new \WP_Error( 'action_baseline_exists', __( 'Baseline evidence is already recorded for this action.', WPE_RI_TEXT_DOMAIN ), array( 'status' => 409 ) ); }
		if ( 'followup' === $phase ) {
			$baseline = null;
			foreach ( $action['evidence'] as $snapshot ) { if ( 'baseline' === $snapshot['phase'] ) { $baseline = $snapshot; break; } }
			if ( $baseline && $report['periods']['current']['date_from'] <= $baseline['period_to'] ) { return new \WP_Error( 'action_followup_window_not_ready', __( 'The follow-up cohort must start after the baseline cohort ends. Wait for the selected comparison window to advance.', WPE_RI_TEXT_DOMAIN ), array( 'status' => 409 ) ); }
		}
		$matched = array();
		foreach ( $report['patterns'] as $pattern ) {
			foreach ( $targets as $target ) {
				if ( $target['product_id'] && (int) $target['product_id'] !== (int) $pattern['product_id'] ) { continue; }
				if ( $target['variation_id'] && (int) $target['variation_id'] !== (int) $pattern['variation_id'] ) { continue; }
				if ( $target['reason_id'] && $target['reason_id'] !== $pattern['reason_id'] ) { continue; }
				$matched[] = $pattern;
				break;
			}
		}
		if ( ! $matched ) {
			return new \WP_Error( 'action_evidence_unavailable', __( 'No pattern rows match this action’s targets in the current evidence window.', WPE_RI_TEXT_DOMAIN ), array( 'status' => 422 ) );
		}
		$sold_by_product = array(); $returned = 0.0; $available = true;
		foreach ( $matched as $pattern ) {
			$cohort = $pattern['current'];
			$product_key = (int) $pattern['product_id'] . ':' . (int) $pattern['variation_id'];
			$sold_by_product[ $product_key ] = (float) $cohort['sold_quantity'];
			$returned += (float) $cohort['returned_quantity'];
			$available = $available && 'available' === $cohort['rate_status'];
		}
		$sold = array_sum( $sold_by_product );
		$coverage = $report['coverage']['current'];
		$rate = $available && $sold > 0 && $returned <= $sold ? round( 100 * $returned / $sold, 2 ) : null;
		$row = array(
			'action_id' => $id,
			'phase' => $phase,
			'metric_definition_version' => $report['metric_definition_version'],
			'period_from' => $report['periods']['current']['date_from'],
			'period_to' => $report['periods']['current']['date_to'],
			'returned_quantity' => round( $returned, 4 ),
			'sold_quantity' => round( $sold, 4 ),
			'rate_percent' => $rate,
			'mapping_percent' => $coverage['return_mapping_percent'] ?? null,
			'eligible_orders' => (int) ( $coverage['eligible_order_count'] ?? 0 ),
			'captured_at_gmt' => gmdate( 'Y-m-d H:i:s' ),
			'captured_by' => get_current_user_id(),
		);
		if ( false === $wpdb->query( 'START TRANSACTION' ) ) { return new \WP_Error( 'action_evidence_transaction_failed', __( 'Evidence could not be captured safely.', WPE_RI_TEXT_DOMAIN ), array( 'status' => 500 ) ); }
		$locked = $wpdb->get_row( $wpdb->prepare( 'SELECT action_id FROM ' . self::table() . ' WHERE action_id = %d FOR UPDATE', $id ), ARRAY_A );
		if ( ! empty( $wpdb->last_error ) ) {
			$wpdb->query( 'ROLLBACK' );
			return new \WP_Error( 'action_evidence_lock_failed', __( 'The action could not be locked while capturing evidence.', WPE_RI_TEXT_DOMAIN ), array( 'status' => 500 ) );
		}
		if ( ! $locked ) {
			$wpdb->query( 'ROLLBACK' );
			return new \WP_Error( 'action_not_found', __( 'Corrective action not found.', WPE_RI_TEXT_DOMAIN ), array( 'status' => 404 ) );
		}
		$locked_action = self::get( $id );
		if ( is_wp_error( $locked_action ) ) {
			$wpdb->query( 'ROLLBACK' );
			return $locked_action;
		}
		if ( (int) $locked_action['measurement_days'] !== (int) $action['measurement_days'] || wp_json_encode( $locked_action['targets'] ) !== wp_json_encode( $action['targets'] ) ) {
			$wpdb->query( 'ROLLBACK' );
			return new \WP_Error( 'action_changed_during_evidence', __( 'The action settings changed while evidence was being prepared. Refresh and try again.', WPE_RI_TEXT_DOMAIN ), array( 'status' => 409 ) );
		}
		$has_baseline = false; $has_followup = false; $locked_baseline = null;
		foreach ( $locked_action['evidence'] as $snapshot ) {
			if ( 'baseline' === $snapshot['phase'] ) { $has_baseline = true; $locked_baseline = $snapshot; }
			if ( 'followup' === $snapshot['phase'] ) { $has_followup = true; }
		}
		if ( 'followup' === $phase && ! $has_baseline ) {
			$wpdb->query( 'ROLLBACK' );
			return new \WP_Error( 'action_baseline_required', __( 'Capture baseline evidence before follow-up evidence.', WPE_RI_TEXT_DOMAIN ), array( 'status' => 409 ) );
		}
		if ( 'baseline' === $phase && ( $has_baseline || $has_followup ) ) {
			$wpdb->query( 'ROLLBACK' );
			return new \WP_Error( 'action_baseline_exists', __( 'Baseline evidence is already recorded for this action.', WPE_RI_TEXT_DOMAIN ), array( 'status' => 409 ) );
		}
		if ( 'followup' === $phase && $locked_baseline && $report['periods']['current']['date_from'] <= $locked_baseline['period_to'] ) {
			$wpdb->query( 'ROLLBACK' );
			return new \WP_Error( 'action_followup_window_not_ready', __( 'The follow-up cohort must start after the baseline cohort ends. Wait for the selected comparison window to advance.', WPE_RI_TEXT_DOMAIN ), array( 'status' => 409 ) );
		}
		$inserted = $wpdb->insert( self::evidence_table(), $row, array( '%d', '%s', '%s', '%s', '%s', '%f', '%f', '%f', '%f', '%d', '%s', '%d' ) );
		if ( false === $inserted ) {
			$wpdb->query( 'ROLLBACK' );
			return new \WP_Error( 'action_evidence_failed', __( 'The evidence snapshot could not be saved.', WPE_RI_TEXT_DOMAIN ), array( 'status' => 500 ) );
		}
		$baseline_id = $wpdb->get_var( $wpdb->prepare( 'SELECT evidence_id FROM ' . self::evidence_table() . ' WHERE action_id = %d AND phase = %s ORDER BY evidence_id ASC LIMIT 1', $id, 'baseline' ) );
		if ( ! empty( $wpdb->last_error ) ) { $wpdb->query( 'ROLLBACK' ); return new \WP_Error( 'action_evidence_retention_failed', __( 'Evidence retention could not be completed.', WPE_RI_TEXT_DOMAIN ), array( 'status' => 500 ) ); }
		$trim_id = $wpdb->get_var( $wpdb->prepare( 'SELECT evidence_id FROM ' . self::evidence_table() . ' WHERE action_id = %d ORDER BY evidence_id DESC LIMIT 1 OFFSET %d', $id, self::MAX_EVIDENCE - 1 ) );
		if ( ! empty( $wpdb->last_error ) ) { $wpdb->query( 'ROLLBACK' ); return new \WP_Error( 'action_evidence_retention_failed', __( 'Evidence retention could not be completed.', WPE_RI_TEXT_DOMAIN ), array( 'status' => 500 ) ); }
		if ( $trim_id ) {
			$delete_sql = 'DELETE FROM ' . self::evidence_table() . ' WHERE action_id = %d AND evidence_id <= %d';
			$args = array( $id, absint( $trim_id ) );
			if ( $baseline_id ) { $delete_sql .= ' AND evidence_id <> %d'; $args[] = absint( $baseline_id ); }
			if ( false === $wpdb->query( $wpdb->prepare( $delete_sql, $args ) ) ) { $wpdb->query( 'ROLLBACK' ); return new \WP_Error( 'action_evidence_retention_failed', __( 'Evidence retention could not be completed.', WPE_RI_TEXT_DOMAIN ), array( 'status' => 500 ) ); }
		}
		if ( false === $wpdb->query( 'COMMIT' ) ) {
			$wpdb->query( 'ROLLBACK' );
			return new \WP_Error( 'action_evidence_commit_failed', __( 'The evidence snapshot could not be committed. Refresh before retrying.', WPE_RI_TEXT_DOMAIN ), array( 'status' => 500 ) );
		}
		return self::get( $id );
	}

	private static function validate_fields( array $data, bool $creating ) {
		if ( ( $creating || array_key_exists( 'title', $data ) ) && isset( $data['title'] ) && ! is_string( $data['title'] ) ) { return new \WP_Error( 'invalid_action_title', __( 'Action title must be text.', WPE_RI_TEXT_DOMAIN ), array( 'status' => 400 ) ); }
		if ( $creating || array_key_exists( 'title', $data ) ) {
			$title = trim( (string) ( $data['title'] ?? '' ) );
			if ( '' === $title || strlen( $title ) > 160 ) { return new \WP_Error( 'invalid_action_title', __( 'Enter an action title up to 160 characters.', WPE_RI_TEXT_DOMAIN ), array( 'status' => 400 ) ); }
		}
		if ( ( $creating || array_key_exists( 'hypothesis', $data ) ) && isset( $data['hypothesis'] ) && ! is_string( $data['hypothesis'] ) ) { return new \WP_Error( 'invalid_action_hypothesis', __( 'Change hypothesis must be text.', WPE_RI_TEXT_DOMAIN ), array( 'status' => 400 ) ); }
		if ( $creating || array_key_exists( 'hypothesis', $data ) ) {
			$hypothesis = trim( (string) ( $data['hypothesis'] ?? '' ) );
			if ( '' === $hypothesis || strlen( $hypothesis ) > 4000 ) { return new \WP_Error( 'invalid_action_hypothesis', __( 'Enter a hypothesis up to 4,000 characters.', WPE_RI_TEXT_DOMAIN ), array( 'status' => 400 ) ); }
		}
		if ( isset( $data['audit_note'] ) && ! is_string( $data['audit_note'] ) ) { return new \WP_Error( 'invalid_action_note', __( 'Audit note must be text.', WPE_RI_TEXT_DOMAIN ), array( 'status' => 400 ) ); }
		if ( isset( $data['issue_url'] ) && ! is_string( $data['issue_url'] ) ) { return new \WP_Error( 'invalid_action_url', __( 'Issue URL must be text.', WPE_RI_TEXT_DOMAIN ), array( 'status' => 400 ) ); }
		if ( isset( $data['planned_review_date'] ) && ! is_string( $data['planned_review_date'] ) ) { return new \WP_Error( 'invalid_action_date', __( 'Review date must be a date string.', WPE_RI_TEXT_DOMAIN ), array( 'status' => 400 ) ); }
		if ( array_key_exists( 'measurement_days', $data ) && ( ! isset( $data['measurement_days'] ) || false === filter_var( $data['measurement_days'], FILTER_VALIDATE_INT ) ) ) { return new \WP_Error( 'invalid_action_window', __( 'Measurement window must be a whole number of days.', WPE_RI_TEXT_DOMAIN ), array( 'status' => 400 ) ); }
		if ( isset( $data['owner_id'] ) && ( false === filter_var( $data['owner_id'], FILTER_VALIDATE_INT ) || (int) $data['owner_id'] < 0 ) ) { return new \WP_Error( 'invalid_action_owner', __( 'Owner must be a user ID.', WPE_RI_TEXT_DOMAIN ), array( 'status' => 400 ) ); }
		if ( isset( $data['measurement_days'] ) && false === filter_var( $data['measurement_days'], FILTER_VALIDATE_INT ) ) { return new \WP_Error( 'invalid_action_window', __( 'Measurement window must be a number of days.', WPE_RI_TEXT_DOMAIN ), array( 'status' => 400 ) ); }
		if ( isset( $data['status'] ) && ! is_string( $data['status'] ) ) { return new \WP_Error( 'invalid_action_status', __( 'Status must be text.', WPE_RI_TEXT_DOMAIN ), array( 'status' => 400 ) ); }
		if ( isset( $data['audit_note'] ) && strlen( (string) $data['audit_note'] ) > 200 ) { return new \WP_Error( 'invalid_action_note', __( 'Audit note must be 200 characters or fewer.', WPE_RI_TEXT_DOMAIN ), array( 'status' => 400 ) ); }
		if ( isset( $data['issue_url'] ) && strlen( (string) $data['issue_url'] ) > 255 ) { return new \WP_Error( 'invalid_action_url', __( 'Issue URL must be 255 characters or fewer.', WPE_RI_TEXT_DOMAIN ), array( 'status' => 400 ) ); }
		if ( isset( $data['issue_url'] ) && '' !== $data['issue_url'] && ! wp_http_validate_url( $data['issue_url'] ) ) { return new \WP_Error( 'invalid_action_url', __( 'Enter a valid issue URL.', WPE_RI_TEXT_DOMAIN ), array( 'status' => 400 ) ); }
		if ( isset( $data['owner_id'] ) && absint( $data['owner_id'] ) && ! get_userdata( absint( $data['owner_id'] ) ) ) { return new \WP_Error( 'invalid_action_owner', __( 'Choose an existing owner.', WPE_RI_TEXT_DOMAIN ), array( 'status' => 400 ) ); }
		if ( isset( $data['measurement_days'] ) && ! in_array( absint( $data['measurement_days'] ), array( 30, 60, 90, 120, 180 ), true ) ) { return new \WP_Error( 'invalid_action_window', __( 'Choose a supported measurement window.', WPE_RI_TEXT_DOMAIN ), array( 'status' => 400 ) ); }
		if ( isset( $data['status'] ) && ! in_array( $data['status'], self::STATUSES, true ) ) { return new \WP_Error( 'invalid_action_status', __( 'Choose a valid action status.', WPE_RI_TEXT_DOMAIN ), array( 'status' => 400 ) ); }
		if ( isset( $data['planned_review_date'] ) && '' !== $data['planned_review_date'] ) {
			$date = explode( '-', (string) $data['planned_review_date'] );
			if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', (string) $data['planned_review_date'] ) || 3 !== count( $date ) || ! checkdate( (int) $date[1], (int) $date[2], (int) $date[0] ) ) { return new \WP_Error( 'invalid_action_date', __( 'Enter a valid review date.', WPE_RI_TEXT_DOMAIN ), array( 'status' => 400 ) ); }
		}
		return true;
	}

	private static function validate_targets( $targets ) {
		if ( ! is_array( $targets ) || ! $targets || count( $targets ) > self::MAX_TARGETS ) { return new \WP_Error( 'invalid_action_targets', __( 'Add between one and ten product or reason targets.', WPE_RI_TEXT_DOMAIN ), array( 'status' => 400 ) ); }
		$normalized = array(); $seen = array();
		foreach ( $targets as $target ) {
			if ( ! is_array( $target ) ) { return new \WP_Error( 'invalid_action_target', __( 'Each target must be a valid product/reason reference.', WPE_RI_TEXT_DOMAIN ), array( 'status' => 400 ) ); }
			$product_raw = $target['product_id'] ?? 0; $variation_raw = $target['variation_id'] ?? 0;
			if ( false === filter_var( $product_raw, FILTER_VALIDATE_INT ) || false === filter_var( $variation_raw, FILTER_VALIDATE_INT ) || (int) $product_raw < 0 || (int) $variation_raw < 0 ) { return new \WP_Error( 'invalid_action_product', __( 'Product and variation IDs must be whole numbers.', WPE_RI_TEXT_DOMAIN ), array( 'status' => 400 ) ); }
			$product_id = absint( $product_raw ); $variation_id = absint( $variation_raw ); $reason_raw = $target['reason_id'] ?? '';
			if ( ! is_string( $reason_raw ) ) { return new \WP_Error( 'invalid_action_reason', __( 'Reason ID must be text.', WPE_RI_TEXT_DOMAIN ), array( 'status' => 400 ) ); }
			$reason_id = sanitize_key( $reason_raw );
			if ( ! $product_id && '' === $reason_id ) { return new \WP_Error( 'invalid_action_target', __( 'Each target must include a product or reason.', WPE_RI_TEXT_DOMAIN ), array( 'status' => 400 ) ); }
			if ( $variation_id && ! $product_id ) { return new \WP_Error( 'invalid_action_variation', __( 'A variation target also requires its product ID.', WPE_RI_TEXT_DOMAIN ), array( 'status' => 400 ) ); }
			if ( $product_id ) {
				$product = wc_get_product( $product_id );
				if ( ! $product ) { return new \WP_Error( 'invalid_action_product', __( 'Choose an existing product.', WPE_RI_TEXT_DOMAIN ), array( 'status' => 400 ) ); }
			}
			if ( $variation_id ) {
				$variation = wc_get_product( $variation_id );
				if ( ! $variation || ! $variation->is_type( 'variation' ) || (int) $variation->get_parent_id() !== $product_id ) { return new \WP_Error( 'invalid_action_variation', __( 'Choose a variation that belongs to the selected product.', WPE_RI_TEXT_DOMAIN ), array( 'status' => 400 ) ); }
			}
			if ( $reason_id && ! ReasonCatalog::find( $reason_id, true ) ) { return new \WP_Error( 'invalid_action_reason', __( 'Choose an active reason.', WPE_RI_TEXT_DOMAIN ), array( 'status' => 400 ) ); }
			$key = $product_id . ':' . $variation_id . ':' . $reason_id;
			if ( isset( $seen[ $key ] ) ) { continue; }
			$seen[ $key ] = true;
			$normalized[] = array( 'product_id' => $product_id, 'variation_id' => $variation_id, 'reason_id' => $reason_id );
		}
		return $normalized;
	}

	private static function count_actions() {
		global $wpdb;
		$count = $wpdb->get_var( 'SELECT COUNT(*) FROM ' . self::table() );
		if ( ! empty( $wpdb->last_error ) ) { return new \WP_Error( 'actions_count_failed', __( 'Corrective-action storage could not be checked.', WPE_RI_TEXT_DOMAIN ), array( 'status' => 500 ) ); }
		return (int) $count;
	}

	private static function hydrate( array $row ) {
		$hydrated = self::hydrate_many( array( $row ) );
		if ( is_wp_error( $hydrated ) ) { return $hydrated; }
		return $hydrated[0] ?? $row;
	}

	/** Hydrate a bounded list with three batched lookups instead of per-action queries. */
	private static function hydrate_many( array $rows ) {
		global $wpdb;
		if ( ! $rows ) { return array(); }
		$ids = array_map( 'absint', array_column( $rows, 'action_id' ) );
		$placeholders = implode( ',', array_fill( 0, count( $ids ), '%d' ) );
		$targets = $wpdb->get_results( $wpdb->prepare( 'SELECT action_id, product_id, variation_id, reason_id FROM ' . self::targets_table() . ' WHERE action_id IN (' . $placeholders . ') ORDER BY target_id ASC', $ids ), ARRAY_A );
		if ( ! empty( $wpdb->last_error ) ) { return new \WP_Error( 'actions_targets_query_failed', __( 'Corrective-action targets could not be loaded.', WPE_RI_TEXT_DOMAIN ), array( 'status' => 500 ) ); }
		$history = $wpdb->get_results( $wpdb->prepare( 'SELECT action_id, previous_status, new_status, note, actor_id, changed_at_gmt FROM ' . self::history_table() . ' WHERE action_id IN (' . $placeholders . ') ORDER BY history_id DESC', $ids ), ARRAY_A );
		if ( ! empty( $wpdb->last_error ) ) { return new \WP_Error( 'actions_history_query_failed', __( 'Corrective-action history could not be loaded.', WPE_RI_TEXT_DOMAIN ), array( 'status' => 500 ) ); }
		$evidence = $wpdb->get_results( $wpdb->prepare( 'SELECT action_id, phase, metric_definition_version, period_from, period_to, returned_quantity, sold_quantity, rate_percent, mapping_percent, eligible_orders, captured_at_gmt, captured_by FROM ' . self::evidence_table() . ' WHERE action_id IN (' . $placeholders . ') ORDER BY evidence_id DESC', $ids ), ARRAY_A );
		if ( ! empty( $wpdb->last_error ) ) { return new \WP_Error( 'actions_evidence_query_failed', __( 'Corrective-action evidence could not be loaded.', WPE_RI_TEXT_DOMAIN ), array( 'status' => 500 ) ); }
		$groups = array( 'targets' => array(), 'history' => array(), 'evidence' => array() );
		foreach ( array( 'targets' => $targets, 'history' => $history, 'evidence' => $evidence ) as $kind => $items ) {
			foreach ( is_array( $items ) ? $items : array() as $item ) {
				$id = (int) $item['action_id']; unset( $item['action_id'] );
				if ( 'history' === $kind && count( $groups[ $kind ][ $id ] ?? array() ) >= self::MAX_HISTORY ) { continue; }
				if ( 'evidence' === $kind && count( $groups[ $kind ][ $id ] ?? array() ) >= self::MAX_EVIDENCE ) { continue; }
				$groups[ $kind ][ $id ][] = $item;
			}
		}
		foreach ( $rows as &$row ) {
			$id = (int) $row['action_id'];
			$row['action_id'] = $id; $row['owner_id'] = (int) $row['owner_id']; $row['measurement_days'] = (int) $row['measurement_days'];
			$row['targets'] = $groups['targets'][ $id ] ?? array();
			$row['history'] = $groups['history'][ $id ] ?? array();
			$row['evidence'] = $groups['evidence'][ $id ] ?? array();
		}
		unset( $row );
		return $rows;
	}

	private static function record_status( int $id, string $previous, string $next, string $note ): bool {
		global $wpdb;
		if ( false === $wpdb->insert( self::history_table(), array( 'action_id' => $id, 'previous_status' => $previous, 'new_status' => $next, 'note' => sanitize_text_field( $note ), 'actor_id' => get_current_user_id(), 'changed_at_gmt' => gmdate( 'Y-m-d H:i:s' ) ), array( '%d', '%s', '%s', '%s', '%d', '%s' ) ) ) { return false; }
		$trim_id = $wpdb->get_var( $wpdb->prepare( 'SELECT history_id FROM ' . self::history_table() . ' WHERE action_id = %d ORDER BY history_id DESC LIMIT 1 OFFSET %d', $id, self::MAX_HISTORY ) );
		if ( ! empty( $wpdb->last_error ) ) { return false; }
		if ( $trim_id && false === $wpdb->query( $wpdb->prepare( 'DELETE FROM ' . self::history_table() . ' WHERE action_id = %d AND history_id <= %d', $id, absint( $trim_id ) ) ) ) { return false; }
		return true;
	}
}
