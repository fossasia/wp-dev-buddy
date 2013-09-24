<?php

/**
* A class that will be common across feed plugins
*
* This class is used as a provider of properties
* and methods that will be common across feed
* plugins.
*
* @version 1.0.0
*/
class DevBuddy_Feed_Plugin_Twitter {

	/**
	* @var string The name of the plugin to be used within the code
	*/
	public $plugin_name;

	/**
	* @var mixed Holds raw feed data returned from API after main request is made
	*/
	public $feed_data;

	/**
	* @var string The output of the entire feed will be stored here
	*/
	public $output = '';

	/**
	* @var bool A boolean indication of whether or not a cached version of the output is available
	*/
	public $is_cached;

	/**
	* @var bool A boolean indication of whether or not the feed has been called via shortcode
	*/
	public $is_shortcode_called = FALSE;

	/**
	* @var string The width of the display picture when set by the user
	*/
	protected $dp_width;

	/**
	* @var string The height of the display picture when set by the user
	*/
	protected $dp_height;


	/**
	* Used to get the value of an option stored in the database
	* 
	* Option data is actually stored within an array of values
	* under one option entry within the WordPress database. So
	* to get an option's value you need to provide the option
	* entry along with the specific option you want the value
	* of.
	*
	* @access public
	* @return mixed The value of the option you're looking for or FALSE if no value exists
	*
	* @param string $option_entry The option name that WP recognises as an entry
	* @param string $option_name  The name of the specific option you want the value of
	*
	* @since 1.0.0
	*/
	public function get_db_plugin_option( $option_entry, $option_name ) {
		$options = get_option( $option_entry );

		if ( isset( $options[ $option_name ] ) && $options[ $option_name ] != '' ) {
			return $options[ $option_name ];
		} else {
			return FALSE;
		}
	}


	/**
	* Check that an option exists and validate it
	*
	* Will likely be removed. Validation should really be
	* specific to each feed's needs.
	*
	* @access public
	* @return mixed
	*
	* @param mixed  $given_value
	* @param string $option_name
	* @param mixed  $default
	*
	* @since 1.0.0
	*/
	public function validate_option( $given_value, $option_name, $default = FALSE ) {
		if ( $option_name === 'user' && $given_value != '' ) {
			return $given_value;

		} else {
			if ( $option_name !== 'user' && ( $given_value === 'yes' || $given_value === 'no' || is_numeric( $given_value ) || (int) $given_value > 0 ) ) {
				return $given_value;

			} else {
				$stored_value = $this->get_db_plugin_option( $this->options_name_main, $option_name );
				if ( $stored_value && $stored_value != '' ) {
					return $stored_value;

				} else {
					return $default;
				}
			}
		}
	}


	/**
	* Cache whatever is in the DevBuddy_Feed_Plugin::$output property
	*
	* This method also sets the DevBuddy_Feed_Plugin::$is_cached property
	* to TRUE once the cache is set.
	*
	* @access public
	* @return void
	* @since 1.0.0
	*
	* @param int $hours The number of hours the output should be cached for
	*/
	public function cache_output( $hours = 0 ) {
		if ( (int) $hours !== 0 ) {
			set_transient( $this->plugin_name.'_output_'.$this->options['user'], $this->output, HOUR_IN_SECONDS*$hours );
			$this->is_cached = TRUE;
		}
	}


	/**
	* Clear the cached output of a specific user
	*
	* This method also sets the DevBuddy_Feed_Plugin::$is_cached
	* property to FALSE once the cache is deleted and is called
	* when changes have been saved on the settings page in
	* WordPress.
	*
	* @access public
	* @return void
	* @since 1.0.0
	*
	* @param string $user The username/ID of the feed owner
	*/
	public function clear_cache_output( $user ) {
		delete_transient( $this->plugin_name.'_output_'.$user );
		$this->is_cached = FALSE;
	}


