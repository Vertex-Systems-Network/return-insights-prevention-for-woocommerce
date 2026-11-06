<?php
/**
 * Weekly, evidence-based in-product alert lifecycle.
 *
 * @package WPEssentialReturnInsights
 */
namespace WPEssential\ReturnInsights;
defined( 'ABSPATH' ) || exit;

/** Evaluates bounded Pro pattern evidence and stores deduplicated alert state. */
final class AlertService {
	private const OPTION = 'wpe_ri_alert_state';
	private const SCAN_OPTION = 'wpe_ri_alert_last_scan';
	private const CRON_HOOK = 'wpessential_return_insights_weekly_alert_refresh';
	private const MAX_ALERTS = 2000;

	public static function register(): void {
		add_action( 'init', array( self::class, 'schedule' ) );
		add_action( self::CRON_HOOK, array( self::class, 'refresh' ) );
	}

	public static function schedule(): void {
		if ( ! wp_next_scheduled( self::CRON_HOOK ) ) {
			wp_schedule_event( time() + HOUR_IN_SECONDS, 'weekly', self::CRON_HOOK );
		}
	}

	/** Evaluate default approved launch thresholds once per distinct ISO week. */
	public static function refresh() {
		if ( ! PatternService::is_entitled() ) {
			return new \WP_Error( 'pro_required', __( 'Alerts require the Pro add-on.', WPE_RI_TEXT_DOMAIN ), array( 'status' => 403 ) );
		}

		$report = PatternService::report( 90, 90, true );
		if ( is_wp_error( $report ) ) {
			self::save_scan( array( 'status' => 'unavailable', 'reason' => $report->get_error_code(), 'checked_at_gmt' => gmdate( 'Y-m-d H:i:s' ) ) );
			return $report;
		}
		$coverage = $report['coverage'] ?? array();
		$mapping = min(
			(float) ( $coverage['current']['return_mapping_percent'] ?? 0 ),
			(float) ( $coverage['comparison']['return_mapping_percent'] ?? 0 )
		);
		if ( $mapping < 80 ) {
			$scan = array( 'status' => 'coverage_blocked', 'reason' => 'coverage_below_threshold', 'mapping_percent' => $mapping, 'current_coverage' => $coverage['current'] ?? array(), 'comparison_coverage' => $coverage['comparison'] ?? array(), 'checked_at_gmt' => gmdate( 'Y-m-d H:i:s' ) );
			self::save_scan( $scan );
			return array( 'evaluated' => 0, 'suppressed' => 'coverage_below_threshold', 'scan' => $scan );
		}

		$week = current_datetime()->format( 'o-W' );
		$state = get_option( self::OPTION, array() );
		if ( ! is_array( $state ) ) { $state = array(); }
		$clusters = ClusterService::build( $report['patterns'] ?? array(), $coverage );
		$evaluated = 0;
		$suppressed_counts = array();

		foreach ( $clusters as $cluster ) {
			$current = $cluster['current'];
			$previous = $cluster['comparison'];
			$suppressed = array();
			if ( (float) $current['returned_quantity'] < 5 ) { $suppressed[] = 'below_return_units'; }
			if ( (float) $current['sold_quantity'] < 20 ) { $suppressed[] = 'low_current_sold_sample'; }
			if ( (float) $previous['sold_quantity'] < 20 ) { $suppressed[] = 'low_comparison_sold_sample'; }
			if ( 'available' !== $current['rate_status'] ) { $suppressed[] = 'current_' . sanitize_key( (string) $current['rate_status'] ); }
			if ( 'available' !== $previous['rate_status'] ) { $suppressed[] = 'comparison_' . sanitize_key( (string) $previous['rate_status'] ); }
			if ( $suppressed ) {
				foreach ( $suppressed as $reason ) { $suppressed_counts[ $reason ] = (int) ( $suppressed_counts[ $reason ] ?? 0 ) + 1; }
				continue;
			}

			$key = hash( 'sha256', implode( ':', array( $cluster['product_id'], $cluster['variation_id'], $cluster['cluster_id'], $report['metric_definition_version'], 90, 90 ) ) );
			$prior = $state[ $key ] ?? array();
			if ( ( $prior['last_week'] ?? '' ) === $week ) { continue; }
			$last_week = (string) ( $prior['last_week'] ?? '' );
			$last_week_date = $last_week ? \DateTimeImmutable::createFromFormat( '!o-W', $last_week, wp_timezone() ) : false;
			$current_week_date = \DateTimeImmutable::createFromFormat( '!o-W', $week, wp_timezone() );
			$adjacent = $last_week_date && $current_week_date && 7 === (int) $last_week_date->diff( $current_week_date )->days;
			$streak = $adjacent ? (int) ( $prior['streak'] ?? 0 ) + 1 : 1;
			$state[ $key ] = array_merge( $prior, array(
				'alert_id' => $key,
				'product_id' => (int) $cluster['product_id'],
				'variation_id' => (int) $cluster['variation_id'],
				'cluster_id' => (string) $cluster['cluster_id'],
				'reason_ids' => array_values( $cluster['reason_ids'] ),
				'current_returned_quantity' => (float) $current['returned_quantity'],
				'current_sold_quantity' => (float) $current['sold_quantity'],
				'current_rate_percent' => $current['rate_percent'],
				'comparison_rate_percent' => $previous['rate_percent'],
				'mapping_percent' => $mapping,
				'cohort_from' => $report['periods']['current']['date_from'],
				'cohort_to' => $report['periods']['current']['date_to'],
				'last_week' => $week,
				'streak' => $streak,
				'status' => $streak >= 2 ? ( ( $prior['status'] ?? '' ) === 'dismissed' ? 'dismissed' : 'active' ) : 'candidate',
				'last_seen_gmt' => gmdate( 'Y-m-d H:i:s' ),
			) );
			++$evaluated;
		}

		if ( count( $state ) > self::MAX_ALERTS ) {
			uasort( $state, static function ( array $a, array $b ): int { return strcmp( (string) ( $b['last_seen_gmt'] ?? '' ), (string) ( $a['last_seen_gmt'] ?? '' ) ); } );
			$state = array_slice( $state, 0, self::MAX_ALERTS, true );
		}
		update_option( self::OPTION, $state, false );
		$scan = array( 'status' => 'complete', 'week' => $week, 'evaluated' => $evaluated, 'suppressed_counts' => $suppressed_counts, 'checked_at_gmt' => gmdate( 'Y-m-d H:i:s' ) );
		self::save_scan( $scan );
		return array( 'evaluated' => $evaluated, 'week' => $week, 'scan' => $scan );
	}

