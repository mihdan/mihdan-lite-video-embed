# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## What this is

"Lite Video Embed" — a WordPress plugin (`mihdan/lite-youtube-embed`) that replaces heavy YouTube/RuTube/VK iframe embeds with a lightweight facade that loads faster, using `[embed]`/shortcode-style oEmbed handlers registered per provider.

## Commands

PHP tooling via Composer (PHP 7.4+ required):

```bash
composer run phpcs    # WordPress Coding Standards check (phpcs.xml)
composer run phpcbf    # auto-fix coding standard violations
composer run psalm     # static analysis (psalm.xml, has psalm-baseline.xml for pre-existing issues)
composer run tests     # PHPUnit test suite (phpunit.xml)
```

Run a single test file or method:

```bash
vendor/bin/phpunit tests/phpunit/tests/Providers/YouTubeTest.php
vendor/bin/phpunit --filter test_method_name
```

Frontend assets (SCSS/JS) via Gulp — outputs go to `assets/dist/`, which is what the plugin actually enqueues:

```bash
npx gulp          # default task (build + watch, see gulpfile.js for exact task names)
```

CI (`.github/workflows/ci.yml`) runs phpcs, psalm, and phpunit on PHP 7.4 and 8.2 on every push/PR to `master`.

## Architecture

- **Entry point**: `mihdan-lite-youtube-embed.php` defines plugin constants (`MIHDAN_LITE_YOUTUBE_EMBED_*`), loads Composer's autoloader, and instantiates `Main`. Everything lives under the `Mihdan\LiteYouTubeEmbed` namespace, PSR-4 autoloaded from `src/`.
- **`Main`** (`src/Main.php`) is the composition root: it wires up `Utils`, `Options` (settings API wrapper), `Settings` (admin settings page), instantiates each video `Provider`, and registers the WP hooks for asset enqueueing, cache clearing, and activation/deactivation.
- **`Provider`** (`src/Provider.php`) is an abstract base class all video sources extend (`src/Providers/YouTube.php`, `RuTube.php`, `VK.php`). Each provider implements:
  - `setup_hooks()` — registers its oEmbed handler / content filter.
  - `get_data()` / `get_fallback_data()` — fetch video metadata from the provider's API (with a fallback path, e.g. YouTube API key vs. plain oEmbed).
  - `handler_callback()` — the regex/oEmbed match callback that renders the embed markup.
  - `validate_api_key()`, `get_preview_url()`, `register_handler()`, `auto_embed_content()`.
  - `load_template()` renders the corresponding file in `templates/` (`templates/youtube.php`, `rutube.php`, `vk-video.php`) and minifies the output (strips newlines/tabs, collapses whitespace) before returning it — templates must produce valid HTML even when squashed onto one line.
- **`Options`** (`src/Options.php`) is a generic WP Settings API wrapper (WPOSA-style) used to register/render the plugin's admin settings sections and fields; `Settings` (`src/Settings.php`) defines the actual settings screens/fields for this plugin on top of it.
- **`Utils`** (`src/Utils.php`) provides static accessors for plugin path/URL/slug/version, reading from the constants defined in the main plugin file (or in `tests/phpunit/bootstrap.php` for tests) — prefer these over hardcoding paths or re-reading constants directly.
- **`ThirdParty/CreativeMotionClearfy.php`** contains compatibility shims for a specific caching/optimization plugin; keep third-party integrations isolated here rather than sprinkled into core classes.
- Provider API keys, timeouts, and behavior toggles (e.g. lazy load) are read via `Options::get( $key, $group )`, where `$group` is a settings group like `mlye_general`.

## Testing notes

- Tests use PHPUnit 9 + `10up/wp_mock` + `lucatume/function-mocker` (no full WordPress bootstrap) — `tests/phpunit/bootstrap.php` defines the plugin constants and calls `WP_Mock::bootstrap()`. Mock WP functions with `WP_Mock` rather than requiring WordPress core.
- Test classes mirror `src/` structure under `tests/phpunit/tests/` (e.g. `tests/phpunit/tests/Providers/` for provider tests).
