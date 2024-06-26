<?php
/**
 * Plugin Name: Lite Video Embed (YouTube, RuTube)
 * Description: A faster YouTube/RuTube embed. Renders faster than a sneeze.
 * Version: 1.8.0.3
 * Author: Mikhail Kobzarev
 * Author URI: https://www.kobzarev.com/
 * Plugin URI: https://wordpress.org/plugins/mihdan-lite-youtube-embed/
 * GitHub Plugin URI: https://github.com/mihdan/mihdan-lite-video-embed
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Donate link: https://www.kobzarev.com/donate/
 *
 * @link https://github.com/paulirish/lite-youtube-embed
 * @package mihdan-lite-youtube-embed
 */

namespace Mihdan\LiteYouTubeEmbed;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'MIHDAN_LITE_YOUTUBE_EMBED_VERSION', '1.8.0.3' );
define( 'MIHDAN_LITE_YOUTUBE_EMBED_SLUG', 'mihdan-lite-youtube-embed' );
define( 'MIHDAN_LITE_YOUTUBE_EMBED_NAME', 'Lite Video Embed' );
define( 'MIHDAN_LITE_YOUTUBE_EMBED_DIR', __DIR__ );
define( 'MIHDAN_LITE_YOUTUBE_EMBED_FILE', __FILE__ );
define( 'MIHDAN_LITE_YOUTUBE_EMBED_URL', untrailingslashit( plugin_dir_url( MIHDAN_LITE_YOUTUBE_EMBED_FILE ) ) );

if ( file_exists( MIHDAN_LITE_YOUTUBE_EMBED_DIR . '/vendor/autoload.php' ) ) {
	require_once MIHDAN_LITE_YOUTUBE_EMBED_DIR . '/vendor/autoload.php';

	( new Main() )->setup_hooks();
}
