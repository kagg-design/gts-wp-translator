<?php
/**
 * TranslationCart class file.
 *
 * @package gts/wp-translator
 */

namespace GTS\WPTranslator\Pages;

use GTS\WPTranslator\Cookie;
use GTS\WPTranslator\Cost;
use GTS\WPTranslator\API;
use GTS\WPTranslator\Export;
use GTS\WPTranslator\Main;

/**
 * Translation cart page.
 */
class Cart {

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
	 * Cart class constructor.
	 */
	public function __construct() {
		$this->api  = new API();
		$this->cost = new Cost();

		$this->init();
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
						<h1 class="wp-heading-inline"><?php esc_attr_e( 'Translation Cart', 'gts-wp-translator' ); ?></h1>
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
							<td><?php esc_html_e( 'Items:', 'gts-wp-translator' ); ?></td>
							<td><span id="item_count"><?php echo esc_html( $items_count ); ?></span></td>
						</tr>
						<tr>
							<td><?php esc_html_e( 'Total Words:', 'gts-wp-translator' ); ?></td>
							<td><?php echo esc_html( $this->get_total_word_count() ); ?></td>
						</tr>
						<tr>
							<td><?php esc_html_e( 'Min Order:', 'gts-wp-translator' ); ?></td>
							<td>
								$<?php echo number_format( $this->cost->get_min_order(), 2 ); ?>
								<?php esc_html_e( 'per language', 'gts-wp-translator' ); ?>
							</td>
						</tr>
						<?php $this->show_cost_per_language(); ?>
						<tr>
							<td><?php esc_html_e( 'Total Cost:', 'gts-wp-translator' ); ?></td>
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
										id="gts-wp-translator-back-to-translation" class="btn btn-primary">
									<?php esc_html_e( 'Back to selection', 'gts-wp-translator' ); ?>
								</a>
							</td>
						</tr>
						<tr>
							<td colspan="2">
								<?php
								$disable_class = $items_count ? '' : 'disabled';
								?>
								<button type="button" id="gts-wp-translator-send-to-translation"
										class="btn btn-primary <?php echo esc_attr( $disable_class ); ?>">
									<?php esc_html_e( 'Send to translation', 'gts-wp-translator' ); ?>
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
					<?php esc_html_e( 'Email:', 'gts-wp-translator' ); ?>
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
					<?php wp_nonce_field( 'gts_wp_translation_cart', 'gts_wp_translation_cart_nonce', false ); ?>
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
			wp_send_json_error( __( 'Bad Nonce', 'gts-wp-translator' ) );
		}

		$source    = ! empty( $_POST['source'] ) ? filter_var( wp_unslash( $_POST['source'] ), FILTER_SANITIZE_STRING ) : '';
		$target    = ! empty( $_POST['target'] ) ? filter_var( wp_unslash( $_POST['target'] ), FILTER_SANITIZE_STRING ) : '';
		$languages = explode( ', ', $target );

		if ( ! $source || ! $languages ) {
			wp_send_json_error( __( 'Languages not selected.', 'gts-wp-translator' ) );
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
			wp_send_json_error( __( 'Bad Nonce', 'gts-wp-translator' ) );
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
			wp_send_json_error( __( 'Bad Nonce', 'gts-wp-translator' ) );
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
		$word_count   = 0;

		add_filter( 'query', [ $this, 'add_id_to_query' ] );

		foreach ( $ids as $id ) {
			$this->id = $id;

			ob_start();
			$export->export_wp(); // Uses $this->id via add_id_to_query filter.
			$export_file = ob_get_clean();

			$file_word_count = $this->cost->get_word_count( $id );
			$export_files[]  = [
				'content'    => $export_file,
				'file_name'  => get_the_title( $id ) . '.xml',
				'id'         => $id,
				'word_count' => $file_word_count,
			];

			$word_count += $file_word_count;
		}

		remove_filter( 'query', [ $this, 'add_id_to_query' ] );

		$user       = wp_get_current_user();
		$user_login = isset( $user, $user->user_login ) ? $user->user_login : '';
		$user_id    = get_current_user_id();
		$user_meta  = get_user_meta( $user_id );
		$first_name = isset( $user_meta['first_name'][0] ) ? $user_meta['first_name'][0] : '';
		$last_name  = isset( $user_meta['last_name'][0] ) ? $user_meta['last_name'][0] : '';
		$full_name  = $first_name . ' ' . $last_name;
		$full_name  = trim( $full_name ) ? $full_name : $user_login;

		$response = $this->api->create_order(
			[
				'domain'     => home_url(),
				'email'      => $email,
				'files'      => $export_files,
				'full_name'  => $full_name,
				'industry'   => $industry,
				'source'     => $source,
				'target'     => $target,
				'total'      => $total,
				'version'    => GTS_WP_TRANSLATOR_VERSION,
				'word_count' => $word_count,
			]
		);

		if ( false === $response || ( empty( $response->success ) && empty( $response->error ) ) ) {
			wp_send_json_error( [ 'message' => __( 'Unknown communication error.', 'gts-wp-translator' ) ] );
		}

		if ( empty( $response->success ) ) {
			wp_send_json_error( [ 'message' => $response->error ] );
		}

		// @todo: Save $ids as meta.
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
			wp_send_json_error( [ 'message' => __( 'Cannot save order.', 'gts-wp-translator' ) ] );
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
			wp_send_json_error( __( 'Bad Nonce.', 'gts-wp-translator' ) );
		}

