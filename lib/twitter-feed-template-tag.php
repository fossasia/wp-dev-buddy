<?php
function db_twitter_feed( $feed_config = NULL ) {
	$the_feed = new DB_Twitter_Feed( $feed_config );

	if ( $the_feed->is_cached ) {
		$the_feed->echo_output();

	} else {
		$the_feed->retrieve_feed_data();

		if ( $the_feed->is_empty() ) {
			$the_feed->output .= '<p style="text-align:center;">Looks like your timeline is completely empty!<br />Why don&rsquo;t you <a href="'.$the_feed->tw.'" target="_blank">login to Twitter</a> and post a tweet or two.</p>';

		} else {
			$the_feed->render_feed_html();
		}

		$the_feed->echo_output();
	}
}
?>