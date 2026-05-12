<?php
/**
 * Tests for {@see HelloLog\Transport\RetryPolicy}.
 *
 * @package HelloLog\Tests
 */

declare(strict_types=1);

namespace HelloLog\Tests\Transport;

use HelloLog\Transport\RetryPolicy;
use PHPUnit\Framework\TestCase;

/**
 * The retry policy is a pure value object — no WP dependencies — so it's
 * the easiest place to anchor confidence in the schedule. These tests
 * pin the curve to the documented schedule.
 */
final class RetryPolicyTest extends TestCase {

	public function test_delay_uses_documented_schedule(): void {
		$policy = new RetryPolicy();
		$this->assertSame( 30, $policy->delay_seconds( 0 ) );
		$this->assertSame( 120, $policy->delay_seconds( 1 ) );
		$this->assertSame( 600, $policy->delay_seconds( 2 ) );
		$this->assertSame( 3600, $policy->delay_seconds( 3 ) );
		$this->assertSame( 21600, $policy->delay_seconds( 4 ) );
		$this->assertSame( 86400, $policy->delay_seconds( 5 ) );
	}

	public function test_delay_clamps_to_last_step_after_max(): void {
		$policy = new RetryPolicy();
		$this->assertSame( 86400, $policy->delay_seconds( 99 ) );
	}

	public function test_dead_letter_triggers_at_max_attempts(): void {
		$policy = new RetryPolicy();
		$this->assertFalse( $policy->should_dead_letter( 5 ) );
		$this->assertTrue( $policy->should_dead_letter( 6 ) );
	}

	public function test_next_try_is_an_rfc_style_utc_timestamp(): void {
		$policy = new RetryPolicy();
		$value  = $policy->next_try( 0 );
		$this->assertMatchesRegularExpression( '/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $value );
	}
}