		$language  = ! empty( $_POST['target'] ) ? filter_var( wp_unslash( $_POST['target'] ), FILTER_SANITIZE_STRING ) : '';
		$source    = ! empty( $_POST['source'] ) ? filter_var( wp_unslash( $_POST['source'] ), FILTER_SANITIZE_STRING ) : '';
		$languages = explode( ', ', $language );

		if ( ! $source || ! $languages ) {
			wp_send_json_error( __( 'Languages not selected.', 'gts-wp-translator' ) );
		}

		$ids   = Cookie::get_cart_cookie();
		$price = [];

		foreach ( $ids as $id ) {
			$price[] = [
				'price' => round( $this->cost->price_for_post( $source, $languages, $id ), 2 ),
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
	 * Get total word count.
	 *
	 * @return int
	 */
	private function get_total_word_count() {
		$ids = Cookie::get_cart_cookie();

		return $this->cost->get_total_word_count( $ids );
	}

	/**
	 * Get cart total.
	 *
	 * @return float|int
	 */
	public function get_total() {
		$filter = Cookie::get_filter_cookie();

		return array_sum(
			$this->cost->get_amount_each_language( $filter->source, $filter->target, $this->get_total_word_count() )
		);
	}

	/**
	 * Show add to cart button.
	 *
	 * @return void
	 */
	public function show_add_to_cart_button() {
		?>
		<button type="button" class="btn btn-primary add-bulk-to-cart btn-sm">
			<?php esc_attr_e( 'Add to Cart', 'gts-wp-translator' ); ?>
		</button>
		<?php
	}

	/**
	 * Show mini cart.
	 *
	 * @return void
	 */
	public function show_mini_cart() {
		?>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=' . Main::GTS_SUB_MENU_CART_SLUG ) ); ?>">
			<button type="button" class="btn btn-secondary btn-sm">
				<span class="dashicons dashicons-cart"></span>
				<span><?php echo esc_html( $this->get_count() ); ?></span>
				<span>&nbsp;&nbsp;&nbsp;$<?php echo number_format( $this->get_total(), 2 ); ?></span>
			</button>
		</a>
		<?php
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
				<td colspan="6"><?php esc_html_e( 'Cart is Empty', 'gts-wp-translator' ); ?></td>
			</tr>
			<?php

			return;
		}

		$filter = Cookie::get_filter_cookie();

		foreach ( $ids as $id ) {
			$post  = get_post( $id );
			$title = $post ? $post->post_title : __( '(no title)', 'gts-wp-translator' );
			$price = 0;

			if ( $post && ! empty( $filter->source ) && $filter->target ) {
				$price = $this->cost->price_for_post( $filter->source, $filter->target, $post->ID );
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
			<th scope="col"><?php esc_attr_e( 'Title', 'gts-wp-translator' ); ?></th>
			<th scope="col"><?php esc_attr_e( 'Type', 'gts-wp-translator' ); ?></th>
			<th scope="col"><?php esc_attr_e( 'Cost', 'gts-wp-translator' ); ?></th>
			<th scope="col"><?php esc_attr_e( 'Action', 'gts-wp-translator' ); ?></th>
		</tr>
		<?php
	}

	/**
	 * Show cost per language.
	 *
	 * @return void
	 */
	private function show_cost_per_language() {
		$filter               = Cookie::get_filter_cookie();
		$amount_each_language = $this->cost->get_amount_each_language(
			$filter->source,
			$filter->target,
			$this->get_total_word_count()
		);

		foreach ( $filter->target as $index => $target_language ) {
			$unit = $this->cost->is_rate_per_char( $filter->source, $target_language ) ?
				__( 'per char', 'gts-wp-translator' ) :
				__( 'per word', 'gts-wp-translator' );
			?>
			<tr>
				<td><?php echo esc_html( $target_language ); ?>:</td>
				<td>
					$<?php echo number_format( $amount_each_language[ $index ], 2 ); ?>
					($<?php echo number_format( $this->cost->get_rate_per_word( $filter->source, $target_language ), 2 ); ?>
					<?php echo esc_html( $unit ); ?>)
				</td>
			</tr>
			<?php
		}
	}
}
