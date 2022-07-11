<?php
/**
 * Plugin init class.
 *
 * @package gts/translation-order
 */

namespace GTS\TranslationOrder;

use GTS\TranslationOrder\Filter\PostFilter;
use GTS\TranslationOrder\Pages\Cart;
use GTS\TranslationOrder\Pages\Order;

/**
 * PluginInit class file.
 */
class Main {

	/**
	 * Add to cart action name.
	 */
	const ADD_TO_CART_ACTION = 'gts-to-add-to-cart';

	/**
	 * Delete from cart action name.
	 */
	const DELETE_FROM_CART_ACTION = 'gts-to-delete-from-cart';

	/**
	 * Sent to translation action name.
	 */
	const SEND_TO_TRANSLATION_ACTION = 'gts-to-send-to-translation';

	/**
	 * Update price action name.
	 */
	const UPDATE_PRICE_ACTION = 'gts-to-update-price';

	/**
	 * Top menu slug.
	 */
	const GTS_MENU_SLUG = 'gts_translation_order';

	/**
	 * Sub menu cart slug.
	 */
	const GTS_SUB_MENU_CART_SLUG = 'gts_translation_cart';

	/**
	 * Sub menu token slug
	 */
	const GTS_SUB_MENU_TOKEN_SLUG = 'gts_translation_token';

	/**
	 * Page Menu slugs.
	 */
	const GTS_PAGES_MENU_SLUGS = [
		'toplevel_page_' . self::GTS_MENU_SLUG,
		'gts-wp-translator_page_' . self::GTS_SUB_MENU_CART_SLUG,
		'gts-wp-translator_page_' . self::GTS_SUB_MENU_TOKEN_SLUG,
	];

	/**
	 * Order status sent.
	 */
	const ORDER_STATUS_SENT = 'Sent';

	/**
	 * Order table created option.
	 */
	const ORDER_TABLE_OPTION = 'gts_order_table_created';

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
		$filter                  = new PostFilter();
		$this->translation_order = new Order( $filter );
		$this->translation_cart  = new Cart();
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

		register_deactivation_hook( GTS_TRANSLATION_ORDER_FILE, [ $this, 'deactivate' ] );
	}

	/**
	 * Init text domain.
	 *
	 * @return void
	 */
	public function init_text_domain() {
		load_plugin_textdomain( 'gts-translation-order', false, GTS_TRANSLATION_ORDER_PATH . '/languages/' );
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
			wp_enqueue_script( 'bootstrap', '//cdn.jsdelivr.net/npm/bootstrap@5.2.0-beta1/dist/js/bootstrap.bundle.min.js', [ 'jquery' ], '5.2.0', true );
			wp_enqueue_script( 'sweetalert2', '//cdn.jsdelivr.net/npm/sweetalert2@11', [ 'jquery' ], '2.11.0', true );
			wp_enqueue_style( 'bootstrap', '//cdn.jsdelivr.net/npm/bootstrap@5.2.0-beta1/dist/css/bootstrap.min.css', '', '5.2.0' );
			wp_enqueue_style( 'bootstrap-icon', GTS_TRANSLATION_ORDER_URL . '/vendor/twbs/bootstrap-icons/font/bootstrap-icons.css', '', '1.8.0' );
		}

		wp_enqueue_style( 'gts-to-admin-style', GTS_TRANSLATION_ORDER_URL . '/assets/css/admin/style.css', '', GTS_TRANSLATION_ORDER_VERSION );
		wp_enqueue_script( 'gts-to-main', GTS_TRANSLATION_ORDER_URL . '/assets/js/admin/main.js', [ 'jquery' ], GTS_TRANSLATION_ORDER_VERSION, true );

		wp_localize_script(
			'gts-to-main',
			'GTSTranslationOrderObject',
			[
				'url'                     => admin_url( 'admin-ajax.php' ),
				'addToCartAction'         => self::ADD_TO_CART_ACTION,
				'addToCartNonce'          => wp_create_nonce( self::ADD_TO_CART_ACTION ),
				'deleteFromCartAction'    => self::DELETE_FROM_CART_ACTION,
				'deleteFromCartNonce'     => wp_create_nonce( self::DELETE_FROM_CART_ACTION ),
				'sendToTranslationAction' => self::SEND_TO_TRANSLATION_ACTION,
				'sendToTranslationNonce'  => wp_create_nonce( self::SEND_TO_TRANSLATION_ACTION ),
				'addToCartText'           => __( 'Adding item to cart', 'gts-translation-order' ),
				'sendOrderText'           => __( 'Order is being sent', 'gts-translation-order' ),
				'deleteFromCartText'      => __( 'Removing item from cart', 'gts-translation-order' ),
				'createOrder'             => __( 'Order has been created and we will contact you soon', 'gts-translation-order' ),
				'cartCookieName'          => Cookie::CART_COOKIE_NAME,
				'updatePrice'             => self::UPDATE_PRICE_ACTION,
				'updatePriceNonce'        => wp_create_nonce( self::UPDATE_PRICE_ACTION ),
				'emptyTarget'             => __( 'Fill in the fields Target languages', 'gts-translation-order' ),
				'emptySource'             => __( 'Fill in the fields Source language', 'gts-translation-order' ),
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
			__( 'GTS WP Translator', 'gts-translation-order' ),
			__( 'GTS WP Translator', 'gts-translation-order' ),
			'edit_others_posts',
			self::GTS_MENU_SLUG,
			[ $this->translation_order, 'show_translation_page' ],
			GTS_TRANSLATION_ORDER_URL . '/assets/icons/language-solid.svg',
			27
		);

		add_submenu_page(
			self::GTS_MENU_SLUG,
			__( 'Translation Cart', 'gts-translation-order' ),
			__( 'Translation Cart', 'gts-translation-order' ),
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
		$sql = "CREATE TABLE `$wpdb->prefix$table_name`  
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
