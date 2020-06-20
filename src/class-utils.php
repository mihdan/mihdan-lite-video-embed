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
	 * Get templates path.
	 *
	 * @return string
	 */
	public function get_templates_path() {
		return MIHDAN_LITE_YOUTUBE_EMBED_DIR . '/templates';
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

	/**
	 * Get plugin slug.
	 *
	 * @return string
	 */
	public function get_plugin_slug() {
		return MIHDAN_LITE_YOUTUBE_EMBED_SLUG;
	}

	/**
	 * Get plugin title.
	 *
	 * @return string
	 */
	public function get_plugin_title() {
		$slug = MIHDAN_LITE_YOUTUBE_EMBED_SLUG;
		$slug = str_replace( 'mihdan-', '', $slug );
		$slug = str_replace( '-', ' ', $slug );
		$slug = ucwords( $slug );

		return $slug;
	}
}