<?php
/**
 * Plugin init class.
 *
 * @package GTS\GTSTranslationOrder
 */

namespace GTS\GTSTranslationOrder;

use GTS\GTSTranslationOrder\Filter\PostFilter;

/**
 * PluginInit class file.
 */
class PluginInit {

	public const GTS_MENU_SLUG = 'gts_translation_order';

	public const GTS_SUB_MENU_CART_SLUG = 'gts_translation_cart';

	/**
	 * PluginInit construct.
	 */
	public function __construct() {
		$this->init();

		new PostFilter();
	}

	/**
	 * Check php version.
	 *
	 * @return bool
	 * @noinspection ConstantCanBeUsedInspection
	 */
	public static function is_php_version(): bool {
		if ( version_compare( constant( 'GTS_MINIMUM_PHP_REQUIRED_VERSION' ), phpversion(), '>' ) ) {
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
		add_action( 'admin_enqueue_scripts', [ $this, 'add_admin_scripts' ] );
		add_action( 'admin_menu', [ $this, 'add_menu_page' ] );
	}

	/**
	 * Init Text Domain.
	 *
	 * @return void
	 */
	public function init_text_domain(): void {
		load_plugin_textdomain( 'gts_translation_order', false, TRANSLATION_ORDER_PATH . '/languages/' );
	}

	/**
	 * Add Script and Style to Admin panel.
	 *
	 * @param string $hook_suffix Top Level Page slug.
	 *
	 * @return void
	 */
	public function add_admin_scripts( string $hook_suffix ): void {

		if ( 'toplevel_page_gts_translation_order' === $hook_suffix ) {
			wp_enqueue_script( 'bootstrap', '//cdn.jsdelivr.net/npm/bootstrap@5.2.0-beta1/dist/js/bootstrap.bundle.min.js', [ 'jquery' ], '5.2.0', true );
			wp_enqueue_style( 'bootstrap', '//cdn.jsdelivr.net/npm/bootstrap@5.2.0-beta1/dist/css/bootstrap.min.css', '', '5.2.0' );
			wp_enqueue_style( 'bootstrap-icon', TRANSLATION_ORDER_URL . '/vendor/twbs/bootstrap-icons/font/bootstrap-icons.css', '', '1.8.0' );
			wp_enqueue_script( 'main', TRANSLATION_ORDER_URL . '/assets/js/admin/main.js', [ 'jquery' ], TRANSLATION_ORDER_VERSION, true );
		}

		wp_enqueue_style( 'admin-style', TRANSLATION_ORDER_URL . '/assets/css/admin/style.css', '', TRANSLATION_ORDER_VERSION );
	}

	/**
	 * Add admin menu item.
	 *
	 * @return void
	 */
	public function add_menu_page(): void {
		add_menu_page(
			__( 'Translation Order', 'gts_translation_order' ),
			__( 'Translation Order', 'gts_translation_order' ),
			'edit_others_posts',
			self::GTS_MENU_SLUG,
			[ $this, 'output_translation_page' ],
			TRANSLATION_ORDER_URL . '/assets/icons/language-solid.svg',
			30
		);

		add_submenu_page(
			self::GTS_MENU_SLUG,
			__( 'Translation Cart', 'gts_translation_order' ),
			__( 'Translation Cart', 'gts_translation_order' ),
			'edit_others_posts',
			self::GTS_SUB_MENU_CART_SLUG,
			[ $this, 'output_translation_cart' ]
		);

	}

	/**
	 * Output template translation order.
	 *
	 * @return void
	 */
	public function output_translation_page(): void {
		include TRANSLATION_ORDER_PATH . '/template/translation-order-page.php';
	}

	public function output_translation_cart(): void {
		include TRANSLATION_ORDER_PATH . '/template/translation-cart-page.php';
	}
}
