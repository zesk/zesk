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
 * Class containing some debugging tools
 *
 * @author kent
 */
class Debug {
	/**
	 *
	 * @var string
	 */
	public const DUMP_SPACER = '   ';

	/**
	 *
	 * @var integer
	 */
	public static int $indent_limit = 4;

	/**
	 * Set up PHP errors to output on web pages
	 */
	public static function php_errors_configure(): void {
		error_reporting(E_ALL | E_STRICT);
		ini_set('error_prepend_string', "\nPHP-ERROR " . str_repeat('=', 80) . "\n");
		ini_set('error_append_string', "\n" . str_repeat('*', 80) . "\n");
		ini_set('display_errors', true);
	}

	/**
	 * Returns the file which called this function. Useful for debugging.
	 *
	 * @return string
	 * @see debug_backtrace()
	 */
	public static function calling_file(): string {
		$bt = debug_backtrace();
		$top = array_shift($bt);
		return $top['file'];
	}

	/**
	 * Dumps a variable using print_r and surrounds with <pre> tag
	 *
	 * @param mixed $x
	 *            Variable to dump
	 * @param boolean $html
	 *            Whether to dump as HTML or not (surround by pre tags)
	 * @return void
	 * @see print_r
	 */
	public static function output(): void {
		$args = func_get_args();
		$result = call_user_func_array([
			'zesk\\Debug',
			'dump',
		], $args);
		echo $result . "\n";
	}

	/**
	 * Returns what "dump" would print (doesn't echo)
	 *
	 * @return A string representation of the value
	 * @see print_r, dump
	 */
	public static function dump(): string {
		$args = func_get_args();
		$result = [];
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
	private static function _dump(mixed $x): string {
		static $indent = 0;
		if ($x === null) {
			return '(null)';
		} elseif (is_bool($x)) {
			return '(boolean) ' . ($x ? 'true' : 'false');
		} elseif (is_string($x)) {
			return '(string(' . strlen($x) . ')) "' . addcslashes($x, "\0\r\n\t") . '"';
		} elseif (is_scalar($x)) {
			return '(' . gettype($x) . ") $x";
		} elseif (is_resource($x)) {
			return "(resource) $x";
		} elseif (is_array($x)) {
			$result = [];
			if ($indent < self::$indent_limit) {
				$indent++;
				$max_len = 0;
				foreach ($x as $k => $v) {
					$max_len = max($max_len, strlen("$k"));
				}
				foreach ($x as $k => $v) {
					$result[] = self::DUMP_SPACER . "[$k] " . str_repeat(' ', $max_len - strlen("$k")) . '= ' . self::_dump($v);
				}
				$indent--;
				$prefix = str_repeat(self::DUMP_SPACER, $indent);
				if (is_object($x)) {
					$type = $x::class;
				} else {
					$type = 'array';
				}
				return $prefix . "$type(" . (count($result) === 0 ? '' : ("\n$prefix" . implode(",\n$prefix", $result) . "\n$prefix")) . ')';
			} else {
				return '(recursion limit ' . self::$indent_limit . ')';
			}
		} elseif (is_object($x)) {
			foreach (['_debugDump', '_debug_dump'] as $method) {
				if (method_exists($x, $method)) {
					return $x->$method();
				}
			}
			return implode("\n" . str_repeat(self::DUMP_SPACER, $indent), explode("\n", self::_dump($x)));
		}
		return strval($x);
	}
}
