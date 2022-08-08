<?php
/**
 * Translation Order page.
 *
 * @package gts/translation-order
 */

namespace GTS\TranslationOrder\Pages;

use GTS\TranslationOrder\Filter\PostFilter;
use GTS\TranslationOrder\Main;

/**
 * Order class file.
 */
class Order {

	/**
	 * Post filter class instance.
	 *
	 * @var PostFilter
	 */
	private $filter;

	/**
	 * Cart class instance.
	 *
	 * @var Cart
	 */
	private $cart;


	/**
	 * TranslationOrder class file.
	 *
	 * @param PostFilter $filter Post filter class instance.
	 * @param Cart       $cart   Cart class instance.
	 */
	public function __construct( PostFilter $filter, Cart $cart ) {
		$this->filter = $filter;
		$this->cart   = $cart;
	}

	/**
	 * Show template translation order.
	 *
	 * @return void
	 */
	public function show_translation_page() {
		?>
		<div class="container" id="gts-translation-order">
			<div class="row">
				<div class="col">
					<div class="wrap">
						<h1 class="wp-heading-inline"><?php esc_attr_e( 'Select Posts for Translation', 'gts-translation-order' ); ?></h1>
					</div>
				</div>
			</div>
			<form action="" id="filter-form" method="post">
				<div class="row">
					<div class="col-auto">
						<?php $this->filter->show_post_types_select(); ?>
					</div>
					<div class="col-auto">
						<?php $this->filter->show_search_field(); ?>
					</div>
					<div class="col-auto">
						<?php $this->filter->show_source_language(); ?>
					</div>
					<div class="col-auto">
						<?php
						$this->filter->show_target_language_select();
						$this->filter->show_target_language_popup();
						?>
					</div>
					<div class="col-auto">
						<input type="submit" name="gts_filter_submit" class="btn-sm btn btn-primary" value="Filter">
						<?php wp_nonce_field( 'gts_post_type_filter', 'gts_post_type_filter_nonce', false ); ?>
					</div>
					<div class="col-auto">
						<?php $this->show_add_to_cart_button(); ?>
					</div>
					<div class="col-auto">
						<?php $this->show_mini_cart(); ?>
					</div>
				</div>
			</form>
			<div class="row">
				<div class="col">
					<table class="table table-striped table-hover">
						<thead class="table-group-divider"><?php $this->show_column_titles(); ?></thead>
						<tbody class="table-group-divider"><?php $this->filter->show_table(); ?></tbody>
						<tfoot class="table-group-divider"><?php $this->show_column_titles(); ?></tfoot>
						<caption class="table-group-divider"><?php $this->show_add_to_cart_button(); ?></caption>
					</table>
					<?php
					if ( $this->filter->count_posts > PostFilter::OUTPUT_LIMIT ) {
						$this->filter->pagination->show();
					}
					?>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Show column titles.
	 *
	 * @return void
	 */
	private function show_column_titles() {
		static $count = 0;

		$positions = [
			'header',
			'footer',
		];

		$count = min( count( $positions ) - 1, $count );
		$id    = 'gts_to_all_page_' . $positions[ $count ];

		?>
		<tr>
			<th scope="col">
				<label for="<?php echo esc_attr( $id ); ?>"></label>
				<input type="checkbox" name="gts_to_all_page" class="gts_to_all_page" id="<?php echo esc_attr( $id ); ?>">
			</th>
			<th scope="col">
				<?php esc_attr_e( 'Title', 'gts-translation-order' ); ?>
			</th>
			<th scope="col"><?php esc_attr_e( 'Type', 'gts-translation-order' ); ?></th>
			<th scope="col"><?php esc_attr_e( 'Status', 'gts-translation-order' ); ?></th>
			<th scope="col"><?php esc_attr_e( 'Word count', 'gts-translation-order' ); ?></th>
			<th scope="col"><?php esc_attr_e( 'Cost', 'gts-translation-order' ); ?></th>
		</tr>
		<?php

		$count ++;
	}

	/**
	 * Show add to cart button.
	 *
	 * @return void
	 */
	private function show_add_to_cart_button() {
		?>
		<button type="button" class="btn btn-primary add-bulk-to-cart btn-sm">
			<?php esc_attr_e( 'Add to Cart', 'gts-translation-order' ); ?>
		</button>
		<?php
	}

	/**
	 * Show mini cart.
	 *
	 * @return void
	 */
	private function show_mini_cart() {
		?>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=' . Main::GTS_SUB_MENU_CART_SLUG ) ); ?>">
			<button type="button" class="btn btn-secondary btn-sm">
				<span class="dashicons dashicons-cart"></span>
				<span><?php echo esc_html( $this->cart->get_count() ); ?></span>
				<span>&nbsp;&nbsp;&nbsp;$<?php echo number_format( $this->cart->get_total(), 2 ); ?></span>
			</button>
		</a>
		<?php
	}
}
