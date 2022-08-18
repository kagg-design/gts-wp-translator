<?php
/**
 * PostFilter class file.
 *
 * @package gts/translation-order
 */

namespace GTS\TranslationOrder\Filter;

use GTS\TranslationOrder\Admin\AdminNotice;
use GTS\TranslationOrder\Cookie;
use GTS\TranslationOrder\API;
use GTS\TranslationOrder\Pages\Cart;

/**
 * PostFilter class form filter in admin panel.
 */
class PostFilter {

	/**
	 * Exclude post types.
	 */
	const EXCLUDE_POST_TYPES = [
		'attachment',
		'revision',
		'nav_menu_item',
		'clients',
		'notification',
	];

	/**
	 * Cart class instance.
	 *
	 * @var Cart
	 */
	private $cart;

	/**
	 * Languages.
	 *
	 * @var array
	 */
	private $languages;

	/**
	 * PostFilter construct.
	 *
	 * @param Cart $cart Cart class instance.
	 */
	public function __construct( $cart ) {
		$this->cart      = $cart;
		$this->languages = ( new API() )->get_languages();

		$this->init();
	}

	/**
	 * Init Hooks.
	 *
	 * @return void
	 */
	private function init() {
		add_action( 'init', [ $this, 'update_filter' ] );
	}

	/**
	 * Update pasts filter.
	 *
	 * @return void
	 */
	public function update_filter() {
		if ( ! isset( $_POST['gts_filter_submit'] ) ) {
			return;
		}

		$nonce = isset( $_POST['gts_post_type_filter_nonce'] ) ?
			filter_var( wp_unslash( $_POST['gts_post_type_filter_nonce'] ), FILTER_SANITIZE_STRING ) :
			null;

		if ( ! wp_verify_nonce( $nonce, 'gts_post_type_filter' ) ) {
			add_action( 'admin_notices', [ AdminNotice::class, 'bad_nonce' ] );

			return;
		}

		$post_type = ! empty( $_POST['gts_to_post_type_select'] ) ?
			filter_var( wp_unslash( $_POST['gts_to_post_type_select'] ), FILTER_SANITIZE_STRING ) :
			'';
		$search    = ! empty( $_POST['gts_to_search'] ) ?
			filter_var( wp_unslash( $_POST['gts_to_search'] ), FILTER_SANITIZE_STRING ) :
			'';
		$source    = ! empty( $_POST['gts_source_language'] ) ?
			filter_var( wp_unslash( $_POST['gts_source_language'] ), FILTER_SANITIZE_STRING ) :
			'';
		$target    = ! empty( $_POST['target_language'] ) ?
			filter_var( wp_unslash( $_POST['target_language'] ), FILTER_SANITIZE_STRING ) :
			'';

		$param = [
			'post_type' => $post_type,
			'search'    => $search,
			'source'    => $source,
			'target'    => explode( ', ', $target ),
		];

		Cookie::set_filter_cookie( $param );
	}

	/**
	 * Show filter form.
	 *
	 * @return void
	 */
	public function show_form() {
		?>
		<form action="" id="filter-form" method="post">
			<div class="row">
				<div class="col-auto">
					<?php $this->show_post_types_select(); ?>
				</div>
				<div class="col-auto">
					<?php $this->show_search_field(); ?>
				</div>
				<div class="col-auto">
					<?php $this->show_source_language(); ?>
				</div>
				<div class="col-auto">
					<?php $this->show_target_language_select(); ?>
				</div>
				<div class="col-auto">
					<input type="submit" name="gts_filter_submit" class="btn-sm btn btn-primary" value="Filter">
					<?php wp_nonce_field( 'gts_post_type_filter', 'gts_post_type_filter_nonce', false ); ?>
				</div>
				<div class="col-auto">
					<?php $this->cart->show_add_to_cart_button(); ?>
				</div>
				<div class="col-auto">
					<?php $this->cart->show_mini_cart(); ?>
				</div>
			</div>
		</form>
		<?php
	}

