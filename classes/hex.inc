<?php
/**
 * $URL: https://code.marketacumen.com/zesk/trunk/classes/hex.inc $
 * @package zesk
 * @subpackage system
 * @author kent
 * @copyright Copyright &copy; 2009, Market Acumen, Inc.
 * Created on Thu Apr 15 22:31:10 EDT 2010 22:31:10
 */

class hex {
	const codes = "0123456789ABCDEF";

	public static function decode($x) {
		$r = "";
		$x = strtoupper($x);
		$n = strlen($x);
		for($i = 0; $i < $n; $i = $i + 2) {
			$r .= chr(strpos(self::codes, $x[$i]) << 4 | strpos(self::codes, $x[$i + 1]));
		}
		return $r;
	}

	public static function encode($x) {
		$h = self::codes;
		$r = "";
		$n = strlen($x);
		for($i = 0; $i < $n; $i++) {
			$r .= $h[(ord($x[$i]) >> 4) & 0x0F] . $h[ord($x[$i]) & 0x0F];
		}
		return $r;
	}
}

