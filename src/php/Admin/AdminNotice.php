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

	public $eror_massage;

	/**
	 * Low PHP version.
	 */
	public static function bad_php_version(): void {
		printf(
			'<div id="gts-php-nope" class="notice notice-error is-dismissible"><p>%s</p></div>',
			wp_kses(
				sprintf(
				/* translators: 1: Required PHP version number, 2: Current PHP version number, 3: URL of PHP update help page */
					__( 'The GTS Translation Order plugin requires PHP version %1$s or higher. This site is running PHP version %2$s. <a href="%3$s">Learn about updating PHP.</a>', 'gts-translation-order' ),
					GTS_MINIMUM_PHP_REQUIRED_VERSION,
					PHP_VERSION,
					'https://wordpress.org/support/update-php/'
				),
				[
					'a' => [
						'href' => [],
					],
				]
			)
		);
	}

	/**
	 * Bad nonce error.
	 *
	 * @return void
	 */
	public static function bad_nonce(): void {
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
	public static function empty_token(): void {
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
	public function api_error(): void {
		printf(
			'<div id="pcs-php-nope" class="notice notice-error is-dismissible"><p>%s</p></div>',
			esc_html( $this->eror_massage )
		);
	}

	/**
	 * Token accepted.
	 *
	 * @return void
	 */
	public static function token_success(): void {
		printf(
			'<div id="pcs-php-nope" class="notice notice-success is-dismissible"><p>%s</p></div>',
			esc_html__( 'Token generated and accepted', 'gts-translation-order' )
		);
	}

	/**
	 * Unknown error.
	 */
	public static function error(): void {
		printf(
			'<div id="pcs-woocommerce-nope" class="notice notice-error is-dismissible"><p>%s</p></div>',
			esc_html__( 'Unknown error. Please contact the plugin developer.', 'gts-translation-order' ),
		);
	}
}