	/**
	 * Show Post Types select.
	 *
	 * @return void
	 */
	public function show_post_types_select() {
		$filter    = Cookie::get_filter_cookie();
		$post_type = isset( $filter->post_type ) ? $filter->post_type : '';
		?>
		<select class="form-select" id="gts_to_post_type_select" aria-label="Post Type" name="gts_to_post_type_select">
			<option value="null" selected><?php esc_html_e( 'Select post type', 'gts-translation-order' ); ?></option>
			<?php foreach ( $this->get_post_types() as $type ) : ?>
				<option value="<?php echo esc_attr( $type ); ?>" <?php echo $post_type ? selected( $post_type, $type, false ) : ''; ?>>
					<?php echo esc_attr( $type ); ?>
				</option>
			<?php endforeach; ?>
		</select>
		<?php
	}

	/**
	 * Show search field.
	 *
	 * @return void
	 */
	public function show_search_field() {
		$filter = Cookie::get_filter_cookie();
		$search = isset( $filter->search ) ? $filter->search : '';
		?>
		<label for="gts_to_search" class="hidden"></label>
		<input
				type="text"
				class="form-control"
				id="gts_to_search"
				name="gts_to_search"
				value="<?php echo esc_html( $search ); ?>"
				placeholder="<?php esc_html_e( 'Search by title', 'gts-translation-order' ); ?>">
		<?php
	}

	/**
	 * Show select current target language.
	 *
	 * @return void
	 */
	public function show_target_language_select() {
		$filter = Cookie::get_filter_cookie();
		?>
		<label for="target-language" class="hidden"></label>
		<input
				type="text"
				class="form-control"
				id="target-language"
				name="target_language"
				value="<?php echo esc_attr( implode( ', ', $filter->target ) ); ?>"
				placeholder="<?php esc_html_e( 'Target languages', 'gts-translation-order' ); ?>"
				readonly>
		<?php
	}

	/**
	 * Show target language popup.
	 *
	 * @return void
	 */
	public function show_target_language_popup() {
		$filter = Cookie::get_filter_cookie();
		$i      = 0;
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
							echo '<tr>';
							foreach ( $this->languages as $language ) {
								$i ++;
								?>
								<td class="cell">
									<input
											type="checkbox" name="regi_target_language[]"
											value="<?php echo esc_html( $language->language_name ); ?>"
											id="<?php echo esc_html( $language->language_name ); ?>"
											class="lang-checkbox"
										<?php echo in_array( $language->language_name, $filter->target, true ) ? 'checked' : ''; ?>
									/>
									<label for="<?php echo esc_html( $language->language_name ); ?>">
										<?php echo esc_html( $language->language_name ); ?>
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
								class="btn btn-primary"
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
	 * Get all post types name.
	 *
	 * @return array
	 */
	public function get_post_types() {
		return array_diff( get_post_types( [ 'public' => true ] ), self::EXCLUDE_POST_TYPES );
	}

	/**
	 * Show source languages select.
	 *
	 * @return void
	 */
	public function show_source_language() {
		$filter = Cookie::get_filter_cookie();
		?>
		<select
				class="form-select"
				name="gts_source_language"
				id="gts_source_language"
				aria-label="<?php esc_html_e( 'Source language', 'gts-translation-order' ); ?>">
			<option value="0"
					selected><?php esc_html_e( 'Source language', 'gts-translation-order' ); ?></option>
			<?php
			foreach ( $this->languages as $language ) {
				if ( $language->active ) {
					?>
					<option value="<?php echo esc_html( $language->language_name ); ?>" <?php selected( $language->language_name, $filter->source ); ?>>
						<?php echo esc_html( $language->language_name ); ?>
					</option>
					<?php
				}
			}
			?>
		</select>
		<?php
	}
}
