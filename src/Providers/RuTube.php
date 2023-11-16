<?php
/**
 * Class RuTube.
 *
 * @package mihdan-lite-youtube-embed
 */

namespace Mihdan\LiteYouTubeEmbed\Providers;

use Latte\Engine as Latte;
use Mihdan\LiteYouTubeEmbed\Options;
use Mihdan\LiteYouTubeEmbed\Provider;
use Mihdan\LiteYouTubeEmbed\Utils;
use Exception;

/**
 * Extend Provider class.
 */
class RuTube extends Provider {
	/**
	 * Latte instance.
	 *
	 * @var Latte
	 */
	private Latte $latte;

	/**
	 * Shemas for RuTube.
	 *
	 * @var array|string[]
	 */
	protected array $schemes = [
		'#https?://(?:www\.)?rutube\.ru/(?:play|video|plst)/([^/]+)/?$#i',
	];

	/**
	 * Provider ID.
	 *
	 * @var string
	 */
	protected string $id = 'mlye-rutube';

	/**
	 * Provider oembed URL.
	 *
	 * @var string
	 */
	protected string $oembed_url = 'https://rutube.ru/api/oembed/?url=https://rutube.ru/video/%s/';

	/**
	 * Pr0vider API URL.
	 *
	 * @var string
	 */
	protected string $api_url = 'https://rutube.ru/api/video/%s';

	/**
	 * Provider iframe template.
	 *
	 * @var string
	 */
	protected string $template = '<iframe width="720" height="405" src="https://rutube.ru/play/embed/%s" frameBorder="0" allow="clipboard-write; autoplay" webkitAllowFullScreen mozallowfullscreen allowFullScreen></iframe>';

	/**
	 * Constructor.
	 *
	 * @param Latte $latte Latte instance.
	 */
	public function __construct( Latte $latte ) {
		$this->latte = $latte;
	}

	/**
	 * Hooks init.
	 *
	 * @return void
	 */
	public function setup_hooks(): void {
		add_action( 'init', [ $this, 'register_handler' ] );
		add_action( 'mlye/rutube/render', [ $this, 'auto_embed_content' ] );
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
			'duration'      => $body['duration'],
			'name'          => $body['title'],
			'description'   => $this->sanitize_video_description( $body['description'] ),
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
				$this->get_id(),
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
		$data = $this->get_data( $matches[1] );

		if ( ! $data ) {
			$data = $this->get_fallback_data( $matches[1] );
		}

		$video_id = $matches[1];

		$player_size = explode( 'x', Options::get( 'player_size', 'mlye_general', '16x9' ) );

		$player_parameters = '';

		$params = array(
			'use_microdata'     => ( 'yes' === Options::get( 'use_microdata', 'mlye_general' ) ),
			'use_lazy_load'     => ( 'yes' === Options::get( 'use_lazy_load', 'mlye_general' ) ),
			'preview_quality'   => Options::get( 'preview_quality', 'mlye_general', 'auto' ),
			'video_id'          => $video_id,
			'player_width'      => in_array( $player_size[0], array( '16', '4', '9' ), true ) ? 1280 : $player_size[0],
			'player_height'     => in_array( $player_size[1], array( '9', '3', '16' ), true ) ? 720 : $player_size[1],
			'player_class'      => 'lite-youtube_' . $player_size[0] . 'x' . $player_size[1],
			'player_parameters' => $player_parameters,
			'upload_date'       => $data['upload_date'],
			'duration'          => $data['duration'],
			'url'               => $url,
			'description'       => mb_substr( $data['description'], 0, 250, 'UTF-8' ) . '...',
			'name'              => $data['name'],
			'embed_url'         => $data['embed_url'],
			'preview_url'       => $data['thumbnail_url'],
		);

		$render = $this->latte->renderToString(
			Utils::get_templates_path() . '/template-video.latte',
			$params
		);

		return $render;
	}

}