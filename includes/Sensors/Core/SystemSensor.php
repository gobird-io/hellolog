<?php
/**
 * Sensor: WordPress core update.
 *
 * @package HelloLog
 */

declare(strict_types=1);

namespace HelloLog\Sensors\Core;

use HelloLog\Sensors\AbstractSensor;

defined( 'ABSPATH' ) || exit;

/**
 * Single-purpose sensor for `_core_updated_successfully`. WordPress fires
 * this once a core update finishes (manual or automatic). The hook
 * argument is the new version string.
 */
final class SystemSensor extends AbstractSensor {

	public function key(): string {
		return 'core-system';
	}

	public function boot(): void {
		add_action( '_core_updated_successfully', [ $this, 'on_core_updated' ], 10, 1 );
	}

	public function on_core_updated( string $wp_version ): void {
		$this->emit(
			6100,
			[
				'version'  => $wp_version,
				'metadata' => [ 'wp_version' => $wp_version ],
			]
		);
	}
}
