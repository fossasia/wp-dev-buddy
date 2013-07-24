<?php
class DB_Twitter_feed {

	public function __construct() {
		add_action('wp_enqueue_scripts', array($this, 'register_default_styling'));
		add_shortcode('db_twitter_feed', array($this, 'register_twitter_feed_sc'));
	}

	/*	Select a specific option from anywhere in
		the theme with a great deal of ease */

	/*	Simply pass the name of the option to the
		function in the form of a string */
	/************************************************/
	public function get_dbtf_option($option) {
		global $db_twitter_feed_option_name;

		$options = get_option($db_twitter_feed_option_name);
		return $options[$option];
	}


	/*	Sort of step–by–step check of the options
		set by the user */

	/*	If no value has been set for this option by
		the user this function will return the
		value passed in the 3rd param, or false if
		no 3rd param has been passed */
	/************************************************/
	public function check_option_isset($option, $option_name, $default = false) {
		$stored_value = $this->get_dbtf_option($option_name);

		if($option_name === 'twitter_username' && $option != '') {
			return $option;

		} else {
			if($option_name !== 'twitter_username' && ($option === 'yes' || $option === 'no')) {
				return $option;

			} else {
				if($stored_value != '') {
					return $stored_value;

				} else {
					return $default;
				}
			}
		}
	}


	/*	The HTML output of the feed is cached upon
		being rendered. This function is for
		emptying the cache should you need to */

	/*	It should also be noted that when the user
		clicks the "Save Changes" button in the
		plugin's option page this function is
		called and the value of the Twitter username
		field is offered as the param */

	/*	A specific cache can be deleted by setting
		$clear_cache = true when calling the feed
		via function or shortcode and ensuring that
		the $user is set */
	/************************************************/
	public function empty_feed_cache($user) {
		$user_output = 'db_twitter_feed_output_'.$user;

		delete_transient($user_output);
	}


	/*	Register the default styling for displaying
		feed */

	/*	Only when $default_styling = true || 1 will
		the stylesheet be enqueued */
	/************************************************/
	public function register_default_styling() {
		wp_register_style('dbtf-default', DBTF_URL.'/assets/feed.css', null, '1.0', 'all');
	}


	/*	Adds the shortcode that can be used to render
		the feed */

	/*	This shortcode simply passes data
		to the db_twitter_feed() function, which
		does all of the actual work */
	/************************************************/
	public function register_twitter_feed_sc($given_atts) {
		$default_atts =
		array(
			'user'						=> null,
			'count'						=> null,
			'exclude_replies'			=> null,
			'default_styling'			=> null,
			'clear_cache'				=> null,
			'oauth_access_token'		=> null,
			'oauth_access_token_secret'	=> null,
			'consumer_key'				=> null,
			'consumer_secret'			=> null
		);

		extract(
			shortcode_atts($default_atts, $given_atts)
		);

		$feed_config =
		array(
			'user'						=> $user,
			'count'						=> $count,
			'exclude_replies'			=> $exclude_replies,
			'default_styling'			=> $default_styling,
			'clear_cache'				=> $clear_cache,
			'oauth_access_token'		=> $oauth_access_token,
			'oauth_access_token_secret'	=> $oauth_access_token_secret,
			'consumer_key'				=> $consumer_key,
			'consumer_secret'			=> $consumer_secret
		);

		return db_twitter_feed($feed_config);
	}


	/*	Hide that there admin page. Useful for
		1:1 developer–client situations where you
		want to hide the magic from the client */

	/*	You need to add the following line to either
		the theme's functions.php file or the plugin's
		lib/functions.php file, within __contruct():

		add_action('admin_menu', array($dbtf, 'hide_admin_page'), 999);

	/*	Exchange $dbtf for $this when using line
		within __contruct() */
	/************************************************/
	public function hide_admin_page() {
		remove_submenu_page('options-general.php', 'db-twitter-feed-settings');
	}


	/*	Format the date based on what the time
		that the data given represents */
	/************************************************/
	public function formatify_date($datetime) {
		define('HOUR', 60*60);
		define('DAY', HOUR*24);
		define('WEEK', DAY*7);

		$now = mktime();
		$then = strtotime($datetime);
		$diff = $now - $then;

		$mins = $diff / 60 % 60;
		$the_mins_ago = $mins;
		$the_mins_ago .= ($mins == '1') ? ' minute ago' : ' minutes ago';

		$hours = $diff / 3600 % 24;
		$the_hours_ago = 'About ';
		$the_hours_ago .= $hours;
		$the_hours_ago .= ($hours == '1') ? ' hour ago' : ' hours ago';

		$the_time = date('H:i', $then);
		$the_day = date('D', $then);
		$the_date = date('j M', $then);


		if($diff < HOUR) {
			return $the_mins_ago;

		} elseif($diff > HOUR && $diff < DAY) {
			return $the_hours_ago;

		} elseif($diff > DAY && $diff < WEEK) {
			return $the_time.', '.$the_day;

		} else {
			return $the_date;
		}
	}


	/*	The following "linkify" functions look for
		specific components within the tweet text
		and converts them to links using the data
		provided by Twitter */

	/*	@Mouthful
		Each function accepts an array holding arrays.
		Each array held within the array represents
		an instance of a linkable item within that
		particular tweet and has named keys
		representing useful data to do with that
		instance of the linkable item */

	/*	It's slightly different for the hashtags but
		I can't remember why */
	/************************************************/
	public function linkify_hashtags($tweet, $hashtags) {
		$search = 'https://twitter.com/search?q=%23';

		if($hashtags !== null) {
			foreach($hashtags as $hashtag) {
				$tweet = str_replace(
					'#'.$hashtag,
					'<a href="'.$search.$hashtag.'" target="_blank" title="Search Twitter for \''.$hashtag.'\' ">#'.$hashtag.'</a>',
					$tweet
				);
			}

			return $tweet;

		} else {
			return $tweet;
		}
	}

	public function linkify_mentions($tweet, $mentions) {
		$twitter = 'https://twitter.com/';

		if(is_array($mentions) && count($mentions) !== 0) {
			foreach($mentions as $mention) {
				$count = count($mentions);
				for($i = 0; $i < $count; $i++) {
					$tweet = preg_replace(
						'|@'.$mentions[$i]['screen_name'].'|',
						'<a href="'.$twitter.$mentions[$i]['screen_name'].'" target="_blank" title="'.$mentions[$i]['name'].'">@'.$mentions[$i]['screen_name'].'</a>',
						$tweet
					);
				}
				return $tweet;
			}

		} else {
			return $tweet;
		}
	}

	public function linkify_links($tweet, $urls) {
		if(is_array($urls) && count($urls) !== 0) {
			foreach($urls as $url) {
				$count = count($urls);
				for($i = 0; $i < $count; $i++) {
					$tweet = str_replace(
						$urls[$i]['short_url'],
						'<a href="'.$urls[$i]['short_url'].'" target="_blank">'.$urls[$i]['display_url'].'</a>',
						$tweet
					);
				}
			return $tweet;
			}

		} else {
			return $tweet;
		}
	}

	public function linkify_media($tweet, $media) {
		if(is_array($media) && count($media) !== 0) {
			foreach($media as $item) {
				$count = count($media);
				for($i = 0; $i < $count; $i++) {
					$tweet = str_replace(
						$media[$i]['short_url'],
						'<a href="'.$media[$i]['short_url'].'" target="_blank">'.$media[$i]['display_url'].'</a>',
						$tweet
					);
				}
			return $tweet;
			}

		} else {
			return $tweet;
		}
	}
}