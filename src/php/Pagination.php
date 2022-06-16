<?php
/**
 * Pagination class file.
 *
 * Script Name: *Digg Style Paginator Class
 * Script URI: http://www.mis-algoritmos.com/2007/05/27/digg-style-pagination-class/
 * Description: Class in PHP that allows to use a pagination like a digg or sabrosus style.
 * Script Version: 0.4
 * Author: Victor De la Rocha
 * Author URI: http://www.mis-algoritmos.com
 *
 * @package GTS\Quote
 */

namespace GTS\TranslationOrder;

// phpcs:disable WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid
// phpcs:disable WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
// phpcs:disable WordPress.NamingConventions.ValidVariableName.PropertyNotSnakeCase

/**
 * Class Pagination.
 */
class Pagination {

	/**
	 * Total items.
	 *
	 * @var int
	 */
	private $total_items = - 1;

	/**
	 * Limit.
	 *
	 * @var int|null
	 */
	private $limit;

	/**
	 * Target url.
	 *
	 * @var string
	 */
	private $target = '';

	/**
	 * Current page number.
	 *
	 * @var int
	 */
	private $page = 1;

	/**
	 * Number of adjacent pages.
	 *
	 * @var int
	 */
	private $adjacents = 2;

	/**
	 * Show counter.
	 *
	 * @var bool
	 */
	private $showCounter = false;

	/**
	 * Class name of pagination div.
	 *
	 * @var string
	 */
	private $className = 'pagination';

	/**
	 * Page link query arg name for page number.
	 *
	 * @var string
	 */
	private $parameterName = 'page';

	/**
	 * Friendly url.
	 *
	 * @var string|null
	 */
	private $urlF = null;

	/**
	 * Pagination html.
	 *
	 * @var string
	 */
	private $pagination = '';

	/**
	 * Prev button text.
	 *
	 * @var string
	 */
	private $prevT = 'Previous';

	/**
	 * Prev button icon.
	 *
	 * @var string
	 */
	private $prevI = '&#171;';

	/**
	 * Next button text.
	 *
	 * @var string
	 */
	private $nextT = 'Next';

	/**
	 * Next button icon.
	 *
	 * @var string
	 */
	private $nextI = '&#187;';

	/**
	 * Calculate.
	 *
	 * @var string
	 */
	private $calculated = false;

	/**
	 * Set total items.
	 *
	 * @param int $value Total items.
	 */
	public function items( $value ) {
		$this->total_items = $value;
	}

	/**
	 * Set limit - how many items to show per page.
	 *
	 * @param int $value Limit.
	 */
	public function limit( $value ) {
		$this->limit = $value;
	}

	/**
	 * Set target url.
	 *
	 * @param string $value Target url.
	 */
	public function target( $value ) {
		$this->target = $value;
	}

	/**
	 * Set current page number.
	 *
	 * @param int $value Current page number.
	 */
	public function currentPage( $value ) {
		$this->page = $value;
	}

	/**
	 * Set adjacent pages - number of pages should be shown on each side of the current page.
	 *
	 * @param int $value Adjacent pages.
	 */
	public function adjacents( $value ) {
		$this->adjacents = $value;
	}

	/**
	 * Set show counter.
	 *
	 * @param bool $value Show counter.
	 */
	public function showCounter( $value ) {
		$this->showCounter = $value;
	}

	/**
	 * Set class name.
	 *
	 * @param string $value Class name of pagination div.
	 */
	public function changeClass( $value = '' ) {
		$this->className = $value;
	}

	/**
	 * Set prev label.
	 *
	 * @param string $value Prev label.
	 */
	public function prevLabel( $value ) {
		$this->prevT = $value;
	}

	/**
	 * Set prev icon.
	 *
	 * @param string $value Prev icon.
	 */
	public function prevIcon( $value ) {
		$this->prevI = $value;
	}

	/**
	 * Set next label.
	 *
	 * @param string $value Next label.
	 */
	public function nextLabel( $value ) {
		$this->nextT = $value;
	}

	/**
	 * Set next icon.
	 *
	 * @param string $value Next icon.
	 */
	public function nextIcon( $value ) {
		$this->nextI = $value;
	}

