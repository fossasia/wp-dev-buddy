<?php

/**
* Handles processes that occur outside of rendering the feed
*
* This class is used to handle processes that
* occur outside of the feed rendering process
*
* @version 1.2.0
*/
if ( ! class_exists( 'DB_Twitter_Feed_Base' ) ) {

class DB_Twitter_Feed_Base extends DevBuddy_Feed_Plugin {

	/**
	* @var string The name of the plugin to be used within the code
	*/
	public $plugin_name = 'db_twitter_feed';

	/**
	* @var string The short name of the plugin to be used within the code
	*/
	public $plugin_short_name = 'dbtf';

	/**
	* @var object Twitter API object
	*/
	public $twitter;

	/**
	* @var string Used for storing the main group of options in the WP database
	*/
	protected $options_group_main;

	/**
	* @var string Used for identifying the main options in the WP database
	*/
	protected $options_name_main;

	/**
	* @var string Page URI within the WordPress admin
	*/
	protected $page_uri_main;

	/**
	* @var array Holds the configuration options and their default and/or user defined values
	*/
	protected $defaults = array(
		'feed_type'                 => 'user_timeline',  // String: ("user_timeline" or "search") The type of feed to render
		'user'                      => 'EjiOsigwe',      // String: Any valid Twitter username
		'search_term'               => '#twitter',       // String: Any term to be search on Twitter
		'count'                     => '10',             // String: Number of tweets to retrieve
		'exclude_replies'           => 'no',             // String: ("yes" or "no") Only display tweets that aren't replies
		'default_styling'           => 'no',             // String: ("yes" or "no") Load the bundled stylesheet
		'cache_hours'               => 0,                // Int:    Number of hours to cache the output
		'clear_cache'               => 'no',             // String: ("yes" or "no") Clear the cache for the set "user",
		'oauth_access_token'        => NULL,             // String: The OAuth Access Token
		'oauth_access_token_secret' => NULL,             // String: The OAuth Access Token Secret
		'consumer_key'              => NULL,             // String: The Consumer Key
		'consumer_secret'           => NULL              // String: The Consumer Secret
	);


	/**
	* Initialise important aspects of the plugin
	*
	* Set properties used for administritive processes
	* and register the bundled stylesheet and shortcode
	* with WordPress.
	*
	* @access public
	* @return void
	* @since 1.0.0
	*/
	public function __construct() {
		$this->set_main_admin_vars();

		add_action( 'wp_enqueue_scripts', array( $this, 'register_default_styling' ) );
		add_shortcode( 'db_twitter_feed', array( $this, 'register_twitter_feed_sc' ) );

		// Register batch_clear_cache() for use via WP AJAX
		//add_action( 'wp_ajax_my_action', array( 'DB_Twitter_Feed_Static', 'batch_clear_cache' ) );

		if ( $this->get_db_plugin_option( $this->options_name_main, 'default_styling' ) === 'yes' )
			add_action( 'wp_enqueue_scripts', array( $this, 'load_default_styling' ) );
	}


	/**
	* Set properties used for administritive processes
	*
	* @access protected
	* @return void
	* @since 1.0.0
	*/
	protected function set_main_admin_vars() {
		$this->options_group_main = $this->plugin_name;
		$this->options_name_main  = $this->plugin_name.'_options';
		$this->page_uri_main      = 'db-twitter-feed-settings';
	}


	/**
	* Register the bundled stylesheet within WordPress ready for loading
	*
	* @access public
	* @return void
	* @since 1.0.0
	*/
	public function register_default_styling() {
		wp_register_style( $this->plugin_name.'-default', DBTF_URL.'/assets/feed.css', NULL, '2.1', 'all' );
	}


	/**
	* Set the bundled stylesheet to be loaded to the page by WordPress
	*
	* @access public
	* @return void
	* @since 1.0.0
	*/
	public function load_default_styling() {
		wp_enqueue_style( $this->plugin_name.'-default' );
	}


	/**
	* Register the shortcode that is used to render the feed
	*
	* This method is merely a port that moves the configuration data given
	* to the db_twitter_feed() template tag, which does all of the actual
	* work.
	*
	* @access public
	* @return The db_twitter_feed() template tag with the $given_atts array as the parameter
	* @since 1.0.0
	*
	* @param array $given_atts An associative array of feed configuration options
	*/
	public function register_twitter_feed_sc( $given_atts ) {
		$default_atts =
		array(
			'feed_type'                 => NULL,
			'user'                      => NULL,
			'search_term'               => NULL,
			'count'                     => NULL,
			'exclude_replies'           => NULL,
			'default_styling'           => NULL,
			'cache_hours'               => NULL,
			'clear_cache'               => NULL,
			'oauth_access_token'        => NULL,
			'oauth_access_token_secret' => NULL,
			'consumer_key'              => NULL,
			'consumer_secret'           => NULL
		);

		extract(
			shortcode_atts( $default_atts, $given_atts )
		);

		$feed_config =
		array(
			'feed_type'                 => $feed_type,
			'user'                      => $user,
			'search_term'               => $search_term,
			'count'                     => $count,
			'exclude_replies'           => $exclude_replies,
			'default_styling'           => $default_styling,
			'cache_hours'               => $cache_hours,
			'clear_cache'               => $clear_cache,
			'oauth_access_token'        => $oauth_access_token,
			'oauth_access_token_secret' => $oauth_access_token_secret,
			'consumer_key'              => $consumer_key,
			'consumer_secret'           => $consumer_secret,
			'is_shortcode_called'       => TRUE
		);

		return db_twitter_feed( $feed_config );
	}


	/**
	* Retrieve original version of masked data 
	*
	* Takes a name, searches the database for,
	* and returns the original, untampered data.
	*
	* @access public
	* @return array
	* @since 1.0.0
	*
	* @param array $input An associative array of the submitted data
	*/
	public function unmask_data( $input ) {
		// Check to see if any of the authentication data has been edited, grab the stored value if not
		if ( preg_match( '|^([0-9]+)([x]+)-([x]+)([a-zA-Z0-9]{3})$|', $input['oauth_access_token'] ) === 1 ) {
			$input['oauth_access_token'] = $this->get_db_plugin_option( $this->options_name_main, 'oauth_access_token' );
		}
		if ( preg_match( '|^([a-zA-Z0-9]{3})([x]+)([a-zA-Z0-9]{3})$|', $input['oauth_access_token_secret'] ) === 1 ) {
			$input['oauth_access_token_secret'] = $this->get_db_plugin_option( $this->options_name_main, 'oauth_access_token_secret' );
		}
		if ( preg_match( '|^([a-zA-Z0-9]{3})([x]+)([a-zA-Z0-9]{3})$|', $input['consumer_key'] ) === 1 ) {
			$input['consumer_key'] = $this->get_db_plugin_option( $this->options_name_main, 'consumer_key' );
		}
		if ( preg_match( '|^([a-zA-Z0-9]{3})([x]+)([a-zA-Z0-9]{3})$|', $input['consumer_secret'] ) === 1 ) {
			$input['consumer_secret'] = $this->get_db_plugin_option( $this->options_name_main, 'consumer_secret' );
		}


		return $input;
	}


	/**
	* Method used to parse through data submitted on the feed's settings page within WordPress
	*
	* This method will unmask authentication
	* data if necessary by searching for and
	* returning the value stored in the
	* database.
	*
	* This method will also check for values
	* marked as hidden, and move their data
	* over to their visible counterparts
	*
	* @access public
	* @return array
	* @since 1.1.0
	*
	* @param array $input An associative array of the submitted data
	*/
	public function sanitize_settings_submission( $input ) {
		// Deals with a cache clear request
		if ( isset($input['cache_clear_flag']) && (int) $input['cache_clear_flag'] === 1 ) {
			$bcc_feedback = $this->batch_clear_cache( $input['cache_segment'] );

			// Establish tone of error by reading what is returned by batch_clear_cache()
			switch ( $bcc_feedback ) {
				case 'no_' . $input['cache_segment'] . '_cache_data_to_clear':
				case 'no_cache_data_to_clear':
				case 'no_segment_chosen':
				case 'cache_not_cleared':
				case 'unknown_error':
					$tone = 'error';
					break;

				case ucfirst($input['cache_segment']) . '_cache_cleared':
				case 'cache_cleared':
					$tone = 'updated';
					break;
			}

			// Set notification for next page
			add_settings_error( 'cache_hours', 'cache_hours', ucfirst(str_replace('_', ' ', $bcc_feedback)), $tone );

			// Return the original options as they're not be changed
			$input = get_option( $this->options_name_main );
			return $input;
		}


		// Makes sure that no quotes have been used in any of the fields
		foreach ( $input as $field_name => $field ) {
			if ( strpos($field, '"') || strpos($field, "'") ) {
				// Variable used for consistency when referencing the field name
				$option_name = $field_name;

				// Get the field name without the "_hid" if it has it
				$field_is_hidden = FALSE;
				if ( strpos($field_name, '_hid') ) {
					$field_is_hidden = TRUE;
					$field_name_wh = str_replace('_hid', '', $field_name);
					$option_name = $field_name_wh;
				}

				// Replace value with quote in with old value from database
				$input[$field_name] = $this->get_option( $this->options_name_main, $option_name );

				// Check if we already set the error for this field (i.e. has duplicate hidden field)
				$set_error = TRUE;
				$error_msgs = get_settings_errors();

				foreach ( $error_msgs as $error_msg ) {
					if ( in_array('quotes_in_field_' . $option_name, $error_msg) ) {
						$set_error = FALSE;
					}
				}

				if ( $set_error ) {
					// Let the user know what's up
					add_settings_error( '', 'quotes_in_field_' . $option_name, 'Quotes are not allowed in the <u>' . ucwords(str_replace('_', ' ', $option_name)) . '</u> field. Your change for that field was not applied' );
				}

				// Cleared as used else where in this section of code
				unset($option_name);
			}
		}


		// We don't want the cache_segment option to be saved
		if ( isset( $input['cache_segment'] ) ) {
			unset( $input['cache_segment'] );
			unset( $input['cache_clear_flag'] );
		}

		/* Saving via the WP settings page overwrites all
		   options that are currently saved. Here, we grab
		   items that aren't available in the settings page
		   and ensure that they aren't overwritten. */
		$options      = get_option( $this->options_name_main );
		$option_saver = array();
		$option_saver_list = array(
			//'feed_term_cache'
		);

		foreach ( $option_saver_list as $option_name ) {
			/* However, we don't want to create an option
			   that doesn't already exist */
			if ( isset( $options[ $option_name ] ) ) {
				$input[ $option_name ] = $options[ $option_name ];
			}
		}


		/* Some settings have matching hidden and visible fields
		   The hidden ones are the ones we want and this little
		   bit of script ensures that that's what we get */
		foreach ( $input as $item => $value ) {
			if ( preg_match( '|_hid$|', $item ) === 1 ) {
				$feed_value_name = str_replace('_hid', '', $item);

				unset( $input[ $feed_value_name ] );
				$input[ $feed_value_name ] = $value;

				unset( $input[ $item ] );
			}
		}


		// Check to see if any of the authentication data has been edited, grab the stored value if not
		if ( preg_match( '|^([a-zA-Z0-9]{3})([x]+)([a-zA-Z0-9]{3})$|', $input['consumer_key'] ) === 1 ) {
			$input['consumer_key'] = $this->get_db_plugin_option( $this->options_name_main, 'consumer_key' );
		}
		if ( preg_match( '|^([a-zA-Z0-9]{3})([x]+)([a-zA-Z0-9]{3})$|', $input['consumer_secret'] ) === 1 ) {
			$input['consumer_secret'] = $this->get_db_plugin_option( $this->options_name_main, 'consumer_secret' );
		}
		if ( preg_match( '|^([0-9]+)([x]+)?-([x]+)([a-zA-Z0-9]{3})$|', $input['oauth_access_token'] ) === 1 ) {
			$input['oauth_access_token'] = $this->get_db_plugin_option( $this->options_name_main, 'oauth_access_token' );
		}
		if ( preg_match( '|^([a-zA-Z0-9]{3})([x]+)([a-zA-Z0-9]{3})$|', $input['oauth_access_token_secret'] ) === 1 ) {
			$input['oauth_access_token_secret'] = $this->get_db_plugin_option( $this->options_name_main, 'oauth_access_token_secret' );
		}


		// Finally, clear the cache of the current feed term
		switch ( $input['feed_type'] ) {
			case 'user_timeline':
				$cache_id = $input['twitter_username'];
			break;

			case 'search':
				$cache_id = $input['search_term'];
			break;

			default:
				$cache_id = FALSE;
			break;
		}

		if ( $cache_id !== FALSE ) {
			$this->clear_cache_output( $cache_id, $input['feed_type'] );
		}

		return $input;

	}


	/**
	* Cache whatever is in the DevBuddy_Feed_Plugin::$output property
	*
	* This method also sets the DevBuddy_Feed_Plugin::$is_cached property
	* to TRUE once the cache is set.
	*
	* @access public
	* @return void
	* @since 1.2.0
	*
	* @param int $hours The number of hours the output should be cached for
	*/
	public function cache_output( $hours = 0 ) {
		if ( ! isset( $this->options ) ) {
			error_log( __CLASS__ . '::$options must be set before an output can be cached' );
			return FALSE;
		}

		/* The cache for the feed instance is set using the
		   feed term as its ID. Here we grab the ID */
		$id = ( isset($this->feed_term) ) ? $this->feed_term : FALSE;

		if ( (int) $hours > 0 ) {
			if ( $id ) {
				// Encode the cache
				$the_output = json_encode($this->output);

				// Create the cache
				set_transient( $this->plugin_name . '_output_' . $id, $the_output, 3600 * $hours );

				// Check that the cache creation was successful
				$cache_successful = get_transient( $this->plugin_name . '_output_' . $id );

				if ( $cache_successful ) {
					$this->is_cached = TRUE;

					$this->update_feed_term_cache(
						array(
							'user'        => $this->options['user'],
							'feed_type'   => $this->options['feed_type'],
							'search_term' => $this->options['search_term'],
							'cache_hours' => $this->options['cache_hours']
						)
					);

					return TRUE;

				} else {
					error_log('Cache operation unsuccessful near line ' . __LINE__ . ' in ' . __FILE__);
					return FALSE;
				}

			} else {
				error_log('Given ID should not be FALSE near line ' . __LINE__ . ' in ' . __FILE__);
				return FALSE;
			}

		}
	}


	/**
	* Clear the cached output of a specific user or search
	*
	* This method also sets the DevBuddy_Feed_Plugin::$is_cached
	* property to FALSE once the cache is deleted and is called
	* when changes have been saved on the settings page in
	* WordPress.
	*
	* @access public
	* @return bool
	* @since 1.0.0
	*
	* @param string $id The cached ID of the feed
	* @param string $segment The segment of the feed term cache that the $id belongs to
	*/
	public function clear_cache_output( $id, $segment = NULL ) {
		delete_transient( $this->plugin_name . '_output_' . $id );

		$clear_cache_successful = ( get_transient( $this->plugin_name . '_output_' . $id ) ) ? FALSE : TRUE;

		if ( $clear_cache_successful ) {
			$this->is_cached = FALSE;

			/* If a segment is available, we specify it to add accuracy to
			   feed term cache update */
			if ( $segment === NULL ) {
				$this->clear_feed_term_cache_item( $id );
			} else {
				$this->clear_feed_term_cache_item( $id, $segment );
			}

			return TRUE;

		} else {
			error_log(__METHOD__ . ' was unsuccessful');
			return FALSE;
		}
	}


	/**
	 * Update the feed term cache with about a cache.
	 *
	 * Necessary data to pass to the array: user, feed_type, search_term, cache_hours
	 *
	 * @access protected
	 * @return bool A boolean indication of whether or not the feed term cache update was successful
	 * @since  1.2.0
	 * 
	 * @param  array $input An associative array of data necessary to populate the feed term cache
	 */
	protected function update_feed_term_cache( $input ) {
		// No array? No go.
		if ( ! is_array($input) ) {
			return FALSE;
		}

		$options = get_option( $this->options_name_main );
		$valid_feed_type = TRUE;

		// Grab the feed term cache or create it if it doesn't exist
		$ftc = get_transient( $this->plugin_name . '_ftc' );
		if ( ! $ftc ) {
			$ftc = array(
				'user_timeline' => array(),
				'search'        => array()
			);
		}

		/* Check that the user has requested to cache the feed and
		   create the cache data array item */
		if ( (int) $input['cache_hours'] !== 0 && ! $this->is_empty() && ! $this->has_errors() ) {
			$cache_hours   = (int) $input['cache_hours'] * 3600;
			$cache_expires = time() + $cache_hours;

			switch ( $input['feed_type'] ) {
				case 'user_timeline':
					$user = htmlspecialchars($input['user'], ENT_QUOTES);
					if ( $user !== FALSE ) {
						$ftc['user_timeline'][ $user ] = array(
							'cache_began'   => time(),
							'cache_lasts'   => $cache_hours,
							'cache_expires' => $cache_expires
						);
					}
				break;

				case 'search':
					$search_term = htmlspecialchars($input['search_term'], ENT_QUOTES);
					if ( $search_term !== FALSE ) {
						$ftc['search'][ $search_term ] = array(
							'cache_began'   => time(),
							'cache_lasts'   => $cache_hours,
							'cache_expires' => $cache_expires
						);
					}
				break;

				default:
					$valid_feed_type = FALSE;
				break;
			}

			if ( $valid_feed_type === TRUE ) {
				if ( set_transient( $this->plugin_name . '_ftc', $ftc, 3600 * 24 ) === FALSE ) {
					error_log('set_transient() for ' . $this->plugin_name . '_ftc was unsuccessful');
					return FALSE;

				} else {
					return TRUE;
				}

			} else {
				error_log('Invalid feed type on line ' . __LINE__);
				return FALSE;
			}
		}
	}


	/**
	 * Clear a specific option term the feed term cache
	 *
	 * @access protected
	 * @return bool A boolean indication of whether or not the operation was successful
	 * @since  1.2.0
	 * 
	 * @param  string $term   The identifier for the term you wish to clear
	 * @param  mixed $segment The identifier for the segment you wish to clear
	 */
	protected function clear_feed_term_cache_item( $term, $segment = NULL ) {
		$ftc = get_transient( $this->plugin_name . '_ftc' );

		// Check that a feed term cache is even available
		if ( $ftc === FALSE ) {
			error_log('A feed term cache has not recently been created, nothing to clear');
			return FALSE;
		}

		// Return false if given segment doesn't exist
		if ( $segment !== NULL && ! array_key_exists($segment, $ftc) ) {
			error_log('Given segment [' . $segment . '] does not exist in feed term cache:' . __LINE__);
			return FALSE;
		}

		// Return false if data for given identifier doesn't exist
		if ( $segment !== NULL && array_key_exists($segment, $ftc) && ! isset($ftc[$segment][$term]) ) {
			error_log('Given term [' . $term . '] does not exist in feed term cache:' . __LINE__);
			return FALSE;
		}

		// Clear the term from the feed term cache
		// If $segment is NULL, clear all instances of $term
		if ( $segment === NULL ) {
			foreach ( $ftc as $s => $t_data ) {
				foreach ( $t_data as $t => $data ) {
					if ( $t === $term ) {
						unset($ftc[$s][$term]);
					}
				}
			}

		} else {
			unset($ftc[$segment][$term]);

		}

		// Push the updated feed term cache
		$ftc_updated = set_transient( $this->plugin_name . '_ftc', $ftc, 3600 * 24 );
		if ( $ftc_updated === TRUE ) {
			return TRUE;
		} else {
			error_log(__METHOD__ . ', ftc transient update was unsuccessful');
			return FALSE;
		}

	}


	/**
	* Batch clear feed cache
	*
	* @access public
	* @return string A line of text describing the outcome of the operation
	* @since 1.2.0
	*
	* @param string $segment The segment of the cache you wish to clear
	*/
	public function batch_clear_cache( $segment = 'all' ) {
		if ( is_array($segment) && isset($segment['segment']) ) {
			$segment = $segment['segment'];
		}

		// Check that a cache segment has been passed
		if ( $segment === '0' ) {
			return 'no_segment_chosen';
		}

		// Check for existing feed term cache
		$ftc = get_transient( $this->plugin_name . '_ftc' );
		$no_cache_records = FALSE;

		// Check that the transient did exist
		if ( $ftc === FALSE ) {
			$no_cache_records = TRUE;
		}

		/* Though the transient may exist, it may be empty so we
		   check through each segment to see if it contains data */
		if ( $ftc !== FALSE ) {
			$active_segments = 0;

			foreach ( $ftc as $seg ) {
				if ( is_array($seg) && count($seg) > 0 ) {
					$active_segments++;
				}
			}

			if ( $active_segments === 0 ) {
				$no_cache_records = TRUE;
			}
		}

		if ( $no_cache_records === TRUE  ) {
			return 'no_cache_data_to_clear';
		}

		// Holds info on each term cache clear. Useful for debugging
		$feedback = array();

		switch ( $segment ) {
			case 'user_timeline':
				$clearance_list = $ftc['user_timeline'];
			break;

			case 'search':
				$clearance_list = $ftc['search'];
			break;

			case 'all':
				$clearance_list = NULL;
			break;

			default:
				$clearance_list = FALSE;
			break;
		}

		$cache_cleared = FALSE;

		if ( $clearance_list !== FALSE && is_array($clearance_list) ) {

			// Check that there's data to clear and clear if so
			if ( is_array($clearance_list) && count($clearance_list) > 0 ) {
				foreach ( $clearance_list as $term => $term_data ) {
					$feedback[ $term ] = $this->clear_cache_output( $term, $segment );
					$cache_cleared     = TRUE;
				}

			} else {
				$cache_cleared = FALSE;
			}

			if ( $cache_cleared ) {
				return ucfirst($segment) . '_cache_cleared';
			} else {
				return 'no_' . $segment . '_cache_data_to_clear';
			}

		} elseif ( $clearance_list === NULL ) {
			foreach ( $ftc as $s => $t_data ) {
				foreach ( $t_data as $t => $data ) {
					$feedback[ $t ] = $this->clear_cache_output( $t, $s );
					$cache_cleared = TRUE;
				}
			}

			if ( $cache_cleared ) {
				return 'cache_cleared';
			} else {
				return 'cache_not_cleared';
			}

		} else {
			return 'unknown_error';
		}
	}


} // END class

} // END class_exists