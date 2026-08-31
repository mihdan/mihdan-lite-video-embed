<?php
/**
 * Class YouTube.
 *
 * @package mihdan-lite-youtube-embed
 */

namespace Mihdan\LiteYouTubeEmbed\Providers;

use Mihdan\LiteYouTubeEmbed\Provider;
use Mihdan\LiteYouTubeEmbed\Options;
use Exception;
use Mihdan\LiteYouTubeEmbed\Utils;

/**
 * Extend Provider.
 */
class YouTube extends Provider {

	/**
	 * Pattern for parsing YouTube iframe
	 *
	 * @link https://regexr.com/5hocf
	 */
	const IFRAME_PATTERN = '#<iframe\s.*?src="(?:https?:)?\/\/(?:www\.|m\.)?(?:youtu\.be\/|youtube\.com(?:\/embed\/|\/v\/|\/watch\?v=))([\w\-]{10,12})(?:\?[\w\-]+=[\w\-]+)?"(?:[^>]+)?><\/iframe>#si';

	/**
	 * Replacement for parsing YouTube iframe
	 */
	const IFRAME_REPLACEMENT = 'https://www.youtube.com/watch?v=$1';

	/**
	 * Validate key URL.
	 */
	const VALIDATE_KEY_URL = 'https://www.googleapis.com/youtube/v3/search?part=snippet&q=YouTube+Data+API&type=video&key=%s';

	/**
	 * Content details URL.
	 */
	const CONTENT_DETAILS_URL = 'https://www.googleapis.com/youtube/v3/videos?id=%s&key=%s&part=contentDetails,snippet';

	/**
	 * Simple content URL.
	 */
	const SIMPLE_CONTENT_URL = 'https://www.youtube.com/oembed?url=youtube.com/watch?v=%s';

	/**
	 * Public playlist feed URL — no API key required.
	 */
	const PLAYLIST_FEED_URL = 'https://www.youtube.com/feeds/videos.xml?playlist_id=%s';

	/**
	 * YouTube preview URL template.
	 */
	const PREVIEW_URL = 'https://i.ytimg.com/vi/%s/%s.jpg';

	/**
	 * Provider ID.
	 *
	 * @var string
	 */
	protected string $id = 'youtube';

	/**
	 * API key.
	 *
	 * @var string|mixed
	 */
	protected string $api_key;

	/**
	 * HTTP timeout.
	 *
	 * @var int
	 */
	private $timeout;

	/**
	 * Constructor.
	 */
	public function __construct() {
		parent::__construct();

		$this->api_key = Options::get( 'api_key', 'mlye_general' );
		$this->timeout = Options::get( 'timeout', 'mlye_general' );
	}

	/**
	 * Init hooks.
	 *
	 * @return void
	 */
	public function setup_hooks(): void {
		add_filter( 'the_content', array( $this, 'parse_iframe' ) );
		add_action( 'init', [ $this, 'remove_provider' ] );
		add_filter( 'pre_update_option_mlye_general', array( $this, 'maybe_validate_api_key' ), 10, 2 );
		add_filter( 'oembed_remote_get_args', array( $this, 'oembed_remote_set_timeout' ), 10, 2 );
		add_filter( 'oembed_dataparse', array( $this, 'oembed_html' ), 100, 3 );
		add_filter( 'mlye/youtube/render', [ $this, 'auto_embed_content' ] );
		add_filter( 'oembed_ttl', array( $this, 'shorten_playlist_ttl' ), 10, 2 );
	}

	/**
	 * Playlists change over time (new videos added) — cache them for a shorter time than
	 * WordPress's default 24h so a playlist embed picks up its latest video sooner.
	 *
	 * @param int    $ttl Cache TTL in seconds.
	 * @param string $url The oEmbed source URL being cached.
	 *
	 * @return int
	 */
	public function shorten_playlist_ttl( $ttl, $url ) {
		if ( false === strpos( $url, 'youtube.com' ) || false === strpos( $url, 'list=' ) ) {
			return $ttl;
		}

		return HOUR_IN_SECONDS;
	}

	/**
	 * Парсит переданный текст, делая auto-embed.
	 *
	 * @param string $content Текст для парсинга.
	 *
	 * @return string
	 */
	public function auto_embed_content( string $content ): string {
		global $wp_embed;

		$content = preg_replace(
			self::IFRAME_PATTERN,
			self::IFRAME_REPLACEMENT,
			$content
		);

		return $wp_embed->autoembed( $content );
	}

