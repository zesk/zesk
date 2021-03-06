<?php
/**
 * @package zesk
 * @subpackage system
 * @author kent
 * @copyright Copyright &copy; 2012, Market Acumen, Inc.
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
	 *
	 * @param integer $r
	 * @param integer $g
	 * @param integer $b
	 */
	public function __construct($r = false, $g = false, $b = false) {
		if (is_numeric($r) && is_numeric($g) && is_numeric($b)) {
			$this->red = clamp(0, $r, 255);
			$this->green = clamp(0, $g, 255);
			$this->blue = clamp(0, $b, 255);
		} elseif (is_string($r)) {
			$parts = CSS::color_parse($r);
			if (is_array($parts)) {
				list($this->red, $this->green, $this->blue) = $parts;
			}
		} elseif (is_array($r)) {
			if (array_key_exists('r', $r)) {
				$this->red = $r['r'];
				$this->green = $r['g'];
				$this->blue = $r['b'];
			} else {
				list($this->red, $this->green, $this->blue) = $r;
			}
		}
	}

	/**
	 * Convert to string
	 *
	 * @return string
	 */
	public function __toString() {
		return CSS::rgb_to_hex(array(
			$this->red,
			$this->green,
			$this->blue,
		));
	}
}
