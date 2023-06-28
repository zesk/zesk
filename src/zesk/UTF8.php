<?php
declare(strict_types=1);
/**
 * @package zesk
 * @subpackage system
 * @author kent
 * @copyright Copyright &copy; 2023, Market Acumen, Inc.
 */

namespace zesk;

use zesk\Exception\FileParseException;
use zesk\Exception\ParseException;
use zesk\Exception\KeyNotFound;

/**
 * 8-bit UTF utilities
 *
 * @author kent
 */
class UTF8
{
	/**
	 * @param string $mixed Data to convert
	 * @param string $characterSet Charset string to use (see ... for examples)
	 * @return string
	 * @throws ParseException
	 * @throws KeyNotFound
	 * @throws FileParseException
	 */
	public static function fromCharacterSet(string $mixed, string $characterSet): string
	{
		return CharacterSet::toUTF8($mixed, $characterSet);
	}

	public static function toISO8859(string $mixed): string
	{
		return utf8_decode($mixed);
	}
}
