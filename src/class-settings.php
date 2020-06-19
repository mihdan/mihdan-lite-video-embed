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
				'id'      => 'use_microdata',
				'type'    => 'select',
				'name'    => __( 'Use Microdata', 'mihdan-lite-youtube-embed' ),
				'options' => array(
					'yes' => __( 'Yes', 'mihdan-lite-youtube-embed' ),
					'no'  => __( 'No', 'mihdan-lite-youtube-embed' ),
				),
				'default' => 'yes',
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