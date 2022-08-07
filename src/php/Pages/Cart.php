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
use wpdb;

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
	 * Industry.
	 */
	const GTS_INDUSTRY_LIST = [
		'Academic',
		'Chemical (MSDS)',
		'Financial',
		'Gaming',
		'General',
		'Human Resources',
		'Legal',
		'Marketing Material',
		'Medical',
		'Patent',
		'Scientific',
		'Software/IT',
		'Technical Manual',
		'Technical/Engineering',
		'Web Content',
	];

	/**
	 * Cost calculation.
	 *
	 * @var Cost $cost Cost class.
	 */
	private $cost;

	/**
	 * Total Price.
	 *
	 * @var float $total total.
	 */
	private $total = 0;

	/**
	 * IDs of posts to process.
	 *
	 * @var int[]
	 */
	private $ids;

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
							<td>$<span id="total"><?php echo number_format( $this->total, 2 ); ?></span></td>
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

		$this->show_pop_up_language();
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
	 * Show pop-up target language
	 *
	 * @return void
	 */
	private function show_pop_up_language() {
		?>
		<div class="modal modal-lg" tabindex="-1" id="language-modal">
			<div class="modal-dialog modal-dialog-centered">
				<div class="modal-content">
					<div class="modal-header">
						<button type="button" class="btn-close" data-bs-dismiss="modal"
								aria-label="<?php esc_html_e( 'Close', 'gts-translation-order' ); ?>"></button>
					</div>
					<div class="modal-body">
						<table class="table">
							<tbody>
							<?php
							$i      = 0;
							$target = Cookie::get_filter_cookie()->target;
							echo '<tr>';
							foreach ( $this->language_list as $lang ) {
								$i ++;
								?>
								<td class="cell">
									<input
											type="checkbox" name="regi_target_language[]"
											value="<?php echo esc_html( $lang->language_name ); ?>"
											id="<?php echo esc_html( $lang->language_name ); ?>"
											class="lang-checkbox"
										<?php echo in_array( $lang->language_name, $target, true ) ? 'checked' : ''; ?>
									/>
									<label for="<?php echo esc_html( $lang->language_name ); ?>">
										<?php echo esc_html( $lang->language_name ); ?>
									</label>
								</td>
								<?php
								if ( 4 === $i ) {
									echo '</tr><tr>';
									$i = 0;
								}
							}
							echo '</tr>';
							?>
							</tbody>
						</table>
					</div>
					<div class="modal-footer">
						<button
								type="button"
								class="btn btn-primary add-to-cart-bulk"
								id="save-target-language">
							<?php esc_attr_e( 'Save', 'gts-translation-order' ); ?>
						</button>
					</div>
				</div>
			</div>
		</div>
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

		$filter = Cookie::get_filter_cookie();

		$source    = ! empty( $_POST['source'] ) ? filter_var( wp_unslash( $_POST['source'] ), FILTER_SANITIZE_STRING ) : '';
		$target    = ! empty( $_POST['target'] ) ? filter_var( wp_unslash( $_POST['target'] ), FILTER_SANITIZE_STRING ) : '';
		$languages = explode( ', ', $target );

		if ( ! $source || ! $languages ) {
			wp_send_json_error( __( 'Languages not selected.', 'gts-translation-order' ) );
		}

		$filter->source = $source;
		$filter->target = $languages;

		Cookie::set( Cookie::FILTER_COOKIE_NAME, (array) $filter );

		$post_id = ! empty( $_POST['post_id'] ) ? filter_input( INPUT_POST, 'post_id', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY ) : null;
		$result  = $this->save_post_to_cart(
			[
				'type'    => 'add',
				'post_id' => $post_id,
			]
		);

		wp_send_json_success( [ 'posts_id' => $result ] );
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

		$posts_id = ! empty( $_POST['post_id'] ) ? filter_var( wp_unslash( $_POST['post_id'] ), FILTER_SANITIZE_NUMBER_INT ) : null;

		$result = $this->save_post_to_cart(
			[
				'type'    => 'remove',
				'post_id' => [ $posts_id ],
			]
		);

		wp_send_json_success( [ 'posts_id' => $result ] );
	}

	/**
	 * Send cart items to translation.
	 *
	 * @return void
	 */
	public function send_to_translation() {
		$this->ids = Cookie::get_cart_cookie();

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

		add_filter( 'query', [ $this, 'add_ids_to_query' ] );

		foreach ( $this->ids as $id ) {
			$this->id = $id;

			ob_start();
			$export->export_wp();
			$export_file = ob_get_clean();

			$export_files[] = [
				'file_name' => get_the_title( $id ),
				'file'      => $export_file,
			];
		}

		remove_filter( 'query', [ $this, 'add_ids_to_query' ] );

		$user       = wp_get_current_user();
		$user_login = $user ? $user->user_login : '';
		$user_id    = get_current_user_id();
		$user_meta  = get_user_meta( $user_id );
		$first_name = isset( $user_meta['first_name'][0] ) ? $user_meta['first_name'][0] : '';
		$last_name  = isset( $user_meta['last_name'][0] ) ? $user_meta['last_name'][0] : '';
		$full_name  = $first_name . ' ' . $last_name;
		$full_name  = trim( $full_name ) ? $full_name : $user_login;
		$word_count = $this->cost->get_total_words( $this->ids );

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
				'post_id'          => $this->ids,
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
		$this->ids = Cookie::get_cart_cookie();
		$filter    = Cookie::get_filter_cookie();

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

		$price = [];

		foreach ( $this->ids as $id ) {
			$price[] = [
				'price' => round( $this->cost->price_by_post( $source, $languages, $id ), 2 ),
				'id'    => $id,
			];
		}

		$filter->source = $source;
		$filter->target = $languages;

		Cookie::set( Cookie::FILTER_COOKIE_NAME, (array) $filter );

		wp_send_json_success( [ 'newPrice' => $price ] );
	}

	/**
	 * Filter db query to add post ids.
	 *
	 * @param string $query Database query.
	 *
	 * @return string
	 */
	public function add_ids_to_query( $query ) {
		global $wpdb;

		if ( ! preg_match( "/SELECT ID FROM $wpdb->posts .* WHERE /", $query ) ) {
			return $query;
		}

		return "SELECT $wpdb->posts.ID FROM $wpdb->posts WHERE $wpdb->posts.ID = $this->id";
	}

	/**
	 * Save post id to cart
	 *
	 * @param array $args Arguments.
	 */
	private function save_post_to_cart( array $args ) {
		$cart_post_ids = Cookie::get_cart_cookie();
		$result        = [];

		if ( 'add' === $args['type'] ) {
			$result = array_unique( array_merge( $cart_post_ids, $args['post_id'] ) );
		}

		if ( 'remove' === $args['type'] ) {
			$result = array_diff( $cart_post_ids, $args['post_id'] );
		}

		Cookie::set( Cookie::CART_COOKIE_NAME, $result );

		return $result;
	}

	/**
	 * Show table.
	 *
	 * @return void
	 */
	private function show_table() {
		$cart_items = Cookie::get_cart_cookie();
		$filter     = Cookie::get_filter_cookie();

		if ( 0 === count( $cart_items ) ) {
			?>
			<tr>
				<td colspan="6"><?php esc_html_e( 'Cart is Empty', 'gts-translation-order' ); ?></td>
			</tr>
			<?php

			return;
		}

		$this->total = 0;

		foreach ( $cart_items as $item ) {
			$post  = get_post( $item );
			$title = $post->post_title;
			$title = $title ?: __( '(no title)', 'gts-translation-order' );
			$price = 0;

			if ( ! empty( $filter->source ) && $filter->target ) {
				$price = $this->cost->price_by_post( $filter->source, $filter->target, $post->ID );

				$this->total += $price;
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
	 * Changes array of items into string of items, separated by comma and sql-escaped
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