	public static function last_scan(): array {
		$scan = get_option( self::SCAN_OPTION, array() );
		return is_array( $scan ) ? $scan : array();
	}

	private static function save_scan( array $scan ): void {
		update_option( self::SCAN_OPTION, $scan, false );
	}

	public static function all(): array {
		$state = get_option( self::OPTION, array() );
		if ( ! is_array( $state ) ) { return array(); }
		$rows = array_values( array_filter( $state, static function ( array $row ): bool { return in_array( $row['status'] ?? '', array( 'active', 'dismissed' ), true ); } ) );
		usort( $rows, static function ( array $a, array $b ): int { return strcmp( (string) ( $b['last_seen_gmt'] ?? '' ), (string) ( $a['last_seen_gmt'] ?? '' ) ); } );
		return $rows;
	}

	public static function set_status( string $id, string $status, string $note ) {
		if ( ! in_array( $status, array( 'dismissed', 'active' ), true ) ) {
			return new \WP_Error( 'invalid_alert_status', __( 'Choose a valid alert status.', WPE_RI_TEXT_DOMAIN ), array( 'status' => 400 ) );
		}
		$state = get_option( self::OPTION, array() );
		if ( ! is_array( $state ) || ! isset( $state[ $id ] ) || (int) ( $state[ $id ]['streak'] ?? 0 ) < 2 ) {
			return new \WP_Error( 'alert_not_found', __( 'Alert not found.', WPE_RI_TEXT_DOMAIN ), array( 'status' => 404 ) );
		}
		if ( 'dismissed' === $status && ( '' === trim( $note ) || strlen( $note ) > 200 ) ) {
			return new \WP_Error( 'alert_audit_note_required', __( 'Provide a dismissal note up to 200 characters.', WPE_RI_TEXT_DOMAIN ), array( 'status' => 400 ) );
		}
		$state[ $id ]['status'] = $status;
		$audit_history = isset( $state[ $id ]['audit_history'] ) && is_array( $state[ $id ]['audit_history'] ) ? $state[ $id ]['audit_history'] : array();
		$audit_history[] = array(
			'status' => $status,
			'note' => sanitize_text_field( $note ),
			'actor_id' => get_current_user_id(),
			'at_gmt' => gmdate( 'Y-m-d H:i:s' ),
		);
		$state[ $id ]['audit_history'] = array_slice( $audit_history, -50 );
		update_option( self::OPTION, $state, false );
		return $state[ $id ];
	}
}
