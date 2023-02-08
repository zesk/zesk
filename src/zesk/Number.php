<?php declare(strict_types=1);
/**
 * @package zesk
 * @subpackage system
 * @author kent
 * @copyright Copyright &copy; 2023, Market Acumen, Inc.
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
	private static $magnitudes = [
		'K' => 1024,
		'M' => 1048576,
		'G' => 1073741824,
		'T' => 1099511627776,
	];

	/**
	 * Takes a string like "20KB" and converts it to a byte
	 *
	 * @param string $string
	 * @return integer
	 */
	public static function parse_bytes(string $string): float {
		$matches = false;
		if (!preg_match("/([0-9.]+)\s*([KMGT])B?/i", $string, $matches)) {
			return intval($string);
		}
		[$whole, $number, $magnitude] = $matches;
		return floatval($number) * self::$magnitudes[strtoupper($magnitude)];
	}

	/**
	 * @deprecated 2022-10
	 * @param Locale $locale
	 * @param int $n
	 * @param int $precision
	 * @return string
	 */
	public static function format_bytes(Locale $locale, int $n, int $precision = 1): string {
		return self::formatBytes($locale, $n, $precision);
	}

	/**
	 * Format bytes
	 *
	 * @param Locale $locale
	 * @param int $n
	 * @param int $precision
	 * @return string
	 */
	public static function formatBytes(Locale $locale, int $n, int $precision = 1): string {
		if ($n >= 1099511627776) {
			return $locale('Number::format_bytes:={0} TB', [
				round(($n / self::$magnitudes['T']), $precision),
			]);
		} elseif ($n >= 1073741824) {
			return $locale('Number::format_bytes:={0} GB', [
				round(($n / self::$magnitudes['G']), $precision),
			]);
		} elseif ($n >= 1048576) {
			return $locale('Number::format_bytes:={0} MB', [
				round(($n / self::$magnitudes['M']), $precision),
			]);
		} elseif ($n >= 1024) {
			return $locale('Number::format_bytes:={0} KB', [
				round($n / self::$magnitudes['K'], $precision),
			]);
		} else {
			return $locale->__('Number::format_bytes:={0} {1}', [
				$n,
				$locale->plural($locale->__('byte'), intval($n)),
			]);
		}
	}

	/**
	 * Compute the standard deviation of an array of numbers
	 *
	 * @param array $a
	 * @param float|null $mean Use this value as the computed mean, otherwise compute it
	 * @return float
	 */
	public static function stddev(array $a, float $mean = null): float {
		$n = count($a);
		if ($n <= 1) {
			return 0;
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
	 * @param float $zero What to return if array is empty
	 * @return float
	 */
	public static function mean(array $a, float $zero = 0): float {
		if (count($a) == 0) {
			return $zero;
		}
		$total = array_sum($a);
		return floatval($total) / count($a);
	}
}
