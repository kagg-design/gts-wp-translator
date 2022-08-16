<?php
/**
 * Translation Order page.
 *
 * @package gts/translation-order
 */

namespace GTS\TranslationOrder\Pages;

use GTS\TranslationOrder\Cookie;
use GTS\TranslationOrder\Cost;
use GTS\TranslationOrder\Filter\PostFilter;
use GTS\TranslationOrder\Main;
use GTS\TranslationOrder\Pagination;
use wpdb;

/**
 * Order class file.
 */
class Order {

	/**
	 * Limit output posts.
	 */
	const OUTPUT_LIMIT = 20;

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
	 * Cost calculation.
	 *
	 * @var Cost $cost Cost class.
	 */
	private $cost;

	/**
	 * Pagination Class.
	 *
	 * @var Pagination $pagintion pagination.
	 */
	private $pagination;

	/**
	 * Status texts to show.
	 *
	 * @var array
	 */
	private $status_texts;

	/**
	 * Page number.
	 *
	 * @var int $page Page number.
	 */
	private $page;

	/**
	 * Count posts.
	 *
	 * @var int Post count.
	 */
	private $post_count;

	/**
	 * TranslationOrder class file.
	 *
	 * @param PostFilter $filter Post filter class instance.
	 * @param Cart       $cart   Cart class instance.
	 */
	public function __construct( PostFilter $filter, Cart $cart ) {
		$this->filter = $filter;
		$this->cart   = $cart;
		$this->cost   = new Cost();

		$this->status_texts = [
			Main::ORDER_STATUS_SENT => __( 'Out for translation', 'gts-translation-order' ),
		];

		$paging = filter_input( INPUT_GET, 'paging', FILTER_VALIDATE_INT );

		$this->page = $paging ?: 1;
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
			<?php $this->filter->show_form(); ?>
			<div class="row">
				<div class="col">
					<table class="table table-striped table-hover">
						<thead class="table-group-divider"><?php $this->show_column_titles(); ?></thead>
						<tbody class="table-group-divider"><?php $this->show_table(); ?></tbody>
						<tfoot class="table-group-divider"><?php $this->show_column_titles(); ?></tfoot>
						<caption class="table-group-divider"><?php $this->cart->show_add_to_cart_button(); ?></caption>
					</table>
					<?php
					if ( $this->post_count > self::OUTPUT_LIMIT ) {
						$this->pagination->show();
					}
					?>
				</div>
			</div>
		</div>
		<?php
		$this->filter->show_target_language_popup();
	}

	/**
	 * Show post table to translate.
	 *
	 * @return void
	 */
	private function show_table() {
		$filter        = Cookie::get_filter_cookie();
		$cart_post_ids = Cookie::get_cart_cookie();

		if ( ! isset( $filter->post_type ) ) {
			$filter = (object) [
				'post_type' => 'null',
				'search'    => '',
				'source'    => '',
				'target'    => [],

			];
		}

		$limit            = self::OUTPUT_LIMIT;
		$post_info        = $this->get_posts_by_post_type( $filter->post_type, $filter->search, ( $this->page - 1 ) * $limit, $limit );
		$curr_page_url    = isset( $_SERVER['QUERY_STRING'] ) ?
			'admin.php?' . filter_var( wp_unslash( $_SERVER['QUERY_STRING'] ), FILTER_SANITIZE_STRING ) :
			'';
		$this->post_count = 0;
		$posts            = $post_info['posts'];
		$count            = $post_info['rows_found'];
		$post_ids         = wp_list_pluck( $posts, 'ID' );
		$post_ids         = array_map( 'intval', $post_ids );
		$posts_statuses   = $this->get_post_statuses( $post_ids );

		if ( $count > 0 ) {
			$p = new Pagination();
			$p->items( $count );
			$p->limit( $limit ); // Limit entries per page.
			$p->target( $curr_page_url );
			$p->currentPage( $this->page ); // Gets and validates the current page.
			$p->parameterName( 'paging' );
			$p->adjacents( 1 ); // No. of page away from the current page.
			$p->calculate(); // Calculates what to show.

			$this->pagination = $p;
			$this->post_count = $count;
		} else {
			?>
			<tr>
				<td colspan="6">
					<?php esc_html_e( 'No posts found.', 'gts-translation-order' ); ?>
				</td>
			</tr>
			<?php
		}

		foreach ( $posts as $post ) {
			$title = $post->post_title;
			$title = $title ?: __( '(no title)', 'gts-translation-order' );
			$id    = "gts_to_translate-$post->ID";
			$name  = "gts_to_translate[$post->ID]";
			$price = 0;

			if ( ! empty( $filter->source ) && ! empty( $filter->target ) ) {
				$price = $this->cost->price_for_post( $filter->source, $filter->target, $post->ID );
			}

			$in_cart      = in_array( $post->ID, $cart_post_ids, true );
			$status       = isset( $posts_statuses[ $post->ID ] ) ? $posts_statuses[ $post->ID ] : '';
			$status_class = ( $status || $in_cart ) ? 'text-bg-primary' : 'bg-secondary';
			$status_text  = isset( $this->status_texts[ $status ] )
				? $this->status_texts[ $status ] : __( 'Not translated', 'gts-translation-order' );
			$status_text  = $in_cart ? __( 'In cart', 'gts-translation-order' ) : $status_text;
			$has_checkbox = ! $status && ! $in_cart;
			$tr_class     = $in_cart ? 'table-primary' : '';
			$word_count   = $this->cost->get_word_count( $post->ID );
			?>
			<tr class="<?php echo esc_attr( $tr_class ); ?>">
				<th scope="row">
					<?php if ( $has_checkbox ) { ?>
					<label for="<?php echo esc_attr( $id ); ?>" class="hidden"></label>
					<input
							type="checkbox"
							data-id="<?php echo esc_attr( $post->ID ); ?>"
							id="<?php echo esc_attr( $id ); ?>"
							name="<?php echo esc_attr( $name ); ?>">
				</th>
				<?php } ?>
				<td><?php echo esc_html( $title ); ?></td>
				<td><?php echo esc_html( $post->post_type ); ?></td>
				<td>
					<span class="badge <?php echo esc_attr( $status_class ); ?>">
						<?php echo esc_html( $status_text ); ?>
					</span>
				</td>
				<td><?php echo esc_html( $word_count ); ?></td>
				<td>$<?php echo esc_html( $price ); ?> </td>
			</tr>
			<?php
		}
	}

