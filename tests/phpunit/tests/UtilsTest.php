<?php
/**
 * Class UtilsTest.
 *
 * @package mihdan-lite-youtube-embed
 */

namespace Mihdan\LiteYouTubeEmbed;

use PHPUnit\Framework\TestCase;
use WP_Mock;

/**
 * Тесты для Utils.
 */
final class UtilsTest extends TestCase {

	/**
	 * Set up WP_Mock before each test.
	 */
	public function setUp(): void {
		WP_Mock::setUp();

		WP_Mock::userFunction( 'wp_strip_all_tags' )
			->andReturnUsing(
				static function ( $text ) {
					return trim( strip_tags( $text ) );
				}
			);
	}

	/**
	 * Tear down WP_Mock after each test.
	 */
	public function tearDown(): void {
		WP_Mock::tearDown();
	}

	/**
	 * Duration 0 seconds.
	 */
	public function test_iso8601_duration_zero_seconds() {
		$this->assertSame( 'PT0H0M0S', Utils::iso8601_duration( 0 ) );
	}

	/**
	 * Duration less than a minute.
	 */
	public function test_iso8601_duration_seconds_only() {
		$this->assertSame( 'PT0H0M45S', Utils::iso8601_duration( 45 ) );
	}

	/**
	 * Duration with minutes and seconds.
	 */
	public function test_iso8601_duration_minutes_and_seconds() {
		$this->assertSame( 'PT0H1M1S', Utils::iso8601_duration( 61 ) );
	}

	/**
	 * Duration of exactly one hour.
	 */
	public function test_iso8601_duration_exact_hour() {
		$this->assertSame( 'PT1H0M0S', Utils::iso8601_duration( 3600 ) );
	}

	/**
	 * Duration with hours, minutes and seconds.
	 */
	public function test_iso8601_duration_hours_minutes_seconds() {
		$this->assertSame( 'PT1H1M1S', Utils::iso8601_duration( 3661 ) );
	}

	/**
	 * Regression: значение раньше могло получиться без ведущей "P" (невалидный ISO8601).
	 */
	public function test_iso8601_duration_always_has_p_prefix() {
		foreach ( [ 0, 1, 59, 60, 600, 3599, 3600, 36000 ] as $seconds ) {
			$this->assertStringStartsWith( 'P', Utils::iso8601_duration( $seconds ) );
		}
	}

	/**
	 * Duration longer than a day includes the date part.
	 */
	public function test_iso8601_duration_more_than_a_day() {
		$this->assertSame( 'P1DT1H1M1S', Utils::iso8601_duration( 86400 + 3661 ) );
	}

	/**
	 * Duration spanning multiple days.
	 */
	public function test_iso8601_duration_multiple_days() {
		$this->assertSame( 'P2DT0H0M0S', Utils::iso8601_duration( 2 * 86400 ) );
	}

	/**
	 * HTML tags are stripped from the description.
	 */
	public function test_sanitize_video_description_strips_tags() {
		$this->assertSame(
			'Hello world',
			Utils::sanitize_video_description( '<b>Hello</b> <i>world</i>' )
		);
	}

	/**
	 * Newlines are replaced with a single space.
	 */
	public function test_sanitize_video_description_replaces_newlines_with_space() {
		$this->assertSame(
			'Line1 Line2',
			Utils::sanitize_video_description( 'Line1' . PHP_EOL . 'Line2' )
		);
	}

	/**
	 * Empty description stays empty.
	 */
	public function test_sanitize_video_description_empty_string() {
		$this->assertSame( '', Utils::sanitize_video_description( '' ) );
	}
}
