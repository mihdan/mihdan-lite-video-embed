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

		$tools = get_option( 'mlye_tools' );

		// Delete plugin data upon uninstall.
		if ( isset( $tools['delete_plugin_data'] ) && $tools['delete_plugin_data'] === 'on' ) {
			$this->delete_plugin_data();
		}
	}

	/**
	 * Clear oembed cache.
	 */
	public function clear_oembed_cache(): void {
		global $wpdb;

		$sql = "DELETE FROM {$wpdb->postmeta} WHERE LEFT(meta_key, 8) = '_oembed_'";
		$wpdb->query( $sql );
	}

	/**
	 * Delete plugin data upon uninstall.
	 *
	 * @return void
	 */
	public function delete_plugin_data(): void {
		global $wpdb;

		$sql = "DELETE FROM {$wpdb->options} WHERE LEFT(option_name, 5) = 'mlye_'";
		$wpdb->query( $sql );
	}
}

new Uninstall();