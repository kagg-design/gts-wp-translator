<?php
/**
 * Translation cart page.
 *
 * @package gts/translation-order
 */

namespace GTS\TranslationOrder\Pages;

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
	public const GTS_INDUSTRY_LIST = [
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
	 * TranslationOrder class file.
	 */
	public function __construct() {
		$this->init();

		$api                 = new GTS_API();
		$this->language_list = $api->get_languages_list();
	}

	/**
	 * Init hooks.
	 *
	 * @return void
	 */
	public function init(): void {

	}

	/**
	 * Show translation cart.
	 *
	 * @return void
	 */
	public function show_translation_cart(): void {
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
					<table class="table table-striped table-hover">
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
						<tr>
							<td>English Translation Services</td>
							<td>Page</td>
							<td>$300</td>
							<td>
								<a href="#" class="plus"><i class="bi bi-dash-square"></i></a>
							</td>
						</tr>
						<tr>
							<td>English Translation Services</td>
							<td>Page</td>
							<td>$300</td>
							<td>
								<a href="#" class="plus"><i class="bi bi-dash-square"></i></a>
							</td>
						</tr>
						<tr>
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
	private function show_total_form(): void {
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
						value="<?php echo esc_html( wp_get_current_user()->user_email ) ?? ''; ?>"
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
							<option value="<?php echo esc_attr( $item->language_name ); ?>">
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
	private function show_pop_up_language(): void {
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
}
