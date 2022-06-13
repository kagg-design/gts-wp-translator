<?php
/**
 * Translation price calculation
 *
 * @package gts/translation-order
 */

namespace GTS\TranslationOrder;

use stdClass;

/**
 * Cost class file.
 */
class Cost {

	/**
	 * Translate price list.
	 *
	 * @var object|stdClass|null
	 */
	private $translate_price;

	/**
	 * Cost construct.
	 */
	public function __construct() {
		$api = new GTS_API();

		$this->translate_price = $api->get_price_languages_list();
	}

	/**
	 * Get post word count.
	 *
	 * @param string|int $post_id post id.
	 *
	 * @return array|int|string[]
	 */
	private function get_count_words( $post_id ) {
		$post_object = get_post( (int) $post_id );

		return str_word_count( wp_strip_all_tags( strip_shortcodes( $post_object->post_content ), true ) );
	}

	/**
	 * Get count symbols.
	 *
	 * @param int|string $post_id Post ID.
	 *
	 * @return int
	 */
	private function get_count_symbols( $post_id ): int {
		$post_object = get_post( (int) $post_id );

		$count = iconv_strlen( wp_strip_all_tags( strip_shortcodes( $post_object->post_content ), true ) );
		if ( ! $count ) {
			$count = 0;
		}

		return $count;
	}

	/**
	 * Total price by translated to post.
	 *
	 * @param string     $source_language Source language.
	 * @param array      $target_language Target language.
	 * @param int|string $post_id         Post ID.
	 *
	 * @return float|int|mixed
	 */
	public function price_by_post( string $source_language, array $target_language, $post_id ) {

		$total = 0;
		foreach ( $target_language as $item ) {
			$prices = $this->get_language_price( $source_language, $item );

			if ( isset( $prices->is_rate_per_char ) && $prices->is_rate_per_char ) {
				$count_word = $this->get_count_symbols( $post_id );
			} else {
				$count_word = $this->get_count_words( $post_id );
			}

			if ( 0 !== $count_word ) {
				$rate = $prices->rate_per_word ?? 0.19;

				$total += ( $count_word * $rate );
			}
		}

		return $total;
	}

	/**
	 * Get rate by language.
	 *
	 * @param string $source_language Source language.
	 * @param string $target_language Target language.
	 *
	 * @return mixed|object
	 */
	public function get_language_price( string $source_language, string $target_language ) {

		foreach ( $this->translate_price as $item ) {
			if ( $source_language === $item->source_language && $target_language === $item->target_language ) {
				return $item;
			}
		}

		return (object) null;
	}
}
