<?php
/**
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2005, Market Acumen, Inc.
 * @package zesk
 * @subpackage system
 */
namespace zesk;

/**
 * The Options object is universally used to tag various objects in the system with optional
 * configuration settings and values.
 *
 * They are also used to communicate at times between objects
 * in the system where the type of object varies.
 *
 * Options are essentially a case-insensitive associative array with a set of calls to make
 * it easy to convert and modify the type of information contained.
 *
 * They are intended to contain scalar and array values primarily, as they are used heavily
 * in specifying options within template files, configuration files, class definitions, widget
 * definitions, and are required to be easily converted into string values for serialization.
 *
 * Most objects in the system, specifically, View, Control, Model, and Template
 * are derived from Options to allow any object in the system to be tagged and have its
 * default behavior modified via configuration settings (globals) in the application.
 *
 * @package zesk
 * @subpackage system
 */
class Options implements \ArrayAccess {

	/**
	 * Character used for space
	 * @var string
	 */
	const option_space = "_";

	/**
	 * An associative array of lower-case strings pointing to mixed values. $options should
	 * always be serializable so it should only contain primitive scalar types.
	 *
	 * Keys typically use _ for dash or space
	 *
	 * @var array
	 */
	protected $options = array();

	/**
	 * Create a Options object.
	 *
	 * @return Options
	 * @param array $options An array of options to set up, or false for no options.
	 */
	function __construct(array $options = array()) {
		if (count($this->options) > 0) {
			$this->options = self::_option_key($this->options);
		}
		$this->options = self::_option_key($options) + $this->options;
	}

	/**
	 * @ignore
	 */
	function __sleep() {
		return array(
			"options"
		);
	}

	/**
	 * options_exclude
	 *
	 * @param mixed $remove A ';' delimited string or an array of options to remove
	 * @return array The options for this object, minus any listed in $remove
	 */
	function options_exclude($remove = false) {
		$arr = $this->options;
		if (!is_array($arr)) {
			return $arr;
		}
		if (is_string($remove)) {
			$remove = explode(";", $remove);
		}
		if (is_array($remove)) {
			foreach ($remove as $k) {
				if (array_key_exists($k, $arr)) {
					unset($arr[$k]);
				}
			}
		}
		return $arr;
	}

	/**
	 * options_include
	 *
	 * @return array A array of options for this object. Keys are all lowercase.
	 */
	function options_include($selected = null) {
		if ($selected === null) {
			return $this->options;
		}
		$result = array();
		foreach (to_list($selected) as $k) {
			$k = self::_option_key($k);
			if (isset($this->options[$k])) {
				$result[$k] = $this->options[$k];
			}
		}
		return $result;
	}

	/**x
	 * @return array A list of all of the keys in this Options object.
	 */
	function option_keys() {
		return array_keys($this->options);
	}

	/**
	 * Checks an option to see if it is set and optionally if it has a non-empty value.
	 *
	 * @param string $name The name of the option key to check
	 * @param bool $check_empty True if you want to ensure that the value is non-empty (e.g. not null, 0, "0", "", array(), or false)
	 * @see empty()
	 * @return bool
	 */
	function has_option($name, $check_empty = false) {
		if (is_array($name)) {
			foreach ($name as $k) {
				if ($this->has_option($k, $check_empty)) {
					return true;
				}
			}
			return false;
		}
		$name = self::_option_key($name);
		if (!array_key_exists($name, $this->options)) {
			return false;
		}
		if (!$check_empty) {
			return true;
		}
		return !empty($this->options[$name]);
	}

