<?php
/**
 * Plugin Name: Mihdan: Lite YouTube Embed
 * Description: A faster youtube embed.
 * Version: 1.4.4
 * Author: Mikhail Kobzarev
 * Author URI: https://www.kobzarev.com/
 * Plugin URI: https://wordpress.org/plugins/mihdan-lite-youtube-embed/
 * GitHub Plugin URI: https://github.com/mihdan/mihdan-lite-youtube-embed
 *
 * @link https://github.com/paulirish/lite-youtube-embed
 * @package mihdan-lite-youtube-embed
 */
namespace Mihdan\LiteYouTubeEmbed;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'MIHDAN_LITE_YOUTUBE_EMBED_VERSION', '1.4.4' );
define( 'MIHDAN_LITE_YOUTUBE_EMBED_SLUG', 'mihdan-lite-youtube-embed' );
define( 'MIHDAN_LITE_YOUTUBE_EMBED_DIR', __DIR__ );
define( 'MIHDAN_LITE_YOUTUBE_EMBED_FILE', __FILE__ );
define( 'MIHDAN_LITE_YOUTUBE_EMBED_URL', untrailingslashit( plugin_dir_url( MIHDAN_LITE_YOUTUBE_EMBED_FILE ) ) );

require_once MIHDAN_LITE_YOUTUBE_EMBED_DIR . '/vendor/autoload.php';

$mihdan_lite_youtube_embed = new Main();

// eol.
