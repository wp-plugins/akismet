jQuery( document ).ready( function() {
	var timing = new Date();
	var ak_js = document.getElementById( 'ak_js' );

	// if the form field already exists just use that
	if ( ak_js ) {
		ak_js.value = timing.getTime();
		return;
	}

	var input = '<input type="hidden" id="ak_js" name="ak_js" value="' 
		+ timing.getTime() + '"/>';

	var div = document.createElement( 'div' );
	div.innerHTML = input

	// single page, front side comment form
	jQuery( '#commentform' ).append( div );

	// inline comment reply, wp-admin
	jQuery( '#replyrow td' ).append( input );
} );
