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
		if ( (int) $hours !== 0 ) {
			/* The cache for the feed instance is set using the
			   feed term as its ID. Here we grab the ID */
			switch ( $this->options['feed_type'] ) {
				case 'user_timeline':
					$id = $this->options['user'];
				break;

				case 'search':
					$id = $this->options['search_term'];
				break;

				default:
					$id = FALSE;
				break;
			}

			if ( $id ) {
				// Create the cache
				set_transient( $this->plugin_name . '_output_' . $id, $this->output, 3600*$hours );

				// Check that the cache creation was successful
				$cache_successful = get_transient( $this->plugin_name . '_output_' . $this->options['user'] );

				if ( $cache_successful ) {
					$this->is_cached = TRUE;
				}
			}
		}
	}

} // END class

} // END class_exists