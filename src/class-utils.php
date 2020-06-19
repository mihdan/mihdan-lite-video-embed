<?php
/**
 * Class Utils
 *
 * @package mihdan-lite-youtube-embed
 */

namespace Mihdan\LiteYouTubeEmbed;

/**
 * Class Utils
 *
 * @package mihdan-lite-youtube-embed
 */
class Utils {

	/**
	 * Get plugin path.
	 *
	 * @return string
	 */
	public function get_plugin_path() {
		return MIHDAN_LITE_YOUTUBE_EMBED_DIR;
	}

	/**
	 * Get plugin version.
	 *
	 * @return string
	 */
	public function get_plugin_version() {
		return MIHDAN_LITE_YOUTUBE_EMBED_VERSION;
	}

	/**
	 * Get plugin URL.
	 *
	 * @return string
	 */
	public function get_plugin_url() {
		return MIHDAN_LITE_YOUTUBE_EMBED_URL;
	}
}