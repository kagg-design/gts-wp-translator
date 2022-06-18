<?php
/**
 * PostFilter class form filter in admin panel.
 *
 * @package gts/translation-order
 */

namespace GTS\TranslationOrder\Filter;

use GTS\TranslationOrder\Admin\AdminNotice;
use GTS\TranslationOrder\Cost;
use GTS\TranslationOrder\API;
use GTS\TranslationOrder\Pagination;
use wpdb;

/**
 * PostFilter class file.
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
	 * Cookie filter name.
	 */
	const COOKIE_FILTER_NAME = 'gts_post_filter';

	/**
	 * Cookie cart name.
	 */
	const COOKIE_CART_NAME = 'gts_cart_data';

	/**
	 * Limit output posts.
	 */
	const LIMIT_OUTPUT = 50;

	/**
	 * Page number.
	 *
	 * @var int $page Page number.
	 */
	private $page;

	/**
	 * Pagination Class.
	 *
	 * @var Pagination $pagintion pagination.
	 */
	public $pagination;

	/**
	 * Count posts.
	 *
	 * @var int $count_posts count post
	 */
	public $count_posts;

	/**
	 * Language list.
	 *
	 * @var array
	 */
	private $language_list;

	/**
	 * Cost calculation.
	 *
	 * @var Cost $cost Cost class.
	 */
	private $cost;

	/**
	 * PostFilter construct.
	 */
	public function __construct() {
		$this->init();

		$api                 = new API();
		$this->language_list = $api->get_language_list();

		$this->cost = new Cost();
	}

	/**
	 * Init Hooks.
	 *
	 * @return void
	 */
	private function init() {
		add_action( 'init', [ $this, 'filter' ] );

		$this->page = 1;

		$paging = filter_input( INPUT_GET, 'paging', FILTER_VALIDATE_INT );

		if ( $paging >= 1 ) {
			$this->page = $paging;
		}
	}

	/**
	 * Get all post types name.
	 *
	 * @return array
	 */
	private function get_post_types_array() {
		return array_diff( get_post_types( [ 'public' => true ] ), self::EXCLUDE_POST_TYPES );
	}

	/**
	 * Show Post Types select.
	 *
	 * @return void
	 */
	public function show_post_types_select() {
		$post_type_select = $this->get_cookie( self::COOKIE_FILTER_NAME );
		?>
		<select class="form-select" id="gts_to_post_type_select" aria-label="Post Type" name="gts_to_post_type_select">
			<option value="null" selected><?php esc_html_e( 'Select post type', 'gts-translation-order' ); ?></option>
			<?php foreach ( $this->get_post_types_array() as $type ) : ?>
				<option value="<?php echo esc_attr( $type ); ?>" <?php echo isset( $post_type_select->post_type ) ? selected( $post_type_select->post_type, $type, false ) : ''; ?>>
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
		$cookie = $this->get_cookie( self::COOKIE_FILTER_NAME );
		$search = isset( $cookie->search ) ? $cookie->search : '';
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
	public function show_target_select_language() {
		$target_select = $this->get_cookie( self::COOKIE_FILTER_NAME );
		?>
		<label for="target-language" class="hidden"></label>
		<input
				type="text"
				class="form-control"
				id="target-language"
				name="target_language"
				value="<?php echo esc_attr( implode( ', ', $target_select->target ) ); ?>"
				placeholder="<?php esc_html_e( 'Select a target languages', 'gts-translation-order' ); ?>"
				readonly>
		<?php
	}

	/**
	 * Show pop-up target language
	 *
	 * @return void
	 */
	public function show_pop_up_language() {
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
	 * Show source languages select.
	 *
	 * @return void
	 */
	public function show_source_language() {
		$source_select = $this->get_cookie( self::COOKIE_FILTER_NAME );
		?>
		<select
				class="form-select"
				name="gts_source_language"
				aria-label="<?php esc_html_e( 'Source language', 'gts-translation-order' ); ?>">
			<option value="0"
					selected><?php esc_html_e( 'Select a source language', 'gts-translation-order' ); ?></option>
			<?php
			foreach ( $this->language_list as $language ) {
				if ( $language->active ) {
					?>
					<option value="<?php echo esc_html( $language->language_name ); ?>" <?php selected( $language->language_name, $source_select->source ); ?>>
						<?php echo esc_html( $language->language_name ); ?>
					</option>
					<?php
				}
			}
			?>
		</select>
		<?php
	}

	/**
	 * Filter form.
	 *
	 * @return void
	 */
	public function filter() {

		if ( ! isset( $_POST['gts_filter_submit'] ) ) {
			return;
		}

		$nonce = isset( $_POST['gts_post_type_filter_nonce'] ) ? filter_var( wp_unslash( $_POST['gts_post_type_filter_nonce'] ), FILTER_SANITIZE_STRING ) : null;

		if ( ! wp_verify_nonce( $nonce, 'gts_post_type_filter' ) ) {
			add_action( 'admin_notices', [ AdminNotice::class, 'bad_nonce' ] );

			return;
		}

		$post_type = ! empty( $_POST['gts_to_post_type_select'] ) ? filter_var( wp_unslash( $_POST['gts_to_post_type_select'] ), FILTER_SANITIZE_STRING ) : '';
		$search    = ! empty( $_POST['gts_to_search'] ) ? filter_var( wp_unslash( $_POST['gts_to_search'] ), FILTER_SANITIZE_STRING ) : '';
		$source    = ! empty( $_POST['gts_source_language'] ) ? filter_var( wp_unslash( $_POST['gts_source_language'] ), FILTER_SANITIZE_STRING ) : '';
		$target    = ! empty( $_POST['target_language'] ) ? filter_var( wp_unslash( $_POST['target_language'] ), FILTER_SANITIZE_STRING ) : '';

		$param = [
			'post_type' => $post_type,
			'search'    => $search,
			'source'    => $source,
			'target'    => explode( ', ', $target ),
		];

		$this->set_cookie( self::COOKIE_FILTER_NAME, $param );
	}


	/**
	 * Show table post to translate.
	 *
	 * @return void
	 */
	public function show_table() {

		$filter_params = $this->get_cookie( self::COOKIE_FILTER_NAME );
		$cart_post_id  = (array) $this->get_cookie( self::COOKIE_CART_NAME );
		if ( ! isset( $filter_params->post_type ) ) {
			$filter_params = (object) [
				'post_type' => 'null',
				'search'    => '',
				'source'    => '',
				'target'    => [],

			];
		}
		$limit = self::LIMIT_OUTPUT;
		$posts = $this->get_posts_by_post_type( $filter_params->post_type, $filter_params->search, ( $this->page - 1 ) * $limit, $limit );

		$curr_page_url = isset( $_SERVER['QUERY_STRING'] ) ? 'admin.php?' . filter_var( wp_unslash( $_SERVER['QUERY_STRING'] ), FILTER_SANITIZE_STRING ) : '';

		$this->count_posts = 0;
		$count             = $posts['rows_found'];

		if ( $count > 0 ) {
			$p = new Pagination();
			$p->items( $count );
			$p->limit( $limit ); // Limit entries per page.
			$p->target( $curr_page_url );
			$p->currentPage( $this->page ); // Gets and validates the current page.
			$p->parameterName( 'paging' );
			$p->adjacents( 1 ); // No. of page away from the current page.
			$p->calculate(); // Calculates what to show.

			$this->pagination  = $p;
			$this->count_posts = $count;
		} else {
			?>
			<tr>
				<td colspan="6">
					<?php esc_html_e( 'Post not found', 'gts-translation-order' ); ?>
				</td>
			</tr>
			<?php
		}

		foreach ( $posts['posts'] as $post ) {
			$title        = $post->post_title;
			$title        = $title ?: __( '(no title)', 'gts-translation-order' );
			$id           = "gts_to_translate-$post->id";
			$name         = "gts_to_translate[$post->id]";
			$icon_class   = in_array( $post->id, $cart_post_id, true ) ? 'bi-dash-square' : 'bi-plus-square';
			$button_class = in_array( $post->id, $cart_post_id, true ) ? 'remove-to-cart' : 'add-to-cart';
			$price        = 0;

			if ( ! empty( $filter_params->source ) && ! empty( $filter_params->target ) ) {
				$price = $this->cost->price_by_post( $filter_params->source, $filter_params->target, $post->id );
			}
			?>
			<tr>
				<th scope="row">
					<label for="<?php echo esc_attr( $id ); ?>" class="hidden"></label>
					<input
							type="checkbox"
							data-id="<?php echo esc_attr( $post->id ); ?>"
							id="<?php echo esc_attr( $id ); ?>"
							name="<?php echo esc_attr( $name ); ?>">
				</th>
				<td><?php echo esc_html( $title ); ?></td>
				<td><?php echo esc_html( $post->post_type ); ?></td>
				<td><span class="badge bg-secondary">Not translated</span></td>
				<td>$<?php echo esc_html( $price ); ?> </td>
				<td>
					<a
							href="#" data-post_id="<?php echo esc_attr( $post->id ); ?>"
							class="plus <?php echo esc_attr( $button_class ); ?>">
						<i class="bi <?php echo esc_attr( $icon_class ); ?>"></i>
					</a>
				</td>
			</tr>
			<?php
		}
	}

	/**
	 * Set cookie.
	 *
	 * @param string           $name   Name cookie.
	 * @param array|string|int $values Value.
	 *
	 * @return void
	 */
	private function set_cookie( $name, $values ) {

		if ( is_array( $values ) ) {
			$values = wp_json_encode( $values, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE );
		}

		setcookie( $name, $values, strtotime( '+30 days' ), COOKIEPATH, COOKIE_DOMAIN );

		$_COOKIE[ $name ] = $values;
	}

	/**
	 * Get posts by post type.
	 *
	 * @param string|null $post_type Post type.
	 * @param string|null $search    Search string.
	 * @param int         $number    Number of post to output.
	 * @param int         $offset    Offset.
	 *
	 * @return array
	 */
	private function get_posts_by_post_type( $post_type = null, $search = null, $number = 25, $offset = 0 ) {
		global $wpdb;

		$post_types = [ $post_type ];

		if ( null === $post_type || 'null' === $post_type ) {
			$post_types = $this->get_post_types_array();
		}

		$slq_post_type = $this->prepare_in( $post_types );

		$sql = "SELECT SQL_CALC_FOUND_ROWS id, post_title, post_type FROM `{$wpdb->prefix}posts` WHERE `post_type` IN ($slq_post_type)";

		if ( $search ) {
			$sql .= "AND `post_title` LIKE '%" . $wpdb->esc_like( $search ) . "%'";
		}

		$sql .= 'LIMIT %d, %d';

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared
		$result = $wpdb->get_results(
			$wpdb->prepare(
				$sql,
				esc_sql( $number ),
				esc_sql( $offset )
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
}
