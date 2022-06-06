<?php
/**
 * PostFilter class form filter in admin panel.
 *
 * @package GTS\GTSTranslationOrder\Filter
 */

namespace GTS\GTSTranslationOrder\Filter;

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
	private const TRANSIENT_NAME = 'gts_post_filter';

	/**
	 * Selected post type.
	 *
	 * @var string|mixed
	 */
	private string $post_type_select;

	/**
	 * Search string.
	 *
	 * @var string|mixed
	 */
	private string $search;

	/**
	 * PostFilter construct.
	 */
	public function __construct() {
		$this->init();
		$params                 = get_transient( self::TRANSIENT_NAME ) ?? '';
		$this->post_type_select = $params['post_type'] ?? 'null';
		$this->search           = $params['search'] ?? '';
	}

	/**
	 * Init Hooks.
	 *
	 * @return void
	 */
	private function init(): void {
		add_action( 'init', [ $this, 'filter_callback' ] );
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
		$post_type_select = get_transient( self::TRANSIENT_NAME );
		?>
		<select class="form-select" id="gts_to_post_type_select" aria-label="Post Type" name="gts_to_post_type_select">
			<option value="null" selected>Select post type</option>
			<?php foreach ( $this->get_post_types_array() as $type ) : ?>
				<option value="<?php echo esc_attr( $type ); ?>" <?php isset( $post_type_select['post_type'] ) ? selected( $post_type_select['post_type'], $type ) : ''; ?>>
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
		?>
		<input
				type="text"
				class="form-control"
				id="gts_to_search"
				name="gts_to_search"
				value="<?php echo esc_html( $this->search ); ?>"
				placeholder="Search to title">
		<?php
	}

	/**
	 * Filter form callback.
	 *
	 * @return void
	 */
	public function filter_callback(): void {

		if ( ! isset( $_POST['gts_filter_submit'] ) ) {
			return;
		}

		$nonce = isset( $_POST['gts_post_type_filter_nonce'] ) ? filter_var( wp_unslash( $_POST['gts_post_type_filter_nonce'] ), FILTER_SANITIZE_STRING ) : null;

		if ( ! wp_verify_nonce( $nonce, 'gts_post_type_filter' ) ) {
			add_action( 'admin_notices', 'GTS\GTSTranslationOrder\Admin\AdminNotice::bad_nonce_code' );

			return;
		}

		$post_type = ! empty( $_POST['gts_to_post_type_select'] ) ? filter_var( wp_unslash( $_POST['gts_to_post_type_select'] ), FILTER_SANITIZE_STRING ) : '';
		$search    = ! empty( $_POST['gts_to_search'] ) ? filter_var( wp_unslash( $_POST['gts_to_search'] ), FILTER_SANITIZE_STRING ) : '';

		$old_transient = get_transient( self::TRANSIENT_NAME );
		if ( $old_transient ) {
			delete_transient( self::TRANSIENT_NAME );
		}

		$param = [
			'post_type' => $post_type,
			'search'    => $search,
		];

		set_transient( self::TRANSIENT_NAME, $param, HOUR_IN_SECONDS );

	}


	/**
	 * Show table post to translate.
	 *
	 * @return void
	 */
	public function show_table(): void {
		$rows = $this->get_pots_by_post_type( $this->post_type_select, $this->search, 24, 0 )['posts'];

		foreach ( $rows as $row ) :
			?>
			<tr>
				<th scope="row">
					<input
							type="checkbox" name="gts_to_translate[<?php echo esc_attr( $row->id ); ?>]"
							id="gts_to_translate-<?php echo esc_attr( $row->id ); ?>">
				</th>
				<td><?php echo esc_html( $row->post_title ); ?></td>
				<td><?php echo esc_html( $row->post_type ); ?></td>
				<td><span class="badge bg-secondary">Not translated</span></td>
				<td>
					<a href="#" class="plus"><i class="bi bi-plus-square"></i></a>
				</td>
			</tr>
		<?php
		endforeach;
	}

	/**
	 * Get posts by post type.
	 *
	 * @param string|null $post_type    Post type.
	 * @param string|null $search       Search string.
	 * @param int         $count_output Count output post.
	 * @param int         $offset       Offset.
	 *
	 * @return array
	 */
	private function get_pots_by_post_type( string $post_type = null, string $search = null, int $count_output = 25, int $offset = 0 ): array {
		global $wpdb;

		$post_types = [];

		if ( null !== $post_type && 'null' !== $post_type ) {
			$post_types[] = $post_type;
		} else {
			foreach ( $this->get_post_types_array() as $type ) {
				$post_types[] = $type;
			}
		}

		$slq_post_type = $this->prepare_in( $post_types );

		$sql = "SELECT SQL_CALC_FOUND_ROWS id, post_title, post_type FROM `{$wpdb->prefix}posts` WHERE `post_type` IN ($slq_post_type)";

		if ( $search ) {
			$sql .= "AND `post_title` LIKE '$search'";
		}

		$sql .= 'LIMIT %d OFFSET %d';

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery
		// phpcs:disable WordPress.DB.DirectDatabaseQuery.NoCaching
		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared
		$result = $wpdb->get_results(
			$wpdb->prepare(
				$sql,
				esc_sql( $count_output ),
				esc_sql( $offset )
			)
		);

		$row_found = $wpdb->get_results( 'SELECT FOUND_ROWS();', ARRAY_N );
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.NoCaching
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared

		if ( ! $result ) {
			return [
				'posts'     => null,
				'row_found' => 0,
			];
		}

		return [
			'posts'     => $result,
			'row_found' => $row_found[0][0],
		];
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
}

