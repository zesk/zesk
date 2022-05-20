<?php
declare(strict_types=1);

/**
 * Array tools
 *
 * @package zesk
 * @subpackage system
 * @author kent
 * @copyright Copyright &copy; 2017, Market Acumen, Inc.
 */

namespace zesk;

/**
 * Array tools for pretty much anything you can think of for arrays.
 *
 * @author kent
 */
class ArrayTools {
	/**
	 * Default whitespace trimming characters
	 *
	 * @var string
	 */
	public const TRIM_WHITESPACE = " \t\r\n\0\x0B";

	/**
	 * Capitalize all values in an array
	 *
	 * @param array $array
	 * @return array
	 */
	public static function capitalize(array $array): array {
		foreach ($array as $k => $v) {
			if (is_array($v)) {
				$array[$k] = self::capitalize($v);
			} elseif (is_string($v)) {
				$array[$k] = StringTools::capitalize($v);
			}
		}
		return $array;
	}

	/**
	 * Convert each value of an array to be a scalar value
	 *
	 * @param array $a
	 * @return array
	 * @see flatten
	 */
	public static function flatten(array $a): array {
		foreach ($a as $k => $v) {
			if (!is_scalar($v)) {
				$a[$k] = flatten($v);
			}
		}
		return $a;
	}

	/**
	 * Convert each value of an array to be a scalar value or array value (no objects)
	 *
	 * @param array $a
	 * @return array
	 * @see flatten
	 */
	public static function simplify(array $a): array {
		foreach ($a as $k => $v) {
			if (is_object($v)) {
				$a[$k] = method_exists($v, '__toString') ? strval($v) : self::simplify(get_object_vars($v));
			} elseif (is_array($v)) {
				$a[$k] = self::simplify($v);
			} else {
				$a[$k] = flatten($v);
			}
		}
		return $a;
	}

	/**
	 * Deletes array values which match value passed
	 *
	 * @param array $array_to_clean Array to clean
	 * @param mixed $values_to_remove Value or array of values to remove. Must match explicitly (===)
	 * @return array
	 */
	public static function clean(array $array_to_clean, mixed $values_to_remove = [null, '', false]): array {
		if (is_array($values_to_remove)) {
			foreach ($values_to_remove as $v) {
				$array_to_clean = self::clean($array_to_clean, $v);
			}
			return $array_to_clean;
		}
		foreach ($array_to_clean as $i => $v) {
			if ($v === $values_to_remove) {
				unset($array_to_clean[$i]);
			}
		}
		return $array_to_clean;
	}

	/**
	 * Calls PHP trim() on each element of an array.
	 *
	 * @param string $character_list List of characters to trim
	 * @param mixed $a
	 *            Array to trim
	 * @return array
	 * @see trim()
	 */
	public static function trim(array $a, string $character_list = self::TRIM_WHITESPACE): array {
		foreach ($a as $i => $v) {
			$a[$i] = is_array($v) ? self::trim($v, $character_list) : (is_string($v) ? trim($v, $character_list) : '');
		}
		return $a;
	}

	/**
	 * Removes items from the beginning or end of the list which are empty.
	 *
	 * @param array $a
	 * @return array
	 */
	public static function list_trim(array $a): array {
		return self::list_trim_tail(self::list_trim_head($a));
	}

	/**
	 * Removes items from the beginning of the list which are empty.
	 *
	 * @param array $a
	 * @return array
	 */
	public static function list_trim_head(array $a): array {
		while (count($a) > 0) {
			$item = first($a);
			if (trim($item) === '') {
				array_shift($a);
			} else {
				break;
			}
		}
		return $a;
	}

	/**
	 * Removes items from the end of the list which are empty.
	 *
	 * @param array $a
	 * @return array
	 */
	public static function list_trim_tail(array $a): array {
		while (count($a) > 0) {
			$item = last($a);
			if (trim($item) === '') {
				array_pop($a);
			} else {
				break;
			}
		}
		return $a;
	}

	/**
	 * Trim array values, then remove ones which match the empty string.
	 *
	 * @param array $arr
	 * @param string $character_list List of characters to trim
	 * @param string $value
	 *            Value which is removed
	 * @return array
	 */
	public static function trim_clean(array $arr, string $character_list = self::TRIM_WHITESPACE, array $values = [
		null,
		'',
		false,
	]): array {
		return self::clean(self::trim($arr, $character_list), $values);
	}

	/**
	 * Calls PHP rtrim() on each element of an array.
	 * If $a is not an array, returns rtrim($a)
	 *
	 * @param mixed $array_to_trim
	 *            Array to trim
	 * @param string $character_list String containing characters to remove
	 * @return array|string
	 * @see trim()
	 */
	public static function rtrim(array $array_to_trim, string $character_list = self::TRIM_WHITESPACE): array {
		foreach ($array_to_trim as $i => $v) {
			$array_to_trim[$i] = is_string($v) ? rtrim($v, $character_list) : (is_array($v) ? self::rtrim($v, $character_list) : $v);
		}
		return $array_to_trim;
	}

	/**
	 * Calls PHP ltrim() on each element of an array.
	 * If $a is not an array, returns ltrim($a)
	 *
	 * @param string $character_list String containing characters to remove
	 * @param mixed $a
	 *            Array to trim
	 * @return array|string
	 * @see trim()
	 */
	public static function ltrim(array $array_to_trim, string $character_list = self::TRIM_WHITESPACE): array {
		foreach ($array_to_trim as $i => $v) {
			$array_to_trim[$i] = is_string($v) ? ltrim($v, $character_list) : (is_array($v) ? self::ltrim($v, $character_list) : $v);
		}
		return $array_to_trim;
	}

	/**
	 * Calls PHP ltrim() on each key of an array.
	 * If $a is not an array, returns ltrim($a)
	 *
	 * @param string $character_list String containing characters to remove
	 * @param mixed $array_keys_to_trim
	 *            Array to rtrim
	 * @return array|string
	 * @see ltrim()
	 */
	public static function kltrim(array $array_keys_to_trim, string $character_list = self::TRIM_WHITESPACE): array {
		$b = [];
		foreach ($array_keys_to_trim as $i => $v) {
			$b[ltrim($i, $character_list)] = $v;
		}
		return $b;
	}

