<?php
/**
 * Translation Cart.
 *
 * @package gts/translation-order
 */

namespace GTS\TranslationOrder\Cart;

/**
 * TranslationCart class file
 */
class TranslationCart {

	/**
	 * Languages list.
	 *
	 * @var array
	 */
	public array $language_list;

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
	 * TranslationCart construct.
	 */
	public function __construct() {
		// @todo Get it from GTS site and store in transient.
		$this->get_language_list();
	}

	/**
	 * Init language list.
	 *
	 * @return void
	 */
	private function get_language_list(): void {
		// @todo Get it from GTS site and store in transient.
		$request = file_get_contents( GTS_TRANSLATION_ORDER_PATH . '/languages/languages.json' );

		$data = json_decode( $request );

		$this->language_list = $data;
	}

	/**
	 * Show total form.
	 *
	 * @return void
	 */
	public function show_total_form(): void {
		?>
		<form action="" method="post">
			<div class="mb-3">
				<label for="gts_client_email" class="form-label">
					<?php esc_html_e( 'Email address', 'gts_translation_cart_nonce' ); ?>
				</label>
				<input
						type="email"
						name="gts_client_email"
						class="form-control"
						id="gts_client_email"
						value="<?php echo esc_html( get_option( 'admin_email' ) ) ?? ''; ?>"
						placeholder="name@example.com">
			</div>
			<div class="mb-3">
				<label for="language"><?php esc_html_e( 'Select Languages', 'gts_translation_cart_nonce' ); ?></label>
				<select
						class="form-select"
						id="language"
						name="gts_language_doc"
						aria-label="<?php esc_html_e( 'Select Languages', 'gts_translation_cart_nonce' ); ?>">
					<option value="0"
							selected><?php esc_html_e( 'Select Languages', 'gts_translation_cart_nonce' ); ?></option>
					<?php foreach ( $this->language_list as $item ) : ?>
						<option value="<?php echo esc_attr( $item->name ); ?>">
							<?php echo esc_html( $item->display_name ); ?>
						</option>
					<?php endforeach; ?>
				</select>
			</div>
			<div class="mb-3">
				<input
						type="text"
						class="form-control"
						id="target-language"
						placeholder="<?php esc_html_e( 'Select a target languages', 'gts_translation_cart_nonce' ); ?>"
						readonly>
			</div>
			<div class="mb-3">
				<label for="gts-industry"><?php esc_html_e( 'Select Industry', 'gts_translation_cart_nonce' ); ?></label>
				<select
						class="form-select"
						id="gts-industry"
						name="gts-industry"
						aria-label="<?php esc_html_e( 'Select Industry', 'gts_translation_cart_nonce' ); ?>">
					<option value="0" selected>
						<?php esc_html_e( 'Select Industry', 'gts_translation_cart_nonce' ); ?>
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
	public function show_pop_up_language(): void {
		?>
		<div class="modal modal-lg" tabindex="-1" id="language-modal">
			<div class="modal-dialog modal-dialog-centered">
				<div class="modal-content">
					<div class="modal-header">
						<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
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
											value="<?php echo esc_html( $lang->name ); ?>"
											id="<?php echo esc_html( $lang->name ); ?>"
											class="lang-checkbox"
									/>
									<label for="<?php echo esc_html( $lang->name ); ?>">
										<?php echo esc_html( $lang->display_name ); ?>
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
