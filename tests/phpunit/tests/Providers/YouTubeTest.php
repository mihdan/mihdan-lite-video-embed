<?php
/**
 * Class YouTubeTest.
 *
 * @package mihdan-lite-youtube-embed
 */

namespace Mihdan\LiteYouTubeEmbed\Providers;

use PHPUnit\Framework\TestCase;
use WP_Mock;

/**
 * Тесты для Providers\YouTube.
 */
final class YouTubeTest extends TestCase {

	/**
	 * Set up WP_Mock and common stubs before each test.
	 */
	public function setUp(): void {
		WP_Mock::setUp();

		foreach ( [ 'esc_attr', 'esc_html', 'esc_url', '__', 'esc_html__' ] as $function ) {
			WP_Mock::userFunction( $function )->andReturnUsing(
				static function ( $value ) {
					return $value;
				}
			);
		}

		WP_Mock::userFunction( 'wp_strip_all_tags' )->andReturnUsing(
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
	 * Create a YouTube instance with the given `mlye_general` option values.
	 *
	 * @param array $options Option values keyed by field id.
	 *
	 * @return YouTube
	 */
	private function make_youtube( array $options = [] ): YouTube {
		$options = array_merge(
			[
				'api_key' => '',
				'timeout' => 5,
			],
			$options
		);

		WP_Mock::userFunction( 'get_option' )
			->with( 'mlye_general' )
			->andReturn( $options );

		return new YouTube();
	}

	/**
	 * A non-YouTube oEmbed provider is passed through untouched.
	 */
	public function test_oembed_html_ignores_other_providers() {
		$youtube = $this->make_youtube();

		$data = (object) [ 'provider_name' => 'Vimeo' ];

		$this->assertSame( 'original', $youtube->oembed_html( 'original', $data, 'https://vimeo.com/1' ) );
	}

	/**
	 * HTML without a recognizable embed src is passed through untouched.
	 */
	public function test_oembed_html_ignores_html_without_embed_src() {
		$youtube = $this->make_youtube();

		$data = (object) [
			'provider_name' => 'YouTube',
			'html'          => '<p>no iframe here</p>',
		];

		$this->assertSame( 'original', $youtube->oembed_html( 'original', $data, 'https://youtube.com/watch?v=abc' ) );
	}

	/**
	 * Happy path: video data comes from the YouTube Data API and duration is valid ISO8601.
	 */
	public function test_oembed_html_renders_template_with_api_data() {
		$youtube = $this->make_youtube(
			[
				'api_key'            => 'FAKE_KEY',
				'timeout'            => 5,
				'player_size'        => '16x9',
				'hide_related_video' => 'no',
				'use_microdata'      => 'yes',
				'use_lazy_load'      => 'no',
				'preview_quality'    => 'hqdefault',
			]
		);

		WP_Mock::userFunction( 'get_post' )->andReturn(
			(object) [
				'post_title'   => 'Post Title',
				'post_excerpt' => '',
			]
		);
		WP_Mock::userFunction( 'get_post_time' )->andReturn( '2024-01-01T00:00:00+00:00' );
		WP_Mock::userFunction( 'wp_parse_url' )->andReturn( 'autoplay=1' );

		$api_response_body = json_encode(
			[
				'items' => [
					[
						'contentDetails' => [ 'duration' => 'PT5M30S' ],
						'snippet'        => [
							'title'       => 'API Video Title',
							'description' => 'API description',
							'publishedAt' => '2024-02-02T00:00:00Z',
						],
					],
				],
			]
		);

		WP_Mock::userFunction( 'wp_remote_get' )->andReturn( [ 'body' => $api_response_body ] );
		WP_Mock::userFunction( 'wp_remote_retrieve_response_code' )->andReturn( 200 );
		WP_Mock::userFunction( 'wp_remote_retrieve_body' )->andReturn( $api_response_body );

		$data = (object) [
			'provider_name' => 'YouTube',
			'html'          => '<iframe src="https://www.youtube.com/embed/abcDEFghijk?feature=oembed"></iframe>',
		];

		$result = $youtube->oembed_html( 'original', $data, 'https://youtube.com/watch?v=abcDEFghijk' );

		$this->assertStringContainsString( 'API Video Title', $result );
		$this->assertStringContainsString( 'PT5M30S', $result );
	}

	/**
	 * Reports failure when wp_remote_get() returns a WP_Error.
	 */
	public function test_validate_api_key_fails_on_wp_error() {
		$youtube = $this->make_youtube();

		$error = \Mockery::mock( 'WP_Error' );
		$error->shouldReceive( 'get_error_message' )->andReturn( 'connection failed' );

		WP_Mock::userFunction( 'wp_remote_get' )->andReturn( $error );
		WP_Mock::userFunction( 'is_wp_error' )->andReturn( true );

		$result = $youtube->validate_api_key( 'some-key' );

		$this->assertFalse( $result['success'] );
		$this->assertSame( 'connection failed', $result['data'] );
	}

	/**
	 * Reports failure when the response body is empty.
	 */
	public function test_validate_api_key_fails_on_empty_body() {
		$youtube = $this->make_youtube();

		WP_Mock::userFunction( 'wp_remote_get' )->andReturn( [ 'body' => '' ] );
		WP_Mock::userFunction( 'is_wp_error' )->andReturn( false );
		WP_Mock::userFunction( 'wp_remote_retrieve_body' )->andReturn( '' );

		$result = $youtube->validate_api_key( 'some-key' );

		$this->assertFalse( $result['success'] );
	}

	/**
	 * Reports failure when the API returns an error payload.
	 */
	public function test_validate_api_key_fails_on_api_error() {
		$youtube = $this->make_youtube();

		$body = json_encode( [ 'error' => [ 'message' => 'API key not valid' ] ] );

		WP_Mock::userFunction( 'wp_remote_get' )->andReturn( [ 'body' => $body ] );
		WP_Mock::userFunction( 'is_wp_error' )->andReturn( false );
		WP_Mock::userFunction( 'wp_remote_retrieve_body' )->andReturn( $body );

		$result = $youtube->validate_api_key( 'bad-key' );

		$this->assertFalse( $result['success'] );
		$this->assertSame( 'API key not valid', $result['data'] );
	}

	/**
	 * Reports success for a valid response.
	 */
	public function test_validate_api_key_succeeds() {
		$youtube = $this->make_youtube();

		$body = json_encode( [ 'items' => [] ] );

		WP_Mock::userFunction( 'wp_remote_get' )->andReturn( [ 'body' => $body ] );
		WP_Mock::userFunction( 'is_wp_error' )->andReturn( false );
		WP_Mock::userFunction( 'wp_remote_retrieve_body' )->andReturn( $body );

		$result = $youtube->validate_api_key( 'good-key' );

		$this->assertTrue( $result['success'] );
	}

	/**
	 * Does nothing in wp-admin.
	 */
	public function test_parse_iframe_skips_admin() {
		$youtube = $this->make_youtube();

		WP_Mock::userFunction( 'is_admin' )->andReturn( true );

		$this->assertSame( '<iframe></iframe>', $youtube->parse_iframe( '<iframe></iframe>' ) );
	}

	/**
	 * Does nothing when the iframe_support option is disabled.
	 */
	public function test_parse_iframe_skips_when_disabled() {
		$youtube = $this->make_youtube( [ 'iframe_support' => 'no' ] );

		WP_Mock::userFunction( 'is_admin' )->andReturn( false );

		$content = '<iframe src="https://www.youtube.com/embed/abcDEFghijk"></iframe>';

		$this->assertSame( $content, $youtube->parse_iframe( $content ) );
	}

	/**
	 * Rewrites a pasted YouTube iframe into an auto-embeddable URL.
	 */
	public function test_parse_iframe_rewrites_iframe_when_enabled() {
		$youtube = $this->make_youtube( [ 'iframe_support' => 'yes' ] );

		WP_Mock::userFunction( 'is_admin' )->andReturn( false );

		// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- test double for the global $wp_embed.
		$GLOBALS['wp_embed'] = new class() {
			/**
			 * Fake autoembed() that returns the content unchanged.
			 *
			 * @param string $content Content.
			 *
			 * @return string
			 */
			public function autoembed( $content ) {
				return $content;
			}
		};

		$content = '<iframe src="https://www.youtube.com/embed/abcDEFghijk"></iframe>';
		$result  = $youtube->parse_iframe( $content );

		$this->assertStringContainsString( 'https://www.youtube.com/watch?v=abcDEFghijk', $result );

		unset( $GLOBALS['wp_embed'] );
	}

	/**
	 * With an explicit quality, does not perform any HTTP request.
	 */
	public function test_get_preview_url_with_explicit_quality() {
		$youtube = $this->make_youtube( [ 'preview_quality' => 'hqdefault' ] );

		WP_Mock::userFunction( 'wp_remote_head' )->never();

		$url = $youtube->get_preview_url( 'abcDEFghijk' );

		$this->assertSame( 'https://i.ytimg.com/vi/abcDEFghijk/hqdefault.jpg', $url );
	}

	/**
	 * With quality=auto, returns the first thumbnail size that responds with HTTP 200.
	 */
	public function test_get_preview_url_auto_returns_first_available_size() {
		$youtube = $this->make_youtube( [ 'preview_quality' => 'auto' ] );

		WP_Mock::userFunction( 'wp_remote_head' )->andReturn( [] );
		WP_Mock::userFunction( 'wp_remote_retrieve_response_code' )
			->andReturnValues( [ 404, 200 ] );

		$url = $youtube->get_preview_url( 'abcDEFghijk' );

		$this->assertSame( 'https://i.ytimg.com/vi/abcDEFghijk/sddefault.jpg', $url );
	}

	/**
	 * With quality=auto, falls back to sddefault when nothing responds with HTTP 200.
	 */
	public function test_get_preview_url_auto_falls_back_when_nothing_available() {
		$youtube = $this->make_youtube( [ 'preview_quality' => 'auto' ] );

		WP_Mock::userFunction( 'wp_remote_head' )->andReturn( [] );
		WP_Mock::userFunction( 'wp_remote_retrieve_response_code' )->andReturn( 404 );

		$url = $youtube->get_preview_url( 'abcDEFghijk' );

		$this->assertSame( 'https://i.ytimg.com/vi/abcDEFghijk/sddefault.jpg', $url );
	}
}
