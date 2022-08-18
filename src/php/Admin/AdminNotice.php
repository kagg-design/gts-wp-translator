<?php
/**
 * AdminNotice Class file.
 *
 * @package gts/translation-order
 */

namespace GTS\TranslationOrder\Admin;

/**
 * Admin Notification.
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
	 * Unknown error.
	 */
	public static function error() {
		printf(
			'<div id="pcs-woocommerce-nope" class="notice notice-error is-dismissible"><p>%s</p></div>',
			esc_html__( 'Unknown error. Please contact the plugin developer.', 'gts-translation-order' )
		);
	}
}
