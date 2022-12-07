<?php
declare(strict_types=1);

/**
 * Things that should probably just be in PHP, or were added after we added these. Review
 * annually to see if we can deprecate functionality.
 *
 * @no-cannon Do not cannon this file (for _W)
 * @package zesk
 * @subpackage system
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2022, Market Acumen, Inc.
 */

use zesk\Kernel;
use zesk\Text;
use zesk\PHP;
use zesk\Debug;
use zesk\JSON;
use zesk\Locale;
use zesk\Application;
use zesk\Hookable;
use zesk\ArrayTools;
use zesk\Exception_Semantics;

/**
 * A regular expression pattern for matching email addresses anywhere (should delimit both ends in
 * your own expression).
 *
 * @see https://stackoverflow.com/questions/201323/using-a-regular-expression-to-validate-an-email-address
 * @see https://stackoverflow.com/questions/2049502/what-characters-are-allowed-in-an-email-address#2049510
 *
 * User contains:
 *
 * - uppercase and lowercase Latin letters A to Z and a to z;
 * - digits 0 to 9;
 * - special characters !#$%&'*+-/=?^_`{|}~;
 * - dot ., provided that it is not the first or last character unless quoted, and provided also that it does not appear consecutively unless quoted (e.g. John..Doe@example.com is not allowed but "John..Doe"@example.com is allowed);
 * - space and "(),:;<>@[\] characters are allowed with restrictions (they are only allowed inside a quoted string, as described in the paragraph below, and in addition, a backslash or double-quote must be preceded by a backslash);
 * - comments are allowed with parentheses at either end of the local-part; e.g. john.smith(comment)@example.com and (comment)john.smith@example.com are both equivalent to john.smith@example.com.
 *
 * Patterns depend on using the / for the delimiter as shown in the PREG_PATTERN_EMAIL_USERNAME_CHAR with the \\ prefixing the / to ensure the pattern does not stop
 *
 * @var string
 * @see preg_match
 */
const PREG_PATTERN_EMAIL_USERNAME_CHAR = '[-a-z0-9!#$%&\'*+\\/=?^_`{|}~]';
const PREG_PATTERN_EMAIL_USERNAME = '(?:' . PREG_PATTERN_EMAIL_USERNAME_CHAR . '+(?:\\.' . PREG_PATTERN_EMAIL_USERNAME_CHAR . '+)*|"(?:[\x01-\x08\x0b\x0c\x0e-\x1f\x21\x23-\x5b\x5d-\x7f]|\\[\x01-\x09\x0b\x0c\x0e-\x7f])*")';
const PREG_PATTERN_EMAIL_DOMAIN = '(?:(?:[a-z0-9](?:[a-z0-9-]*[a-z0-9])?\.)+[a-z0-9](?:[a-z0-9-]*[a-z0-9])?|\[(?:(?:(2(5[0-5]|[0-4][0-9])|1[0-9][0-9]|[1-9]?[0-9]))\.){3}(?:(2(5[0-5]|[0-4][0-9])|1[0-9][0-9]|[1-9]?[0-9])|[a-z0-9-]*[a-z0-9]:(?:[\x01-\x08\x0b\x0c\x0e-\x1f\x21-\x5a\x53-\x7f]|\\[\x01-\x09\x0b\x0c\x0e-\x7f])+)\])';

/**
 * A regular expression pattern for matching email addresses, undelimited. Should run case-insensitive.
 *
 * @var string
 * @see preg_match
 */

const PREG_PATTERN_EMAIL = PREG_PATTERN_EMAIL_USERNAME . '@' . PREG_PATTERN_EMAIL_DOMAIN;

/**
 * Key used to seaparate paths in the globals array
 */
const ZESK_GLOBAL_KEY_SEPARATOR = '::';

/**
 * Get our global Zesk kernel. Use is DISCOURAGED, unless you use it like:
 *
 * zesk()->deprecated()
 *
 * @return ?Kernel
 */
function zesk(): ?Kernel {
	try {
		return Kernel::singleton();
	} catch (Exception $e) {
		return null;
	}
}

/**
 * Returns the first value in array, or $default if array is zero-length.
 *
 * Does NOT assume array is a 0-based key list.
 *
 * @param array $a
 * @param mixed|null $default
 * @return mixed
 */
function first(array $a, mixed $default = null): mixed {
	return count($a) !== 0 ? $a[key($a)] : $default;
}

/**
 * Returns the last value in a PHP array, or $default if array is zero-length.
 * Does NOT assume array is a 0-based key list
 *
 * @param array $a
 * @param mixed|null $default
 * @return mixed
 */
function last(array $a, mixed $default = null): mixed {
	if (count($a) === 0) {
		return $default;
	}
	$kk = array_keys($a);
	return $a[$kk[count($kk) - 1]];
}

/**
 * Return a sane type for a variable
 *
 * @param mixed $mixed
 * @return string
 */
function type(mixed $mixed): string {
	return is_object($mixed) ? $mixed::class : gettype($mixed);
}

