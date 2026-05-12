<?php
/**
 * Plugin Name:       helloLOG
 * Plugin URI:        https://hellowp.io/hellolog
 * Description:       Lightweight WordPress activity log. By hellowp.io and gobird.io.
 * Version:           0.1.0
 * Requires at least: 6.4
 * Requires PHP:      8.0
 * Author:            HelloWP &amp; goBird
 * Author URI:        https://hellowp.io
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       hellolog
 * Domain Path:       /languages
 *
 * @package HelloLog
 */

declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

const HELLOLOG_VERSION = '0.1.0';
const HELLOLOG_FILE    = __FILE__;

define( 'HELLOLOG_DIR', plugin_dir_path( __FILE__ ) );
define( 'HELLOLOG_URL', plugin_dir_url( __FILE__ ) );

require_once HELLOLOG_DIR . 'vendor/autoload.php';

// Action Scheduler ships as a Composer dependency. Its bootstrap auto-detects
// the highest-versioned copy across active plugins, so loading it here is safe
// even when WooCommerce (or another plugin) already bundles it.
require_once HELLOLOG_DIR . 'vendor/woocommerce/action-scheduler/action-scheduler.php';

register_activation_hook( __FILE__, [ \HelloLog\Activator::class, 'activate' ] );
register_deactivation_hook( __FILE__, [ \HelloLog\Deactivator::class, 'deactivate' ] );

add_action(
	'plugins_loaded',
	static function (): void {
		\HelloLog\Plugin::instance()->boot();
	}
);
