<?php
/**
 * Class Main
 *
 * @package mihdan-lite-youtube-embed
 */

namespace Mihdan\LiteYouTubeEmbed;

use Elementor\Widgets_Manager;
use Elementor\Plugin;
use Latte\Engine;
use wpdb;

/**
 * Class Main
 *
 * @package mihdan-lite-youtube-embed
 */
class Main {
	/**
	 * wpdb instance.
	 *
	 * @var wpdb $wpdb
	 */

	private $wpdb;
	/**
	 * Utils instance.
	 *
	 * @var Utils $utils
	 */
	private $utils;

	/**
	 * Settings instance.
	 *
	 * @var Settings $settings
	 */
	private $settings;

	/**
	 * WP_OSA instance.
	 *
	 * @var Wposa $wposa
	 */
	private $wposa;

	/**
	 * Engine instance.
	 *
	 * @var Engine $latte
	 */
	private $latte;

	/**
	 * YouTube preview URL template.
	 */
	const PREVIEW_URL = 'https://i.ytimg.com/vi/%s/%s.jpg';

	/**
	 * Content details URL.
	 */
	const CONTENT_DETAILS_URL = 'https://www.googleapis.com/youtube/v3/videos?id=%s&key=%s&part=contentDetails,snippet';

	/**
	 * Simple content URL.
	 */
	const SIMPLE_CONTENT_URL = 'https://www.youtube.com/oembed?url=youtube.com/watch?v=%s';

	/**
	 * Validate key URL.
	 */
	const VALIDATE_KEY_URL = 'https://www.googleapis.com/youtube/v3/search?part=snippet&q=YouTube+Data+API&type=video&key=%s';

	/**
	 * Pattern for parsing youtube iframe
	 *
	 * @link https://regexr.com/5hocf
	 */
	const IFRAME_PATTERN = '#<iframe\s.*?src="(?:https?:)?\/\/(?:www\.)?(?:youtu\.be\/|youtube\.com(?:\/embed\/|\/v\/|\/watch\?v=))([\w\-]{10,12})"(?:[^>]+)?><\/iframe>#si';
	const IFRAME_REPLACEMENT = 'https://www.youtube.com/watch?v=$1';

	/**
	 * HTTP timeout.
	 *
	 * @var int
	 */
	private $timeout;

	/**
	 * YouTube API key.
	 *
	 * @var string
	 */
	private $api_key;

	/**
	 * Main constructor.
	 */
	public function __construct() {
		$this->wpdb     = $GLOBALS['wpdb'];
		$this->utils    = new Utils();
		$this->wposa    = new Wposa( $this->utils );
		$this->settings = new Settings( $this->wposa );
		$this->latte    = new Engine();
		$this->api_key  = $this->wposa->get_option( 'api_key', 'mlye_general' );
		$this->timeout  = $this->wposa->get_option( 'timeout', 'mlye_general' );
	}

