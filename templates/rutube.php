<?php
/**
 * Шаблон для вывода плеера RuTube.
 *
 * @var bool $use_lazy_load
 * @var bool $use_microdata
 * @var string $player_class
 * @var string $player_src
 * @var string $video_id
 * @var string $name
 * @var string $description
 * @var string $duration
 * @var string $upload_date
 * @var string $url
 * @var string $embed_url
 * @var string $preview_url
 * @var string $player_width
 * @var string $player_height
 *
 * @package mihdan-lite-youtube-embed
 */

ob_start();

if ( $use_lazy_load ) {
	?>
	<lite-youtube class="<?php echo esc_attr( $player_class ); ?>" video-id="<?php echo esc_attr( $video_id ); ?>" player-src="<?php echo esc_url( $player_src ); ?>">
		<lite-youtube__button class="lite-youtube__button lty-playbtn"></lite-youtube__button>
		<lite-youtube__preview class="lite-youtube__preview">
			<img data-src="<?php echo esc_attr( $preview_url ); ?>"
			     data-placeholder-background="#000"
			     width='<?php echo esc_attr( $player_width ); ?>'
			     class="lite-youtube__image lite-youtube_lazy mihdan-lozad"
			     height='<?php echo esc_attr( $player_height ); ?>'
			     alt="<?php echo esc_attr( $name ); ?>" />
		</lite-youtube__preview>
		<lite-youtube__name class="lite-youtube__name"><?php echo esc_html( $name ); ?></lite-youtube__name>
	</lite-youtube>
	<?php
} else {
	?>
	<lite-youtube class="<?php echo esc_attr( $player_class ); ?>" video-id="<?php echo esc_attr( $video_id ); ?>" player-src="<?php echo esc_url( $player_src ); ?>">
		<lite-youtube__button class="lite-youtube__button lty-playbtn"></lite-youtube__button>
		<lite-youtube__preview class="lite-youtube__preview">
			<img src="<?php echo esc_attr( $preview_url ); ?>"
			     width='<?php echo esc_attr( $player_width ); ?>'
			     class="lite-youtube__image"
			     height='<?php echo esc_attr( $player_height ); ?>'
			     alt="<?php echo esc_attr( $name ); ?>" />
		</lite-youtube__preview>
		<lite-youtube__name class="lite-youtube__name"><?php echo esc_html( $name ); ?></lite-youtube__name>
	</lite-youtube>
	<?php
}

if ( $use_microdata ) {
	?>
	<div style="display: none" itemprop="video" itemscope itemtype="https://schema.org/VideoObject">
		<meta itemprop="name" content="<?php echo esc_attr( $name ); ?>" />
		<meta itemprop="description" content="<?php echo esc_attr( $description ); ?>" />
		<meta itemprop="duration" content="<?php echo esc_attr( $duration ); ?>" />
		<meta itemprop="uploadDate" content="<?php echo esc_attr( $upload_date ); ?>" />
		<meta itemprop="datePublished" content="<?php echo esc_attr( $upload_date ); ?>" />
		<meta itemprop="isFamilyFriendly" content="true" />

		<link itemprop="url" href="<?php echo esc_attr( $url ); ?>" />
		<link itemprop="embedUrl" href="<?php echo esc_attr( $embed_url ); ?>" />

		<link itemprop="thumbnailUrl" href="<?php echo esc_attr( $preview_url ); ?>">
		<span itemprop="thumbnail" itemscope="" itemtype="http://schema.org/ImageObject">
			<link itemprop="contentUrl" href="<?php echo esc_attr( $preview_url ); ?>" />
			<meta itemprop="width" content="<?php echo esc_attr( $player_width ); ?>">
			<meta itemprop="height" content="<?php echo esc_attr( $player_height ); ?>">
		</span>
	</div>
	<?php
}

return ob_get_clean();
