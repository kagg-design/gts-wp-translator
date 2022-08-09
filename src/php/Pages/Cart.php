<?php
/**
 * Translation cart page.
 *
 * @package gts/translation-order
 */

namespace GTS\TranslationOrder\Pages;

use GTS\TranslationOrder\Cookie;
use GTS\TranslationOrder\Cost;
use GTS\TranslationOrder\API;
use GTS\TranslationOrder\Export;
use GTS\TranslationOrder\Main;
use stdClass;

/**
 * TranslationCart class file.
 */
class Cart {

	/**
	 * Languages list.
	 *
	 * @var object|stdClass|null
	 */
	public $language_list;

	/**
	 * Cost calculation.
	 *
	 * @var Cost $cost Cost class.
	 */
	private $cost;

	/**
	 * Post id to query.
	 *
	 * @var int
	 */
	private $id;

	/**
	 * Api GTS Translation.
	 *
	 * @var API
	 */
	private $api;

	/**
	 * TranslationOrder class file.
	 */
	public function __construct() {
		$this->init();

		$this->api           = new API();
		$this->language_list = $this->api->get_language_list();
		$this->cost          = new Cost();
	}

	/**
	 * Init hooks.
	 *
	 * @return void
	 */
	public function init() {
		add_action( 'wp_ajax_' . Main::ADD_TO_CART_ACTION, [ $this, 'add_to_cart' ] );
		add_action( 'wp_ajax_' . Main::DELETE_FROM_CART_ACTION, [ $this, 'delete_from_cart' ] );
		add_action( 'wp_ajax_' . Main::SEND_TO_TRANSLATION_ACTION, [ $this, 'send_to_translation' ] );
		add_action( 'wp_ajax_' . Main::UPDATE_PRICE_ACTION, [ $this, 'update_price' ] );
	}

