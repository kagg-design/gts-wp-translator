jQuery( document ).ready( function( $ ) {
	if ( $( '#language-modal' ).length ) {
		console.log($( '#language-modal' ).length)
		var languageModal = new bootstrap.Modal( '#language-modal' );

		$( '#target-language' ).click( function() {
			languageModal.show();
		} );
	}

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


	let flag_view = false;

	$( '#eye_btn' ).click( function( e ) {
		if ( ! flag_view ) {
			$( '#gts_token' ).attr( 'type', 'text' )
			$( this ).find( 'i' ).removeClass( 'bi-eye-fill' )
			$( this ).find( 'i' ).addClass( 'bi-eye-slash-fill' )
		} else {
			$( '#gts_token' ).attr( 'type', 'password' )
			$( this ).find( 'i' ).removeClass( 'bi-eye-slash-fill' )
			$( this ).find( 'i' ).addClass( 'bi-eye-fill' )
		}

		flag_view = ! flag_view;
	} );
} );