<?php
/**
 * Main class file.
 *
 * @package gts/wp-translator
 */

namespace GTS\WPTranslator;

use GTS\WPTranslator\Filter\PostFilter;
use GTS\WPTranslator\Pages\Cart;
use GTS\WPTranslator\Pages\Order;

/**
 * Plugin main class.
 */
class Main {

	/**
	 * Add to cart action name.
	 */
	const ADD_TO_CART_ACTION = 'gts-wp-translator-add-to-cart';

	/**
	 * Delete from cart action name.
	 */
	const DELETE_FROM_CART_ACTION = 'gts-wp-translator-delete-from-cart';

	/**
	 * Sent to translation action name.
	 */
	const SEND_TO_TRANSLATION_ACTION = 'gts-wp-translator-send-to-translation';

	/**
	 * Update price action name.
	 */
	const UPDATE_PRICE_ACTION = 'gts-wp-translator-update-price';

	/**
	 * Top menu slug.
	 */
	const GTS_MENU_SLUG = 'gts-wp-translator';

	/**
	 * Sub menu cart slug.
	 */
	const GTS_SUB_MENU_CART_SLUG = 'gts-wp-translator-cart';

	/**
	 * Page Menu slugs.
	 */
	const GTS_PAGES_MENU_SLUGS = [
		'toplevel_page_' . self::GTS_MENU_SLUG,
		'gts-wp-translator_page_' . self::GTS_SUB_MENU_CART_SLUG,
	];

	/**
	 * Order status sent.
	 */
	const ORDER_STATUS_SENT = 'Sent';

	/**
	 * Order table created option.
	 */
	const ORDER_TABLE_OPTION = 'gts_wp_translator_table_created';

	/**
	 * Order table name.
	 */
	const ORDER_TABLE_NAME = 'gts_to_orders';

	/**
	 * API claas instance.
	 *
	 * @var API
	 */
	private $api;

	/**
	 * Order class instance.
	 *
	 * @var Order
	 */
	private $translation_order;

	/**
	 * Cart class instance.
	 *
	 * @var Cart
	 */
	private $translation_cart;

	/**
	 * Main construct.
	 */
	public function __construct() {
		$this->hooks();
	}

	/**
	 * Init plugin.
	 *
	 * @return void
	 */
	public function init() {
		/*
		 * Prevent loading of the plugins code on REST request.
		 * This allows activation of the plugin on the GTS server for test purposes.
		 */

		$request_uri = isset( $_SERVER['REQUEST_URI'] ) ?
			esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ) :
			'';

		if ( 0 === strpos( $request_uri, '/' . rest_get_url_prefix() ) ) {
			return;
		}

