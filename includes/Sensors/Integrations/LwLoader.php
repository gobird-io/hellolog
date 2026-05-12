<?php
/**
 * Registers the LW-family sensors.
 *
 * @package HelloLog
 */

declare(strict_types=1);

namespace HelloLog\Sensors\Integrations;

use HelloLog\Events\EventDispatcher;
use HelloLog\Sensors\SensorManager;

defined( 'ABSPATH' ) || exit;

final class LwLoader {

	public function attach( SensorManager $manager, EventDispatcher $dispatcher ): void {
		$manager->register( new LwFirewallSensor( $dispatcher ) );
		$manager->register( new LwSeoSensor( $dispatcher ) );
		$manager->register( new LwDisableSensor( $dispatcher ) );
		$manager->register( new LwCookieSensor( $dispatcher ) );
		$manager->register( new LwZenAdminSensor( $dispatcher ) );
	}
}
