<?php
/**
 * Registers the `wp hellolog` WP-CLI command tree.
 *
 * @package HelloLog
 */

declare(strict_types=1);

namespace HelloLog\Cli;

defined( 'ABSPATH' ) || exit;

/**
 * Thin glue: hands the {@see Command} class to WP-CLI when we're in a
 * CLI context. Kept out of {@see Command} itself so the bootstrap stays
 * a single `if WP_CLI` line in Plugin::boot().
 */
final class Registrar {

	public function register(): void {
		if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
			return;
		}
		if ( class_exists( '\WP_CLI' ) ) {
			\WP_CLI::add_command( 'hellolog', Command::class );
		}
	}
}
