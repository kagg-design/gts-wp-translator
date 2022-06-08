<?php
/**
 * PostFilter class form filter in admin panel.
 *
 * @package gts/translation-order
 */

namespace GTS\TranslationOrder\Filter;

use GTS\TranslationOrder\Admin\AdminNotice;
use wpdb;

/**
 * PostFilter class file.
 */
class PostFilter {

	/**
	 * Exclude post types.
	 */
	private const EXCLUDE_POST_TYPES = [
		'attachment',
		'revision',
		'nav_menu_item',
		'clients',
		'notification',
	];

	/**
	 * Transient name.
	 */
	private const COOKIE_NAME = 'gts_post_filter';

	/**
	 * PostFilter construct.
	 */
	public function __construct() {
		$this->init();
	}

	/**
	 * Init Hooks.
	 *
	 * @return void
	 */
	private function init(): void {
		add_action( 'init', [ $this, 'filter' ] );
	}

	/**
	 * Get all post types name.
	 *
	 * @return array
	 */
	private function get_post_types_array(): array {
		return array_diff( get_post_types( [ 'public' => true ] ), self::EXCLUDE_POST_TYPES );
	}

	/**
	 * Show Post Types select.
	 *
	 * @return void
	 */
	public function show_post_types_select(): void {
		$post_type_select = $this->get_cookie();
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
	 * Show Search field.
	 *
	 * @return void
	 */
	public function show_search_field(): void {
		$searach = $this->get_cookie()->search ?? '';
		?>
		<label for="gts_to_search"></label>
		<input
				type="text"
				class="form-control"
				id="gts_to_search"
				name="gts_to_search"
				value="<?php echo esc_html( $searach ); ?>"
				placeholder="Search to title">
		<?php
	}

	/**
	 * Filter form.
	 *
	 * @return void
	 */
	public function filter(): void {

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

		$param = [
			'post_type' => $post_type,
			'search'    => $search,
		];

		$this->set_cookie( $param );
	}


	/**
	 * Show table post to translate.
	 *
	 * @return void
	 */
	public function show_table(): void {

		$filter_params = $this->get_cookie();
		$posts         = $this->get_pots_by_post_type( $filter_params->post_type, $filter_params->search, 24, 0 )['posts'];

		foreach ( $posts as $post ) {
			$title = $post->post_title;
			$title = $title ?: __( '(no title)', 'gts-translation-order' );
			$id    = "gts_to_translate-$post->id";
			$name  = "gts_to_translate[$post->id]";

			?>
			<tr>
				<th scope="row">
					<input
							type="checkbox"
							id="<?php echo esc_attr( $id ); ?>"
							name="<?php echo esc_attr( $name ); ?>">
				</th>
				<td><?php echo esc_html( $title ); ?></td>
				<td><?php echo esc_html( $post->post_type ); ?></td>
				<td><span class="badge bg-secondary">Not translated</span></td>
				<td>
					<a href="#" class="plus"><i class="bi bi-plus-square"></i></a>
				</td>
			</tr>
			<?php
		}
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
	private function get_pots_by_post_type( string $post_type = null, string $search = null, int $number = 25, int $offset = 0 ): array {
		global $wpdb;

		$post_types = [ $post_type ];

		if ( null === $post_type || 'null' === $post_type ) {
			$post_types = $this->get_post_types_array();
		}

		$slq_post_type = $this->prepare_in( $post_types );

		$sql = "SELECT SQL_CALC_FOUND_ROWS id, post_title, post_type FROM `{$wpdb->prefix}posts` WHERE `post_type` IN ($slq_post_type)";

		if ( $search ) {
			$sql .= "AND `post_title` LIKE '$search'";
		}

		$sql .= 'LIMIT %d OFFSET %d';

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
				'posts'      => null,
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
	private function prepare_in( $items, string $format = '%s' ): string {
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
	 * Set cookie.
	 *
	 * @param array|string|int $values Value.
	 *
	 * @return void
	 */
	private function set_cookie( $values ): void {

		if ( is_array( $values ) ) {
			$values = wp_json_encode( $values, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE );
		}

		setcookie( self::COOKIE_NAME, $values, strtotime( '+30 days' ), COOKIEPATH, COOKIE_DOMAIN );

		$_COOKIE[ self::COOKIE_NAME ] = $values;
	}

	/**
	 * Get cookie filter params.
	 *
	 * @return object
	 */
	private function get_cookie(): object {
		if ( isset( $_COOKIE[ self::COOKIE_NAME ] ) ) {
			return (object) json_decode( filter_var( wp_unslash( $_COOKIE[ self::COOKIE_NAME ] ) ) );
		}

		return (object) null;
	}
}
