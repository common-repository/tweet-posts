=== Tweet Posts ===
Contributors: betzster
Tags: shorturl, twitter
Requires at least: 3.4
Tested up to: 3.4.1
Stable tag: 0.3

Tweet your posts.

== Description ==

Tweets when you post, obviously. It looks at the post format to figure out what the message should look like. Also adds the appropriate meta tags to activate [Twitter Cards](https://dev.twitter.com/docs/cards).

== Installation ==

1. Upload `tweet-posts` to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Apply for a Twitter API key at http://dev.twitter.com/apps
4. Update the Twitter API information in the Options -> Tweet Posts menu

If you'd like to use Twitter Cards, apply for that on the [Twitter Dev](https://dev.twitter.com/form/participate-twitter-cards) site as well.

== Changelog ==

= 0.3 =
* Rewrite of the Twitter-OAuth interface to use the WordPress HTTP API instead of using cURL directly.

= 0.2.1 =
* Bug fix: Didn't show Twitter API info in the admin

= 0.2 =
* Add support for Twitter Cards
* Add Twitter to user profiles

= 0.1.1 =
* Fix bug where tweets would be sent even if the post wasn't new

= 0.1 =
* Initial plugin
