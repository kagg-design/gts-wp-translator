<?php
/**
 * Translation cart page.
 *
 * @package gts/translation-order
 */

namespace GTS\TranslationOrder\Pages;

use GTS\TranslationOrder\Cost;
use GTS\TranslationOrder\GTS_API;
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
	private $total;

	/**
	 * TranslationOrder class file.
	 */
	public function __construct() {
		$this->init();

		$api                 = new GTS_API();
		$this->language_list = $api->get_languages_list();
		$this->cost          = new Cost();
		$this->total         = 0;
	}

	/**
	 * Init hooks.
	 *
	 * @return void
	 */
	public function init() {
		add_action( 'wp_ajax_add_to_cart', [ $this, 'add_to_cart' ] );
		add_action( 'wp_ajax_nopriv_add_to_cart', [ $this, 'add_to_cart' ] );

		add_action( 'wp_ajax_delete_from_cart', [ $this, 'delete_from_cart' ] );
		add_action( 'wp_ajax_nopriv_delete_from_cart', [ $this, 'delete_from_cart' ] );
	}

	/**
	 * Show translation cart.
	 *
	 * @return void
	 */
	public function show_translation_cart() {
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
						<thead>
						<tr>
							<th scope="col">
								<?php esc_attr_e( 'Title', 'gts_translation_order' ); ?>
							</th>
							<th scope="col"><?php esc_attr_e( 'Type', 'gts-translation-order' ); ?></th>
							<th scope="col"><?php esc_attr_e( 'Cost', 'gts-translation-order' ); ?></th>
							<th scope="col"><?php esc_attr_e( 'Action', 'gts-translation-order' ); ?></th>
						</tr>
						</thead>
						<tbody class="table-group-divider">
						<?php $this->show_table(); ?>
						</tbody>
					</table>
				</div>
				<div class="col-auto">
					<table class="table table-dark table-striped total-table">
						<tr>
							<td>Total Cost:</td>
							<td>$<?php echo esc_attr( $this->total ); ?></td>
						</tr>
						<tr>
							<td colspan="2">
								<?php $this->show_total_form(); ?>
							</td>
						</tr>
						<tr>
							<td><?php esc_html_e( 'Send to Translation:', 'gts-translation-order' ); ?></td>
							<td>
								<button type="button" class="btn btn-primary">
									<?php esc_html_e( 'Send', 'gts-translation-order' ); ?>
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
		$filter     = $this->get_cookie( 'gts_post_filter' );
		$user       = wp_get_current_user();
		$user_email = ( $user && isset( $user->user_email ) ) ? $user->user_email : '';
		?>
		<form action="" method="post">
			<div class="mb-3">
				<label for="gts_client_email" class="form-label">
					<?php esc_html_e( 'Email address', 'gts-translation-order' ); ?>
				</label>
				<input
						type="email"
						name="gts_client_email"
						class="form-control"
						id="gts_client_email"
						value="<?php echo esc_html( $user_email ); ?>"
						placeholder="name@example.com">
			</div>
			<div class="mb-3">
				<label for="language"><?php esc_html_e( 'Select Languages', 'gts-translation-order' ); ?></label>
				<select
						class="form-select"
						id="language"
						name="gts_source_language"
						aria-label="<?php esc_html_e( 'Select Languages', 'gts-translation-order' ); ?>">
					<option value="0"
							selected><?php esc_html_e( 'Select Languages', 'gts-translation-order' ); ?></option>
					<?php
					foreach ( $this->language_list as $item ) {
						if ( $item->active ) {
							?>
							<option value="<?php echo esc_attr( $item->language_name ); ?>" <?php selected( $item->language_name, $filter->source ); ?>>
								<?php echo esc_html( $item->language_name ); ?>
							</option>
							<?php
						}
					}
					?>
				</select>
			</div>
			<div class="mb-3">
				<label for="target-language" class="hidden"></label>
				<input
						type="text"
						class="form-control"
						id="target-language"
						name="gts_target_language"
						value="<?php echo esc_attr( implode( ', ', $filter->target ) ); ?>"
						placeholder="<?php esc_html_e( 'Select a target languages', 'gts-translation-order' ); ?>"
						readonly>
			</div>
			<div class="mb-3">
				<label for="gts-industry"><?php esc_html_e( 'Select Industry', 'gts-translation-order' ); ?></label>
				<select
						class="form-select"
						id="gts-industry"
						name="gts-industry"
						aria-label="<?php esc_html_e( 'Select Industry', 'gts-translation-order' ); ?>">
					<option value="0" selected>
						<?php esc_html_e( 'Select Industry', 'gts-translation-order' ); ?>
					</option>
					<?php foreach ( self::GTS_INDUSTRY_LIST as $item ) : ?>
						<option value="<?php echo esc_attr( $item ); ?>">
							<?php echo esc_html( $item ); ?>
						</option>
					<?php endforeach; ?>
				</select>
			</div>
			<input type="hidden" name="gts_target_language" id="gts_target_language">
			<?php wp_nonce_field( 'gts_translation_cart', 'gts_translation_cart_nonce', false ); ?>
		</form>
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
							$i = 0;
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
									/>
									<label for="<?php echo esc_html( $lang->language_name ); ?>">
										<?php echo esc_html( $lang->language_name ); ?>
									</label>
								</td>
								<?php
								if ( 3 === $i ) {
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

		if ( ! wp_verify_nonce( $nonce, 'gts_add_to_cart_nonce' ) ) {
			wp_send_json_error( __( 'Bad Nonce', 'gts-translation-order' ) );
		}

		$bulk = ! empty( $_POST['bulk'] ) && filter_var( wp_unslash( $_POST['bulk'] ), FILTER_SANITIZE_STRING );

		if ( $bulk ) {
			// bulk add.

			$posts_id = ! empty( $_POST['post_id'] ) ? filter_input( INPUT_POST, 'post_id', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY ) : null;
			$result   = $this->save_post_to_cart(
				[
					'type'    => 'add',
					'post_id' => $posts_id,
				]
			);

		} else {
			// single add.
			$posts_id = ! empty( $_POST['post_id'] ) ? filter_var( wp_unslash( $_POST['post_id'] ), FILTER_SANITIZE_NUMBER_INT ) : null;
			$result   = $this->save_post_to_cart(
				[
					'type'    => 'add',
					'post_id' => [ $posts_id ],
				]
			);

		}

		wp_send_json_success( [ 'posts_id' => $result ] );
	}

	/**
	 * Remove post from cart.
	 *
	 * @return void
	 */
	public function delete_from_cart() {
		$nonce = ! empty( $_POST['nonce'] ) ? filter_var( wp_unslash( $_POST['nonce'] ), FILTER_SANITIZE_STRING ) : '';

		if ( ! wp_verify_nonce( $nonce, 'gts_remove_from_cart_nonce' ) ) {
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
	 * Save post id to cart
	 *
	 * @param array $args Arguments.
	 */
	private function save_post_to_cart( array $args ) {

		$cart_post_ids = ! empty( $_COOKIE['gts_cart_data'] ) ? (array) json_decode( filter_var( wp_unslash( $_COOKIE['gts_cart_data'] ) ) ) : (array) null;

		$result = [];

		if ( 'add' === $args['type'] ) {
			$result = array_merge( $cart_post_ids, $args['post_id'] );
		}

		if ( 'remove' === $args['type'] ) {
			$result = array_diff( $cart_post_ids, $args['post_id'] );
		}

		setcookie(
			'gts_cart_data',
			wp_json_encode( $result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE ),
			strtotime( '+30 days' ),
			COOKIEPATH,
			COOKIE_DOMAIN
		);

		$_COOKIE['gts_cart_data'] = $result;

		return $result;

	}

	/**
	 * Get cookie filter params.
	 *
	 * @param string $name Name cookie.
	 *
	 * @return object
	 */
	private function get_cookie( $name ) {
		if ( isset( $_COOKIE[ $name ] ) ) {
			return (object) json_decode( filter_var( wp_unslash( $_COOKIE[ $name ] ) ) );
		}

		return (object) null;
	}

	/**
	 * Show table.
	 *
	 * @return void
	 */
	private function show_table() {
		$cart_item = $this->get_cookie( 'gts_cart_data' );
		$filter    = $this->get_cookie( 'gts_post_filter' );
		if ( 0 !== count( (array) $cart_item ) ) {
			foreach ( $cart_item as $item ) {
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
					<td>$<?php echo esc_html( $price ); ?></td>
					<td>
						<a
								href="#" data-post_id="<?php echo esc_attr( $post->ID ); ?>"
								class="plus remove-to-cart">
							<i class="bi bi-dash-square"></i>
						</a>
					</td>
				</tr>
				<?php
			}
		} else {
			?>
			<tr>
				<td colspan="6"><?php esc_html_e( 'Cart is Empty', 'gts-translation-order' ); ?></td>
			</tr>
			<?php
		}
	}
}