	/**
	 * Get posts by post type.
	 *
	 * @param string|null $post_type Post type.
	 * @param string|null $search    Search string.
	 * @param int         $offset    Offset.
	 * @param int         $limit     Limit.
	 *
	 * @return array
	 */
	private function get_posts_by_post_type( $post_type = null, $search = null, $offset = 0, $limit = 20 ) {
		global $wpdb;

		$post_types = [ $post_type ];

		if ( null === $post_type || 'null' === $post_type ) {
			$post_types = $this->filter->get_post_types();
		}

		$slq_post_type = $this->prepare_in( $post_types );

		$sql = "SELECT SQL_CALC_FOUND_ROWS po.ID, po.post_title, po.post_type 
				FROM `$wpdb->posts` po 
				WHERE `post_type` IN ( $slq_post_type ) 
				AND `post_status` = 'publish'";

		if ( $search ) {
			$sql .= "AND `post_title` LIKE '%" . $wpdb->esc_like( $search ) . "%'";
		}

		$sql .= 'LIMIT %d, %d';

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared
		$result = $wpdb->get_results(
			$wpdb->prepare(
				$sql,
				esc_sql( $offset ),
				esc_sql( $limit )
			)
		);

		$rows_found = $wpdb->get_results( 'SELECT FOUND_ROWS();', ARRAY_N );
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared

		if ( ! $result ) {
			return [
				'posts'      => [],
				'rows_found' => 0,
			];
		}

		return [
			'posts'      => $result,
			'rows_found' => $rows_found[0][0],
		];
	}

	/**
	 * Get all post ids in orders.
	 *
	 * @param int[] $post_ids Post ids.
	 *
	 * @return array
	 */
	private function get_post_statuses( $post_ids ) {
		global $wpdb;

		if ( ! $post_ids ) {
			return [];
		}

		$table_name = Main::ORDER_TABLE_NAME;
		$in         = $this->prepare_in( $post_ids, '%d' );

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$results = $wpdb->get_results(
			"SELECT post_id, status FROM $wpdb->prefix$table_name
					WHERE id IN(
					    SELECT MAX(id) FROM $wpdb->prefix$table_name
					    WHERE post_id IN ( $in )
					    GROUP BY post_id
					    )"
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		if ( ! $results ) {
			return [];
		}

		$statuses = [];

		foreach ( $results as $result ) {
			$statuses[ $result->post_id ] = $result->status;
		}

		return $statuses;
	}

	/**
	 * Changes array of items into string of items, separated by comma and sql-escaped.
	 *
	 * @see https://coderwall.com/p/zepnaw
	 * @global wpdb       $wpdb
	 *
	 * @param mixed|array $items  item(s) to be joined into string.
	 * @param string      $format %s or %d.
	 *
	 * @return string Items separated by comma and sql-escaped
	 */
	private function prepare_in( $items, $format = '%s' ) {
		global $wpdb;

		$items    = (array) $items;
		$how_many = count( $items );

		if ( $how_many > 0 ) {
			$placeholders    = array_fill( 0, $how_many, $format );
			$prepared_format = implode( ',', $placeholders );
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			$prepared_in = $wpdb->prepare( $prepared_format, $items );
		} else {
			$prepared_in = '';
		}

		return $prepared_in;
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
}
