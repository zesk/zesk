<?php
declare(strict_types=1);
/**
 *
 */

namespace zesk;

use JetBrains\PhpStorm\Pure;
use \ReflectionObject;
use \ReflectionProperty;

/**
 *
 * @author kent
 *
 */
class PHP {
	/**
	 * Character used to indent code
	 *
	 * @var string
	 */
	public $indent_char = " ";

	/**
	 * When indenting, use $indent_multiple times $indent_char for each indent level
	 *
	 * @var integer
	 */
	public $indent_multiple = 4;

	/**
	 * Value to separate array values
	 *
	 * @var string
	 */
	public $array_value_separator = "\n";

	/**
	 * Put a trainling comma on array output
	 *
	 * @var boolean
	 */
	public $array_trailing_comma = false;

	/**
	 * Characters to place before an array arrow =>
	 *
	 * @var string
	 */
	public $array_arrow_prefix = " ";

	/**
	 * Characters to place after an array arrow =>
	 *
	 * @var string
	 */
	public $array_arrow_suffix = " ";

	/**
	 * Characters to place before an array open parenthesis
	 *
	 * @var string
	 */
	public $array_open_parenthesis_prefix = "";

	/**
	 * Characters to place after an array open parenthesis
	 *
	 * @var string
	 */
	public $array_open_parenthesis_suffix = "\n";

	/**
	 * Characters to place before an array close parenthesis
	 *
	 * @var string
	 */
	public $array_close_parenthesis_prefix = "";

	/**
	 * Characters to place after an array close parenthesis
	 *
	 * @var string
	 */
	public $array_close_parenthesis_suffix = "";

	/**
	 * Global dump settings, used when called statically
	 *
	 * @var php
	 */
	private static $singleton = null;

	/**
	 * Return global static dump object
	 *
	 * @return php
	 */
	public static function singleton() {
		if (!self::$singleton instanceof self) {
			self::$singleton = new self();
		}
		return self::$singleton;
	}

	/**
	 * Retrieve the php.ini path
	 *
	 * @return string
	 */
	public static function ini_path() {
		return get_cfg_var('cfg_file_path');
	}

	/**
	 * Set or get the current settings
	 *
	 * @param array $set
	 * @return
	 */
	public static function dump_settings(array $set = null) {
		return self::singleton()->settings($set);
	}

	/**
	 * Set the settings in this object to support one-line output
	 *
	 * @return php
	 */
	public function settings_one() {
		$this->indent_char = "";
		$this->array_value_separator = " ";
		$this->array_open_parenthesis_suffix = "";
		$this->array_close_parenthesis_suffix = "";
		return $this;
	}

	/**
	 *
	 * @return php
	 */
	public static function dump_settings_one() {
		return self::singleton()->settings_one()->settings();
	}

	/**
	 * Dump value as PHP
	 *
	 * @param mixed $x
	 * @return string
	 */
	public static function dump($x) {
		return self::singleton()->render($x);
	}

	/**
	 * Get or set the settings in this object
	 *
	 * @param array $set
	 * @return ReflectionProperty[]|$this
	 */
	public function settings(array $set = null) {
		$x = new ReflectionObject($this);
		if (is_array($set)) {
			foreach ($set as $prop => $value) {
				if ($x->hasProperty($prop)) {
					$this->$prop = $value;
				}
			}
			return $this;
		}
		$result = $x->getProperties();
		return $result;
	}