/**
 * Does string begin with another string?
 *
 * @param string $string
 * @param string $prefix
 * @return boolean
 * @see \zesk\StringTools::begins
 * @deprecated 2022-02
 * @see str_starts_with
 */
function begins(string $haystack, string $needle): bool {
	return str_starts_with($haystack, $needle);
}

/**
 * Does string begin with another string (case-insensitive)?
 *
 * @param string $string
 * @param string $prefix
 * @return boolean
 * @see \zesk\StringTools::beginsi
 * @see str_starts_with
 * @deprecated 2022-02
 */
function beginsi(string $haystack, string $needle): bool {
	$n = strlen($needle);
	if ($n === 0) {
		return true;
	}
	return strcasecmp(substr($haystack, 0, $n), $needle) === 0;
}

/**
 * Does string end with another string?
 *
 * @param string $string
 * @param string $prefix
 * @return boolean
 * @see \zesk\StringTools::ends
 * @see str_ends_with()
 * @deprecated 2022-02
 */
function ends(string $haystack, string $needle): bool {
	$n = strlen($needle);
	if ($n === 0) {
		return true;
	}
	return (substr($haystack, -$n) === $needle);
}

/**
 * Does string end with another string (insensitive)?
 *
 * @param string $string
 * @param string $prefix
 * @return boolean
 * @see \zesk\StringTools::endsi
 * @see str_ends_with()
 * @deprecated 2022-02
 */
function endsi(string $haystack, string $needle): bool {
	$n = strlen($needle);
	if ($n === 0) {
		return true;
	}
	return strcasecmp(substr($haystack, -$n), $needle) === 0;
}

/**
 * Set or get the newline character.
 * Probably should deprecate this for an output class.
 *
 * @param ?string $set
 * @return string
 * @deprecated 2017-12
 */
function newline(string $set = null): string {
	if ($set !== null) {
		zesk()->newline = $set;
		return $set;
	}
	return zesk()->newline;
}

/**
 * Return a backtrace of the stack
 *
 * @param int $n
 *            The number of frames to output. Pass a negative number to pass all frames.
 */
function _backtrace(int $n = -1): string {
	$bt = debug_backtrace();
	array_shift($bt);
	if ($n <= 0) {
		$n = count($bt);
	}
	$result = [];
	foreach ($bt as $i) {
		$file = 'closure';
		$line = '-none-';
		$class = '-noclass-';
		$type = $function = $args = null;
		extract($i, EXTR_OVERWRITE | EXTR_IF_EXISTS);
		$line = "$file: $line $class$type$function";
		if (is_array($args)) {
			$arg_dump = [];
			foreach ($args as $index => $arg) {
				if (is_object($arg)) {
					$arg_dump[$index] = $arg::class;
				} elseif (is_scalar($arg)) {
					$arg_dump[$index] = PHP::dump($arg);
				} else {
					$arg_dump[$index] = type($arg);
				}
			}
			if (count($arg_dump)) {
				$line .= '(' . implode(', ', $arg_dump) . ')';
			}
		}
		$result[] = $line;
		if (--$n <= 0) {
			break;
		}
	}
	return implode("\n", $result);
}

/**
 * Output a backtrace of the stack
 *
 * @param bool $exit Exit the program
 * @param int $n The number of frames to output
 * @return void
 */
function backtrace(bool $exit = true, int $n = -1): void {
	echo _backtrace($n);
	if ($exit) {
		exit($exit);
	}
}

/**
 * Returns the name of the function or class/method which called the current code.
 * Useful for debugging.
 *
 * Moved from Debug:: class to assist in profiling bootstrap functions (for example)
 * which don't have the autoloader set yet.
 *
 * @param int $depth
 * @return string
 * @see debug_backtrace()
 * @see Debug::calling_function
 */
function calling_function(int $depth = 1, bool $include_line = true): string {
	$bt = debug_backtrace();
	array_shift($bt); // Remove this function from the stack
	if ($depth > 0) {
		while ($depth-- !== 0) {
			array_shift($bt);
		}
	}
	$top = array_shift($bt);
	if (!$top) {
		return '-no calling function $depth deep-';
	}
	return ($top['file'] ?? '') . ' ' . ($top['class'] ?? '') . ($top['type'] ?? '') . $top['function'] . ($include_line ? ':' . ($top['line'] ?? '-') : '');
}

/**
 * Dumps a variable using print_r and surrounds with <pre> tag
 * Optionally defined because "dump" is also defined by Drush
 *
 * Probably should switch to a namespace version of this as well.
 *
 * @param mixed $x
 *            Variable to dump
 * @param boolean $html
 *            Whether to dump as HTML or not (surround by pre tags)
 * @return void echos to page
 * @see print_r
 */
