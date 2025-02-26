<?php
namespace Mihdan\LiteYouTubeEmbed\Blocks;

class Block {
	public function setup_hooks() : void {
		add_action( 'init', [ $this, 'register_block' ] );
	}

	public function register_block(): void {
		register_block_type(
			'mihdan/lite-video-embed',
			array(
				'title' => 'zalupa',
				'description' => 'zalupa',
				'category' => 'widgets',
				'icon' => 'admin-links',
				'api_version' => 3,
				'render_callback' => function( array $block_attributes, string $content ): string {
					return 'zzzzzzz';
				},
			)
		);
	}
}