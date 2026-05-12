<?php
/**
 * Sensor: theme/plugin file editor saves.
 *
 * @package HelloLog
 */

declare(strict_types=1);

namespace HelloLog\Sensors\Core;

use HelloLog\Sensors\AbstractSensor;

defined( 'ABSPATH' ) || exit;

/**
 * Picks up wp-admin Theme Editor and Plugin Editor saves
 * (`wp_ajax_edit-theme-plugin-file`). Periodic disk-integrity scans
 * are a separate concern handled by FilesystemScanCron (M7+).
 */
final class FilesSensor extends AbstractSensor {

	public function key(): string {
		return 'core-files';
	}

	public function boot(): void {
		add_action( 'admin_init', [ $this, 'detect_editor_save' ], 10, 0 );
	}

	public function detect_editor_save(): void {
		if ( ! isset( $_POST['action'], $_POST['file'] ) ) {
			return;
		}
		$action = sanitize_key( wp_unslash( (string) $_POST['action'] ) );
		if ( ! in_array( $action, [ 'edit-theme-plugin-file', 'update', 'updateheaders' ], true ) ) {
			return;
		}
		$file = sanitize_text_field( wp_unslash( (string) $_POST['file'] ) );
		$kind = ! empty( $_POST['plugin'] ) ? 'plugin' : ( ! empty( $_POST['theme'] ) ? 'theme' : 'unknown' );

		$this->emit(
			6300,
			[
				'kind'     => ucfirst( $kind ),
				'file'     => $file,
				'metadata' => [
					'kind' => $kind,
					'file' => $file,
				],
			]
		);
	}
}