	/**
	 * Setup hooks.
	 */
	public function setup_hooks() {
		add_filter( 'oembed_remote_get_args', array( $this, 'oembed_remote_set_timeout' ), 10, 2 );
		add_filter( 'plugin_action_links', array( $this, 'add_settings_link' ), 10, 2 );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend_assets' ) );
		add_action( 'after_setup_theme', array( $this, 'enqueue_tinymce_assets' ) );
		add_action( 'enqueue_block_editor_assets', array( $this, 'enqueue_gutenberg_assets' ) );
		add_filter( 'oembed_dataparse', array( $this, 'oembed_html' ), 100, 3 );
		add_filter( 'pre_update_option_mlye_tools', array( $this, 'maybe_clear_cache' ), 10, 2 );
		add_filter( 'pre_update_option_mlye_general', array( $this, 'maybe_validate_api_key' ), 10, 2 );
		add_filter( 'the_content', array( $this, 'parse_iframe' ) );

		// Elementor support.
		if ( did_action( 'elementor/loaded' ) ) {
			add_action( 'elementor/init', array( $this, 'register_category' ) );
			add_action( 'elementor/widgets/widgets_registered', array( $this, 'require_widgets' ) );
		}

		register_activation_hook( $this->utils->get_plugin_file(), array( $this, 'on_activate' ) );
		register_deactivation_hook( $this->utils->get_plugin_file(), array( $this, 'on_deactivate' ) );

		/**
		 * Remove OceanWP wrapper for YouTube videos.
		 */
		add_filter(
			'ocean_oembed_responsive_hosts',
			function ( $hosts ) {

				$allowed = array(
					'youtube.com',
					'#http://((m|www)\.)?youtube\.com/watch.*#i',
					'#https://((m|www)\.)?youtube\.com/watch.*#i',
					'#http://((m|www)\.)?youtube\.com/playlist.*#i',
					'#https://((m|www)\.)?youtube\.com/playlist.*#i',
					'#http://youtu\.be/.*#i',
					'#https://youtu\.be/.*#i',
				);

				return array_diff( $hosts, $allowed );
			}
		);
	}

	/**
	 * Oembed remote get set request timeout.
	 *
	 * @param array  $args Array of default arguments.
	 * @param string $url  Provider URL with args.
	 *
	 * @return array
	 */
	public function oembed_remote_set_timeout( $args, $url ) {
		if ( false === strpos( $url, 'youtube' ) ) {
			return $args;
		}

		$args[ 'timeout' ] = $this->get_timeout();

		return $args;
	}

	/**
	 * Create new category for widget.
	 */
	public function register_category() {
		Plugin::$instance->elements_manager->add_category(
			'mihdan',
			array(
				'title' => 'Mihdan Widgets',
				'icon'  => 'font',
			)
		);
	}

	/**
	 * Register Widgets
	 *
	 * Register new Elementor widgets.
	 *
	 * @since 1.3
	 * @access public
	 *
	 * @param Widgets_Manager $widgets_manager Widgets_Manager instance.
	 */
	public function require_widgets( Widgets_Manager $widgets_manager ) {
		//$widgets_manager->register_widget_type( new Elementor() );
	}

	public function parse_iframe( $content ) {
		global $wp_embed;

		if ( is_admin() ) {
			return $content;
		}

		if ( 'yes' !== $this->wposa->get_option( 'iframe_support', 'mlye_general', 'no' ) ) {
			return $content;
		}

		// Fix breaking layout.
		$content = str_replace(
			[ '<p><iframe', '</iframe></p>' ],
			[ '<iframe', '</iframe>' ],
			$content
		);

		return $wp_embed->autoembed( preg_replace( self::IFRAME_PATTERN, PHP_EOL . self::IFRAME_REPLACEMENT . PHP_EOL, $content ) );
	}

	/**
	 * Get preview template.
	 *
	 * @param string $video_id Video ID.
	 * @param string $quality Video preview quality.
	 *
	 * @return string
	 */
	public function get_preview_template( $video_id, $quality ) {
		return sprintf( self::PREVIEW_URL, $video_id, $quality );
	}

	private function sanitize_video_description( $description ) {
		return wp_strip_all_tags( str_replace( PHP_EOL, ' ', $description ) );
	}

