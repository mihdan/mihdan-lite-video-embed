<?php
/**
 * Class Settings.
 *
 * @package mihdan-lite-youtube-embed
 */

namespace Mihdan\LiteYouTubeEmbed;

/**
 * Class Settings.
 *
 * @package mihdan-lite-youtube-embed
 */
class Settings {
	/**
	 * WP_OSA instance.
	 *
	 * @var WP_OSA $wposa
	 */
	private $wposa;

	public function __construct( WP_OSA $wposa ) {
		$this->wposa = $wposa;

		$this->setup_hooks();
		$this->setup_fields();
	}

	public function setup_hooks() {}
	public function setup_fields() {
		$this->wposa->add_section(
			array(
				'id'    => 'mlye_general',
				'title' => __( 'General', 'mihdan-lite-youtube-embed' ),
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
				'id'      => 'preview_quality',
				'type'    => 'select',
				'name'    => __( 'Preview Quality', 'mihdan-lite-youtube-embed' ),
				'options' => array(
					'sddefault'     => __( 'Standard Quality', 'mihdan-lite-youtube-embed' ),
					'hqdefault'     => __( 'High Quality', 'mihdan-lite-youtube-embed' ),
					'mqdefault'     => __( 'Medium Quality', 'mihdan-lite-youtube-embed' ),
					'maxresdefault' => __( 'Maximum Resolution', 'mihdan-lite-youtube-embed' ),
				),
				'default' => 'sddefault',
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
	}
}