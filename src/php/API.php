<?php
/**
 * GTS Translation Api.
 *
 * @package gts/translation-order
 */

namespace GTS\TranslationOrder;

use stdClass;

/**
 * Api class file.
 */
class API {

	/**
	 * Token name.
	 */
	const GTS_TOKEN_NAME = 'gts-translation-order-token';

	/**
	 * Language list transient.
	 */
	const LANGUAGES_LIST_TRANSIENT = 'gts-translation-order-languages_list';


	/**
	 * Token Access.
	 *
	 * @var string $token Token.
	 */
	public $token;

	/**
	 * Server URL.
	 *
	 * @var string $url_server Server URL.
	 */
	private $url_server;

	/**
	 * Api construct.
	 */
	public function __construct() {
		$this->token = get_option( self::GTS_TOKEN_NAME, '' );

		if ( GTS_REST_DEBUG ) {
			$this->url_server = GTS_REST_DEBUG_URL;
		} else {
			$this->url_server = GTS_REST_URL;
		}

	}

	/**
	 * Created transient languages list.
	 *
	 * @return array
	 */
	public function get_languages_list() {

		$languages_list = get_transient( self::LANGUAGES_LIST_TRANSIENT );

		if ( $languages_list ) {
			return $languages_list;
		}

		$response = wp_remote_get(
			$this->url_server . 'get-language',
			[
				'body' => [
					'token' => $this->token,
				],
			]
		);

		if ( is_wp_error( $response ) || 200 !== $response['response']['code'] ) {
			return [];
		}

		$response = json_decode( $response['body'] );

		set_transient( self::LANGUAGES_LIST_TRANSIENT, $response, DAY_IN_SECONDS );

		return $response;
	}

	/**
	 * Create transient price languages list.
	 *
	 * @return object|stdClass|null
	 */
	public function get_price_languages_list() {
		$prices = get_transient( 'get_price_languages_list' );

		if ( ! $prices ) {
			$response = wp_remote_request(
				$this->url_server . 'get-price-language',
				[
					'method' => 'GET',
					'body'   => [
						'token' => $this->token,
					],
				]
			);

			$response = json_decode( $response['body'] );

			if ( is_wp_error( $response ) ) {
				return (object) null;
			}

			set_transient( 'get_price_languages_list', $response, DAY_IN_SECONDS );

			return $response;
		}

		return $prices;
	}
}
