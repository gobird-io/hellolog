<?php
/**
 * Contact Form 7 sensor.
 *
 * @package HelloLog
 */

declare(strict_types=1);

namespace HelloLog\Sensors\Integrations;

use HelloLog\Sensors\AbstractSensor;

defined( 'ABSPATH' ) || exit;

/**
 * CF7 stores forms as `wpcf7_contact_form` CPT. We listen to the dedicated
 * save action plus the mail-sent action for submissions.
 */
final class ContactForm7Sensor extends AbstractSensor {

	public function key(): string {
		return 'contact-form-7';
	}

	public function should_load(): bool {
		return defined( 'WPCF7_VERSION' );
	}

	public function boot(): void {
		add_action( 'wpcf7_after_save', [ $this, 'on_save' ], 10, 1 );
		add_action( 'wpcf7_mail_sent', [ $this, 'on_mail_sent' ], 10, 1 );
	}

	/**
	 * @param mixed $contact_form
	 */
	public function on_save( $contact_form ): void {
		if ( ! is_object( $contact_form ) || ! method_exists( $contact_form, 'id' ) ) {
			return;
		}
		$title = method_exists( $contact_form, 'title' ) ? (string) $contact_form->title() : '';
		$this->emit(
			5850,
			[
				'title'    => $title,
				'metadata' => [ 'form_id' => (int) $contact_form->id() ],
			]
		);
	}

	/**
	 * @param mixed $contact_form
	 */
	public function on_mail_sent( $contact_form ): void {
		if ( ! is_object( $contact_form ) || ! method_exists( $contact_form, 'id' ) ) {
			return;
		}
		$title = method_exists( $contact_form, 'title' ) ? (string) $contact_form->title() : '';
		$this->emit(
			5851,
			[
				'title'    => $title,
				'metadata' => [ 'form_id' => (int) $contact_form->id() ],
			]
		);
	}
}
