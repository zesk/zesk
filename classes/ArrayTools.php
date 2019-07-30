<?php

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
	const TRIM_WHITESPACE = " \t\r\n\0\x0B";

	/**
	 * Capitalize all values in an array
	 *
	 * @param array $array
	 * @return array
	 */
	public static function capitalize(array $array) {
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
	public static function flatten(array $a) {
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
	public static function simplify(array $a) {
		foreach ($a as $k => $v) {
			if (is_object($v)) {
				$a[$k] = method_exists($v, "__toString") ? strval($v) : self::simplify(get_object_vars($v));
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
	 * @return array
	 * @param mixed $a
	 *        	Array to clean. If an array is not passed, just returned as-is.
	 * @param mixed $value
	 *        	Value or array of values to remove
	 */
	public static function clean($a, $value = "") {
		if (!is_array($a)) {
			return $a;
		}
		if (is_array($value)) {
			foreach ($value as $v) {
				$a = self::clean($a, $v);
			}
			return $a;
		}
		foreach ($a as $i => $v) {
			if ($v === $value) {
				unset($a[$i]);
			}
		}
		return $a;
	}

	/**
	 * Calls PHP trim() on each element of an array.
	 * If $a is not an array, returns trim($a)
	 *
	 * @return array
	 * @param mixed $a
	 *        	Array to trim
	 * @see trim()
	 */
	public static function trim(array $a, $charlist = self::TRIM_WHITESPACE) {
		foreach ($a as $i => $v) {
			$a[$i] = is_array($v) ? self::trim($v, $charlist) : trim($v, $charlist);
		}
		return $a;
	}

	/**
	 * Removes items from the beginning or end of the list which are empty.
	 *
	 * @param array $a
	 * @return array
	 */
	public static function list_trim(array $a) {
		return self::list_trim_tail(self::list_trim_head($a));
	}

	/**
	 * Removes items from the beginning of the list which are empty.
	 *
	 * @param array $a
	 * @return array
	 */
	public static function list_trim_head(array $a) {
		while (count($a) > 0) {
			$item = first($a);
			if (trim($item) === "") {
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
	public static function list_trim_tail(array $a) {
		while (count($a) > 0) {
			$item = last($a);
			if (trim($item) === "") {
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
	 * @param string $charlist
	 *        	List of characters to trim
	 * @param string $value
	 *        	Value which is removed
	 * @return array
	 */
	public static function trim_clean(array $arr, $charlist = self::TRIM_WHITESPACE, $value = "") {
		return self::clean(self::trim($arr, $charlist), $value);
	}

	/**
	 * Calls PHP rtrim() on each element of an array.
	 * If $a is not an array, returns rtrim($a)
	 *
	 * @return array
	 * @param mixed $a
	 *        	Array to trim
	 * @see trim()
	 */
	public static function rtrim($a, $charlist = self::TRIM_WHITESPACE) {
		if (!is_array($a)) {
			return rtrim($a, $charlist);
		}
		foreach ($a as $i => $v) {
			$a[$i] = self::rtrim($v, $charlist);
		}
		return $a;
	}

	/**
	 * Calls PHP ltrim() on each element of an array.
	 * If $a is not an array, returns ltrim($a)
	 *
	 * @return array
	 * @param mixed $a
	 *        	Array to trim
	 * @see trim()
	 */
	public static function ltrim($a, $charlist = self::TRIM_WHITESPACE) {
		if (!is_array($a)) {
			return ltrim($a, $charlist);
		}
		foreach ($a as $i => $v) {
			$a[$i] = self::ltrim($v, $charlist);
		}
		return $a;
	}

	/**
	 * Calls PHP ltrim() on each key of an array.
	 * If $a is not an array, returns ltrim($a)
	 *
	 * @return array
	 * @param mixed $a
	 *        	Array to rtrim
	 * @see ltrim()
	 */
	public static function kltrim($a, $charlist = self::TRIM_WHITESPACE) {
		if (!is_array($a)) {
			return ltrim($a, $charlist);
		}
		$b = array();
		foreach ($a as $i => $v) {
			$b[ltrim($i, $charlist)] = $v;
		}
		return $b;
	}

	/**
	 * Calls PHP trim() on each key of an array.
	 * If $a is not an array, returns trim($a)
	 *
	 * @return array
	 * @param mixed $a
	 *        	Array to rtrim
	 * @see ltrim()
	 */
	public static function ktrim($a, $charlist = self::TRIM_WHITESPACE) {
		if (!is_array($a)) {
			return trim($a, $charlist);
		}
		$b = array();
		foreach ($a as $i => $v) {
			$b[trim($i, $charlist)] = $v;
		}
		return $b;
	}

	/**
	 * Prefix each value in an array with a string
	 *
	 * @param array $arr
	 * @param string $str
	 * @return array
	 */
	public static function prefix(array $arr, $str) {
		foreach ($arr as $k => $v) {
			$arr[$k] = $str . $v;
		}
		return $arr;
	}

	/**
	 * Suffix each value in an array with a string
	 *
	 * @param array $arr
	 * @param string $str
	 * @return array
	 */
	public static function suffix(array $arr, $str) {
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
	public static function wrap(array $arr, $prefix = "", $suffix = "") {
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
	public static function kprefix(array $arr, $str) {
		$result = array();
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
	public static function ksuffix(array $arr, $str) {
		$result = array();
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
	public static function kwrap(array $arr, $prefix = "", $suffix = "") {
		$result = array();
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
	 *        	Remove unmatched entries
	 * @return array
	 */
	public static function unprefix(array $arr, $str, $remove = false) {
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
	 * Works recursivly on arrays in arrays.
	 *
	 * @param array $arr
	 * @param string $str
	 * @param boolean $remove
	 *        	Remove unmatched entries
	 * @return array
	 */
	public static function unsuffix(array $arr, $str, $remove = false) {
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
	 * Works recursivly on arrays in arrays.
	 *
	 * @param array $arr
	 * @param string $prefix
	 * @param string $suffix
	 * @param boolean $remove
	 *        	Remove unmatched entries
	 * @return array
	 */
	public static function unwrap(array $arr, $prefix, $suffix, $remove = false) {
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
	 *        	Remove unmatched entries
	 * @return array
	 */
	public static function kunprefix(array $arr, $str, $remove = false) {
		$n = strlen($str);
		$result = array();
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
	 *        	Remove unmatched entries
	 * @return array
	 */
	public static function kunsuffix(array $arr, $str, $remove = false) {
		$n = strlen($str);
		$result = array();
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
	 *        	Array or list of keys to remove
	 * @return array
	 */
	public static function remove(array $arr, $keys) {
		$keys = to_list($keys);
		foreach ($keys as $k) {
			unset($arr[$k]);
		}
		return $arr;
	}

	/**
	 * Remove values from an array
	 *
	 * @param array $arr
	 * @param mixed $keys
	 *        	Array or list of keys to remove
	 * @return array
	 */
	public static function remove_values(array $arr, $values) {
		$keys = array_flip(to_list($values));
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
	 *        	array initial array
	 * @param
	 *        	array array to merge
	 * @param
	 *        	array ...
	 * @return array
	 */
	public static function merge(array $a1) {
		$result = array();
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
	 * @param array $current
	 * @param string $path
	 *        	A path into the array separated by $separator (e.g. "document.title")
	 * @param mixed $value
	 *        	Value to set the path in the trr
	 * @param string $separator
	 *        	Character used to separate levels in the array
	 * @return array
	 */
	public static function path_set(array &$array, $path, $value, $separator = ".") {
		return apath_set($array, $path, $value, $separator);
	}

	/**
	 * Gets a value from an array using a delimited separated path.
	 * // Get the value of $array['foo']['bar']
	 * $value = apath($array, 'foo.bar');
	 *
	 * @param
	 *        	array array to search
	 * @param
	 *        	string key path, dot separated
	 * @param
	 *        	mixed default value if the path is not set
	 * @return mixed
	 * @see ArrayTools::path_set
	 * @deprecated Use apath
	 */
	public static function path(array $array, $path, $default = null, $separator = ".") {
		return apath($array, $path, $default, $separator);
	}

	/**
	 * Take a list of arrays and create a new array using values found in it.
	 *
	 * @param array $arrays
	 * @param string|null $key_key
	 * @param string|null $value_key
	 * @return array
	 */
	public static function extract(array $arrays, $key_key = null, $value_key = null, $default_value = null) {
		$result = array();
		if ($key_key === null) {
			foreach ($arrays as $key => $array) {
				$result[$key] = avalue($array, $value_key, $default_value);
			}
		} else {
			foreach ($arrays as $array) {
				$result[avalue($array, $key_key)] = avalue($array, $value_key, $default_value);
			}
		}
		return $result;
	}

	/**
	 * Take a list of arrays and create a new array using values found in it.
	 *
	 * @see ArrayTools::extract
	 * @param array $arrays
	 * @param string|null $key_key
	 * @param string|null $value_key
	 * @return array
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
		$result = array();
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
	 *        	Array to convert
	 * @param array $key_map
	 *        	Array of old_key => new_key to convert
	 * @return array The converted array
	 */
	public static function map_keys(array $array, array $key_map) {
		if (count($key_map) === 0) {
			return $array;
		}
		$new_array = $skip = array();
		foreach ($array as $k => $v) {
			$newk = avalue($key_map, $k, $k);
			if ($newk !== $k) {
				$skip[$newk] = true;
			} elseif (array_key_exists($k, $skip)) {
				continue;
			}
			$new_array[$newk] = $v;
		}
		return $new_array;
	}

	/**
	 * Convert values in this array to new values
	 *
	 * @param array $array
	 *        	Array to convert
	 * @param array $key_map
	 *        	Array of old_value => new_value to convert
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
	 *        	List of delimiter characters
	 */
	public static function field(array $array, $index = null, $delim = " \t", $max_fields = null) {
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
	 * Convert a list of strings to a set of key pairs by dividing them along a delimeter
	 *
	 * @inline_test ArrayTools::kpair(["dog bark", "cat meow", "child coo"]) === ["dog" => "bark", "cat" => "meow", "child" => "coo"];
	 *
	 * @param array $array
	 *        	Array of strings
	 * @param string $delim
	 *        	Delimiter to split on
	 */
	public static function kpair(array $array, $delim = " ") {
		$result = array();
		foreach ($array as $k => $item) {
			list($key, $value) = pair($item, $delim, $k, $item);
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
	 * @param array $array
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
	 *        	A key, or an array of keys to check for. If a string, converted to a list via
	 *        	to_list
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
	 * @param mixed $keys
	 *        	A key, or an array of keys to check for. If a string, converted to a list via
	 *        	to_list
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
	 *        	A key, or an array of keys to check for. If a string, converted to a list via
	 *        	to_list
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
	 * @param mixed $keys
	 *        	A key, or an array of keys to check for. If a string, converted to a list via
	 *        	to_list
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
	 *        	Array to search
	 * @param mixed $default
	 *        	Value to return of no minimum value found in array
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
	 *        	Array to search
	 * @param mixed $default
	 *        	Value to return of no maximum value found in array
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
		$r = array();
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
	 *        	The array to filter
	 * @param mixed $include
	 *        	A list of keys to include either as a string "k1;k2;k3", or array('k1','k2','k3'),
	 *        	or a map
	 *        	array('k1' => 'newkk1','k2' => 'newk2') to convert extracted keys
	 * @return array The array with only the keys passed in which already exist in the array
	 */
	public static function filter(array $arr, $keys) {
		if (is_string($keys)) {
			$keys = explode(";", $keys);
		}
		$r = array();
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
	 *        	An array to retrieve key values from
	 * @param mixed $ks
	 *        	A ;-separated list, or an array of keys of strings which contain prefixes which
	 *        	should match
	 * @return array
	 */
	public static function filter_prefix($a, $prefixes, $lower = false) {
		if (is_string($prefixes)) {
			$prefixes = explode(";", $prefixes);
		}
		$r = array();
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
	 *        	A reference to an array to filter
	 * @param array $include
	 *        	A list of array values to explicitly include, or false to include all items
	 * @param array $exclude
	 *        	A list of array values to explicitly exclude, or false to exclude no items
	 * @param bool $lower
	 *        	Whether the array values are case-sensistive or not. 2015-06-23 Lower is by
	 *        	default false (no changes to input).
	 * @return Filtered array
	 */
	public static function include_exclude(array $a, $include = null, $exclude = null, $lower = false) {
		if (!is_array($a)) {
			return $a;
		}
		$newa = $lower ? self::change_value_case($a) : $a;
		if (!is_bool($include) && !empty($include)) {
			$include = to_list($include, null);
			if (is_array($include)) {
				if ($lower) {
					$include = array_keys(array_change_key_case(array_flip($include)));
				}
				$newa = array_intersect($newa, $include);
			}
		}
		if (!is_bool($exclude) && !empty($exclude)) {
			$exclude = to_list($exclude, array());
			$exclude = array_flip($exclude);
			if ($lower) {
				$exclude = array_change_key_case($exclude);
			}
			foreach ($a as $i => $action) {
				if (!is_scalar($action)) {
					error_log(__METHOD__ . ' ' . type($action) . " " . _backtrace(), E_USER_ERROR);
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
	 * @param array $a
	 *        	A reference to an array to filter
	 * @param array $include
	 *        	A list of array keys to explicitly include, or false to include all items
	 * @param array $exclude
	 *        	A list of array keys to explicitly exclude, or false to exclude no items
	 * @param bool $lower
	 *        	Whether the array keys are case-sensistive or not
	 * @return integer false count of the number of items in the filtered array
	 */
	public static function kfilter($a, $include = false, $exclude = false, $lower = false) {
		if (!is_array($a)) {
			return $a;
		}
		if ($exclude === true) {
			$exclude = array_keys($a);
		}
		if (is_string($include)) {
			$include = explode(";", $include);
		}
		if (is_string($exclude)) {
			$exclude = explode(";", $exclude);
		}
		if (!is_array($include) && !is_array($exclude)) {
			return $a;
		}

		$ak = array_keys($a);
		if (is_array($exclude)) {
			$exclude = array_flip($exclude);
			if ($lower) {
				$exclude = array_change_key_case($exclude);
			}
		}
		if (is_array($include)) {
			$include = array_flip($include);
			if ($lower) {
				$include = array_change_key_case($include);
			}
			foreach ($ak as $k) {
				$lk = $lower ? strtolower($k) : $k;
				if (!isset($include[$lk]) && isset($exclude[$lk])) {
					unset($a[$lk]);
				}
			}
		} else {
			foreach ($ak as $k) {
				$lk = $lower ? strtolower($k) : $k;
				if (isset($exclude[$lk])) {
					unset($a[$k]);
				}
			}
		}
		if (is_array($include)) {
			foreach ($ak as $k) {
				$lk = $lower ? strtolower($k) : $k;
				if (!isset($include[$lk])) {
					unset($a[$k]);
				}
			}
		}
		return $a;
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
	 * @return void
	 * @param array $arr
	 *        	A reference to an array to append
	 * @param string $k
	 *        	A key value for the array
	 * @param mixed $v
	 *        	The value to store in the array
	 * @see ArrayTools::prepend
	 */
	public static function append(&$arr, $k, $v = null) {
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
				$arr[$k] = array(
					$arr[$k],
					$v,
				);
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
	 * @return void
	 * @param array $arr
	 *        	A reference to an array to append
	 * @param string $k
	 *        	A key value for the array
	 * @param mixed $v
	 *        	The value to store in the array
	 * @see ArrayTools::append
	 */
	public static function prepend(&$arr, $k, $v = null) {
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
				$arr[$k] = array(
					$v,
					$arr[$k],
				);
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
	 *        	Array to modify
	 * @param boolean $lower
	 *        	Convert the keys to lowercase. Defaults to "true"
	 * @return An array the keys and values identical (or lowercase eqivalent)
	 */
	public static function flip_copy($x, $lower = true) {
		$result = array();
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
	 * @return Ambigous <multitype:multitype:unknown , unknown>
	 */
	public static function flip_multiple(array $arr) {
		$result = array();
		foreach ($arr as $k => $v) {
			if (array_key_exists($v, $result)) {
				$result[$v][] = $k;
			} else {
				$result[$v] = array(
					$k,
				);
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
	 *        	Array to modify
	 * @return array
	 */
	public static function flip_assign($x, $value = null) {
		$result = array();
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
	 *        	Array to search
	 * @param array $sourcekeys
	 *        	List of keys to find in source
	 * @param mixed $default
	 *        	default value to return if found key is empty
	 * @return mixed Key found, default, or false if none found
	 */
	public static function kfind(array $source, array $sourcekeys, $default = false) {
		foreach ($sourcekeys as $k) {
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
	 * @param $arr1 array
	 *        	to insert into
	 * @param $key key
	 *        	of $arr1 to insert after (or before)
	 * @param $arr2 array
	 *        	whose values should be inserted
	 * @param $before boolean.
	 *        	insert before the given key. defaults to inserting after
	 * @return merged array
	 */
	public static function insert($arr1, $key, $arr2, $before = false) {
		$result = array();
		$key = strval($key);
		if (!array_key_exists($key, $arr1)) {
			return $before ? array_merge($arr2, $arr1) : array_merge($arr1, $arr2);
		}
		foreach ($arr1 as $k => $v) {
			if (strval($k) === $key) {
				$found = true;
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
	 * @return integer
	 * @param array $arr
	 *        	An array to create a counter in
	 * @param string|numeric $key
	 *        	Key to increment
	 * @param numeric $amount
	 *        	Amount to increment
	 */
	public static function increment(&$arr, $k, $amount = 1) {
		if (array_key_exists($k, $arr)) {
			return $arr[$k] += $amount;
		}
		return $arr[$k] = $amount;
	}

	/**
	 * Given a string and an array of strings, find if string $needles exists in $haystack.
	 * <em>Performs a
	 * case-insensitive search.</em>
	 *
	 * @return mixed Integer or string key of found needle in $needles
	 * @param array $haystack
	 *        	Array of strings to search
	 * @param array $needles
	 *        	Array of strings to find in $haystack
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
	 * @return mixed Integer or string key of found needle in $needles
	 * @param string $haystack
	 * @param array $needles
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
	 * @return boolean
	 * @param mixed $haystack
	 * @param mixed $needles
	 * @see strstr()
	 */
	public static function find($haystack, $needles) {
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
		return (strpos($haystack, $needles) !== false);
	}

	/**
	 * Given arrays and string inputs, find if any needles appear in haystack.
	 * <em>Case insensitive.</em>
	 *
	 * @return boolean
	 * @param mixed $haystack
	 * @param mixed $needles
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
	 * @return array Array of values which match
	 * @param mixed $haystack
	 * @param mixed $needles
	 * @see strstr()
	 */
	public static function match(array $haystack, $needles) {
		$result = array();
		if (is_array($needles)) {
			foreach ($needles as $needle) {
				$result = array_merge($result, self::match($haystack, $needle));
			}
			return $result;
		}
		foreach ($haystack as $h => $v) {
			if (strpos($v, $needles) !== false) {
				$result[$h] = $v;
			}
		}
		return $result;
	}

	/**
	 * Given arrays and string inputs, find if any needles appear in haystack.
	 * <em>Case insensitive.</em>
	 *
	 * @return array Array of values which match
	 * @param mixed $haystack
	 * @param mixed $needles
	 * @see strstr()
	 */
	public static function imatch(array $haystack, $needles) {
		$result = array();
		if (is_array($needles)) {
			foreach ($needles as $needle) {
				$result = array_merge($result, self::match($haystack, $needle));
			}
			return $result;
		}
		foreach ($haystack as $h => $v) {
			if (stripos($v, $needle) !== false) {
				$result[$h] = $v;
			}
		}
		return $result;
	}

	/**
	 * Run preg_quote on an array of values
	 *
	 * @param mixed $string
	 *        	Array or string to preg_quote
	 * @param string $delimiter
	 *        	Delimiter passed to preg_quote
	 * @return mixed
	 */
	public static function preg_quote($string, $delimiter = null) {
		if (is_string($string)) {
			return preg_quote($string, $delimiter);
		} elseif (is_array($string)) {
			$result = array();
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
		$new = array();
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
	 *        	Key to pull from the values of the array
	 */
	public static function collapse(array $array, $key, $default = null) {
		foreach ($array as $k => $subarray) {
			$array[$k] = avalue($subarray, $key, $default);
		}
		return $array;
	}

	/**
	 * Convert all values in an array to integers
	 *
	 * @param array $arr
	 * @param mixed $def
	 *        	If value can not be converted to integer, use this value instead
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
		$new = array();
		foreach ($arr as $k => $v) {
			$new[strtr($k, array(
				$find => $replace,
			))] = $v;
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
			$term[$k] += intval(avalue($add, $k, 0));
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
	 *        	Double or array of key => number pairs.
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
	 *        	Double or array of key => number pairs.
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
	public static function join_wrap(array $array, $prefix = "", $suffix = "") {
		if (count($array) === 0) {
			return "";
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
	 * @param string $prefix
	 * @return string
	 */
	public static function join_suffix(array $array, $suffix) {
		return self::join_wrap($array, "", $suffix);
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
	 * @param string $strict
	 * @return multitype:unknown
	 */
	public static function filter_value(array $array, $value, $strict = true) {
		if (is_scalar($value)) {
			$value = array(
				$value,
			);
		}
		$value = to_array($value);
		if (count($value) === 0) {
			return $value;
		}
		$result = array();
		foreach ($array as $k => $v) {
			if (in_array($v, $value, $strict)) {
				$result[$k] = $v;
			}
		}
		return $result;
	}

	/**
	 * Filter an array by one or more values
	 *
	 * @param array $array
	 * @param mixed $value
	 * @param string $strict
	 * @return multitype:unknown
	 */
	public static function filter_value_ends(array $array, $ends) {
		$result = array();
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
		$len = 0;
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
		$result = array();
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
