jQuery( document ).ready( function( $ ) {
	const languageModal = new bootstrap.Modal( '#language-modal' );

	$( '#target-language' ).click( function() {
		languageModal.show();
	} );

	$( '#save-target-language' ).click( function( e ) {
		e.preventDefault();

		let languages = $( '.lang-checkbox:checked' );
		let languagesTextArray = [];
		let languagesSlugArray = [];

		$.each( languages, function( index, value ) {
			languagesTextArray.push( $( value ).parent().find( 'label' ).text().trim() );
			languagesSlugArray.push( $( value ).val() );
		} );

		$( '#target-language' ).attr( 'value', languagesTextArray.join( ', ' ) )
		$( '#gts_target_language' ).val( languagesSlugArray.join( ',' ) )

		languageModal.hide();
	} );

	/**
	 * Get cookie by name.
	 *
	 * @param name
	 * @returns {string|null}
	 */
	function getCookie( name ) {
		function escape( s ) {
			return s.replace( /([.*+?\^$(){}|\[\]\/\\])/g, '\\$1' );
		}

		let match = document.cookie.match( RegExp( '(?:^|;\\s*)' + escape( name ) + '=([^;]*)' ) );
		return match ? match[ 1 ] : null;
	}
} );