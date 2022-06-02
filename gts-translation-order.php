<?php
/**
 * Plugin Name: GTS Translation Order
 * Plugin URI: https://kagg.eu/
 * Description: GTS Translation Order
 * Version: 0.0.1
 * Author: alexlavigin, kaggdesign
 * Author URI: https://kagg.eu/
 * License: GPL2
 *
 * Text Domain: gts_translation_order
 * Domain Path: /languages
 *
 * @package  GTS\GTSTranslationOrder
 */

use GTS\GTSTranslationOrder\PluginInit;

// Main constants.
const TRANSLATION_ORDER_VERSION = '0.0.1'; // Plugin version.
const TRANSLATION_ORDER_PATH    = __DIR__; // Plugin path.
define( 'TRANSLATION_ORDER_URL', untrailingslashit( plugin_dir_url( __FILE__ ) ) ); // Plugin url.
const TRANSLATION_ORDER_FILE           = __FILE__; // Plugin main file.
const GTS_MINIMUM_PHP_REQUIRED_VERSION = '7.4'; // Plugin main file.

require_once TRANSLATION_ORDER_PATH . '/vendor/autoload.php';

if ( ! PluginInit::is_php_version() ) {

	add_action( 'admin_notices', 'GTS\GTSTranslationOrder\Admin\AdminNotice::php_version_nope' );

	if ( is_plugin_active( plugin_basename( constant( 'PCS_FILE' ) ) ) ) {
		deactivate_plugins( plugin_basename( constant( 'PCS_FILE' ) ) );
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		if ( isset( $_GET['activate'] ) ) {
			unset( $_GET['activate'] );
		}
	}

	return;
}

new PluginInit();
