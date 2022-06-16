<?php
/**
 * Admin Notification.
 *
 * @package gts/translation-order
 */

namespace GTS\TranslationOrder\Admin;

/**
 * AdminNotice Class file.
 */
class AdminNotice {

	/**
	 * Error message.
	 *
	 * @var string
	 */
	public $error_message;

	/**
	 * Bad nonce error.
	 *
	 * @return void
	 */
	public static function bad_nonce() {
		printf(
			'<div id="pcs-php-nope" class="notice notice-error is-dismissible"><p>%s</p></div>',
			esc_html__( 'Bad nonce!', 'gts-translation-order' )
		);
	}

	/**
	 * Bad nonce error.
	 *
	 * @return void
	 */
	public static function empty_token() {
		printf(
			'<div id="pcs-php-nope" class="notice notice-error is-dismissible"><p>%s</p></div>',
			esc_html__( 'Empty Token!', 'gts-translation-order' )
		);
	}

	/**
	 * Api Errors.
	 *
	 * @return void
	 */
	public function api_error() {
		printf(
			'<div id="pcs-php-nope" class="notice notice-error is-dismissible"><p>%s</p></div>',
			esc_html( $this->error_message )
		);
	}

	/**
	 * Token accepted.
	 *
	 * @return void
	 */
	public static function token_success() {
		printf(
			'<div id="pcs-php-nope" class="notice notice-success is-dismissible"><p>%s</p></div>',
			esc_html__( 'Token generated and accepted', 'gts-translation-order' )
		);
	}

	/**
	 * Unknown error.
	 */
	public static function error() {
		printf(
			'<div id="pcs-woocommerce-nope" class="notice notice-error is-dismissible"><p>%s</p></div>',
			esc_html__( 'Unknown error. Please contact the plugin developer.', 'gts-translation-order' )
		);
	}
}
