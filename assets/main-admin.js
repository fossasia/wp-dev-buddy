(function($) {
	var n  = DBTF.pluginName;
	var sn = DBTF.pluginShortName;

	var optionsName  = DBTF.optionsNameMain;
	var optionsGroup = DBTF.optionsGroup;

	// Disable all but the selected feed type
	$( '.input_feed_type' ).attr( { disabled:'disabled' } );
	$( 'input[name="' + optionsName + '[feed_type]"]' ).each( function() {
		if ( $( this ).is( ':checked' ) ) {
			enableFeedTypeInput( $( this ).val() );
		}
	} );

	// When the feed type option is changed, enable the corresponding feed term input field
	$( 'input[name="' + optionsName + '[feed_type]"]' ).on( 'change', function(e) {
		var $target = $( e.target );
		enableFeedTypeInput( $target.val() );
	} );

	// When feed values have been edited, reflect the change in the hidden values
	$( '.input_feed_type' ).on( 'change', function(e) {
		var termInput    = $( e.target ).attr( 'name' );
		var termInputHid = termInput.replace( /]/, '_hid]' );

		$( 'input[name="' + termInputHid + '"]' ).val( $( e.target ).val() );
	} );


	// Enable a feed term input field and disable all others
	function enableFeedTypeInput( feedTypeSelected ) {
		$( '.input_feed_type' ).attr( { disabled:'disabled' } );

		switch ( feedTypeSelected ) {
			case 'user_timeline':
				var activeField = $( '#' + sn + '_twitter_username' );
			break;

			case 'search':
				var activeField = $( '#' + sn + '_search_term') ;
			break;
		}

		activeField.removeAttr( 'disabled' );
	}
})(jQuery);