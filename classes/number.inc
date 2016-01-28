<?php
/**
 * $URL: https://code.marketacumen.com/zesk/trunk/classes/number.inc $
 * @package zesk
 * @subpackage system
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2008, Market Acumen, Inc.
 * Created on Tue Jul 15 13:03:41 EDT 2008
 */

/**
 * Number formatting tools and functions
 *
 * @author kent
 *
 */
class number {
	static $magnitudes = array(
		'K' => 1024,
		'M' => 1048576,
		'G' => 1073741824,
		'T' => 1099511627776
	);

	/**
	 *
	 * @param string $string
	 */
	public static function parse_bytes($string) {
		$matches = false;
		if (!preg_match("/([0-9.]+)\s*([KMGT])B?/i", $string, $matches)) {
			return intval($string);
		}
		list($whole, $int, $magnitude) = $matches;
		return intval($int) * self::$magnitudes[strtoupper($magnitude)];
	}

	public static function format_bytes($n, $precision = 1) {
		if ($n >= 1099511627776) {
			return __("number::format_bytes:={0} TB", round(($n / self::$magnitudes['T']), $precision));
		} else if ($n >= 1073741824) {
			return __("number::format_bytes:={0} GB", round(($n / self::$magnitudes['G']), $precision));
		} else if ($n >= 1048576) {
			return __("number::format_bytes:={0} MB", round(($n / self::$magnitudes['M']), $precision));
		} else if ($n >= 1024) {
			return __("number::format_bytes:={0} KB", round($n / self::$magnitudes['K'], $precision));
		} else {
			return __("number::format_bytes:={0} {1}", intval($n), lang::plural(__("byte"), intval($n)));
		}
	}
	public static function stddev(array $a, $mean = false) {
		$n = count($a);
		if ($n == 0) {
			return 0;
		}
		if ($n == 1) {
			rewind($a);
			return current($a);
		}
		if ($mean === false) {
			$t = array_sum($a);
			$mean = $t / $n;
		}
		$dd = 0.0;
		foreach ($a as $v) {
			$delta = $v - $mean;
			$dd += $delta * $delta;
		}
		return sqrt($dd / ($n - 1));
	}
	public static function mean(array $a, $zero = 0) {
		if (count($a) == 0)
			return $zero;
		$total = array_sum($a);
		return floatval($total) / count($a);
	}
}

/**
 * Computes the standard deviation of an array
 *
 * @param array $a
 *        	Array containing numeric values
 * @param float $mean
 *        	Optional pre-calculated mean (average)
 * @return float Standard deviation of the values in this array
 * @deprecated 2013
 */
function array_stddev(array $a, $mean = false) {
	return number::stddev($a, $mean);
}

/**
 * Computes the mean (average) of an array
 *
 * @param array $a
 *        	Array containing numeric values
 * @return float Average (Mean) of the values in this array
 * @deprecated 2013
 */
function array_mean($a, $zero = 0) {
	return number::mean($a, $zero);
}