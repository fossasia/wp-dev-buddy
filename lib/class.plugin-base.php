<?php

/**
* A class that will be common across feed plugins
*
* This class is used as a provider of properties
* and methods that will be common across feed
* plugins.
*
* @version 1.0.2
*/
if ( ! class_exists( 'DevBuddy_Feed_Plugin' ) ) {

class DevBuddy_Feed_Plugin {

	/**
	* @var string The name of the plugin to be used within the code
	*/
	public $plugin_name;

	/**
	* @var mixed Holds raw feed data returned from API after main request is made
	*/
	public $feed_data;

	/**
	* @var array Holds the configuration options once the feed class has been instantiated
	*/
	public $options = array();

	/**
	* @var string The output of the entire feed will be stored here
	*/
	public $output = '';

	/**
	* @var int The number of feed items that have been rendered
	*/
	private $item_count = 0;

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
	private $dp_width;

	/**
	* @var string The height of the display picture when set by the user
	*/
	private $dp_height;


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
	* @since 1.0.1
	*/
	public function get_option( $option_entry, $option_name ) {
		$options = get_option( $option_entry );

		if ( isset( $options[ $option_name ] ) && $options[ $option_name ] != '' ) {
			return $options[ $option_name ];
		} else {
			return FALSE;
		}
	}


	/**
	* An alias of DevBuddy_Feed_Plugin::get_option()
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
		return $this->get_option( $option_entry, $option_name );
	}


	/**
	* Increase the feed item count by one
	*
	* @access public
	* @return void
	* @since 1.0.1
	*/
	public function increase_feed_item_count() {
		$this->item_count++;
	}


	/**
	* Increase the feed item count by one
	*
	* @access public
	* @return void
	* @since 1.0.1
	*/
	public function get_item_count() {
		return $this->item_count;
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
			set_transient( $this->plugin_name.'_output_'.$this->options['user'], $this->output, 3600*$hours );

			$cache_successful = get_transient( $this->plugin_name.'_output_'.$this->options['user'] );

			if ( $cache_successful ) {
				$this->is_cached = TRUE;
			}
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

		$clear_cache_successful = ( get_transient( $this->plugin_name.'_output_'.$user ) ) ? FALSE : TRUE;

		if ( $clear_cache_successful ) {
			$this->is_cached = FALSE;
		}
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
		$a_day   = $an_hour*24;
		$a_week  = $a_day*7;

		$now  = time();
		$then = strtotime( $datetime );
		$diff = $now - $then;

		$mins          = $diff / 60 % 60;
		$the_mins_ago  = $mins;
		$the_mins_ago .= ( $mins == '1' ) ? ' minute ago' : ' minutes ago';

		$hours          = $diff / 3600 % 24;
		$the_hours_ago  = 'About ';
		$the_hours_ago .= $hours;
		$the_hours_ago .= ( $hours == '1' ) ? ' hour ago' : ' hours ago';

		$the_time = date( 'H:i', $then );
		$the_day  = date( 'D', $then );
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
	* @access public
	* @return void
	* @since 1.0.0
	*
	* @param mixed $width_height The desired width/height of the display picture; either a string or an array is accepted
	*/
	/************************************************/
	public function set_dp_size( $width_height ) {
		$min_size = 10;
		$max_size = 200;

		if ( ! is_array( $width_height ) ) {
			$width_height_arr = explode( 'x', $width_height );

			// No "x" was present
			if ( is_array( $width_height_arr ) && count( $width_height_arr ) === 1 ) {
				$width_height_arr[0] = $width_height_arr[0];
				$width_height_arr[1] = $width_height_arr[0];

			// "x" was present
			} elseif ( is_array( $width_height_arr ) && count( $width_height_arr ) === 2 ) {
				/* Don't actually need to do anything here,
				   but we don't want this condition getting
				   caught in the "else" either */

			// Empty string
			} else {
				$width_height_arr[0] = $this->defaults['dp_size'];
				$width_height_arr[1] = $this->defaults['dp_size'];
			}

		// An array of two items, both numeric
		} elseif( is_array( $width_height ) && count( $width_height ) === 2 ) {
			$width_height_arr[0] = $width_height[0];
			$width_height_arr[1] = $width_height[1];
		}

		// Check for minimums and maximums
		$i = 0;
		foreach ( $width_height_arr as $dimension ) {
			if ( $dimension < $min_size ) {
				$width_height_arr[ $i ] = $min_size;

			} elseif( $dimension > $max_size ) {
				$width_height_arr[ $i ] = $max_size;
			}

			$i++;
		}
		unset( $i );

		$this->dp_width  = $width_height_arr[0];
		$this->dp_height = $width_height_arr[1];
	}


	/**
	* Return the values of the display picture size
	*
	* This value is set via DevBuddy_Feed_Plugin::set_dp_size()
	*
	* @access public
	* @return array
	* @since 1.0.1
	*/
	public function get_dp_size() {
		$dp = array( 'width' => $this->dp_width, 'height' => $this->dp_height );
		return $dp;
	}


	/**
	* Converts comma-separated values in a string to an array
	*
	* Sometimes a value may be either an array or a string
	* so this is a way to ensure that we always get a the
	* format we want
	*
	* @access public
	* @return mixed
	* @since 1.0.2
	*/
	public function list_convert( $list ) {
		if ( ! is_array( $list ) ) {
			$list = explode( ',', $list );
		}

		return $list;
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
	* @used_by DevBuddy_Feed_Plugin::hide_wp_admin_menu_item() Executes this request
	* @since 1.0.0
	*/
	public function hide_admin_page() {
		remove_submenu_page( 'options-general.php', $this->page_uri_main );
	}


	/**
	* A request to hide the plugin's WordPress admin menu item
	*
	* This method is used to execute the hiding of
	* this plugin's menu item from the WordPress admin.
	*
	* @access public
	* @return void
	* @uses DevBuddy_Feed_Plugin::hide_admin_page() Registers this request
	* @since 1.0.0
	*/
	public function hide_wp_admin_menu_item() {
		add_action( 'admin_menu', array( $this, 'hide_admin_page' ), 999 );
	}

} // END class

} // END class_exists

?>