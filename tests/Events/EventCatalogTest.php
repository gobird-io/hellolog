<?php
/**
 * Tests for {@see HelloLog\Events\EventCatalog}.
 *
 * @package HelloLog\Tests
 */

declare(strict_types=1);

namespace HelloLog\Tests\Events;

use HelloLog\Events\EventCatalog;
use HelloLog\Events\EventDefinition;
use PHPUnit\Framework\TestCase;

/**
 * Covers the registration / lookup contract. Heavier tests around the
 * seeder live next to the seeder itself.
 */
final class EventCatalogTest extends TestCase {

	public function test_register_then_get_round_trips_definition(): void {
		$catalog = new EventCatalog();
		$def     = new EventDefinition( 1000, 'user', 'login', 'info', 'User {username} logged in.' );

		$catalog->register( $def );
		$out = $catalog->get( 1000 );

		$this->assertNotNull( $out );
		$this->assertSame( 1000, $out->code );
		$this->assertSame( 'user', $out->object );
		$this->assertSame( 'login', $out->event_type );
	}

	public function test_get_unknown_code_returns_null(): void {
		$catalog = new EventCatalog();
		$this->assertNull( $catalog->get( 42 ) );
		$this->assertFalse( $catalog->has( 42 ) );
	}

	public function test_register_overwrites_same_code(): void {
		$catalog = new EventCatalog();
		$catalog->register( new EventDefinition( 1, 'a', 'b', 'info', 'first' ) );
		$catalog->register( new EventDefinition( 1, 'x', 'y', 'high', 'second' ) );

		$out = $catalog->get( 1 );
		$this->assertNotNull( $out );
		$this->assertSame( 'x', $out->object );
		$this->assertSame( 'second', $out->message_template );
	}
}
