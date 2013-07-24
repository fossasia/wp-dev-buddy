<?php
$db_twitter_feed_option_group = 'db_twitter_feed';
$db_twitter_feed_option_name = 'db_twitter_feed_options';

$db_twitter_feed_sections =
array(
	'config' => array(
		'id' => 'configuration_sec',
		'title' => 'Configuration',
		'callback' => 'write_configuration_sec',
		'page' => 'db-twitter-feed-settings'
	),
	'settings' => array(
		'id' => 'settings_sec',
		'title' => 'General Settings',
		'callback' => 'write_settings_sec',
		'page' => 'db-twitter-feed-settings'
	)
);

$db_twitter_feed_settings =
array(
	'oauth_access_token' => array(
		'id' => 'oauth_access_token',
		'title' => 'OAuth Access Token',
		'callback' => 'write_oauth_access_token_field',
		'page' => 'db-twitter-feed-settings',
		'section' => 'configuration_sec'
	),
	'oauth_access_token_secret' => array(
		'id' => 'oauth_access_token_secret',
		'title' => 'OAuth Access Token Secret',
		'callback' => 'write_oauth_access_token_secret_field',
		'page' => 'db-twitter-feed-settings',
		'section' => 'configuration_sec'
	),
	'consumer_key' => array(
		'id' => 'consumer_key',
		'title' => 'Consumer Key',
		'callback' => 'write_consumer_key_field',
		'page' => 'db-twitter-feed-settings',
		'section' => 'configuration_sec'
	),
	'consumer_secret' => array(
		'id' => 'consumer_secret',
		'title' => 'Consumer Secret',
		'callback' => 'write_consumer_secret_field',
		'page' => 'db-twitter-feed-settings',
		'section' => 'configuration_sec'
	),
	'twitter_user' => array(
		'id' => 'twitter_username',
		'title' => 'Twitter Username',
		'callback' => 'write_twitter_username_field',
		'page' => 'db-twitter-feed-settings',
		'section' => 'settings_sec'
	),
	'result_count' => array(
		'id' => 'result_count',
		'title' => 'Number of tweets to show',
		'callback' => 'write_result_count_field',
		'page' => 'db-twitter-feed-settings',
		'section' => 'settings_sec'
	),
	'exclude_replies' => array(
		'id' => 'exclude_replies',
		'title' => 'Exclude replies?',
		'callback' => 'write_exclude_replies_field',
		'page' => 'db-twitter-feed-settings',
		'section' => 'settings_sec'
	),
	'default_styling' => array(
		'id' => 'default_styling',
		'title' => 'Load default stylesheet?',
		'callback' => 'write_default_styling_field',
		'page' => 'db-twitter-feed-settings',
		'section' => 'settings_sec'
	)/*,
	'' => array(
		'id' => '',
		'title' => '',
		'callback' => 'write__field',
		'page' => 'db-twitter-feed-settings',
		'section' => ''
	)*/
);


/**************************************************************************************************************
 Add the settings page to the WP admin main menu
 **************************************************************************************************************/
// Add "Twitter Plugin" as a menu item under the "Setting" tab
function add_db_twitter_feed_menu_item() {
	add_submenu_page(
		'options-general.php',
		'Configure your Twitter feed set up',
		'Twitter Feed Settings',
		'manage_options',
		'db-twitter-feed-settings',
		'db_twitter_feed_settings_markup'
	);
}
add_action('admin_menu', 'add_db_twitter_feed_menu_item');

// Initialise all of the settings, settings sections, and settings options
function init_db_twitter_feed_options() {
	global $db_twitter_feed_option_group, $db_twitter_feed_option_name, $db_twitter_feed_sections, $db_twitter_feed_settings;
	register_setting($db_twitter_feed_option_group, $db_twitter_feed_option_name);

	// Loop through the Sections and Settings arrays and add them to WordPress
	foreach($db_twitter_feed_sections as $section) {
		add_settings_section(
			$section['id'],
			$section['title'],
			$section['callback'],
			$section['page']
		);
	}
	foreach($db_twitter_feed_settings as $setting) {
		add_settings_field(
			$setting['id'],
			$setting['title'],
			$setting['callback'],
			$setting['page'],
			$setting['section']
		);
	}
}
add_action('admin_init', 'init_db_twitter_feed_options');


/**************************************************************************************************************
 Callbacks for writing the option fields themselves to the options page
 **************************************************************************************************************/
