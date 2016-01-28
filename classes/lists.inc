<?php
/**
 * $URL: https://code.marketacumen.com/zesk/trunk/classes/lists.inc $
 * @package zesk
 * @subpackage system
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2008, Market Acumen, Inc.
 */

/**
 * Handles more traditional Cold-Fusion style lists, basically a string separated by characters
 * Lists, by default, are separated by semicolon, ";" but can be separated by any character sequence
 * These are meant to handle simplistic cases, and does not really scale for lists of thousands of items.
 * If that's the case, then use arrays or another structure.
 *
 * @author kent
 */
class lists {

	/**
	 * Make a list unique
	 * @param string $list
	 * @param string $sep
	 * @return string
	 */
	public static function unique($list, $sep = ";") {
		return implode($sep, array_unique(explode($sep, $list)));
	}

	/**
	 * Remove a specific item from a list, maintaining existing order
	 *
	 * @param string $list List of tokens
	 * @param string $item Item to remove
	 * @param string $sep List separator, defaults to ";"
	 * @return string The new list
	 */
	public static function remove($list, $item, $sep = ";") {
		$is_arr = is_array($list);
		if (!$is_arr) {
			$a = explode($sep, $list);
		}
		$item = to_list($item);
		foreach ($a as $k => $i) {
			if (in_array($i, $item)) {
				unset($a[$k]);
			}
		}
		return !$is_arr ? implode($sep, $a) : $a;
	}

	/**
	 * Add an item to a list, maintaining existing order
	 *
	 * @param string $list List of strings
	 * @param string $item Item to add
	 * @param string $sep List separator, defaults to ";"
	 * @return string The new list
	 */
	public static function append($list, $item, $sep = ";") {
		if (is_array($list)) {
			$list[] = $item;
			return $list;
		}
		if (strlen($list) === 0) {
			return $item;
		}
		if (strlen($item) === 0) {
			return $list;
		}
		return $list . $sep . $item;
	}

	/**
	 * Add an item to a list only if it's not in it already
	 * @param string $list List of strings
	 * @param string $item Item(s) to add (array or string)
	 * @param string $sep List separator, defaults to ";"
	 * @return string Result list
	 */
	public static function append_unique($list, $item, $sep = ";") {
		if (empty($item)) {
			return $list;
		}
		if (is_array($item)) {
			foreach ($item as $i) {
				if (empty($i)) {
					continue;
				}
				$list = self::append_unique($list, $i, $sep);
			}
			return $list;
		}
		if (!self::contains($list, $item, $sep)) {
			return self::append($list, $item, $sep);
		}
		return $list;
	}

	/**
	 * Does this list contain an item?
	 *
	 * @param string $list List to check
	 * @param string $item Item to check if it appears in the list
	 * @param string $sep List separator, defaults to ";"
	 * @return unknown
	 */
	public static function contains($list, $item, $sep = ";") {
		if (is_array($list)) {
			return in_array($item, $list);
		}
		return strpos($sep . $list . $sep, $sep . $item . $sep) !== false;
	}

	/**
	 * Prepend an item to a list, maintaining existing order
	 *
	 * @param string $list List of strings
	 * @param string $item Item to add
	 * @param string $sep List separator, defaults to ";"
	 * @return string The new list
	 */
	public static function prepend($list, $item, $sep = ";") {
		if (strlen($list) == 0) {
			return $item;
		}
		return $item . $sep . $list;
	}

	public static function pop($list, $sep = ";") {
		if (strlen($list) == 0) {
			return null;
		}
		$x = explode($list, $sep);
		array_pop($x);
		return implode($sep, $x);
	}
}