	/**
	 * Output PHP structures formatted in PHP language
	 *
	 * @param mixed $x
	 * @return string
	 */
	public function render($x) {
		$args = func_get_args();
		$no_first_line_indent = to_bool(avalue($args, 2));
		if (is_array($x)) {
			if (count($x) === 0) {
				return "array()";
			}
			$indent_level = avalue($args, 1, 0);
			$result = ($no_first_line_indent ? '' : str_repeat($this->indent_char, $indent_level * $this->indent_multiple)) . "array" . $this->array_open_parenthesis_prefix . "(" . $this->array_open_parenthesis_suffix;
			$items = [];
			if (ArrayTools::is_list($x)) {
				foreach ($x as $k => $v) {
					$items[] = str_repeat($this->indent_char, ($indent_level + 1) * $this->indent_multiple) . $this->render($v, $indent_level + 1, true);
				}
			} else {
				foreach ($x as $k => $v) {
					$items[] = str_repeat($this->indent_char, ($indent_level + 1) * $this->indent_multiple) . $this->render($k) . $this->array_arrow_prefix . "=>" . $this->array_arrow_suffix . $this->render($v, $indent_level + 1, true);
				}
			}
			$sep = "," . $this->array_value_separator;
			$result .= implode($sep, $items);
			$result .= $this->array_trailing_comma ? "," : "";
			$result .= $this->array_value_separator;
			$result .= str_repeat($this->indent_char, $indent_level * $this->indent_multiple) . $this->array_close_parenthesis_prefix . ")" . $this->array_close_parenthesis_suffix;
			return $result;
		} elseif (is_string($x)) {
			return '"' . addcslashes($x, "\$\"\\\0..\37") . '"';
		} elseif (is_int($x)) {
			return "$x";
		} elseif ($x === null) {
			return "null";
		} elseif (is_bool($x)) {
			return $x ? "true" : "false";
		} elseif (is_object($x)) {
			if (method_exists($x, '_to_php')) {
				return $x->_to_php();
			}
			if (method_exists($x, '__toString')) {
				return 'new ' . get_class($x) . '(' . strval($x) . ')';
			}
			return 'new ' . get_class($x) . '()';
		} else {
			return strval($x);
		}
	}

	/**
	 * Exception logged during unserialization
	 *
	 * @var Exception
	 */
	public static $unserialize_exception = null;

	/**
	 * Temporary error handler during unserialization
	 *
	 * @param integer $errno
	 * @param string $errstr
	 */
	public static function _unserialize_handler($errno, $errstr): void {
		self::$unserialize_exception = new Exception_Syntax($errstr, [], $errno);
	}

	/**
	 * Safe unserialize function.
	 *
	 * NOT THREAD SAFE!
	 *
	 * @param string $serialized
	 * @return mixed
	 * @throws Exception_Syntax
	 */
	public static function unserialize($serialized) {
		self::$unserialize_exception = null;
		set_error_handler([
			__CLASS__,
			'_unserialize_handler',
		]);
		$original = unserialize($serialized);
		restore_error_handler();
		if (self::$unserialize_exception) {
			$exception = self::$unserialize_exception;
			self::$unserialize_exception = null;

			throw $exception;
		}
		return $original;
	}

	/**
	 * Test PHP for presence of various features
	 *
	 * @param mixed $features
	 * @param boolean $die
	 *            Die if features aren't present
	 * @return mixed
	 */
	public static function requires($features, $die = false) {
		$features = to_list($features);
		$results = [];
		$errors = [];
		foreach ($features as $feature) {
			switch ($feature) {
				case "pcntl":
					$results[$feature] = $result = function_exists('pcntl_exec');
					if (!$result) {
						$errors[] = map("Need pcntl extensions for PHP\nphp.ini at {0}\n", [get_cfg_var('cfg_file_path')]);
					}

					break;
				case "time_limits":
					$results[$feature] = $result = !to_bool(ini_get('safe_mode'));
					if (!$result) {
						$errors[] = map("PHP safe mode prevents removing time limits on pages\nphp.ini at {0}\n", [get_cfg_var('safe_mode')]);
					}

					break;
				case "posix":
					$results[$feature] = $result = function_exists('posix_getpid');
					if (!$result) {
						$errors[] = "Need POSIX extensions to PHP (posix_getpid)";
					}

					break;
				default:
					$results[$feature] = $result = false;
					$errors[] = "Unknown feature \"$feature\"";

					break;
			}
		}
		if (count($errors) > 0) {
			if ($die) {
				die(implode("\n", $errors));
			}
		}
		if (count($features) === 1) {
			return $result;
		}
		return $results;
	}

	/**
	 * Set a PHP feature. Useful when you're a moron and can't remember the ini file settings names.
	 *
	 * @param string $feature
	 * @param mixed $value
	 *            Value to set it to
	 *
	 * @return boolean True if successful
	 */
	public static function feature($feature, $value) {
		$feature = strtolower($feature);
		switch ($feature) {
			case self::FEATURE_TIME_LIMIT:
				$old_value = ini_get('max_execution_time');
				set_time_limit(intval($value));
				return $old_value;
			case self::FEATURE_MEMORY_LIMIT:
				$old_value = to_bytes(ini_get('memory_limit'));
				ini_set('memory_limit', to_bytes($value));
				return $old_value;
			default:
				throw new Exception_Unimplemented("No such feature {feature}", compact("feature"));
		}
	}