	/**
	 * Change oembed HTML.
	 *
	 * @link https://wp-kama.ru/hook/oembed_dataparse
	 * @link https://developers.google.com/search/docs/data-types/video
	 *
	 * @param string $html The returned oEmbed HTML.
	 * @param object $data A data object result from an oEmbed provider.
	 * @param string $url  The URL of the content to be embedded.
	 *
	 * @return string
	 */
	public function oembed_html( $html, $data, $url ) {
		if ( 'YouTube' !== $data->provider_name ) {
			return $html;
		}
		preg_match( '#src="(.*?embed\/([^\?]+).*?)"#', $data->html, $matches );

		if ( ! $matches ) {
			return $html;
		}

		$video_id  = $matches[2];
		$embed_url = $matches[1];

		$player_parameters = wp_parse_url( $embed_url, PHP_URL_QUERY );

		$player_size = explode( 'x', Options::get( 'player_size', 'mlye_general', '16x9' ) );

		if ( 'yes' === Options::get( 'hide_related_video', 'mlye_general' ) ) {
			$player_parameters = add_query_arg(
				[
					'rel'            => 0,
					'showinfo'       => 0,
					'modestbranding' => 1,
				],
				$player_parameters
			);
		}

		// A playlist embed has no single video ID to look up — use the oEmbed data we already have,
		// falling back to the title of its first (default) video, fetched from the public playlist feed.
		if ( 'videoseries' === $video_id ) {
			preg_match( '/(?:^|&)list=([^&]+)/', $player_parameters, $list_matches );
			$playlist_id  = $list_matches[1] ?? '';
			$first_video  = $playlist_id ? $this->get_playlist_first_video( $playlist_id ) : [
				'title'    => '',
				'video_id' => '',
			];
			$video_name   = $first_video['title'] ? $first_video['title'] : $data->title;
			$thumbnail_id = $first_video['video_id'];

			$post = get_post();
			$api  = [
				'duration'    => 'PT00H10M00S',
				'name'        => $video_name,
				'description' => Utils::sanitize_video_description( $video_name ),
				'upload_date' => get_post_time( 'c', false, $post, false ),
			];

			if ( $thumbnail_id ) {
				$preview_url    = $this->get_preview_url( $thumbnail_id );
				$preview_srcset = sprintf(
					'https://i.ytimg.com/vi/%1$s/mqdefault.jpg 640w, https://i.ytimg.com/vi/%1$s/hqdefault.jpg 920w, https://i.ytimg.com/vi/%1$s/maxresdefault.jpg 1280w',
					$thumbnail_id
				);
			} else {
				$preview_url    = ! empty( $data->thumbnail_url ) ? $data->thumbnail_url : '';
				$preview_srcset = $preview_url ? esc_url( $preview_url ) . ' 1280w' : '';
			}
		} else {
			// Get duration from API.
			$api            = $this->get_data_from_api( $video_id );
			$preview_url    = $this->get_preview_url( $video_id );
			$preview_srcset = sprintf(
				'https://i.ytimg.com/vi/%1$s/mqdefault.jpg 640w, https://i.ytimg.com/vi/%1$s/hqdefault.jpg 920w, https://i.ytimg.com/vi/%1$s/maxresdefault.jpg 1280w',
				$video_id
			);
		}

		$params = array(
			'use_microdata'   => ( 'yes' === Options::get( 'use_microdata', 'mlye_general' ) ),
			'use_lazy_load'   => ( 'yes' === Options::get( 'use_lazy_load', 'mlye_general' ) ),
			'preview_quality' => Options::get( 'preview_quality', 'mlye_general', 'auto' ),
			'video_id'        => $video_id,
			'player_width'    => in_array( $player_size[0], array( '16', '4', '9' ), true ) ? 1280 : $player_size[0],
			'player_height'   => in_array( $player_size[1], array( '9', '3', '16' ), true ) ? 720 : $player_size[1],
			'player_class'    => 'lite-youtube_' . $player_size[0] . 'x' . $player_size[1],
			'player_src'      => sprintf( 'https://www.youtube-nocookie.com/embed/%s?autoplay=1&%s', $video_id, $player_parameters ),
			'upload_date'     => $api['upload_date'],
			'duration'        => $api['duration'],
			'url'             => $url,
			'description'     => mb_substr( $api['description'], 0, 250, 'UTF-8' ) . '...',
			'name'            => $api['name'],
			'embed_url'       => $embed_url,
			'preview_url'     => $preview_url,
			'preview_srcset'  => $preview_srcset,
		);

		return $this->load_template( $params );
	}

