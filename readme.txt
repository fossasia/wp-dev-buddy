=== DevBuddy Twitter Feed ===
Contributors: Sandboxmode
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=XMXJEVPQ35YMJ
Tags: Twitter, Twitter Feed, Twitter 1.1, Twitter API, Twitter Shortcode, Twitter tweet, tweets, twitter, twitter connect, twitter share, twitter share button
Requires at least: 3.5
Tested up to: 3.5.2
Stable tag: 1.0.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

A Twitter (v1.1) feed plugin for the developers. It's flexible, supports multiple feeds, custom styling, and aims to make your dev process swift.

== Description ==

**Features**:

* Embed multiple Twitter timelines in your theme
* Use either a template tag or a shortcode to render feeds
* All feeds are cached on first render, reducing subsequent load times
* Well thought out and thorough HTML that is also compliant with Twitter's rules on displaying such feeds
* A default stylesheet is included that you can either use for display or study when creating your own

**Tips**:

* Multiple feeds on the same page could each easily be styled differently by wrapping each feed within an element with its own ID and using descendant selectors when styling elements for each.

* It is possible to move the plugin folder from the plugin directory into your theme's directory. Upon requiring `devbuddy-twitter-feed.php` in `functions.php` and changing the `DBTF_PATH` and `DBTF_URL` constants accordingly, you can use this plugin as if native to your theme with all functionality intact.

* You can hide this plugin's settings page by adding one simple line of code to your theme. Simply add `add_action('admin_menu', array($dbtf, 'hide_admin_page'), 999);` to your theme's functions.php file. If you've moved the plugin folder into your theme and included it, you'll need to ensure that this line comes **after** the include for it to work.

* Before this plugin can be used the end user will need to offer it Consumer and OAuth keys that can be obtained by creating an application at the [Twitter developers site](https://dev.twitter.com/apps/new). Further information on this can be found under the "Installation" tab.

== Installation ==

**Getting Started**

Before this plugin can be used the end user will need to offer it Consumer and OAuth keys that are used to authenticate your communication with Twitter. To obtain these:

1. Visit the [create application page](https://dev.twitter.com/apps/new) on the Twitter developers site. You may be required to sign in, your usual Twitter.com login credentials will work here
2. Fill in the necessary details and click the "Create you Twitter application" button at the bottom. Don't worry about being creative here, the details you put in won't be public (unless you make them public, that is)
3. If all goes well you'll be taken to the "Details" tab of the new app. Scroll down and look for the "Create Access Token" (or similar) button near the bottom of the page and click on it.
4. Finally, click on the "OAuth Tool" tab. This page holds the Consumer Key, Consumer Secret, Access Token, and Access Token Secret necessary for this plugin to function.

**Rendering the feed**

You can use either the:

* `<?php db_twitter_feed() ?>` template tag, which takes an associative array as its only parameter; or the
* `[db_twitter_feed]` shortcode

Both accept the same arguments/attributes which are all listed and explained below. All arguments/attributes are optional.

**Options set via tempate tag or shortcode take highest priority. If an option is not set in the tag/shortcode this plugin will then check to see if the option is set in the WordPress admin. If no options have been set the plugin will render with the defaults, listed below**

**user (string)**; *default*: twitterapi
> Any valid Twitter username.

**count (int)**; *default*: 10
> The number of tweets you want displayed. The maximum Twitter allows per request is at 200 but anything higher than 30 seems to noticeably affect the page load time, especially when loading multiple feeds on the one page.

**exclude_replies (string)**: `yes` or `no`; *default*: `no`
> The option of whether or not to keep replies out of the feed displayed. Go with `no` to keep replies in, `yes` to take them out. NOTE: Twitter removes replies only after it retrieves the number of tweets you request. Thus if you choose 10, and out of that 10 6 are replies, only 4 tweets will be displayed.

**default_styling (string)**: `yes` or `no`; *default*: `no`
> The option of whether or not to load the default stylesheet bundled with this plugin. Go with `yes` to load it, `no` to skip loading it. Bear in mind that once the stylesheet is loaded it is loaded to the page so all feeds on the page will be affected by it. Hence, when rendering multiple feeds you only need to `yes` with one, and leave it out of the others.

**clear_cache (string)**: `yes` or `no`; *default*: `no`
> Clears the cached version of the feed. If a cached version exists this plugin skips looking at the options altogether so this is a must if you're changing any options. If you're using either the template tag or the shortcode *without* passing information (i.e. all settings from settings page), the cache will be cleared each time the "Save Changes" button is clicked on the plugin's settings page. **This option will only work if the "user" option has been set**

**oauth_access_token**,
**oauth_access_token_secret**,
**consumer_key**,
**consumer_secret (string)**; *default*: N/A
> See the first part of the "Installation" tab to find out how to get these. They are necessary for authenticating your communication with Twitter and this plugin unfortunately won't work without them.

== Changelog ==

= 1.0 =
First release.

== Upgrade Notice ==

= 1.0 =
First release.