if (!function_exists('dump')) {
	function dump(): void {
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
 */
function _dump($x): string {
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
 */
function toBool(mixed $value, bool $default = null): ?bool {
	static $true_values = [
		1,
		'1',
		't',
		'y',
		'yes',
		'on',
		'enabled',
		'true',
	];
	static $false_values = [
		0,
		'',
		'0',
		'f',
		'n',
		'no',
		'off',
		'disabled',
		'false',
		'null',
	];
	if (is_bool($value)) {
		return $value;
	}
	if (is_object($value) || (is_array($value) && count($value) > 0)) {
		return true;
	}
	if (!is_scalar($value)) {
		/* is_scalar(null) === false */
		return $default;
	}
	if (is_string($value)) {
		$value = strtolower($value);
	}
	if (in_array($value, $true_values, true)) {
		return true;
	}
	if (in_array($value, $false_values, true)) {
		return false;
	}
	return $default;
}

/**
 * Ensures a value is an integer value.
 * If not, the default value is returned.
 *
 * @param mixed $s
 *            Value to convert to integer
 * @param mixed $def
 *            The default value. Not converted to integer.
 * @return integer The integer value, or $def if it can not be converted to an integer
 */
function toInteger(mixed $s, int $def = 0): int {
	return is_numeric($s) ? intval($s) : $def;
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
 */
function toFloat(mixed $s, float $def = null): float {
	return is_numeric($s) ? floatval($s) : floatval($def);
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
 */
function toList(mixed $mixed, array $default = [], string $delimiter = ';'): array {
	if ($mixed === '' || $mixed === null) {
		return $default;
	} elseif (is_scalar($mixed)) {
		return explode($delimiter, strval($mixed));
	} elseif (is_array($mixed)) {
		return $mixed;
	} elseif (is_object($mixed) && method_exists($mixed, 'to_list')) {
		return toList($mixed->to_list());
	} else {
		return $default;
	}
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
 */
function toArray(mixed $mixed, array $default = []): array {
	if (is_array($mixed)) {
		return $mixed;
	}
	if (is_scalar($mixed) && $mixed !== false) {
		return [
			$mixed,
		];
	}
	if (is_object($mixed) && method_exists($mixed, 'to_array')) {
		return $mixed->toArray();
	}
	return $default;
}

/**
 * Converts a PHP value to a string, usually for debugging.
 *
 * @param mixed $mixed
 * @return string
 */
function toText(mixed $mixed): string {
	if (is_bool($mixed)) {
		return $mixed ? 'true' : 'false';
	}
	if ($mixed === null) {
		return 'null';
	}
	if (is_array($mixed)) {
		return Text::format_pairs($mixed);
	}
	return strval($mixed);
}

/**
 * Converts a PHP value to a string, usually for debugging.
 *
 * @param mixed $mixed
 * @return string
 * @deprecated 2022-02
 */
function to_text(mixed $mixed): string {
	return toText($mixed);
}

/**
 * Converts an object into an iterator, suitable for a foreach
 *
 * @param mixed $mixed
 * @return array|Iterator
 * @deprecated 2022-01
 */
function to_iterator(mixed $mixed): iterable {
	zesk()->deprecated(__METHOD__);
	return toIterable($mixed);
}

/**
 * Gently coerce things to iterable
 *
 * @param mixed $mixed
 * @return iterable
 * @deprecated 2022-12
 */
function to_iterable(mixed $mixed): iterable {
	zesk()->deprecated(__METHOD__);
	return toIterable($mixed);
}

/**
 * Gently coerce things to iterable
 *
 * @param mixed $mixed
 * @return iterable
 */
function toIterable(mixed $mixed): iterable {
	if (is_iterable($mixed)) {
		return $mixed;
	}
	if (empty($mixed)) {
		return [];
	}
	return [
		$mixed,
	];
}

/**
 * Converts 20G to integer value
 *
 * @param string|int $mixed
 * @param int $default
 * @return int
 */
function toBytes(string|int $mixed, int $default = 0): int {
	if (is_int($mixed)) {
		return $mixed;
	}
	$mixed = strtolower(trim($mixed));
	if (is_numeric($mixed)) {
		return intval($mixed);
	}
	if (!preg_match('/[0-9]+([gmk])/', $mixed, $matches)) {
		return toInteger($mixed, $default);
	}
	$b = intval($mixed);
	$pow = ['g' => 3, 'm' => 2, 'k' => 1][$matches[1]] ?? 0;
	return $b * (1024 ** $pow);
}

/**
 * Converts 20G to integer value
 *
 * @param string $mixed
 * @param int $default
 * @return float
 * @deprecated 2022-11
 */
function to_bytes(string $mixed, int $default = 0): float {
	return toBytes($mixed, $default);
}

/**
 * Localize a string to the current locale.
 *
 * @param string $phrase
 *            Phrase to translate
 * @return string
 * @deprecated 2017-12 Use $application->locale->__($phrase) instead.
 * @see Locale::__invoke
 */
function __(array|string $phrase): string {
	$args = func_get_args();
	$locale = Kernel::singleton()->application()->locale;
	array_shift($args);
	return count($args) === 1 && is_array($args[0]) ? $locale($phrase, $args[0]) : $locale($phrase, $args);
}

/**
 * Shorthand for array_key_exists($k,$a) ? $a[$k] : $default.
 * Asserts $a is an array, $k is a string or numeric.
 *
 * NOTE: In PHP 7 this will go away and we can use
 *
 * $foo = $a['key'] ?? $default;
 *
 * FINALLY.
 * @param array $a
 *            An array to look in
 * @param string $k
 *            The key to look for
 * @param mixed $default
 *            A value to return if $a[$k] is not set
 * @return mixed The value of $a[$k], or $default if not set
 * @deprecated 2022-01 PHP8
 * @see https://wiki.php.net/rfc/isset_ternary
 */
function avalue(array $a, string|int $k, mixed $default = null): mixed {
	$k = strval($k);
	return array_key_exists($k, $a) ? $a[$k] : $default;
}

/**
 * Shorthand for array_key_exists($k,$a) || !empty($a[$k]) ? $a[$k] : $default.
 * Asserts $a is an array.
 * @param array $a
 *            An array to look in
 * @param string $k
 *            The key to look for
 * @param mixed $default
 *            A value to return if $a[$k] is not set or is empty
 * @return mixed The value of $a[$k] if non-empty, or $default if not set or empty
 * @deprecated 2022-01 PHP8
 *
 */
function aevalue(array $a, string|int $k, mixed $default = null): mixed {
	$k = strval($k);
	return array_key_exists($k, $a) && !empty($a[$k]) ? $a[$k] : $default;
}

/**
 * Convert a deep object into a flat one (string)
 *
 * @param mixed $mixed
 * @return string
 * @throws Exception_Semantics
 */
function flatten(mixed $mixed): string {
	if (is_array($mixed)) {
		$mixed = ArrayTools::flatten($mixed);
	}
	if ($mixed === null) {
		return '';
	}
	if (is_object($mixed)) {
		return method_exists($mixed, '__toString') ? strval($mixed) : flatten(get_object_vars($mixed));
	}
	if (is_scalar($mixed)) {
		return $mixed;
	}
	return JSON::encode($mixed);
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
 */
function tr(mixed $mixed, array $map): mixed {
	if (can_iterate($mixed)) {
		foreach ($mixed as $k => $v) {
			$mixed[$k] = tr($v, $map);
		}
		return $mixed;
	} elseif (is_string($mixed)) {
		$map = ArrayTools::flatten($map);
		return strtr($mixed, $map);
	} elseif (is_object($mixed)) {
		return $mixed instanceof Hookable ? $mixed->callHookArguments('tr', [
			$map,
		], $mixed) : $mixed;
	} else {
		return $mixed;
	}
}

/**
 * preg_replace for arrays
 *
 * @param string $pattern
 *            Pattern to match
 * @param string $replacement
 *            Replacement string
 * @param mixed $subject
 *            String or array to manipulate
 * @return array|string|null
 */
function preg_replace_mixed(string $pattern, string $replacement, array|string $subject): array|string|null {
	if (is_array($subject)) {
		foreach ($subject as $k => $v) {
			$subject[$k] = preg_replace_mixed($pattern, $replacement, $v);
		}
		return $subject;
	}
	return preg_replace($pattern, $replacement, $subject);
}

/**
 * preg_replace_callback for arrays
 *
 * @param string $pattern
 *            Pattern to match
 * @param callable $callback
 *            Replacement string
 * @param array|string $subject
 *            String or array to manipulate
 * @return array|string
 */
function preg_replace_callback_mixed(string $pattern, callable $callback, array|string $subject): array|string {
	if (is_array($subject)) {
		foreach ($subject as $k => $v) {
			$subject[$k] = preg_replace_callback_mixed($pattern, $callback, $v);
		}
		return $subject;
	}
	return preg_replace_callback($pattern, $callback, $subject);
}

/**
 * Map array keys and values
 *
 * @param array $target
 *            Array to modify keys AND values
 * @param array $map
 *            Array of name => value of search => replace
 * @param boolean $insensitive
 *            Case sensitive search/replace (defaults to true)
 * @param string $prefix_char
 *            Prefix character for tokens (defaults to "{")
 * @param string $suffix_char
 *            Suffix character for tokens (defaults to "}")
 * @return array
 */
function amap(array $target, array $map, bool $insensitive = false, string $prefix_char = '{', string $suffix_char = '}'): array {
	return map(kmap($target, $map, $insensitive, $prefix_char, $suffix_char), $map, $insensitive, $prefix_char, $suffix_char);
}

/**
 * Map keys instead of values
 *
 * @param array $target
 *            Array to modify keys
 * @param array $map
 *            Array of name => value of search => replace
 * @param boolean $insensitive
 *            Case sensitive search/replace (defaults to true)
 * @param string $prefix_char
 *            Prefix character for tokens (defaults to "{")
 * @param string $suffix_char
 *            Suffix character for tokens (defaults to "}")
 * @return array
 */
function kmap(array $target, array $map, bool $insensitive = false, string $prefix_char = '{', string $suffix_char = '}'): array {
	$new_mixed = [];
	foreach ($target as $key => $value) {
		$new_mixed[map($key, $map, $insensitive, $prefix_char, $suffix_char)] = $value;
	}
	return $new_mixed;
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
 * @test_inline $this->assertEquals(map("{a}{B}", array("a" => "ala")), "ala{B}");
 * @test_inline $this->assertEquals(map("{a}{B}", array("a" => "ala"), true), "ala{b}");
 *
 * @param mixed $mixed Target to modify
 * @param array $map Array of name => value of search => replace
 * @param boolean $insensitive Case sensitive search/replace (defaults to false)
 * @param string $prefix_char Prefix character for tokens (defaults to "{")
 * @param string $suffix_char Suffix character for tokens (defaults to "}")
 * @return array|string
 */
function map(array|string $mixed, array $map, bool $insensitive = false, string $prefix_char = '{', string $suffix_char = '}'): array|string {
	if ($insensitive) {
		$map = array_change_key_case($map);
	}
	$s = [];
	foreach ($map as $k => $v) {
		if (is_array($v)) {
			if (ArrayTools::isList($v)) {
				$v = implode(';', ArrayTools::flatten($v));
			} else {
				try {
					$v = JSON::encode($v);
				} catch (\zesk\Exception_Semantics $e) {
					$v = null;
				}
			}
		} elseif (is_object($v)) {
			if (method_exists($v, '__toString')) {
				$v = strval($v);
			} else {
				$v = $v::class;
			}
		}
		$s[$prefix_char . $k . $suffix_char] = $v;
	}
	if ($insensitive) {
		static $func = null;
		if (!$func) {
			$func = fn ($matches) => strtolower($matches[0]);
		}
		$mixed = preg_replace_callback_mixed('#' . preg_quote($prefix_char, '/') . '([-:_ =,./\'"A-Za-z0-9]+)' . preg_quote($suffix_char, '/') . '#i', $func, $mixed);
	}
	// tr("{a}", array("{a} => null)) = "null"
	return tr($mixed, $s);
}

/**
 * Clean map tokens from a string
 *
 * @test_inline $this->assertEquals(map_clean("He wanted {n} days"), "He wanted  days");
 * @test_inline $this->assertEquals(map_clean("{}{}{}{}{}{all}{of}{this}{is}{removed}except}{}"),"except}");
 *
 * @param mixed $mixed
 * @param string $prefix_char
 * @param string $suffix_char
 * @return mixed
 */
function map_clean(string $mixed, string $prefix_char = '{', string $suffix_char = '}'): string {
	$delimiter = '#';
	$suffix = preg_quote($suffix_char, $delimiter);
	return preg_replace_mixed($delimiter . preg_quote($prefix_char, $delimiter) . '[^' . $suffix . ']*' . $suffix . $delimiter, '', $mixed);
}

/**
 * Return true if string contains tokens which can be mapped using prefix/suffix
 *
 * @param string $string
 * @return boolean
 */
function can_map(string $string, string $prefix_char = '{', string $suffix_char = '}'): bool {
	$tokens = map_tokens($string, $prefix_char, $suffix_char);
	return count($tokens) !== 0;
}

/**
 * Retrieve map tokens from a string
 *
 * @param string $subject
 * @param string $prefix_char
 * @param string $suffix_char
 * @return array
 */
function map_tokens(string $subject, string $prefix_char = '{', string $suffix_char = '}'): array {
	$delimiter = '#';
	$prefix = preg_quote($prefix_char, $delimiter);
	$suffix = preg_quote($suffix_char, $delimiter);
	$matches = [];
	$pattern = $delimiter . $prefix . '[^' . $suffix . ']*' . $suffix . $delimiter;
	if (!preg_match_all($pattern, $subject, $matches)) {
		return [];
	}
	return $matches[0];
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
 */
function pair(string $a, string $delim = '.', string $left = '', string $right = '', string $include_delimiter = ''): array {
	$n = strpos($a, $delim);
	$delim_len = strlen($delim);
	return ($n === false) ? [
		$left,
		$right,
	] : [
		substr($a, 0, $n + ($include_delimiter === 'left' ? $delim_len : 0)),
		substr($a, $n + ($include_delimiter === 'right' ? 0 : $delim_len)),
	];
}

/**
 * Same as pair, but does a reverse search for the delimiter
 *
 * @param string $a
 *            A string to parse into a pair
 * @param string $delim
 *            The delimiter to break the string apart
 * @param string $left
 *            The default left value if delimiter is not found
 * @param string $right
 *            The default right value if delimiter is not found
 * @param string $include_delimiter
 *            If "left" includes the delimiter in the left value
 *            If "right" includes the delimiter in the right value
 *          Any other value the delimiter is stripped from the results
 * @return array A size 2 array containing the left and right portions of the pair
 * @see pair
 */
function reversePair(string $a, string $delim = '.', string $left = '', string $right = '', string
$include_delimiter = ''): array {
	$n = strrpos($a, $delim);
	$delim_len = strlen($delim);
	return ($n === false) ? [
		$left,
		$right,
	] : [
		substr($a, 0, $n + ($include_delimiter === 'left' ? $delim_len : 0)),
		substr($a, $n + ($include_delimiter === 'right' ? 0 : $delim_len)),
	];
}

/**
 * @param string $a
 * @param string $delim
 * @param string $left
 * @param string $right
 * @param string $include_delimiter
 * @return string[]
 */
function pairr(string $a, string $delim = '.', string $left = '', string $right = '', string $include_delimiter = ''): array {
	return reversePair($a, $delim, $left, $right, $include_delimiter);
}

/**
 * Glue to strings together, ensuring there is one and only one character sequence between them
 *
 * Similar to path, but more useful to construct urls, e.g
 *
 * glue("http://localhost/test/","/","/foo") === "http://localhost/test/foo";
 * glue("http://localhost/test","/","/foo") === "http://localhost/test/foo";
 * glue("http://localhost/test/","/","foo") === "http://localhost/test/foo";
 * glue("http://localhost/test","/","foo") === "http://localhost/test/foo";
 *
 * @param string $left
 * @param string $glue
 * @param string $right
 * @return string
 */
function glue(string $left, string $glue, string $right): string {
	return rtrim($left, $glue) . $glue . ltrim($right, $glue);
}

/**
 * Unquote a string and optionally return the quote removed.
 *
 * Meant to work with unique pairs of quotes, so passing in "[A[B[C" will break it.
 *
 * @param string $string_to_unquote
 *            A string to unquote
 * @param string $quotes
 *            A list of quote pairs to unquote
 * @param string $left_quote
 *            Returns the left quote removed
 * @return string
 */
function unquote(string $string_to_unquote, string $quotes = '\'\'""', string &$left_quote = ''): string {
	if (strlen($string_to_unquote) < 2) {
		$left_quote = '';
		return $string_to_unquote;
	}
	$q = substr($string_to_unquote, 0, 1);
	$quote_left_offset = strpos($quotes, $q);
	if ($quote_left_offset === false) {
		$left_quote = '';
		return $string_to_unquote;
	}
	$quote_right = $quotes[$quote_left_offset + 1];
	if (substr($string_to_unquote, -1) === $quote_right) {
		$left_quote = $quotes[$quote_left_offset];
		return substr($string_to_unquote, 1, -1);
	}
	return $string_to_unquote;
}

/**
 * Generic function to create paths correctly. Note that any double-separators are removed and converted to single-slashes so
 * this is unsuitable for use with URLs. Use glue() instead.
 *
 * @param string separator Token used to divide path
 * @param array mixed List of path items, or array of path items to concatenate
 * @return string with a properly formatted path
 * @see glue
 * @see domain
 * @inline_test path_from_array("/", ["", "", ""]) === "/"
 * @inline_test path_from_array("/", ["", null, false]) === "/"
 * @inline_test path_from_array("/", ["", "", "", null, false, "a", "b"]) === "/a/b"
 */
function path_from_array(string $separator, array $mixed): string {
	$r = array_shift($mixed);
	if (is_array($r)) {
		$r = path_from_array($separator, $r);
	} elseif (!is_string($r)) {
		$r = '';
	}
	foreach ($mixed as $p) {
		if ($p === null) {
			continue;
		}
		if (is_array($p)) {
			$p = path_from_array($separator, $p);
		}
		if (is_string($p)) {
			$r .= ((substr($r, -1) === $separator || substr($p, 0, 1) === $separator)) ? $p : $separator . $p;
		}
	}
	$separator_quoted = preg_quote($separator);
	return preg_replace("|$separator_quoted$separator_quoted+|", $separator, $r);
}

/**
 * Create a file path and ensure only one slash appears between path entries. Do not use this
 * with URLs, use glue instead.
 *
 * @param mixed path Variable list of path items, or array of path items to concatenate
 * @return string with a properly formatted path
 * @see glue
 * @see domain
 */
function path(/* dir, dir, ... */): string {
	$args = func_get_args();
	$r = path_from_array('/', $args);
	return preg_replace('|(/\.)+/|', '/', $r); // TODO Test this doesn't munge foo/.bar
}

/**
 * Create a domain name ensure one and only one dot appears between entries
 *
 * @param mixed path Variable list of path items, or array of path items to concatenate
 * @return string with a properly formatted domain path
 * @see glue
 * @see domain
 */
function domain(/* name, name, ... */): string {
	$args = func_get_args();
	return trim(path_from_array('.', $args), '.');
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
 */
function clamp(mixed $minValue, mixed $value, mixed $maxValue): mixed {
	if ($value < $minValue) {
		return $minValue;
	}
	if ($value > $maxValue) {
		return $maxValue;
	}
	return $value;
}

/**
 * Utility for comparing floating point numbers where inaccuracies and rounding in math
 * produces close numbers which are not actually equal.
 *
 * @param float $a
 * @param float $b
 * @param float $epsilon
 * @return boolean
 */
function real_equal(float $a, float $b, float $epsilon = 1e-5): bool {
	return abs($a - $b) <= $epsilon;
}

/**
 * Can I do foreach on this object?
 *
 * @param mixed $mixed
 * @return boolean
 */
function can_iterate(mixed $mixed): bool {
	return is_array($mixed) || $mixed instanceof Traversable;
}
/**
 * Is this value close (enough) to zero? Handles rounding errors with double-precision values.
 *
 * @param double $value
 * @param float $epsilon
 * @return boolean
 */
function is_zero(float|int $value, float $epsilon = 1e-5): bool {
	return abs($value) < $epsilon;
}

if (!function_exists('is_countable')) {
	/**
	 * Verify that the contents of a variable is a countable value
	 *
	 * @param mixed $object
	 * @return boolean
	 */
	function is_countable(mixed $object): bool {
		return is_array($object) || $object instanceof Countable;
	}
}

/**
 * Simple integer comparison routine, syntactic sugar
 *
 * @param int $min
 * @param int $x
 * @param int $max
 * @return boolean
 */
function integer_between(int $min, int $x, int $max): bool {
	return ($x >= $min) && ($x <= $max);
}

/**
 * Get the date in the UTC locale
 *
 * @param ?int $ts
 * @return array
 * @see getdate
 * @todo Does this do anything useful?
 */
function utc_getdate(int $ts = null): array {
	$otz = date_default_timezone_get();
	date_default_timezone_set('UTC');
	$result = getdate($ts);
	date_default_timezone_set($otz);
	return $result;
}

/**
 * Parse a time in UTC locale
 *
 * @param string $ts
 * @return ?int
 */
function utc_parse_time(string $ts): ?int {
	$otz = date_default_timezone_get();
	date_default_timezone_set('UTC');
	$result = parse_time($ts);
	date_default_timezone_set($otz);
	return $result;
}

/**
 * Parse a time in the current locale
 *
 * @param string $ts
 * @return ?int number, or null if can not parse
 */
function parse_time(string $ts): ?int {
	if (empty($ts)) {
		return null;
	}
	$x = @strtotime($ts);
	if ($x < 0 || $x === false) {
		return null;
	}
	return $x;
}

/**
 * Determine if a string is a properly formatted date
 *
 * @param string $x
 *            A string to check
 * @return boolean true if $x is a valid date
 */
function is_date(mixed $x): bool {
	if (empty($x)) {
		return false;
	}
	$result = @strtotime($x);
	if ($result < 0 || $result === false) {
		return false;
	}
	return true;
}

/**
 * Determine if a string is a possible email address
 *
 * @param string $email
 * @return boolean
 */
function is_email(string $email): bool {
	return preg_match('/^' . PREG_PATTERN_EMAIL . '$/i', $email) !== 0;
}

/**
 * Determine if a string is a possible phone number
 *
 * @param string $phone
 * @return boolean
 */
function is_phone(string $phone): bool {
	return preg_match('/^\s*\+?[- \t0-9.)(x]{7,}\s*$/', $phone) !== 0;
}

/**
 * Gets a value from an array using a delimited separated path.
 * // Get the value of $array['foo']['bar']
 *
 * $value = apath($array, 'foo.bar');
 * @param array $array
 * @param mixed $path string path or array
 * @param mixed $default value to return if value is not found
 * @param string $separator string separator for string paths
 * @return mixed
 * @see apath_set
 */
function &apath(array $array, array|string $path, mixed $default = null, string $separator = '.'): mixed {
	// Split the keys by $separator
	$keys = is_array($path) ? $path : explode($separator, $path);
	while (is_array($array)) {
		$key = array_shift($keys);
		if (!array_key_exists($key, $array)) {
			break;
		}
		if (count($keys) === 0) {
			return $array[$key];
		}
		$array = &$array[$key];
	}
	return $default;
}

/**
 * Partner of apath - sets an array path to a specific value
 *
 * @param array $array
 * @param array|string $path A path into the array separated by $separator (e.g. "document.title")
 * @param mixed $value Value to set the path in the tree. Use null to delete the target item.
 * @param string $separator Character used to separate levels in the array
 * @return mixed
 */
function &apath_set(array &$array, string|array $path, mixed $value = null, string $separator = '.'): mixed {
	if ($value === null) {
		zesk()->deprecated('use apath_unset');
		apath_unset($array, toList($path, $separator));
		return $array;
	}
	$current = &$array;
	// Split the keys by separator
	$keys = is_array($path) ? $path : explode($separator, $path);
	while (count($keys) > 1) {
		$key = array_shift($keys);
		if (isset($current[$key])) {
			if (!is_array($current[$key])) {
				$current[$key] = [];
			}
		} else {
			$current[$key] = [];
		}
		$current = &$current[$key];
	}
	$key = array_shift($keys);
	$current[$key] = $value;
	return $current[$key];
}

/**
 * Partner of apath - removes an array path and value
 *
 * @param array $array Array to manipulate
 * @param array $keys Path to item in the tree
 * @return bool Value was found and unset.
 */
function apath_unset(array &$array, array $keys): bool {
	$current = &$array;
	// Split the keys by separator
	while (count($keys) > 1) {
		$key = array_shift($keys);
		if (isset($current[$key])) {
			if (!is_array($current[$key])) {
				return false;
			}
		} else {
			return false;
		}
		$current = &$current[$key];
	}
	$key = array_shift($keys);
	if (!isset($current[$key])) {
		return false;
	}
	unset($current[$key]);
	return true;
}

const ZESK_INTERNAL_WEIGHT_FIRST = 'zesk-first';
const ZESK_WEIGHT_FIRST = 'first';
const ZESK_WEIGHT_LAST = 'last';
const ZESK_INTERNAL_WEIGHT_LAST = 'zesk-last';

/**
 * Convert our special weights into a number
 *
 * @param string|float|int $weight
 * @return float
 */
function zesk_weight(string|float|int $weight): float {
	static $weights = [
		ZESK_INTERNAL_WEIGHT_FIRST => -1e300,
		ZESK_WEIGHT_FIRST => -1e299,
		ZESK_WEIGHT_LAST => 1e299,
		ZESK_INTERNAL_WEIGHT_LAST => 1e300,
	];
	return floatval($weights[strval($weight)] ?? $weight);
}

/**
 * Sort an array based on the weight array index
 * Support special terms such as "first" and "last"
 *
 * use like:
 *
 * `usort` does not maintain index association:
 *
 * usort($this->links_sorted, "zesk_sort_weight_array"));
 *
 * `uasort` DOES maintain index association:
 *
 * uasort($this->links_sorted, "zesk_sort_weight_array"));
 *
 * @param array $a
 * @param array $b
 * @return int
 * @see uasort
 * @see usort
 */
function zesk_sort_weight_array(array $a, array $b): int {
	// Get weight a, convert to double
	$aw = array_key_exists('weight', $a) ? zesk_weight($a['weight']) : 0;

	// Get weight b, convert to double
	$bw = array_key_exists('weight', $b) ? zesk_weight($b['weight']) : 0;

	// a < b -> -1
	// a > b -> 1
	// a === b -> 0
	return $aw < $bw ? -1 : ($aw > $bw ? 1 : 0);
}

/**
 * Revers sorting a weight array so highest weights are at the top
 *
 * @param array $a
 * @param array $b
 * @return int
 */
function zesk_sort_weight_array_reverse(array $a, array $b): int {
	return zesk_sort_weight_array($b, $a);
}

/**
 * Convert a global name to a standard internal format.
 *
 * @param string $key
 * @return array
 */
function _zesk_global_key(string $key): array {
	return explode(ZESK_GLOBAL_KEY_SEPARATOR, strtr(strtolower($key), [
		'__' => ZESK_GLOBAL_KEY_SEPARATOR,
		'.' => '_',
		'/' => '_',
		'-' => '_',
		' ' => '_',
	]));
}

/**
 * Normalize a zesk global key and return a string
 *
 * @param string $key
 * @return string
 */
function zesk_global_key_normalize(string $key): string {
	return implode(ZESK_GLOBAL_KEY_SEPARATOR, _zesk_global_key($key));
}

/**
 * Do we need to deal with platform issues on Windows? Probably, you know, because.
 *
 * @return boolean
 */
function is_windows(): bool {
	return PATH_SEPARATOR === '\\';
}

/**
 * For classes which are serialized but do not want to serialize the Application, use this
 * to restore it upon __wakeup
 */
function __wakeup_application(): Application {
	return Kernel::singleton()->application();
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
function app(): Application {
	$kernel = zesk();
	$kernel->deprecated();
	return $kernel->application();
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
 * @deprecated 2022-05
 */
function to_list(mixed $mixed, array $default = [], string $delimiter = ';'): array {
	return toList($mixed, $default, $delimiter);
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
 * @deprecated 2022-02 PSR
 */
function to_array(mixed $mixed, array $default = []): array {
	return toArray($mixed, $default);
}

/**
 * Ensures a value is an integer value.
 * If not, the default value is returned.
 *
 * @param mixed $s
 *            Value to convert to integer
 * @param mixed $def
 *            The default value. Not converted to integer.
 * @return integer The integer value, or $def if it can not be converted to an integer
 * @deprecated 2022-05
 */
function to_integer(mixed $s, int $def = 0): int {
	return toInteger($s, $def);
}

/**
 * Convert to a boolean or null if not able to be parsed
 *
 * @param mixed $value
 * @param bool|null $default
 * @return bool|null
 * @deprecated 2022-05
 */
function to_bool(mixed $value, bool $default = null): ?bool {
	return toBool($value, $default);
}