	/**
	 * Set an option for this object, or remove it.
	 *
	 * This function may be called in one of three ways, either with a name and a value, or with an array.
	 *
	 * With a name and a value:
	 * <code>
	 * $widget->set_option("Value", $value);
	 * $widget->set_option("Format", "{Name} ({ID})");
	 * </code>
	 *
	 * With an array:
	 * <code>
	 * $arr = array("Value" => $value, "Format" => "{Name} ({ID})");
	 * $widget->set_option($arr);
	 * </code>
	 *
	 * @param array|string $mixed An array of name/value pairs, or a string of the option name to set
	 * @param string $value The value of the option to set. If $mixed is an array, this parameter is ignored.
	 * @param bool $overwrite Whether to overwrite a value if it already exists. When true, the values are always written.
	 * When false, values are written only if the current option does not have a value. Default is true. This parameter is useful when you wish to
	 * set default options for an object only if the user has not set them already.
	 * @return void
	 */
	function set_option($mixed, $value = null, $overwrite = true) {
		if (is_array($mixed)) {
			foreach ($mixed as $name => $value) {
				$name = self::_option_key($name);
				if ($overwrite || !array_key_exists($name, $this->options)) {
					if ($value === null) {
						unset($this->options[$name]);
					} else {
						$this->options[$name] = $value;
					}
				}
			}
		} else {
			$name = self::_option_key($mixed);
			if ($overwrite || !array_key_exists($name, $this->options)) {
				if ($value === null) {
					unset($this->options[$name]);
				} else {
					$this->options[$name] = $value;
				}
			}
		}
		return $this;
	}

	/**
	 * Converts a non-array option into an array, and appends a value to the end.
	 *
	 * Guarantees that future option($name) will return an array.
	 *
	 * @param string $mixed A string of the option name to convert and append.
	 * @param string $value The value to append to the end of the option's value array.
	 * @return Options
	 */
	function option_append_list($name, $value) {
		$name = self::_option_key($name);
		$current_value = avalue($this->options, $name);
		if (is_scalar($current_value) && $current_value !== null && $current_value !== false) {
			$this->options[$name] = array(
				$current_value
			);
		}
		$this->options[$name][] = $value;
		return $this;
	}

	/**
	 * Appends a variable to a option which is an array
	 *
	 * Guarantees that future option($name) will return an array.
	 *
	 * @param string $mixed A string of the option name to convert and append.
	 * @param string $value The value to append to the end of the option's value array.
	 * @return Options
	 */
	function option_append($name, $key, $value) {
		$this->options[self::_option_key($name)][$key] = $value;
		return $this;
	}

	/**
	 * Get an option, or multiple options for this object.
	 *
	 * This function may be called in one of four ways, with no parameters,
	 * with a name and a value, or with a list array("a, "b"), or an associative array("a" => "aval", "b" => "bval")
	 *
	 * With no options, it returns the list of all options set.
	 *
	 * With a name and a value:
	 * <code>
	 * $value = $widget->option("Value", $default_value);
	 * $values = $widget->option(array("Format", "{Name} ({ID})");
	 * </code>
	 *
	 * With a list array:
	 * <code>
	 * list($name, $format) = $widget->option(array("Name", "Format"));
	 * </code>
	 *
	 * With an associative array:
	 * <code>
	 * $values = $widget->option(array("Name" => "NoName", "Format" => "{Name}"));
	 * echo $values["Name"];
	 * </code>
	 *
	 * The benefit of this flexibility that you can create array structures of results
	 * based on what you pass in, e.g.
	 *
	 * <code>
	 * $option =
	 * array(
	 * "DisplayGroup" => array("Format" => "{Name}", "Value" => "Not set."),
	 * "Buttons" => array("EditButton" => false, "ViewButton" => false)
	 * );
	 * $result = $widget->option($option);
	 * </code>
	 * "DisplayGroup" and "Buttons" are not actually options, but they will
	 * recursively do option on "Format", "Name", "EditButton", and "ViewButton".
	 * The above code is equivalent to:
	 *
	 * <code>
	 * $result =
	 * array(
	 * "DisplayGroup" =>
	 * array(
	 * "Format" => $widget->option("Format", "{Name}"),
	 * "Value" => $widget->option("Value", "Not set."),
	 * ),
	 * "Buttons" =>
	 * array(
	 * "EditButton" => $widget->option("EditButton", false),
	 * "ViewButton" => $widget->option("ViewButton", false),
	 * ),
	 * );
	 * </code>
	 *
	 * The above is handy when outputting options to templates.
	 *
	 * @param array|string $mixed An array of name/value pairs, or a string of the option name to set
	 * @param string $value The value of the option to set. If $mixed is an array, this parameter is ignored.
	 * @param bool $overwrite Whether to overwrite a value if it already exists. When true, the values are always written.
	 * When false, values are written only if the current option does not have a value. Default is true. This parameter is useful when you wish to
	 * set default options for an object only if the user has not set them already.
	 * @return mixed
	 */
	function option($name = null, $default = null) {
		if ($name === null) {
			return $this->options;
		}
		if (is_array($name)) {
			$result = false;
			foreach ($name as $n => $v) {
				if (is_numeric($n)) {
					$result[] = $this->option($v, $default);
				} else {
					$result[$n] = $this->option($n, $v);
				}
			}
			return $result;
		}
		$name = self::_option_key($name);
		if (array_key_exists($name, $this->options)) {
			return $this->options[$name];
		}
		return $default;
	}

