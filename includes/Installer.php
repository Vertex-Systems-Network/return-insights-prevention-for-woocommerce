<?php
/**
 * Versioned plugin-table installation.
 *
 * @package WPEssentialReturnInsights
 */

namespace WPEssential\ReturnInsights;

defined( 'ABSPATH' ) || exit;

/**
 * Creates and upgrades the plugin-owned data tables.
 */
final class Installer {
	/**
	 * Current schema version.
	 */
	public const DB_VERSION = '3';

	/**
	 * Schema version option.
	 */
	private const DB_VERSION_OPTION = 'wpe_ri_db_version';

	/**
	 * Install or upgrade the event table when the schema version changes.
	 *
	 * @return bool
	 */
	public static function maybe_upgrade(): bool {
		$installed_version = (string) get_option( self::DB_VERSION_OPTION, '0' );
		if ( version_compare( $installed_version, self::DB_VERSION, '>=' ) ) {
			return true;
		}

		return self::install();
	}

	/**
	 * Run the forward-only schema migration.
	 *
	 * @return bool
	 */
	public static function install(): bool {
		global $wpdb;

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$table_name      = self::events_table();
		$actions_table   = ActionService::table();
		$targets_table   = ActionService::targets_table();
		$history_table   = ActionService::history_table();
		$evidence_table  = ActionService::evidence_table();
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE {$table_name} (
			event_id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			event_uuid char(36) NOT NULL,
			source_key char(64) NOT NULL,
			source_type varchar(32) NOT NULL,
			source_record_id varchar(100) NOT NULL,
			event_kind varchar(32) NOT NULL,
			order_id bigint(20) unsigned NOT NULL,
			refund_id bigint(20) unsigned NOT NULL DEFAULT 0,
			order_item_id bigint(20) unsigned NOT NULL,
			product_id bigint(20) unsigned NOT NULL,
			variation_id bigint(20) unsigned NOT NULL DEFAULT 0,
			reason_id varchar(64) NOT NULL,
			quantity decimal(12,4) NOT NULL,
			attribution_scope varchar(16) NOT NULL DEFAULT 'item',
			mapping_state varchar(24) NOT NULL DEFAULT 'mapped',
			occurred_at_gmt datetime NOT NULL,
			created_at_gmt datetime NOT NULL,
			updated_at_gmt datetime NOT NULL,
			deleted_at_gmt datetime NULL,
			created_by bigint(20) unsigned NOT NULL DEFAULT 0,
			updated_by bigint(20) unsigned NOT NULL DEFAULT 0,
			deletion_note varchar(200) NOT NULL DEFAULT '',
			PRIMARY KEY  (event_id),
			UNIQUE KEY event_uuid (event_uuid),
			UNIQUE KEY source_key (source_key),
			KEY order_item_kind_deleted (order_item_id,event_kind,deleted_at_gmt),
			KEY order_id_occurred (order_id,occurred_at_gmt),
			KEY refund_id (refund_id),
			KEY reason_occurred (reason_id,occurred_at_gmt),
			KEY product_variation_occurred (product_id,variation_id,occurred_at_gmt)
		) {$charset_collate};";

		dbDelta( $sql );

		dbDelta( "CREATE TABLE {$actions_table} (
			action_id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			action_uuid char(36) NOT NULL,
			title varchar(160) NOT NULL,
			hypothesis text NOT NULL,
			issue_url varchar(255) NOT NULL DEFAULT '',
			owner_id bigint(20) unsigned NOT NULL DEFAULT 0,
			status varchar(24) NOT NULL DEFAULT 'proposed',
			measurement_days smallint(5) unsigned NOT NULL DEFAULT 90,
			planned_review_date date NULL,
			created_at_gmt datetime NOT NULL,
			updated_at_gmt datetime NOT NULL,
			created_by bigint(20) unsigned NOT NULL DEFAULT 0,
			updated_by bigint(20) unsigned NOT NULL DEFAULT 0,
			PRIMARY KEY  (action_id),
			UNIQUE KEY action_uuid (action_uuid),
			KEY status_review_date (status,planned_review_date),
			KEY owner_status (owner_id,status),
			KEY updated_at (updated_at_gmt)
		) {$charset_collate} ENGINE=InnoDB;" );

		dbDelta( "CREATE TABLE {$targets_table} (
			target_id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			action_id bigint(20) unsigned NOT NULL,
			product_id bigint(20) unsigned NOT NULL DEFAULT 0,
			variation_id bigint(20) unsigned NOT NULL DEFAULT 0,
			reason_id varchar(64) NOT NULL DEFAULT '',
			PRIMARY KEY  (target_id),
			KEY action_id (action_id),
			KEY product_variation_reason (product_id,variation_id,reason_id)
		) {$charset_collate} ENGINE=InnoDB;" );

		dbDelta( "CREATE TABLE {$history_table} (
			history_id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			action_id bigint(20) unsigned NOT NULL,
			previous_status varchar(24) NOT NULL DEFAULT '',
			new_status varchar(24) NOT NULL,
			note varchar(200) NOT NULL DEFAULT '',
			actor_id bigint(20) unsigned NOT NULL DEFAULT 0,
			changed_at_gmt datetime NOT NULL,
			PRIMARY KEY  (history_id),
			KEY action_changed (action_id,changed_at_gmt)
		) {$charset_collate} ENGINE=InnoDB;" );

		dbDelta( "CREATE TABLE {$evidence_table} (
			evidence_id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			action_id bigint(20) unsigned NOT NULL,
			phase varchar(16) NOT NULL,
			metric_definition_version varchar(64) NOT NULL,
			period_from date NOT NULL,
			period_to date NOT NULL,
			returned_quantity decimal(12,4) NOT NULL,
			sold_quantity decimal(12,4) NOT NULL,
			rate_percent decimal(7,2) NULL,
			mapping_percent decimal(5,2) NULL,
			eligible_orders int(10) unsigned NOT NULL DEFAULT 0,
			captured_at_gmt datetime NOT NULL,
			captured_by bigint(20) unsigned NOT NULL DEFAULT 0,
			PRIMARY KEY  (evidence_id),
			KEY action_phase_capture (action_id,phase,captured_at_gmt)
		) {$charset_collate} ENGINE=InnoDB;" );

		if ( ! empty( $wpdb->last_error ) ) {
			return false;
		}

		$required_columns = array(
			$table_name => array( 'event_id', 'event_uuid', 'source_key', 'event_kind', 'refund_id', 'order_item_id', 'reason_id', 'quantity', 'deleted_at_gmt' ),
			$actions_table => array( 'action_id', 'action_uuid', 'title', 'hypothesis', 'owner_id', 'status', 'measurement_days', 'planned_review_date' ),
			$targets_table => array( 'target_id', 'action_id', 'product_id', 'variation_id', 'reason_id' ),
			$history_table => array( 'history_id', 'action_id', 'previous_status', 'new_status', 'actor_id', 'changed_at_gmt' ),
			$evidence_table => array( 'evidence_id', 'action_id', 'phase', 'metric_definition_version', 'period_from', 'period_to', 'returned_quantity', 'sold_quantity', 'rate_percent', 'mapping_percent', 'eligible_orders' ),
		);
		foreach ( $required_columns as $table => $columns ) {
			$found = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $table ) ) );
			if ( $table !== $found || ! empty( $wpdb->last_error ) ) {
				return false;
			}
			$installed_columns = $wpdb->get_col( "SHOW COLUMNS FROM {$table}", 0 );
			if ( ! is_array( $installed_columns ) || array_diff( $columns, $installed_columns ) || ! empty( $wpdb->last_error ) ) {
				return false;
			}
		}

		update_option( self::DB_VERSION_OPTION, self::DB_VERSION, false );

		return true;
	}

	/**
	 * Get the site-scoped custom event table name.
	 *
	 * @return string
	 */
	public static function events_table(): string {
		global $wpdb;

		return $wpdb->prefix . 'wpessential_return_insights_events';
	}
}
