<?php
/**
 * Registers every form-plugin sensor.
 *
 * @package HelloLog
 */

declare(strict_types=1);

namespace HelloLog\Sensors\Integrations;

use HelloLog\Events\EventDispatcher;
use HelloLog\Sensors\SensorManager;

defined( 'ABSPATH' ) || exit;

final class FormsLoader {

	public function attach( SensorManager $manager, EventDispatcher $dispatcher ): void {
		$manager->register( new GravityFormsSensor( $dispatcher ) );
		$manager->register( new WPFormsSensor( $dispatcher ) );
		$manager->register( new ContactForm7Sensor( $dispatcher ) );
		$manager->register( new FluentFormsSensor( $dispatcher ) );
	}
}
