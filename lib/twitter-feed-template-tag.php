<?php
function db_twitter_feed( $feed_config = NULL ) {
	$the_feed = new DB_Twitter_Feed( $feed_config );

	if ( $the_feed->is_cached ) {
		$the_feed->echo_output();

	} else {
		$the_feed->retrieve_feed_data();
		$the_feed->render_feed_html();
		$the_feed->echo_output();
	}
}
?>