<?php
/**
 * Class Main
 *
 * @package mihdan-lite-youtube-embed
 */

namespace Mihdan\LiteYouTubeEmbed;

use Elementor\Widgets_Manager;
use Elementor\Plugin;

use Latte\Engine as Latte;
use Mihdan\LiteYouTubeEmbed\Providers\RuTube;
use Mihdan\LiteYouTubeEmbed\Providers\YouTube;
use Mihdan\LiteYouTubeEmbed\ThirdParty\CreativeMotionClearfy;
use wpdb;
use Exception;

/**
 * Class Main
 *
 * @package mihdan-lite-youtube-embed
 */
class Main {
	/**
	 * Instance of wpdb class.
	 *
	 * @var wpdb $wpdb
	 */
	private $wpdb;

	/**
	 * Utils instance.
	 *
	 * @var Utils $utils
	 */
	private $utils;

	/**
	 * Settings instance.
	 *
	 * @var Settings $settings
	 */
	private $settings;

	/**
	 * WP_OSA instance.
	 *
	 * @var Options $wposa
	 */
	private $wposa;

	/**
	 * Main constructor.
	 */
	public function __construct() {
		$this->wpdb     = $GLOBALS['wpdb'];
		$this->utils    = new Utils();
		$this->wposa    = new Options( $this->utils );
		$this->settings = new Settings( $this->wposa );

		( new YouTube() )->setup_hooks();
		( new RuTube() )->setup_hooks();

		// Webcraftic Clearfy.
		( new CreativeMotionClearfy() )->setup_hooks();
	}

	/**
	 * Setup hooks.
	 */
	public function setup_hooks(): void {
		add_filter( 'plugin_action_links', array( $this, 'add_settings_link' ), 10, 2 );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend_assets' ) );
		add_action( 'after_setup_theme', array( $this, 'enqueue_tinymce_assets' ) );
		//add_action( 'enqueue_block_editor_assets', array( $this, 'enqueue_gutenberg_assets' ) );
		add_filter( 'pre_update_option_mlye_tools', array( $this, 'maybe_clear_cache' ), 10, 2 );

		register_activation_hook( Utils::get_plugin_file(), array( $this, 'on_activate' ) );
		register_deactivation_hook( Utils::get_plugin_file(), array( $this, 'on_deactivate' ) );
	}

	/**
	 * Enqueue Gutenberg assets.
	 */
	public function enqueue_gutenberg_assets() {
		wp_enqueue_style(
			Utils::get_plugin_slug(),
			Utils::get_plugin_url() . '/assets/dist/css/admin.css',
			array( 'wp-edit-blocks' ),
			filemtime( Utils::get_plugin_path() . '/assets/dist/css/admin.css' )
		);
	}

	/**
	 * Enqueue tinymce assets.
	 */
	public function enqueue_tinymce_assets() {
		add_editor_style(
			Utils::get_plugin_url() . '/assets/dist/css/admin.css'
		);
	}

	/**
	 * Enqueue frontend assets.
	 */
	public function enqueue_frontend_assets() {

		wp_enqueue_style(
			Utils::get_plugin_slug(),
			Utils::get_plugin_url() . '/assets/dist/css/frontend.css',
			array(),
			Utils::get_plugin_version()
		);

		wp_enqueue_script(
			Utils::get_plugin_slug(),
			Utils::get_plugin_url() . '/assets/dist/js/frontend.js',
			[],
			filemtime( Utils::get_plugin_path() . '/assets/dist/js/frontend.js' ),
			true
		);

		// Lazy Load.
		if ( ! wp_script_is( 'mihdan-lozad' ) && 'yes' === Options::get( 'use_lazy_load', 'mlye_general' ) ) {
			wp_enqueue_script(
				'mihdan-lozad',
				Utils::get_plugin_url() . '/assets/dist/js/lozad.js',
				[],
				Utils::get_plugin_version(),
				true
			);

			// Lozad init.
			$lozad = "const observer = lozad( '.mihdan-lozad', { threshold: 0.1, enableAutoReload: true }); observer.observe();";

			wp_add_inline_script( 'mihdan-lozad', $lozad );
		}
	}

	/**
	 * Add plugin action links
	 *
	 * @param array  $actions     Default actions.
	 * @param string $plugin_file Plugin file.
	 *
	 * @return array
	 * @throws Exception Exception.
	 */
	public function add_settings_link( array $actions, string $plugin_file ): array {
		if ( Utils::get_plugin_basename() === $plugin_file ) {
			$actions[] = sprintf(
				'<a href="%s">%s</a>',
				admin_url( 'options-general.php?page=mihdan-lite-youtube-embed' ),
				esc_html__( 'Settings', 'mihdan-lite-youtube-embed' )
			);
		}

		return $actions;
	}


	/**
	 * Trigger for clear cache via button in settings page.
	 *
	 * @param array $value     New value.
	 * @param array $old_value Old value.
	 *
	 * @return array
	 * @throws Exception Exception.
	 */
	public function maybe_clear_cache( array $value, array $old_value ): array {
		if ( isset( $value['clear_cache'] ) && 'on' === $value['clear_cache'] ) {
			$value['clear_cache'] = 'off';

			$this->clear_oembed_cache();

			add_settings_error( 'general', 'embed_cache_cleared', __( 'Cache was cleared successfully.' ), 'success' );
		}

		return $value;
	}

	/**
	 * Fired on plugin activate.
	 */
	public function on_activate() {
		$this->clear_oembed_cache();
	}

	/**
	 * Fired on plugin deactivate.
	 */
	public function on_deactivate() {
		$this->clear_oembed_cache();
	}

	/**
	 * Clear all oembed cache.
	 */
	public function clear_oembed_cache() {
		$sql = "DELETE FROM {$this->wpdb->postmeta} WHERE LEFT(meta_key, 8) = '_oembed_'";
		$this->wpdb->query( $sql );
	}
}
