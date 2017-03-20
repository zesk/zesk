<?php
/**
 * 
 */
namespace zesk;

use \ReflectionObject;

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
	 * Set or get the current settings
	 *
	 * @param array $set        	
	 * @return Ambigous <php, array>
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
	 * @return php|array
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
			$items = array();
			if (arr::is_list($x)) {
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
		} else if (is_string($x)) {
			return '"' . addcslashes($x, "'\0..\37") . '"';
		} else if (is_integer($x)) {
			return "$x";
		} else if ($x === null) {
			return "null";
		} else if (is_bool($x)) {
			return $x ? "true" : "false";
		} else if (is_object($x)) {
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
	static $unserialize_exception = null;
	
	/**
	 * Temporary error handler during unserialization
	 *
	 * @param integer $errno        	
	 * @param string $errstr        	
	 */
	public static function _unserialize_handler($errno, $errstr) {
		self::$unserialize_exception = new Exception_Syntax($errstr, array(), $errno);
	}
	
	/**
	 * Safe unserialize function
	 *
	 * @param string $serialized        	
	 * @throws Exception
	 * @return mixed
	 */
	public static function unserialize($serialized) {
		self::$unserialize_exception = null;
		set_error_handler(array(
			__CLASS__,
			'_unserialize_handler'
		));
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
	 *        	Die if features aren't present
	 * @return mixed
	 */
	public static function requires($features, $die = false) {
		$features = to_list($features);
		$results = array();
		$errors = array();
		foreach ($features as $feature) {
			switch ($feature) {
				case "pcntl":
					$results[$feature] = $result = function_exists('pcntl_exec');
					if (!$result) {
						$errors[] = __("Need pcntl extensions for PHP\nphp.ini at {0}\n", get_cfg_var('cfg_file_path'));
					}
					break;
				case "time_limits":
					$results[$feature] = $result = !to_bool(ini_get('safe_mode'));
					if (!$result) {
						$errors[] = __("PHP safe mode prevents removing time limits on pages\nphp.ini at {0}\n", get_cfg_var('safe_mode'));
					}
					break;
				case "posix":
					$results[$feature] = $result = function_exists('posix_getpid');
					if (!$result) {
						$errors[] = __("Need POSIX extensions to PHP (posix_getpid)");
					}
					break;
				default :
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
	 *        	Value to set it to
	 *        	
	 * @return boolean True if successful
	 */
	public static function feature($feature, $value) {
		$feature = strtolower($feature);
		switch ($feature) {
			case "time_limit":
				set_time_limit(intval($value));
				return true;
			default :
				throw new Exception_Unimplemented("No such feature {feature}", compact("feature"));
		}
		return null;
	}
	
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
	 *        	String to clean
	 * @return string
	 */
	public static function clean_function($func) {
		return preg_replace("/[^a-zA-Z0-9_]/", '_', $func);
	}
	
	/**
	 * Convert a string into a valid PHP class name.
	 *
	 * @param string $func
	 *        	String to clean
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
	public static function autotype($value) {
		if (is_array($value)) {
			foreach ($value as $k => $v) {
				$value[$k] = self::autotype($v);
			}
			return $value;
		}
		if (!is_string($value)) {
			return $value;
		}
		// Convert numeric types first, then boolean
		if (preg_match('/^[0-9]+$/', $value)) {
			return to_integer($value);
		}
		if (is_numeric($value)) {
			return to_double($value);
		}
		if (($b = to_bool($value, null)) !== null) {
			return $b;
		}
		if ($value === 'null') {
			return null;
		}
		if (unquote($value, '{}[]\'\'""') !== $value) {
			return JSON::decode($value, true);
		}
		return $value;
	}
}

