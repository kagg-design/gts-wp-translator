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
use GTS\TranslationOrder\Pages\Token;

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
		'translation-order_page_' . self::GTS_SUB_MENU_CART_SLUG,
		'translation-order_page_' . self::GTS_SUB_MENU_TOKEN_SLUG,
	];

	/**
	 * Translation Order page.
	 *
	 * @var Order Translation Order.
	 */
	public $translation_order;

	/**
	 * Cart class.
	 *
	 * @var Cart $translation_cart Cart class.
	 */
	public $translation_cart;

	/**
	 * Filter class.
	 *
	 * @var PostFilter
	 */
	private $filter;

	/**
	 * Token class.
	 *
	 * @var Token $translation_token Token class
	 */
	public $translation_token;

	/**
	 * PluginInit construct.
	 */
	public function __construct() {
		$this->filter = new PostFilter();

		$this->init();
	}

	/**
	 * Init Hooks.
	 *
	 * @return void
	 */
	public function init() {
		add_action( 'plugins_loaded', [ $this, 'init_text_domain' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'admin_scripts' ] );
		add_action( 'admin_menu', [ $this, 'menu_page' ] );
		add_action( 'init', [ $this, 'create_order_table' ] );

		$this->translation_order = new Order( $this->filter );
		$this->translation_cart  = new Cart();
		$this->translation_token = new Token();
	}

	/**
	 * Init Text Domain.
	 *
	 * @return void
	 */
	public function init_text_domain() {
		load_plugin_textdomain( 'gts-translation-order', false, GTS_TRANSLATION_ORDER_PATH . '/languages/' );
	}

	/**
	 * Add Script and Style to Admin panel.
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
				'url'                  => admin_url( 'admin-ajax.php' ),
				'addToCartAction'      => self::ADD_TO_CART_ACTION,
				'addToCartNonce'       => wp_create_nonce( self::ADD_TO_CART_ACTION ),
				'deleteFromCartAction' => self::DELETE_FROM_CART_ACTION,
				'deleteFromCartNonce'  => wp_create_nonce( self::DELETE_FROM_CART_ACTION ),
				'addToCartText'        => __( 'Add item to cart', 'gts-translation-order' ),
				'deleteFromCartText'   => __( 'Remove item from cart', 'gts-translation-order' ),
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
			__( 'Translation Order', 'gts-translation-order' ),
			__( 'Translation Order', 'gts-translation-order' ),
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

		add_submenu_page(
			self::GTS_MENU_SLUG,
			__( 'Token', 'gts-translation-order' ),
			__( 'Token', 'gts-translation-order' ),
			'edit_others_posts',
			self::GTS_SUB_MENU_TOKEN_SLUG,
			[ $this->translation_token, 'show_token_page' ]
		);

	}

	/**
	 * Create translation order table.
	 *
	 * @return void
	 */
	public function create_order_table() {
		global $wpdb;

		$table = get_option( 'gts_order_table_create' );

		if ( ! $table ) {

			// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery
			// phpcs:disable WordPress.DB.DirectDatabaseQuery.NoCaching
			$sql = "CREATE TABLE `{$wpdb->prefix}gts_translation_order`  
					(
					    `id` BIGINT NOT NULL AUTO_INCREMENT , 
					    `posts_id` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL , 
					    `status` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL , 
					    `total_cost` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL , 
					    `date_send` DATE NOT NULL DEFAULT CURRENT_TIMESTAMP , 
					    `date_response` DATE NOT NULL , 
					    `site_language` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL , 
					    `target_languages` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL , 
					    `industry` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL , 
					    PRIMARY KEY (`id`)
					);";

			require_once ABSPATH . 'wp-admin/includes/upgrade.php';

			dbDelta( $sql );

			// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery
			// phpcs:enable WordPress.DB.DirectDatabaseQuery.NoCaching

			update_option( 'gts_order_table_create', true );
		}
	}
}