	private function get_data_from_api( $video_id ) {
		$api_key = $this->get_api_key();

		// Default data.
		$post        = get_post();
		$duration    = 'T00H10M00S';
		$upload_date = get_post_time( 'c', false, $post, false );
		$name        = $post->post_title;

		$description = ( ! empty( $post->post_excerpt ) )
			? $post->post_excerpt
			: $this->wposa->get_option( 'description', 'mlye_general' );

		$result = [
			'duration' => $duration,
			'name' => $name,
			'description' => $this->sanitize_video_description( $description ),
			'upload_date' => $upload_date,
		];

		if ( $api_key ) {
			$request = sprintf( self::CONTENT_DETAILS_URL, $video_id, $api_key );
			$request = wp_remote_get( $request, array( 'timeout' => $this->get_timeout() ) );
			$body    = wp_remote_retrieve_body( $request );

			if ( $body ) {
				$body            = json_decode( $body, false );
				$content_details = $body->items[0]->contentDetails;
				$snippet         = $body->items[0]->snippet;

				$result = [
					'duration' => $content_details->duration,
					'name' => $snippet->title,
					'description' => $this->sanitize_video_description( $snippet->description ),
					'upload_date' => $snippet->publishedAt,
				];
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
	 * Change oembed HTML.
	 *
	 * @link https://wp-kama.ru/hook/oembed_dataparse
	 * @link https://developers.google.com/search/docs/data-types/video
	 *
	 * @param string $return The returned oEmbed HTML.
	 * @param object $data   A data object result from an oEmbed provider.
	 * @param string $url    The URL of the content to be embedded.
	 *
	 * @return string
	 */
	public function oembed_html( $return, $data, $url ) {
		if ( 'YouTube' !== $data->provider_name ) {
			return $return;
		}
		preg_match( '#src="(.*?embed\/([^\?]+).*?)"#', $data->html, $matches );

		if ( ! $matches ) {
			return $return;
		}

		$video_id  = $matches[2];
		$embed_url = $matches[1];

		$player_parameters = parse_url( $embed_url, PHP_URL_QUERY );

		$player_size = explode( 'x', $this->wposa->get_option( 'player_size', 'mlye_general', '16x9' ) );

		if ( 'yes' === $this->wposa->get_option( 'hide_related_video', 'mlye_general' ) ) {
			$player_parameters = add_query_arg(
				[
					'rel'            => 0,
					'showinfo'       => 0,
					'modestbranding' => 1,
				],
				$player_parameters
			);
		}

		// Get duration from API.
		$api = $this->get_data_from_api( $video_id );

		$params = array(
			'use_microdata'     => ( 'yes' === $this->wposa->get_option( 'use_microdata', 'mlye_general' ) ),
			'use_lazy_load'     => ( 'yes' === $this->wposa->get_option( 'use_lazy_load', 'mlye_general' ) ),
			'preview_quality'   => $this->wposa->get_option( 'preview_quality', 'mlye_general', 'auto' ),
			'video_id'          => $video_id,
			'player_width'      => in_array( $player_size[0], array( '16', '4' ), true ) ? 1280 : $player_size[0],
			'player_height'     => in_array( $player_size[1], array( '9', '3' ), true ) ? 720 : $player_size[1],
			'player_class'      => 'lite-youtube_' . $player_size[0] . 'x' . $player_size[1],
			'player_parameters' => $player_parameters,
			'upload_date'       => $api['upload_date'],
			'duration'          => $api['duration'],
			'url'               => $url,
			'description'       => mb_substr( $api['description'], 0, 250, 'UTF-8' ) . '...',
			'name'              => $api['name'],
			'embed_url'         => $embed_url,
			'preview_url'       => $this->get_preview_url( $video_id ),
		);

		$render = $this->latte->renderToString(
			$this->utils->get_templates_path() . '/template-video.latte',
			$params
		);

		return str_replace( array( "\n", "\t", "\r" ), '', $render );
	}

	/**
	 * Get video preview URL by video ID.
	 *
	 * @param string $video_id Video ID.
	 *
	 * @return string
	 */
	public function get_preview_url( $video_id ) {
		$quality = $this->wposa->get_option( 'preview_quality', 'mlye_general', 'auto' );

		if ( 'auto' === $quality ) {
			foreach( array( 'maxresdefault', 'sddefault', 'hqdefault', 'mqdefault' ) as $size ) {
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
	 * Enqueue Gutenberg assets.
	 */
	public function enqueue_gutenberg_assets() {
		wp_enqueue_style(
			Utils::get_plugin_slug(),
			$this->utils->get_plugin_url() . '/assets/dist/css/admin.css?g',
			array( 'wp-edit-blocks' ),
			//$this->utils->get_plugin_version()
			time()
		);
	}

	/**
	 * Enqueue tinymce assets.
	 */
	public function enqueue_tinymce_assets() {
		add_editor_style( $this->utils->get_plugin_url() . '/assets/dist/css/admin.css?t' );
	}

	/**
	 * Enqueue frontend assets.
	 */
	public function enqueue_frontend_assets() {

		wp_enqueue_style(
			Utils::get_plugin_slug(),
			$this->utils->get_plugin_url() . '/assets/dist/css/frontend.css',
			array(),
			$this->utils->get_plugin_version()
		);

		wp_enqueue_script(
			Utils::get_plugin_slug(),
			$this->utils->get_plugin_url() . '/assets/dist/js/frontend.js',
			[],
			$this->utils->get_plugin_version(),
			true
		);

		// Lazy Load.
		if ( ! wp_script_is( 'mihdan-lozad' ) && 'yes' === $this->wposa->get_option( 'use_lazy_load', 'mlye_general' ) ) {
			wp_enqueue_script(
				'mihdan-lozad',
				$this->utils->get_plugin_url() . '/assets/dist/js/lozad.js',
				[],
				$this->utils->get_plugin_version(),
				true
			);

			// Lozad init.
			$lozad = "const observer = lozad( '.mihdan-lozad', { threshold: 0.1, enableAutoReload: true }); observer.observe();";

			wp_add_inline_script( 'mihdan-lozad', $lozad );
		}
	}

	/**
	 * Add plugin action links
	 *
	 * @param array  $actions Default actions.
	 * @param string $plugin_file Plugin file.
	 *
	 * @return array
	 */
	public function add_settings_link( $actions, $plugin_file ) {
		if ( $this->utils->get_plugin_basename() === $plugin_file ) {
			$actions[] = sprintf(
				'<a href="%s">%s</a>',
				admin_url( 'options-general.php?page=mihdan-lite-youtube-embed' ),
				esc_html__( 'Settings', 'mihdan-lite-youtube-embed' )
			);
		}

		return $actions;
	}

	/**
	 * Get API key.
	 *
	 * @return string
	 */
	public function get_api_key() {
		return $this->api_key;
	}

	/**
	 * Get HTTP timeout.
	 *
	 * @return string
	 */
	public function get_timeout() {
		return $this->timeout;
	}

	/**
	 * Validate API key.
	 *
	 * @param string $api_key API key.
	 *
	 * @link https://stackoverflow.com/questions/21096602/using-youtube-v3-api-key/21117446#21117446
	 * @return array
	 */
	public function validate_api_key( $api_key ) {
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
	 * Trigger for clear cache via button in settings page.
	 *
	 * @param array $value     New value.
	 * @param array $old_value Old value.
	 *
	 * @return array
	 */
	public function maybe_clear_cache( $value, $old_value ) {
		if ( isset( $value['clear_cache'] ) && 'on' === $value['clear_cache'] ) {
			$value['clear_cache'] = 'off';

			$this->clear_oembed_cache();

			add_settings_error( 'general', 'embed_cache_cleared', __( 'Cache was cleared successfully.' ), 'success' );
		}

		return $value;
	}

	/**
	 * Fired on plugin activate.
	 */
	public function on_activate() {
		$this->clear_oembed_cache();
	}

	/**
	 * Fired on plugin deactivate.
	 */
	public function on_deactivate() {
		$this->clear_oembed_cache();
	}

	/**
	 * Clear all oembed cache.
	 */
	public function clear_oembed_cache() {
		$sql = "DELETE FROM {$this->wpdb->postmeta} WHERE LEFT(meta_key, 8) = '_oembed_'";
		$this->wpdb->query( $sql );
	}
}
