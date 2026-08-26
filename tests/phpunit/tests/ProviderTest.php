<?php
/**
 * Class ProviderTest.
 *
 * @package mihdan-lite-youtube-embed
 */

namespace Mihdan\LiteYouTubeEmbed;

use PHPUnit\Framework\TestCase;
use WP_Mock;

/**
 * Тесты для абстрактного Provider (через анонимную реализацию).
 */
final class ProviderTest extends TestCase {

	/**
	 * Set up WP_Mock before each test.
	 */
	public function setUp(): void {
		WP_Mock::setUp();

		foreach ( [ 'esc_attr', 'esc_html', 'esc_url' ] as $function ) {
			WP_Mock::userFunction( $function )->andReturnUsing(
				static function ( $value ) {
					return $value;
				}
			);
		}
	}

	/**
	 * Tear down WP_Mock after each test.
	 */
	public function tearDown(): void {
		WP_Mock::tearDown();
	}

	/**
	 * Create a concrete Provider instance with a given id.
	 *
	 * @param string $id Provider id.
	 *
	 * @return Provider
	 */
	private function make_provider( string $id ): Provider {
		return new class( $id ) extends Provider {
			/**
			 * Constructor.
			 *
			 * @param string $id Provider id.
			 */
			public function __construct( string $id ) {
				$this->id = $id;
			}

			/**
			 * No-op.
			 */
			public function setup_hooks(): void {}

			/**
			 * No-op.
			 *
			 * @param string $video_id Video id.
			 *
			 * @return array
			 */
			public function get_data( string $video_id ): array {
				return [];
			}

			/**
			 * No-op.
			 *
			 * @param string $content Content.
			 *
			 * @return string
			 */
			public function auto_embed_content( string $content ): string {
				return $content;
			}

			/**
			 * No-op.
			 *
			 * @param string $video_id Video id.
			 *
			 * @return array
			 */
			public function get_fallback_data( string $video_id ): array {
				return [];
			}

			/**
			 * No-op.
			 *
			 * @param string $api_key API key.
			 *
			 * @return array
			 */
			public function validate_api_key( string $api_key ): array {
				return [];
			}

			/**
			 * No-op.
			 *
			 * @param string $video_id Video id.
			 *
			 * @return string
			 */
			public function get_preview_url( string $video_id ): string {
				return '';
			}

			/**
			 * No-op.
			 */
			public function register_handler(): void {}

			/**
			 * No-op.
			 *
			 * @param array  $matches Matches.
			 * @param array  $attr    Attributes.
			 * @param string $url     URL.
			 * @param array  $rawattr Raw attributes.
			 *
			 * @return string
			 */
			public function handler_callback( array $matches, array $attr, string $url, array $rawattr ): string {
				return '';
			}

			/**
			 * Expose the protected load_template() for testing.
			 *
			 * @param array $args Template args.
			 *
			 * @return string
			 */
			public function call_load_template( array $args ) {
				return $this->load_template( $args );
			}
		};
	}

	/**
	 * Existing template is rendered with collapsed whitespace and substituted values.
	 */
	public function test_load_template_renders_existing_template() {
		$provider = $this->make_provider( 'youtube' );

		$result = $provider->call_load_template(
			[
				'use_lazy_load' => false,
				'use_microdata' => false,
				'player_class'  => 'lite-youtube_16x9',
				'player_src'    => 'https://example.com/embed',
				'video_id'      => 'abc123',
				'name'          => 'Test video',
				'description'   => 'Description',
				'duration'      => 'PT1H0M0S',
				'upload_date'   => '2024-01-01T00:00:00+00:00',
				'url'           => 'https://example.com',
				'embed_url'     => 'https://example.com/embed',
				'preview_url'   => 'https://example.com/preview.jpg',
				'player_width'  => 1280,
				'player_height' => 720,
			]
		);

		$this->assertStringContainsString( 'lite-youtube_16x9', $result );
		$this->assertStringContainsString( 'Test video', $result );
		$this->assertStringNotContainsString( "\n", $result );
		$this->assertStringNotContainsString( "\t", $result );
	}

	/**
	 * Unknown provider id (no matching template file) renders to an empty string.
	 */
	public function test_load_template_returns_empty_string_for_missing_template() {
		$provider = $this->make_provider( 'does-not-exist' );

		$this->assertSame( '', $provider->call_load_template( [] ) );
	}
}