	/**
	 * Constant to set the current script time limit (applies to web server only)
	 *
	 * @see PHP::feature
	 * @var string
	 */
	public const FEATURE_TIME_LIMIT = 'time_limit';

	/**
	 * Constant to set the current script memory limit. Takes an integer, or a string compatible with to_bytes
	 *
	 * @see to_bytes
	 * @see PHP::feature
	 * @var string
	 */
	public const FEATURE_MEMORY_LIMIT = 'memory_limit';

	/**
	 * Used with FEATURE_MEMORY_LIMIT to set limit to unlimited. Use with caution.
	 *
	 * @var integer`
	 */
	public const MEMORY_LIMIT_UNLIMITED = -1;

	/**
	 * Is this a valid function name, syntactically?
	 *
	 * @param string $func
	 * @return boolean
	 */
	public static function valid_function($func) {
		return self::clean_function($func) === $func;
	}

	/**
	 * Is this a valid class name, syntactically?
	 *
	 * @param string $class
	 * @return boolean
	 */
	public static function valid_class($class) {
		return self::clean_function($class) === $class;
	}

	/**
	 * Convert a string into a valid PHP function name.
	 * Useful for cleaning hooks generated automatically or
	 * from user input.
	 *
	 * @param string $func
	 *            String to clean
	 * @return string
	 */
	public static function clean_function($func) {
		return preg_replace("/[^a-zA-Z0-9_]/", '_', $func);
	}

	/**
	 * Convert a string into a valid PHP class name.
	 *
	 * @param string $func
	 *            String to clean
	 * @return string
	 */
	public static function clean_class($func) {
		return preg_replace("/[^a-zA-Z0-9_\\\\]/", '_', $func);
	}

	/**
	 * Convert a value automatically into a native PHP type
	 *
	 * @param mixed $value
	 * @return mixed
	 */

	/**
	 * Convert a value automatically into a native PHP type
	 *
	 * @param mixed $value
	 * @param boolean $throw Throw an Exception_Parse error when value is invalid JSON. Defaults to true.
	 * @return mixed
	 */
	public static function autotype(mixed $value, bool $throw = true): mixed {
		if (is_array($value)) {
			foreach ($value as $k => $v) {
				$value[$k] = self::autotype($v);
			}
			return $value;
		}
		if (is_bool($value)) {
			return to_bool($value);
		}
		if (is_numeric($value)) {
			return to_double($value);
		}
		if (!is_string($value)) {
			return $value;
		}
		if ($value === 'null') {
			return null;
		}
		// Convert numeric types first, then boolean
		if (preg_match('/^[0-9]+$/', $value)) {
			return to_integer($value);
		}
		if (unquote($value, '{}[]\'\'""') !== $value) {
			try {
				return JSON::decode($value, true);
			} catch (Exception_Parse $e) {
				if ($throw) {
					throw $e;
				}
				return $value;
			}
		}
		return $value;
	}

	/**
	 * Given a class with a namespace, return just the class portion.
	 *
	 * e.g. PHP::parse_class("zesk\Dude") === "Dude"
	 *
	 * As of November 2018, does not appera that PHP have a native function which does this.
	 *
	 * @param string $class
	 * @return string
	 */
	#[Pure]
	public static function parse_class(string $class): string {
		[$_, $cl] = self::parse_namespace_class($class);
		return $cl;
	}

	/**
	 * Given a class with a namespace, return just the class portion.
	 *
	 * e.g. PHP::parse_class("zesk\Dude") === "Dude"
	 *
	 * @param string $class
	 * @return string
	 */
	public static function parse_namespace(string $class): string {
		[$ns] = self::parse_namespace_class($class);
		return $ns;
	}

	/**
	 * Given a class with a namespace, return a two-element list with the namespace first and the class second.
	 *
	 * Returns "" for namespace if no namespace
	 *
	 * @param string $class
	 * @return string[]
	 */
	#[Pure]
	public static function parse_namespace_class(string $class): array {
		return pairr($class, "\\", "", $class);
	}

	/**
	 *
	 * @param string $message
	 * @param array $arguments
	 */
	public static function log(string $message, array $arguments = []): void {
		error_log(map($message, $arguments));
	}
}
