<?php
declare(strict_types=1);
/**
 * Things that should probably just be in PHP, or were added after we added these. Review
 * annually to see if we can deprecate functionality.
 *
 * TODO Move all of these to static methods to avoid having to include this file.
 *
 * @package zesk
 * @subpackage system
 * @author kent
 * @copyright Copyright &copy; 2023, Market Acumen, Inc.
 */

use zesk\Kernel;
use zesk\Number;
use zesk\Types;
use zesk\StringTools;
use zesk\Debug;
use zesk\Application;
use zesk\Directory;
use zesk\ArrayTools;
use zesk\Exception\SemanticsException;

/**
 * Key used to separate paths in the globals array
 */
const ZESK_GLOBAL_KEY_SEPARATOR = '::';

/**
 * Returns the first value in array, or $default if array is zero-length.
 *
 * Does NOT assume array is a 0-based key list.
 *
 * @param array $a
 * @param mixed|null $default
 * @return mixed
 * @deprecated 2023-01
 */
function first(array $a, mixed $default = null): mixed
{
	return ArrayTools::first($a, $default);
}

/**
 * Returns the last value in a PHP array, or $default if array is zero-length.
 * Does NOT assume array is a 0-based key list
 *
 * @param array $a
 * @param mixed|null $default
 * @return mixed
 * @deprecated 2023-01
 */
function last(array $a, mixed $default = null): mixed
{
	return ArrayTools::last($a, $default);
}

/**
 * Return a sane type for a variable
 *
 * @param mixed $mixed
 * @return string
 * @deprecated 2023-01
 */
function type(mixed $mixed): string
{
	return Types::type($mixed);
}

/**
 * Dumps a variable using print_r and surrounds with <pre> tag
 * Optionally defined because "dump" is also defined by Drush
 *
 * Probably should switch to a namespace version of this as well.
 *
 * @param mixed $x
 *            Variable to dump
 * @return void echos to page
 * @see print_r
 * @deprecated 2023-01
 */
if (!function_exists('dump')) {
	function dump(): void
	{
		call_user_func_array('zesk\Debug::output', func_get_args());
	}
}

/**
 * Returns what "dump" would print (doesn't echo)
 *
 * @param mixed $x
 *            Variable to dump
 * @return string string representation of the value
 * @see print_r, dump
 * @deprecated 2023-01
 */
function _dump(mixed $x): string
{
	return Debug::dump($x);
}

/**
 * Parses $value for a boolean value.
 * Intended for parsing developer or user inputs which include the
 * values:
 * <ul>
 * <li>True, False, T, F</li>
 * <li>0,1</li>
 * <li>Yes, No, Y, N</li>
 * <li><em>empty string</em></li>
 * <li>Enabled, Disabled</li>
 * </ul>
 *
 * @param mixed $value
 *            A value to parse to find a boolean value.
 * @param ?bool $default
 *            A value to return if parsing is unsuccessful
 * @return ?bool Returns true or false, or null if parsing fails
 * @deprecated 2023-01
 */
function toBool(mixed $value, ?bool $default = false): ?bool
{
	return Types::toBool($value, $default);
}

/**
 * Ensures a value is an integer value.
 * If not, the default value is returned.
 *
 * @param mixed $s Value to convert to integer
 * @param int $default The value if we can not convert to integer
 * @return integer The integer value, or $def if it can not be converted to an integer
 * @deprecated 2023-01
 */
function toInteger(mixed $s, int $default = 0): int
{
	return Types::toInteger($s, $default);
}

/**
 * Convert to a valid array key
 *
 * @param mixed $key
 * @return string|int
 * @deprecated 2023-01
 */
function toKey(mixed $key): string|int
{
	return Types::toKey($key);
}

/**
 * Ensures a value is a float value.
 * If not, the default value is returned.
 *
 * @param mixed $s
 *            Value to convert to float
 * @param ?float $def
 *            The default value. Not converted to float.
 * @return float The value, or $def if it can not be converted to a float
 * @deprecated 2023-01
 */
function toFloat(mixed $s, float $def = null): float
{
	return Types::toFloat($s, $def);
}

/**
 * Converts a string to a list via explode.
 * If it's already an array, return it. Otherwise, return the default.
 *
 * @param mixed $mixed
 *            Array or string to convert to a "list"
 * @param mixed $default
 *            Value to return if not a string or array
 * @param string $delimiter
 *            String list delimiter (";" is default)
 * @return array or $default
 * @deprecated 2023-01
 */
function toList(mixed $mixed, array $default = [], string $delimiter = ';'): array
{
	return Types::toList($mixed, $default, $delimiter);
}

/**
 * Converts a scalar to an array.
 * Returns default for values of null or false.
 *
 * @param mixed $mixed
 *            If false or null, returns default value
 * @param mixed $default
 *            Default value to return if can't easily convert to an array.
 * @return array
 * @deprecated 2023-01
 */
function toArray(mixed $mixed, array $default = []): array
{
	return Types::toArray($mixed, $default);
}

/**
 * Converts a PHP value to a string, usually for debugging.
 *
 * @param mixed $mixed
 * @return string
 * @deprecated 2023-01
 */
function toText(mixed $mixed): string
{
	return Types::toText($mixed);
}
/**
 * Gently coerce things to iterable
 *
 * @param mixed $mixed
 * @return iterable
 * @deprecated 2023-01
 */