	/**
	 * Set page link query arg name for page number.
	 *
	 * @param string $value Page link query arg name for page number.
	 */
	public function parameterName( $value = '' ) {
		$this->parameterName = $value;
	}

	/**
	 * Set friendly url.
	 *
	 * @param string $value Friendly url.
	 *
	 * @return string|null
	 */
	public function urlFriendly( $value = '%' ) {
		$this->urlF = null;

		if ( ! preg_match( '/^ *$/', $value ) ) {
			$this->urlF = $value;
		}

		return $this->urlF;
	}

	/**
	 * Show pagination.
	 */
	public function show() {
		echo wp_kses_post( $this->getOutput() );
	}

	/**
	 * Get output.
	 *
	 * @return string
	 */
	public function getOutput() {
		if ( $this->calculated ) {
			return "<div class=\"$this->className\">$this->pagination</div>\n";
		}

		if ( $this->calculate() ) {
			return "<div class=\"$this->className\">$this->pagination</div>\n";
		}

		return '';
	}

	/**
	 * Get page number link.
	 *
	 * @param int $id Page number.
	 *
	 * @return string
	 */
	private function get_page_number_link( $id ) {
		if ( false === strpos( $this->target, '?' ) ) {
			if ( $this->urlF ) {
				return (string) str_replace( $this->urlF, $id, $this->target );
			}

			return "$this->target?$this->parameterName=$id";
		}

		return "$this->target&$this->parameterName=$id";
	}

