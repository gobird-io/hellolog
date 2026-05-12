<?php
/**
 * Single admin page that hosts the Vue SPA.
 *
 * @package HelloLog
 */

declare(strict_types=1);

namespace HelloLog\Admin;

defined( 'ABSPATH' ) || exit;

/**
 * Adds a single `Tools → helloLOG` entry. PHP only owns the
 * capability check, the menu wiring, and the Vue mount-point — the
 * top-bar inside the SPA switches between the Logs and Settings views.
 */
final class AdminPage {

	public const PARENT = 'tools.php';
	public const SLUG   = 'hellolog';

	public function register(): void {
		add_action( 'admin_menu', [ $this, 'add_menu' ] );
	}

	public function add_menu(): void {
		add_submenu_page(
			self::PARENT,
			__( 'helloLOG', 'hellolog' ),
			__( 'helloLOG', 'hellolog' ),
			'manage_options',
			self::SLUG,
			[ $this, 'render' ]
		);
	}

	public function render(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		echo '<div id="hellolog-app"></div>';
	}
}
