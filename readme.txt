=== Mihdan: Lite YouTube Embed ===
Contributors: mihdan
Donate link: https://www.kobzarev.com/donate/
Tags: youtube, wordpress, seo-friendly, seo, cache, embed
Requires at least: 5.0
Tested up to: 6.1
Stable tag: 1.6.11
Requires PHP: 5.6.20
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A faster youtube embed.

== Description ==

A faster youtube embed. Renders faster than a sneeze.

Provide videos with a supercharged focus on visual performance. This custom element renders just like the real thing but approximately 224X faster.

== Installation ==

This section describes how to install the plugin and get it working.

e.g.

1. Upload the plugin files to the `/wp-content/plugins/plugin-name` directory, or install the plugin through the WordPress plugins screen directly.
1. Activate the plugin through the 'Plugins' screen in WordPress
1. Use the Settings->Plugin Name screen to configure the plugin
1. (Make your instructions match the desired user flow for activating and installing your plugin. Include any steps that might be needed for explanatory purposes)

== Frequently Asked Questions ==

== Changelog ==

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
* Fixed bug with support Youtube iframe

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
* Added micro data for video from API

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