		$this->api               = new API();
		$this->translation_cart  = new Cart();
		$filter                  = new PostFilter( $this->translation_cart );
		$this->translation_order = new Order( $filter, $this->translation_cart );
	}

	/**
	 * Init Hooks.
	 *
	 * @return void
	 */
	private function hooks() {
		add_action( 'plugins_loaded', [ $this, 'init_text_domain' ] );
		add_action( 'plugins_loaded', [ $this, 'init' ], 20 );
		add_action( 'init', [ $this, 'create_order_table' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'admin_scripts' ] );
		add_action( 'admin_menu', [ $this, 'menu_page' ] );

		register_deactivation_hook( GTS_WP_TRANSLATOR_FILE, [ $this, 'deactivate' ] );
	}

	/**
	 * Init text domain.
	 *
	 * @return void
	 */
	public function init_text_domain() {
		load_plugin_textdomain( 'gts-wp-translator', false, GTS_WP_TRANSLATOR_PATH . '/languages/' );
	}

	/**
	 * Add script and style to admin panel.
	 *
	 * @param string $hook_suffix Top Level Page slug.
	 *
	 * @return void
	 */
	public function admin_scripts( $hook_suffix ) {
		if ( in_array( $hook_suffix, self::GTS_PAGES_MENU_SLUGS, true ) ) {
			wp_enqueue_style( 'bootstrap', '//cdn.jsdelivr.net/npm/bootstrap@5.2.0-beta1/dist/css/bootstrap.min.css', '', '5.2.0' );
			wp_enqueue_script( 'bootstrap', '//cdn.jsdelivr.net/npm/bootstrap@5.2.0-beta1/dist/js/bootstrap.bundle.min.js', [ 'jquery' ], '5.2.0', true );
			wp_enqueue_script( 'sweetalert2', '//cdn.jsdelivr.net/npm/sweetalert2@11', [ 'jquery' ], '2.11.0', true );
		}

		$min = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';

		wp_enqueue_style( 'gts-wp-translator-admin-style', GTS_WP_TRANSLATOR_URL . "/assets/css/admin/style$min.css", '', GTS_WP_TRANSLATOR_VERSION );
		wp_enqueue_script( 'gts-wp-translator-main', GTS_WP_TRANSLATOR_URL . "/assets/js/admin/main$min.js", [ 'jquery' ], GTS_WP_TRANSLATOR_VERSION, true );

		$site = API::GTS_SITE;

		if ( defined( 'GTS_WP_TRANSLATOR_DEBUG_SITE' ) && GTS_WP_TRANSLATOR_DEBUG_SITE ) {
			$site = GTS_WP_TRANSLATOR_DEBUG_SITE;
		}

		wp_localize_script(
			'gts-wp-translator-main',
			'GTSWPTranslatorObject',
			[
				'url'                     => admin_url( 'admin-ajax.php' ),
				'addToCartAction'         => self::ADD_TO_CART_ACTION,
				'addToCartNonce'          => wp_create_nonce( self::ADD_TO_CART_ACTION ),
				'deleteFromCartAction'    => self::DELETE_FROM_CART_ACTION,
				'deleteFromCartNonce'     => wp_create_nonce( self::DELETE_FROM_CART_ACTION ),
				'sendToTranslationAction' => self::SEND_TO_TRANSLATION_ACTION,
				'sendToTranslationNonce'  => wp_create_nonce( self::SEND_TO_TRANSLATION_ACTION ),
				'addToCartText'           => __( 'Adding item to cart', 'gts-wp-translator' ),
				'sendOrderText'           => __( 'Order is being sent', 'gts-wp-translator' ),
				'deleteFromCartText'      => __( 'Removing item from cart', 'gts-wp-translator' ),
				'cartCookieName'          => Cookie::CART_COOKIE_NAME,
				'updatePrice'             => self::UPDATE_PRICE_ACTION,
				'updatePriceNonce'        => wp_create_nonce( self::UPDATE_PRICE_ACTION ),
				'emptySource'             => __( 'Please fill in the Source language field', 'gts-wp-translator' ),
				'emptyTarget'             => __( 'Please fill in the Target languages field', 'gts-wp-translator' ),
				'emptyList'               => __( 'Please select posts for adding to cart', 'gts-wp-translator' ),
				'sendOrderTitle'          => __( 'You have been successfully registered on the site', 'gts-wp-translator' ),
				'sendOrderTextConfirm'    => __( 'We have sent access information to your email.', 'gts-wp-translator' ),
				'sendOrderTextButton'     => __( 'Proceed to payment', 'gts-wp-translator' ),
				'sendCancelButton'        => __( 'Back to selection', 'gts-wp-translator' ),
				'paymentLink'             => $site . '/confirm/?fqid=',
				'selectPostsLink'         => admin_url( 'admin.php?page=' . self::GTS_MENU_SLUG ),
				'cartLink'                => admin_url( 'admin.php?page=' . self::GTS_SUB_MENU_CART_SLUG ),
			]
		);
	}

	/**
	 * Add admin menu items.
	 *
	 * @return void
	 */
	public function menu_page() {
		add_menu_page(
			__( 'GTS WP Translator', 'gts-wp-translator' ),
			__( 'GTS WP Translator', 'gts-wp-translator' ),
			'edit_others_posts',
			self::GTS_MENU_SLUG,
			[ $this->translation_order, 'show_translation_page' ],
			GTS_WP_TRANSLATOR_URL . '/assets/icons/language-solid.svg',
			27
		);

		add_submenu_page(
			self::GTS_MENU_SLUG,
			__( 'Translation Cart', 'gts-wp-translator' ),
			__( 'Translation Cart', 'gts-wp-translator' ),
			'edit_others_posts',
			self::GTS_SUB_MENU_CART_SLUG,
			[ $this->translation_cart, 'show_translation_cart' ]
		);
	}

	/**
	 * Create translation order table.
	 *
	 * @return void
	 */
	public function create_order_table() {
		global $wpdb;

		if ( get_option( self::ORDER_TABLE_OPTION ) ) {
			return;
		}

		$table_name = self::ORDER_TABLE_NAME;

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared
		$sql = "CREATE TABLE IF NOT EXISTS `$wpdb->prefix$table_name`  
				(
				    `id`               BIGINT AUTO_INCREMENT PRIMARY KEY,
				    `order_id`         INT             NULL,
				    `post_id`          BIGINT UNSIGNED NULL,
				    `status`           VARCHAR(16)     NULL,
				    `total`            DOUBLE          NULL,
				    `date`             DATE            NULL,
				    `source_language`  VARCHAR(200)    NULL,
				    `target_languages` MEDIUMTEXT      NULL,
				    `industry`         VARCHAR(100)    NULL,
				    INDEX (order_id),
				    INDEX (post_id)
				)";

		$wpdb->query( $sql );
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared

		update_option( self::ORDER_TABLE_OPTION, true );
	}

	/**
	 * Plugin deactivation hook.
	 *
	 * @return void
	 */
	public function deactivate() {
		$this->api->delete_transients();
	}
}
