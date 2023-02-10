<?php declare(strict_types=1);
/**
 * @package zesk
 * @subpackage system
 * @author kent
 * @copyright Copyright &copy; 2023, Market Acumen, Inc.
 */
namespace zesk;

/**
 * @see Base26_Test
 */
class Base26 {
	/**
	 * Convert an integer into a base-26 alphabetic string with a minimum length of $nChars.
	 *
	 * @param int $integer Number to convert to base-26 alphabetic string
	 * @param int $nChars Minimum number of characters to return.
	 * @return string Base-26 alphabetic string
	 * @see self::toInteger()
	 */
	public static function fromInteger(int $integer, int $nChars): string {
		$A = ord('A');
		$s = '';
		while ($integer > 0) {
			$mod = $integer % 26;
			$integer = intval($integer / 26);
			$s = chr($A + $mod) . $s;
		}
		$nChars -= strlen($s);
		if ($nChars > 0) {
			$s = str_repeat('A', $nChars) . $s;
		}
		return $s;
	}

	/**
	 * Convert an a base-26 alphabetic string to an integer (or double).
	 *
	 * Strips any non-character values from the string first.
	 *
	 * @param string $token Number to convert from base-26 alphabetic string
	 * @return integer Numeric value of the input string.
	 * @see self::fromInteger()
	 */
	public static function toInteger(string $token): int {
		$token = preg_replace('/[^A-Z]/', '', strtoupper($token));
		$A = ord('A');
		$mul = 1;
		$total = 0;
		for ($i = strlen($token) - 1; $i >= 0; $i--) {
			$total += (ord($token[$i]) - $A) * $mul;
			$mul = $mul * 26;
		}
		return intval($total);
	}
}
