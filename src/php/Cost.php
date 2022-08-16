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
	 * Translation prices.
	 *
	 * @var array
	 */
	private $prices;

	/**
	 * Currency rates.
	 *
	 * @var array
	 */
	private $rates;

	/**
	 * Min order value.
	 *
	 * @var float
	 */
	private $min_order;

	/**
	 * Default rate per word.
	 *
	 * @var float
	 */
	private $default_rate_per_word;

	/**
	 * Cost construct.
	 */
	public function __construct() {
		$api = new API();

		$this->prices                = $api->get_prices();
		$this->rates                 = $api->get_rates();
		$this->min_order             = $api->get_min_order();
		$this->default_rate_per_word = $api->get_default_rate_per_word();
	}

	/**
	 * Get min order.
	 *
	 * @return float
	 */
	public function get_min_order() {
		return $this->min_order;
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
	 * @param array $post_ids Post IDs.
	 *
	 * @return int
	 */
	public function get_total_word_count( $post_ids ) {
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
	 * Translation price for post.
	 *
	 * @param string     $source_language  Source language.
	 * @param array      $target_languages Target languages.
	 * @param int|string $post_id          Post ID.
	 *
	 * @return float|int
	 */
	public function price_for_post( $source_language, array $target_languages, $post_id ) {
		$total = 0;

		foreach ( $target_languages as $target_language ) {
			$price = $this->get_language_pair_price( $source_language, $target_language );

			if ( isset( $price->is_rate_per_char ) && $price->is_rate_per_char ) {
				$unit_count = $this->get_char_count( $post_id );
			} else {
				$unit_count = $this->get_word_count( $post_id );
			}

			$rate = isset( $price->rate_per_word ) ? $price->rate_per_word : $this->default_rate_per_word;

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
		foreach ( $this->prices as $price ) {
			if (
				$source_language === $price->source_language &&
				$target_language === $price->target_language
			) {
				return $price;
			}
		}

		return (object) null;
	}

	/**
	 * Get currency exchange rate.
	 *
	 * @param string $currency Currency.
	 *
	 * @return float
	 */
	private function get_rate( $currency ) {
		return isset( $this->rates[ $currency ] ) ? $this->rates[ $currency ] : 1.000;
	}

	/**
	 * Get translation rate per unit (word or char).
	 *
	 * @param string $source_language Source language.
	 * @param string $target_language Target language.
	 *
	 * @return float
	 */
	public function get_rate_per_word( $source_language, $target_language ) {
		foreach ( $this->prices as $price ) {
			if ( $price->source_language === $source_language && $price->target_language === $target_language ) {
				return $price->rate_per_word;
			}
		}

		return $this->default_rate_per_word;
	}

	/**
	 * Return whether language translation rate is per word or char.
	 *
	 * @param string $source_language Source language.
	 * @param string $target_language Target language.
	 *
	 * @return bool True when rate per word. False when per char.
	 */
	public function is_rate_per_word( $source_language, $target_language ) {
		foreach ( $this->prices as $price ) {
			if ( $price->source_language === $source_language && $price->target_language === $target_language ) {
				return (bool) $price->is_rate_per_char;
			}
		}

		return $this->default_rate_per_word;
	}

	/**
	 * Get amount for each language.
	 *
	 * @param string $source_language  Source language.
	 * @param array  $target_languages Target languages.
	 * @param int    $word_count       Word count.
	 * @param string $currency         Currency.
	 *
	 * @return array
	 */
	public function get_amount_each_language( $source_language, $target_languages, $word_count, $currency = 'USD' ) {

		$word_count = $word_count ?: 0;

		if ( ! $word_count ) {
			return array_pad( [], count( $target_languages ), 0 );
		}

		$target_languages     = array_map( 'trim', $target_languages );
		$rate                 = $this->get_rate( $currency );
		$min_order            = $this->min_order;
		$amount_each_language = [];

		foreach ( $target_languages as $target_language ) {
			$rate_per_word          = $this->get_rate_per_word( $source_language, $target_language );
			$amount_lang            = max( $min_order, $word_count * $rate_per_word ) * $rate;
			$amount_each_language[] = round( $amount_lang, 2 );
		}

		return $amount_each_language;
	}
}
