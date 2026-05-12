<?php
/**
 * Registers the SEO + ACF sensors.
 *
 * @package HelloLog
 */

declare(strict_types=1);

namespace HelloLog\Sensors\Integrations;

use HelloLog\Events\EventDispatcher;
use HelloLog\Sensors\SensorManager;

defined( 'ABSPATH' ) || exit;

final class SeoAcfLoader {

	public function attach( SensorManager $manager, EventDispatcher $dispatcher ): void {
		$manager->register( new YoastSeoSensor( $dispatcher ) );
		$manager->register( new RankMathSensor( $dispatcher ) );
		$manager->register( new AcfSensor( $dispatcher ) );
	}
}
