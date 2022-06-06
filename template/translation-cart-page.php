<?php
/**
 * Template translation cart page in admin.
 *
 * @package GTS\GTSTranslationOrder
 */


use GTS\GTSTranslationOrder\Cart\TranslationCart;

$cart = new TranslationCart();
?>

<div class="container">
	<div class="row">
		<div class="col-auto">
			<div class="wrap">
				<h1 class="wp-heading-inline"><?php esc_attr_e( 'Translation Cart', 'gts_translation_order' ); ?></h1>
			</div>
		</div>
	</div>
	<div class="row">
		<div class="col-auto">
			<table class="table table-striped table-hover">
				<thead>
				<tr>
					<th scope="col">
						<input type="checkbox" name="gts_to_all_page" id="gts_to_all_page">
					</th>
					<th scope="col">
						<?php esc_attr_e( 'Title', 'gts_translation_order' ); ?>
					</th>
					<th scope="col"><?php esc_attr_e( 'Type', 'gts_translation_order' ); ?></th>
					<th scope="col"><?php esc_attr_e( 'Cost', 'gts_translation_order' ); ?></th>
					<th scope="col"><?php esc_attr_e( 'Action', 'gts_translation_order' ); ?></th>
				</tr>
				</thead>
				<tbody class="table-group-divider">
				<tr>
					<th scope="row">
						<input
								type="checkbox" name="gts_to_translate[]"
								id="gts_to_translate-">
					</th>
					<td>English Translation Services</td>
					<td>Page</td>
					<td>$300</td>
					<td>
						<a href="#" class="plus"><i class="bi bi-dash-square"></i></a>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<input
								type="checkbox" name="gts_to_translate[]"
								id="gts_to_translate-">
					</th>
					<td>English Translation Services</td>
					<td>Page</td>
					<td>$300</td>
					<td>
						<a href="#" class="plus"><i class="bi bi-dash-square"></i></a>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<input
								type="checkbox" name="gts_to_translate[]"
								id="gts_to_translate-">
					</th>
					<td>English Translation Services</td>
					<td>Page</td>
					<td>$300</td>
					<td>
						<a href="#" class="plus"><i class="bi bi-dash-square"></i></a>
					</td>
				</tr>
				</tbody>
			</table>
		</div>
		<div class="col-auto">
			<table class="table table-dark table-striped total-table">
				<tr>
					<td>Total Cost:</td>
					<td>$900</td>
				</tr>
				<tr>
					<td colspan="2">
						<?php $cart->show_total_form(); ?>
					</td>
				</tr>
				<tr>
					<td>Send to Translation:</td>
					<td>
						<button type="button" class="btn btn-primary">Send</button>
					</td>
				</tr>
			</table>
		</div>
	</div>
</div>
<?php $cart->show_pop_up_language(); ?>

