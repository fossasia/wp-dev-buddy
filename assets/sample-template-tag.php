<?php

/*
Once you've initialised the feed using `new DB_Twitter_Feed` the configuration options
will be available to you as an array. The defaults of which are shown below:

$the_feed->$options = array(
	'user'                      => 'EjiOsigwe', // String: Any valid Twitter username
	'count'                     => '10',        // String: Number of tweets to retrieve
	'exclude_replies'           => 'no',        // String: ("yes" or "no") Only display tweets that aren't replies
	'default_styling'           => 'no',        // String: ("yes" or "no") Load the bundled stylesheet
	'cache_hours'               => 3,           // Int:    Number of hours to cache the output
	'clear_cache'               => 'no',        // String: ("yes" or "no") Clear the cache for the set "user",
	'oauth_access_token'        => NULL,        // String: The OAuth Access Token
	'oauth_access_token_secret' => NULL,        // String: The OAuth Access Token Secret
	'consumer_key'              => NULL,        // String: The Consumer Key
	'consumer_secret'           => NULL         // String: The Consumer Secret
)

There are also some Twitter specific links available too:
$the_feed->tw = 'https://twitter.com/';
$the_feed->search = 'https://twitter.com/search?q=%23';
$the_feed->intent = 'https://twitter.com/intent/';

Any other useful methods and properties are used and commented on in the template tag below.
*/

// Copy this entire function into your theme
// Read through the comments and edit as you see fit
function my_twitter_feed_template_tag( $feed_config = NULL ) {

	/* Configuration validity checks are performed on initialisation
	   so you don't have to worry your (presumably) pretty little head */
	$the_feed = new DB_Twitter_Feed( $feed_config );

	if ( ! $the_feed->is_cached ) {
		// We only want to talk to Twitter when our cache is on empty
		$the_feed->retrieve_feed_data();

		// After attempting data retrieval, check for errors
		if ( $the_feed->has_errors() ) {
			$the_feed->output .= '<p>Your own error message here. Otherwise you can loop through the <code>$errors</code> (array) property to view the errors returned.</p>';

		// Then check for an empty timeline
		} elseif( $the_feed->is_empty() ) {
			$the_feed->output .= '<p>Your own &ldquo;timeline empty&rdquo; message here.</p>';

		// With the checks done we can get to HTML renderin'
		} else {

			/* Below is the default HTML layout. Tweak to taste
			*********************************************************/
			$the_feed->output .= '<div class="tweets">'; // START The Tweet list

			foreach ( $the_feed->feed_data as $tweet ) {
				/* parse_tweet_data() takes the raw data, gets what's useful, formats it,
				   and returns it as a nice, neat array */
				$t = $the_feed->parse_tweet_data( $tweet );


				// START Rendering the Tweet's HTML (outer tweet wrapper)
				$the_feed->output .= '<article class="tweet">';

				// START tweet_content (inner tweet wrapper)
				$the_feed->output .= '<div class="tweet_content">';

				// START Display pic
				$the_feed->output .= '<figure class="tweet_profile_img">';
				$the_feed->output .= '<a href="'.$the_feed->tw.$t['user_screen_name'].'" target="_blank" title="'.$t['user_display_name'].'"><img src="'.$t['profile_img_url'].'" alt="'.$t['user_display_name'].'" /></a>';
				$the_feed->output .= '</figure>';
				// END Display pic

				// START Twitter username/@screen name
				$the_feed->output .= '<header class="tweet_header">';
				$the_feed->output .= '<a href="'.$the_feed->tw.$t['user_screen_name'].'" target="_blank" class="tweet_user" title="'.$t['user_description'].'">'.$t['user_display_name'].'</a>';
				$the_feed->output .= ' <span class="tweet_screen_name">@'.$t['user_screen_name'].'</span>';
				$the_feed->output .= '</header>';
				// END Twitter username/@screen name

				// START The Tweet text
				$the_feed->output .= '<div class="tweet_text">'.$t['text'].'</div>';
				// END The Tweet text

				// START Tweet footer
				$the_feed->output .= '<div class="tweet_footer">';

				// START Tweet date
				$the_feed->output .= '<a href="'.$the_feed->tw.$t['user_screen_name'].'/status/'.$t['id'].'" target="_blank" title="View this tweet in Twitter" class="tweet_date">'.$t['date'].'</a>';
				// END Tweet date

				// START "Retweeted by"
				if ( $t['is_retweet'] ) {
					$the_feed->output .= '<span class="tweet_retweet">';
					$the_feed->output .= '<span class="tweet_icon tweet_icon_retweet"></span>';
					$the_feed->output .= 'Retweeted by ';
					$the_feed->output .= '<a href="'.$the_feed->tw.$t['retweeter_screen_name'].'" target="_blank" title="'.$t['retweeter_display_name'].'">'.$t['retweeter_display_name'].'</a>';
					$the_feed->output .= '</span>';
				}
				// END "Retweeted by"

				// START Tweet intents
				$the_feed->output .= '<div class="tweet_intents">';

				// START Reply intent
				$the_feed->output .= '<a href="'.$the_feed->intent.'tweet?in_reply_to='.$t['id'].'" title="Reply to this tweet" target="_blank" class="tweet_intent_reply">';
				$the_feed->output .= '<span class="tweet_icon tweet_icon_reply"></span>';
				$the_feed->output .= '<b>Reply</b></a>';
				// END Reply intent

				// START Retweet intent
				$the_feed->output .= '<a href="'.$the_feed->intent.'retweet?tweet_id='.$t['id'].'" title="Retweet this tweet" target="_blank" class="tweet_intent_retweet">';
				$the_feed->output .= '<span class="tweet_icon tweet_icon_retweet"></span>';
				$the_feed->output .= '<b>Retweet</b></a>';
				// END Retweet intent

				// START Favourite intent
				$the_feed->output .= '<a href="'.$the_feed->intent.'favorite?tweet_id='.$t['id'].'" title="Favourite this tweet" target="_blank" class="tweet_intent_favourite">';
				$the_feed->output .= '<span class="tweet_icon tweet_icon_favourite"></span>';
				$the_feed->output .= '<b>Favourite</b></a>';
				// END Favourite intent

				$the_feed->output .= '</div>';     // END Tweet intents
				$the_feed->output .= '</div>';     // END Tweet footer
				$the_feed->output .= '</div>';     // END Tweet content
				$the_feed->output .= '</article>'; // END Rendering Tweet's HTML
			}

			$the_feed->output .= '</div>'; // END The Tweet list

			$the_feed->cache_output( $the_feed->options['cache_hours'] );

		}

	}

	/* WP needs shortcode called content to be returned
	   rather than echoed, which is where the
	   $is_shortcode_called property comes in */
	if ( $the_feed->is_shortcode_called ) {
		return $the_feed->output;
	} else {
		echo $the_feed->output;
	}

}

?>