function db_twitter_feed_settings_markup() {
	if(!current_user_can('manage_options')) {
		wp_die(__('You do not have sufficient permissions to access this page.'));
	}

	global $dbtf, $db_twitter_feed_option_group; ?>

	<div class="wrap">

		<?php screen_icon() ?>
		<h2>Twitter Feed Settings</h2>

		<form id="db_twitter_feed_settings" action="options.php" method="post">
			<?php
			if(isset($_GET['settings-updated']) && (bool)$_GET['settings-updated'] === true) {
				$user = $dbtf->get_dbtf_option('twitter_username');
				$dbtf->empty_feed_cache($user);
			}

			settings_fields($db_twitter_feed_option_group);
			do_settings_sections('db-twitter-feed-settings');
			submit_button('Save Changes'); ?>
		</form>

	</div><!--END-wrap-->
<?php }


/* Write Configuration section
*******************************************/
function write_configuration_sec() {
	echo 'You\'ll need to log into the <a href="https://dev.twitter.com/" target="_blank">Twitter Developer page</a> and set up an app. Once you\'ve set up and app you will get the data necessary for below.';
}

function write_oauth_access_token_field() {
	global $db_twitter_feed_option_name;
	$options = get_option($db_twitter_feed_option_name);

	echo '<input type="text" name="'.$db_twitter_feed_option_name.'[oauth_access_token]" value="'.$options['oauth_access_token'].'" style="width:450px;" />';
}

function write_oauth_access_token_secret_field() {
	global $db_twitter_feed_option_name;
	$options = get_option($db_twitter_feed_option_name);

	echo '<input type="text" name="'.$db_twitter_feed_option_name.'[oauth_access_token_secret]" value="'.$options['oauth_access_token_secret'].'" style="width:450px;" />';
}

function write_consumer_key_field() {
	global $db_twitter_feed_option_name;
	$options = get_option($db_twitter_feed_option_name);

	echo '<input type="text" name="'.$db_twitter_feed_option_name.'[consumer_key]" value="'.$options['consumer_key'].'" style="width:450px;" />';
}

function write_consumer_secret_field() {
	global $db_twitter_feed_option_name;
	$options = get_option($db_twitter_feed_option_name);

	echo '<input type="text" name="'.$db_twitter_feed_option_name.'[consumer_secret]" value="'.$options['consumer_secret'].'" style="width:450px;" />';
}


/* Write General Settings section
*******************************************/
function write_settings_sec() {
	echo '';
}

function write_twitter_username_field() {
	global $db_twitter_feed_option_name;
	$options = get_option($db_twitter_feed_option_name);

	echo '<input type="text" name="'.$db_twitter_feed_option_name.'[twitter_username]"';
	if(isset($options['twitter_username'])) {
		echo ' value="'.$options['twitter_username'].'"';
	}
	echo ' />';
}

function write_result_count_field() {
	global $db_twitter_feed_option_name;
	$options = get_option($db_twitter_feed_option_name);

	echo '<select name="'.$db_twitter_feed_option_name.'[result_count]">';
	for($num = 1; $num <= 30; $num++) {
		echo '<option value="'.$num.'"';
		if(isset($options['result_count']) && (int)$options['result_count'] === $num) {
			echo ' selected="selected"';
		}
		echo '>'.$num.'</option>';
	}
	echo '</select>';
}

function write_exclude_replies_field() {
	global $db_twitter_feed_option_name;
	$options = get_option($db_twitter_feed_option_name);

	echo '<input type="checkbox" name="'.$db_twitter_feed_option_name.'[exclude_replies]" value="1"';
	if(isset($options['exclude_replies']) && (int)$options['exclude_replies'] === 1) {
		echo ' checked="checked"';
	}
	echo '/>';
	echo '<br /><em style="color:#5e5e5e;">Twitter removes replies only after it retrieves the number of tweets you request.<br />Thus if you choose 10, and out of that 10 6 are replies, only 4 tweets will be displayed.</em>';
}

function write_default_styling_field() {
	global $db_twitter_feed_option_name;
	$options = get_option($db_twitter_feed_option_name);

	echo '<input type="checkbox" name="'.$db_twitter_feed_option_name.'[default_styling]" value="1"';
	if(isset($options['default_styling']) && (int)$options['default_styling'] === 1) {
		echo ' checked="checked"';
	}
	echo '/>';
}

?>