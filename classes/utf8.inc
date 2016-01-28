<?php
/**
 * $URL: https://code.marketacumen.com/zesk/trunk/classes/utf8.inc $
 * @package zesk
 * @subpackage system
 * @author kent
 * @copyright Copyright &copy; 2014, Market Acumen, Inc.
 */

/**
 * 8-bit UTF utilities
 *
 * @author kent
 */
class utf8 {
	public static function from_charset($mixed, $charset) {
		return charset::to_utf8($mixed, $charset);
	}

	public static function to_iso8859($mixed) {
		if (is_array($mixed)) {
			foreach ($mixed as $k => $v) {
				$mixed[$k] = utf8::to_iso8859($v);
			}
			return $mixed;
		}
		return utf8_decode($mixed);
	}
}
