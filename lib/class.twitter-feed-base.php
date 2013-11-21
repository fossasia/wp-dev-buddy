<?php

/**
* Handles processes that occur outside of rendering the feed
*
* This class is used to handle processes that
* occur outside of the feed rendering process
*
* @version 1.0.0
*/
if ( ! class_exists( 'DB_Twitter_Feed_Base' ) ) {

class DB_Twitter_Feed_Base extends DevBuddy_Feed_Plugin {

	/**
	* @var string The name of the plugin to be used within the code
	*/
	public $plugin_name = 'db_twitter_feed';

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
		'user'                      => 'EjiOsigwe', // String: Any valid Twitter username
		'count'                     => '10',        // String: Number of tweets to retrieve
		'exclude_replies'           => 'no',        // String: ("yes" or "no") Only display tweets that aren't replies
		'default_styling'           => 'no',        // String: ("yes" or "no") Load the bundled stylesheet
		'cache_hours'               => 0,           // Int:    Number of hours to cache the output
		'clear_cache'               => 'no',        // String: ("yes" or "no") Clear the cache for the set "user",
		'oauth_access_token'        => NULL,        // String: The OAuth Access Token
		'oauth_access_token_secret' => NULL,        // String: The OAuth Access Token Secret
		'consumer_key'              => NULL,        // String: The Consumer Key
		'consumer_secret'           => NULL         // String: The Consumer Secret
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
			'user'                      => NULL,
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
			'user'                      => $user,
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
	*/
	public function unmask_data( $input ) {
		if ( preg_match( '|^([0-9]+)-([x]+)([a-zA-Z0-9]{3})$|', $input['oauth_access_token'] ) === 1 ) {
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
} // END class

} // END class_exists

?>