	/**
	 * Calls PHP trim() on each key of an array.
	 * If $a is not an array, returns trim($a)
	 *
	 * @param string $character_list String containing characters to remove
	 * @param array|string $array_keys_to_trim
	 *            Array to ktrim
	 * @return array|string
	 * @see ltrim()
	 */
	public static function ktrim(array $array_keys_to_trim, string $character_list = self::TRIM_WHITESPACE): array {
		$b = [];
		foreach ($array_keys_to_trim as $i => $v) {
			$b[trim($i, $character_list)] = $v;
		}
		return $b;
	}

	/**
	 * Prefix each value in an array with a string
	 *
	 * @param array $array_values_to_prefix
	 * @param string $str
	 * @return array
	 */
	public static function prefix(array $array_values_to_prefix, string $str): array {
		foreach ($array_values_to_prefix as $k => $v) {
			$array_values_to_prefix[$k] = $str . $v;
		}
		return $array_values_to_prefix;
	}

	/**
	 * Suffix each value in an array with a string
	 *
	 * @param array $arr
	 * @param string $str
	 * @return array
	 */
	public static function suffix(array $arr, string $str): array {
		foreach ($arr as $k => $v) {
			$arr[$k] = $v . $str;
		}
		return $arr;
	}

	/**
	 * Wrap each value in an array with a string (prefix and suffix)
	 *
	 * @param array $arr
	 * @param string $prefix
	 * @param string $suffix
	 * @return array
	 */
	public static function wrap(array $arr, string $prefix = '', string $suffix = ''): array {
		foreach ($arr as $k => $v) {
			$arr[$k] = $prefix . $v . $suffix;
		}
		return $arr;
	}

	/**
	 * Prefix each key in an array with a string
	 *
	 * @param array $arr
	 * @param string $str
	 * @return array
	 */
	public static function kprefix(array $arr, string $str): array {
		$result = [];
		foreach ($arr as $k => $v) {
			$result[$str . $k] = $v;
		}
		return $result;
	}

	/**
	 * Suffix each key in an array with a string
	 *
	 * @param array $arr
	 * @param string $str
	 * @return array
	 */
	public static function ksuffix(array $arr, string $str): array {
		$result = [];
		foreach ($arr as $k => $v) {
			$result[$k . $str] = $v;
		}
		return $result;
	}

	/**
	 * Wrap each key in an array with a string (prefix and suffix)
	 *
	 * @param array $arr
	 * @param string $prefix
	 * @param string $suffix
	 * @return array
	 */
	public static function kwrap(array $arr, string $prefix = '', string $suffix = ''): array {
		$result = [];
		foreach ($arr as $k => $v) {
			$result[$prefix . $k . $suffix] = $v;
		}
		return $result;
	}

	/**
	 * Remove a string prefix from each value in an array.
	 * Works recursivly on arrays in arrays.
	 *
	 * @param array $arr
	 * @param string $str
	 * @param boolean $remove
	 *            Remove unmatched entries
	 * @return array
	 */
	public static function unprefix(array $arr, string $str, bool $remove = false): array {
		$n = strlen($str);
		foreach ($arr as $k => $v) {
			if (is_string($v)) {
				if (begins($v, $str)) {
					$arr[$k] = substr($v, $n);
				} elseif ($remove) {
					unset($arr[$k]);
				}
			} elseif (is_array($v)) {
				$arr[$k] = self::unprefix($v, $str, $remove);
			}
		}
		return $arr;
	}

	/**
	 * Remove a string suffix from each value in an array.
	 * Works recursively on arrays in arrays.
	 *
	 * @param array $arr
	 * @param string $str
	 * @param boolean $remove
	 *            Remove unmatched entries
	 * @return array
	 */
	public static function unsuffix(array $arr, string $str, bool $remove = false): array {
		$n = strlen($str);
		foreach ($arr as $k => $v) {
			if (is_string($v)) {
				if (ends($v, $str)) {
					$arr[$k] = substr($v, 0, -$n);
				} elseif ($remove) {
					unset($arr[$k]);
				}
			} elseif (is_array($v)) {
				$arr[$k] = self::unsuffix($v, $str, $remove);
			}
		}
		return $arr;
	}

	/**
	 * Remove a string prefix and suffix from each value in an array.
	 * Works recursively on arrays in arrays.
	 *
	 * @param array $arr
	 * @param string $prefix
	 * @param string $suffix
	 * @param boolean $remove
	 *            Remove unmatched entries
	 * @return array
	 */
	public static function unwrap(array $arr, string $prefix, string $suffix, bool $remove = false): array {
		$n_prefix = strlen($prefix);
		$n_suffix = strlen($suffix);
		foreach ($arr as $k => $v) {
			if (is_string($v)) {
				if (begins($v, $prefix) && ends($v, $suffix)) {
					$arr[$k] = substr($v, $n_prefix, -$n_suffix);
				} elseif ($remove) {
					unset($arr[$k]);
				}
			} elseif (is_array($v)) {
				$arr[$k] = ArrayTools::$n_suffix($v, $prefix, $suffix, $remove);
			}
		}
		return $arr;
	}

	/**
	 * Remove a string prefix from each key in an array
	 *
	 * @param array $arr
	 * @param string $str
	 * @param boolean $remove
	 *            Remove unmatched entries
	 * @return array
	 */
	public static function kunprefix(array $arr, string $str, bool $remove = false): array {
		$n = strlen($str);
		$result = [];
		foreach ($arr as $k => $v) {
			if (substr($k, 0, $n) === $str) {
				$result[substr($k, $n)] = $v;
			} elseif (!$remove) {
				$result[$k] = $v;
			}
		}
		return $result;
	}

