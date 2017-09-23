<?php
/**
 * $URL: https://code.marketacumen.com/zesk/trunk/classes/Debug.php $
 * @package zesk
 * @subpackage system
 * @author kent
 * @copyright Copyright &copy; 2009, Market Acumen, Inc.
 */
namespace zesk;

/**
 * Class containing some debugging tools
 *
 * @author kent
 */
class Debug {
	/**
	 * 
	 * @var string
	 */
	const dump_spacer = "  ";
	
	/**
	 *
	 * @var integer
	 */
	public static $indent_limit = 2;
	
	/**
	 * Set up PHP errors to output on web pages
	 */
	public static function php_errors_configure() {
		error_reporting(E_ALL | E_STRICT);
		ini_set('error_prepend_string', "\nPHP-ERROR " . str_repeat("=", 80) . "\n");
		ini_set('error_append_string', "\n" . str_repeat("*", 80) . "\n");
		ini_set('display_errors', true);
	}
	
	/**
	 * Returns the file which called this function. Useful for debugging.
	 *
	 * @return string
	 * @see debug_backtrace()
	 */
	public static function calling_file() {
		$bt = debug_backtrace();
		$top = array_shift($bt);
		return $top['file'];
	}
	
	/**
	 * Dumps a variable using print_r and surrounds with <pre> tag
	 *
	 * @param mixed $x
	 *        	Variable to dump
	 * @param boolean $html
	 *        	Whether to dump as HTML or not (surround by pre tags)
	 * @return echos to page
	 * @see print_r
	 */
	public static function output() {
		$args = func_get_args();
		$result = call_user_func_array(array(
			'zesk\\Debug',
			"dump"
		), $args);
		if (zesk()->console) {
			echo $result . newline();
		} else {
			echo nl2br($result . newline());
		}
	}
	
	/**
	 * Returns what "dump" would print (doesn't echo)
	 *
	 * @param mixed $x
	 *        	Variable to dump
	 * @return A string representation of the value
	 * @see print_r, dump
	 */
	public static function dump() {
		$args = func_get_args();
		$result = array();
		foreach ($args as $x) {
			$result[] = self::_dump($x);
		}
		return implode("\n", $result);
	}
	
	/**
	 * Internal dump function
	 *
	 * @param string $x
	 * @return string
	 */
	private static function _dump($x) {
		static $indent = 0;
		if ($x === null) {
			return "(null)";
		} else if (is_bool($x)) {
			return "(boolean) " . ($x ? 'true' : 'false');
		} else if (is_string($x)) {
			return "(string(" . strlen($x) . ")) \"" . addcslashes($x, "\0\r\n\t") . "\"";
		} else if (is_scalar($x)) {
			return "(" . gettype($x) . ") $x";
		} else if (is_resource($x)) {
			return "(resource) $x";
		} else if (is_array($x)) {
			$result = array();
			if ($indent < self::$indent_limit) {
				$indent++;
				$max_len = 0;
				foreach ($x as $k => $v) {
					$max_len = max($max_len, strlen("$k"));
				}
				foreach ($x as $k => $v) {
					$result[] = self::dump_spacer . "[$k] " . str_repeat(" ", $max_len - strlen("$k")) . "= " . self::_dump($v);
				}
				$indent--;
				$prefix = str_repeat(self::dump_spacer, $indent);
				if (is_object($x)) {
					$type = get_class($x);
				} else {
					$type = "array";
				}
				return $prefix . "$type(" . (count($result) === 0 ? "" : ("\n$prefix" . implode(",\n$prefix", $result) . "\n$prefix")) . ")";
			} else {
				return "(recursion limit " . self::$indent_limit . ")";
			}
		} else if (is_object($x)) {
			if (method_exists($x, "_debug_dump")) {
				return $x->_debug_dump();
			}
			return implode("\n" . str_repeat(self::dump_spacer, $indent), explode("\n", self::_dump($x, true)));
		}
		return strval($x);
	}
}
