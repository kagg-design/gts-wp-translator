<?php
/**
 * Translation price calculation
 *
 * @package gts/translation-order
 */

namespace GTS\TranslationOrder;

/**
 * Cost class file.
 */
class Cost {
	/**
	 * Default rate per word = $0.19.
	 */
	const DEFAULT_RATE_PER_WORD = 0.19;

	/**
	 * Translation prices.
	 *
	 * @var array
	 */
	private $translation_prices;

	/**
	 * Cost construct.
	 */
	public function __construct() {
		$api = new API();

		$this->translation_prices = $api->get_prices();
	}

	/**
	 * Get post word count.
	 *
	 * @param string|int $post_id Post id.
	 *
	 * @return int
	 */
	public function get_word_count( $post_id ) {
		$post_object = get_post( (int) $post_id );

		return str_word_count( wp_strip_all_tags( strip_shortcodes( $post_object->post_content ), true ) );
	}

	/**
	 * Get total word count.
	 *
	 * @param array $post_ids Post ID.
	 *
	 * @return int
	 */
	public function get_total_words( $post_ids ) {
		$total = 0;

		foreach ( $post_ids as $id ) {
			$total += $this->get_word_count( $id );
		}

		return $total;
	}

	/**
	 * Get character count.
	 *
	 * @param int|string $post_id Post ID.
	 *
	 * @return int
	 */
	private function get_char_count( $post_id ) {
		$post_object = get_post( (int) $post_id );
		$count       = iconv_strlen( wp_strip_all_tags( strip_shortcodes( $post_object->post_content ), true ) );

		return false === $count ? 0 : $count;
	}

	/**
	 * Total price by translated to post.
	 *
	 * @param string     $source_language  Source language.
	 * @param array      $target_languages Target languages.
	 * @param int|string $post_id          Post ID.
	 *
	 * @return float|int
	 */
	public function price_by_post( $source_language, array $target_languages, $post_id ) {
		$total = 0;

		foreach ( $target_languages as $target_language ) {
			$price = $this->get_language_pair_price( $source_language, $target_language );

			if ( isset( $price->is_rate_per_char ) && $price->is_rate_per_char ) {
				$unit_count = $this->get_char_count( $post_id );
			} else {
				$unit_count = $this->get_word_count( $post_id );
			}

			$rate = isset( $price->rate_per_word ) ? $price->rate_per_word : self::DEFAULT_RATE_PER_WORD;

			$total += ( $unit_count * $rate );
		}

		return $total;
	}

	/**
	 * Get rate for language pair.
	 *
	 * @param string $source_language Source language.
	 * @param string $target_language Target language.
	 *
	 * @return object
	 */
	private function get_language_pair_price( $source_language, $target_language ) {
		foreach ( $this->translation_prices as $translation_price ) {
			if (
				$source_language === $translation_price->source_language &&
				$target_language === $translation_price->target_language
			) {
				return $translation_price;
			}
		}

		return (object) null;
	}
}
