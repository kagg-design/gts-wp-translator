<?php
/**
 * Plugin Name: GTS Translation Order
 * Plugin URI: https://kagg.eu/
 * Description: GTS Translation Order
 * Version: 0.0.1
 * Author: alexlavigin, kaggdesign
 * Author URI: https://kagg.eu/
 * License: GPL2
 */

// Main constants.
const TRANSLATION_ORDER_VERSION  = '0.0.1'; // Plugin version.
const TRANSLATION_ORDER_PATH     = __DIR__; // Plugin path.
define( 'TRANSLATION_ORDER_URL', untrailingslashit( plugin_dir_url( __FILE__ ) ) ); // Plugin url.
const TRANSLATION_ORDER_FILE = __FILE__; // Plugin main file.

require_once TRANSLATION_ORDER_PATH . '/vendor/autoload.php';

