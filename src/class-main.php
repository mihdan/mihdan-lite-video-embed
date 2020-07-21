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
		add_action(
			'wp_enqueue_scripts',
			function () {
				wp_enqueue_script(
					MIHDAN_LITE_YOUTUBE_EMBED_SLUG,
					MIHDAN_LITE_YOUTUBE_EMBED_URL . '/frontend/js/lite-yt-embed.js',
					[],
					filemtime( MIHDAN_LITE_YOUTUBE_EMBED_DIR . '/frontend/js/lite-yt-embed.js' ),
					true
				);

				wp_enqueue_style(
					MIHDAN_LITE_YOUTUBE_EMBED_SLUG,
					MIHDAN_LITE_YOUTUBE_EMBED_URL . '/frontend/css/lite-yt-embed.css'
				);
			}
		);

		add_action(
			'admin_enqueue_scripts',
			function () {
				wp_enqueue_script(
					MIHDAN_LITE_YOUTUBE_EMBED_SLUG,
					MIHDAN_LITE_YOUTUBE_EMBED_URL . '/frontend/js/lite-yt-embed.js',
					[],
					filemtime( MIHDAN_LITE_YOUTUBE_EMBED_DIR . '/frontend/js/lite-yt-embed.js' ),
					true
				);
			}
		);

		add_action(
			'after_setup_theme',
			function () {
				// Add support for editor styles.
				add_theme_support( 'editor-styles' );

				// Enqueue editor styles.
				add_editor_style( MIHDAN_LITE_YOUTUBE_EMBED_URL . '/frontend/css/lite-yt-embed.css' );
			}
		);

		add_action(
			'enqueue_block_editor_assets',
			function () {
				wp_enqueue_style(
					MIHDAN_LITE_YOUTUBE_EMBED_SLUG,
					MIHDAN_LITE_YOUTUBE_EMBED_URL . '/frontend/css/lite-yt-embed.css',
					array( 'wp-edit-blocks' ),
					time()
				);
			}
		);

		/**
		 * @link https://www.youtube.com/watch?v=6VLL9Txw6c4
		 */
		add_filter(
			'pre_oembed_result',
			function ( $null, $url, $attr ) {

				if ( preg_match( '#youtu#i', $url ) ) {
					preg_match( '#watch\?v=([0-9a-z\-\_]+)#i', $url, $matchs );

					if ( ! empty( $matchs[1] ) ) {

						$post = get_post();

						$player_size = explode( 'x', $this->wposa->get_option( 'player_size', 'mlye_general', '480x360' ) );

						// Get duration from API.
						$duration = 'T6M34S';
						$api_key  = $this->wposa->get_option( 'api_key', 'mlye_general' );

						if ( $api_key ) {
							$request = sprintf( 'https://www.googleapis.com/youtube/v3/videos?id=%s&key=%s&part=contentDetails', $matchs[1], $api_key );
							$request = wp_remote_get( $request );

							if ( ! is_wp_error( $request ) ) {
								$body = wp_remote_retrieve_body( $request );

								if ( $body ) {
									$body     = json_decode( $body );
									$duration = $body->items[0]->contentDetails->duration;
								}
							}
						}

						$params = array(
							'use_microdata'   => ( 'yes' === $this->wposa->get_option( 'use_microdata', 'mlye_general' ) ),
							'preview_quality' => $this->wposa->get_option( 'preview_quality', 'mlye_general', 'sddefault' ),
							'video_id'        => $matchs[1],
							'player_width'    => $player_size[0],
							'player_height'   => $player_size[1],
							'player_class'    => ( 1.8 === round( $player_size[0] / $player_size[1], 1 ) )
								? 'lite-youtube_16x9'
								: 'lite-youtube_4x3',
							'upload_date'     => get_the_date( 'Y-m-d', $post->ID ),
							'duration'        => $duration,
							'url'             => $url,
							'description'     => wp_strip_all_tags( get_the_excerpt( $post->ID ) ),
							'name'            => get_the_title( $post->ID ),
						);

						return $this->latte->renderToString(
							$this->utils->get_templates_path() . '/template-video.latte',
							$params
						);
					}
				}

				return $null;
			},
			20,
			3
		);

		add_filter( 'pre_update_option_mlye_tools', array( $this, 'maybe_clear_cache' ), 10, 2 );

		register_activation_hook( $this->utils->get_plugin_file(), array( $this, 'on_activate' ) );
		register_deactivation_hook( $this->utils->get_plugin_file(), array( $this, 'on_deactivate' ) );
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
