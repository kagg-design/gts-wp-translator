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
class GTS_API {

	/**
	 * Token name.
	 */
	private const GTS_TOKEN_NAME = 'gts_translation_token';


	/**
	 * Token Access.
	 *
	 * @var string $token Token.
	 */
	public string $token;

	/**
	 * Server URL.
	 *
	 * @var string $url_server Server URL.
	 */
	private string $url_server;

	/**
	 * Api construct.
	 */
	public function __construct() {
		$this->token = get_option( self::GTS_TOKEN_NAME ) ?? '';

		if ( GTS_REST_DEBUG ) {
			$this->url_server = GTS_DEBUG_REST_URL;
		} else {
			$this->url_server = GTS_REST_URL;
		}

	}

	/**
	 * Created transient languages list.
	 *
	 * @return object|stdClass|null
	 */
	public function get_languages_list() {

		$languages_list = get_transient( 'gts_languages_list' );

		if ( ! $languages_list ) {
			$response = wp_remote_request(
				$this->url_server . 'get-language',
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

			set_transient( 'gts_languages_list', $response, DAY_IN_SECONDS );

			return $response;
		}

		return $languages_list;
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