	/**
	 * Returns first option found
	 *
	 * @param mixed $name An option to get, or an array of option => default values
	 * @param mixed $default The default value to return of the option is not found
	 * @return mixed The retrieved option
	 */
	function first_option($names, $default = null) {
		$names = to_list($names);
		foreach ($names as $name) {
			$name = self::_option_key($name);
			$value = avalue($this->options, $name);
			if ($value !== null) {
				return $value;
			}
		}
		return $default;
	}

	/**
	 * Generate an option key from an option name
	 * @param string $name
	 * @return string normalized key name
	 */
	final static protected function _option_key($name) {
		if (is_array($name)) {
			$result = array();
			foreach ($name as $k => $v) {
				$result[self::_option_key($k)] = $v;
			}
			return $result;
		}
		return strtolower(strtr(trim($name), array(
			"-" => self::option_space,
			"_" => self::option_space,
			" " => self::option_space
		)));
	}

	/**
	 * Get an option as a boolean.
	 *
	 * @param string $name Option to retrieve as a boolean value.
	 */
	function option_bool($name, $default = false) {
		return to_bool(avalue($this->options, self::_option_key($name), $default), $default);
	}

	/**
	 * Get an option as an integer value.
	 *
	 * @param string $name Option to retrieve as a integer value.
	 * @param mixed $default Value to return if this option is not set or is not is_numeric.
	 * @return integer The integer value of the option, or $default. The default value is passed back without modification.
	 * @see is_numeric()
	 */
	function option_integer($name, $default = null) {
		return to_integer(avalue($this->options, self::_option_key($name)), $default);
	}

	/**
	 * Get an option as a numeric (floating-point or integer) value.
	 *
	 * @param string $name Option to retrieve as a real value.
	 * @param mixed $default Value to return if this option is not set or is not is_numeric.
	 * @return float The real value of the option, or $default. The default value is passed back without modification.
	 * @see is_numeric()
	 */
	function option_double($name, $default = null) {
		$name = self::_option_key($name);
		if (isset($this->options[$name]) && is_numeric($this->options[$name])) {
			return doubleval($this->options[$name]);
		}
		return $default;
	}

	/**
	 * Get an option as an array.
	 *
	 * @param string $name Option to retrieve as an array value.
	 * @param mixed $default Value to return if this option is not set or is not an array.
	 * @return array The real value of the option, or $default. The default value is passed back without modification.
	 * @see is_array()
	 */
	function option_array($name, $default = array()) {
		$name = self::_option_key($name);
		if (isset($this->options[$name]) && is_array($this->options[$name])) {
			return $this->options[$name];
		}
		return $default;
	}