	/**
	 * Remove a string suffix from each key in an array
	 *
	 * @param array $arr
	 * @param string $str
	 * @param boolean $remove
	 *            Remove unmatched entries
	 * @return array
	 */
	public static function kunsuffix(array $arr, string $str, bool $remove = false): array {
		$n = strlen($str);
		$result = [];
		foreach ($arr as $k => $v) {
			if (substr($k, -$n) === $str) {
				$result[substr($k, 0, -$n)] = $v;
			} elseif (!$remove) {
				$result[$k] = $v;
			}
		}
		return $result;
	}

	/**
	 * Remove keys from an array
	 *
	 * @param array $arr
	 * @param mixed $keys
	 *            Array or list of keys to remove
	 * @return array
	 */
	public static function remove(array $arr, array $keys): array {
		foreach ($keys as $k) {
			unset($arr[$k]);
		}
		return $arr;
	}

	/**
	 * Remove values from an array
	 *
	 * @param array $arr
	 * @param array $values List of values to remove
	 * @return array
	 */
	public static function remove_values(array $arr, array $values): array {
		$keys = array_flip($values);
		foreach ($arr as $k => $v) {
			if (array_key_exists($v, $keys)) {
				unset($arr[$k]);
			}
		}
		return $arr;
	}

	/**
	 * Merges one or more arrays recursively and preserves all keys.
	 * Note that this does not work the same the PHP function array_merge_recursive()!
	 *
	 * @param
	 *            array initial array
	 * @param
	 *            array array to merge
	 * @param
	 *            array ...
	 * @return array
	 * @todo Is this used
	 */
	public static function merge(): array {
		$result = [];
		for ($i = 0, $total = func_num_args(); $i < $total; $i++) {
			foreach (func_get_arg($i) as $key => $val) {
				if (isset($result[$key])) {
					if (is_array($val)) {
						// Arrays are merged recursively
						$result[$key] = self::merge($result[$key], $val);
					} elseif (is_int($key)) {
						// Indexed arrays are appended
						array_push($result, $val);
					} else {
						// Associative arrays are replaced
						$result[$key] = $val;
					}
				} else {
					// New values are added
					$result[$key] = $val;
				}
			}
		}
		return $result;
	}

	/**
	 * Partner of path - sets an array path to a specific value
	 *
	 * @param array $array
	 * @param string $path
	 *            A path into the array separated by $separator (e.g. "document.title")
	 * @param mixed $value
	 *            Value to set the path in the trr
	 * @param string $separator
	 *            Character used to separate levels in the array
	 * @return array
	 */
	public static function path_set(array &$array, $path, $value, $separator = '.'): array {
		return apath_set($array, $path, $value, $separator);
	}

	/**
	 * Gets a value from an array using a delimited separated path.
	 * // Get the value of $array['foo']['bar']
	 * $value = apath($array, 'foo.bar');
	 *
	 * @param array $array to search
	 * @param string $path key path, dot separated
	 * @param mixed $default default value if the path is not set
	 * @param string $separator string separator for string paths
	 * @return mixed
	 * @see ArrayTools::path_set
	 * @deprecated Use apath
	 */
	public static function path(array $array, $path, $default = null, $separator = '.') {
		return apath($array, $path, $default, $separator);
	}

	/**
	 * Take a list of arrays and create a new array using values found in it.
	 *
	 * @param array $arrays
	 * @param string|null $key_key
	 * @param string|null $value_key
	 * @param mixed $default_value
	 * @return array
	 */
	public static function extract(array $arrays, $key_key = null, $value_key = null, $default_value = null) {
		$result = [];
		if ($key_key === null) {
			foreach ($arrays as $key => $array) {
				$result[$key] = $array[$value_key] ?? $default_value;
			}
		} else {
			foreach ($arrays as $array) {
				$value = $array[$value_key] ?? $default_value;
				if (isset($array[$key_key])) {
					$result[$array[$key_key]] = $value;
				} else {
					$result[] = $value;
				}
			}
		}
		return $result;
	}

	/**
	 * Take a list of arrays and create a new array using values found in it.
	 *
	 * @param array $arrays
	 * @param string|null $key_key
	 * @param string|null $value_key
	 * @param mixed $default_value
	 * @return array
	 * @see ArrayTools::extract
	 * @deprecated 2019-07
	 */
	public static function key_value(array $arrays, $key_key = null, $value_key = null, $default_value = null) {
		return self::extract($arrays, $key_key, $value_key, $default_value);
	}

	/**
	 * Convert array("col1","col2","col3"), array("zero", 1, false)) to
	 * array("col1" => "zero", "col2" => 1, "col3" => false);
	 * Useful for parsing delimited text files or CSV files.
	 * To skip values, specify null as the key
	 *
	 * @param array $keys
	 * @param array $values
	 * @return array
	 */
	public static function rekey(array $keys, array $values) {
		$result = [];
		foreach ($keys as $i => $key) {
			if ($key === null) {
				continue;
			}
			if (!array_key_exists($i, $values)) {
				continue;
			}
			$result[$key] = $values[$i];
		}
		return $result;
	}

	/**
	 * Convert key names in this array to new key names
	 *
	 * @param array $array
	 *            Array to convert
	 * @param array $key_map
	 *            Array of old_key => new_key to convert
	 * @return array The converted array
	 */
	public static function map_keys(array $array, array $key_map) {
		if (count($key_map) === 0) {
			return $array;
		}
		$new_array = $skip = [];
		foreach ($array as $k => $v) {
			$new_key = $key_map[$k] ?? $k;
			if ($new_key !== $k) {
				$skip[$new_key] = true;
			} elseif (array_key_exists($k, $skip)) {
				continue;
			}
			$new_array[$new_key] = $v;
		}
		return $new_array;
	}

