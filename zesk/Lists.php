<?php
declare(strict_types=1);
/**
 * @package zesk
 * @subpackage system
 * @author kent
 * @copyright Copyright &copy; 2023, Market Acumen, Inc.
 */

namespace zesk;

/**
 * Handles more traditional Cold-Fusion or Shell style lists, basically a string separated by characters
 *
 * Lists, by default, are separated by semicolon, ";" but can be separated by any character sequence
 * These are meant to handle simplistic cases, and does not really scale for lists of thousands of items.
 * If that's the case, then use arrays or another structure.
 *
 * All functions within support array lists as well; the semantic is:
 * - if an array is passed in for a list, then an array is returned
 * - if a delimited-string list is passed in for a list, then a delimited-string list is returned
 *
 * In the documentation
 *
 * @author kent
 */
class Lists {
	/**
	 * Make a list unique
	 *
	 * @param string|array $list
	 * @param string $sep
	 * @return string|array
	 */
	public static function unique(string|array $list, string $sep = ';'): string|array {
		if (is_array($list)) {
			return array_unique($list);
		}
		return implode($sep, array_unique(explode($sep, $list)));
	}

	/**
	 * Remove a specific item from a list, maintaining existing order
	 *
	 * @param string|array $list List of tokens
	 * @param string|array $item Item or items to remove
	 * @param string $sep List separator, defaults to ";", only relevant if string passed for first parameter
	 * @return string|array The new list
	 */
	public static function keysRemove(array|string $list, string|array $item, string $sep = ';'): array|string {
		$is_arr = is_array($list);
		$a = $is_arr ? $list : explode($sep, $list);
		$item = toList($item, [], $sep);
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
	 * @param string|array $list List of strings
	 * @param string|array $item Item or items to add
	 * @param string $sep List separator, defaults to ";"
	 * @return string|array The new list
	 */
	public static function append(string|array $list, string|array $item, string $sep = ';'): string|array {
		$items = ArrayTools::clean(toList($item, [''], $sep), null);
		if (count($items) === 0) {
			return $list;
		}
		if (is_array($list)) {
			return array_merge($list, $items);
		}
		if (strlen($list) === 0) {
			return implode($sep, $items);
		}
		return $list . $sep . implode($sep, $items);
	}

	/**
	 * Add an item to a list only if it's not in it already
	 *
	 * @param string|array $list List to append - same type is returned
	 * @param string|array $item Items to add
	 * @param string $sep List separator
	 * @return string|array Result list
	 */
	public static function appendUnique(string|array $list, string|array $item, string $sep = ';'): string|array {
		$items = array_unique(ArrayTools::clean(toList($item, [''], $sep), null));
		if (is_array($list)) {
			return array_unique(array_merge($list, $items));
		} elseif ($list === '') {
			return implode($sep, $items);
		} else {
			return implode($sep, array_unique(array_merge(explode($sep, $list), $items)));
		}
	}

	/**
	 * Does this list contain an item?
	 *
	 * @param string|array $list List to check
	 * @param string $item Item to check if it appears in the list
	 * @param string $sep List separator, defaults to ";"
	 * @return bool
	 */
	public static function contains(array|string $list, string $item, string $sep = ';'): bool {
		if (is_array($list)) {
			return in_array($item, $list);
		}
		return str_contains($sep . $list . $sep, $sep . $item . $sep);
	}

	/**
	 * Prepend an item to a list, maintaining existing order
	 *
	 * @param array|string $list List of strings
	 * @param array|string $items Item to add
	 * @param string $sep List separator, defaults to ";"
	 * @return array|string The new list
	 */
	public static function prepend(array|string $list, array|string $items, string $sep = ';'): array|string {
		if (is_array($list)) {
			return is_array($items) ? array_merge($items, $list) : array_merge([
				$items,
			], $list);
		}
		if (strlen($list) === 0) {
			return is_array($items) ? implode($sep, $items) : $items;
		}
		return $items . $sep . $list;
	}

	/**
	 * Pop an item from a list (similar to a stack)
	 *
	 * @param string|array $list
	 * @param string $sep
	 * @return string|array List with the last item removed
	 */
	public static function pop(string|array $list, string $sep = ';'): array|string {
		if (is_array($list)) {
			array_pop($list);
			return $list;
		}
		if (strlen($list) == 0) {
			return '';
		}
		$x = explode($sep, $list);
		array_pop($x);
		return implode($sep, $x);
	}
}
