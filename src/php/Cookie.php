<?php
/**
 * Cookie helpers.
 *
 * @package gts/translation-order
 */

namespace GTS\TranslationOrder;

/**
 * Cookie class file.
 */
class Cookie {

	/**
	 * Filter cookie name.
	 */
	const FILTER_COOKIE_NAME = 'gts-translation-order-post-filter';

	/**
	 * Cart cookie name.
	 */
	const CART_COOKIE_NAME = 'gts-translation-order-cart-data';

	/**
	 * Get filter cookie.
	 *
	 * @return object
	 */
	public static function get_filter_cookie() {
		$value = self::get( self::FILTER_COOKIE_NAME );

		if ( null !== $value ) {
			return $value;
		}

		return (object) [
			'post_type' => 'page',
			'search'    => '',
			'source'    => '',
			'target'    => [],
		];
	}

	/**
	 * Get cart cookie.
	 *
	 * @return array
	 */
	public static function get_cart_cookie() {
		$value = (array) self::get( self::CART_COOKIE_NAME );

		if ( null !== $value ) {
			return $value;
		}

		return [];
	}

	/**
	 * Get cookie.
	 *
	 * @param string $name Cookie name.
	 *
	 * @return object|null
	 */
	private static function get( $name ) {
		$cookie = isset( $_COOKIE[ $name ] ) ?
			sanitize_text_field( wp_unslash( $_COOKIE[ $name ] ) ) :
			'';

		return json_decode( $cookie );
	}

	/**
	 * Set cookie.
	 *
	 * @param string $name  Cookie name.
	 * @param mixed  $value Cookie value.
	 *
	 * @return void
	 */
	public static function set( $name, $value ) {

		if ( is_array( $value ) ) {
			$value = wp_json_encode( $value, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE );
		}

		setcookie( $name, $value, strtotime( '+30 days' ), COOKIEPATH, COOKIE_DOMAIN );

		$_COOKIE[ $name ] = $value;
	}
}