	/**
	 * Convert values in this array to new values
	 *
	 * @param array $array Array to convert
	 * @param array $value_map Array of old_value => new_value to convert
	 * @return array The converted array
	 */
	public static function map_values(array $array, array $value_map) {
		if (count($value_map) === 0) {
			return $array;
		}
		foreach ($array as $k => $v) {
			if ((is_string($v) || is_numeric($v)) && array_key_exists($v, $value_map)) {
				$array[$k] = $value_map[$v];
			}
		}
		return $array;
	}

	/**
	 * Kind of like UNIX "awk '{ print $index }'"
	 * Null for index means return the whole list as an array
	 *
	 * @param array $array
	 * @param integer $index
	 * @param string $delim
	 *            List of delimiter characters
	 * @param integer $max_fields
	 * @return array
	 */
	public static function field(array $array, int $index = null, string $delim = " \t", int $max_fields = null): array {
		foreach ($array as $k => $v) {
			if (is_string($v)) {
				$array[$k] = StringTools::field($v, $index, $delim, $max_fields);
			} elseif (is_array($v)) {
				$array[$k] = self::field($v, $index, $delim, $max_fields);
			}
		}
		return $array;
	}

	/**
	 * Convert a list of strings to a set of key pairs by dividing them along a delimiter
	 *
	 * @inline_test ArrayTools::kpair(["dog bark", "cat meow", "child coo"]) === ["dog" => "bark", "cat" => "meow", "child" => "coo"];
	 *
	 * @param array $array
	 *            Array of strings
	 * @param string $delim
	 *            Delimiter to split on
	 * @return array
	 */
	public static function kpair(array $array, $delim = ' ') {
		$result = [];
		foreach ($array as $k => $item) {
			[$key, $value] = pair($item, $delim, $k, $item);
			$result[$key] = $value;
		}
		return $result;
	}

	/**
	 * Returns whether an array is an associative array (true) or a simple list (false).
	 *
	 * @param array $array
	 * @return boolean
	 */
	public static function is_assoc(array $array) {
		$i = 0;
		foreach (array_keys($array) as $k) {
			if ($k !== $i) {
				return true;
			}
			$i++;
		}
		return false;
	}

	/**
	 * Returns whether an array is an associative array (false) or a simple list (true).
	 *
	 * @param mixed $mixed
	 * @return boolean
	 */
	public static function is_list($mixed) {
		if (!is_array($mixed)) {
			return false;
		}
		return !self::is_assoc($mixed);
	}

	/**
	 * Return true if an array has all keys specified
	 *
	 * @param array $array
	 * @param mixed $keys
	 *            A key, or an array of keys to check for. If a string, converted to a list via
	 *            to_list
	 * @return boolean
	 * @see to_list
	 */
	public static function has(array $array, $keys) {
		$keys = to_list($keys);
		foreach ($keys as $key) {
			if (!array_key_exists($key, $array)) {
				return false;
			}
		}
		return true;
	}

	/**
	 * Return true if an array has all values specified
	 *
	 * @param array $array
	 * @param string|array $values
	 *            A key, or an array of keys to check for. If a string, converted to a list via
	 *            to_list
	 * @return boolean
	 * @see to_list
	 */
	public static function has_value(array $array, $values) {
		$values = to_list($values);
		foreach ($values as $value) {
			if (!in_array($value, $array)) {
				return false;
			}
		}
		return true;
	}

