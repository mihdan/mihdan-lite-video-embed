<?php
/**
 * Class VK.
 *
 * @package mihdan-lite-youtube-embed
 * @link https://dev.vk.com/ru/method/video.get
 * @link https://vkhost.github.io
 */

namespace Mihdan\LiteYouTubeEmbed\Providers;

use Mihdan\LiteYouTubeEmbed\Options;
use Mihdan\LiteYouTubeEmbed\Provider;
use Mihdan\LiteYouTubeEmbed\Utils;
use Exception;
use JsonException;

/**
 * Extend Provider class.
 */
class VK extends Provider {

	/**
	 * Schemas for VK.
	 *
	 * VK video moved from vk.com to vkvideo.ru, and clips use a "clip-" prefix
	 * instead of "video-" — both old and new URLs are supported.
	 *
	 * @link https://vk.com/video-38661454_456292170
	 * @link https://vkvideo.ru/video-38661454_456292170
	 * @link https://vkvideo.ru/clip-38661454_456292170
	 *
	 * @var array|string[]
	 */
	protected array $schemes = [
		'#https?://(?:vk\.com|vkvideo\.ru)/(?:video|clip)\-(?P<oid>[\d]+)_(?P<id>[\d]+)(?:\?.*)?$#i',
	];

	/**
	 * Provider ID.
	 *
	 * @var string
	 */
	protected string $id = 'vk-video';

	/**
	 * Provider oembed URL.
	 *
	 * @var string
	 */
	protected string $oembed_url = 'https://vk.com/video_ext.php?oid=-%d&id=%d&autoplay=1&hash=7a04b96977ca1c88&hd=1';

	/**
	 * Provider API URL.
	 *
	 * @var string
	 */
	protected string $api_url = 'https://api.vk.com/method/video.get';

	/**
	 * Constructor.
	 */
	public function __construct() {
		parent::__construct();

		$this->api_key = Options::get( 'access_token', 'mlye_vk_video' );
	}

	/**
	 * Hooks init.
	 *
	 * @return void
	 */
	public function setup_hooks(): void {
		add_action( 'init', [ $this, 'register_handler' ] );
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
	 * @param   string $video_id  Video ID.
	 *
	 * @return array
	 * @throws JsonException If the API response body is not valid JSON.
	 */
	public function get_data( string $video_id ): array {
		if ( ! $this->get_api_key() ) {
			return [];
		}

		$params = [
			'access_token' => $this->get_api_key(),
			'videos'       => $video_id,
			'v'            => '5.81',
			'count'        => 1,
		];

		$response = wp_remote_get(
			$this->get_api_url() . '?' . http_build_query( $params )
		);

		if ( wp_remote_retrieve_response_code( $response ) !== 200 ) {
			return [];
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true, 512, JSON_THROW_ON_ERROR );

		if ( ! isset( $body['response']['items'][0] ) ) {
			return [];
		}

		$data = $body['response']['items'][0];

		return [
			'duration'      => Utils::iso8601_duration( $data['duration'] ),
			'name'          => $data['title'],
			'description'   => Utils::sanitize_video_description( $data['description'] ),
			'upload_date'   => gmdate( 'c', $data['date'] ),
			'thumbnail_url' => $data['photo_1280'],
			'player_src'    => add_query_arg( 'autoplay', 1, $data['player'] ),
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
		$post = get_post();

		$upload_date   = get_post_time( 'c', false, $post, false );
		$thumbnail_url = get_the_post_thumbnail_url( $post );
		$description   = ( ! empty( $post->post_excerpt ) )
			? $post->post_excerpt
			: Options::get( 'description', 'mlye_general' );

		[ $oid, $id ] = explode( '_', $video_id );

		$player_src = sprintf(
			$this->get_oembed_url(),
			$oid,
			$id
		);

		return [
			'duration'      => 'PT00H10M00S',
			'name'          => $post->post_title,
			'description'   => Utils::sanitize_video_description( $description ),
			'upload_date'   => $upload_date,
			'thumbnail_url' => $thumbnail_url,
			'player_src'    => $player_src,
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

		if ( empty( $matches['id'] ) || empty( $matches['oid'] ) ) {
			return '';
		}

		$data = $this->get_data( '-' . $matches['oid'] . '_' . $matches['id'] );

		if ( ! $data ) {
			$data = $this->get_fallback_data( '-' . $matches['oid'] . '_' . $matches['id'] );
		}

		$player_size = explode( 'x', Options::get( 'player_size', 'mlye_general', '16x9' ) );

		$params = array(
			'use_microdata'   => ( 'yes' === Options::get( 'use_microdata', 'mlye_general' ) ),
			'use_lazy_load'   => ( 'yes' === Options::get( 'use_lazy_load', 'mlye_general' ) ),
			'preview_quality' => Options::get( 'preview_quality', 'mlye_general', 'auto' ),
			'video_id'        => $matches['id'],
			'player_width'    => in_array( $player_size[0], array( '16', '4', '9' ), true ) ? 1280 : $player_size[0],
			'player_height'   => in_array( $player_size[1], array( '9', '3', '16' ), true ) ? 720 : $player_size[1],
			'player_class'    => 'lite-youtube_' . $player_size[0] . 'x' . $player_size[1],
			'player_src'      => $data['player_src'],
			'upload_date'     => $data['upload_date'],
			'duration'        => $data['duration'],
			'url'             => $url,
			'description'     => mb_substr( $data['description'], 0, 250, 'UTF-8' ) . '...',
			'name'            => $data['name'],
			'embed_url'       => $data['player_src'],
			'preview_url'     => $data['thumbnail_url'],
		);

		return $this->load_template( $params );
	}
}