	/**
	 * Oembed remote get set request timeout.
	 *
	 * @param array  $args Array of default arguments.
	 * @param string $url  Embed URL with args.
	 *
	 * @return array
	 */
	public function oembed_remote_set_timeout( $args, $url ) {
		if ( false === strpos( $url, 'youtube' ) ) {
			return $args;
		}

		$args['timeout'] = $this->get_timeout();

		return $args;
	}

	/**
	 * Get HTTP timeout.
	 *
	 * @return int
	 */
	public function get_timeout() {
		return $this->timeout;
	}

	/**
	 * Trigger for clear cache via button in settings page.
	 *
	 * @param array $value     New value.
	 * @param array $old_value Old value.
	 *
	 * @return array
	 */
	public function maybe_validate_api_key( $value, $old_value ) {

		if ( empty( $value['api_key'] ) ) {
			return $value;
		}

		if ( $value['api_key'] === $this->get_api_key() ) {
			return $value;
		}

		$response = $this->validate_api_key( $value['api_key'] );

		if ( true === $response['success'] ) {
			add_settings_error( 'general', 'api_key_valid', $response['data'], 'success' );
		} else {
			add_settings_error( 'general', 'api_key_invalid', $response['data'], 'error' );
		}

		return $value;
	}

	/**
	 * Validate API key.
	 *
	 * @param string $api_key API key.
	 *
	 * @link https://stackoverflow.com/questions/21096602/using-youtube-v3-api-key/21117446#21117446
	 * @return array
	 */
	public function validate_api_key( string $api_key ): array {
		$request = sprintf( self::VALIDATE_KEY_URL, $api_key );
		$request = wp_remote_get( $request, array( 'timeout' => $this->get_timeout() ) );

		if ( is_wp_error( $request ) ) {
			return array(
				'success' => false,
				'data'    => $request->get_error_message(),
			);
		}

		$body = wp_remote_retrieve_body( $request );

		if ( ! $body ) {
			return array(
				'success' => false,
				'data'    => __( 'API key is invalid: Response is empty', 'mihdan-lite-youtube-embed' ),
			);
		}

		$body = json_decode( $body, true );

		if ( ! empty( $body['error'] ) ) {
			return array(
				'success' => false,
				'data'    => $body['error']['message'],
			);
		}

		return array(
			'success' => true,
			'data'    => __( 'API key is valid.', 'mihdan-lite-youtube-embed' ),
		);
	}

	/**
	 * Replace iframe with custom tag.
	 *
	 * @param string $content Post content.
	 *
	 * @return string
	 */
	public function parse_iframe( $content ): string {
		global $wp_embed;

		if ( is_admin() ) {
			return $content;
		}

		if ( 'yes' !== Options::get( 'iframe_support', 'mlye_general', 'no' ) ) {
			return $content;
		}

		// Fix breaking layout.
		$content = str_replace(
			[ '<p><iframe', '</iframe></p>' ],
			[ '<iframe', '</iframe>' ],
			$content
		);

		return $wp_embed->autoembed(
			preg_replace(
				self::IFRAME_PATTERN,
				PHP_EOL . self::IFRAME_REPLACEMENT . PHP_EOL,
				$content
			)
		);
	}

	/**
	 * Get video data via the YouTube Data API, or fall back to post data.
	 *
	 * @param string $video_id Video ID.
	 *
	 * @return array
	 */
	private function get_data_from_api( $video_id ) {
		$api_key = $this->get_api_key();

		// Default data.
		$post        = get_post();
		$duration    = 'PT00H10M00S';
		$upload_date = get_post_time( 'c', false, $post, false );
		$name        = $post->post_title;

		$description = ( ! empty( $post->post_excerpt ) )
			? $post->post_excerpt
			: Options::get( 'description', 'mlye_general' );

		$result = [
			'duration'    => $duration,
			'name'        => $name,
			'description' => Utils::sanitize_video_description( $description ),
			'upload_date' => $upload_date,
		];

		if ( $api_key ) {
			$request = sprintf( self::CONTENT_DETAILS_URL, $video_id, $api_key );
			$request = wp_remote_get( $request, array( 'timeout' => $this->get_timeout() ) );

			if ( wp_remote_retrieve_response_code( $request ) === 200 ) {
				$body = wp_remote_retrieve_body( $request );
				$body = json_decode( $body, false );

				if ( ! empty( $body->items[0] ) ) {
					$content_details = $body->items[0]->contentDetails;
					$snippet         = $body->items[0]->snippet;

					$result = [
						'duration'    => $content_details->duration,
						'name'        => $snippet->title,
						'description' => Utils::sanitize_video_description( $snippet->description ),
						'upload_date' => $snippet->publishedAt, // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- external API field name.
					];
				}
			}
		} else {
			$url     = sprintf( self::SIMPLE_CONTENT_URL, $video_id );
			$request = wp_remote_get( $url, array( 'timeout' => $this->get_timeout() ) );
			$body    = wp_remote_retrieve_body( $request );

			if ( $body ) {
				$body   = json_decode( $body, false );
				$result = wp_parse_args( [ 'name' => $body->title ], $result );

				if ( empty( $result['description'] ) ) {
					$result['description'] = $result['name'];
				}
			}
		}

		return $result;
	}

