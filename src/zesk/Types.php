<?php
declare(strict_types=1);
/**
 * Basic type conversions and parsing (bool, int, string, array, list, iterable, email)
 *
 * @package zesk
 * @subpackage core
 * @author kent
 * @copyright Copyright &copy; 2023, Market Acumen, Inc.
 */

namespace zesk;

use Stringable;
use Traversable;
use TypeError;
use zesk\Exception\ParseException;
use zesk\Exception\SemanticsException;
use zesk\Interface\Simplifiable;

class Types
{
	/**
	 *
	 */
	private const INTERNAL_WEIGHT_FIRST = 'zesk-first';

	/**
	 *
	 */
	public const WEIGHT_FIRST = 'first';

	/**
	 *
	 */
	public const WEIGHT_LAST = 'last';

	/**
	 *
	 */
	private const INTERNAL_WEIGHT_LAST = 'zesk-last';

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
	public const PREG_PATTERN_IP6 = '[a-z0-9-]*[a-z0-9]:' . '(?:' . '[\x01-\x08\x0b\x0c\x0e-\x1f\x21-\x5a\x53-\x7f]|\\[\x01-\x09\x0b\x0c\x0e-\x7f]' . ')+';

	public const PREG_PATTERN_IP4_DIGIT = '(2(5[0-5]|[0-4][0-9])|1[0-9][0-9]|[1-9]?[0-9])';

	public const PREG_PATTERN_IP4_DIGIT1 = '(2(5[0-5]|[0-4][0-9])|1[0-9][0-9]|[1-9][0-9]|[1-9])';

	public const PREG_PATTERN_IP4_0 = '(?:' . self::PREG_PATTERN_IP4_DIGIT . '\.){3}' . self::PREG_PATTERN_IP4_DIGIT;

	public const PREG_PATTERN_IP4_1 = self::PREG_PATTERN_IP4_DIGIT1 . '\.(?:' . self::PREG_PATTERN_IP4_DIGIT . '\.){2}' . self::PREG_PATTERN_IP4_DIGIT1;

	/**
	 * A regular expression pattern for matching email addresses, not delimited. Should run case-insensitive.
	 *
	 * Email user@ characters
	 */
	public const PREG_PATTERN_EMAIL_USERNAME_CHAR = '[-a-z0-9!#$%&\'*+\\/=?^_`{|}~]';

	/**
	 * Email user@ sequences
	 */
	public const PREG_PATTERN_EMAIL_USERNAME = '(?:' . self::PREG_PATTERN_EMAIL_USERNAME_CHAR . '+(?:\\.' . self::PREG_PATTERN_EMAIL_USERNAME_CHAR . '+)*|"(?:[\x01-\x08\x0b\x0c\x0e-\x1f\x21\x23-\x5b\x5d-\x7f]|\\[\x01-\x09\x0b\x0c\x0e-\x7f])*")';

	public const PREG_PATTERN_ALPHANUMERIC_CHAR = '[a-z0-9]';

	public const PREG_PATTERN_ALPHANUMERIC_DASH_CHAR = '[a-z0-9-]';

	public const PREG_PATTERN_EMAIL_DOMAIN_SINGLE = '(?:' . self::PREG_PATTERN_ALPHANUMERIC_CHAR . '(?:' . self::PREG_PATTERN_ALPHANUMERIC_DASH_CHAR . '*' . self::PREG_PATTERN_ALPHANUMERIC_CHAR . ')?' . '\.)*' . self::PREG_PATTERN_ALPHANUMERIC_CHAR . '(?:' . self::PREG_PATTERN_ALPHANUMERIC_DASH_CHAR . '*' . self::PREG_PATTERN_ALPHANUMERIC_CHAR . ')?';

	public const PREG_PATTERN_EMAIL_DOMAIN_DOTTED = '(?:' . self::PREG_PATTERN_ALPHANUMERIC_CHAR . '(?:' . self::PREG_PATTERN_ALPHANUMERIC_DASH_CHAR . '*' . self::PREG_PATTERN_ALPHANUMERIC_CHAR . ')?' . '\.)+' . self::PREG_PATTERN_ALPHANUMERIC_CHAR . '(?:' . self::PREG_PATTERN_ALPHANUMERIC_DASH_CHAR . '*' . self::PREG_PATTERN_ALPHANUMERIC_CHAR . ')?';

