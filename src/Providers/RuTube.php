<?php
/**
 * Class RuTube.
 *
 * @package mihdan-lite-youtube-embed
 */

namespace Mihdan\LiteYouTubeEmbed\Providers;

use Mihdan\LiteYouTubeEmbed\Embed;

class RuTube extends Embed {
	protected $schemes = [
		'#https?://(?:www\.)?rutube\.ru/(play|video|plst)/([^/]+)/?$#i',
		//'#https?://(?:www\.)?rutube\.ru/plst/([^/]+)/?$#i',
	];
	protected $id    = 'mlye-rutube';
	protected $oembed_url = 'https://rutube.ru/api/oembed/?url=https://rutube.ru/video/%s/';

	public function setup_hooks(): void {
		add_action( 'init', [ $this, 'register_handler' ] );
	}
	public function get_data( string $video_id ): array {

		$url = sprintf( $this->get_oembed_url(), $video_id );
		$request = wp_remote_get( $url );
		$body    = wp_remote_retrieve_body( $request );

		print_r($request);die;


		$result = [
			'duration' => '',
			'name' => $name,
			'description' => $this->sanitize_video_description( $description ),
			'upload_date' => $upload_date,
		];

		return [];
	}
	public function get_api_key(): string {
		return '';
	}
	public function validate_api_key(): string {
		return '';
	}
	public function get_preview_url(): string {
		return '';
	}
	public function register_handler(): void {

		foreach ( $this->get_schemes() as $scheme ) {
			wp_embed_register_handler(
				$this->get_id(),
				$scheme,
				[ $this, 'handler_callback' ]
			);
		}
	}

	public function handler_callback( array $matches, $attr, $url, $rawattr ): string {
		//$data = $this->get_data( $matches[3] );


		//print_r($matches);


		$embed = sprintf(
			'<iframe width="720" height="405" src="https://rutube.ru/%s/embed/%s" frameBorder="0" allow="clipboard-write" webkitAllowFullScreen mozallowfullscreen allowFullScreen></iframe>',
			esc_attr($matches[1]),
			esc_attr($matches[2])
		);

		return $embed;
	}

}