	/**
	 * Show translation cart.
	 *
	 * @return void
	 */
	public function show_translation_cart() {
		$items_count = count( Cookie::get_cart_cookie() );

		?>
		<div class="container">
			<div class="row">
				<div class="col-auto">
					<div class="wrap">
						<h1 class="wp-heading-inline"><?php esc_attr_e( 'Translation Cart', 'gts-translation-order' ); ?></h1>
					</div>
				</div>
			</div>
			<div class="row">
				<div class="col-auto">
					<table class="table table-striped table-hover cart">
						<thead class="table-group-divider"><?php $this->show_column_titles(); ?></thead>
						<tbody class="table-group-divider"><?php $this->show_table(); ?></tbody>
						<tfoot class="table-group-divider"><?php $this->show_column_titles(); ?></tfoot>
					</table>
				</div>
				<div class="col-auto">
					<table class="table table-dark total-table">
						<tr>
							<td><?php esc_html_e( 'Items:', 'gts-translation-order' ); ?></td>
							<td><span id="item_count"><?php echo esc_html( $items_count ); ?></span></td>
						</tr>
						<tr>
							<td><?php esc_html_e( 'Total Cost:', 'gts-translation-order' ); ?></td>
							<td>$<span id="total"><?php echo number_format( $this->get_total(), 2 ); ?></span></td>
						</tr>
						<?php $this->show_total_form(); ?>
						<tr>
							<td colspan="2">
								<?php
								$url = get_admin_url( null, 'admin.php?page=' . Main::GTS_MENU_SLUG );
								?>
								<a
										href="<?php echo esc_url( $url ); ?>"
										id="gts-to-back-to-translation" class="btn btn-primary">
									<?php esc_html_e( 'Back to selection', 'gts-translation-order' ); ?>
								</a>
							</td>
						</tr>
						<tr>
							<td colspan="2">
								<?php
								$disable_class = $items_count ? '' : 'disabled';
								?>
								<button type="button" id="gts-to-send-to-translation"
										class="btn btn-primary <?php echo esc_attr( $disable_class ); ?>">
									<?php esc_html_e( 'Send to translation', 'gts-translation-order' ); ?>
								</button>
							</td>
						</tr>
					</table>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Show total form.
	 *
	 * @return void
	 */
	private function show_total_form() {
		$filter     = Cookie::get_filter_cookie();
		$user       = wp_get_current_user();
		$user_email = ( $user && isset( $user->user_email ) ) ? $user->user_email : '';
		?>
		<tr>
			<td>
				<label for="gts-client-email">
					<?php esc_html_e( 'Email:', 'gts-translation-order' ); ?>
				</label>
			</td>
			<td>
				<input
						type="email"
						name="gts_client_email"
						class="form-control"
						id="gts-client-email"
						value="<?php echo esc_html( $user_email ); ?>"
						placeholder="name@example.com">
			</td>
		</tr>
		<tr>
			<td colspan="2" class="hidden">
				<form action="" method="POST">
					<input type="hidden" name="gts_target_language" id="gts_target_language">
					<input
							type="hidden" name="gts-source-language" id="gts-source-language"
							value="<?php echo esc_attr( $filter->source ); ?>">
					<input
							type="hidden" name="target-language" id="target-language"
							value="<?php echo esc_attr( implode( ', ', $filter->target ) ); ?>">
					<input
							type="hidden" name="gts-industry" id="gts-industry"
							value="General">
					<?php wp_nonce_field( 'gts_translation_cart', 'gts_translation_cart_nonce', false ); ?>
				</form>
			</td>
		</tr>
		<?php
	}

	/**
	 * Ajax handler add to cart.
	 *
	 * @return void
	 */
	public function add_to_cart() {
		$nonce = ! empty( $_POST['nonce'] ) ? filter_var( wp_unslash( $_POST['nonce'] ), FILTER_SANITIZE_STRING ) : '';

		if ( ! wp_verify_nonce( $nonce, Main::ADD_TO_CART_ACTION ) ) {
			wp_send_json_error( __( 'Bad Nonce', 'gts-translation-order' ) );
		}

		$source    = ! empty( $_POST['source'] ) ? filter_var( wp_unslash( $_POST['source'] ), FILTER_SANITIZE_STRING ) : '';
		$target    = ! empty( $_POST['target'] ) ? filter_var( wp_unslash( $_POST['target'] ), FILTER_SANITIZE_STRING ) : '';
		$languages = explode( ', ', $target );

		if ( ! $source || ! $languages ) {
			wp_send_json_error( __( 'Languages not selected.', 'gts-translation-order' ) );
		}

		$filter         = Cookie::get_filter_cookie();
		$filter->source = $source;
		$filter->target = $languages;

		Cookie::set_filter_cookie( (array) $filter );

		$post_ids = empty( $_POST['post_ids'] ) ?
			[] :
			filter_input( INPUT_POST, 'post_ids', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY );

		$this->save_posts_to_cart(
			[
				'type'     => 'add',
				'post_ids' => $post_ids,
			]
		);

		wp_send_json_success();
	}

	/**
	 * Remove post from cart.
	 *
	 * @return void
	 */
	public function delete_from_cart() {
		$nonce = ! empty( $_POST['nonce'] ) ? filter_var( wp_unslash( $_POST['nonce'] ), FILTER_SANITIZE_STRING ) : '';

		if ( ! wp_verify_nonce( $nonce, Main::DELETE_FROM_CART_ACTION ) ) {
			wp_send_json_error( __( 'Bad Nonce', 'gts-translation-order' ) );
		}

		$post_id = empty( $_POST['post_id'] ) ?
			[] :
			filter_var( wp_unslash( $_POST['post_id'] ), FILTER_SANITIZE_NUMBER_INT );

		$this->save_posts_to_cart(
			[
				'type'     => 'remove',
				'post_ids' => [ $post_id ],
			]
		);

		wp_send_json_success();
	}

	/**
	 * Send cart items to translation.
	 *
	 * @return void
	 * @noinspection DisconnectedForeachInstructionInspection
	 */
	public function send_to_translation() {
		$nonce = ! empty( $_POST['nonce'] ) ? filter_var( wp_unslash( $_POST['nonce'] ), FILTER_SANITIZE_STRING ) : '';

		if ( ! wp_verify_nonce( $nonce, Main::SEND_TO_TRANSLATION_ACTION ) ) {
			wp_send_json_error( __( 'Bad Nonce', 'gts-translation-order' ) );
		}

		$email        = ! empty( $_POST['email'] ) ? filter_var( wp_unslash( $_POST['email'] ), FILTER_SANITIZE_EMAIL ) : '';
		$source       = ! empty( $_POST['source'] ) ? filter_var( wp_unslash( $_POST['source'] ), FILTER_SANITIZE_STRING ) : '';
		$target       = ! empty( $_POST['target'] ) ? filter_var( wp_unslash( $_POST['target'] ), FILTER_SANITIZE_STRING ) : '';
		$industry     = ! empty( $_POST['industry'] ) ? filter_var( wp_unslash( $_POST['industry'] ), FILTER_SANITIZE_STRING ) : '';
		$total        = ! empty( $_POST['total'] ) ? filter_var( wp_unslash( $_POST['total'] ), FILTER_SANITIZE_STRING ) : 0;
		$total        = (float) str_replace( ',', '', $total );
		$export_files = [];
		$export       = new Export();
		$ids          = Cookie::get_cart_cookie();

		add_filter( 'query', [ $this, 'add_id_to_query' ] );

		foreach ( $ids as $id ) {
			$this->id = $id;

			ob_start();
			$export->export_wp(); // Uses $this->id via add_id_to_query filter.
			$export_file = ob_get_clean();

			$export_files[] = [
				'file_name' => get_the_title( $id ) . '.xml',
				'file'      => $export_file,
			];
		}

		remove_filter( 'query', [ $this, 'add_id_to_query' ] );

		$user       = wp_get_current_user();
		$user_login = $user->user_login ?? '';
		$user_id    = get_current_user_id();
		$user_meta  = get_user_meta( $user_id );
		$first_name = $user_meta['first_name'][0] ?? '';
		$last_name  = $user_meta['last_name'][0] ?? '';
		$full_name  = $first_name . ' ' . $last_name;
		$full_name  = trim( $full_name ) ? $full_name : $user_login;
		$word_count = $this->cost->get_total_words( $ids );

		$response = $this->api->send_order(
			[
				'email'      => $email,
				'source'     => $source,
				'target'     => $target,
				'industry'   => $industry,
				'file'       => $export_files,
				'full_name'  => $full_name,
				'word_count' => $word_count,
				'total'      => $total,
			]
		);

		if ( false === $response || ( empty( $response->success ) && empty( $response->error ) ) ) {
			wp_send_json_error( [ 'message' => __( 'Unknown communication error.', 'gts-translation-order' ) ] );
		}

		if ( empty( $response->success ) ) {
			wp_send_json_error( [ 'message' => $response->error ] );
		}

		$result = $this->save_order(
			[
				'order_id'         => $response->order_id,
				'post_id'          => $ids,
				'status'           => Main::ORDER_STATUS_SENT,
				'total'            => $total,
				'date'             => gmdate( 'Y-m-d H:i:s', time() ),
				'source_language'  => $source,
				'target_languages' => $target,
				'industry'         => $industry,
			]
		);

		if ( ! $result ) {
			wp_send_json_error( [ 'message' => __( 'Cannot save order.', 'gts-translation-order' ) ] );
		}

		wp_send_json_success( $response );
	}

	/**
	 * Update price.
	 *
	 * @return void
	 */
	public function update_price() {
		$nonce = ! empty( $_POST['nonce'] ) ? filter_var( wp_unslash( $_POST['nonce'] ), FILTER_SANITIZE_STRING ) : '';

		if ( ! wp_verify_nonce( $nonce, Main::UPDATE_PRICE_ACTION ) ) {
			wp_send_json_error( __( 'Bad Nonce.', 'gts-translation-order' ) );
		}

		$language  = ! empty( $_POST['target'] ) ? filter_var( wp_unslash( $_POST['target'] ), FILTER_SANITIZE_STRING ) : '';
		$source    = ! empty( $_POST['source'] ) ? filter_var( wp_unslash( $_POST['source'] ), FILTER_SANITIZE_STRING ) : '';
		$languages = explode( ', ', $language );

		if ( ! $source || ! $languages ) {
			wp_send_json_error( __( 'Languages not selected.', 'gts-translation-order' ) );
		}

		$ids   = Cookie::get_cart_cookie();
		$price = [];

		foreach ( $ids as $id ) {
			$price[] = [
				'price' => round( $this->cost->price_by_post( $source, $languages, $id ), 2 ),
				'id'    => $id,
			];
		}

		$filter         = Cookie::get_filter_cookie();
		$filter->source = $source;
		$filter->target = $languages;

		Cookie::set_filter_cookie( (array) $filter );

		wp_send_json_success( [ 'newPrice' => $price ] );
	}

	/**
	 * Filter db query to add post id.
	 *
	 * @param string $query Database query.
	 *
	 * @return string
	 */
	public function add_id_to_query( $query ) {
		global $wpdb;

		if ( ! preg_match( "/SELECT ID FROM $wpdb->posts .* WHERE /", $query ) ) {
			return $query;
		}

		return "SELECT $wpdb->posts.ID FROM $wpdb->posts WHERE $wpdb->posts.ID = $this->id";
	}

	/**
	 * Get items count.
	 *
	 * @return int
	 */
	public function get_count() {
		return count( Cookie::get_cart_cookie() );
	}

	/**
	 * Get cart total.
	 *
	 * @return float|int
	 */
	public function get_total() {
		$filter = Cookie::get_filter_cookie();
		$ids    = Cookie::get_cart_cookie();
		$total  = 0;

		foreach ( $ids as $id ) {
			$post = get_post( $id );

			if ( $post && ! empty( $filter->source ) && $filter->target ) {
				$price = $this->cost->price_by_post( $filter->source, $filter->target, $post->ID );

				$total += $price;
			}
		}

		return $total;
	}

	/**
	 * Save post ids to cart.
	 *
	 * @param array $args Arguments.
	 */
	private function save_posts_to_cart( array $args ) {
		$ids    = Cookie::get_cart_cookie();
		$result = [];

		if ( 'add' === $args['type'] ) {
			$result = array_unique( array_merge( $ids, $args['post_ids'] ) );
		}

		if ( 'remove' === $args['type'] ) {
			$result = array_diff( $ids, $args['post_ids'] );
		}

		Cookie::set_cart_cookie( $result );
	}

	/**
	 * Show table.
	 *
	 * @return void
	 */
	private function show_table() {
		$ids = Cookie::get_cart_cookie();

		if ( 0 === count( $ids ) ) {
			?>
			<tr>
				<td colspan="6"><?php esc_html_e( 'Cart is Empty', 'gts-translation-order' ); ?></td>
			</tr>
			<?php

			return;
		}

		$filter = Cookie::get_filter_cookie();

		foreach ( $ids as $id ) {
			$post  = get_post( $id );
			$title = $post ? $post->post_title : __( '(no title)', 'gts-translation-order' );
			$price = 0;

			if ( $post && ! empty( $filter->source ) && $filter->target ) {
				$price = $this->cost->price_by_post( $filter->source, $filter->target, $post->ID );
			}
			?>
			<tr>
				<td><?php echo esc_html( $title ); ?></td>
				<td><?php echo esc_html( $post->post_type ); ?></td>
				<td class="price">$<?php echo number_format( $price, 2 ); ?></td>
				<td>
					<a
							href="#" data-post_id="<?php echo esc_attr( $post->ID ); ?>"
							class="plus remove-from-cart">
						<span class="dashicons dashicons-minus"></span>
					</a>
				</td>
			</tr>
			<?php
		}
	}

	/**
	 * Change Status.
	 *
	 * @param array $args Arguments.
	 *
	 * @return bool
	 */
	private function save_order( $args ) {
		global $wpdb;

		$formats = [
			'order_id'         => '%d',
			'post_id'          => '%d',
			'status'           => '%s',
			'total'            => '%f',
			'date'             => '%s',
			'source_language'  => '%s',
			'target_languages' => '%s',
			'industry'         => '%s',
		];

		ksort( $args );
		ksort( $formats );

		$columns_arr = array_keys( $formats );

		if ( array_keys( $args ) !== $columns_arr ) {
			return false;
		}

		$columns         = implode( ', ', $columns_arr );
		$ids             = $args['post_id'];
		$args['post_id'] = 0;
		$values_arr      = [];
		$table_name      = Main::ORDER_TABLE_NAME;

		foreach ( (array) $ids as $id ) {
			$args['post_id'] = $id;

			// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
			$value = $wpdb->prepare(
				implode( ', ', array_values( $formats ) ),
				array_values( $args )
			);
			// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare

			$values_arr[] = '(' . $value . ')';
		}

		if ( ! $values_arr ) {
			return false;
		}

		$values = implode( ', ', $values_arr );

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$result = $wpdb->query(
			"INSERT INTO $wpdb->prefix$table_name
    		($columns)
			VALUES $values"
		);

		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		return false !== $result;
	}

	/**
	 * Show column titles.
	 *
	 * @return void
	 */
	private function show_column_titles() {
		?>
		<tr>
			<th scope="col"><?php esc_attr_e( 'Title', 'gts_translation_order' ); ?></th>
			<th scope="col"><?php esc_attr_e( 'Type', 'gts-translation-order' ); ?></th>
			<th scope="col"><?php esc_attr_e( 'Cost', 'gts-translation-order' ); ?></th>
			<th scope="col"><?php esc_attr_e( 'Action', 'gts-translation-order' ); ?></th>
		</tr>
		<?php
	}
}
