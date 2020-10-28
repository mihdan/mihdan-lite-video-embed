<?php
/**
 * Class Main
 *
 * @package mihdan-lite-youtube-embed
 */

namespace Mihdan\LiteYouTubeEmbed;

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
	 * @var WP_OSA $wposa
	 */
	private $wposa;

	/**
	 * Engine instance.
	 *
	 * @var Engine $latte
	 */
	private $latte;

	/**
	 * Main constructor.
	 */
	public function __construct() {
		$this->wpdb     = $GLOBALS['wpdb'];
		$this->utils    = new Utils();
		$this->wposa    = new WP_OSA( $this->utils );
		$this->settings = new Settings( $this->wposa );
		$this->latte    = new Engine();

		$this->setup_hooks();
	}

	/**
	 * Setup hooks.
	 */
	public function setup_hooks() {
		add_filter( 'plugin_action_links', array( $this, 'add_settings_link' ), 10, 2 );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend_assets' ) );
		add_action( 'after_setup_theme', array( $this, 'enqueue_tinymce_assets' ) );
		add_action( 'enqueue_block_editor_assets', array( $this, 'enqueue_gutenberg_assets' ) );
		add_filter( 'oembed_dataparse', array( $this, 'oembed_html' ), 10, 3 );
		add_filter( 'pre_update_option_mlye_tools', array( $this, 'maybe_clear_cache' ), 10, 2 );
		add_filter( 'pre_update_option_mlye_general', array( $this, 'maybe_validate_api_key' ), 10, 2 );

		register_activation_hook( $this->utils->get_plugin_file(), array( $this, 'on_activate' ) );
		register_deactivation_hook( $this->utils->get_plugin_file(), array( $this, 'on_deactivate' ) );
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
		if ( 'YouTube' === $data->provider_name ) {
			preg_match( '#src="(.*?embed\/([^\?]+).*?)"#', $data->html, $matches );

			if ( ! $matches ) {
				return $return;
			}

			$post = get_post();

			$video_id  = $matches[2];
			$embed_url = $matches[1];

			$player_size = explode( 'x', $this->wposa->get_option( 'player_size', 'mlye_general', '16x9' ) );

			// Get duration from API.
			$duration    = 'T00H10M00S';
			$upload_date = get_post_time( 'c', false, $post, false );
			$name        = ( ! empty( $data->title ) )
				? $data->title
				: $post->post_title;

			$description = ( ! empty( $post->post_excerpt ) )
				? $post->post_excerpt
				: $this->wposa->get_option( 'description', 'mlye_general' );

			$api_key     = $this->wposa->get_option( 'api_key', 'mlye_general' );

			if ( $api_key ) {
				$request = sprintf( 'https://www.googleapis.com/youtube/v3/videos?id=%s&key=%s&part=contentDetails,snippet', $video_id, $api_key );
				$request = wp_remote_get( $request );

				if ( ! is_wp_error( $request ) ) {
					$body = wp_remote_retrieve_body( $request );

					if ( $body ) {
						$body            = json_decode( $body );
						$content_details = $body->items[0]->contentDetails;
						$snippet         = $body->items[0]->snippet;

						$duration    = $content_details->duration;
						$name        = $snippet->title;
						$description = $snippet->description;
						$upload_date = $snippet->publishedAt;
					}
				}
			}

			$description = str_replace( PHP_EOL, ' ', $description );
			$description = wp_strip_all_tags( $description );

			$params = array(
				'use_microdata'   => ( 'yes' === $this->wposa->get_option( 'use_microdata', 'mlye_general' ) ),
				'use_lazy_load'   => ( 'yes' === $this->wposa->get_option( 'use_lazy_load', 'mlye_general' ) ),
				'preview_quality' => $this->wposa->get_option( 'preview_quality', 'mlye_general', 'sddefault' ),
				'video_id'        => $video_id,
				'player_width'    => in_array( $player_size[0], array( '16', '4' ) ) ? 1280 : $player_size[0],
				'player_height'   => in_array( $player_size[1], array( '9', '3' ) ) ? 720 : $player_size[1],
				'player_class'    => 'lite-youtube_' . $player_size[0] . 'x' . $player_size[1],
				'upload_date'     => $upload_date,
				'duration'        => $duration,
				'url'             => $url,
				'description'     => mb_substr( $description, 0, 250, 'UTF-8' ) . '...',
				'name'            => $name,
				'embed_url'       => $embed_url,
			);

			return $this->latte->renderToString(
				$this->utils->get_templates_path() . '/template-video.latte',
				$params
			);
		}

		return $return;
	}

	/**
	 * Enqueue Gutenberg assets.
	 */
	public function enqueue_gutenberg_assets() {
		wp_enqueue_style(
			$this->utils->get_plugin_slug(),
			$this->utils->get_plugin_url() . '/admin/css/lite-yt-embed.css',
			array(),
			$this->utils->get_plugin_version()
		);
	}

	/**
	 * Enqueue tinymce assets.
	 */
	public function enqueue_tinymce_assets() {
		add_editor_style( $this->utils->get_plugin_url() . '/frontend/css/lite-yt-embed.css' );
	}

	/**
	 * Enqueue frontend assets.
	 */
	public function enqueue_frontend_assets() {
		$suffix = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';

		wp_enqueue_script(
			$this->utils->get_plugin_slug(),
			$this->utils->get_plugin_url() . '/frontend/js/lite-yt-embed' . $suffix. '.js',
			[],
			$this->utils->get_plugin_version(),
			true
		);

		wp_enqueue_style(
			$this->utils->get_plugin_slug(),
			$this->utils->get_plugin_url() . '/frontend/css/lite-yt-embed' . $suffix. '.css',
			array(),
			$this->utils->get_plugin_version()
		);

		// Lazy Load.
		if ( 'yes' === $this->wposa->get_option( 'use_lazy_load', 'mlye_general' ) ) {
			wp_enqueue_script(
				$this->utils->get_plugin_slug() . '-lozad',
				$this->utils->get_plugin_url() . '/frontend/js/lozad' . $suffix. '.js',
				[ $this->utils->get_plugin_slug() ],
				$this->utils->get_plugin_version(),
				true
			);

			// Lozad init.
			$lozad = "const observer = lozad( '.lite-youtube_lazy', { threshold: 0.1, enableAutoReload: true }); observer.observe();";

			wp_add_inline_script( $this->utils->get_plugin_slug() . '-lozad', $lozad );
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
	 * Validate API key.
	 *
	 * @param string $api_key API key.
	 *
	 * @link https://stackoverflow.com/questions/21096602/using-youtube-v3-api-key/21117446#21117446
	 * @return boolean
	 */
	public function validate_api_key( $api_key ) {
		$request = sprintf( 'https://www.googleapis.com/youtube/v3/search?part=snippet&q=YouTube+Data+API&type=video&key=%s', $api_key );
		$request = wp_remote_get( $request );

		if ( is_wp_error( $request ) ) {
			return false;
		}

		$body = wp_remote_retrieve_body( $request );

		if ( ! $body ) {
			return false;
		}

		$body = json_decode( $body );

		if ( $body->error ) {
			return false;
		}

		return true;
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

		if ( $this->validate_api_key( $value ['api_key'] ) ) {
			add_settings_error( 'general', 'api_key_valid', __( 'API key is valid.' ), 'success' );
		} else {
			add_settings_error( 'general', 'api_key_invalid', __( 'API key is invalid.' ), 'error' );
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
