<?php
/**
 * Logger class file.
 *
 * @package gts/wp-translator
 */

namespace GTS\WPTranslator;

/**
 * Logger class.
 */
class Logger {

	/**
	 * Log message.
	 * Maybe dump an item.
	 *
	 * @param string $message Message.
	 * @param mixed  $item    Item.
	 *
	 * @return void
	 * @noinspection ForgottenDebugOutputInspection
	 */
	public static function log( $message, $item = null ) {
		if ( ! ( defined( 'WP_DEBUG' ) && WP_DEBUG ) ) {
			return;
		}

		if ( null !== $item ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_print_r
			$message .= ' ' . print_r( $item, true );
		}

		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
		error_log( 'GTS WP Translator:  ' . $message );
	}
}
