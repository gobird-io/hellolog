<?php
/**
 * PHPUnit bootstrap.
 *
 * @package HelloLog\Tests
 */

declare(strict_types=1);

defined( 'ABSPATH' ) || define( 'ABSPATH', '/tmp/wordpress/' );
defined( 'HELLOLOG_VERSION' ) || define( 'HELLOLOG_VERSION', '0.1.0' );

require_once __DIR__ . '/../vendor/autoload.php';
