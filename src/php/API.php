<?php
/**
 * GTS Translation Api.
 *
 * @package gts/translation-order
 */

namespace GTS\TranslationOrder;

use stdClass;
use WPForms\Integrations\LiteConnect\Integration;

/**
 * Api class file.
 */
class API {

	/**
	 * Max number of attempts for generate_site_key().
	 */
	const MAX_GENERATE_KEY_ATTEMPTS = 20;

	/**
	 * Generate key attempt counter.
	 */
	const GENERATE_KEY_ATTEMPT_COUNTER_OPTION = 'gts-translation-order-generate-key-attempt-counter';

	/**
	 * The create_not_logged_in_nonce() action.
	 */
	const KEY_ACTION = 'gts-translation-order-key-action';

	/**
	 * The generate_site_key() lock transient name.
	 */
	const SITE_KEY_LOCK = 'gts-translation-order-site-key-lock';

	/**
	 * The generate_access_token() lock transient name.
	 */
	const ACCESS_TOKEN_LOCK = 'gts-translation-order-access-token-lock';

	/**
	 * The $_GET argument to trigger the auth key endpoint.
	 */
	const AUTH_KEY_ARG = 'gts-translation-order-auth-key';

	/**
	 * Option name.
	 */
	const AUTH_OPTION = 'gts-translation-auth';





	/**
	 * Token name.
	 */
	const GTS_TOKEN_NAME = 'gts-translation-order-token';

	/**
	 * Language list transient.
	 */
	const LANGUAGE_LIST_TRANSIENT = 'gts-translation-order-languages_list';

	/**
	 * GTS site url.
	 */
	const GTS_SITE = 'https://www.gts-translation.com';

	/**
	 * GTS site REST url prefix.
	 */
	const REST_URL_PREFIX = 'wp-json';

	/**
	 * REST namespace.
	 */
	const REST_NAMESPACE = 'gts-translation-order/v1';

	/**
	 * Price list.
	 */
	const PRICES_TRANSIENT = 'get_price_languages_list';

	/**
	 * Token Access.
	 *
	 * @var string $token Token.
	 */
	public $token;

	/**
	 * Server URL.
	 *
	 * @var string.
	 */
	private $server_url;

	/**
	 * Api construct.
	 */
	public function __construct() {
		$site = self::GTS_SITE;

		if ( defined( 'GTS_TRANSLATION_ORDER_DEBUG_SITE' ) && GTS_TRANSLATION_ORDER_DEBUG_SITE ) {
			$site = GTS_TRANSLATION_ORDER_DEBUG_SITE;
		}

		$this->server_url = $site . '/' . self::REST_URL_PREFIX . '/' . self::REST_NAMESPACE . '/';

		$this->endpoints();

		$this->site_key = $this->get_site_key();
		$this->token = get_option( self::GTS_TOKEN_NAME, '' );
	}

	/**
	 * Get the site key.
	 *
	 * @return array|false The site key, or false on error.
	 */
	private function get_site_key() {

		// If site key already exists, then we won't need to regenerate it.
		$auth = get_option( self::AUTH_OPTION, [] );

		if ( ! empty( $auth['key'] ) ) {
			return $auth['key'];
		}

		// Generate the site key.
		$this->generate_site_key();

		return false;
	}

	/**
	 * Generate the site key.
	 */
	private function generate_site_key() {

		if ( $this->is_max_generate_key_attempts_reached() ) {
			return;
		}

		if ( get_transient( self::SITE_KEY_LOCK ) ) {
			return;
		}

		set_transient( self::SITE_KEY_LOCK, true, MINUTE_IN_SECONDS );

		$admin_email = get_option( 'admin_email' );
		$admin       = get_user_by( 'email', $admin_email );
		$data        = [
			'domain'      => home_url(),
			'admin_email' => $admin_email,
			'first_name'  => ! empty( $admin->first_name ) ? $admin->first_name : '',
			'last_name'   => ! empty( $admin->last_name ) ? $admin->last_name : '',
			'nonce'       => $this->create_not_logged_in_nonce(),
			'callback'    => add_query_arg( [ self::AUTH_KEY_ARG => '' ], trailingslashit( home_url() ) ),
		];

		$response = wp_remote_get(
			$this->server_url . 'request-token',
			[ 'body' => $data ]
		);

		if ( false !== $response ) {
			delete_transient( self::SITE_KEY_LOCK );
		}

		$this->update_generate_key_attempts_count();

		// At this point, we do not have the site key.
		// It will be sent to our callback url.
	}


	/**
	 * Request token.
	 *
	 * @return void
	 */
	public function request_token() {
		$response = wp_remote_get(
			$this->server_url . 'request-token',
			[
				'body' => [
					'token' => $this->token,
				],
			]
		);
	}

	/**
	 * Get language list.
	 *
	 * @return array
	 */
	public function get_language_list() {

//		$language_list = get_transient( self::LANGUAGE_LIST_TRANSIENT );
//
//		if ( false !== $language_list ) {
//			return $language_list;
//		}

		$response = $this->request(
			$this->server_url . 'languages',
			[
				'body' => [
					'token' => $this->token,
				],
			]
		);

		$response = $response ?: [];

		set_transient( self::LANGUAGE_LIST_TRANSIENT, $response, DAY_IN_SECONDS );

		return $response;
	}

