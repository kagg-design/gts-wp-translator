<?php
/**
 * Template translation order page in admin.
 *
 * @package gts/translation-order
 */

use GTS\TranslationOrder\Filter\PostFilter;

if ( ! defined( 'ABSPATH' ) ) {
	// @codeCoverageIgnoreStart
	exit;
	// @codeCoverageIgnoreEnd
}

$filter = new PostFilter();

?>
<div class="container" id="gts-translation-order">
	<div class="row">
		<div class="col">
			<div class="wrap">
				<h1 class="wp-heading-inline"><?php esc_attr_e( 'Pages for translation', 'gts-translation-order' ); ?></h1>
			</div>
		</div>
	</div>
	<form action="" id="filter-form" method="post">
		<div class="row">
			<div class="col-auto">
				<?php $filter->show_post_types_select(); ?>
			</div>
			<div class="col-auto">
				<?php $filter->show_search_field(); ?>
			</div>
			<div class="col-auto">
				<input type="submit" name="gts_filter_submit" class="btn-sm btn btn-primary" value="Submit">
			</div>
			<?php wp_nonce_field( 'gts_post_type_filter', 'gts_post_type_filter_nonce', false ); ?>
		</div>
	</form>
	<div class="row">
		<div class="col">
			<table class="table table-striped table-hover">
				<thead>
				<tr>
					<th scope="col">
						<label for="gts_to_all_page"></label>
						<input type="checkbox" name="gts_to_all_page" id="gts_to_all_page">
					</th>
					<th scope="col">
						<?php esc_attr_e( 'Title', 'gts-translation-order' ); ?>
					</th>
					<th scope="col"><?php esc_attr_e( 'Type', 'gts-translation-order' ); ?></th>
					<th scope="col"><?php esc_attr_e( 'Status', 'gts-translation-order' ); ?></th>
					<th scope="col"><?php esc_attr_e( 'Action', 'gts-translation-order' ); ?></th>
				</tr>
				</thead>
				<tbody class="table-group-divider">
				<?php $filter->show_table(); ?>
				</tbody>
				<caption>
					<div class="tablenav bottom">
						<div class="alignleft actions bulkactions">
							<button type="button" class="btn btn-primary btn-sm">
								<?php esc_attr_e( 'Add to Cart', 'gts-translation-order' ); ?>
							</button>
						</div>
					</div>
				</caption>
			</table>
			<?php // @todo Make pagination working. ?>
			<nav aria-label="<?php esc_attr_e( 'Pagination', 'gts-translation-order' ); ?>">
				<ul class="pagination">
					<li class="page-item disabled">
						<a class="page-link"><?php esc_html_e( 'Previous', 'gts-translation-order' ); ?></a>
					</li>
					<li class="page-item"><a class="page-link" href="#">1</a></li>
					<li class="page-item active" aria-current="page">
						<a class="page-link" href="#">2</a>
					</li>
					<li class="page-item"><a class="page-link" href="#">3</a></li>
					<li class="page-item">
						<a class="page-link" href="#"><?php esc_html_e( 'Next', 'gts-translation-order' ); ?></a>
					</li>
				</ul>
			</nav>
		</div>
	</div>
</div>
