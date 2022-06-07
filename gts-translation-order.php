<?php
/**
 * GTS Translation Order
 *
 * @package           gts/translation-order
 * @author            ALex Lavigin, KAGG Design
 * @license           GPL-2.0-or-later
 * @wordpress-plugin
 *
 * Plugin Name: GTS Translation Order
 * Plugin URI: https://www.gts-translation.com/
 * Description: Make a Translation Order to the GTS site.
 * Version: 0.0.1
 * Author: ALex Lavigin, KAGG Design
 * Author URI: https://kagg.eu/
 * License: GPL2
 *
 * Text Domain: gts-translation-order
 * Domain Path: /languages
 */

use GTS\TranslationOrder\Admin\AdminNotice;
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
const GTS_TRANSLATION_ORDER_FILE       = __FILE__; // Plugin main file.
const GTS_MINIMUM_PHP_REQUIRED_VERSION = '7.4'; // Minimum required PHP version.

require_once GTS_TRANSLATION_ORDER_PATH . '/vendor/autoload.php';

if ( ! Main::is_php_version_required() ) {
	add_action( 'admin_notices', [ AdminNotice::class, 'bad_php_version' ] );

	if ( ! is_plugin_active( plugin_basename( GTS_TRANSLATION_ORDER_FILE ) ) ) {
		return;
	}

	deactivate_plugins( plugin_basename( GTS_TRANSLATION_ORDER_FILE ) );

	// phpcs:disable WordPress.Security.NonceVerification.Recommended
	if ( isset( $_GET['activate'] ) ) {
		unset( $_GET['activate'] );
	}

	return;
}

new Main();
