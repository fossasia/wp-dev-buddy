<?php

/**
* A class for rendering the Twitter feed
*
* @version 1.0.1
*/
if ( ! class_exists( 'DB_Twitter_Feed' ) ) {

class DB_Twitter_Feed extends DB_Twitter_Feed_Base {

	/**
	* @var string Main Twitter URL
	*/
	public $tw = 'https://twitter.com/';

	/**
	* @var string Twitter search URL
	*/
	public $search = 'https://twitter.com/search?q=%23';

	/**
	* @var string Twitter intent URL
	*/
	public $intent = 'https://twitter.com/intent/';


	/**
	* Configure data necessary for rendering the feed
	*
	* Get the feed configuration provided by the user
	* and use defaults for options not provided, check
	* for a cached version of the feed under the given
	* user, initialise a Twitter API object.
	*
	* @access public
	* @return void
	* @since 1.0.0
	*/
	public function __construct( $feed_config ) {
		$this->set_main_admin_vars();

	/*	Populate the $options property with the config options submitted
		by the user. Should any of the options not be set, fall back on
		stored values, then defaults */
		if ( ! is_array( $feed_config ) ) {
			$feed_config = array();
		}

		foreach ( $this->options as $option => $value ) {
			if ( ! array_key_exists( $option, $feed_config ) || $feed_config[ $option ] === NULL ) {
				if ( $option === 'user' ) {
					$stored_value = $this->get_db_plugin_option( $this->options_name_main, 'twitter_username' );
				} else {
					$stored_value = $this->get_db_plugin_option( $this->options_name_main, $option );
				}

				if ( $stored_value !== FALSE ) {
					$this->options[ $option ] = $stored_value;
				} else {
					$this->options[ $option ] = $value;
				}

			} elseif ( array_key_exists( $option, $feed_config ) ) {
				$this->options[ $option ] = $feed_config[ $option ];
			}
		}

	/*	The shortcode delivered feed config brings with it
		the 'is_shortcode_called' option */

	/*	As the above check is based on the items in the
		$options property array, this option is ignored
		by the check even if defined in the config by
		the user because it isn't defined in $options */
		if ( isset( $feed_config['is_shortcode_called'] ) && $feed_config['is_shortcode_called'] === TRUE ) {
			$this->is_shortcode_called = TRUE;
		}

	/*	Check to see if there is a cache available with the
		username provided. Move into the output if so */

	/*	However, we don't do this without first checking
		whether or not a clearance of the cache has been
		requested */
		if ( $this->options['clear_cache'] === 'yes' ) {
			$this->clear_cache_output( $this->options['user'] );
		}

		$this->output = get_transient( $this->plugin_name.'_output_'.$this->options['user'] );
		if ( $this->output !== FALSE ) {
			$this->is_cached = TRUE;

		} else {
			$this->is_cached = FALSE;
		}

		// Load the bundled stylesheet if requested
		if ( $this->options['default_styling'] === 'yes' ) {
			$this->load_default_styling();
		}

		// Get Twitter object
		$auth_data = array(
			'oauth_access_token'        => $this->options['oauth_access_token'],
			'oauth_access_token_secret' => $this->options['oauth_access_token_secret'],
			'consumer_key'              => $this->options['consumer_key'],
			'consumer_secret'           => $this->options['consumer_secret']
		);

		$this->twitter = new TwitterAPIExchange( $auth_data );
	}


	/**
	* Based on a limited number of config options, retrieve the raw feed (JSON)
	*
	* @access public
	* @return void
	* @since 1.0.0
	*/
	public function retrieve_feed_data() {
		// Establish the destination point of the request, check for additional params
		$request_method = 'GET';
		$url            = 'https://api.twitter.com/1.1/statuses/user_timeline/'.$this->options['user'].'.json';
		$get_field      = '?screen_name='.$this->options['user'].'&count='.$this->options['count'];

		if ( $this->options['exclude_replies'] === 'yes' ) {
			$get_field .= '&exclude_replies=true';
		}

		$this->feed_data = $this->twitter->setGetfield( $get_field )
		                                 ->buildOauth( $url, $request_method )
		                                 ->performRequest();

		$this->feed_data = json_decode( $this->feed_data );
	}


	/**
	* Check that the timeline queried actually has tweets
	*
	* @access public
	* @return bool An indication of whether or not the returned feed data has any renderable entries
	* @since 1.0.1
	*/
	public function is_empty() {
		if ( is_array( $this->feed_data ) && count( $this->feed_data ) === 0 ) {
			return TRUE;

		} else {
			return FALSE;
		}
	}


	/**
	* Loop through the feed data and render the HTML of the feed
	*
	* The output is stored in the $output property
	*
	* @access public
	* @return void
	* @since 1.0.0
	*/
	public function render_feed_html() {

	/*	If Twitter's having none of it (most likely due to
		bad config) then we get the errors and display them
		to the user */
		if ( is_object( $this->feed_data ) && is_array( $this->feed_data->errors ) ) :
			$this->output .= '<pre>Twitter has returned errors:';

			foreach ( $this->feed_data->errors as $error ) {
				$this->output .= '<br />- &ldquo;'.$error->message.' [error code: '.$error->code.']&rdquo;';
			}

			$this->output .= '</pre>';

		// If all is well, we get on with it
		else :
			$this->output .= '<div class="tweets">';
			//$this->output .= var_dump($this->feed_data);

			foreach ( $this->feed_data as $tweet ) {
				$this->render_tweet_html( $tweet );
			}

			$this->output .= '</div>';

		endif;
	}


	/**
	* Takes a tweet object and renders the HTML for that tweet
	*
	* The output is stored in the $output property
	*
	* @access public
	* @return void
	* @since 1.0.0
	*/
	public function render_tweet_html( $t ) {
		$tweet_is_retweet = ( isset( $t->retweeted_status ) ) ? true : false;


	/*	User data */
	/************************************************/
		if ( ! $tweet_is_retweet ) {
			$tweet_user_id           = $t->user->id_str;
			$tweet_user_display_name = $t->user->name;
			$tweet_user_screen_name  = $t->user->screen_name;
			$tweet_user_description  = $t->user->description;
			$tweet_profile_img_url   = $t->user->profile_image_url;

			if ( isset( $t->user->entities->url->urls ) ) {
				$tweet_user_urls = array();
				foreach ( $t->user->entities->url->urls as $url_data ) {
					$tweet_user_urls['short_url']   = $url_data->url;
					$tweet_user_urls['full_url']    = $url_data->expanded_url;
					$tweet_user_urls['display_url'] = $url_data->display_url;
				}
			}


		/*	When Twitter shows a retweet, the account that has
			been retweeted is shown rather than the retweeter's */

		/*	To emulate this we need to grab the necessary data
			that Twitter has thoughtfully made available to us */
		} elseif ( $tweet_is_retweet ) {
			$retweeter_display_name  = $t->user->name;
			$retweeter_screen_name   = $t->user->screen_name;
			$retweeter_description   = $t->user->description;
			$tweet_user_id           = $t->retweeted_status->user->id_str;
			$tweet_user_display_name = $t->retweeted_status->user->name;
			$tweet_user_screen_name  = $t->retweeted_status->user->screen_name;
			$tweet_user_description  = $t->retweeted_status->user->description;
			$tweet_profile_img_url   = $t->retweeted_status->user->profile_image_url;

			if ( isset( $t->retweeted_status->url ) ) {
				$tweet_user_urls = array();
				foreach ( $t->retweeted_status->user->entities->url->urls as $url_data ) {
					$tweet_user_urls['short_url']   = $url_data->url;
					$tweet_user_urls['full_url']    = $url_data->expanded_url;
					$tweet_user_urls['display_url'] = $url_data->display_url;
				}
			}
		}


	/*	Tweet data */
	/************************************************/
		$tweet_id				= $t->id_str;
		$tweet_text				= $t->text;

		if ( (int) $this->options['cache_hours'] <= 2 ) {
			$tweet_date    = $this->formatify_date( $t->created_at );
		} else {
			$tweet_date    = $this->formatify_date( $t->created_at, FALSE );
		}

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
				'id'			=> $mention_data->id_str
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
					'id'			=> $media_data->id_str,
					'type'			=> $media_data->type,
					'short_url'		=> $media_data->url,
					'media_url'		=> $media_data->media_url,
					'display_url'	=> $media_data->display_url,
					'expanded_url'	=> $media_data->expanded_url
				);
			}
		}


	/*	Clean up and format the tweet text */
	/************************************************/
		if($tweet_is_retweet) {
			// Shave unnecessary "RT [@screen_name]: " from the tweet text
			$char_count = strlen('@'.$tweet_user_screen_name) + 5;
			$shave_point = (0 - strlen($tweet_text)) + $char_count;
			$tweet_text = substr($tweet_text, $shave_point);
		}
		$tweet_text = ( isset( $tweet_hashtags['text'] ) ) ? $this->linkify_hashtags($tweet_text, $tweet_hashtags['text']) : $tweet_text;
		$tweet_text = ( isset( $tweet_mentions ) ) ? $this->linkify_mentions($tweet_text, $tweet_mentions) : $tweet_text;
		$tweet_text = ( isset( $tweet_urls ) ) ? $this->linkify_links($tweet_text, $tweet_urls) : $tweet_text;
		$tweet_text = ( isset( $tweet_media ) ) ? $this->linkify_media($tweet_text, $tweet_media) : $tweet_text;


	/*	Begin rendering HTML for the tweet */
	/************************************************/
		$this->output .= '<article class="tweet">';

		$this->output .= '<div class="tweet_content">';

		$this->output .= '<figure class="tweet_profile_img">';
		$this->output .= '<a href="'.$this->tw.$tweet_user_screen_name.'" target="_blank" title="'.$tweet_user_display_name.'"><img src="'.$tweet_profile_img_url.'" alt="'.$tweet_user_display_name.'" /></a>';
		$this->output .= '</figure>';

		$this->output .= '<header class="tweet_header">';
		$this->output .= '<a href="'.$this->tw.$tweet_user_screen_name.'" target="_blank" class="tweet_user" title="'.$tweet_user_description.'">'.$tweet_user_display_name.'</a>';
		$this->output .= ' <span class="tweet_screen_name">@'.$tweet_user_screen_name.'</span>';
		$this->output .= '</header>';

		$this->output .= '<div class="tweet_text">'.$tweet_text.'</div>';

		$this->output .= '<div class="tweet_footer">';
		$this->output .= '<a href="'.$this->tw.$tweet_user_screen_name.'/status/'.$tweet_id.'" target="_blank" title="View this tweet in Twitter" class="tweet_date">'.$tweet_date.'</a>';

		if($tweet_is_retweet) {
			$this->output .= '<span class="tweet_retweet">';
			$this->output .= '<span class="tweet_icon tweet_icon_retweet"></span>';
			$this->output .= 'Retweeted by ';
			$this->output .= '<a href="'.$this->tw.$retweeter_screen_name.'" target="_blank" title="'.$retweeter_display_name.'">'.$retweeter_display_name.'</a>';
			$this->output .= '</span>';
		}

		$this->output .= '<div class="tweet_intents">';
		$this->output .= '<a href="'.$this->intent.'tweet?in_reply_to='.$tweet_id.'" title="Reply to this tweet" target="_blank" class="tweet_intent_reply">';
		$this->output .= '<span class="tweet_icon tweet_icon_reply"></span>';
		$this->output .= '<b>Reply</b></a>';

		$this->output .= '<a href="'.$this->intent.'retweet?tweet_id='.$tweet_id.'" title="Retweet this tweet" target="_blank" class="tweet_intent_retweet">';
		$this->output .= '<span class="tweet_icon tweet_icon_retweet"></span>';
		$this->output .= '<b>Retweet</b></a>';

		$this->output .= '<a href="'.$this->intent.'favorite?tweet_id='.$tweet_id.'" title="Favourite this tweet" target="_blank" class="tweet_intent_favourite">';
		$this->output .= '<span class="tweet_icon tweet_icon_favourite"></span>';
		$this->output .= '<b>Favourite</b></a>';
		$this->output .= '</div>'; // END tweet_intents

		$this->output .= '</div>'; // END tweet_footer
		$this->output .= '</div>'; // END tweet_content

		$this->output .= '</article>'; // END tweet
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


	/**
	* Echo whatever is currently stored in the DB_Twitter_Feed::output property to the page
	*
	* This method also calls the DevBuddy_Feed_Plugin::cache_output() method
	*
	* @access public
	* @return void
	* @uses DevBuddy_Feed_Plugin::cache_output() to cache the output before it's echoed
	*
	* @since 1.0.0
	*/
	public function echo_output() {
		$this->cache_output( $this->options['cache_hours'] );
		echo $this->output;
	}
} // END class

} // END class_exists

?>