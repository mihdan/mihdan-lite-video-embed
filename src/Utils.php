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

	/**
	 * Конвертирует секунды в формат ISO_8601 для
	 * отображения в duration.
	 *
	 * @param int $seconds Секунды.
	 *
	 * @return string
	 */
	public static function iso8601_duration( int $seconds ): string {
		$intervals = [
			'D' => 60 * 60 * 24,
			'H' => 60 * 60,
			'M' => 60,
			'S' => 1
		];

		$pt     = 'P';
		$result = '';

		foreach ( $intervals as $tag => $divisor ) {
			$qty = floor( $seconds / $divisor );
			if ( ! $qty && $result === '' ) {
				$pt = 'T';
				continue;
			}

			$seconds -= $qty * $divisor;
			$result  .= "$qty$tag";
		}
		if ( $result === '' ) {
			$result = '0S';
		}

		return "$pt$result";
	}

	/**
	 * Sanitize video description.
	 *
	 * @param string $description Description.
	 *
	 * @return string
	 */
	public static function sanitize_video_description( string $description ): string {
		return wp_strip_all_tags( str_replace( PHP_EOL, ' ', $description ) );
	}
}