<?php
/**
 * Class Settings.
 *
 * @package mihdan-lite-youtube-embed
 */

namespace Mihdan\LiteYouTubeEmbed;

use WP_Plugin_Install_List_Table;

/**
 * Class Settings.
 *
 * @package mihdan-lite-youtube-embed
 */
class Settings {
	/**
	 * WP_OSA instance.
	 *
	 * @var Wposa $wposa
	 */
	private $wposa;

	public function __construct( Wposa $wposa ) {
		$this->wposa = $wposa;

		$this->setup_hooks();
		$this->setup_fields();
	}

	public function setup_hooks() {
		add_filter( 'install_plugins_nonmenu_tabs', array( $this, 'install_plugins_nonmenu_tabs' ) );
		add_filter( 'install_plugins_table_api_args_' . Utils::get_plugin_slug(), array( $this, 'install_plugins_table_api_args' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );
	}

	public function admin_enqueue_scripts() {
		wp_enqueue_script( 'plugin_install' );
		wp_enqueue_script( 'updates' );
		add_thickbox();
	}

	public function install_plugins_nonmenu_tabs( $tabs ) {

		$tabs[] = Utils::get_plugin_slug();

		return $tabs;
	}

	public function install_plugins_table_api_args( $args ) {
		global $paged;

		return array(
			'page'     => $paged,
			'per_page' => 100,
			'locale'   => get_user_locale(),
			'author'   => 'mihdan',
		);
	}

	public function setup_fields() {

		$this->wposa->add_section(
			array(
				'id'    => 'mlye_general',
				'title' => __( 'YouTube', 'mihdan-lite-youtube-embed' ),
			)
		);

		$this->wposa->add_field(
			'mlye_general',
			array(
				'id'          => 'api_key',
				'type'        => 'text',
				'name'        => __( 'API Key', 'mihdan-lite-youtube-embed' ),
				'placeholder' => 'AIzaSyDe12JAR7DaIzUSGFIfiMuPPIOf1YMaKr4',
				'desc'        => __( 'Plugin uses YouTube\'s API to fetch information on each video. <br />For your site to use that API, you will have to <a href="https://console.developers.google.com/apis/library" target="_blank">register</a> your site as a new application, <br />enable the YouTube API for it and get a server key and fill it out here.', 'mihdan-lite-youtube-embed' ),
			)
		);

		$this->wposa->add_field(
			'mlye_general',
			array(
				'id'      => 'timeout',
				'type'    => 'select',
				'name'    => __( 'Timeout', 'mihdan-lite-youtube-embed' ),
				'options' => array(
					5  => 5,
					10 => 10,
					15 => 15,
					20 => 20,
					25 => 25,
					30 => 30,
					60 => 60,
					90 => 90,
				),
				'default' => 5,
				'desc'    => __( 'Timeout for HTTP requests', 'mihdan-lite-youtube-embed' ),
			)
		);

		$this->wposa->add_field(
			'mlye_general',
			array(
				'id'      => 'use_microdata',
				'type'    => 'select',
				'name'    => __( 'Use Microdata', 'mihdan-lite-youtube-embed' ),
				'options' => array(
					'yes' => __( 'Yes', 'mihdan-lite-youtube-embed' ),
					'no'  => __( 'No', 'mihdan-lite-youtube-embed' ),
				),
				'default' => 'yes',
				'desc'    => __( 'Add schema.org markup for video', 'mihdan-lite-youtube-embed' ),
			)
		);

		$this->wposa->add_field(
			'mlye_general',
			array(
				'id'          => 'description',
				'type'        => 'textarea',
				'name'        => __( 'Description', 'mihdan-lite-youtube-embed' ),
				'desc'        => __( 'Default video description for microdata', 'mihdan-lite-youtube-embed' ),
			)
		);

		$this->wposa->add_field(
			'mlye_general',
			array(
				'id'      => 'use_lazy_load',
				'type'    => 'select',
				'name'    => __( 'Use Lazy Load', 'mihdan-lite-youtube-embed' ),
				'options' => array(
					'yes' => __( 'Yes', 'mihdan-lite-youtube-embed' ),
					'no'  => __( 'No', 'mihdan-lite-youtube-embed' ),
				),
				'default' => 'yes',
				'desc'    => __( 'Add <code>loading="lazy"</code> attribute for preview', 'mihdan-lite-youtube-embed' ),
			)
		);

		$this->wposa->add_field(
			'mlye_general',
			array(
				'id'      => 'use_async_load',
				'type'    => 'select',
				'name'    => __( 'Use Async Load', 'mihdan-lite-youtube-embed' ),
				'options' => array(
					'yes' => __( 'Yes', 'mihdan-lite-youtube-embed' ),
					'no'  => __( 'No', 'mihdan-lite-youtube-embed' ),
				),
				'default' => 'yes',
				'desc'    => __( 'Add <code>decoding="async"</code> attribute for preview', 'mihdan-lite-youtube-embed' ),
			)
		);

		$this->wposa->add_field(
			'mlye_general',
			array(
				'id'      => 'iframe_support',
				'type'    => 'select',
				'name'    => __( 'Iframe Support', 'mihdan-lite-youtube-embed' ),
				'options' => array(
					'yes' => __( 'Yes', 'mihdan-lite-youtube-embed' ),
					'no'  => __( 'No', 'mihdan-lite-youtube-embed' ),
				),
				'default' => 'no',
				'desc'    => __( 'Enable if you want to lazy-load YouTube iframe Embeds<br />(not recommended, use WordPress YouTube Embeds instead).', 'mihdan-lite-youtube-embed' ),
			)
		);

		$this->wposa->add_field(
			'mlye_general',
			array(
				'id'      => 'hide_related_video',
				'type'    => 'select',
				'name'    => __( 'Hide Related Video', 'mihdan-lite-youtube-embed' ),
				'options' => array(
					'yes' => __( 'Yes', 'mihdan-lite-youtube-embed' ),
					'no'  => __( 'No', 'mihdan-lite-youtube-embed' ),
				),
				'default' => 'no',
				'desc'    => __( 'This option is for deleting the related video from another channel when using YouTube oEmbed.', 'mihdan-lite-youtube-embed' ),
			)
		);

		$this->wposa->add_field(
			'mlye_general',
			array(
				'id'      => 'preview_quality',
				'type'    => 'select',
				'name'    => __( 'Preview Quality', 'mihdan-lite-youtube-embed' ),
				'options' => array(
					'auto'          => __( 'Auto', 'mihdan-lite-youtube-embed' ),
					'sddefault'     => __( 'Standard Quality', 'mihdan-lite-youtube-embed' ),
					'mqdefault'     => __( 'Medium Quality', 'mihdan-lite-youtube-embed' ),
					'hqdefault'     => __( 'High Quality', 'mihdan-lite-youtube-embed' ),
					'maxresdefault' => __( 'Maximum Resolution', 'mihdan-lite-youtube-embed' ),
				),
				'default' => 'auto',
			)
		);

		$this->wposa->add_field(
			'mlye_general',
			array(
				'id'      => 'player_size',
				'type'    => 'radio',
				'name'    => __( 'Player Size', 'mihdan-lite-youtube-embed' ),
				'options' => array(
					'16x9'     => __( 'Responsive (16:9 player)', 'mihdan-lite-youtube-embed' ),
					'9x16'     => __( 'Shorts (9:16 player)', 'mihdan-lite-youtube-embed' ),
					'420x236'  => __( '420x236 (Mini 16:9 player)', 'mihdan-lite-youtube-embed' ),
					'560x315'  => __( '560x315 (Smaller 16:9 player)', 'mihdan-lite-youtube-embed' ),
					'640x360'  => __( '640x360 (YouTube default for 16:9-ratio video)', 'mihdan-lite-youtube-embed' ),
					'853x480'  => __( '853x480 (Larger 16:9 player)', 'mihdan-lite-youtube-embed' ),
					'1280x720' => __( '1280x720 (Maxi 16:9 player)', 'mihdan-lite-youtube-embed' ),
					'4x3'      => __( 'Responsive (4:3 player)', 'mihdan-lite-youtube-embed' ),
					'420x315'  => __( '420x315 (Smaller 4:3 player)', 'mihdan-lite-youtube-embed' ),
					'480x360'  => __( '480x360 (Standard value, YouTube default for 4:3-ratio video)', 'mihdan-lite-youtube-embed' ),
					'640x480'  => __( '640x480 (Larger 4:3 player)', 'mihdan-lite-youtube-embed' ),
					'960x720'  => __( '960x720 (Maxi 4:3 player)', 'mihdan-lite-youtube-embed' ),
				),
				'default' => '16x9',
			)
		);

		$this->wposa->add_section(
			array(
				'id'    => 'mlye_rutube',
				'title' => __( 'RuTube', 'mihdan-lite-youtube-embed' ),
			)
		);

		$this->wposa->add_field(
			'mlye_rutube',
			array(
				'id'          => 'api_key',
				'type'        => 'text',
				'name'        => __( 'API Key', 'mihdan-lite-youtube-embed' ),
				'placeholder' => 'AIzaSyDe12JAR7DaIzUSGFIfiMuPPIOf1YMaKr4',
				'desc'        => __( 'Plugin uses RuTube\'s API to fetch information on each video. <br />For your site to use that API, you will have to <a href="https://console.developers.google.com/apis/library" target="_blank">register</a> your site as a new application, <br />enable the RuTube API for it and get a server key and fill it out here.', 'mihdan-lite-youtube-embed' ),
			)
		);

		$this->wposa->add_section(
			array(
				'id'    => 'mlye_tools',
				'title' => __( 'Tools', 'mihdan-lite-youtube-embed' ),
			)
		);

		$this->wposa->add_field(
			'mlye_tools',
			array(
				'id'          => 'clear_cache',
				'type'        => 'checkbox',
				'name'        => __( 'Clear Cache', 'mihdan-lite-youtube-embed' ),
				'desc'        => __( 'Clear oEmbed cache for all posts.', 'mihdan-lite-youtube-embed' ),
			)
		);

		$this->wposa->add_section(
			array(
				'id'    => 'mlye_contacts',
				'title' => __( 'Contacts', 'mihdan-lite-youtube-embed' ),
			)
		);

		$this->wposa->add_field(
			'mlye_contacts',
			array(
				'id'   => 'description',
				'type' => 'html',
				'name' => __( 'Telegram', 'mihdan-lite-youtube-embed' ),
				'desc' => __( 'Связаться со мной можно в телеграм <a href="https://t.me/mihdan" target="_blank">@mihdan</a>', 'mihdan-lite-youtube-embed' ),
			)
		);

		$this->wposa->add_section(
			array(
				'id'    => 'mlye_plugins',
				'title' => __( 'Plugins', 'mihdan-lite-youtube-embed' ),
				'desc'  => __( 'Другие плагины автора', 'mihdan-lite-youtube-embed' ),
			)
		);

		$this->wposa->add_field(
			'mlye_plugins',
			array(
				'id'   => 'plugins',
				'type' => 'html',
				'name' => '',
				'desc' => function () {
					$transient = Utils::get_plugin_slug() . '-plugins';
					$cached    = get_transient( $transient );

					if ( false !== $cached ) {
						return $cached;
					}

					ob_start();
					require_once ABSPATH . 'wp-admin/includes/class-wp-plugin-install-list-table.php';
					$_POST['tab'] = Utils::get_plugin_slug();
					$table = new WP_Plugin_Install_List_Table();
					$table->prepare_items();


					$table->display();

					$content = ob_get_clean();
					set_transient( $transient, $content, 1 * DAY_IN_SECONDS );

					return $content;
				},
			)
		);
	}
}