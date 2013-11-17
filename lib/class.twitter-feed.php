<?php

/**
* A class for rendering the Twitter feed
*
* This is the class to call at the top when scripting
* the template tag. Be sure to offer a $feed_config
* array as the parameter to properly initialise the
* object instance.
*
* @version 1.0.3
*/
if ( ! class_exists( 'DB_Twitter_Feed' ) ) {

class DB_Twitter_Feed extends DB_Twitter_Feed_Base {

	/**
	* @var array A holding place for parsed tweet data
	* @since 1.0.3
	*/
	protected $tweet;

	/**
	* @var object Holds an instance of the HTML rendering class
	* @since 1.0.3
	*/
	public $html;

	/**
	* @var string Main Twitter URL
	* @since 1.0.0
	*/
	public $tw = 'https://twitter.com/';

	/**
	* @var string Twitter search URL
	* @since 1.0.0
	*/
	public $search = 'https://twitter.com/search?q=%23';

	/**
	* @var string Twitter intent URL
	* @since 1.0.0
	*/
	public $intent = 'https://twitter.com/intent/';

	/**
	* @var array If there are any errors after a check is made for such, they will be stored here
	* @since 1.0.2
	*/
	public $errors;


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

		foreach ( $this->defaults as $option => $value ) {
			if ( ! array_key_exists( $option, $feed_config ) || $feed_config[ $option ] === NULL ) {
				if ( $option === 'user' ) {
					$stored_value = $this->get_option( $this->options_name_main, 'twitter_username' );

				} elseif( $option === 'count' ) {
					$stored_value = $this->get_option( $this->options_name_main, 'result_count' );

				} else {
					$stored_value = $this->get_option( $this->options_name_main, $option );

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


		// Load the HTML rendering class
		$url_data = array(
			'tw'     => $this->tw,
			'search' => $this->search,
			'intent' => $this->intent
		);
		$this->html = new DB_Twitter_HTML( $this->options, $url_data );


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
	* Check to see if any errors have been returned when retrieving feed data
	*
	* Be sure to use this only after the retrieve_feed_data() method has been
	* called as that's the method that first populates the $feed_data property
	* that this method checks.
	*
	* @access public
	* @return bool
	* @since 1.0.2
	*/
	public function has_errors() {

		if ( is_object( $this->feed_data ) && is_array( $this->feed_data->errors ) ) {
			$this->errors = $this->feed_data->errors;
			return TRUE;

		} else {
			return FALSE;

		}

	}


	/**
	* Parse and return useful tweet data from an individual tweet in an array
	*
	* This is best utilised within an iteration loop that iterates through a
	* populated $feed_data.
	*
	* @access public
	* @return array Tweet data from the tweet item given
	* @since 1.0.2
	*/
	public function parse_tweet_data( $t ) {

		$tweet = array();

		$tweet['is_retweet'] = ( isset( $t->retweeted_status ) ) ? TRUE : FALSE;


	/*	User data */
	/************************************************/
		if ( ! $tweet['is_retweet'] ) {
			$tweet['user_id']           = $t->user->id_str;
			$tweet['user_display_name'] = $t->user->name;
			$tweet['user_screen_name']  = $t->user->screen_name;
			$tweet['user_description']  = $t->user->description;
			$tweet['profile_img_url']   = $t->user->profile_image_url;

			if ( isset( $t->user->entities->url->urls ) ) {
				$tweet['user_urls'] = array();
				foreach ( $t->user->entities->url->urls as $url_data ) {
					$tweet['user_urls']['short_url']   = $url_data->url;
					$tweet['user_urls']['full_url']    = $url_data->expanded_url;
					$tweet['user_urls']['display_url'] = $url_data->display_url;
				}
			}


		/*	When Twitter shows a retweet, the account that has
			been retweeted is shown rather than the retweeter's */

		/*	To emulate this we need to grab the necessary data
			that Twitter has thoughtfully made available to us */
		} elseif ( $tweet['is_retweet'] ) {
			$tweet['retweeter_display_name']  = $t->user->name;
			$tweet['retweeter_screen_name']   = $t->user->screen_name;
			$tweet['retweeter_description']   = $t->user->description;
			$tweet['user_id']           = $t->retweeted_status->user->id_str;
			$tweet['user_display_name'] = $t->retweeted_status->user->name;
			$tweet['user_screen_name']  = $t->retweeted_status->user->screen_name;
			$tweet['user_description']  = $t->retweeted_status->user->description;
			$tweet['profile_img_url']   = $t->retweeted_status->user->profile_image_url;

			if ( isset( $t->retweeted_status->url ) ) {
				$tweet['user_urls'] = array();
				foreach ( $t->retweeted_status->user->entities->url->urls as $url_data ) {
					$tweet['user_urls']['short_url']   = $url_data->url;
					$tweet['user_urls']['full_url']    = $url_data->expanded_url;
					$tweet['user_urls']['display_url'] = $url_data->display_url;
				}
			}
		}


	/*	Tweet data */
	/************************************************/
		$tweet['id']				= $t->id_str;
		$tweet['text']				= $t->text;

		if ( (int) $this->options['cache_hours'] <= 2 ) {
			$tweet['date']          = $this->formatify_date( $t->created_at );
		} else {
			$tweet['date']          = $this->formatify_date( $t->created_at, FALSE );
		}

		$tweet['user_replied_to']	= $t->in_reply_to_screen_name;

		$tweet['hashtags'] = array();
		foreach ( $t->entities->hashtags as $ht_data ) {
			$tweet['hashtags']['text'][]    = $ht_data->text;
			$tweet['hashtags']['indices'][] = $ht_data->indices;
		}

		$tweet['mentions'] = array();
		foreach ( $t->entities->user_mentions as $mention_data ) {
			$tweet['mentions'][] = array(
				'screen_name' => $mention_data->screen_name,
				'name'        => $mention_data->name,
				'id'          => $mention_data->id_str
			);
		}

		$tweet['urls'] = array();
		foreach ( $t->entities->urls as $url_data ) {
			$tweet['urls'][] = array(
				'short_url'    => $url_data->url,
				'expanded_url' => $url_data->expanded_url,
				'display_url'  => $url_data->display_url
			);
		}

		if ( isset( $t->entities->media ) ) {
			$tweet['media'] = array();
			foreach ( $t->entities->media as $media_data ) {
				$tweet['media'][] = array(
					'id'           => $media_data->id_str,
					'type'         => $media_data->type,
					'short_url'    => $media_data->url,
					'media_url'    => $media_data->media_url,
					'display_url'  => $media_data->display_url,
					'expanded_url' => $media_data->expanded_url
				);
			}
		}


	/*	Clean up and format the tweet text */
	/************************************************/
		if ( $tweet['is_retweet'] ) {
			// Shave unnecessary "RT [@screen_name]: " from the tweet text
			$char_count  = strlen( '@'.$tweet['user_screen_name'] ) + 5;
			$shave_point = ( 0 - strlen( $tweet['text'] ) ) + $char_count;
			$tweet['text']  = substr( $tweet['text'], $shave_point );
		}

		$tweet['text'] =
		( isset( $tweet['hashtags']['text'] ) ) ? $this->linkify_hashtags( $tweet['text'], $tweet['hashtags']['text'] ) : $tweet['text'];

		$tweet['text'] =
		( isset( $tweet['mentions'] ) ) ? $this->linkify_mentions( $tweet['text'], $tweet['mentions'] ) : $tweet['text'];

		$tweet['text'] =
		( isset( $tweet['urls'] ) ) ? $this->linkify_links( $tweet['text'], $tweet['urls'] ) : $tweet['text'];

		$tweet['text'] =
		( isset( $tweet['media'] ) ) ? $this->linkify_media( $tweet['text'], $tweet['media'] ) : $tweet['text'];

		return $tweet;

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
		if ( $this->has_errors() ) {
			$this->output .= '<p>Twitter has returned errors:</p>';
			$this->output .= '<ul>';

			foreach ( $this->errors as $error ) {
				$this->output .= '<li>&ldquo;'.$error->message.' [error code: '.$error->code.']&rdquo;</li>';
			}

			$this->output .= '</ul>';
			$this->output .= '<p>More information on errors <a href="https://dev.twitter.com/docs/error-codes-responses" target="_blank" title="Twitter API Error Codes and Responses">here</a>.</p>';

	/*	If the timeline of the user requested is empty we
		let the user know */
		} elseif( $this->is_empty() ) {
			$this->output .= '<p>Looks like your timeline is completely empty!<br />Why don&rsquo;t you <a href="'.$this->tw.'" target="_blank">login to Twitter</a> and post a tweet or two.</p>';

		// If all is well, we get on with it
		} else {
			$this->output .= '<div class="tweets">';

			foreach ( $this->feed_data as $tweet ) {
				$this->render_tweet_html( $tweet );
			}

			$this->output .= '</div>';

		}

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
	public function render_tweet_html( $the_tweet ) {

		$this->tweet = $this->parse_tweet_data( $the_tweet );

		$this->html->set( $this->tweet );

		// START Rendering the Tweet's HTML (outer tweet wrapper)
		$this->output .= $this->html->open_tweet();


			// START Tweet content (inner tweet wrapper)
			$this->output .= $this->html->open_tweet_content();


				// START Tweeter's display picture
				$this->output .= $this->html->tweet_display_pic();
				// END Tweeter's display picture


				// START Tweet user info
				$this->output .= $this->html->open_tweet_primary_meta();
					$this->output .= $this->html->tweet_display_name_link();
				$this->output .= $this->html->close_tweet_primary_meta();
				// END Tweet user info


				// START Actual tweet
				$this->output .= $this->html->tweet_text();
				// END Actual tweet


				// START Tweet meta data
				$this->output .= $this->html->open_tweet_secondary_meta();
					$this->output .= $this->html->tweet_date();
					$this->output .= $this->html->tweet_retweeted();
					$this->output .= $this->html->tweet_intents();
				$this->output .= $this->html->close_tweet_secondary_meta();
				// END Tweet meta data


			$this->output .= $this->html->close_tweet_content();
			// END Tweet content


		$this->output .= $this->html->close_tweet();
		// END Rendering Tweet's HTML

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
	public function linkify_hashtags( $tweet, $hashtags ) {

		$search = 'https://twitter.com/search?q=%23';

		if ( $hashtags !== NULL ) {
			foreach ( $hashtags as $hashtag ) {
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

	public function linkify_mentions( $tweet, $mentions ) {

		$twitter = 'https://twitter.com/';

		if ( is_array( $mentions ) && count( $mentions ) !== 0 ) {
			foreach ( $mentions as $mention ) {
				$count = count( $mentions );

				for ( $i = 0; $i < $count; $i++ ) {
					$tweet = preg_replace(
						'|@'.$mentions[ $i ]['screen_name'].'|',
						'<a href="'.$twitter.$mentions[ $i ]['screen_name'].'" target="_blank" title="'.$mentions[ $i ]['name'].'">@'.$mentions[ $i ]['screen_name'].'</a>',
						$tweet
					);
				}

				return $tweet;
			}

		} else {
			return $tweet;

		}

	}

	public function linkify_links( $tweet, $urls ) {

		if ( is_array( $urls ) && count( $urls ) !== 0 ) {
			foreach ( $urls as $url ) {
				$count = count( $urls );

				for ( $i = 0; $i < $count; $i++ ) {
					$tweet = str_replace(
						$urls[ $i ]['short_url'],
						'<a href="'.$urls[ $i ]['short_url'].'" target="_blank">'.$urls[ $i ]['display_url'].'</a>',
						$tweet
					);
				}

				return $tweet;
			}

		} else {
			return $tweet;

		}

	}

	public function linkify_media( $tweet, $media ) {

		if ( is_array( $media ) && count( $media ) !== 0 ) {
			foreach ( $media as $item ) {
				$count = count( $media );

				for ( $i = 0; $i < $count; $i++ ) {
					$tweet = str_replace(
						$media[ $i ]['short_url'],
						'<a href="'.$media[ $i ]['short_url'].'" target="_blank">'.$media[ $i ]['display_url'].'</a>',
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