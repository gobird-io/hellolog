<?php
/**
 * Base class for every sensor module.
 *
 * @package HelloLog
 */

declare(strict_types=1);

namespace HelloLog\Sensors;

use HelloLog\Events\EventDispatcher;

defined( 'ABSPATH' ) || exit;

/**
 * Each integration (core auth, WooCommerce, Yoast SEO, ...) lives in a
 * subclass. The sensor's only job: subscribe to the relevant WordPress
 * actions/filters, translate them into catalog codes, and hand the result
 * to the dispatcher. No DB access, no HTTP — those belong elsewhere.
 *
 * Subclasses MUST implement {@see self::boot()} and SHOULD override
 * {@see self::should_load()} when their hooks depend on a third-party
 * plugin or feature flag being present.
 */
abstract class AbstractSensor {

	public function __construct(
		protected EventDispatcher $dispatcher
	) {
	}

	/**
	 * Stable identifier used in settings (e.g. `"woocommerce"`, `"yoast-seo"`).
	 *
	 * Used by the settings UI as the toggle key and by SensorManager to
	 * skip a sensor that the operator has explicitly disabled. Must be
	 * unique across the plugin.
	 */
	abstract public function key(): string;

	/**
	 * Register the WordPress hooks this sensor cares about.
	 *
	 * Called once per request after {@see should_load()} returned true.
	 */
	abstract public function boot(): void;

	/**
	 * Lazy-load guard. Default is "always on" for core sensors; integration
	 * sensors override this to test for `class_exists()` / `function_exists()`
	 * before registering their hooks.
	 */
	public function should_load(): bool {
		return true;
	}

	/**
	 * Short convenience wrapper for the most common dispatch shape.
	 *
	 * @param array<string, mixed> $fields
	 */
	protected function emit( int $code, array $fields = [] ): void {
		$this->dispatcher->dispatch( $code, $fields );
	}
}
