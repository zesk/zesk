<?php
declare(strict_types=1);
/**
 * @package zesk
 * @subpackage system
 * @author kent
 * @copyright Copyright &copy; 2017, Market Acumen, Inc.
 */

namespace zesk;

/**
 * 8-bit UTF utilities
 *
 * @author kent
 */
class UTF8 {
	/**
	 * @param string $mixed Data to convert
	 * @param string $charset Charset string to use (see ... for examples)
	 * @return array|string
	 * @throws Exception_Convert
	 * @throws Exception_File_Format
	 */
	public static function from_charset(string $mixed, string $charset) {
		return charset::to_utf8($mixed, $charset);
	}

	public static function to_iso8859(string $mixed): string {
		return utf8_decode($mixed);
	}
}
