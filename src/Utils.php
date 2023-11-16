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
	public static function get_plugin_path(): string {
		return constant( 'MIHDAN_LITE_YOUTUBE_EMBED_DIR' );
	}

	/**
	 * Get templates path.
	 *
	 * @return string
	 */
	public static function get_templates_path(): string {
		return self::get_plugin_path() . '/templates';
	}

	/**
	 * Get plugin version.
	 *
	 * @return string
	 */
	public static function get_plugin_version(): string {
		return constant( 'MIHDAN_LITE_YOUTUBE_EMBED_VERSION' );
	}

	/**
	 * Get plugin URL.
	 *
	 * @return string
	 */
	public static function get_plugin_url(): string {
		return constant( 'MIHDAN_LITE_YOUTUBE_EMBED_URL' );
	}

	/**
	 * Get plugin slug.
	 *
	 * @return string
	 */
	public static function get_plugin_slug():string {
		return constant( 'MIHDAN_LITE_YOUTUBE_EMBED_SLUG' );
	}

	/**
	 * Get plugin file.
	 *
	 * @return string
	 */
	public static function get_plugin_file(): string {
		return constant( 'MIHDAN_LITE_YOUTUBE_EMBED_FILE' );
	}

	/**
	 * Get plugin base name.
	 *
	 * @return string
	 */
	public static function get_plugin_basename(): string {
		return plugin_basename( MIHDAN_LITE_YOUTUBE_EMBED_FILE );
	}

	/**
	 * Get plugin title.
	 *
	 * @return string
	 */
	public static function get_plugin_title(): string {
		return constant( 'MIHDAN_LITE_YOUTUBE_EMBED_NAME' );
	}
}