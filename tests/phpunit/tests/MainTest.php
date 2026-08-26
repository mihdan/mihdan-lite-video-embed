<?php
/**
 * Class MainTest.
 *
 * @package mihdan-lite-youtube-embed
 */

namespace Mihdan\LiteYouTubeEmbed;

use PHPUnit\Framework\TestCase;
use ReflectionClass;
use WP_Mock;

/**
 * Тесты для Main.
 *
 * Экземпляр создаётся через ReflectionClass::newInstanceWithoutConstructor(),
 * чтобы не тянуть за собой Options/Settings/Providers.
 */
final class MainTest extends TestCase {

	/**
	 * Set up WP_Mock before each test.
	 */
	public function setUp(): void {
		WP_Mock::setUp();
	}

	/**
	 * Tear down WP_Mock after each test.
	 */
	public function tearDown(): void {
		WP_Mock::tearDown();
	}

	/**
	 * Create a Main instance without running its constructor.
	 *
	 * @return Main
	 */
	private function make_main(): Main {
		return ( new ReflectionClass( Main::class ) )->newInstanceWithoutConstructor();
	}

	/**
	 * A settings link is appended for this plugin's file.
	 */
	public function test_add_settings_link_adds_link_for_this_plugin() {
		if ( ! defined( 'MIHDAN_LITE_YOUTUBE_EMBED_FILE' ) ) {
			define( 'MIHDAN_LITE_YOUTUBE_EMBED_FILE', '/path/mihdan-lite-youtube-embed.php' );
		}

		WP_Mock::userFunction( 'plugin_basename' )->andReturn( 'mihdan-lite-youtube-embed/mihdan-lite-youtube-embed.php' );
		WP_Mock::userFunction( 'admin_url' )->andReturn( 'http://example.com/wp-admin/options-general.php?page=mihdan-lite-youtube-embed' );
		WP_Mock::userFunction( 'esc_html__' )->andReturnUsing(
			static function ( $text ) {
				return $text;
			}
		);

		$main   = $this->make_main();
		$result = $main->add_settings_link( [ '<a href="#">Deactivate</a>' ], 'mihdan-lite-youtube-embed/mihdan-lite-youtube-embed.php' );

		$this->assertCount( 2, $result );
		$this->assertStringContainsString( 'Settings', $result[1] );
	}

	/**
	 * No settings link is added for a different plugin's file.
	 */
	public function test_add_settings_link_ignores_other_plugins() {
		WP_Mock::userFunction( 'plugin_basename' )->andReturn( 'mihdan-lite-youtube-embed/mihdan-lite-youtube-embed.php' );

		$main   = $this->make_main();
		$result = $main->add_settings_link( [ '<a href="#">Deactivate</a>' ], 'some-other-plugin/some-other-plugin.php' );

		$this->assertCount( 1, $result );
	}

	/**
	 * Clears the cache and resets the flag when requested.
	 */
	public function test_maybe_clear_cache_clears_when_requested() {
		$main = $this->make_main();

		$wpdb = new class() {
			/**
			 * Fake postmeta table name.
			 *
			 * @var string
			 */
			public $postmeta = 'wp_postmeta';

			/**
			 * Queries recorded by query().
			 *
			 * @var array
			 */
			public $queries = [];

			/**
			 * Record the query instead of running it.
			 *
			 * @param string $sql SQL query.
			 *
			 * @return void
			 */
			public function query( $sql ) {
				$this->queries[] = $sql;
			}
		};

		$property = ( new ReflectionClass( Main::class ) )->getProperty( 'wpdb' );
		$property->setAccessible( true );
		$property->setValue( $main, $wpdb );

		WP_Mock::userFunction( 'add_settings_error' )->once();
		WP_Mock::userFunction( '__' )->andReturnUsing(
			static function ( $text ) {
				return $text;
			}
		);

		$result = $main->maybe_clear_cache( [ 'clear_cache' => 'on' ], [] );

		$this->assertSame( 'off', $result['clear_cache'] );
		$this->assertCount( 1, $wpdb->queries );
	}

	/**
	 * Does nothing when the flag is not set.
	 */
	public function test_maybe_clear_cache_does_nothing_when_not_requested() {
		$main = $this->make_main();

		$value = [ 'clear_cache' => 'off' ];

		$this->assertSame( $value, $main->maybe_clear_cache( $value, [] ) );
	}
}
