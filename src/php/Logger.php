<?php
/**
 * Logger class file.
 *
 * @package gts/translation-order
 */

namespace GTS\TranslationOrder;

/**
 * Logger .
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
		if ( null !== $item ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_print_r
			$message .= ' ' . print_R( $item, true );
		}

		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
		error_log( current_time( 'mysql' ) . "\t" . $message );
	}
}
