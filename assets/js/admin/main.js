/* global GTSTranslationOrderObject, Swal */

/**
 * @param GTSTranslationOrderObject.url
 * @param GTSTranslationOrderObject.addToCartAction
 * @param GTSTranslationOrderObject.addToCartNonce
 * @param GTSTranslationOrderObject.deleteFromCartAction
 * @param GTSTranslationOrderObject.deleteFromCartNonce
 * @param GTSTranslationOrderObject.sendToTranslationAction
 * @param GTSTranslationOrderObject.sendToTranslationNonce
 * @param GTSTranslationOrderObject.addToCartText
 * @param GTSTranslationOrderObject.deleteFromCartText
 * @param GTSTranslationOrderObject.createOrder
 * @param GTSTranslationOrderObject.cartCookieName
 * @param GTSTranslationOrderObject.updatePrice
 * @param GTSTranslationOrderObject.updatePriceNonce
 * @param GTSTranslationOrderObject.sendOrderText
 * @param GTSTranslationOrderObject.emptySource
 * @param GTSTranslationOrderObject.emptyTarget
 * @param GTSTranslationOrderObject.sendOrderTitle
 * @param GTSTranslationOrderObject.sendOrderTextConfirm
 * @param GTSTranslationOrderObject.sendOrderTextButton
 * @param GTSTranslationOrderObject.paymentLinkRedirect
 * @param GTSTranslationOrderObject.backToSelectRedirect
 * @param GTSTranslationOrderObject.sendCancelButton
 */
jQuery( document ).ready( function( $ ) {

	removeFromCart();

	function round( value, decimals ) {
		return Number( Math.round( value + 'e' + decimals ) + 'e-' + decimals );
	}

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

		updatePrice();
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
	 * Bulk add to cart ajax.
	 */
	$( '.add-bulk-to-cart' ).click( function( e ) {
		e.preventDefault();

		let postsID = [];

		let elements = $( '[name^=\'gts_to_translate\']:checked' );

		let target = $( '#target-language' ).val();
		let source = $( '#gts_source_language option:selected' ).val();


		if ( '0' === source ) {
			Swal.fire( {
				icon: 'error',
				title: 'Error',
				text: GTSTranslationOrderObject.emptySource,
			} );

			return;
		}

		if ( 0 === target.length ) {
			Swal.fire( {
				icon: 'error',
				title: 'Error',
				text: GTSTranslationOrderObject.emptyTarget,
			} );

			return;
		}

		$.each( elements, function( i, val ) {
			postsID.push( $( val ).data( 'id' ) );
		} );

		let data = {
			action: GTSTranslationOrderObject.addToCartAction,
			nonce: GTSTranslationOrderObject.addToCartNonce,
			bulk: true,
			post_id: postsID,
			target: target,
			source: source
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
				} else {
					error_message( res.data.message )
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
				action: GTSTranslationOrderObject.deleteFromCartAction,
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
							$( event ).parents( 'tr' ).remove();
							location.reload();
						}
						event.off( 'click' );
						change_icon( data.post_id, 'remove' );
						Swal.close();
					} else {
						error_message( res.data.message )
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
	 * Send posts to translation.
	 */
	$( '#gts-to-send-to-translation' ).click( function( e ) {
		e.preventDefault();

		let data = {
			action: GTSTranslationOrderObject.sendToTranslationAction,
			nonce: GTSTranslationOrderObject.sendToTranslationNonce,
			email: $( '#gts-client-email' ).val(),
			source: $( '#gts-source-language' ).val(),
			target: $( '#target-language' ).val(),
			industry: $( '#gts-industry' ).val(),
			total: $( '#total' ).text()
		};

		$.post( {
			url: GTSTranslationOrderObject.url,
			data: data,
			beforeSend: function() {
				Swal.fire( {
					title: GTSTranslationOrderObject.sendOrderText,
					didOpen: () => {
						Swal.showLoading();
					},
				} );
			},
			success: function( res ) {
				if ( res.success ) {
					Swal.close();
					Swal.fire( {
						position: 'top-end',
						icon: 'success',
						showConfirmButton: false,
						timer: 1500,
						title: GTSTranslationOrderObject.createOrder,
						willClose: () => {
							Swal.fire( {
								icon: 'success',
								showCancelButton: true,
								confirmButtonText: '<i class="fa fa-thumbs-up"></i> ' + GTSTranslationOrderObject.sendOrderTextButton,
								cancelButtonText: '<i class="fa fa-thumbs-down"></i>' + GTSTranslationOrderObject.sendCancelButton,
								title: GTSTranslationOrderObject.sendOrderTitle,
								text: GTSTranslationOrderObject.sendOrderTextConfirm,
							} ).then( ( result ) => {
								/* Read more about isConfirmed, isDenied below */
								if ( result.isConfirmed ) {
									location.href = GTSTranslationOrderObject.paymentLinkRedirect + res.data.order_id;
								} else if ( result.isDenied ) {
									location.href = GTSTranslationOrderObject.backToSelectRedirect
								}
							} )
						}
					} );


					deleteCookie( GTSTranslationOrderObject.cartCookieName );
				} else {
					error_message( res.data.message )
				}
			},
			error: function( xhr ) {
				console.log( 'error...', xhr );
				//error logging
			}
		} );
	} );

	/**
	 * Delete cookie.
	 *
	 * @param name cookie name.
	 */
	function deleteCookie( name ) {
		document.cookie = name + '=; Path=/; Expires=Thu, 01 Jan 1970 00:00:01 GMT;';
	}

	/**
	 * Update price.
	 */
	function updatePrice() {
		let data = {
			action: GTSTranslationOrderObject.updatePrice,
			nonce: GTSTranslationOrderObject.updatePriceNonce,
			target: $( '#target-language' ).val(),
			source: $( '#gts-source-language' ).val()
		}

		$.ajax( {
			type: 'POST',
			url: GTSTranslationOrderObject.url,
			data: data,
			success: function( res ) {
				if ( res.success ) {
					let newPriceArray = res.data.newPrice;
					let total = 0
					$.each( newPriceArray, function( i, val ) {
						$( `[data-post_id=${val.id}]` ).parents( 'tr' ).find( '.price' ).text( '$' + val.price )
						total += val.price
					} );

					total = round( parseFloat( total ), 2 );

					$( '#total' ).text( total );
				}
			},
			error: function( xhr, ajaxOptions, thrownError ) {
				console.log( 'error...', xhr );
				//error logging
			},
		} );
	}

	/**
	 * Open error windows.
	 *
	 * @param message
	 */
	function error_message( message ) {
		Swal.close();
		Swal.fire( {
			icon: 'error',
			title: 'Oops...',
			text: message,
		} )
	}
} );