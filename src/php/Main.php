<?php
/**
 * Plugin init class.
 *
 * @package gts/translation-order
 */

namespace GTS\TranslationOrder;

/**
 * PluginInit class file.
 */
class Main {

	/**
	 * Top menu slug.
	 */
	public const GTS_MENU_SLUG = 'gts_translation_order';

	/**
	 * Sub menu slug.
	 */
	public const GTS_SUB_MENU_CART_SLUG = 'gts_translation_cart';

	private const GTS_PAGES_MENU_SLUGS = [
		'toplevel_page_' . self::GTS_MENU_SLUG,
		'translation-order_page_' . self::GTS_SUB_MENU_CART_SLUG,
	];

	/**
	 * PluginInit construct.
	 */
	public function __construct() {
		$this->init();
	}

	/**
	 * Check php version.
	 *
	 * @return bool
	 * @noinspection ConstantCanBeUsedInspection
	 */
	public static function is_php_version_required(): bool {
		if ( version_compare( phpversion(), GTS_MINIMUM_PHP_REQUIRED_VERSION, '<' ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Init Hooks.
	 *
	 * @return void
	 */
	public function init(): void {
		add_action( 'plugins_loaded', [ $this, 'init_text_domain' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'admin_scripts' ] );
		add_action( 'admin_menu', [ $this, 'menu_page' ] );
		add_action( 'init', [ $this, 'create_order_table' ] );
	}

	/**
	 * Init Text Domain.
	 *
	 * @return void
	 */
	public function init_text_domain(): void {
		load_plugin_textdomain( 'gts-translation-order', false, GTS_TRANSLATION_ORDER_PATH . '/languages/' );
	}

	/**
	 * Add Script and Style to Admin panel.
	 *
	 * @param string $hook_suffix Top Level Page slug.
	 *
	 * @return void
	 */
	public function admin_scripts( string $hook_suffix ): void {
		if ( in_array( $hook_suffix, self::GTS_PAGES_MENU_SLUGS, true ) ) {
			wp_enqueue_script( 'bootstrap', '//cdn.jsdelivr.net/npm/bootstrap@5.2.0-beta1/dist/js/bootstrap.bundle.min.js', [ 'jquery' ], '5.2.0', true );
			wp_enqueue_style( 'bootstrap', '//cdn.jsdelivr.net/npm/bootstrap@5.2.0-beta1/dist/css/bootstrap.min.css', '', '5.2.0' );
			wp_enqueue_style( 'bootstrap-icon', GTS_TRANSLATION_ORDER_URL . '/vendor/twbs/bootstrap-icons/font/bootstrap-icons.css', '', '1.8.0' );
			wp_enqueue_style( 'admin-style', GTS_TRANSLATION_ORDER_URL . '/assets/css/admin/style.css', '', GTS_TRANSLATION_ORDER_VERSION );
		}

		wp_enqueue_script( 'main', GTS_TRANSLATION_ORDER_URL . '/assets/js/admin/main.js', [ 'jquery' ], GTS_TRANSLATION_ORDER_VERSION, true );
	}

	/**
	 * Add admin menu items.
	 *
	 * @return void
	 */
	public function menu_page(): void {
		add_menu_page(
			__( 'Translation Order', 'gts-translation-order' ),
			__( 'Translation Order', 'gts-translation-order' ),
			'edit_others_posts',
			self::GTS_MENU_SLUG,
			[ $this, 'show_translation_page' ],
			GTS_TRANSLATION_ORDER_URL . '/assets/icons/language-solid.svg',
			27
		);

		add_submenu_page(
			self::GTS_MENU_SLUG,
			__( 'Translation Cart', 'gts-translation-order' ),
			__( 'Translation Cart', 'gts-translation-order' ),
			'edit_others_posts',
			self::GTS_SUB_MENU_CART_SLUG,
			[ $this, 'show_translation_cart' ]
		);

	}

	/**
	 * Show template translation order.
	 *
	 * @return void
	 */
	public function show_translation_page(): void {
		include GTS_TRANSLATION_ORDER_PATH . '/template/translation-order-page.php';
	}

	/**
	 * Show template translation cart.
	 *
	 * @return void
	 */
	public function show_translation_cart(): void {
		include GTS_TRANSLATION_ORDER_PATH . '/template/translation-cart-page.php';
	}

	/**
	 * Create translation order table.
	 *
	 * @return void
	 */
	public function create_order_table(): void {
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
