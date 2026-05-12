<?php
/**
 * Yoast SEO sensor.
 *
 * @package HelloLog
 */

declare(strict_types=1);

namespace HelloLog\Sensors\Integrations;

use HelloLog\Sensors\AbstractSensor;

defined( 'ABSPATH' ) || exit;

/**
 * Tracks Yoast SEO option-table mutations. Yoast prefixes its options
 * with `wpseo_`, so a single `updated_option` filter is enough.
 */
final class YoastSeoSensor extends AbstractSensor {

	public function key(): string {
		return 'yoast-seo';
	}

	public function should_load(): bool {
		return defined( 'WPSEO_VERSION' );
	}

	public function boot(): void {
		add_action( 'updated_option', [ $this, 'on_update' ], 10, 3 );
	}

	/**
	 * @param mixed $old_value
	 * @param mixed $value
	 */
	public function on_update( string $option, $old_value, $value ): void {
		if ( ! str_starts_with( $option, 'wpseo' ) ) {
			return;
		}
		if ( $old_value === $value ) {
			return;
		}
		$this->emit(
			8800,
			[
				'option'   => $option,
				'metadata' => [ 'option' => $option ],
			]
		);
	}
}
