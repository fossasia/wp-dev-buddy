<?php
/*
Provide this function with no parameters to use
the settings set on the plugin's settings page */

/*
If user doesn't want to use the settings page
or wishes to add additional feeds with diff
configs, they should provide an array with
the details of config to this function */

/*
If the config array doesn't contain all of the
necessary data, the function will grab what's
missing from whatever is set within the plugin's
settings page */

/*
Should the settings page also be barren of
the info we need we'll use the defaults that
are set within this function */
function db_twitter_feed($feed_config = null) {

	global $dbtf;


/*	The shortcode for this plugin calls this
	function. If no attributes are set, we
	assume use of the settings on the plugin
	settings page, i.e. the default feed set up */

/*	An attributeless shortcode will return an
	array of null values so is_array doesn't
	quite suffice as a shortcode check. */
	if(is_array($feed_config)) {
		$config_max_count = count($feed_config);
		$config_null_count = 0;
		foreach($feed_config as $config_item) {
			if(is_null($config_item)) $config_null_count++;
		}

		if($config_null_count < $config_max_count) {
			extract($feed_config);
		}
	}


/**************************************************************************************************************
 Configuration
 **************************************************************************************************************/
	// Load the default stylesheet if requested
	$default_styling = $dbtf->check_option_isset($default_styling, 'default_styling', 'no');
	if($default_styling === 'yes' || (int)$default_styling === 1)
		wp_enqueue_style('dbtf-default');


	// Check for a cached version of the feed
	$user = $dbtf->check_option_isset($user, 'twitter_username', 'twitterapi');

	if($clear_cache === 'yes') {
		$dbtf->empty_feed_cache($user);
		$cached_output = false;

	} else {
		$cached_output = get_transient('db_twitter_feed_output_'.$user);
	}


/*	If there's no output cached using the current $user
	we set up to retrieve data anew */
	if($cached_output === false) {
		// Obtain/set up the information necessary to perform data request
		$oauth_access_token =
		($oauth_access_token === null) ? $dbtf->get_dbtf_option('oauth_access_token') : $oauth_access_token;

		$oauth_access_token_secret =
		($oauth_access_token_secret === null) ? $dbtf->get_dbtf_option('oauth_access_token_secret') : $oauth_access_token_secret;

		$consumer_key =
		($consumer_key === null) ? $dbtf->get_dbtf_option('consumer_key') : $consumer_key;

		$consumer_secret =
		($consumer_secret === null) ? $dbtf->get_dbtf_option('consumer_secret') : $consumer_secret;


		$auth_data = array(
			'oauth_access_token'		=>	$oauth_access_token,
			'oauth_access_token_secret'	=>	$oauth_access_token_secret,
			'consumer_key'				=>	$consumer_key,
			'consumer_secret'			=>	$consumer_secret
		);

		$count = $dbtf->check_option_isset($count, 'result_count', 10);
		$exclude_replies = $dbtf->check_option_isset($exclude_replies, 'exclude_replies', 'no');


		// Establish the destination point of the request, check for additional params
		$url = 'https://api.twitter.com/1.1/statuses/user_timeline/'.$user.'.json';
		$request_method = 'GET';
		$get_field = '?screen_name='.$user.'&count='.$count;

		if($exclude_replies === 'yes' || (int)$exclude_replies === 1) {
			$get_field .= '&exclude_replies=true';
		}


		// Perform the request and retrieve and decode the data
		$twitter = new TwitterAPIExchange($auth_data);
		$twitter_data = $twitter->setGetfield($get_field)
								->buildOauth($url, $request_method)
								->performRequest();

	/*	Decode and store data.
		Returns an array, each item of which being an object
		representative of a tweet and its data */
		$tweets = json_decode($twitter_data);


	} else {
		echo $cached_output;
		return;
	}


/**************************************************************************************************************
 Parse and render
 **************************************************************************************************************/
	$twitter = 'https://twitter.com/';
	$search = $twitter.'search?q=%23';
	$intent = $twitter.'intent/';
	$output = '';

/*	If Twitter's having none of it (most likely due to
	bad config) then we get the errors and display them
	to the user */
	if(is_object($tweets) && is_array($tweets->errors)) :
		$output .= '<pre>Twitter has returned errors:';
		foreach($tweets->errors as $error) {
			$output .= '<br />- &ldquo;'.$error->message.' [error code: '.$error->code.']&rdquo;';
		}
		$output .= '</pre>';


	// If all is well, we get on with it
	else :
		// Open the feed
		$output .= '<div class="tweets">';

		foreach($tweets as $t) {
			$tweet_is_retweet = (isset($t->retweeted_status)) ? true : false;


		/*	User data */
		/************************************************/
			if(!$tweet_is_retweet) {
				$tweet_user_id				= $t->user->id;
				$tweet_user_display_name	= $t->user->name;
				$tweet_user_screen_name		= $t->user->screen_name;
				$tweet_user_description		= $t->user->description;
				$tweet_profile_img_url		= $t->user->profile_image_url;

				if(isset($t->user->entities->url->urls)) {
					$tweet_user_urls = array();
					foreach($t->user->entities->url->urls as $url_data) {
						$tweet_user_urls['short_url']	= $url_data->url;
						$tweet_user_urls['full_url']	= $url_data->expanded_url;
						$tweet_user_urls['display_url']	= $url_data->display_url;
					}
				}


			/*	When Twitter shows a retweet, the account that has
				been retweeted is shown rather than the retweeter's */

			/*	To emulate this we need to grab the necessary data
				that Twitter has thoughtfully made available to us */
			} elseif($tweet_is_retweet) {
				$retweeter_display_name		= $t->user->name;
				$retweeter_screen_name		= $t->user->screen_name;
				$retweeter_description		= $t->user->description;
				$tweet_user_id				= $t->retweeted_status->user->id;
				$tweet_user_display_name	= $t->retweeted_status->user->name;
				$tweet_user_screen_name		= $t->retweeted_status->user->screen_name;
				$tweet_user_description		= $t->retweeted_status->user->description;
				$tweet_profile_img_url		= $t->retweeted_status->user->profile_image_url;

				if(isset($t->retweeted_status->url)) {
					$tweet_user_urls = array();
					foreach($t->retweeted_status->user->entities->url->urls as $url_data) {
						$tweet_user_urls['short_url']	= $url_data->url;
						$tweet_user_urls['full_url']	= $url_data->expanded_url;
						$tweet_user_urls['display_url']	= $url_data->display_url;
					}
				}
			}


		/*	Tweet data */
		/************************************************/
			$tweet_id				= $t->id;
			$tweet_text				= $t->text;
			$tweet_date_unix		= strtotime($t->created_at);
			$tweet_date				= $dbtf->formatify_date(date('Y-m-d H:i:s', $tweet_date_unix));
			$tweet_user_replied_to	= $t->in_reply_to_screen_name;

			$tweet_hashtags = array();
			foreach($t->entities->hashtags as $ht_data) {
				$tweet_hashtags['text'][]		= $ht_data->text;
				$tweet_hashtags['indices'][]	= $ht_data->indices;
			}

			$tweet_mentions = array();
			foreach($t->entities->user_mentions as $mention_data) {
				$tweet_mentions[] = array(
					'screen_name'	=> $mention_data->screen_name,
					'name'			=> $mention_data->name,
					'id'			=> $mention_data->id
				);
			}

			$tweet_urls = array();
			foreach($t->entities->urls as $url_data) {
				$tweet_urls[] = array(
					'short_url'		=> $url_data->url,
					'expanded_url'	=> $url_data->expanded_url,
					'display_url'	=> $url_data->display_url
				);
			}

			if(isset($t->entities->media)) {
				$tweet_media = array();
				foreach($t->entities->media as $media_data) {
					$tweet_media[] = array(
						'id'			=> $media_data->id,
						'type'			=> $media_data->type,
						'short_url'		=> $media_data->url,
						'media_url'		=> $media_data->media_url,
						'display_url'	=> $media_data->display_url,
						'expanded_url'	=> $media_data->expanded_url
					);
				}
			}


		/*	Clean up and format the tweet's output text */
		/************************************************/
			if($tweet_is_retweet) {
				// Shave unnecessary "RT @screen_name: " from the tweet text
				$char_count = strlen('@'.$tweet_user_screen_name) + 5;
				$shave_point = (0 - strlen($tweet_text)) + $char_count;
				$tweet_text = substr($tweet_text, $shave_point);
			}
			$tweet_text = $dbtf->linkify_hashtags($tweet_text, $tweet_hashtags['text']);
			$tweet_text = $dbtf->linkify_mentions($tweet_text, $tweet_mentions);
			$tweet_text = $dbtf->linkify_links($tweet_text, $tweet_urls);
			$tweet_text = $dbtf->linkify_media($tweet_text, $tweet_media);


		/*	Begin rendering HTML for the tweet */
		/************************************************/
			$output .= '<article class="tweet">';

			$output .= '<div class="tweet_content">';

			$output .= '<figure class="tweet_profile_img">';
			$output .= '<a href="'.$twitter.$tweet_user_screen_name.'" target="_blank" title="'.$tweet_user_display_name.'"><img src="'.$tweet_profile_img_url.'" alt="'.$tweet_user_display_name.'" /></a>';
			$output .= '</figure>';

			$output .= '<header class="tweet_header">';
			$output .= '<a href="'.$twitter.$tweet_user_screen_name.'" target="_blank" class="tweet_user" title="'.$tweet_user_description.'">'.$tweet_user_display_name.'</a>';
			$output .= ' <span class="tweet_screen_name">@'.$tweet_user_screen_name.'</span>';
			$output .= '</header>';

			$output .= '<div class="tweet_text">'.$tweet_text.'</div>';

			$output .= '<div class="tweet_footer">';
			$output .= '<a href="'.$twitter.$tweet_user_screen_name.'/status/'.$tweet_id.'" target="_blank" title="View this tweet in Twitter" class="tweet_date">'.$tweet_date.'</a>';

			if($tweet_is_retweet) {
				$output .= '<span class="tweet_retweet">';
				$output .= '<span class="tweet_icon tweet_icon_retweet"></span>';
				$output .= 'Retweeted by ';
				$output .= '<a href="'.$twitter.$retweeter_screen_name.'" target="_blank" title="'.$retweeter_display_name.'">'.$retweeter_display_name.'</a>';
				$output .= '</span>';
			}

			$output .= '<div class="tweet_intents">';
			$output .= '<a href="'.$intent.'tweet?in_reply_to='.$tweet_id.'" title="Reply to this tweet" target="_blank" class="tweet_intent_reply">';
			$output .= '<span class="tweet_icon tweet_icon_reply"></span>';
			$output .= '<b>Reply</b></a>';

			$output .= '<a href="'.$intent.'retweet?tweet_id='.$tweet_id.'" title="Retweet this tweet" target="_blank" class="tweet_intent_retweet">';
			$output .= '<span class="tweet_icon tweet_icon_retweet"></span>';
			$output .= '<b>Retweet</b></a>';

			$output .= '<a href="'.$intent.'favorite?tweet_id='.$tweet_id.'" title="Favourite this tweet" target="_blank" class="tweet_intent_favourite">';
			$output .= '<span class="tweet_icon tweet_icon_favourite"></span>';
			$output .= '<b>Favourite</b></a>';
			$output .= '</div>'; // END tweet_intents

			$output .= '</div>'; // END tweet_footer
			$output .= '</div>'; // END tweet_content

			$output .= '</article>'; // END tweet
		}

		// Close the feed
		$output .= '</div>';

		// Cache the rendered HTML for up to an hour
		set_transient('db_twitter_feed_output_'.$user, $output, HOUR_IN_SECONDS);

	endif;

	echo $output; // Spew it up
}