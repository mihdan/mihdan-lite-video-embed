<?php
/**
 * Class RuTube.
 *
 * @package mihdan-lite-youtube-embed
 */

namespace Mihdan\LiteYouTubeEmbed\Providers;

use Mihdan\LiteYouTubeEmbed\Options;
use Mihdan\LiteYouTubeEmbed\Provider;
use Exception;
use Mihdan\LiteYouTubeEmbed\Utils;

/**
 * Extend Provider class.
 */
class RuTube extends Provider {

	/**
	 * Schemas for RuTube.
	 *
	 * Matches plain videos, live streams (an extra "live/" prefix on top of "video"),
	 * shorts, and playlists ("plst", handled separately — see get_playlist_data()).
	 *
	 * @var array|string[]
	 */
	protected array $schemes = [
		'#https?://(?:www\.)?rutube\.ru/(?:live/)?(?P<kind>play|video|shorts|plst)/(?P<video_id>[^/]+)/?$#i',
	];

	/**
	 * Playlist embed URL template.
	 *
	 * RuTube has no oEmbed/metadata API for playlists, so this is built directly.
	 *
	 * @var string
	 */
	protected string $playlist_embed_url = 'https://rutube.ru/play/embed/plst/%s/';

	/**
	 * Playlist metadata API URL.
	 *
	 * @var string
	 */
	protected string $playlist_api_url = 'https://rutube.ru/api/playlist/custom/%s/';

	/**
	 * Provider ID.
	 *
	 * @var string
	 */
	protected string $id = 'rutube';

	/**
	 * Provider oembed URL.
	 *
	 * @var string
	 */
	protected string $oembed_url = 'https://rutube.ru/api/oembed/?url=https://rutube.ru/video/%s/';

	/**
	 * Provider API URL.
	 *
	 * @var string
	 */
	protected string $api_url = 'https://rutube.ru/api/video/%s';

	/**
	 * Hooks init.
	 *
	 * @return void
	 */
	public function setup_hooks(): void {
		add_action( 'init', [ $this, 'register_handler' ] );
		add_filter( 'mlye/rutube/render', [ $this, 'auto_embed_content' ] );
	}

	/**
	 * Auto embed provider content.
	 *
	 * @param string $content Content.
	 *
	 * @return string
	 */
	public function auto_embed_content( string $content ): string {
		return $content;
	}

	/**
	 * Get data from API by Video ID.
	 *
	 * @param string $video_id Video ID.
	 *
	 * @return array
	 */
	public function get_data( string $video_id ): array {

		$url = sprintf( $this->get_api_url(), $video_id );

		$request = wp_remote_get(
			$url,
			$this->get_http_args()
		);

		if ( wp_remote_retrieve_response_code( $request ) !== 200 ) {
			return [];
		}

		$body = json_decode( wp_remote_retrieve_body( $request ), true );

		return [
			'duration'      => Utils::iso8601_duration( (int) $body['duration'] ),
			'name'          => $body['title'],
			'description'   => Utils::sanitize_video_description( $body['description'] ),
			'upload_date'   => $body['created_ts'],
			'thumbnail_url' => $body['thumbnail_url'] ?? '',
			'author_name'   => $body['author']['name'] ?? '',
			'author_url'    => $body['author']['site_url'] ?? '',
			'type'          => $body['type'] ?? 'video',
			'html'          => $body['html'],
			'embed_url'     => $body['embed_url'],
		];
	}

	/**
	 * Get fallback data from API by Video ID.
	 *
	 * @param string $video_id Video ID.
	 *
	 * @return array
	 */
	public function get_fallback_data( string $video_id ): array {

		$url = sprintf( $this->get_oembed_url(), $video_id );

		$request = wp_remote_get(
			$url,
			$this->get_http_args()
		);

		if ( wp_remote_retrieve_response_code( $request ) !== 200 ) {
			return [];
		}

		$body = json_decode( wp_remote_retrieve_body( $request ), true );

		preg_match( '#src="([^"]+)"#', $body['html'] ?? '', $matches );

		return [
			'duration'      => '',
			'name'          => $body['title'],
			'description'   => '',
			'upload_date'   => '',
			'thumbnail_url' => $body['thumbnail_url'] ?? '',
			'author_name'   => $body['author_name'] ?? '',
			'author_url'    => $body['author_url'] ?? '',
			'type'          => $body['type'] ?? 'video',
			'html'          => $body['html'],
			'embed_url'     => $matches[1] ?? '',
		];
	}

