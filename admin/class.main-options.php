<?php

/**
* A class to create the settings page for this plugin within WordPress
*
* @version 2.0.0
*/
if ( ! class_exists( 'DB_Twitter_Feed_Main_Options' ) ) {

class DB_Twitter_Feed_Main_Options extends DB_Twitter_Feed_Base {

	/**
	* @var array Holds important information about sections on the settings page
	*/
	private $sections = array();

	/**
	* @var array Holds important information about individual settings on the settings page
	*/
	private $settings = array();

	/**
	* @var string The prefix used to ensure that the IDs of HTML items are unique to the plugin
	* @since 2.0.0
	*/
	private $html_item_id_prefix;


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
	}


	/**
	* Establish the details of the sections to be used on this settings page
	*
	* @access private
	* @return void
	* @since 1.0.0
	*/
	private function set_sections() {
		$this->sections =
		array(
			'config' => array(
				'id'       => 'configuration_sec',
				'title'    => 'Configuration',
				'callback' => array( $this, 'write_configuration_sec' ),
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
	* Establish the details of the settings to be used on this settings page
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
			'user' => array(
				'id'       => 'twitter_username',
				'title'    => 'Twitter Username',
				'callback' => array( $this, 'write_twitter_username_field' ),
				'page'     => $this->page_uri_main,
				'section'  => 'settings_sec',
				'args'     => ''
			),
			'result_count' => array(
				'id'       => 'result_count',
				'title'    => 'Number of tweets to show',
				'callback' => array( $this, 'write_numeric_dropdown_field' ),
				'page'     => $this->page_uri_main,
				'section'  => 'settings_sec',
				'args'     => array(
					'min'       => 1,
					'max'       => 30 )
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
					'desc'   => '<p class="description">Select 0 if you don\'t wish to cache the feed</p>' )
			),
			'exclude_replies' => array(
				'id'       => 'exclude_replies',
				'title'    => 'Exclude replies?',
				'callback' => array( $this, 'write_checkbox_field' ),
				'page'     => $this->page_uri_main,
				'section'  => 'settings_sec',
				'args'     => array(
					'desc'   => '<p class="description">Twitter removes replies only after it retrieves the number of tweets you request.<br />Thus if you choose 10, and out of that 10 6 are replies, only 4 tweets will be displayed.</p>' )
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
				'section'  => ''
				'args'     => ''
			)*/
		);

		// 
		foreach ( $this->settings as $name => $setting ) {
			$id           = $setting['id'];
			$title        = $setting['title'];
			$html_item_id = $this->html_item_id_prefix.$id;

			// Wrap title in label, add it to appropriate $settings property
			$this->settings[ $name ]['title'] = '<label for="'.$html_item_id.'">'.$title.'</label>';

			// 
			if ( is_array( $setting['args'] ) ) {
				$this->settings[ $name ]['args']['option'] = $id;
			} else {
				$this->settings[ $name ]['args'] = array( 'option' => $id );
			}

			$this->settings[ $name ]['args']['html_item_id'] = $html_item_id;
		}

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
		register_setting( $this->options_group_main, $this->options_name_main, array( $this, 'unmask_data' ) );

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

		<div class="wrap">

			<?php screen_icon() ?>
			<h2>Twitter Feed Settings</h2>

			<form id="<?php echo $this->plugin_name ?>_settings" action="options.php" method="post">
				<?php
				if ( isset( $_GET['settings-updated'] ) && (bool) $_GET['settings-updated'] === TRUE ) {
					$user = $this->get_db_plugin_option( $this->options_name_main, 'twitter_username' );
					$this->clear_cache_output( $user );
				}

				settings_fields( $this->options_group_main );
				do_settings_sections( $this->page_uri_main );
				submit_button( 'Save Changes' ); ?>
			</form>

		</div><!--END-wrap-->
	<?php }


	/**
	* Check that the setting as an HTML id, and include it if it does
	*
	* @access private
	* @return string
	* @since 2.0.0
	*/
	private function _html_item_id_attr( $item_id ) {
		return 'id="'.$this->html_item_id_prefix.$item_id.'"';
	}


	/**
	* Output a basic checkbox field
	*
	* @access public
	* @return void
	* @since 1.0.0
	*
	* @param array $args[option] The name of the option as stored in the database
	* @param array $args[desc]   The description to accompany this field in the admin
	*/
	public function write_checkbox_field( $args ) {
		$stored_value = $this->get_db_plugin_option( $this->options_name_main, $args['option'] );

		echo '<input type="checkbox" '.$this->_html_item_id_attr( $args['option'] ).' name="'.$this->options_name_main.'['.$args['option'].']" value="yes"';
		if( $stored_value && $stored_value === 'yes') {
			echo ' checked="checked"';
		}
		echo ' />';

		if ( isset( $args['desc'] ) ) {
			echo $args['desc'];
		}
	}


	/**
	* Output basic dropdown field that supports numbered dropdowns only
	*
	* @access public
	* @return void
	* @since 1.0.0
	*
	* @param array $args[option] The name of the option as stored in the database
	* @param array $args[min]    The lowest number that the dropdown should reach
	* @param array $args[max]    The highest number that the dropdown should reach
	* @param array $args[desc]   The description to accompany this field in the admin
	*/
	public function write_numeric_dropdown_field( $args ) {
		$stored_value = $this->get_db_plugin_option( $this->options_name_main, $args['option'] );

		echo '<select '.$this->_html_item_id_attr( $args['option'] ).' name="'.$this->options_name_main.'['.$args['option'].']"';

		if ( isset( $args['html_item_id'] ) ) {
			echo ' id="'.$args['html_item_id'].'"';
		}

		echo '>';

		for ( $num = $args['min']; $num <= $args['max']; $num++ ) {
			echo '<option value="'.$num.'"';

			if ( $stored_value && (int) $stored_value === $num ) {
				echo ' selected="selected"';
			}

			echo '>'.$num.'</option>';
		}
		echo '</select>';

		if ( isset( $args['desc'] ) ) {
			echo $args['desc'];
		}
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
		echo 'You\'ll need to log into the Twitter Developers site and set up an app. Once you\'ve set one up you will get the data necessary for below. For a step by step, see the <a href="http://wordpress.org/plugins/devbuddy-facebook-feed/installation/" target="_blank">walkthrough</a>.';
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

		echo '<input type="text" '.$this->_html_item_id_attr( $args['option'] ).' name="'.$this->options_name_main.'[consumer_key]" value="'.$consumer_key.'" style="width:450px;" />';
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

		echo '<input type="text" '.$this->_html_item_id_attr( $args['option'] ).' name="'.$this->options_name_main.'[consumer_secret]" value="'.$consumer_secret.'" style="width:450px;" />';
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

		echo '<input type="text" '.$this->_html_item_id_attr( $args['option'] ).' name="'.$this->options_name_main.'[oauth_access_token]" value="'.$oauth_access_token.'" style="width:450px;" />';
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

		echo '<input type="text" '.$this->_html_item_id_attr( $args['option'] ).' name="'.$this->options_name_main.'[oauth_access_token_secret]" value="'.$oauth_access_token_secret.'" style="width:450px;" />';
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
	function write_settings_sec() {
		echo '';
	}


	/**
	* Output the Twitter username setting's field
	*
	* @access public
	* @return void
	* @since 1.0.0
	*/
	function write_twitter_username_field( $args ) {
		$twitter_username = $this->get_db_plugin_option( $this->options_name_main, 'twitter_username' );

		echo '<strong>twitter.com/<input type="text" '.$this->_html_item_id_attr( $args['option'] ).' name="'.$this->options_name_main.'[twitter_username]"';

		if ( $twitter_username ) {
			echo ' value="'.$twitter_username.'"';
		}

		echo ' />';
	}
}// END class

}// END class_exists