	/**
	 * Return true if an array has any keys specified
	 *
	 * @param array $array
	 * @param mixed $keys
	 *            A key, or an array of keys to check for. If a string, converted to a list via
	 *            to_list
	 * @return boolean
	 * @see to_list
	 */
	public static function has_any(array $array, $keys) {
		$keys = to_list($keys);
		foreach ($keys as $key) {
			if (array_key_exists($key, $array)) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Return true if an array has any keys specified
	 *
	 * @param array $array
	 * @param array|string $values
	 *            A value, or an array of values to check for. If a string, converted to a list via
	 *            to_list
	 * @return boolean
	 * @see to_list
	 */
	public static function has_any_value(array $array, $values) {
		$values = to_list($values);
		foreach ($values as $value) {
			if (in_array($value, $array)) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Finds the minimum numerical value in an array.
	 * Note that no type conversion happens; if you want an integer, then call intval on the result
	 * after checking for NULL
	 *
	 * @param array $a
	 *            Array to search
	 * @param mixed $default
	 *            Value to return of no minimum value found in array
	 * @return mixed Minimum numerical value found in array, or $default if none found.
	 */
	public static function min($a, $default = null) {
		$min = null;
		foreach ($a as $i) {
			if (!is_numeric($i)) {
				continue;
			} elseif ($min === null) {
				$min = $i;
			} elseif ($i < $min) {
				$min = $i;
			}
		}
		return ($min === null) ? $default : $min;
	}

	/**
	 * Finds the maximum numerical value in an array.
	 * Note that no type conversion happens; if you want an integer, then call intval on the result
	 * after checking for NULL
	 *
	 * @param array $a
	 *            Array to search
	 * @param mixed $default
	 *            Value to return of no maximum value found in array
	 * @return mixed Maximum numerical value found in array, or $default if none found.
	 */
	public static function max($a, $default = null) {
		$max = null;
		foreach ($a as $i) {
			if (!is_numeric($i)) {
				continue;
			} elseif ($max === null) {
				$max = $i;
			} elseif ($i > $max) {
				$max = $i;
			}
		}
		return ($max === null) ? $default : $max;
	}

	/**
	 * Like array_change_key_case, but for values.
	 * Only converts string values, numbers, booleans and objects are left alone.
	 *
	 * @param array $arr
	 * @return array with values lowercase
	 */
	public static function change_value_case(array $arr) {
		$r = [];
		foreach ($arr as $k => $v) {
			if (is_string($v)) {
				$r[$k] = strtolower($v);
			} else {
				$r[$k] = $v;
			}
		}
		return $r;
	}

	/**
	 * Include only certain entries in an array
	 *
	 * @param array $arr
	 *            The array to filter
	 * @param array|string $keys
	 *            A list of keys to include either as a string "k1;k2;k3", or array('k1','k2','k3'),
	 *            or a map
	 *            array('k1' => 'newkk1','k2' => 'newk2') to convert extracted keys
	 * @return array The array with only the keys passed in which already exist in the array
	 */
	public static function filter(array $arr, $keys) {
		if (is_string($keys)) {
			$keys = explode(';', $keys);
		}
		$r = [];
		foreach ($keys as $src_key => $dest_key) {
			if (is_numeric($src_key)) {
				$src_key = $dest_key;
			}
			if (array_key_exists($src_key, $arr)) {
				$r[$dest_key] = $arr[$src_key];
			}
		}
		return $r;
	}

	/**
	 * Extract certain key values which have a certain prefix from one array into a new array
	 *
	 * @param array $a
	 *            An array to retrieve key values from
	 * @param mixed $prefixes
	 *            A ;-separated list, or an array of keys of strings which contain prefixes which
	 *            should match
	 * @param boolean $lower Case-insensitive search when true
	 * @return array
	 */
	public static function filter_prefix($a, $prefixes, $lower = false) {
		if (is_string($prefixes)) {
			$prefixes = explode(';', $prefixes);
		}
		$r = [];
		foreach ($a as $k => $v) {
			if (StringTools::begins($k, $prefixes, $lower)) {
				$r[$k] = $v;
			}
		}
		return $r;
	}

	/**
	 * Includes or excludes array values from an array.
	 *
	 * @param array $a
	 *            A reference to an array to filter
	 * @param array $include
	 *            A list of array values to explicitly include, or false to include all items
	 * @param array $exclude
	 *            A list of array values to explicitly exclude, or false to exclude no items
	 * @param bool $lower
	 *            Whether the array values are case-sensitive or not. 2015-06-23 Lower is by
	 *            default false (no changes to input).
	 * @return array Filtered by include/exclude criteria
	 */
	public static function include_exclude(array $a, $include = null, $exclude = null, $lower = false) {
		if (!is_array($a)) {
			return $a;
		}
		$newa = $lower ? self::change_value_case($a) : $a;
		if (!is_bool($include) && !empty($include)) {
			$include = to_list($include);
			if (count($include) > 0) {
				if ($lower) {
					$include = array_keys(array_change_key_case(array_flip($include)));
				}
				$newa = array_intersect($newa, $include);
			}
		}
		if (!is_bool($exclude) && !empty($exclude)) {
			$exclude = to_list($exclude, []);
			$exclude = array_flip($exclude);
			if ($lower) {
				$exclude = array_change_key_case($exclude);
			}
			foreach ($a as $i => $action) {
				if (!is_scalar($action)) {
					error_log(__METHOD__ . ' ' . type($action) . ' ' . _backtrace(), E_USER_ERROR);
				}
				$action = strval($action);
				if (array_key_exists($action, $exclude)) {
					unset($newa[$i]);
				}
			}
		}
		return $newa;
	}

	/**
	 * Includes or excludes array keys from an array.
	 *
	 * @param array $array_to_filter
	 *            A reference to an array to filter
	 * @param array|string $include
	 *            A list of array keys to explicitly include, or null to include all items
	 * @param array|string $exclude
	 *            A list of array keys to explicitly exclude, or empty to exclude no items
	 * @param bool $lower
	 *            Whether the array keys are case-sensitive or not
	 * @return array The filtered array
	 */
	public static function kfilter(array $array_to_filter, array $include = null, array $exclude = [], bool $lower = false): array {
		$ak = array_keys($array_to_filter);
		$exclude = array_flip($exclude);
		if ($lower) {
			$exclude = array_change_key_case($exclude);
		}
		if (is_array($include)) {
			$include = array_flip($include);
			if ($lower) {
				$include = array_change_key_case($include);
			}
			foreach ($ak as $k) {
				$lk = $lower ? strtolower($k) : $k;
				if (!isset($include[$lk]) || isset($exclude[$lk])) {
					unset($array_to_filter[$lk]);
				}
			}
		} else {
			foreach ($ak as $k) {
				$lk = $lower ? strtolower($k) : $k;
				if (isset($exclude[$lk])) {
					unset($array_to_filter[$k]);
				}
			}
		}
		return $array_to_filter;
	}

	/**
	 * Modifies an array value in the following manner, when adding a key multiple times:
	 * <ol>
	 * <li>Adds the item to the array.</li>
	 * <li>Converts the array value to an array, and appends the new item.</li>
	 * <li>Appends the new item to the array key.</li>
	 * </ol>
	 * This facilitates managing arrays which may contain a single value, or arrays of values.
	 * e.g.
	 * <pre>
	 * $foo = array();
	 * self::append($foo, "a", 1);
	 * self::append($foo, "b", 2");
	 * self::append($foo, "c", 3");
	 * self::append($foo, "b", 4");
	 * self::append($foo, "c", 5");
	 * self::append($foo, "c", 6");
	 * builds:
	 * $foo = array(
	 * "a" => 1,
	 * "b" => array(2, 4),
	 * "c" => array(3, 5, 6),
	 * );
	 * </pre>
	 * Useful for cleaning up code which handles this type of structure.
	 *
	 * @param array $arr
	 *            A reference to an array to append
	 * @param string $k
	 *            A key value for the array
	 * @param mixed $v
	 *            The value to store in the array
	 * @return void
	 * @see ArrayTools::prepend
	 */
	public static function append(&$arr, $k, $v = null): void {
		if (is_array($k)) {
			foreach ($k as $k0 => $v) {
				ArrayTools::append($arr, $k0, $v);
			}
		} else {
			if (!isset($arr[$k])) {
				$arr[$k] = $v;
			} elseif (is_array($arr[$k])) {
				$arr[$k][] = $v;
			} else {
				$arr[$k] = [
					$arr[$k],
					$v,
				];
			}
		}
	}

	/**
	 * Modifies an array value in the following manner, when adding a key multiple times:
	 * <ol>
	 * <li>Prepends the item to the array.</li>
	 * <li>Converts the array value to an array, and prepends the new item.</li>
	 * <li>Prepends the new item to the array key.</li>
	 * </ol>
	 * This facilitates managing arrays which may contain a single value, or arrays of values.
	 * e.g.
	 * <pre>
	 * $foo = array();
	 * self::append($foo, "a", 1);
	 * self::append($foo, "b", 2");
	 * self::append($foo, "c", 3");
	 * self::append($foo, "b", 4");
	 * self::append($foo, "c", 5");
	 * self::append($foo, "c", 6");
	 * builds:
	 * $foo = array(
	 * "a" => 1,
	 * "b" => array(4, 2),
	 * "c" => array(6, 5, 3),
	 * );
	 * </pre>
	 * Useful for cleaning up code which handles this type of structure.
	 *
	 * @param array $arr
	 *            A reference to an array to append
	 * @param string $k
	 *            A key value for the array
	 * @param mixed $v
	 *            The value to store in the array
	 * @return void
	 * @see ArrayTools::append
	 */
	public static function prepend(&$arr, $k, $v = null): void {
		if (is_array($k)) {
			foreach ($k as $k0 => $v) {
				ArrayTools::append($arr, $k0, $v);
			}
		} else {
			if (!isset($arr[$k])) {
				$arr[$k] = $v;
			} elseif (is_array($arr[$k])) {
				array_unshift($arr[$k], $v);
			} else {
				$arr[$k] = [
					$v,
					$arr[$k],
				];
			}
		}
	}

	/**
	 * Given an array with just values, converts it to an associative array with the keys and the
	 * values identical (or
	 * lower-case keys and upper-case values)
	 * Useful for form generation with select/options or checkboxes
	 * e.g.
	 * dump(self::flip_copy(array("Red", "Blue", "Orange", "Frog")));
	 * is:
	 * <pre>Array
	 * (
	 * [red] => Red
	 * [blue] => Blue
	 * [orange] => Orange
	 * [frog] => Frog
	 * )</pre>
	 *
	 * @param array $x
	 *            Array to modify
	 * @param boolean $lower
	 *            Convert the keys to lowercase. Defaults to "true"
	 * @return array An array where the keys and values identical (or lowercase equivalent)
	 */
	public static function flip_copy($x, $lower = true) {
		$result = [];
		if ($lower) {
			foreach ($x as $k) {
				$result[strtolower($k)] = $k;
			}
		} else {
			foreach ($x as $k) {
				$result[$k] = $k;
			}
		}
		return $result;
	}

	/**
	 * Flips array but when identical keys exist, keeps all duplicate values, so:
	 *
	 * <code>
	 * ArrayTools::flip_multiple(array(
	 * 'a' => '1',
	 * 'b' => '2',
	 * 'c' => '1',
	 * 'd' => '3',
	 * 'e' => '1'
	 * )) === array('1' => array('a','c','e'), '2' => array('b'), '3' => array('d'));
	 * </code>
	 *
	 * @param array $arr
	 * @return array
	 */
	public static function flip_multiple(array $arr) {
		$result = [];
		foreach ($arr as $k => $v) {
			if (array_key_exists($v, $result)) {
				$result[$v][] = $k;
			} else {
				$result[$v] = [
					$k,
				];
			}
		}
		return $result;
	}

	/**
	 * Given an array with just values, converts it to an associative array using values as keys,
	 * and
	 * assigns a single value.
	 *
	 * e.g.
	 * dump(self::flip_copy(array("Red", "Blue", "Orange", "Frog"), true));
	 * is:
	 * <pre>Array
	 * (
	 * [Red] => true
	 * [Blue] => true
	 * [Orange] => true
	 * [Frog] => true
	 * )</pre>
	 *
	 * @param array $x
	 *            Array to modify
	 * @param mixed $value
	 * @return array
	 */
	public static function flip_assign($x, $value = null) {
		$result = [];
		foreach ($x as $k) {
			$result[$k] = $value;
		}
		return $result;
	}

	/**
	 * Find a series of keys in an array and return the first found key.
	 * If the found value is empty, return the default value.
	 *
	 * @param array $source
	 *            Array to search
	 * @param array $source_keys
	 *            List of keys to find in source
	 * @param mixed $default
	 *            default value to return if found key is empty
	 * @return mixed Key found, default, or false if none found
	 */
	public static function kfind(array $source, array $source_keys, $default = false) {
		foreach ($source_keys as $k) {
			if (isset($source[$k])) {
				if (empty($source[$k])) {
					return $default;
				}
				return $source[$k];
			}
		}
		return false;
	}

	/**
	 * inserts values from $arr2 after (or before) $key in $arr1
	 * if $key is not found, values from $arr2 are appended to the end of $arr1
	 * This function uses array_merge() so be aware that values from conflicting keys
	 * will overwrite each other
	 *
	 * @param array $arr1 array to insert into
	 * @param string $key key of $arr1 to insert after (or before)
	 * @param array $arr2 array whose values should be inserted
	 * @param boolean $before insert before the given key. defaults to inserting after
	 * @return array merged
	 */
	public static function insert($arr1, $key, $arr2, $before = false) {
		$result = [];
		$key = strval($key);
		if (!array_key_exists($key, $arr1)) {
			return $before ? array_merge($arr2, $arr1) : array_merge($arr1, $arr2);
		}
		foreach ($arr1 as $k => $v) {
			if (strval($k) === $key) {
				if ($before) {
					$result = array_merge($result, $arr2);
					$result[$k] = $v;
				} else {
					$result[$k] = $v;
					$result = array_merge($result, $arr2);
				}
			} else {
				$result[$k] = $v;
			}
		}
		return $result;
	}

	/**
	 * Tests for a key in an array, if not available, sets to 1, otherwise increments
	 *
	 * @param array $array An array to create a counter in
	 * @param string $k Key to increment
	 * @param integer|float $amount Amount to increment
	 * @return integer
	 */
	public static function increment(&$array, $k, $amount = 1) {
		if (array_key_exists($k, $array)) {
			return $array[$k] += $amount;
		}
		return $array[$k] = $amount;
	}

	/**
	 * Given a string and an array of strings, find if string $needles exists in $haystack.
	 * <em>Performs a
	 * case-insensitive search.</em>
	 *
	 * @param string $haystack
	 *            String to search
	 * @param array $needles
	 *            Array of strings to find in $haystack
	 * @return mixed Integer or string key of found needle in $needles
	 * @see stristr()
	 */
	public static function stristr($haystack, array $needles) {
		foreach ($needles as $k => $needle) {
			if (stristr($haystack, $needle) !== false) {
				return $k;
			}
		}
		return false;
	}

	/**
	 * Given a string and an array of strings, find if string $haystack exists in $needles.
	 * <em>Case sensitive.</em>
	 *
	 * @param string $haystack
	 * @param array $needles
	 * @return mixed Integer or string key of found needle in $needles
	 * @see strstr()
	 */
	public static function strstr($haystack, array $needles) {
		foreach ($needles as $k => $needle) {
			if (strstr($haystack, $needle) !== false) {
				return $k;
			}
		}
		return false;
	}

	/**
	 * Given arrays and string inputs, find if any needles appear in haystack.
	 * <em>Case sensitive.</em>
	 *
	 * @param mixed $haystack
	 * @param mixed $needles
	 * @return boolean
	 * @see strstr()
	 */
	public static function find(mixed $haystack, mixed $needles): bool {
		if (is_array($haystack)) {
			foreach ($haystack as $h) {
				if (self::find($h, $needles)) {
					return true;
				}
			}
			return false;
		}
		if (is_array($needles)) {
			foreach ($needles as $needle) {
				if (self::find($haystack, $needle)) {
					return true;
				}
			}
			return false;
		}
		if (!is_scalar($haystack) || !is_scalar($needles)) {
			return false;
		}
		return str_contains(strval($haystack), strval($needles));
	}

	/**
	 * Given arrays and string inputs, find if any needles appear in haystack.
	 * <em>Case insensitive.</em>
	 *
	 * @param mixed $haystack
	 * @param mixed $needles
	 * @return boolean
	 * @see strstr()
	 */
	public static function ifind($haystack, $needles) {
		if (is_array($haystack)) {
			foreach ($haystack as $h) {
				if (self::ifind($h, $needles)) {
					return true;
				}
			}
			return false;
		}
		if (is_array($needles)) {
			foreach ($needles as $needle) {
				if (self::ifind($haystack, $needle)) {
					return true;
				}
			}
			return false;
		}
		return (stripos($haystack, $needles) !== false);
	}

	/**
	 * Given arrays and string inputs, find if any needles appear in haystack.
	 * <em>Case sensitive.</em>
	 *
	 * @param mixed $haystack
	 * @param mixed $needles
	 * @return array Array of values which match
	 * @see strstr()
	 */
	public static function match(array $haystack, $needles) {
		$result = [];
		if (is_array($needles)) {
			foreach ($needles as $needle) {
				$result = array_merge($result, self::match($haystack, $needle));
			}
			return $result;
		}
		foreach ($haystack as $h => $v) {
			if (str_contains($v, $needles)) {
				$result[$h] = $v;
			}
		}
		return $result;
	}

	/**
	 * Given arrays and string inputs, find if any needles appear in haystack.
	 * <em>Case insensitive.</em>
	 *
	 * @param mixed $haystack
	 * @param mixed $needles
	 * @return array Array of values which match
	 * @see strstr()
	 */
	public static function imatch(array $haystack, $needles) {
		$result = [];
		if (is_array($needles)) {
			foreach ($needles as $needle) {
				$result = array_merge($result, self::match($haystack, $needle));
			}
			return $result;
		}
		foreach ($haystack as $h => $v) {
			if (stripos($v, $needles) !== false) {
				$result[$h] = $v;
			}
		}
		return $result;
	}

	/**
	 * Run preg_quote on an array of values
	 *
	 * @param mixed $string
	 *            Array or string to preg_quote
	 * @param string $delimiter
	 *            Delimiter passed to preg_quote
	 * @return mixed
	 */
	public static function preg_quote($string, $delimiter = null) {
		if (is_string($string)) {
			return preg_quote($string, $delimiter);
		} elseif (is_array($string)) {
			$result = [];
			foreach ($string as $k => $str) {
				$result[$k] = self::preg_quote($str, $delimiter);
			}
			return $result;
		} else {
			return self::preg_quote(strval($string), $delimiter);
		}
	}

	/**
	 * Convert an AxB array into a BxA array
	 *
	 * @param array $arr
	 * @return array
	 */
	public static function transpose(array $arr) {
		$new = [];
		foreach ($arr as $y => $row) {
			if (is_array($row)) {
				foreach ($row as $x => $data) {
					$new[$x][$y] = $data;
				}
			} else {
				$new[$y] = $row;
			}
		}
		return $new;
	}

	/**
	 * Return the topmost element
	 *
	 * @param array $array
	 * @param mixed $default
	 * @return mixed
	 */
	public static function top(array $array, $default = null) {
		if (count($array) === 0) {
			return $default;
		}
		return $array[count($array) - 1];
	}

	/**
	 * Collapse a keyed array containing arrays
	 * e.g.
	 * $array =
	 * array(
	 * array(
	 * 'min' => 1,
	 * 'max' => 2,
	 * ),
	 * array(
	 * 'min' => 4,
	 * 'max' => 5,
	 * )
	 * );
	 * ArrayTools::collapse($array, 'min') === array(1,4)
	 * ArrayTools::collapse($array, 'max') === array(2,5)
	 * ArrayTools::collapse($array, 'dude') === array(null,null)
	 * Not sure if this is the best name for this.
	 *
	 * @param array $array
	 * @param string $key
	 *            Key to pull from the values of the array
	 * @param mixed $default
	 * @return array
	 */
	public static function collapse(array $array, $key, $default = null) {
		foreach ($array as $k => $subarray) {
			$array[$k] = $subarray[$key] ?? $default;
		}
		return $array;
	}

	/**
	 * Convert all values in an array to integers
	 *
	 * @param array $arr
	 * @param mixed $def
	 *            If value can not be converted to integer, use this value instead
	 * @return array
	 */
	public static function integer(array $arr, $def = null) {
		foreach ($arr as $k => $v) {
			$arr[$k] = to_integer($v, $def);
		}
		return $arr;
	}

	/**
	 * Replace string with another string in keys for this array
	 *
	 * @param array $arr
	 * @param string $find
	 * @param string $replace
	 * @return array
	 */
	public static function kreplace(array $arr, $find, $replace) {
		$new = [];
		foreach ($arr as $k => $v) {
			$new[strtr($k, [
				$find => $replace,
			])] = $v;
		}
		return $new;
	}

	/**
	 * Add up two vectors
	 *
	 * @param array $term
	 * @param array $add
	 * @return array
	 */
	public static function sum(array $term, array $add) {
		foreach ($term as $k => $v) {
			$term[$k] += intval($add[$k] ?? 0);
		}
		$term += $add;
		return $term;
	}

	/**
	 * Scale an array of numbers by a value.
	 * Can be associative or a single value.
	 *
	 * @param array $target
	 * @param mixed $scale
	 *            Double or array of key => number pairs.
	 * @return array, scaled. If scale is invalid type, returns $target.
	 */
	public static function scale(array $target, $scale) {
		if (is_array($scale)) {
			foreach ($scale as $k => $v) {
				if (array_key_exists($k, $target)) {
					$target[$k] *= $v;
				}
			}
		} elseif (is_numeric($scale)) {
			foreach ($target as $k => $v) {
				$target[$k] *= $scale;
			}
		}
		return $target;
	}

	/**
	 * Add an array of numbers by a value.
	 * Can be associative or a single value.
	 *
	 * @param array $target
	 * @param mixed $add
	 *            Double or array of key => number pairs.
	 * @return array, scaled. If scale is invalid type, returns $target.
	 */
	public static function add(array $target, $add) {
		if (is_array($add)) {
			foreach ($add as $k => $v) {
				if (array_key_exists($k, $target)) {
					$target[$k] += $v;
				}
			}
		} elseif (is_numeric($add)) {
			foreach ($target as $k => $v) {
				$target[$k] += $add;
			}
		}
		return $target;
	}

	/**
	 * Join an array to a string, and wrap each value with a prefix and suffix.
	 * Returns the empty string if array is empty.
	 *
	 * @param array $array
	 * @param string $prefix
	 * @param string $suffix
	 * @return string
	 */
	public static function join_wrap(array $array, $prefix = '', $suffix = '') {
		if (count($array) === 0) {
			return '';
		}
		return $prefix . implode($suffix . $prefix, $array) . $suffix;
	}

	/**
	 * Join an array to a string, and prefix each value
	 * Returns the empty string if array is empty.
	 *
	 * @param array $array
	 * @param string $prefix
	 * @return string
	 */
	public static function join_prefix(array $array, $prefix) {
		return self::join_wrap($array, $prefix);
	}

	/**
	 * Join an array to a string, and adds a suffix to each value
	 * Returns the empty string if array is empty.
	 *
	 * @param array $array
	 * @param string $suffix
	 * @return string
	 */
	public static function join_suffix(array $array, $suffix) {
		return self::join_wrap($array, '', $suffix);
	}

	/**
	 * Convert all values within an array to scalar values
	 *
	 * @param array $array
	 * @return array
	 */
	public static function scalars(array $array) {
		foreach ($array as $k => $v) {
			if (is_array($v)) {
				$array[$k] = ArrayTools::scalars($v);
			} elseif ($v !== null && !is_scalar($v)) {
				$array[$k] = strval($v);
			}
		}
		return $array;
	}

	/**
	 * Filter an array by one or more values
	 *
	 * @param array $array
	 * @param mixed $value
	 * @param boolean $strict
	 * @return mixed
	 */
	public static function filter_value(array $array, $value, $strict = true) {
		if (is_scalar($value)) {
			$value = [
				$value,
			];
		}
		$value = to_array($value);
		if (count($value) === 0) {
			return $value;
		}
		$result = [];
		foreach ($array as $k => $v) {
			if (in_array($v, $value, $strict)) {
				$result[$k] = $v;
			}
		}
		return $result;
	}

	/**
	 * Select values in an array which end with a specific string (case-sensitive)
	 *
	 * @param array $array
	 * @param string $ends
	 * @return array
	 */
	public static function filter_value_ends(array $array, $ends) {
		$result = [];
		foreach ($array as $k => $v) {
			if (ends($v, $ends)) {
				$result[$k] = $v;
			}
		}
		return $result;
	}

	/**
	 * Retrieve the maximum string length of all keys in the array
	 *
	 * @param array $array
	 * @return integer
	 */
	public static function key_maximum_length(array $array) {
		$len = 0;
		foreach ($array as $key => $value) {
			$len = max($len, strlen($key));
		}
		return $len;
	}

	/**
	 * Retrieve the minimum length of all keys in the array
	 *
	 * @param array $array
	 * @return integer
	 */
	public static function key_minimum_length(array $array) {
		$len = PHP_INT_MAX;
		foreach ($array as $key => $value) {
			$len = min($len, strlen($key));
		}
		return $len;
	}

	/**
	 * Convert multi-dimensional arrays to a single-dimension array, using separator to separate
	 * entities
	 *
	 * @param array $array
	 * @param string $separator
	 * @return array
	 */
	public static function kflatten(array $array, $separator) {
		$result = [];
		foreach ($array as $key => $item) {
			if (is_array($item)) {
				$item = self::kflatten($item, $separator);
				foreach ($item as $k => $v) {
					$result[$key . $separator . $k] = $v;
				}
			} else {
				$result[$key] = $item;
			}
		}
		return $result;
	}
}
