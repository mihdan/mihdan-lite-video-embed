<?php
/**
 * Plugin Name: Mihdan: Lite YouTube Embed
 * Description: A faster youtube embed.
 * Version: 1.0
 * Author: Mikhail Kobzarev
 * Author URI: https://www.kobzarev.com/
 * Plugin URI: https://www.kobzarev.com/
 *
 * @link https://github.com/paulirish/lite-youtube-embed
 * @package mihdan-lite-youtube-embed
 */
namespace Mihdan\LiteYouTubeEmbed;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'MIHDAN_LITE_YOUTUBE_EMBED_SLUG', 'mihdan-lite-youtube-embed' );
define( 'MIHDAN_LITE_YOUTUBE_EMBED_DIR', __DIR__ );
define( 'MIHDAN_LITE_YOUTUBE_EMBED_FILE', __FILE__ );
define( 'MIHDAN_LITE_YOUTUBE_EMBED_URL', untrailingslashit( plugin_dir_url( MIHDAN_LITE_YOUTUBE_EMBED_FILE ) ) );

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
		//add_editor_style( MIHDAN_LITE_YOUTUBE_EMBED_URL . '/frontend/css/lite-yt-embed.css' );
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

				$result  = '<lite-youtube videoid="' . $video_id . '" style="background-image: url(\'https://i.ytimg.com/vi/' . $video_id . '/hqdefault.jpg\');">';
				$result .= '<div class="lty-playbtn"></div>';
				$result .= '</lite-youtube>';

				return $result;
			}
		}

		return $null;
	},
	10,
	3
);

// eol.
