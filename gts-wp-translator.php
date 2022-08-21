<?php
/**
 * GTS WP Translator
 *
 * @package           gts/wp-translator
 * @author            ALex Lavyhin, KAGG Design
 * @license           GPL-2.0-or-later
 * @wordpress-plugin
 *
 * Plugin Name: GTS WP Translator
 * Plugin URI: https://www.gts-translation.com/
 * Description: Make a translation via GTS site.
 * Version: 2.0.0
 * Author: ALex Lavyhin, KAGG Design
 * Author URI: https://kagg.eu/
 * License: GPL2
 *
 * Text Domain: gts-wp-translator
 * Domain Path: /languages
 */

use GTS\WPTranslator\Main;

if ( ! defined( 'ABSPATH' ) ) {
	// @codeCoverageIgnoreStart
	exit;
	// @codeCoverageIgnoreEnd
}

if ( defined( 'GTS_WP_TRANSLATOR_VERSION' ) ) {
	return;
}

// Main constants.
const GTS_WP_TRANSLATOR_VERSION = '2.0.0'; // Plugin version.
const GTS_WP_TRANSLATOR_PATH    = __DIR__; // Plugin path.
const GTS_WP_TRANSLATOR_FILE    = __FILE__; // Plugin main file.
define( 'GTS_WP_TRANSLATOR_URL', untrailingslashit( plugin_dir_url( GTS_WP_TRANSLATOR_FILE ) ) ); // Plugin url.

require_once GTS_WP_TRANSLATOR_PATH . '/vendor/autoload.php';

new Main();
