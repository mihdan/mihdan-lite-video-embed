<?php
/**
 * Class VKTest.
 *
 * @package mihdan-lite-youtube-embed
 */

namespace Mihdan\LiteYouTubeEmbed\Providers;

use PHPUnit\Framework\TestCase;
use WP_Mock;

/**
 * Тесты для Providers\VK.
 */
final class VKTest extends TestCase {

	/**
	 * Set up WP_Mock and common stubs before each test.
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
	 * Create a VK instance with the given `access_token` value.
	 *
	 * @param string $access_token Access token.
	 *
	 * @return VK
	 */
	private function make_vk( string $access_token = '' ): VK {
		WP_Mock::userFunction( 'get_option' )
			->with( 'mlye_vk_video' )
			->andReturn( [ 'access_token' => $access_token ] );

		return new VK();
	}

	/**
	 * Returns an empty array without an HTTP request when no access token is configured.
	 */
	public function test_get_data_without_access_token_returns_empty_array() {
		$vk = $this->make_vk( '' );

		WP_Mock::userFunction( 'wp_remote_get' )->never();

		$this->assertSame( [], $vk->get_data( '-1_2' ) );
	}

	/**
	 * Returns an empty array when the API responds with a non-200 status.
	 */
	public function test_get_data_returns_empty_array_on_error_response() {
		$vk = $this->make_vk( 'TOKEN' );

		WP_Mock::userFunction( 'wp_remote_get' )->andReturn( [ 'body' => '' ] );
		WP_Mock::userFunction( 'wp_remote_retrieve_response_code' )->andReturn( 403 );

		$this->assertSame( [], $vk->get_data( '-1_2' ) );
	}

	/**
	 * Returns an empty array when the response has no items.
	 */
	public function test_get_data_returns_empty_array_when_no_items() {
		$vk = $this->make_vk( 'TOKEN' );

		$body = json_encode( [ 'response' => [ 'items' => [] ] ] );

		WP_Mock::userFunction( 'wp_remote_get' )->andReturn( [ 'body' => $body ] );
		WP_Mock::userFunction( 'wp_remote_retrieve_response_code' )->andReturn( 200 );
		WP_Mock::userFunction( 'wp_remote_retrieve_body' )->andReturn( $body );

		$this->assertSame( [], $vk->get_data( '-1_2' ) );
	}

	/**
	 * Happy path: converts duration to ISO8601 and builds an autoplay player URL.
	 */
	public function test_get_data_happy_path() {
		$vk = $this->make_vk( 'TOKEN' );

		$body = json_encode(
			[
				'response' => [
					'items' => [
						[
							'duration'    => 125,
							'title'       => 'VK video',
							'description' => 'Description',
							'date'        => 1700000000,
							'photo_1280'  => 'https://vk.com/photo.jpg',
							'player'      => 'https://vk.com/video_ext.php?oid=-1&id=2',
						],
					],
				],
			]
		);

		WP_Mock::userFunction( 'wp_remote_get' )->andReturn( [ 'body' => $body ] );
		WP_Mock::userFunction( 'wp_remote_retrieve_response_code' )->andReturn( 200 );
		WP_Mock::userFunction( 'wp_remote_retrieve_body' )->andReturn( $body );
		WP_Mock::userFunction( 'add_query_arg' )->andReturnUsing(
			static function ( $key, $value, $url ) {
				return $url . '&' . $key . '=' . $value;
			}
		);

		$data = $vk->get_data( '-1_2' );

		$this->assertSame( 'PT0H2M5S', $data['duration'] );
		$this->assertSame( 'VK video', $data['name'] );
		$this->assertStringContainsString( 'autoplay=1', $data['player_src'] );
	}

	/**
	 * Splits the video id into oid/id and builds the oEmbed player URL.
	 */
	public function test_get_fallback_data_builds_player_src_from_oid_and_id() {
		$vk = $this->make_vk( '' );

		WP_Mock::userFunction( 'get_post' )->andReturn(
			(object) [
				'post_title'   => 'Post title',
				'post_excerpt' => 'Excerpt',
			]
		);
		WP_Mock::userFunction( 'get_post_time' )->andReturn( '2024-01-01T00:00:00+00:00' );
		WP_Mock::userFunction( 'get_the_post_thumbnail_url' )->andReturn( 'https://example.com/thumb.jpg' );

		$data = $vk->get_fallback_data( '-123_456' );

		$this->assertStringContainsString( 'oid=--123', $data['player_src'] );
		$this->assertStringContainsString( 'id=456', $data['player_src'] );
	}

	/**
	 * Returns an empty string when regex matches are missing id/oid.
	 */
	public function test_handler_callback_returns_empty_string_when_matches_missing() {
		$vk = $this->make_vk( '' );

		$this->assertSame( '', $vk->handler_callback( [], [], 'https://vk.com/video-1_2', [] ) );
	}

	/**
	 * Data provider of VK/VK Video URLs that the registered scheme should recognize.
	 *
	 * @return array
	 */
	public function url_provider(): array {
		return [
			'legacy vk.com video'      => [ 'https://vk.com/video-38661454_456292170', '38661454', '456292170' ],
			'vkvideo.ru video'         => [ 'https://vkvideo.ru/video-191394096_456250182', '191394096', '456250182' ],
			'vkvideo.ru video with pl' => [ 'https://vkvideo.ru/video-205214227_456239365?pl=-205214227_-4', '205214227', '456239365' ],
			'vkvideo.ru clip'          => [ 'https://vkvideo.ru/clip-37979706_456242829', '37979706', '456242829' ],
		];
	}

	/**
	 * The registered scheme recognizes both vk.com/vkvideo.ru domains, video/clip prefixes,
	 * and a trailing query string.
	 *
	 * @dataProvider url_provider
	 *
	 * @param string $url          URL to match.
	 * @param string $expected_oid Expected "oid" named group.
	 * @param string $expected_id  Expected "id" named group.
	 */
	public function test_scheme_matches_all_supported_url_shapes( string $url, string $expected_oid, string $expected_id ) {
		$vk = $this->make_vk( '' );

		$reflection = new \ReflectionMethod( $vk, 'get_schemes' );
		$reflection->setAccessible( true );
		$schemes = $reflection->invoke( $vk );

		$matched = false;

		foreach ( $schemes as $scheme ) {
			if ( preg_match( $scheme, $url, $matches ) ) {
				$matched = true;
				$this->assertSame( $expected_oid, $matches['oid'] );
				$this->assertSame( $expected_id, $matches['id'] );
			}
		}

		$this->assertTrue( $matched, "URL should match a VK scheme: $url" );
	}
}
