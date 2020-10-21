<?php
/**
 * Class Uninstall.
 *
 * @package mihdan-lite-youtube-embed
 */

namespace Mihdan\LiteYouTubeEmbed;

/**
 * Class Uninstall.
 *
 * @package mihdan-lite-youtube-embed
 */
class Uninstall {
	/**
	 * Uninstall constructor.
	 */
	public function __construct() {
		$this->clear_oembed_cache();
	}

	/**
	 * Clear oembed cache.
	 */
	public function clear_oembed_cache() {
		global $wpdb;

		$sql = "DELETE FROM {$wpdb->postmeta} WHERE LEFT(meta_key, 8) = '_oembed_'";
		$wpdb->query( $sql );
	}
}

new Uninstall();