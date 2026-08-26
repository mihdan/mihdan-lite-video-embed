<?php
/**
 * Интеграция с плагином Clearfy от CreativeMotion (Webcraftic)
 *
 * @package mihdan-lite-youtube-embed
 */

namespace Mihdan\LiteYouTubeEmbed\ThirdParty;

/**
 * Класс CreativeMotionClearfy.
 */
class CreativeMotionClearfy {
	/**
	 * Скрипты, которые не нужно минифицировать и склеивать
	 * с ассетами сайта.
	 */
	const JS_EXCLUDED = [
		'mihdan-lite-youtube-embed/assets/dist/js/frontend.js',
		'mihdan-lite-youtube-embed/assets/dist/js/lozad.js',
	];
	/**
	 * Инициализирует хуки.
	 *
	 * @return void
	 */
	public function setup_hooks(): void {
		add_filter( 'wmac_filter_js_exclude', [ $this, 'filter_js_exclude' ] );
		add_filter( 'wmac_filter_js_minify_excluded', [ $this, 'filter_js_minify_excluded' ], 10, 2 );
	}

	/**
	 * Исключает скрипты из процесса минимфикации.
	 *
	 * @param bool   $excluded Исключать ли файл.
	 * @param string $path     Путь к скрипту.
	 *
	 * @return bool
	 */
	public function filter_js_minify_excluded( bool $excluded, string $path ): bool {
		foreach ( self::JS_EXCLUDED as $asset ) {
			if ( strpos( $path, $asset ) !== false ) {
				return false;
			}
		}

		return $excluded;
	}

	/**
	 * Исключает скрипты из процесса склейки.
	 *
	 * @param string $excluded_scripts Скрипты по умолчанию.
	 *
	 * @return string
	 */
	public function filter_js_exclude( string $excluded_scripts ): string {
		return $excluded_scripts . ', ' . implode( ', ', self::JS_EXCLUDED );
	}
}
