=== DevBuddy Twitter Feed ===
Contributors: EjiOsigwe
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=XMXJEVPQ35YMJ
Tags: Twitter, Twitter Feed, Twitter 1.1, Twitter API, Twitter Shortcode, Twitter tweet, tweets, Twitter, Twitter connect, Twitter share, Twitter share button, DevBuddy
Requires at least: 3.1.0
Tested up to: 3.6
Stable tag: 2.1.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

A Twitter (v1.1) feed plugin for the developers. It's flexible, supports multiple feeds, custom styling, and aims to make your dev process swift.

== Description ==

**NOTE: This plugin requires your server to have cURL enabled to work.**

**Features**:

* Nice and simple settings page
* Embed multiple Twitter timelines on one page
* Use either a template tag or a shortcode to render feeds
* All feeds are cached on first render to reduce subsequent load times, along with the option to choose who many hours the cache lasts
* Well thought out and thorough HTML that is also compliant with Twitter's rules on displaying their feeds
* A default stylesheet is included that you can use either for display or for study when creating your own
* Sensitive OAuth and Consumer data is masked within the WordPress admin to prevent unauthorised access to your app data

**Tips**:

* Multiple feeds on the same page could each easily be styled differently by wrapping each feed within an element with its own ID and using descendant selectors when styling elements for each.

* It is possible to move the plugin folder from the plugin directory into your theme's directory. Upon requiring `devbuddy-twitter-feed.php` in `functions.php` and changing the `DBTF_PATH` and `DBTF_URL` constants accordingly, you can use this plugin as if native to your theme with all functionality intact.

* You can hide this plugin's settings page by adding one simple line of code to your theme. Simply add `if ( is_object( $dbtf ) ) { $dbtf->hide_wp_admin_menu_item(); }` to your theme's functions.php file. If you've moved the plugin folder into your theme and included it, you'll need to ensure that this line comes **after** the include for it to work.

* Before this plugin can be used the end user will need to offer it Consumer and OAuth keys that can be obtained by creating an application at the [Twitter developers site](https://dev.twitter.com/apps/new). Further information on this can be found under the "Installation" tab.

== Installation ==

**Getting Started**

Before this plugin can be used the end user will need to offer it Consumer and OAuth keys that are used to authenticate your communication with Twitter. To obtain these:

1. Visit the [create application page](https://dev.twitter.com/apps/new) on the Twitter developers site. You may be required to sign in, your usual Twitter.com login credentials will work here
2. Fill in the necessary details and click the "Create your Twitter application" button at the bottom. Don't worry about being creative here, the details you put in won't be public (unless you make them public, that is)
3. If all goes well you'll be taken to the "Details" tab of the new app. Scroll down and look for the "Create my access token" button near the bottom of the page and click on it.
4. Finally, click on the "OAuth Tool" tab. This page holds the Consumer Key, Consumer Secret, Access Token, and Access Token Secret necessary for this plugin to function. Copy them over into your settings.

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

**cache_hours (int)**; *default*: 0
> The number of hours you would like the feed cached for. The cache is saved using WordPress' own `set_transient()` function which means that when it is cached once, it is then cached for every subsequent visitor to your site for the duration that the cache exists.

**clear_cache (string)**: `yes` or `no`; *default*: `no`
> Clears the cached version of the feed. If a cached version exists this plugin skips looking at the options altogether so this is a must if you're changing any options. If you're using either the template tag or the shortcode *without* passing information (i.e. all settings from settings page), the cache will be cleared each time the "Save Changes" button is clicked on the plugin's settings page. **This option will only work if the "user" option has been set**

**consumer_key**,
**consumer_secret**,
**oauth_access_token**,
**oauth_access_token_secret (string)**; *default*: N/A
> See the first part of the "Installation" tab to find out how to get these. They are necessary for authenticating your communication with Twitter and this plugin unfortunately won't work without them.

== Changelog ==

= 2.1.0 =
* Default stylesheet has been updated to be responsive, and to match your theme's appearance as much as possible
* Despite the work in the 2.0.2 release, the empty timeline feedback didn't render but it does now
* Minor refactor work on the code to make it less bug and error prone

= 2.0.3 =
Bug Fix: Using the shortcode to render the feed in the WordPress editor places the feed within the content rather than directly above it.

= 2.0.2 =
* Bug fix: The feed now extracts the string versions of IDs rather than the integer versions. This means long IDs are no longer susceptible to being read mathmetically, i.e. 372489002391470081 instead of 3.7248900239147E+17. 
* The feed now offers friendly feedback should the timeline requested be empty.

= 2.0.1 =
Minor rectifications to code that prevented the defaut stylesheet from loading

= 2.0.0 =
* Complete overhaul of the plugin's code. Code is now much more modular and refined
* `cache_hours` was added and implemented as a feed configuration option
* Addition of masking/unmasking facilities utilised within the admin to hide sensitive OAuth and Consumer Key/Secret data

= 1.0.1 =
Amendment of plugin description and settings page to include important and useful information.

= 1.0.0 =
First release.

== Upgrade Notice ==

= 2.1.0 =
Default stylesheet has been updated to be responsive, and to match your theme's appearance as much as possible. That and some code cleanup.

= 2.0.3 =
Fixes a bug that meant the feed would be render before the content, rather than within, if the shortcode was used in the WordPress editor.

= 2.0.2 =
Fixes a bug that led to IDs being read mathematically. As some of the links rendered by the feed use these IDs, those links may have been faulty as a result.

= 2.0.1 =
Minor rectifications to code that prevented the default stylesheet from loading. Update to be able to take advantage of the bundled stylesheet.

= 2.0.0 =
The plugin code structure has undergone considerable changes but this won't be noticeable to the user. Additionally, you can now change the number of hours that the feed is cached for and sensitive OAuth and Consumer Key/Secret data is now masked in the admin.

= 1.0.1 =
Amendment of plugin description and settings page to include important and useful information. Not an urgent upgrade.

= 1.0.0 =
First release.