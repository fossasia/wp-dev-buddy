<?php

/**
* A class to create the settings page for this plugin within WordPress
*
* @version 2.1.0
*/
if ( ! class_exists( 'DB_Twitter_Feed_Main_Options' ) ) {

class DB_Twitter_Feed_Main_Options extends DB_Plugin_WP_Admin_Helper {

	/**
	* @var array Holds important information about sections on the settings page
	* @since 1.0.0
	*/
	private $sections = array();

	/**
	* @var array Holds important information about individual settings on the settings page
	* @since 1.0.0
	*/
	private $settings = array();

	/**
	* @var string The prefix used to ensure that the IDs of HTML items are unique to the plugin
	* @since 2.0.0
	*/
	protected $html_item_id_prefix;


	/**
	* Sets up the settings and initialises them within WordPress
	*
	* @access public
	* @return void
	* @since 1.0.0
	*/
	public function __construct() {
		$this->set_main_admin_vars();

		$this->html_item_id_prefix = $this->plugin_short_name.'_';

		$this->set_sections();
		$this->set_settings();

		add_action( 'admin_menu', array( $this, 'add_menu_item' ) );
		add_action( 'admin_init', array( $this, 'init_options' ) );
		add_action( 'admin_head', array( $this, 'set_admin_vars_js' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts_styles' ) );
	}


	/**
	* Establish the details of the sections to be rendered by WP on this settings page
	*
	* @access private
	* @return void
	* @since 1.0.0
	*/
	private function set_sections() {
		$this->sections =
		array(
			'cache' => array(
				'id'       => 'cache_sec',
				'title'    => 'Cache Management',
				'callback' => array( $this, 'write_cache_sec' ),
				'page'     => $this->page_uri_main
			),
			'config' => array(
				'id'       => 'configuration_sec',
				'title'    => 'Configuration',
				'callback' => array( $this, 'write_configuration_sec' ),
				'page'     => $this->page_uri_main
			),
			'feed' => array(
				'id'       => 'feed_sec',
				'title'    => 'Feed Settings',
				'callback' => array( $this, 'write_feed_sec' ),
				'page'     => $this->page_uri_main
			),
			'settings' => array(
				'id'       => 'settings_sec',
				'title'    => 'General Settings',
				'callback' => array( $this, 'write_settings_sec' ),
				'page'     => $this->page_uri_main
			)
		);
	}


	/**
	* Establish the details of the settings to be rendered by WP on this settings page
	*
	* @access private
	* @return void
	* @since 1.0.0
	*/
	private function set_settings() {
		$this->settings =
		array(
			'consumer_key' => array(
				'id'       => 'consumer_key',
				'title'    => 'Consumer Key',
				'callback' => array( $this, 'write_consumer_key_field' ),
				'page'     => $this->page_uri_main,
				'section'  => 'configuration_sec',
				'args'     => ''
			),
			'consumer_secret' => array(
				'id'       => 'consumer_secret',
				'title'    => 'Consumer Secret',
				'callback' => array( $this, 'write_consumer_secret_field' ),
				'page'     => $this->page_uri_main,
				'section'  => 'configuration_sec',
				'args'     => ''
			),
			'oauth_access_token' => array(
				'id'       => 'oauth_access_token',
				'title'    => 'OAuth Access Token',
				'callback' => array( $this, 'write_oauth_access_token_field' ),
				'page'     => $this->page_uri_main,
				'section'  => 'configuration_sec',
				'args'     => ''
			),
			'oauth_access_token_secret' => array(
				'id'       => 'oauth_access_token_secret',
				'title'    => 'OAuth Access Token Secret',
				'callback' => array( $this, 'write_oauth_access_token_secret_field' ),
				'page'     => $this->page_uri_main,
				'section'  => 'configuration_sec',
				'args'     => ''
			),
			'feed_type' => array(
				'id'       => 'feed_type',
				'title'    => 'Feed Type',
				'callback' => array( $this, 'write_radio_fields' ),
				'page'     => $this->page_uri_main,
				'section'  => 'feed_sec',
				'args'     => array(
					'no_label' => TRUE,
					'options'  => array(
						'Timeline' => 'user_timeline',
						'Search'   => 'search'
					)
				)
			),
			'user' => array(
				'id'       => 'twitter_username',
				'title'    => 'Twitter Username',
				'callback' => array( $this, 'write_twitter_username_field' ),
				'page'     => $this->page_uri_main,
				'section'  => 'feed_sec',
				'args'     => array(
					'attr' => array(
						'class' => 'input_feed_type'
					)
				)
			),
			'search_term' => array(
				'id'       => 'search_term',
				'title'    => 'Search Term',
				'callback' => array( $this, 'write_search_term_field' ),
				'page'     => $this->page_uri_main,
				'section'  => 'feed_sec',
				'args'     => array(
					'desc'   => 'Searches with or without a hashtag are acceptable.',
					'attr'   => array(
						'class' => 'input_feed_type'
					)
				)
			),
			'result_count' => array(
				'id'       => 'result_count',
				'title'    => 'Number of tweets to show',
				'callback' => array( $this, 'write_numeric_dropdown_field' ),
				'page'     => $this->page_uri_main,
				'section'  => 'settings_sec',
				'args'     => array(
					'min'    => 1,
					'max'    => 30
				)
			),
			'cache_hours' => array(
				'id'       => 'cache_hours',
				'title'    => 'Cache the feed for how many hours?',
				'callback' => array( $this, 'write_numeric_dropdown_field' ),
				'page'     => $this->page_uri_main,
				'section'  => 'settings_sec',
				'args'     => array(
					'min'    => 0,
					'max'    => 24,
					'desc'   => '<p class="description">Select 0 if you don&rsquo;t wish to cache the feed.</p>' )
			),
			'exclude_replies' => array(
				'id'       => 'exclude_replies',
				'title'    => 'Exclude replies?',
				'callback' => array( $this, 'write_checkbox_field' ),
				'page'     => $this->page_uri_main,
				'section'  => 'settings_sec',
				'args'     => array(
					'desc'   => '<p class="description">Twitter removes replies only after it retrieves the number of tweets you request.<br />Thus if you choose 10, and out of that 10 6 are replies, only 4 tweets will be displayed.</p>'
				)
			),
			'show_images' => array(
				'id'       => 'show_images',
				'title'    => 'Show embedded images?',
				'callback' => array( $this, 'write_checkbox_field' ),
				'page'     => $this->page_uri_main,
				'section'  => 'settings_sec',
				'args'     => ''
			),
			'https' => array(
				'id'       => 'https',
				'title'    => 'Load media over HTTPS?',
				'callback' => array( $this, 'write_checkbox_field' ),
				'page'     => $this->page_uri_main,
				'section'  => 'settings_sec',
				'args'     => array(
					'desc'   => '<p class="description">This only affects media served by Twitter.</p>'
				)
			),
			'default_styling' => array(
				'id'       => 'default_styling',
				'title'    => 'Load default stylesheet?',
				'callback' => array( $this, 'write_checkbox_field' ),
				'page'     => $this->page_uri_main,
				'section'  => 'settings_sec',
				'args'     => ''
			)/*,
			'' => array(
				'id'       => '',
				'title'    => '',
				'callback' => array( $this, 'write__field' ),
				'page'     => $this->page_uri_main,
				'section'  => '',
				'args'     => ''
			)*/
		);

		foreach ( $this->settings as $name => $setting ) {
			$id           = $setting['id'];
			$title        = $setting['title'];
			$html_item_id = $this->html_item_id_prefix.$id;

			// Wrap title in label, add it to appropriate $settings property
			$no_label = ( isset( $setting['args']['no_label'] ) ) ? $setting['args']['no_label'] : FALSE;
			if ( $no_label !== TRUE ) {
				$this->settings[ $name ]['title'] = '<label for="'.$html_item_id.'">'.$title.'</label>';
			}


			// Add standard data to the arguments of the setting
			if ( is_array( $setting['args'] ) ) {
				$this->settings[ $name ]['args']['option'] = $id;
			} else {
				$this->settings[ $name ]['args'] = array( 'option' => $id );
			}

			$this->settings[ $name ]['args']['html_item_id'] = $html_item_id;
		}

	}


	/**
	* Load JavaScripts and styles necessary for the page
	*
	* @access public
	* @return void
	* @since 2.0.0
	*/
	public function enqueue_scripts_styles( $hook ) {
		if ( $hook != 'settings_page_db-twitter-feed-settings' ) {
			return;
		}
		wp_enqueue_style( $this->plugin_name.'_admin_styles', DBTF_URL.'/assets/main-admin.css', NULL, '1.0.1', 'all' );
		wp_enqueue_script( $this->plugin_name.'_admin_functions', DBTF_URL.'/assets/main-admin.js', array( 'jquery-core' ), '1.0.0', true );
	}


	/**
	* Create global JavaScript object that will hold certain plugin information
	*
	* @access public
	* @return void
	* @since 2.0.0
	*/
	public function set_admin_vars_js() {
		$br      = "\n";
		$tab     = '	';

		$class_name = strtoupper($this->plugin_short_name);

		$output  = $br.'<script type="text/javascript">'.$br;
		$output .= $tab.'var '.$class_name.' = '.$class_name.' || {};'.$br;

		$output .= $tab.$class_name.'.pluginName      = \''.$this->plugin_name.'\';'.$br;
		$output .= $tab.$class_name.'.pluginShortName = \''.$this->plugin_short_name.'\';'.$br;

		$output .= $tab.$class_name.'.optionsNameMain  = \''.$this->options_name_main.'\';'.$br;
		$output .= $tab.$class_name.'.optionsGroup     = \''.$this->options_group_main.'\';'.$br;

		$output .= '</script>'.$br.$br;

		echo $output;
	}


	/**
	* Add the item to the WordPress admin menu and call the function that renders the markup
	*
	* @access public
	* @return void
	* @since 1.0.0
	*/
	public function add_menu_item() {
		add_submenu_page(
			'options-general.php',
			'Configure your Twitter feed set up',
			'Twitter Feed Settings',
			'manage_options',
			$this->page_uri_main,
			array( $this, 'settings_page_markup' )
		);
	}


	/**
	* Officially register the sections/settings with WordPress
	*
	* @access public
	* @return void
	* @since 1.0.0
	*/
	public function init_options() {
		register_setting( $this->options_group_main, $this->options_name_main, array( $this, 'sanitize_settings_submission' ) );

		// Loop through the Sections/Settings arrays and add them to WordPress
		foreach ( $this->sections as $section ) {
			add_settings_section(
				$section['id'],
				$section['title'],
				$section['callback'],
				$section['page']
			);
		}
		foreach ( $this->settings as $setting ) {
			add_settings_field(
				$setting['id'],
				$setting['title'],
				$setting['callback'],
				$setting['page'],
				$setting['section'],
				$setting['args']
			);
		}
	}


	/**************************************************************************************************************
	 Callbacks for writing the option fields themselves to the options page
	 **************************************************************************************************************/
	/**
	* Write the markup for the settings page
	*
	* This method also checks to see if settings have been updated. If they have
	* the method will clear the cache of the ID currently in the twitter_username
	* field.
	*
	* @access public
	* @return void
	* @since 1.0.0
	*/
	public function settings_page_markup() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
		} ?>

		<div id="<?php echo $this->plugin_short_name ?>" class="wrap">

			<?php screen_icon() ?>
			<h2>Twitter Feed Settings</h2>

			<form id="<?php echo $this->plugin_name ?>_settings" action="options.php" method="post">
				<?php
				settings_fields( $this->options_group_main );
				do_settings_sections( $this->page_uri_main );

				submit_button( 'Save Changes' )
				?>
			</form>

		</div><!--END-<?php echo $this->plugin_short_name ?>-->
	<?php }


	/**
	* Takes a string and returns it with the plugin's shortname prefixed to it
	*
	* This method should only be used to prefix HTML
	* id attributes
	*
	* @access protected
	* @return string
	* @since 2.0.0
	*
	* @param string $item_id The ID of the item to be prefixed
	*/
	protected function _html_item_id_attr( $item_id ) {
		return $this->html_item_id_prefix.$item_id;
	}


	/* Write Cache Management section
	*******************************************/
	/**
	* Output the Cache management section and its fields
	*
	* @access public
	* @return void
	* @since 2.0.0
	*/
	public function write_cache_sec() {
		echo 'Select a cache segment to clear.';
		echo '<div class="' . $this->plugin_short_name . '_cache_management_section settings_item">';

		echo '<select name="' . $this->options_name_main . '[cache_segment]" id="' . $this->plugin_short_name . '_cache_segment">
				<option value="0">--</option>
				<option value="user_timeline">User timelines</option>
				<option value="search">Searches</option>
				<option value="all">All</option>
			</select>';

		echo '<input type="hidden" id="' . $this->plugin_short_name . '_cache_clear_flag" name="' . $this->options_name_main . '[cache_clear_flag]" value="0" />';

		echo get_submit_button( 'Clear Cache', 'secondary', $this->plugin_short_name . '_batch_clear_cache' );
		echo '</div>';
	}


	/**
	* Output batch clear cache field
	*
	* @access public
	* @return void
	* @since 2.0.0
	*/
	public function write_cache_segment_field() {
		echo '';
	}


	/**
	* Output batch clear clear flag field
	*
	* @access public
	* @return void
	* @since 2.0.0
	*/
	public function write_cache_clear_flag() {
		echo '';
	}


	/* Write Configuration section
	*******************************************/
	/**
	* Output the section as set in the set_sections() method along with a little bit of guidance
	*
	* @access public
	* @return void
	* @since 1.0.0
	*/
	public function write_configuration_sec() {
		echo 'You\'ll need to log into the Twitter Developers site and set up an app. Once you\'ve set one up you will get the data necessary for below. For a step by step, see the <a href="http://wordpress.org/plugins/devbuddy-twitter-feed/installation/" target="_blank">walkthrough</a>.';
	}


	/**
	* Output the Consumer Key setting's field
	*
	* @access public
	* @return void
	* @since 1.0.0
	*/
	public function write_consumer_key_field( $args ) {
		$consumer_key = $this->get_db_plugin_option( $this->options_name_main, 'consumer_key' );
		$consumer_key = $this->mask_data( $consumer_key );

		echo '<input type="text" id="'.$this->_html_item_id_attr( $args['option'] ).'" name="'.$this->options_name_main.'[consumer_key]" value="'.$consumer_key.'" style="width:450px;" />';
	}


	/**
	* Output the Consumer Secret setting's field
	*
	* @access public
	* @return void
	* @since 1.0.0
	*/
	public function write_consumer_secret_field( $args ) {
		$consumer_secret = $this->get_db_plugin_option( $this->options_name_main, 'consumer_secret' );
		$consumer_secret = $this->mask_data( $consumer_secret );

		echo '<input type="text" id="'.$this->_html_item_id_attr( $args['option'] ).'" name="'.$this->options_name_main.'[consumer_secret]" value="'.$consumer_secret.'" style="width:450px;" />';
	}


	/**
	* Output the OAuth Access Token setting's field
	*
	* @access public
	* @return void
	* @since 1.0.0
	*/
	public function write_oauth_access_token_field( $args ) {
		$oauth_access_token = $this->get_db_plugin_option( $this->options_name_main, 'oauth_access_token' );

		$oat_arr = explode( '-', $oauth_access_token );
		$start = strlen( $oat_arr[0] );

		$oauth_access_token = $this->mask_data( $oauth_access_token, $start );

		echo '<input type="text" id="'.$this->_html_item_id_attr( $args['option'] ).'" name="'.$this->options_name_main.'[oauth_access_token]" value="'.$oauth_access_token.'" style="width:450px;" />';
	}


	/**
	* Output the OAuth Access Token Secret setting's field
	*
	* @access public
	* @return void
	* @since 1.0.0
	*/
	public function write_oauth_access_token_secret_field( $args ) {
		$oauth_access_token_secret = $this->get_db_plugin_option( $this->options_name_main, 'oauth_access_token_secret' );
		$oauth_access_token_secret = $this->mask_data( $oauth_access_token_secret );

		echo '<input type="text" id="'.$this->_html_item_id_attr( $args['option'] ).'" name="'.$this->options_name_main.'[oauth_access_token_secret]" value="'.$oauth_access_token_secret.'" style="width:450px;" />';
	}


	/* Write Feed Settings section
	*******************************************/
	/**
	* Output the section as set in the set_sections() method
	*
	* @access public
	* @return void
	* @since 1.0.0
	*/
	public function write_feed_sec() {
		echo '';
	}


	/**
	* Output the Twitter username setting's field
	*
	* @access public
	* @return void
	* @since 1.0.0
	*/
	public function write_twitter_username_field( $args ) {
		$twitter_username = $this->get_db_plugin_option( $this->options_name_main, 'twitter_username' );

		echo '<strong>twitter.com/<input type="text" id="'.$this->_html_item_id_attr( $args['option'] ).'" name="'.$this->options_name_main.'[twitter_username]"';

		if ( $twitter_username ) {
			echo ' value="'.$twitter_username.'"';
		}

		echo ( isset( $args['attr'] ) ) ? $this->write_attr( $args['attr'] ) : '';

		echo ' /></strong>';

		echo '<input type="hidden" name="'.$this->options_name_main.'[twitter_username_hid]"';

		if ( $twitter_username ) {
			echo ' value="'.$twitter_username.'"';
		}

		echo ' />';

		echo ( isset( $args['desc'] ) ) ? $this->write_desc( $args['desc'] ) : '';
	}


	/**
	* Output the Twitter username setting's field
	*
	* @access public
	* @return void
	* @since 1.0.0
	*/
	public function write_search_term_field( $args ) {
		$search_term = $this->get_db_plugin_option( $this->options_name_main, 'search_term' );

		echo '<input type="text" id="'.$this->_html_item_id_attr( $args['option'] ).'" name="'.$this->options_name_main.'[search_term]"';

		if ( $search_term ) {
			echo ' value="'.$search_term.'"';
		}

		echo ( isset( $args['attr'] ) ) ? $this->write_attr( $args['attr'] ) : '';

		echo ' />';

		echo '<input type="hidden" name="'.$this->options_name_main.'[search_term_hid]"';

		if ( $search_term ) {
			echo ' value="'.$search_term.'"';
		}

		echo ' />';

		echo ( isset( $args['desc'] ) ) ? $this->write_desc( $args['desc'] ) : '';
	}


	/* Write General Settings section
	*******************************************/
	/**
	* Output the section as set in the set_sections() method
	*
	* @access public
	* @return void
	* @since 1.0.0
	*/
	public function write_settings_sec() {
		echo '';
	}

}// END class

}// END class_exists