<?php
/**
 * Class RuTubeTest.
 *
 * @package mihdan-lite-youtube-embed
 */

namespace Mihdan\LiteYouTubeEmbed\Providers;

use PHPUnit\Framework\TestCase;
use WP_Mock;

/**
 * Тесты для Providers\RuTube.
 */
final class RuTubeTest extends TestCase {

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
	 * Converts the API's raw-seconds duration to ISO8601.
	 */
	public function test_get_data_converts_duration_to_iso8601() {
		$rutube = new RuTube();

		$body = json_encode(
			[
				'duration'    => 3661,
				'title'       => 'RuTube video',
				'description' => 'Some description',
				'created_ts'  => 1700000000,
				'embed_url'   => 'https://rutube.ru/play/embed/1',
				'html'        => '<iframe></iframe>',
			]
		);

		WP_Mock::userFunction( 'wp_remote_get' )->andReturn( [ 'body' => $body ] );
		WP_Mock::userFunction( 'wp_remote_retrieve_response_code' )->andReturn( 200 );
		WP_Mock::userFunction( 'wp_remote_retrieve_body' )->andReturn( $body );

		$data = $rutube->get_data( 'some-id' );

		$this->assertSame( 'PT1H1M1S', $data['duration'] );
		$this->assertSame( 'RuTube video', $data['name'] );
	}

	/**
	 * Returns an empty array when the API responds with a non-200 status.
	 */
	public function test_get_data_returns_empty_array_on_error_response() {
		$rutube = new RuTube();

		WP_Mock::userFunction( 'wp_remote_get' )->andReturn( [ 'body' => '' ] );
		WP_Mock::userFunction( 'wp_remote_retrieve_response_code' )->andReturn( 404 );

		$this->assertSame( [], $rutube->get_data( 'missing-id' ) );
	}

	/**
	 * Returns an empty array when the oEmbed endpoint responds with a non-200 status.
	 */
	public function test_get_fallback_data_returns_empty_array_on_error_response() {
		$rutube = new RuTube();

		WP_Mock::userFunction( 'wp_remote_get' )->andReturn( [ 'body' => '' ] );
		WP_Mock::userFunction( 'wp_remote_retrieve_response_code' )->andReturn( 500 );

		$this->assertSame( [], $rutube->get_fallback_data( 'some-id' ) );
	}

	/**
	 * Falls back to oEmbed data when the main API call fails.
	 */
	public function test_handler_callback_uses_fallback_when_api_fails() {
		$rutube = new RuTube();

		WP_Mock::userFunction( 'get_option' )
			->with( 'mlye_general' )
			->andReturn(
				[
					'player_size'   => '16x9',
					'use_microdata' => 'no',
					'use_lazy_load' => 'no',
				]
			);

		$fallback_body = json_encode(
			[
				'title'         => 'Fallback title',
				'thumbnail_url' => 'https://rutube.ru/thumb.jpg',
				'author_name'   => 'Author',
				'html'          => '<iframe src="https://rutube.ru/play/embed/video-id"></iframe>',
			]
		);

		WP_Mock::userFunction( 'wp_remote_get' )->andReturn( [ 'body' => '' ] )->ordered();
		WP_Mock::userFunction( 'wp_remote_retrieve_response_code' )
			->andReturnValues( [ 404, 200 ] );
		WP_Mock::userFunction( 'wp_remote_retrieve_body' )->andReturn( $fallback_body );

		$result = $rutube->handler_callback(
			[
				'kind'     => 'video',
				'video_id' => 'video-id',
			],
			[],
			'https://rutube.ru/video/video-id/',
			[]
		);

		$this->assertStringContainsString( 'Fallback title', $result );
		$this->assertStringContainsString( 'https://rutube.ru/play/embed/video-id', $result );
	}

	/**
	 * Extracts embed_url from the oEmbed HTML snippet.
	 */
	public function test_get_fallback_data_extracts_embed_url_from_html() {
		$rutube = new RuTube();

		$body = json_encode(
			[
				'title' => 'Video',
				'html'  => '<iframe src="https://rutube.ru/play/embed/abc123" allowfullscreen></iframe>',
			]
		);

		WP_Mock::userFunction( 'wp_remote_get' )->andReturn( [ 'body' => $body ] );
		WP_Mock::userFunction( 'wp_remote_retrieve_response_code' )->andReturn( 200 );
		WP_Mock::userFunction( 'wp_remote_retrieve_body' )->andReturn( $body );

		$data = $rutube->get_fallback_data( 'abc123' );

		$this->assertSame( 'https://rutube.ru/play/embed/abc123', $data['embed_url'] );
	}

