<?php
/**
 * Class Main
 *
 * @package mihdan-lite-youtube-embed
 */

namespace Mihdan\LiteYouTubeEmbed;

/**
 * Class Main
 *
 * @package mihdan-lite-youtube-embed
 */
class Main {
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
	 * Main constructor.
	 *
	 * @param Utils $utils Utils instance
	 */
	public function __construct( Utils $utils ) {
		$this->utils = $utils;

		$this->include_requirements();
		$this->setup_hooks();

		$this->wposa    = new WP_OSA( $this->utils );
		$this->settings = new Settings( $this->wposa );
	}

	/**
	 * Include requirements.
	 */
	public function include_requirements() {
		require_once $this->utils->get_plugin_path() . '/src/class-wp-osa.php';
		require_once $this->utils->get_plugin_path() . '/src/class-settings.php';
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
						$video_id = $matchs[1];

						$result = '';

						$result .= '<lite-youtube videoid="%1$s" style="background-image: url(https://i.ytimg.com/vi/%1$s/hqdefault.jpg);">';
							$result .= '<div class="lty-playbtn"></div>';
						$result .= '</lite-youtube>';

						if ( 'yes' === $this->wposa->get_option( 'use_microdata', 'mlye_general' ) ) {
							$result .= '<div style="display: none" itemprop="video" itemscope="" itemtype="https://schema.org/VideoObject">';
								$result .= '<meta itemprop="description" content="%3$s">';
								$result .= '<meta itemprop="duration" content="T6M34S">';
								$result .= '<link itemprop="url" href="%2$s">';
								$result .= '<link itemprop="thumbnailUrl" href="https://i.ytimg.com/vi/%1$s/hqdefault.jpg">';
								$result .= '<meta itemprop="name" content="%4$s">';
								$result .= '<meta itemprop="uploadDate" content="%5$s">';
								$result .= '<meta itemprop="isFamilyFriendly" content="true">';
								$result .= '<span itemprop="thumbnail" itemscope="" itemtype="http://schema.org/ImageObject">';
									$result .= '<meta itemprop="contentUrl" content="https://i.ytimg.com/vi/%1$s/hqdefault.jpg">';
									$result .= '<meta itemprop="width" content="640">';
									$result .= '<meta itemprop="height" content="360">';
								$result .= '</span>';
							$result .= '</div>';
						}

						$result = sprintf(
							$result,
							$video_id,
							$url,
							'description',
							'name',
							'2020-06-12'
						);

						return $result;
					}
				}

				return $null;
			},
			10,
			3
		);
	}
}
