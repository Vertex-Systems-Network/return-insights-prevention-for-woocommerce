<?php
/**
 * Versioned event-table installation.
 *
 * @package WPEssentialReturnInsights
 */

namespace WPEssential\ReturnInsights;

defined( 'ABSPATH' ) || exit;

/**
 * Creates and upgrades the plugin-owned event table.
 */
final class Installer {
	/**
	 * Current schema version.
	 */
	public const DB_VERSION = '2';

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

		if ( ! empty( $wpdb->last_error ) ) {
			return false;
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
