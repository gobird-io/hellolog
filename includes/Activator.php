<?php
/**
 * Activation routine — creates the local outgoing queue table.
 *
 * @package HelloLog
 */

declare(strict_types=1);

namespace HelloLog;

defined( 'ABSPATH' ) || exit;

/**
 * Runs on plugin activation. Creates `{$wpdb->prefix}hellolog_queue` and seeds
 * default options. The queue is the ONLY local table this plugin owns —
 * long-term event storage lives on the external backend.
 */
final class Activator {

	private const TABLE_BASENAME = 'hellolog_queue';

	/**
	 * Sensors that are noisy on a typical site (REST polling, XML-RPC,
	 * 404 monitor) and not useful enough to justify their volume by
	 * default. Operators can toggle them back on from Filters tab.
	 */
	private const DEFAULT_DISABLED_SENSORS = [
		'core-request' => true,
	];

	private const DEFAULT_OPTS = [
		'hellolog_token'          => '',
		'hellolog_anonymize_ip'   => false,
		'hellolog_sensor_filters' => self::DEFAULT_DISABLED_SENSORS,
	];

	public static function activate(): void {
		self::create_queue_table();
		self::seed_default_options();
		self::store_db_version();
	}

	private static function create_queue_table(): void {
		global $wpdb;

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$table   = self::table_name();
		$collate = $wpdb->get_charset_collate();

		// The schema is intentionally narrow: one JSON payload per row plus
		// the bookkeeping columns the dispatcher needs to retry and drain.
		$sql = "CREATE TABLE {$table} (
			id         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			payload    LONGTEXT NOT NULL,
			attempts   SMALLINT UNSIGNED NOT NULL DEFAULT 0,
			next_try   DATETIME NOT NULL,
			status     VARCHAR(16) NOT NULL DEFAULT 'pending',
			last_error VARCHAR(512) NULL,
			created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY queue_pick_idx (status, next_try)
		) {$collate};";

		dbDelta( $sql );
	}

	private static function seed_default_options(): void {
		foreach ( self::DEFAULT_OPTS as $option => $default ) {
			if ( false === get_option( $option, false ) ) {
				add_option( $option, $default, '', false );
			}
		}
	}

	private static function store_db_version(): void {
		update_option( 'hellolog_db_version', HELLOLOG_VERSION, false );
	}

	public static function table_name(): string {
		global $wpdb;

		return $wpdb->prefix . self::TABLE_BASENAME;
	}
}
