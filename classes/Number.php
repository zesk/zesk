<?php
/**
 * $URL: https://code.marketacumen.com/zesk/trunk/classes/Number.php $
 * @package zesk
 * @subpackage system
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2008, Market Acumen, Inc.
 * Created on Tue Jul 15 13:03:41 EDT 2008
 */
namespace zesk;

/**
 * Number formatting tools and functions
 *
 * @author kent
 *
 */
class Number {
	/**
	 * Kilobytes, Megabytes, Gigabytes, Terabytes
	 * 
	 * @var array
	 */
	private static $magnitudes = array(
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
	
	/**
	 * Format bytes
	 * 
	 * @param unknown $n
	 * @param number $precision
	 * @return string
	 */
	public static function format_bytes($n, $precision = 1) {
		if ($n >= 1099511627776) {
			return __("Number::format_bytes:={0} TB", round(($n / self::$magnitudes['T']), $precision));
		} else if ($n >= 1073741824) {
			return __("Number::format_bytes:={0} GB", round(($n / self::$magnitudes['G']), $precision));
		} else if ($n >= 1048576) {
			return __("Number::format_bytes:={0} MB", round(($n / self::$magnitudes['M']), $precision));
		} else if ($n >= 1024) {
			return __("Number::format_bytes:={0} KB", round($n / self::$magnitudes['K'], $precision));
		} else {
			return __("Number::format_bytes:={0} {1}", intval($n), Locale::plural(__("byte"), intval($n)));
		}
	}
	/**
	 * Compute the standard deviation of an array of numbers
	 * 
	 * @param array $a
	 * @param double $mean Use this value as the computed mean, otherwise compute it
	 * @return double
	 */
	public static function stddev(array $a, $mean = null) {
		$n = count($a);
		if ($n == 0) {
			return 0;
		}
		if ($n == 1) {
			rewind($a);
			return current($a);
		}
		if ($mean === null) {
			$mean = self::mean($a, 0);
		}
		$dd = 0.0;
		foreach ($a as $v) {
			$delta = $v - $mean;
			$dd += $delta * $delta;
		}
		return sqrt($dd / ($n - 1));
	}
	/**
	 * Compute the mean of an array of numbers
	 * 
	 * @param array $a
	 * @param number $zero What to return if array is empty
	 * @return double
	 */
	public static function mean(array $a, $zero = 0) {
		if (count($a) == 0) {
			return $zero;
		}
		$total = array_sum($a);
		return floatval($total) / count($a);
	}
}