	/**
	 * Data provider of RuTube URLs and the kind/video_id they should resolve to.
	 *
	 * @return array
	 */
	public function url_provider(): array {
		return [
			'plain video' => [ 'https://rutube.ru/video/958aee8234f8415c2d0ded294906aa8c/', 'video', '958aee8234f8415c2d0ded294906aa8c' ],
			'live video'  => [ 'https://rutube.ru/live/video/9f87a9a0cecbe773be6fddcbd93585ac/', 'video', '9f87a9a0cecbe773be6fddcbd93585ac' ],
			'shorts'      => [ 'https://rutube.ru/shorts/e5f11e8598cbe408c39e7bb26be013fe/', 'shorts', 'e5f11e8598cbe408c39e7bb26be013fe' ],
			'playlist'    => [ 'https://rutube.ru/plst/930704/', 'plst', '930704' ],
		];
	}

	/**
	 * The registered scheme recognizes plain videos, live videos, shorts and playlists.
	 *
	 * @dataProvider url_provider
	 *
	 * @param string $url             URL to match.
	 * @param string $expected_kind   Expected "kind" named group.
	 * @param string $expected_id     Expected "video_id" named group.
	 */
	public function test_scheme_matches_all_supported_url_shapes( string $url, string $expected_kind, string $expected_id ) {
		$rutube = new RuTube();

		$reflection = new \ReflectionMethod( $rutube, 'get_schemes' );
		$reflection->setAccessible( true );
		$schemes = $reflection->invoke( $rutube );

		$matched = false;

		foreach ( $schemes as $scheme ) {
			if ( preg_match( $scheme, $url, $matches ) ) {
				$matched = true;
				$this->assertSame( $expected_kind, $matches['kind'] );
				$this->assertSame( $expected_id, $matches['video_id'] );
			}
		}

		$this->assertTrue( $matched, "URL should match a RuTube scheme: $url" );
	}

	/**
	 * Reads title/description/thumbnail from the playlist API and builds the embed URL.
	 */
	public function test_get_playlist_data_happy_path() {
		$rutube = new RuTube();

		$body = json_encode(
			[
				'title'            => 'My playlist',
				'description'      => '',
				'thumbnail_url'    => 'https://pic.rtbcdn.ru/playlist/thumb.jpg',
				'last_modified_ts' => '2024-03-13T12:49:31+03:00',
			]
		);

		WP_Mock::userFunction( 'wp_remote_get' )->andReturn( [ 'body' => $body ] );
		WP_Mock::userFunction( 'wp_remote_retrieve_response_code' )->andReturn( 200 );
		WP_Mock::userFunction( 'wp_remote_retrieve_body' )->andReturn( $body );

		$data = $rutube->get_playlist_data( '930704' );

		$this->assertSame( 'My playlist', $data['name'] );
		$this->assertSame( 'https://rutube.ru/play/embed/plst/930704/', $data['embed_url'] );
		$this->assertSame( '', $data['duration'] );
	}

	/**
	 * Returns an empty array when the playlist API responds with a non-200 status.
	 */
	public function test_get_playlist_data_returns_empty_array_on_error_response() {
		$rutube = new RuTube();

		WP_Mock::userFunction( 'wp_remote_get' )->andReturn( [ 'body' => '' ] );
		WP_Mock::userFunction( 'wp_remote_retrieve_response_code' )->andReturn( 404 );

		$this->assertSame( [], $rutube->get_playlist_data( 'missing' ) );
	}

	/**
	 * Routes playlist URLs to get_playlist_data() instead of get_data()/get_fallback_data().
	 */
	public function test_handler_callback_uses_playlist_data_for_plst_kind() {
		$rutube = new RuTube();

		WP_Mock::userFunction( 'get_option' )
			->with( 'mlye_general' )
			->andReturn(
				[
					'player_size'   => '16x9',
					'use_microdata' => 'no',
					'use_lazy_load' => 'no',
				]
			);

		$body = json_encode(
			[
				'title'         => 'My playlist',
				'thumbnail_url' => 'https://pic.rtbcdn.ru/playlist/thumb.jpg',
			]
		);

		WP_Mock::userFunction( 'wp_remote_get' )->andReturn( [ 'body' => $body ] );
		WP_Mock::userFunction( 'wp_remote_retrieve_response_code' )->andReturn( 200 );
		WP_Mock::userFunction( 'wp_remote_retrieve_body' )->andReturn( $body );

		$result = $rutube->handler_callback(
			[
				'kind'     => 'plst',
				'video_id' => '930704',
			],
			[],
			'https://rutube.ru/plst/930704/',
			[]
		);

		$this->assertStringContainsString( 'My playlist', $result );
		$this->assertStringContainsString( 'https://rutube.ru/play/embed/plst/930704/', $result );
	}

	/**
	 * Returns an empty string when both the main and fallback lookups fail.
	 */
	public function test_handler_callback_returns_empty_string_when_data_unavailable() {
		$rutube = new RuTube();

		WP_Mock::userFunction( 'wp_remote_get' )->andReturn( [ 'body' => '' ] );
		WP_Mock::userFunction( 'wp_remote_retrieve_response_code' )->andReturn( 404 );

		$result = $rutube->handler_callback(
			[
				'kind'     => 'video',
				'video_id' => 'missing-id',
			],
			[],
			'https://rutube.ru/video/missing-id/',
			[]
		);

		$this->assertSame( '', $result );
	}
}
