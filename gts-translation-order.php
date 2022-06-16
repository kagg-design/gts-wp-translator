<?php
/**
 * GTS Translation Order
 *
 * @package           gts/translation-order
 * @author            ALex Lavyhin, KAGG Design
 * @license           GPL-2.0-or-later
 * @wordpress-plugin
 *
 * Plugin Name: GTS Translation Order
 * Plugin URI: https://www.gts-translation.com/
 * Description: Make a Translation Order to the GTS site.
 * Version: 0.0.1
 * Author: ALex Lavyhin, KAGG Design
 * Author URI: https://kagg.eu/
 * License: GPL2
 *
 * Text Domain: gts-translation-order
 * Domain Path: /languages
 */

use GTS\TranslationOrder\Main;

if ( ! defined( 'ABSPATH' ) ) {
	// @codeCoverageIgnoreStart
	exit;
	// @codeCoverageIgnoreEnd
}

if ( defined( 'GTS_TRANSLATION_ORDER_VERSION' ) ) {
	return;
}

// Main constants.
const GTS_TRANSLATION_ORDER_VERSION = '0.0.1'; // Plugin version.
const GTS_TRANSLATION_ORDER_PATH    = __DIR__; // Plugin path.
define( 'GTS_TRANSLATION_ORDER_URL', untrailingslashit( plugin_dir_url( __FILE__ ) ) ); // Plugin url.

/**
 * Debug mode.
 */
const GTS_REST_DEBUG = true;

const GTS_REST_DEBUG_URL = 'https://stages.i-wp-dev.com/wp-json/gts-translation-order/v1/';
const GTS_REST_URL       = 'https://www.gts-translation.com/wp-json/gts-translation-order/v1/';

require_once GTS_TRANSLATION_ORDER_PATH . '/vendor/autoload.php';

new Main();