	public const PREG_PATTERN_EMAIL_DOMAIN_IP = '\[' . '(?:' . self::PREG_PATTERN_IP4_0 . '|' . self::PREG_PATTERN_IP6 . ')' . '\]';

	public const PREG_PATTERN_EMAIL_DOMAIN = '(?:' . self::PREG_PATTERN_EMAIL_DOMAIN_DOTTED . '|' . self::PREG_PATTERN_EMAIL_DOMAIN_IP . ')';

	public const PREG_PATTERN_EMAIL_SIMPLE_DOMAIN = '(?:' . self::PREG_PATTERN_EMAIL_DOMAIN_SINGLE . '|' . self::PREG_PATTERN_EMAIL_DOMAIN_IP . ')';

	public const PREG_PATTERN_EMAIL = self::PREG_PATTERN_EMAIL_USERNAME . '@' . self::PREG_PATTERN_EMAIL_DOMAIN;

	public const PREG_PATTERN_SIMPLE_EMAIL = self::PREG_PATTERN_EMAIL_USERNAME . '@' . self::PREG_PATTERN_EMAIL_SIMPLE_DOMAIN;

	/**
	 * Determine if a string is a properly formatted date
	 *
	 * @param string $x
	 *            A string to check
	 * @return boolean true if $x is a valid date
	 */
	public static function isDate(mixed $x): bool
	{
		if (empty($x) || !is_string($x)) {
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
	public static function isEmail(string $email): bool
	{
		return preg_match('/^' . self::PREG_PATTERN_EMAIL . '$/i', $email) !== 0;
	}

	/**
	 * Determine if a string is a possible email address
	 *
	 * @param string $email
	 * @return boolean
	 */
	public static function isSimpleEmail(string $email): bool
	{
		return preg_match('/^' . self::PREG_PATTERN_SIMPLE_EMAIL . '$/i', $email) !== 0;
	}

	/**
	 * Determine if a string is a valid IP4 address
	 *
	 * @param string $content
	 * @return boolean
	 */
	public static function isIP4(string $content): bool
	{
		return preg_match('/^' . self::PREG_PATTERN_IP4_1 . '$/i', $content) !== 0;
	}

	/**
	 * Determine if a string is a possible phone number
	 *
	 * @param string $phone
	 * @return boolean
	 */
	public static function isPhone(string $phone): bool
	{
		return preg_match('/^\s*\+?[- \t0-9.)(x]{7,}\s*$/', $phone) !== 0;
	}

	/**
	 * Can I do foreach on this object?
	 *
	 * @param mixed $mixed
	 * @return boolean
	 */
	public static function canIterate(mixed $mixed): bool
	{
		return is_array($mixed) || $mixed instanceof Traversable;
	}

	/**
	 * Ensures a value is an integer value.
	 * If not, the default value is returned.
	 *
	 * @param mixed $s Value to convert to integer
	 * @param int $default The value if we can not convert to integer
	 * @return integer The integer value, or $def if it can not be converted to an integer
	 */
	public static function toInteger(mixed $s, int $default = 0): int
	{
		return is_scalar($s) ? intval($s) : $default;
	}

	/**
	 * Convert to a valid array key
	 *
	 * @param mixed $key
	 * @return string|int
	 */
	public static function toKey(mixed $key): string|int
	{
		return is_int($key) ? $key : strval($key);
	}

	/**
	 * Ensures a value is a float value.
	 * If not, the default value is returned.
	 *
	 * @param mixed $s
	 *            Value to convert to float
	 * @param ?float $def The default value. Not converted to float.
	 * @return float The value, or $def if it can not be converted to a float
	 */
	public static function toFloat(mixed $s, float $def = null): float
	{
		return floatval(is_numeric($s) ? $s : $def);
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
	public static function toList(mixed $mixed, array $default = [], string $delimiter = ';'): array
	{
		if ($mixed === '' || $mixed === null) {
			return $default;
		} elseif (is_scalar($mixed)) {
			return explode($delimiter, strval($mixed));
		} elseif (is_array($mixed)) {
			return $mixed;
		} elseif (is_object($mixed) && method_exists($mixed, 'to_list')) {
			return Types::toList($mixed->toList());
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
	public static function toArray(mixed $mixed, array $default = []): array
	{
		if (is_array($mixed)) {
			return $mixed;
		}
		if (is_scalar($mixed) && $mixed !== false) {
			return [
				$mixed,
			];
		}
		if (is_object($mixed) && method_exists($mixed, 'toArray')) {
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
	public static function toText(mixed $mixed): string
	{
		if (is_bool($mixed)) {
			return $mixed ? 'true' : 'false';
		}
		if ($mixed === null) {
			return 'null';
		}
		if (is_array($mixed)) {
			return Text::formatPairs($mixed);
		}
		return strval($mixed);
	}

	/**
	 * Gently coerce things to iterable
	 *
	 * @param mixed $mixed
	 * @return iterable
	 */
	public static function toIterable(mixed $mixed): iterable
	{
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
	public static function toBytes(string|int $mixed, int $default = 0): int
	{
		if (is_int($mixed)) {
			return $mixed;
		}
		$mixed = strtolower(trim($mixed));
		if (is_numeric($mixed)) {
			return intval($mixed);
		}
		if (!preg_match('/[0-9]+([gmk])/', $mixed, $matches)) {
			return Types::toInteger($mixed, $default);
		}
		$b = intval($mixed);
		$pow = ['g' => 3, 'm' => 2, 'k' => 1][$matches[1]] ?? 0;
		return $b * (1024 ** $pow);
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
	public static function toBool(mixed $value, ?bool $default = false): ?bool
	{
		static $true_values = [
			1, '1', 't', 'y', 'yes', 'on', 'enabled', 'true',
		];
		static $false_values = [
			0, '', '0', 'f', 'n', 'no', 'off', 'disabled', 'false', 'null',
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
	 * Converts to an object which can ultimately be represented as JSON or a related object
	 *
	 * @param mixed $mixed
	 * @return bool|int|float|array|string
	 */
	public static function simplify(mixed $mixed): bool|int|float|array|string
	{
		if (is_scalar($mixed)) {
			return $mixed;
		}
		if (is_array($mixed)) {
			return array_map(Types::simplify(...), $mixed);
		}
		if (is_object($mixed)) {
			if ($mixed instanceof Simplifiable) {
				return $mixed->simplify();
			}
			if ($mixed instanceof Stringable) {
				return strval($mixed);
			}
		}

		throw new TypeError('Unable to simplify ' . Types::type($mixed));
	}

	/**
	 * Convert a deep object into a flat one (string)
	 *
	 * @param mixed $mixed
	 * @return string|int|float|bool
	 * @throws SemanticsException
	 */
	public static function flatten(mixed $mixed): string|int|float|bool
	{
		if (is_array($mixed)) {
			$mixed = ArrayTools::flatten($mixed);
		}
		if ($mixed === null) {
			return '';
		}
		if (is_object($mixed)) {
			return method_exists($mixed, '__toString') ? strval($mixed) : self::flatten(get_object_vars($mixed));
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
	public static function replaceSubstrings(mixed $mixed, array $map): mixed
	{
		/* Used to flatten map the leaf, this is probably really slow so do it at the top */
		return self::_replaceSubstrings($mixed, ArrayTools::flatten($map));
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
	private static function _replaceSubstrings(mixed $mixed, array $map): mixed
	{
		if (Types::canIterate($mixed)) {
			foreach ($mixed as $k => $v) {
				$mixed[$k] = self::_replaceSubstrings($v, $map);
			}
			return $mixed;
		} elseif (is_string($mixed)) {
			return strtr($mixed, $map);
		} elseif (is_object($mixed)) {
			return method_exists($mixed, 'replaceSubstrings') ? $mixed->replaceSubstrings($map) : $mixed;
		} else {
			return $mixed;
		}
	}

	/**
	 * Return a sane type for a variable
	 *
	 * @param mixed $mixed
	 * @return string
	 */
	public static function type(mixed $mixed): string
	{
		return is_resource($mixed) ? get_resource_type($mixed) : (is_object($mixed) ? $mixed::class : gettype($mixed));
	}

	/**
	 * Convert our special weights into a number
	 *
	 * @param string|float|int $weight
	 * @return float
	 */
	private static function _weight(string|float|int $weight): float
	{
		static $weights = [
			self::INTERNAL_WEIGHT_FIRST => -1e300, self::WEIGHT_FIRST => -1e299, self::WEIGHT_LAST => 1e299,
			self::INTERNAL_WEIGHT_LAST => 1e300,
		];
		return floatval($weights[strval($weight)] ?? $weight);
	}

	/**
	 * Sort so that highest weights are at the top
	 *
	 * @param array $a
	 * @param array $b
	 * @return int
	 */
	public static function weightCompareReverse(array $a, array $b): int
	{
		return -self::weightCompare($a, $b);
	}

	/**
	 * Sort an array based on the weight array index
	 * Support special terms such as "first" and "last"
	 *
	 * use like:
	 *
	 * `usort` does not maintain index association:
	 *
	 * usort($this->links_sorted, Types::weightCompare(...));
	 *
	 * `uasort` DOES maintain index association:
	 *
	 * uasort($this->links_sorted, Types::weightCompare(...));
	 *
	 * @param array $left
	 * @param array $right
	 * @return int
	 * @see uasort
	 * @see usort
	 */
	public static function weightCompare(array $left, array $right): int
	{
		// Get weight left, convert to double
		$aw = array_key_exists('weight', $left) ? self::_weight($left['weight']) : 0;

		// Get weight right, convert to double
		$bw = array_key_exists('weight', $right) ? self::_weight($right['weight']) : 0;

		// a < b -> -1
		// a > b -> 1
		// a === b -> 0
		return $aw < $bw ? -1 : ($aw > $bw ? 1 : 0);
	}

	/**
	 *
	 */
	public const KEY_SEPARATOR = '::';

	/**
	 * Convert a global name to a standard internal format.
	 *
	 * @param string $key
	 * @return array
	 */
	public static function configurationKey(string $key): array
	{
		return explode(self::KEY_SEPARATOR, strtr(strtolower($key), [
			'__' => self::KEY_SEPARATOR, '.' => '_', '/' => '_', '-' => '_', ' ' => '_',
		]));
	}

	/**
	 * @param array $key
	 * @return string
	 */
	public static function keyToString(array $key): string
	{
		return implode(self::KEY_SEPARATOR, $key);
	}

	/**
	 * Convert a value automatically into a native PHP type
	 *
	 * @param mixed $value
	 * @param boolean $throw Throw an ParseException error when value is invalid JSON. Defaults to true.
	 * @return mixed
	 * @throws ParseException
	 * @see PHP::autoType()
	 */
	public static function autoType(mixed $value, bool $throw = true): mixed
	{
		if (is_object($value)) {
			return $value;
		}
		if (is_array($value)) {
			foreach ($value as $k => $v) {
				$value[$k] = self::autoType($v);
			}
			return $value;
		}
		// Convert numeric types first, then boolean
		$boolValue = Types::toBool($value, null);
		if (is_bool($boolValue)) {
			return $boolValue;
		}
		if (is_numeric($value)) {
			if (preg_match('/^\d+$/', "$value")) {
				return Types::toInteger($value);
			}
			return Types::toFloat($value);
		}
		if (!is_string($value)) {
			return $value;
		}
		if ($value === 'null') {
			return null;
		}
		if (StringTools::unquote($value, '{}[]\'\'""') !== $value) {
			try {
				return JSON::decode($value);
			} catch (ParseException $e) {
				if ($throw) {
					throw $e;
				}
				return $value;
			}
		}
		return $value;
	}
}