	/**
	 * Get an option as a tree-path
	 * @param string $name Option to retrieve as an array value.
	 * @param mixed $default Value to return if this option is not set or is not an array.
	 * @return array The real value of the option, or $default. The default value is passed back without modification.
	 * @see is_array()
	 */
	function option_path($path, $default = null, $separator = ".") {
		$path = to_list($path, array(), $separator);
		$node = $this->options;
		foreach ($path as $p) {
			$p = self::_option_key($p);
			if (!is_array($node)) {
				return $default;
			}
			if (array_key_exists($p, $node)) {
				$node = $node[$p];
			} else {
				return $default;
			}
		}
		return $node;
	}

	/**
	 * Set an option as a tree-path
	 *
	 * @param string $path
	 * @param mixed $value
	 * @param string $separator String to separate path segments
	 * @return Options
	 */
	function set_option_path($path, $value = null, $separator = ".") {
		$path = self::_option_key($path);
		apath_set($this->options, $path, $value, $separator);
		return $this;
	}

	/**
	 * Get an option as a date formatted as "YYYY-MM-DD".
	 *
	 * @param string $name Option to retrieve as an array value.
	 * @param mixed $default Value to return if this option is not set or is not an array.
	 * @return string The date value of the option, or $default. The default value is passed back without modification.
	 * @see is_date
	 */
	function option_date($name, $default = null) {
		$name = self::_option_key($name);
		if (isset($this->options[$name]) && is_date($this->options[$name])) {
			return $this->options[$name];
		}
		return $default;
	}

	/**
	 * Get an option as a zero-indexed array, or list array("Bob","Rajiv","Franz")
	 *
	 * @param string $name Option to retrieve as an array value.
	 * @param mixed $default Value to return if this option is not set, is not a string or is not an array.
	 * @param string $delimiter If the value is the string, the delimiter used to convert to an array using {@link explode() explode()}.
	 * @return array The string exploded by $delimiter, or the array value. The default value is passed back without modification.
	 * @see is_array(), explode()
	 */
	function option_list($name, $default = array(), $delimiter = ";") {
		$name = self::_option_key($name);
		if (!isset($this->options[$name])) {
			return to_list($default, array(), $delimiter);
		}
		return to_list($this->options[$name], $default, $delimiter);
	}

	/**
	 * Getter/setter interface to make access easy from subclasses
	 *
	 * @param string $name
	 * @param string $set
	 * @return mixed|Options
	 */
	protected function _option_get_set($name, $set = null) {
		return $set === null ? $this->option($name) : $this->set_option($name, $set);
	}
	/**
	 * Handle options like members
	 *
	 * @param string $key
	 * @return mixed
	 */
	function __get($key) {
		return avalue($this->options, self::_option_key($key));
	}

	/**
	 * Handle options like members
	 *
	 * @param string $key
	 * @return mixed
	 */
	function __set($key, $value) {
		$this->options[self::_option_key($key)] = $value;
		return $this;
	}

	/**
	 * Convert to string
	 *
	 * @return string
	 */
	function __toString() {
		return PHP::dump($this->options);
	}

	/**
	 * @see ArrayAccess::offsetExists
	 * @param offset
	 */
	public function offsetExists($offset) {
		return array_key_exists(self::_option_key($offset), $this->options);
	}

	/**
	 * @see ArrayAccess::offsetGet
	 * @param offset
	 */
	public function offsetGet($offset) {
		return avalue($this->options, self::_option_key($offset));
	}

	/**
	 * @see ArrayAccess::offsetSet
	 * @param offset
	 * @param value
	 */
	public function offsetSet($offset, $value) {
		$this->options[self::_option_key($offset)] = $value;
	}

	/**
	 * @see ArrayAccess::offsetUnset
	 * @param offset
	 */
	public function offsetUnset($offset) {
		unset($this->options[self::_option_key($offset)]);
	}
}
