<?php
/**
 * $URL: https://code.marketacumen.com/zesk/trunk/classes/base26.inc $
 * @package zesk
 * @subpackage system
 * @author kent
 * @copyright Copyright &copy; 2010, Market Acumen, Inc.
 */

class base26 {
	/**
	 * Convert an integer into a base-26 alphabetic string with a minimum length of $nChars.
	 *
	 * <code>
	 * assert(base26::from_integer(0,1) === "A");
	 * assert(base26::from_integer(0,2) === "AA");
	 * assert(base26::from_integer(0,5) === "AAAAA");
	 * assert(base26::from_integer(1,5) === "AAAAB");
	 * assert(base26::from_integer(4649370,1) === "KENTY");
	 * assert(base26::from_integer(4649370,5) === "KENTY");
	 * </code>
	 *
	 * @param integer $i Number to convert to base-26 alphabetic string
	 * @param string $nChars Minimum number of characters to return.
	 * @return string Base-26 alphabetic string
	 * @see base26::to_integer()
	 */
	public static function from_integer($i, $nChars) {
		$A = ord('A');
		$s = "";
		while ($i > 0) {
			$mod = $i % 26;
			$i = intval($i / 26);
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
	 * <code>
	 * assert(base26::to_integer("A") === 0);
	 * assert(base26::to_integer("AA") === 0);
	 * assert(base26::to_integer("AAAAAA") === 0);
	 * assert(base26::to_integer("B") === 1);
	 * assert(base26::to_integer("KENTY") === 4649370);
	 * assert(base26::to_integer("-K E N@@#345345@#2@T%3^#%^423@Y@#223$") === 4649370);
	 * </code>
	 *
	 * @param integer $s Number to convert from base-26 alphabetic string
	 * @return integer|double Numeric value of the input string.
	 * @see base26::from_integer()
	 */
	public static function to_integer($s) {
		$s = preg_replace("/[^A-Z]/", "", strtoupper($s));
		$A = ord('A');
		$mul = 1;
		$total = 0;
		for($i = strlen($s) - 1; $i >= 0; $i--) {
			$total += (ord($s[$i]) - $A) * $mul;
			$mul = $mul * 26;
		}
		return intval($total);
	}
}
