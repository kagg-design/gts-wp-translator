/* global gts_main */
jQuery( document ).ready( function( $ ) {

	/**
	 * Init modal target language.
	 */

	if ( $( '#language-modal' ).length ) {
		console.log( $( '#language-modal' ).length )
		var languageModal = new bootstrap.Modal( '#language-modal' );

		$( '#target-language' ).click( function() {
			languageModal.show();
		} );
	}

	/**
	 * Save target language field.
	 */
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
	 * Change icon and text.
	 */

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


	/**
	 * Single add to cart ajax.
	 */
	$( '.add-to-cart' ).click( function( e ) {
		e.preventDefault();

		let data = {
			action: 'add_to_cart',
			nonce: gts_main.nonce,
			post_id: $( this ).data( 'post_id' )
		};

		$.ajax( {
			type: 'POST',
			url: gts_main.url,
			data: data,
			success: function( res ) {
				// do something with ajax data
				console.log( res );
			},
			error: function( xhr ) {
				console.log( 'error...', xhr );
				//error logging
			}
		} );
	} );

	/**
	 * Bulk add to cart ajax.
	 */
	$( '.add-bulk-to-cart' ).click( function( e ) {
		e.preventDefault();

		let postsID = [];

		let elements = jQuery( '[name^=\'gts_to_translate\']:checked' );

		console.log( elements );

		$.each( elements, function( i, val ) {
			postsID.push( $( val ).data( 'id' ) );
		} );

		console.log( postsID )

		let data = {
			action: 'add_to_cart',
			nonce: gts_main.nonce,
			bulk: true,
			post_id: postsID
		};
	} );
} );