	/**
	 * Get playlist data by playlist ID.
	 *
	 * RuTube has no oEmbed/video API for playlists, so metadata comes from the
	 * playlist API and the player URL is built directly.
	 *
	 * @param string $playlist_id Playlist ID.
	 *
	 * @return array
	 */
	public function get_playlist_data( string $playlist_id ): array {

		$url = sprintf( $this->playlist_api_url, $playlist_id );

		$request = wp_remote_get(
			$url,
			$this->get_http_args()
		);

		if ( wp_remote_retrieve_response_code( $request ) !== 200 ) {
			return [];
		}

		$body = json_decode( wp_remote_retrieve_body( $request ), true );

		return [
			'duration'      => '',
			'name'          => $body['title'] ?? '',
			'description'   => Utils::sanitize_video_description( $body['description'] ?? '' ),
			'upload_date'   => $body['last_modified_ts'] ?? '',
			'thumbnail_url' => $body['thumbnail_url'] ?? '',
			'embed_url'     => sprintf( $this->playlist_embed_url, $playlist_id ),
		];
	}

	/**
	 * Validate API Key.
	 *
	 * @param string $api_key key for checking.
	 *
	 * @return array
	 */
	public function validate_api_key( string $api_key ): array {
		return array(
			'success' => false,
			'data'    => '',
		);
	}

	/**
	 * Get preview URL.
	 *
	 * @param string $video_id Video ID.
	 *
	 * @return string
	 */
	public function get_preview_url( string $video_id ): string {
		return '';
	}

	/**
	 * Register new video provider.
	 *
	 * @return void
	 */
	public function register_handler(): void {

		foreach ( $this->get_schemes() as $scheme ) {
			wp_embed_register_handler(
				$this->get_handler_id(),
				$scheme,
				[ $this, 'handler_callback' ]
			);
		}
	}

	/**
	 * Callback for register new video provider.
	 *
	 * @param array  $matches Matches.
	 * @param array  $attr    Shortcode attributes. Optional.
	 * @param string $url     The URL attempting to be embedded.
	 * @param array  $rawattr Raw shortcode attributes. Optional.
	 *
	 * @return string
	 * @throws Exception Exception.
	 */
	public function handler_callback( array $matches, array $attr, string $url, array $rawattr ): string {
		$video_id = $matches['video_id'];

		if ( 'plst' === $matches['kind'] ) {
			$data = $this->get_playlist_data( $video_id );
		} else {
			$data = $this->get_data( $video_id );

			if ( ! $data ) {
				$data = $this->get_fallback_data( $video_id );
			}
		}

		if ( ! $data ) {
			return '';
		}

		$player_size = explode( 'x', Options::get( 'player_size', 'mlye_general', '16x9' ) );

		$params = array(
			'use_microdata'   => ( 'yes' === Options::get( 'use_microdata', 'mlye_general' ) ),
			'use_lazy_load'   => ( 'yes' === Options::get( 'use_lazy_load', 'mlye_general' ) ),
			'preview_quality' => Options::get( 'preview_quality', 'mlye_general', 'auto' ),
			'video_id'        => $video_id,
			'player_width'    => in_array( $player_size[0], array( '16', '4', '9' ), true ) ? 1280 : $player_size[0],
			'player_height'   => in_array( $player_size[1], array( '9', '3', '16' ), true ) ? 720 : $player_size[1],
			'player_class'    => 'lite-youtube_' . $player_size[0] . 'x' . $player_size[1],
			'player_src'      => $data['embed_url'],
			'upload_date'     => $data['upload_date'],
			'duration'        => $data['duration'],
			'url'             => $url,
			'description'     => mb_substr( $data['description'], 0, 250, 'UTF-8' ) . '...',
			'name'            => $data['name'],
			'embed_url'       => $data['embed_url'],
			'preview_url'     => $data['thumbnail_url'],
		);

		return $this->load_template( $params );
	}
}
