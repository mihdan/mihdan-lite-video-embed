=== Lite Video Embed ===
Contributors: mihdan
Tags: youtube, vk, rutube, video
Requires at least: 5.0
Tested up to: 7.1
Stable tag: 1.9.2
Requires PHP: 7.4
License: GPL v2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Donate link: https://www.kobzarev.com/donate/

A faster YouTube/RuTube embed.

== Description ==

A faster YouTube/RuTube/Vkontakte embed. Renders faster than a sneeze.

Provide videos with a supercharged focus on visual performance. This custom element renders just like the real thing but approximately 224X faster.

### ✅ For now, you can embed ###
- YouTube video
- RuTube video
- Vkontakte

### ⏳ Coming soon ###
- Odnoklassniki
- Дзен
- Кинескоп
- Vimeo
- TikTok

### ⛑️ Documentation and support

If you have some questions or suggestions, welcome to our [support forum](https://wordpress.org/support/plugin/mihdan-lite-youtube-embed/).

### 💙 Love Lite Video Embed?
If the plugin was useful, rate it with a [5 star rating](https://wordpress.org/support/plugin/mihdan-lite-youtube-embed/reviews/) and write a few nice words.

### 🏳️ Translations
- [Russian](https://translate.wordpress.org/locale/ru/default/wp-plugins/mihdan-lite-youtube-embed/) – (ru_RU)

Can you help with plugin translation? Please feel free to contribute!

### 🧪 Quality
The plugin's code is covered by an automated PHPUnit test suite and checked with PHPCS/Psalm on every change.

== Installation ==

This section describes how to install the plugin and get it working.

e.g.

1. Upload the plugin files to the `/wp-content/plugins/plugin-name` directory, or install the plugin through the WordPress plugins screen directly.
1. Activate the plugin through the 'Plugins' screen in WordPress
1. Use the Settings->Plugin Name screen to configure the plugin
1. (Make your instructions match the desired user flow for activating and installing your plugin. Include any steps that might be needed for explanatory purposes)

== Frequently Asked Questions ==

== Changelog ==

= 1.9.2 (29.08.2026) =
* Fixed a broken preview when embedding a YouTube playlist link
* The playlist preview now shows the title of its default video instead of the playlist's own title
* Fixed a fatal error when the video description is empty and no fallback description is set in plugin settings
* Fixed the responsive (16:9 etc.) preview image always loading a low-resolution thumbnail on wide containers by making the `sizes` attribute match the actual player width
* Fixed a "Translation loading was triggered too early" notice on WordPress 6.7+ by deferring plugin setup to the `init` hook

= 1.9.1 (26.08.2026) =
* Fixed the "Other plugins by the author" tab showing the wrong plugin list
* Fixed the plugin cards on that tab not laying out in a grid

= 1.9.0 (26.08.2026) =
* Added ability to embed VK videos
* Added support for the new vkvideo.ru domain and VK clips
* Added support for RuTube Shorts, live streams and playlists
* Fixed invalid video duration in schema.org markup for YouTube, RuTube and VK videos
* Removed a non-functional placeholder Gutenberg block

= 1.8.0 (02.05.2024) =
* Latte package has been removed
* Fixed RuTube player display error
* Fixed an error of displaying player in block editor

= 1.7.3 (30.11.2023) =
* Added integration with the Clearfy plugin from Creative Motion

= 1.7.2 (28.11.2023) =
* Added setting to clean up the database when the plugin is uninstalled
* Fixed a fatal error occurring when deleting the cache

= 1.7.1 (17.11.2023) =
* Fixed the hook `mlye/youtube/render`

= 1.7.0 (16.11.2023) =
* Added ability to embed RuTube videos
* Added support for WordPress 6.2 +
* Added the hook `mlye/youtube/render`
* Updated RegEx for iframe support

= 1.6.11 (17.01.2023) =
* Added support for YouTube Shorts (9:16)

= 1.6.10 (07.11.2022) =
* Added support for WordPress 6.1 +
* Added ability to hide related videos

= 1.6.9 (06.08.2022) =
* Added support for WordPress 6.0 +
* Added ability to automatically insert the name of the video in the description, if the YouTube API is not connected.

= 1.6.8 (06.12.2021) =
* Added support for PHP8
* Updated RegEx for iframe support

= 1.6.7 (06.12.2021) =
* Fixed bug with support YouTube iframe

= 1.6.6 (25.10.2021) =
* Fixed bug with muted video on mobile devices

= 1.6.5 (19.08.2021) =
* Added default margin for the player

= 1.6.4 (20.03.2021) =
* Added support for True Image & Lazy Load
* Added the ability to pass parameters to the YouTube player
* Fixed bug with duplicate Lozad library
* Fixed bug with a prev/next page embedding in Gutenberg Editor

= 1.6.3 (07.03.2021) =
* Fixed bug with a responsive video cover

= 1.6.2 (05.03.2021) =
* Added support for WPShop themes
* Wrapped player with a custom tag to prevent many bugs

= 1.6.1 (16.02.2021) =
* Remove OceanWP wrapper for YouTube videos.
* Fixed bug with `[embed]` shortcode
* Fixed bug with `wpautop`

= 1.6.0 (02.02.2021) =
* Added support for iframe version of YouTube embed
* Added support for Elementor page builder
* Added video title if no API key is specified
* Added option for setting HTTP requests timeout
* Added video title on the frontend
* Fixed bug with preview metadata
* Fixed bug with preview lazy load
* Fixed bug with preview position

= 1.5.3 (01.02.2021) =
* Added support for WordPress 5.6

= 1.5.2 (02.11.2020) =
* Added settings for auto get max preview size
* Fixed bugs with microdata

= 1.5.1 (29.10.2020) =
* Fixed notices

= 1.5.0 (28.10.2020) =
* Added lozad.js for Lazy Load support
* Added minify version of assets
* Fixed bugs

= 1.4.8 (21.10.2020) =
* Clear cache on plugin uninstall

= 1.4.7 (01.08.2020) =
* Added support for Gutenberg
* Code refactoring
* WPCS & PHPDoc

= 1.4.6 (31.07.2020) =
* Added support for short link
* Added support for embedUrl
* Added setting for default video description
* Added validation API key

= 1.4.5 (31.07.2020) =
* Fixed bug with microdata

= 1.4.4 (22.07.2020) =
* Fixed bugs
* Added OceanWP theme support
* Added responsive sizes to player
* Added microdata for video from API

= 1.4.3 (22.07.2020) =
* Fixed bugs
* Added support for player size
* Clear embed cache on plugin update

= 1.4 (21.07.2020) =
* Fixed bugs
* Added video duration support

= 1.3 (24.06.2020) =
* Fixed bugs
* Flush oEmbed cache on plugin activate/deactivate
* Flush oEmbed cache on plugin settings page