<?php

/**
 * Class Settings.
 *
 * @package mihdan-lite-youtube-embed
 */

namespace Mihdan\LiteYouTubeEmbed;

use Elementor\Controls_Manager;
use Elementor\Core\DynamicTags\Manager;
use Elementor\Plugin;
use Elementor\Widget_Base;

class Elementor extends Widget_Base {

	/**
	 * Widget constructor.
	 *
	 * @param array $data Data.
	 * @param null  $args Arguments.
	 *
	 * @throws \Exception Exception.
	 */
	public function __construct( $data = array(), $args = null ) {
		parent::__construct( $data, $args );
		$this->setup();
	}

	/**
	 * Setup variables.
	 */
	public function setup() {

	}

	/**
	 * Retrieve heading widget name.
	 *
	 * @since 1.0.0
	 * @access public
	 *
	 * @return string Widget name.
	 */
	public function get_name() {
		return 'mihdan-lite-youtube-embed';
	}

	/**
	 * Retrieve heading widget title.
	 *
	 * @since 1.0.0
	 * @access public
	 *
	 * @return string Widget title.
	 */
	public function get_title() {
		return __( 'Lite YouTube Embed', 'mihdan-lite-youtube-embed' );
	}

	/**
	 * Retrieve heading widget icon.
	 *
	 * @since 1.0.0
	 * @access public
	 *
	 * @return string Widget icon.
	 */
	public function get_icon() {
		return 'elementor-yandex-map-icon';
	}

	/**
	 * Retrieve the list of categories the widget belongs to.
	 *
	 * Used to determine where to display the widget in the editor.
	 *
	 * Note that currently Elementor supports only one category.
	 * When multiple categories passed, Elementor uses the first one.
	 *
	 * @since 1.0.0
	 *
	 * @access public
	 *
	 * @return array Widget categories.
	 */
	public function get_categories() {
		return array( 'mihdan' );
	}

	/**
	 * Get script depends
	 *
	 * @return array
	 */
	public function get_script_depends() {
		return array( 'mihdan-elementor-yandex-maps-api', 'mihdan-elementor-yandex-maps' );
	}


	/**
	 * Register yandex maps widget controls.
	 *
	 * Adds different input fields to allow the user to change and customize the widget settings.
	 *
	 * @since 1.0.0
	 * @access protected
	 */
	protected function _register_controls() {

		/**
		 * Настройки карты
		 */
		$this->start_controls_section(
			'section_map',
			array(
				'label' => __( 'Map', 'mihdan-elementor-yandex-maps' ),
			)
		);

		$this->add_control(
			'map_notice',
			array(
				'label'       => __( 'Find Latitude & Longitude', 'mihdan-elementor-yandex-maps' ),
				'type'        => Controls_Manager::RAW_HTML,
				'raw'         => '<form onsubmit="return mihdan_elementor_yandex_maps_find_address( this );"><input type="search" style="margin-top:10px; margin-bottom:10px;" placeholder="' . __( 'Enter Search Address', 'mihdan-elementor-yandex-maps' ) . '" /><input type="submit" value="Search" class="elementor-button elementor-button-default"></form><div id="eb-output-result" class="eb-output-result" style="margin-top:10px; line-height: 1.3; font-size: 12px;"></div>',
				'label_block' => true,
			)
		);



		$this->end_controls_section();
	}

	/**
	 * Render yandex maps widget output on the frontend.
	 *
	 * Written in PHP and used to generate the final HTML.
	 *
	 * @since 1.0.0
	 * @access protected
	 */
	protected function render() {
		$settings = $this->get_settings_for_display();


	}
}
