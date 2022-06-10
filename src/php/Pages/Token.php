<?php
/**
 * Generate token from access to send translation.
 *
 * @package  gts/translation-order
 */

namespace GTS\TranslationOrder\Pages;

use Exception;
use GTS\TranslationOrder\Admin\AdminNotice;
use GuzzleHttp\Client;

/**
 * Token class file.
 */
class Token {

	/**
	 * Token name.
	 */
	private const GTS_TOKEN_NAME = 'gts_translation_token';

	/**
	 * Debug mode.
	 */
	private const GTS_REST_DEBUG = true;

	/**
	 * Token Access.
	 *
	 * @var string
	 */
	public string $token;

	/**
	 * Server URL.
	 *
	 * @var string
	 */
	private string $url_server;

	/**
	 * Http Client GuzzleHttp.
	 *
	 * @var Client
	 */
	private Client $client;

	/**
	 * Token construct.
	 */
	public function __construct() {
		$this->token = get_option( self::GTS_TOKEN_NAME ) ?? '';
		$this->init();

		if ( self::GTS_REST_DEBUG ) {
			$this->url_server = 'http://gts.docker.localhost:8080/wp-json/gts-translation-order/v1/';
		} else {
			$this->url_server = 'https://www.gts-translation.com/wp-json/gts-translation-order/v1/';
		}

		$this->client = new Client();
	}

	/**
	 * Init hooks.
	 *
	 * @return void
	 */
	public function init(): void {
		add_action( 'init', [ $this, 'save_token' ] );
	}

	/**
	 * Show Token page.
	 *
	 * @return void
	 */
	public function show_token_page(): void {
		?>
		<div class="container" id="gts-translation-token">
			<div class="row">
				<div class="col-auto">
					<div class="wrap">
						<h1 class="wp-heading-inline"><?php esc_html_e( 'Token Settings', 'gts-translation-order' ); ?></h1>
					</div>
				</div>
			</div>
			<div class="row">
				<div class="col-auto">
					<form action="" id="gts_token_form" class="row" method="post">
						<div class="col-auto">
							<div class="input-group mb-3">
								<label for="gts_token" class="hidden"></label>
								<input
										class="form-control"
										type="password"
										placeholder="<?php esc_html_e( 'Generate token', 'gts-translation-order' ); ?>"
										name="gts_token"
										id="gts_token"
										value="<?php echo esc_attr( $this->token ); ?>"
										disabled>
								<span class="input-group-text" id="eye_btn"><i class="bi bi-eye-fill"></i></span>
							</div>
						</div>
						<div class="col-auto">
							<input
									type="submit"
									name="generate_token"
									id="generate_token"
									value="<?php esc_html_e( 'Generate', 'gts-translation-order' ); ?>"
									class="btn btn-primary">
							<?php wp_nonce_field( 'gts_generate_token', 'gts_generate_token_nonce', false ); ?>
						</div>
					</form>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Save Token.
	 *
	 * @return false
	 */
	public function save_token() {

		if ( ! isset( $_POST['generate_token'] ) ) {
			return;
		}

		$nonce = isset( $_POST['gts_generate_token_nonce'] ) ? filter_var( wp_unslash( $_POST['gts_generate_token_nonce'] ), FILTER_SANITIZE_STRING ) : '';

		if ( ! wp_verify_nonce( $nonce, 'gts_generate_token' ) ) {
			add_action( 'admin_notices', [ AdminNotice::class, 'bad_nonce' ] );

			return;
		}

		$bytes = random_bytes( 20 );
		// phpcs:disable WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
		$token = base64_encode( bin2hex( $bytes ) );
		// phpcs:enable WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode

		if ( empty( $token ) ) {
			add_action( 'admin_notices', [ AdminNotice::class, 'empty_token' ] );

			return;
		}

		try {
			$response = $this->client->request(
				'POST',
				$this->url_server . 'add-token/',
				[
					'json' => [
						'token'         => $token,
						'reference_url' => get_bloginfo( 'url' ),
						'ip_server'     => isset( $_SERVER['SERVER_ADDR'] ) ? filter_var( wp_unslash( $_SERVER['SERVER_ADDR'] ), FILTER_VALIDATE_IP ) : null,
					],
					[ 'debug' => true ],
				]
			);
		} catch ( Exception $e ) {
			return;
		}

		var_dump( $response->getBody() );

		update_option( self::GTS_TOKEN_NAME, $token, 'no' );

		$this->token = $token;

	}
}