	/**
	 * Get the title and video ID of a playlist's first (default) video from YouTube's public playlist feed.
	 *
	 * No API key required — this is a public Atom feed, unlike the Data API used elsewhere.
	 *
	 * @param string $playlist_id Playlist ID.
	 *
	 * @return array{title: string, video_id: string} Empty strings if unavailable.
	 */
	private function get_playlist_first_video( string $playlist_id ): array {
		$empty = [
			'title'    => '',
			'video_id' => '',
		];

		$request = sprintf( self::PLAYLIST_FEED_URL, $playlist_id );
		$request = wp_remote_get( $request, array( 'timeout' => $this->get_timeout() ) );

		if ( wp_remote_retrieve_response_code( $request ) !== 200 ) {
			return $empty;
		}

		$feed = simplexml_load_string( wp_remote_retrieve_body( $request ) );

		if ( ! $feed ) {
			return $empty;
		}

		$feed->registerXPathNamespace( 'a', 'http://www.w3.org/2005/Atom' );
		$feed->registerXPathNamespace( 'yt', 'http://www.youtube.com/xml/schemas/2015' );

		$title    = $feed->xpath( '//a:entry[1]/a:title' );
		$video_id = $feed->xpath( '//a:entry[1]/yt:videoId' );

		return [
			'title'    => $title ? trim( (string) $title[0] ) : '',
			'video_id' => $video_id ? trim( (string) $video_id[0] ) : '',
		];
	}

	/**
	 * Placeholder for removing the default WordPress core oEmbed provider.
	 *
	 * @return void
	 */
	public function remove_provider() {
	}

	/**
	 * Get data from API by Video ID.
	 *
	 * YouTube renders via the `oembed_dataparse` filter (see `oembed_html()`), not a custom
	 * `wp_embed_register_handler` callback, so this method is unused but required by `Provider`.
	 *
	 * @param string $video_id Video ID.
	 *
	 * @return array
	 */
	public function get_data( string $video_id ): array {
		return [];
	}

	/**
	 * Get preview template.
	 *
	 * @param string $video_id Video ID.
	 * @param string $quality Video preview quality.
	 *
	 * @return string
	 */
	public function get_preview_template( $video_id, $quality ): string {
		return sprintf( self::PREVIEW_URL, $video_id, $quality );
	}

	/**
	 * Get video preview URL by video ID.
	 *
	 * @param string $video_id Video ID.
	 *
	 * @return string
	 */
	public function get_preview_url( string $video_id ): string {
		$quality = Options::get( 'preview_quality', 'mlye_general', 'auto' );

		if ( 'auto' === $quality ) {
			foreach ( [ 'maxresdefault', 'sddefault', 'hqdefault', 'mqdefault' ] as $size ) {
				$response = wp_remote_head( $this->get_preview_template( $video_id, $size ), array( 'timeout' => $this->get_timeout() ) );

				if ( 200 === wp_remote_retrieve_response_code( $response ) ) {
					return $this->get_preview_template( $video_id, $size );
				}
			}
		} else {
			return $this->get_preview_template( $video_id, $quality );
		}

		return $this->get_preview_template( $video_id, 'sddefault' );
	}

	/**
	 * Register oEmbed Handler.
	 *
	 * @return void
	 */
	public function register_handler(): void {
		// TODO: Implement register_handler() method.
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
		// TODO: Implement handler_callback() method.
		return '';
	}

	/**
	 * Get fallback data from API by Video ID.
	 *
	 * @param string $video_id Video ID.
	 *
	 * @return array
	 */
	public function get_fallback_data( string $video_id ): array {
		// TODO: Implement get_fallback_data() method.
		return [];
	}
}
