<?php
/**
 * @package zesk
 * @subpackage system
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2008, Market Acumen, Inc.
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
    public static function unique($list, $sep = ";") {
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
    public static function remove($list, $item, $sep = ";") {
        $is_arr = is_array($list);
        $a = $is_arr ? $list : explode($sep, $list);
        $item = to_list($item, array(), $sep);
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
     * @return string The new list
     */
    public static function append($list, $item, $sep = ";") {
        if ($item === null) {
            return $list;
        }
        $items = ArrayTools::clean(to_list($item, array(), $sep), null);
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
     * @param string|array $list List of strings
     * @param string $item Item(s) to add (array or string)
     * @param string $sep List separator, defaults to ";"
     * @return string Result list
     */
    public static function append_unique($list, $item, $sep = ";") {
        if ($item === null) {
            return $list;
        }
        $items = array_unique(ArrayTools::clean(to_list($item, array(), $sep), null));
        if (is_array($list)) {
            return array_unique(array_merge($list, $items));
        } elseif ($list === "") {
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
     * @return boolean
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
        if (is_array($list)) {
            return is_array($item) ? array_merge($item, $list) : array_merge(array(
                $item,
            ), $list);
        }
        if (strlen($list) === 0) {
            return is_array($item) ? implode($sep, $item) : $item;
        }
        return $item . $sep . $list;
    }
    
    /**
     * Pop an item from a list (similar to a stack)
     *
     * @param string|array $list
     * @param string $sep
     * @return string|array List with the last item removed
     */
    public static function pop($list, $sep = ";") {
        if (is_array($list)) {
            array_pop($list);
            return $list;
        }
        if (strlen($list) == 0) {
            return null;
        }
        $x = explode($list, $sep);
        array_pop($x);
        return implode($sep, $x);
    }
}
