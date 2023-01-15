<?php
declare(strict_types=1);
/**
 * @package zesk
 * @subpackage system
 * @author kent
 * @copyright Copyright &copy; 2022, Market Acumen, Inc.
 */

namespace zesk;

/**
 * Abstraction for RGB color
 *
 * @author kent
 * @see CSS
 */
class Color_RGB {
	/**
	 *
	 * @var integer
	 */
	public $red = 0;

	/**
	 *
	 * @var integer
	 */
	public $green = 0;

	/**
	 *
	 * @var integer
	 */
	public $blue = 0;

	/**
	 * @param int|string|array $r
	 * @param int $g
	 * @param int $b
	 * @throws Exception_Syntax
	 */
	public function __construct(int|string|array $r = 0, int $g = 0, int $b = 0) {
		if (is_int($r)) {
			$this->red = clamp(0, $r, 255);
			$this->green = clamp(0, $g, 255);
			$this->blue = clamp(0, $b, 255);
		} elseif (is_string($r) || is_numeric($r)) {
			[$this->red, $this->green, $this->blue] = CSS::colorParse($r);
		} elseif (is_array($r)) {
			if (array_key_exists('r', $r)) {
				$this->red = $r['r'];
				$this->green = $r['g'];
				$this->blue = $r['b'];
			} else {
				[$this->red, $this->green, $this->blue] = $r;
			}
		}
	}

	/**
	 * @param int|string|array $r
	 * @param ?int $g
	 * @param ?int $b
	 * @return static
	 * @throws Exception_Syntax
	 */
	public static function factory(int|string|array $r = 0, int $g = 0, int $b = 0): self {
		return new self($r, $g, $b);
	}

	/**
	 * Convert to string
	 *
	 * @return string
	 */
	public function __toString() {
		return CSS::rgbToHex([
			$this->red, $this->green, $this->blue,
		]);
	}
}
