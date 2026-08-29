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
	public static function get_plugin_slug(): string {
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

	/**
	 * Конвертирует секунды в формат ISO_8601 для
	 * отображения в duration.
	 *
	 * @param int $seconds Секунды.
	 *
	 * @return string
	 */
	public static function iso8601_duration( int $seconds ): string {
		$days     = (int) floor( $seconds / 86400 );
		$seconds -= $days * 86400;

		$hours    = (int) floor( $seconds / 3600 );
		$seconds -= $hours * 3600;

		$minutes  = (int) floor( $seconds / 60 );
		$seconds -= $minutes * 60;

		$date_part = $days ? "{$days}D" : '';

		return "P{$date_part}T{$hours}H{$minutes}M{$seconds}S";
	}

	/**
	 * Sanitize video description.
	 *
	 * @param string|null $description Description.
	 *
	 * @return string
	 */
	public static function sanitize_video_description( ?string $description ): string {
		return wp_strip_all_tags( str_replace( PHP_EOL, ' ', (string) $description ) );
	}
}