function toIterable(mixed $mixed): iterable
{
	return Types::toIterable($mixed);
}

/**
 * Converts 20G to integer value
 *
 * @param string|int $mixed
 * @param int $default
 * @return int
 * @deprecated 2023-01
 */
function toBytes(string|int $mixed, int $default = 0): int
{
	return Types::toBytes($mixed, $default);
}

/**
 * Convert a deep object into a flat one (string)
 *
 * @param mixed $mixed
 * @return string|int|float|bool
 * @throws SemanticsException
 * @deprecated 2023-01
 */
function flatten(mixed $mixed): string|int|float|bool
{
	return Types::flatten($mixed);
}

/**
 * Convert tokens in a string to other things.
 * Anything not a string or array is returned as-is.
 *
 * @param mixed $mixed
 *            An array or string
 * @param array $map
 *            Tokens to convert from/to
 * @return mixed Whatever passed in is returned (string/array)
 * @deprecated 2023-01
 */
function tr(mixed $mixed, array $map): mixed
{
	return Types::replaceSubstrings($mixed, $map);
}


/**
 * Convert tokens in a string to other values.
 * 2015-06-13 - Changed $target is first parameter, map is 2nd (similar to __ and Logger::log) -
 * incompatible change
 * 2015-06-13 - Changed $case_sensitive -> $insensitive semantics (implying the opposite of
 * original). avoids accidentally modifying strings)
 *
 * Passing in "insensitive" to true will return a string which has unmatched tokens in lowercase.
 * So:
 *
 * @param mixed $mixed Target to modify
 * @param array $map Array of name => value of search => replace
 * @param boolean $insensitive Case-sensitive search/replace (defaults to false)
 * @param string $prefix_char Prefix character for tokens (defaults to "{")
 * @param string $suffix_char Suffix character for tokens (defaults to "}")
 * @return array|string
 * @deprecated 2023-01
 */
function map(array|string $mixed, array $map, bool $insensitive = false, string $prefix_char = '{', string $suffix_char = '}'): array|string
{
	return ArrayTools::map($mixed, $map, $insensitive, $prefix_char, $suffix_char);
}


/**
 * Breaks a string in half at a given delimiter, and returns default values if delimiter is not
 * found.
 *
 * Usage is generally:
 *
 * list($table, $field) = pair($thing, ".", $default_table, $thing);
 *
 * @param string $a A string to parse into a pair
 * @param string $delim The delimiter to break the string apart
 * @param string $left The default left value if delimiter is not found
 * @param string $right The default right value if delimiter is not found
 * @param string $include_delimiter If "left" includes the delimiter in the left value, if "right" includes the
 *  delimiter in the right value Any other value the delimiter is stripped from the results
 * @return array A size 2 array containing the left and right portions of the pair
 * @deprecated 2023-01 Use StringTools::pair
 */
function pair(string $a, string $delim = '.', string $left = '', string $right = '', string $include_delimiter = ''): array
{
	return StringTools::pair($a, $delim, $left, $right, $include_delimiter);
}

/**
 * Create a file path and ensure only one slash appears between path entries. Do not use this
 * with URLs, use glue instead.
 *
 * @param array|string $path Variable list of path items, or array of path items to concatenate
 * @return string with a properly formatted path
 * @deprecated 2023-01 Directory::path or File::path
 */
function path(array|string $path /* dir, dir, ... */): string
{
	$args = func_get_args();
	return call_user_func_array(Directory::path(...), $args);
}

/**
 * Clamps a numeric value to a minimum and maximum value.
 *
 * @param mixed $minValue
 *            The minimum value in the clamp range
 * @param mixed $value
 *            A scalar value which serves as the value to clamp
 * @param mixed $maxValue
 *            A scalar value which serves as the value to clamp
 * @return mixed
 * @deprecated 2023-01
 */
function clamp(mixed $minValue, mixed $value, mixed $maxValue): mixed
{
	return Number::clamp($minValue, $value, $maxValue);
}


/**
 * Determine if a string is a possible email address
 *
 * @param string $email
 * @return boolean
 * @deprecated 2023-01
 */
function is_email(string $email): bool
{
	return Types::isEmail($email);
}

/**
 * Do we need to deal with platform issues on Windows? Probably, you know, because.
 *
 * @return boolean
 */
function is_windows(): bool
{
	return PATH_SEPARATOR === '\\';
}

/*---------------------------------------------------------------------------------------------------------*\
  ---------------------------------------------------------------------------------------------------------
  ---------------------------------------------------------------------------------------------------------
		 _                               _           _
	  __| | ___ _ __  _ __ ___  ___ __ _| |_ ___  __| |
	 / _` |/ _ \ '_ \| '__/ _ \/ __/ _` | __/ _ \/ _` |
	| (_| |  __/ |_) | | |  __/ (_| (_| | ||  __/ (_| |
	 \__,_|\___| .__/|_|  \___|\___\__,_|\__\___|\__,_|
			   |_|
  ---------------------------------------------------------------------------------------------------------
  ---------------------------------------------------------------------------------------------------------
\*---------------------------------------------------------------------------------------------------------*/

/**
 * Get our global application
 *
 * @return Application
 * @deprecated 2017-08 Avoid usage - use $this->application when available or pass $application around
 *
 */
function app(): Application
{
	return Kernel::singleton()->application();
}
