/* global GTSTranslationOrderObject, Swal */

/**
 * @param GTSTranslationOrderObject.url
 * @param GTSTranslationOrderObject.addToCartAction
 * @param GTSTranslationOrderObject.addToCartNonce
 * @param GTSTranslationOrderObject.deleteFromCartAction
 * @param GTSTranslationOrderObject.deleteFromCartNonce
 * @param GTSTranslationOrderObject.addToCartText
 * @param GTSTranslationOrderObject.deleteFromCartText
 */
jQuery( document ).ready( function( $ ) {

	removeFromCart();
	addToCart();

	/**
	 * Init modal target language.
	 */
	if ( $( '#language-modal' ).length ) {
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
	function addToCart() {
		$( '.add-to-cart' ).click( function( e ) {
			e.preventDefault();

			let event = $( this );

			let data = {
				action: GTSTranslationOrderObject.addToCartAction,
				nonce: GTSTranslationOrderObject.addToCartNonce,
				post_id: $( this ).data( 'post_id' )
			};

			$.ajax( {
				type: 'POST',
				url: GTSTranslationOrderObject.url,
				data: data,
				beforeSend: function() {
					Swal.fire( {
						title: GTSTranslationOrderObject.addToCartText,
						didOpen: () => {
							Swal.showLoading();
						},
					} );
				},
				success: function( res ) {
					if ( res.success ) {
						event.off( 'click' );
						change_icon( data.post_id, 'add' );
						Swal.close();
					}
				},
				error: function( xhr ) {
					console.log( 'error...', xhr );
					//error logging
				}
			} );
		} );
	}

	/**
	 * Bulk add to cart ajax.
	 */
	$( '.add-bulk-to-cart' ).click( function( e ) {
		e.preventDefault();

		let postsID = [];

		let elements = $( '[name^=\'gts_to_translate\']:checked' );

		console.log( elements );

		$.each( elements, function( i, val ) {
			postsID.push( $( val ).data( 'id' ) );
		} );

		console.log( postsID )

		let data = {
			action: GTSTranslationOrderObject.addToCartAction,
			nonce: GTSTranslationOrderObject.addToCartNonce,
			bulk: true,
			post_id: postsID
		};

		$.ajax( {
			type: 'POST',
			url: GTSTranslationOrderObject.url,
			data: data,
			beforeSend: function() {
				Swal.fire( {
					title: GTSTranslationOrderObject.addToCartText,
					didOpen: () => {
						Swal.showLoading();
					},
				} );
			},
			success: function( res ) {
				if ( res.success ) {
					Swal.close();
					location.reload();
				}
			},
			error: function( xhr ) {
				console.log( 'error...', xhr );
				//error logging
			}
		} );
	} );

	/**
	 * Change Icon.
	 *
	 * @param postID
	 */
	function change_icon( postID, type ) {
		let icon = $( `[data-post_id=${postID}] > i` );
		let button = $( icon ).parent();

		if ( 'add' === type ) {
			icon.removeClass( 'bi-plus-square' ).addClass( 'bi-dash-square' );
			button.removeClass( 'add-to-cart' ).addClass( 'remove-to-cart' );

			removeFromCart();
		}

		if ( 'remove' === type ) {
			icon.removeClass( 'bi-dash-square' ).addClass( 'bi-plus-square' );
			button.removeClass( 'remove-to-cart' ).addClass( 'add-to-cart' );

			addToCart();
		}
	}

	/**
	 * Select all post.
	 */
	let checked = false;
	$( '#gts_to_all_page' ).change( function( e ) {

		if ( $( this ).prop( 'checked' ) ) {
			checked = ! checked;
		} else {
			checked = ! checked;
		}

		let item = $( '[name^=\'gts_to_translate\']' );
		$.each( item, function( i, val ) {
			$( val ).prop( 'checked', checked );
		} );
	} );

	/**
	 * Remove post from cart.
	 */
	function removeFromCart() {
		$( '.remove-to-cart' ).click( function( e ) {
			e.preventDefault();

			let data = {
				action: 'delete_from_cart',
				nonce: GTSTranslationOrderObject.deleteFromCartNonce,
				post_id: $( this ).data( 'post_id' )
			};

			let event = $( this );

			$.ajax( {
				type: 'POST',
				url: GTSTranslationOrderObject.url,
				data: data,
				beforeSend: function() {
					Swal.fire( {
						title: GTSTranslationOrderObject.deleteFromCartText,
						didOpen: () => {
							Swal.showLoading();
						},
					} );
				},
				success: function( res ) {
					if ( res.success ) {
						if ( $( '.cart' ).length ) {
							$(event).parents('tr').remove();
							location.reload();
						}
						event.off( 'click' );
						change_icon( data.post_id, 'remove' );
						Swal.close();
					}
				},
				error: function( xhr ) {
					console.log( 'error...', xhr );
					//error logging
				}
			} );
		} );
	}
} );