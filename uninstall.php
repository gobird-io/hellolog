<?php
/**
 * Uninstall entry point.
 *
 * @package HelloLog
 */

declare(strict_types=1);

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

require_once __DIR__ . '/vendor/autoload.php';

\HelloLog\Uninstall::uninstall();