	/**
	 * Get prices.
	 *
	 * @return array
	 */
	public function get_prices() {
		$prices = get_transient( self::PRICES_TRANSIENT );

		if ( false !== $prices ) {
			return $prices;
		}

		$response = $this->request(
			$this->server_url . 'prices',
			[
				'body' => [
					'token' => $this->token,
				],
			]
		);

		$response = $response ?: [];

		set_transient( self::PRICES_TRANSIENT, $response, DAY_IN_SECONDS );

		return $response;
	}

	/**
	 * Make a request to the server.
	 *
	 * @param string $url  Server url.
	 * @param array  $args Arguments.
	 *
	 * @return mixed|false
	 */
	private function request( $url, $args ) {

		$response = wp_remote_get(
			$url,
			$args
		);

		if ( is_wp_error( $response ) || 200 !== $response['response']['code'] ) {
			return false;
		}

		$result = json_decode( $response['body'] );

		return $result ?: false;
	}

	/**
	 * Check that we have not reached the max number of attempts to get keys from API using generate_keys().
	 *
	 * @since {VERSION}
	 *
	 * @return bool
	 */
	private function is_max_generate_key_attempts_reached() {

		$attempts_count = get_option( self::GENERATE_KEY_ATTEMPT_COUNTER_OPTION, 0 );

		return $attempts_count >= self::MAX_GENERATE_KEY_ATTEMPTS;
	}

	/**
	 * Update count of the attempts to get keys from API using generate_keys().
	 * It allows us to prevent sending requests to the API server infinitely.
	 */
	private function update_generate_key_attempts_count() {

		global $wpdb;

		// Store actual attempt counter value to the option.
		// We need here an atomic operation to avoid race conditions with getting site key via callback.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery
		$wpdb->query(
			$wpdb->prepare(
				"INSERT INTO $wpdb->options
				(option_name, option_value, autoload)
                VALUES ( %s, 1, 'no' )
				ON DUPLICATE KEY UPDATE
					option_value = option_value + 1",
				self::GENERATE_KEY_ATTEMPT_COUNTER_OPTION
			)
		);

		wp_cache_delete( self::GENERATE_KEY_ATTEMPT_COUNTER_OPTION, 'options' );
	}

	/**
	 * Create not logged in nonce.
	 * We need it, because callback from the server to the client site will be processed as not logged in.
	 *
	 * @return string
	 */
	private function create_not_logged_in_nonce() {

		$user    = wp_get_current_user();
		$user_id = $user ? $user->ID : 0;

		wp_set_current_user( 0 );

		$saved_cookie = $_COOKIE;
		$_COOKIE      = [];
		$nonce        = wp_create_nonce( self::KEY_ACTION );
		$_COOKIE      = $saved_cookie;

		wp_set_current_user( $user_id );

		return $nonce;
	}

	/**
	 * Provide responses to endpoint requests.
	 */
	private function endpoints() {

		// We check nonce in the endpoint_key().
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( ! isset( $_GET[ self::AUTH_KEY_ARG ] ) ) {
			return;
		}

		$this->endpoint_key();
	}

	/**
	 * Process endpoint for callback on generate_site_key().
	 */
	private function endpoint_key() {

		$json     = file_get_contents( 'php://input' );
		$response = json_decode( $json, true );

		if ( ! $response ) {
			$this->endpoint_die( 'API: No response.' );
		}

		if ( isset( $response['error'] ) ) {
			$this->endpoint_die(
				'API: Unable to add the site to system.',
				$response
			);
		}

		if ( ! isset( $response['key'], $response['id'], $response['nonce'] ) ) {
			$this->endpoint_die(
				'API: Unknown communication error.',
				$response
			);
		}

		if ( ! wp_verify_nonce( $response['nonce'], self::KEY_ACTION ) ) {
			$this->endpoint_die(
				'API: Nonce verification failed.',
				$response
			);
		}

		unset( $response['nonce'] );

		$auth         = get_option( self::AUTH_OPTION, [] );
		$auth['site'] = $response;

		update_option( self::GENERATE_KEY_ATTEMPT_COUNTER_OPTION, 0 );
		update_option( self::AUTH_OPTION, $auth );

		exit();
	}

	/**
	 * Finish the endpoint execution with wp_die().
	 *
	 * @param string $title    Log message title.
	 * @param array  $response Response.
	 *
	 * @noinspection ForgottenDebugOutputInspection
	 */
	private function endpoint_die( $title = '', $response = [] ) { // phpcs:ignore WPForms.PHP.HooksMethod.InvalidPlaceForAddingHooks

		$this->log( $title, $response );

		// We call wp_die too early, before the query is run.
		// So, we should remove some filters to avoid having PHP notices in error log.
		remove_filter( 'wp_robots', 'wp_robots_noindex_embeds' );
		remove_filter( 'wp_robots', 'wp_robots_noindex_search' );

		wp_die(
			esc_html__( 'This is the GTS Translation Order endpoint page.', 'gts-translation-order' ),
			'GTS Translation Order endpoint',
			400
		);
	}

	/**
	 * Log message.
	 *
	 * @param string $title    Log message title.
	 * @param array  $response Response.
	 *
	 * @noinspection ForgottenDebugOutputInspection
	 */
	private function log( $title = '', $response = [] ) {

		if ( ! $title ) {
			return;
		}

		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
		error_log(
			$title,
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_print_r
			print_r( [ 'response' => $response ] )
		);
	}
}