	/**
	* Format the date based on what the time that the data given represents
	*
	* An option for relative datetimes has been included,
	* which will be useful in cases where the output is
	* to be cached and the relative times would thus be
	* inaccurate.
	*
	* @access protected
	* @return string Some human readable representation of the date the post was published
	* @since 1.0.0
	*
	* @param mixed $datetime          The datetime that the post was published in any format that PHP's strtotime() can parse
	* @param bool  $relative_datetime Whether or not to return relative datetimes, e.g. "2 hours ago"
	*/
	protected function formatify_date( $datetime, $relative_datetime = TRUE ) {
		$an_hour = 3600;
		$a_day  = $an_hour*24;
		$a_week = $a_day*7;

		$now = time();
		$then = strtotime( $datetime );
		$diff = $now - $then;

		$mins = $diff / 60 % 60;
		$the_mins_ago = $mins;
		$the_mins_ago .= ( $mins == '1' ) ? ' minute ago' : ' minutes ago';

		$hours = $diff / 3600 % 24;
		$the_hours_ago = 'About ';
		$the_hours_ago .= $hours;
		$the_hours_ago .= ( $hours == '1' ) ? ' hour ago' : ' hours ago';

		$the_time = date( 'H:i', $then );
		$the_day = date( 'D', $then );
		$the_date = date( 'j M', $then );


		if ( $relative_datetime && $diff <= $an_hour ) {
			return $the_mins_ago;

		} elseif ( $diff <= $an_hour ) {
			return $the_time.', '.$the_day;

		} elseif ( $relative_datetime && $diff > $an_hour && $diff <= $a_day ) {
			return $the_hours_ago;

		} elseif ( $diff > $an_hour && $diff <= $a_day ) {
			return $the_time.', '.$the_day;

		} elseif ( $diff > $a_day && $diff <= $a_week ) {
			return $the_time.', '.$the_day;

		} else {
			return $the_date;
		}
	}


	/**
	* Turn plain text links within text into hyperlinks and return the full text
	*
	* @access public
	* @return string The original text with plain text links converted into hyperlinks
	* @since 1.0.0
	*
	* @param string $text The text to parse for plain text links
	*/
	public function hyperlinkify_text( $text ) {
		$new_text = preg_replace('@(https?://([-\w\.]+[-\w])+(:\d+)?(/([\w/_\.#-]*(\?\S+)?[^\.\s])?)?)@', '<a href="$1" target="_blank">$1</a>', $text);
		return $new_text;
	}


	/**
	* Set the width and the height for the user display picture
	*
	* This does not manipulate any image, this method
	* only sets the values that other methods/function
	* can take advantage of.
	*
	* This method can accept an array with the width and
	* height in seperate indexes, a string with just one
	* number which will be used for both width and height,
	* or a string with the width and height seperated with
	* an "x".
	*
	* @access protected
	* @return void
	* @since 1.0.0
	*
	* @param mixed $width_height The desired width/height of the display picture; either a string or an array is accepted
	*/
	/************************************************/
	protected function set_dp_size( $width_height ) {
		if ( ! is_array( $width_height ) ) {
			$width_height_arr = explode( 'x', $width_height );

			if ( is_array( $width_height_arr ) && count( $width_height_arr ) === 2 ) {
				$this->dp_width  = $width_height_arr[0];
				$this->dp_height = $width_height_arr[1];

			} else {
				$this->dp_width  = $width_height;
				$this->dp_height = $width_height;
			}

		} elseif ( is_array( $width_height ) ) {
			$this->dp_width  = $width_height[0];
			$this->dp_height = $width_height[1];
		}
	}


	/**
	* Mask sensitive information
	*
	* Takes a string and replaces certain values with
	* an "x" to retain the privacy of sensitive data.
	*
	* @access protected
	* @return string
	* @since 1.0.0
	*
	* @param string $string     The string you wish to have masked
	* @param int    $start      The point at which you wish masking to begin
	* @param int    $end_offset The point at which you wish masking to end; 0 will result in masking until the end of the $string
	*/
	protected function mask_data( $string, $start = 3, $end_offset = 3 ) {
		$char_data = str_split( $string );
		$length    = count( $char_data ) - 1;

		for ( $i = $start; $i <= $length - $end_offset; $i++ ) {
			if ( $char_data[ $i ] != '-' ) {
				$char_data[ $i ] = 'x';
			}
		}

		$string = '';
		foreach ( $char_data as $char ) {
			$string .= $char;
		}

		return $string;
	}


	/**
	* A request to hide the plugin's WordPress admin menu item
	*
	* This method is used to register the hiding of
	* this plugin's menu item from the WordPress admin
	* menu but it does not execute it.
	*
	* @access public
	* @return void
	* @used_by DevBuddy_Feed_Plugin_Twitter::hide_wp_admin_menu_item() Executes this request
	* @since 1.0.0
	*/
	public function hide_admin_page() {
		remove_submenu_page( 'options-general.php', $this->page_uri_main );
	}


	/**
	* A request to hide the plugin's WordPress admin menu item
	*
	* This method is used to register the hiding of
	* this plugin's menu item from the WordPress admin
	* menu but it does not execute it.
	*
	* @access public
	* @return void
	* @uses DevBuddy_Feed_Plugin_Twitter::hide_admin_page() Registers this request
	* @since 1.0.0
	*/
	public function hide_wp_admin_menu_item() {
		add_action( 'admin_menu', array( $this, 'hide_admin_page' ), 999 );
	}
}
?>