	/**
	 * Calculate pagination.
	 *
	 * @return bool
	 */
	public function calculate() {
		$this->pagination = '';
		$error            = false;

		if ( $this->urlF && '%' !== $this->urlF && false === strpos( $this->target, $this->urlF ) ) {
			echo "You specified a wildcard to substitute, but it doesn't exist in the target<br />";
			$error = true;
		}

		if ( '%' === $this->urlF && false === strpos( $this->target, $this->urlF ) ) {
			echo 'The wildcard % must be specified in the target to replace the page number<br />';
			$error = true;
		}

		if ( $this->total_items < 0 ) {
			echo 'It is necessary to specify the <strong>number of pages</strong><br />';
			$error = true;
		}

		if ( null === $this->limit ) {
			echo 'It is necessary to specify the <strong>limit of items</strong> to show per page<br />';
			$error = true;
		}

		if ( $error ) {
			return false;
		}

		$n = trim( $this->nextT . ' ' . $this->nextI );
		$p = trim( $this->prevI . ' ' . $this->prevT );

		// Setup page vars for display.
		$prev      = $this->page - 1;
		$next      = $this->page + 1;
		$last_page = ceil( $this->total_items / $this->limit );
		$lpm1      = $last_page - 1;

		/**
		 * Now we apply our rules and draw the pagination object.
		 * We're actually saving the code to a variable in case we want to draw it more than once.
		 */
		$this->calculated = true;

		if ( $last_page <= 1 ) {
			return true;
		}

		$counter = 0;
		// Wrapper.
		$this->pagination .= '<nav aria-label="' . __( 'Post pagination', 'gts-translation-order' ) . '"><ul class="pagination">';

		// Previous button.
		if ( $this->page ) {
			if ( $this->page > 1 ) {
				$this->pagination .= '<li class="page-item"><a class="page-link" href="' . $this->get_page_number_link( $prev ) . "\" class=\"prev\">$p</a></li>";
			} else {
				$this->pagination .= "<li class='page-item disabled'><a class=\"page-link\">$p</a></li>";
			}
		}

		// Pages.
		if ( $last_page < 7 + ( $this->adjacents * 2 ) ) {
			// Not enough pages to bother breaking it up.
			for ( $counter = 1; $counter <= $last_page; $counter ++ ) {
				if ( $counter === $this->page ) {
					$this->pagination .= "<li class=\"page-item\"><a class=\"page-link active\" href='#'>$counter</a></li>";
				} else {
					$this->pagination .= '<li class=\"page-item\"><a class="page-link" href="' . $this->get_page_number_link( $counter ) . "\">$counter</a></li>";
				}
			}
		} elseif ( $last_page > 5 + ( $this->adjacents * 2 ) ) {
			// Enough pages to hide some.
			if ( $this->page < 1 + ( $this->adjacents * 2 ) ) {
				// Close to beginning; only hide later pages.
				for ( $counter = 1; $counter < 4 + ( $this->adjacents * 2 ); $counter ++ ) {
					if ( $counter === $this->page ) {
						$this->pagination .= "<li class=\"page-item\"><a class=\"page-link active\" href='#'>$counter</a></li>";
					} else {
						$this->pagination .= '<li class=\"page-item\"><a class="page-link" href="' . $this->get_page_number_link( $counter ) . "\">$counter</a></li>";
					}
				}
				$this->pagination .= '<li class=\"page-item\"><a class="page-link" href="#">...</a></li>';
				$this->pagination .= '<li class=\"page-item\"><a class="page-link" href=\"' . $this->get_page_number_link( $lpm1 ) . "\">$lpm1</a></li>";
				$this->pagination .= '<li class=\"page-item\"><a class="page-link" href=\"' . $this->get_page_number_link( $last_page ) . "\">$last_page</a></li>";
			} elseif ( $last_page - ( $this->adjacents * 2 ) > $this->page && $this->page > ( $this->adjacents * 2 ) ) {
				// In middle; hide some front and some back.
				$this->pagination .= '<li class=\"page-item\"><a class="page-link" href=\"' . $this->get_page_number_link( 1 ) . '">1</a></li>';
				$this->pagination .= '<li class=\"page-item\"><a class="page-link" href=\"' . $this->get_page_number_link( 2 ) . '">2</a></li>';
				$this->pagination .= '<li class=\"page-item\"><a class="page-link" href="#">...</a></li>';
				for ( $counter = $this->page - $this->adjacents; $counter <= $this->page + $this->adjacents; $counter ++ ) {
					if ( $counter === $this->page ) {
						$this->pagination .= "<li class=\"page-item\"><a class=\"page-link active\" href='#'>$counter</a></li>";
					} else {
						$this->pagination .= '<li class=\"page-item\"><a class="page-link" href="' . $this->get_page_number_link( $counter ) . "\">$counter</a></li>";
					}
				}
				$this->pagination .= '<li class=\"page-item\"><a class="page-link" href="#">...</a></li>';
				$this->pagination .= '<li class="page-item"><a class="page-link" href=\"' . $this->get_page_number_link( $lpm1 ) . "\">$lpm1</a></li>";
				$this->pagination .= '<li class="page-item"><a class="page-link" href=\"' . $this->get_page_number_link( $last_page ) . "\">$last_page</a></li>";
			} else {
				// Close to end; only hide early pages.
				$this->pagination .= '<li class="page-item"><a class="page-link" href=\"' . $this->get_page_number_link( 1 ) . '">1</a></li>';
				$this->pagination .= '<li class="page-item"><a class="page-link" href=\"' . $this->get_page_number_link( 2 ) . '">2</a></li>';
				$this->pagination .= '...';
				for ( $counter = $last_page - ( 2 + ( $this->adjacents * 2 ) ); $counter <= $last_page; $counter ++ ) {
					if ( $counter === $this->page ) {
						$this->pagination .= "<li class=\"page-item\"><a class=\"page-link active\" href='#'>$counter</a></li>";
					} else {
						$this->pagination .= '<li class=\"page-item\"><a class="page-link" href="' . $this->get_page_number_link( $counter ) . "\">$counter</a></li>";
					}
				}
			}
		}

		// Next button.
		if ( $this->page ) {
			if ( $this->page < $counter - 1 ) {
				$this->pagination .= '<li class="page-item"><a href=\"' . $this->get_page_number_link( $next ) . "\" class=\"page-link\">$n</a></li>";
			} else {
				$this->pagination .= "<li class='page-item disabled'><a href='#' class=\"page-link\">$n</a></li>";
			}
			if ( $this->showCounter ) {
				$this->pagination .= "<div class=\"pagination_data\">($this->total_items Pages)</div>";
			}
		}

		$this->pagination .= '</ul></nav>';

		return true;
	}
}
