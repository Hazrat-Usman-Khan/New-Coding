=== Article Freshness Checker ===
Contributors: Plugin User
Donate link:
Tags: article, freshness, stale, outdated, content, post, update
Requires at least: 5.0
Tested up to: 6.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Helps you identify and manage stale content on your WordPress site by labeling articles that haven't been updated recently.

== Description ==

The Article Freshness Checker plugin automatically monitors the last updated date of your posts. If a post hasn't been modified within a customizable time period (default is 30 days), it will be visually marked with a "(Needs Update)" label next to its title on your website's front end. This provides a clear visual cue to both site administrators and visitors that the content might be outdated.

You can easily configure the staleness threshold (in days) from the plugin's settings page located under "Settings" > "Article Freshness" in your WordPress admin area.

This plugin helps you:
* Keep your content relevant and up-to-date.
* Identify articles that require attention.
* Improve user experience by signaling potentially outdated information.

== Installation ==

1.  Upload the `article-freshness-checker` folder to the `/wp-content/plugins/` directory.
2.  Activate the plugin through the 'Plugins' menu in WordPress.
3.  Go to "Settings" > "Article Freshness" to configure the staleness threshold if needed.

== Frequently Asked Questions ==

= How do I change when an article is considered "stale"? =

You can change the staleness threshold (the number of days after which an article is marked as needing an update) by going to "Settings" > "Article Freshness" in your WordPress admin panel.

= What does the "(Needs Update)" label mean? =

This label indicates that the article has not been modified for a period equal to or exceeding the "Staleness Threshold" defined in the plugin's settings. It suggests that the content may be outdated and could benefit from a review or update.

= Where does the label appear? =

The label currently appears next to the post title on single post pages and in the main loop.

== Screenshots ==

1.  The "(Needs Update)" label appearing next to a post title.
2.  The Article Freshness Checker settings page in the WordPress admin area.

== Changelog ==

= 1.0.0 =
* Initial release.
* Adds "(Needs Update)" label to stale posts.
* Configurable staleness threshold via settings page.

== Upgrade Notice ==

= 1.0.0 =
Initial release of the plugin.
