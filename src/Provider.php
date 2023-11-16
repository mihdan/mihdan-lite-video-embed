<?php
/**
 * Class Provider.
 *
 * @package mihdan-lite-youtube-embed
 */

namespace Mihdan\LiteYouTubeEmbed;

use Exception;

/**
 * Abstract Class Provider
 */
abstract class Provider {
	/**
	 * Provider identifier.
	 *
	 * @var string
	 */
	protected string $id;

	/**
	 * Provider schemes.
	 *
	 * @var array
	 */
	protected array $schemes = [];

	/**
	 * Provider oEmbed URL.
	 *
	 * @var string
	 */
	protected string $oembed_url;

	/**
	 * HTTP arguments for wp_remote_get.
	 *
	 * @var array
	 */
	protected array $http_args = [
		'timeout'     => 5,
		'httpversion' => '1.1',
		'user-agent'  => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/110.0.0.0 Safari/537.36',
	];

	/**
	 * API URL.
	 *
	 * @var string
	 */
	protected string $api_url;

	/**
	 * API key.
	 *
	 * @var string
	 */
	protected string $api_key;

	/**
	 * Provider iframe template.
	 *
	 * @var string
	 */
	protected string $template;

	/**
	 * Constructor.
	 */
	public function __construct() {
		echo 'construct init' . PHP_EOL;
	}

	/**
	 * Get provider identifier.
	 *
	 * @return string
	 */
	protected function get_id(): string {
		return Utils::get_plugin_slug() . '-' . $this->id;
	}

	/**
	 * Get oEmbed URL.
	 *
	 * @return string
	 */
	protected function get_oembed_url(): string {
		return $this->oembed_url;
	}

	/**
	 * Get API URL.
	 *
	 * @return string
	 */
	protected function get_api_url(): string {
		return $this->api_url;
	}

	/**
	 * Get iframe template for Provider.
	 *
	 * @return string
	 */
	protected function get_template(): string {
		return $this->template;
	}

	/**
	 * Get supported schemes.
	 *
	 * @return array
	 */
	protected function get_schemes(): array {
		return $this->schemes;
	}

	/**
	 * Get HTTP arguments.
	 *
	 * @return array
	 */
	protected function get_http_args(): array {
		return $this->http_args;
	}

	/**
	 * Sanitize video description.
	 *
	 * @param string $description Description.
	 *
	 * @return string
	 */
	protected function sanitize_video_description( string $description ): string {
		return wp_strip_all_tags( str_replace( PHP_EOL, ' ', $description ) );
	}

	/**
	 * Get API key.
	 *
	 * @return string
	 */
	protected function get_api_key(): string {
		return $this->api_key;
	}

	/**
	 * Init hooks.
	 *
	 * @return void
	 */
	abstract public function setup_hooks(): void;

	/**
	 * Get data from API by Video ID.
	 *
	 * @param string $video_id Video ID.
	 *
	 * @return array
	 */
	abstract public function get_data( string $video_id ): array;

	/**
	 * Парсит переданный текст, делая auto-embed.
	 *
	 * @param string $content Текст для парсинга.
	 *
	 * @return string
	 */
	abstract public function auto_embed_content( string $content ): string;

	/**
	 * Get fallback data from API by Video ID.
	 *
	 * @param string $video_id Video ID.
	 *
	 * @return array
	 */
	abstract public function get_fallback_data( string $video_id ): array;

	/**
	 * Validate API key.
	 *
	 * @param  string $api_key API key.
	 *
	 * @return array
	 */
	abstract public function validate_api_key( string $api_key ): array;

	/**
	 * Get preview URL by Video ID.
	 *
	 * @param string $video_id Video ID.
	 *
	 * @return string
	 */
	abstract public function get_preview_url( string $video_id ): string;

	/**
	 * Register oEmbed Handler.
	 *
	 * @return void
	 */
	abstract public function register_handler(): void;

	/**
	 * Callback for register new video provider.
	 *
	 * @param array  $matches Matches.
	 * @param array  $attr    Shortcode attributes. Optional.
	 * @param string $url     The URL attempting to be embedded.
	 * @param array  $rawattr Raw shortcode attributes. Optional.
	 *
	 * @return string
	 * @throws Exception Exception.
	 */
	abstract public function handler_callback( array $matches, array $attr, string $url, array $rawattr ): string;
}