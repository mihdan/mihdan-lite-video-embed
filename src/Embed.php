<?php
/**
 * Class Embed.
 *
 * @package mihdan-lite-youtube-embed
 */

namespace Mihdan\LiteYouTubeEmbed;

/**
 * Abstract Class Embed
 */
abstract class Embed {
	protected $id = '';
	protected $schemes = [];
	protected $oembed_url = '';

	protected function get_id(): string {
		return $this->id;
	}

	protected function get_oembed_url(): string {
		return $this->oembed_url;
	}

	protected function get_schemes(): array {
		return $this->schemes;
	}

	abstract public function setup_hooks(): void;
	abstract public function get_data( string $video_id ): array;
	abstract public function get_api_key(): string;
	abstract public function validate_api_key(): string;
	abstract public function get_preview_url(): string;
	abstract public function register_handler(): void;
	abstract public function handler_callback( array $matches, $attr, $url, $rawattr ): string;
}