<?php

/* Copy all of this into your theme, making sure
   to tweak the name of the shortcode to taste
*********************************************************/
add_shortcode(
	'my_twitter_feed_shortcode', // The name of your shortcode
	'register_my_twitter_feed_shortcode' // This should match the function name below
);


/* Seeing as all the shortcode does is pass data
   to the template tag, changes here will be infrequent */

/* The most likely case is when new configuration
   options are made available, at which point you'll
   need to add them in (should you want them, that is)
*********************************************************/
function register_my_twitter_feed_shortcode( $given_atts ) {

	/* Default values here will always be NULL, defaults
	   are set and checked in the plugin itself */
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

	return my_twitter_feed_template_tag( $feed_config );

}

?>