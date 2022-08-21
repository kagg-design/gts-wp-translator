<?php
/**
 * AdminNotice Class file.
 *
 * @package gts/wp-translator
 */

namespace GTS\WPTranslator\Admin;

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
			esc_html__( 'Bad nonce!', 'gts-wp-translator' )
		);
	}
}
