<?php
declare(strict_types=1);
/**
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2005, Market Acumen, Inc.
 * @package zesk
 * @subpackage system
 */

namespace zesk;

use JetBrains\PhpStorm\Pure;

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
 * default behavior modified via configuration settings (globals) in the application, typically
 * through the Hookable subclass.
 *
 * @see Hookable
 * @package zesk
 * @subpackage system
 */
class Options implements \ArrayAccess {
	/**
	 * Character used for space
	 * @var string
	 */
	public const OPTION_SPACE = '_';

	/**
	 * An associative array of lower-case strings pointing to mixed values. $options should
	 * always be serializable so it should only contain primitive scalar types.
	 *
	 * Keys typically use _ for dash or space
	 *
	 * @var array
	 */
	protected array $options = [];

	/**
	 * Create a Options object.
	 *
	 * @param array $options An array of options to set up, or false for no options.
	 * @return Options
	 */
	public function __construct(array $options = []) {
		if (count($this->options) > 0) {
			$this->options = self::cleanOptionKeys($this->options);
		}
		$this->options = self::cleanOptionKeys($options) + $this->options;
	}

	/**
	 * @ignore
	 */
	public function __sleep() {
		return [
			'options',
		];
	}

	/**
	 * Return a list of options, optionally filtering by key name
	 *
	 * @param array|null $selected
	 * @return array
	 */
	#[Pure]
	public function options(array $selected = null): array {
		if ($selected === null) {
			return $this->options;
		}
		$result = [];
		foreach ($selected as $name => $default) {
			if (is_numeric($name)) {
				$result[] = $this->options[self::_optionKey($default)] ?? null;
			} else {
				$result[$name] = $this->options[self::_optionKey($name)] ?? $default;
			}
		}
		return $result;
	}

	/**
	 * @return array A list of all of the keys in this Options object.
	 */
	public function optionKeys(): array {
		return array_keys($this->options);
	}

	/**
	 * Checks an option to see if it is set and optionally if it has a non-empty value.
	 *
	 * @param string $name The name of the option key to check
	 * @param bool $check_empty True if you want to ensure that the value is non-empty (e.g. not null, 0, "0", "", array(), or false)
	 * @return bool
	 * @see empty()
	 */
	#[Pure]
	public function hasAnyOption(iterable $name, bool $check_empty = false): bool {
		foreach ($name as $k) {
			if ($this->hasOption($k, $check_empty)) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Checks an option to see if it is set and optionally if it has a non-empty value.
	 *
	 * @param string $name The name of the option key to check
	 * @param bool $check_empty True if you want to ensure that the value is non-empty (e.g. not null, 0, "0", "", array(), or false)
	 * @return bool
	 * @see empty()
	 */
	#[Pure]
	public function hasOption(string $name, bool $check_empty = false): bool {
		$name = self::_optionKey($name);
		if (!array_key_exists($name, $this->options)) {
			return false;
		}
		return !$check_empty || !empty($this->options[$name]);
	}

	/**
	 * Set an option for this object, or remove it.
	 *
	 * This function may be called in one of three ways, either with a name and a value, or with an array.
	 *
	 * With a name and a value:
	 * <code>
	 * $widget->setOption("Value", $value);
	 * $widget->setOption("Format", "{Name} ({ID})");
	 * </code>
	 *
	 * @param string $mixed An array of name/value pairs, or a string of the option name to set
	 * @param string $value The value of the option to set. If $mixed is an array, this parameter is ignored.
	 * @param bool $overwrite Whether to overwrite a value if it already exists. When true, the values are always written.
	 * When false, values are written only if the current option does not have a value. Default is true. This parameter is useful when you wish to
	 * set default options for an object only if the user has not set them already.
	 * @return self
	 */
	public function setOption(string $mixed, mixed $value, bool $overwrite = true): self {
		$name = self::_optionKey($mixed);
		if ($overwrite || !array_key_exists($name, $this->options)) {
			if ($value === null) {
				unset($this->options[$name]);
			} else {
				$this->options[$name] = $value;
			}
		}
		return $this;
	}

	/**
	 * @param array $values
	 * @param bool $overwrite
	 * @return $this
	 */
	public function setOptions(array $values, bool $overwrite = true): self {
		foreach ($values as $key => $value) {
			$this->setOption($key, $value, $overwrite);
		}
		return $this;
	}

	/**
	 * @param string $name
	 * @return $this
	 */
	public function clearOption(string $name): self {
		unset($this->options[self::_optionKey($name)]);
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
	public function appendOptionList(string $name, mixed $value): self {
		$name = self::_optionKey($name);
		$current_value = $this->options[$name] ?? null;
		if (is_scalar($current_value) && $current_value !== null && $current_value !== false) {
			$this->options[$name] = [
				$current_value,
			];
		}
		$this->options[$name][] = $value;
		return $this;
	}

	/**
	 * Get an option
	 *
	 * @param string $name
	 * @param mixed|null $default
	 * @return mixed
	 */
	#[Pure]
	public function option(string $name, mixed $default = null): mixed {
		return $this->options[self::_optionKey($name)] ?? $default;
	}

	/**
	 * Returns first option found
	 *
	 * @param mixed $name An option to get, or an array of option => default values
	 * @param mixed $default The default value to return of the option is not found
	 * @return mixed The retrieved option
	 */
	#[Pure]
	public function firstOption(iterable $names, mixed $default = null): mixed {
		foreach ($names as $name) {
			$name = self::_optionKey($name);
			if (isset($this->options[$name])) {
				return $this->options[$name];
			}
		}
		return $default;
	}

	/**
	 * Generate an option key from an option name
	 * @param string $name
	 * @return string normalized key name
	 */
	final protected static function _optionKey(string|int $name): string {
		return strtolower(strtr(trim(strval($name)), [
			'-' => self::OPTION_SPACE,
			'_' => self::OPTION_SPACE,
			' ' => self::OPTION_SPACE,
		]));
	}

	/**
	 * Clean option keys
	 *
	 * @param array $options
	 * @param array $target
	 * @return array
	 */
	#[Pure]
	final protected static function cleanOptionKeys(array $options, array $target = []): array {
		foreach ($options as $k => $v) {
			$target[self::_optionKey($k)] = $v;
		}
		return $target;
	}

	/**
	 * Get an option as a boolean.
	 *
	 * @param string $name Option to retrieve as a boolean value.
	 * @param bool $default
	 * @return bool
	 */
	#[Pure]
	public function optionBool(string $name, bool $default = false): bool {
		return to_bool($this->options[self::_optionKey($name)] ?? $default);
	}

	/**
	 * @param string $name
	 * @param int $default
	 * @return int
	 */
	#[Pure]
	public function optionInt(string $name, int $default = 0): int {
		return to_integer($this->options[self::_optionKey($name)] ?? $default);
	}

	/**
	 * Get an option as a numeric (floating-point or integer) value.
	 *
	 * @param string $name Option to retrieve as a real value.
	 * @param mixed $default Value to return if this option is not set or is not is_numeric.
	 * @return float The real value of the option, or $default. The default value is passed back without modification.
	 * @see is_numeric()
	 */
	#[Pure]
	public function optionFloat(string $name, float $default = 0): float {
		$name = self::_optionKey($name);
		if (isset($this->options[$name]) && is_numeric($this->options[$name])) {
			return floatval($this->options[$name]);
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
	#[Pure]
	public function optionArray(string $name, array $default = []): array {
		$name = self::_optionKey($name);
		if (isset($this->options[$name]) && is_array($this->options[$name])) {
			return $this->options[$name];
		}
		return $default;
	}

	/**
	 * Get an option as a tree-path
	 * @param string $name Option to retrieve as an array value.
	 * @param mixed $default Value to return if this option is not set or is not an array.
	 * @return mixed The real value of the option, or $default. The default value is passed back without modification.
	 * @see is_array()
	 */
	public function optionPath(array $path, mixed $default = null): mixed {
		if (count($path) === 0) {
			return $default;
		}
		$path[0] = self::_optionKey($path[0]);
		return apath($this->options, $path, $default);
	}

	/**
	 * Set an option as a tree-path
	 *
	 * @param string $path
	 * @param mixed $value
	 * @param string $separator String to separate path segments
	 * @return Options
	 */
	public function setOptionPath(array $path, mixed $value): self {
		$path[0] = self::_optionKey($path[0]);
		apath_set($this->options, $path, $value);
		return $this;
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
	#[Pure]
	public function optionIterable(string $name, ?iterable $default = [], string $delimiter = ';'): iterable {
		$name = self::_optionKey($name);
		if (!isset($this->options[$name])) {
			return to_iterable($default, [], $delimiter);
		}
		return to_iterable($this->options[$name], $default, $delimiter);
	}

	/**
	 * Handle options like members
	 *
	 * @param string $key
	 * @return boolean
	 */
	#[Pure]
	public function __isset(string $key): bool {
		return isset($this->options[self::_optionKey($key)]);
	}

	/**
	 * Handle options like members
	 *
	 * @param string $key
	 * @return mixed
	 */
	#[Pure]
	public function __get(string $key): mixed {
		return $this->options[self::_optionKey($key)] ?? null;
	}

	/**
	 * Handle options like members
	 *
	 * @param string $key
	 * @return self
	 */
	public function __set(string $key, mixed $value): void {
		$this->options[self::_optionKey($key)] = $value;
	}

	/**
	 * Convert to string
	 *
	 * @return string
	 */
	public function __toString() {
		return PHP::dump($this->options);
	}

	/**
	 * @param offset
	 * @return bool
	 * @see ArrayAccess::offsetExists
	 */
	#[Pure]
	public function offsetExists($offset): bool {
		return array_key_exists(self::_optionKey($offset), $this->options);
	}

	/**
	 * @param offset
	 * @return int
	 * @see ArrayAccess::offsetGet
	 */
	public function offsetGet($offset): int {
		return avalue($this->options, self::_optionKey($offset));
	}

	/**
	 * @param offset
	 * @param value
	 * @see ArrayAccess::offsetSet
	 */
	public function offsetSet($offset, $value): void {
		$this->options[self::_optionKey($offset)] = $value;
	}

	/**
	 * @param offset
	 * @return void
	 * @see ArrayAccess::offsetUnset
	 */
	public function offsetUnset($offset): void {
		unset($this->options[self::_optionKey($offset)]);
	}

	/* ************************************************************************
	 *      _                               _           _
	 *   __| | ___ _ __  _ __ ___  ___ __ _| |_ ___  __| |
	 *  / _` |/ _ \ '_ \| '__/ _ \/ __/ _` | __/ _ \/ _` |
	 * | (_| |  __/ |_) | | |  __/ (_| (_| | ||  __/ (_| |
	 *  \__,_|\___| .__/|_|  \___|\___\__,_|\__\___|\__,_|
	 *            |_|
	 */

	/**
	 * Get an option as a zero-indexed array, or list array("Bob","Rajiv","Franz")
	 *
	 * @param string $name Option to retrieve as an array value.
	 * @param mixed $default Value to return if this option is not set, is not a string or is not an array.
	 * @param string $delimiter If the value is the string, the delimiter used to convert to an array using {@link explode() explode()}.
	 * @return array The string exploded by $delimiter, or the array value. The default value is passed back without modification.
	 * @see is_array(), explode()
	 * @deprecated 2022-01
	 */
	public function option_list(string $name, ?iterable $default = [], string $delimiter = ';'): iterable {
		return $this->optionIterable($name, $default, $delimiter);
	}

	/**
	 * Get an option as a date formatted as "YYYY-MM-DD".
	 *
	 * @param string $name Option to retrieve as an array value.
	 * @param mixed $default Value to return if this option is not set or is not an array.
	 * @return string The date value of the option, or $default. The default value is passed back without modification.
	 * @deprecated 2022-01 Who uses this?
	 * @see is_date
	 */
	public function option_date($name, $default = null) {
		$name = self::_optionKey($name);
		if (isset($this->options[$name]) && is_date($this->options[$name])) {
			return $this->options[$name];
		}
		return $default;
	}

	/**
	 * Getter/setter interface to make access easy from subclasses.
	 * 2022 - This pattern should probably go away
	 *
	 * @param string $name
	 * @param string $set
	 * @return mixed|Options
	 * @deprecated 2022-01
	 */
	protected function _option_get_set(string $name, mixed $set = null): mixed {
		return $set === null ? $this->option($name) : $this->setOption($name, $set);
	}

	/**
	 * Get an option as an integer value.
	 *
	 * @param string $name Option to retrieve as a integer value.
	 * @param mixed $default Value to return if this option is not set or is not is_numeric.
	 * @return integer The integer value of the option, or $default. The default value is passed back without modification.
	 * @see is_numeric()
	 * @deprecated 2022-01
	 */
	public function option_integer(string $name, int $default = 0) {
		return $this->optionInt($name, $default);
	}

	/**
	 * Get an option as a boolean.
	 * @param string $name Option to retrieve as a boolean value.
	 * @param bool $default
	 * @return bool
	 * @deprecated 2022-01
	 */
	public function option_bool(string $name, bool $default = false): bool {
		return $this->optionBool($name, $default);
	}

	/**
	 * Returns first option found
	 *
	 * @param mixed $name An option to get, or an array of option => default values
	 * @param mixed $default The default value to return of the option is not found
	 * @return mixed The retrieved option
	 * @deprecated 2022-01
	 */
	public function first_option(iterable $names, mixed $default = null): mixed {
		return $this->firstOption($names, $default);
	}

	/**
	 * Checks an option to see if it is set and optionally if it has a non-empty value.
	 *
	 * @param string $name The name of the option key to check
	 * @param bool $check_empty True if you want to ensure that the value is non-empty (e.g. not null, 0, "0", "", array(), or false)
	 * @return bool
	 * @deprecated 2022-01
	 * @see empty()
	 */
	public function has_option(array|string $name, bool $check_empty = false): bool {
		if (is_array($name)) {
			return $this->hasAnyOption($name, $check_empty);
		}
		return $this->hasOption($name, $check_empty);
	}

	/**
	 * Set an option for this object, or remove it.
	 *
	 * This function may be called in one of three ways, either with a name and a value, or with an array.
	 *
	 * With a name and a value:
	 * <code>
	 * $widget->setOption("Value", $value);
	 * $widget->setOption("Format", "{Name} ({ID})");
	 * </code>
	 *
	 * With an array:
	 * <code>
	 * $arr = array("Value" => $value, "Format" => "{Name} ({ID})");
	 * $widget->setOption($arr);
	 * </code>
	 *
	 * @param array|string $mixed An array of name/value pairs, or a string of the option name to set
	 * @param string $value The value of the option to set. If $mixed is an array, this parameter is ignored.
	 * @param bool $overwrite Whether to overwrite a value if it already exists. When true, the values are always written.
	 * When false, values are written only if the current option does not have a value. Default is true. This parameter is useful when you wish to
	 * set default options for an object only if the user has not set them already.
	 * @return self
	 * @deprecated 2022-01
	 */
	public function set_option(string|array $mixed, mixed $value = null, bool $overwrite = true): self {
		if (is_array($mixed)) {
			foreach ($mixed as $name => $value) {
				$this->setOption($name, $value, $overwrite);
			}
			return $this;
		} else {
			return $this->setOption($mixed, $value, $overwrite);
		}
	}

	/**
	 * @return array A list of all of the keys in this Options object.
	 * @deprecated 2022-01
	 */
	public function option_keys() {
		return $this->optionKeys();
	}

	/**
	 * Get an option as a numeric (floating-point or integer) value.
	 *
	 * @param string $name Option to retrieve as a real value.
	 * @param mixed $default Value to return if this option is not set or is not is_numeric.
	 * @return float The real value of the option, or $default. The default value is passed back without modification.
	 * @see is_numeric()
	 * @deprecated 2022-01
	 */
	#[Pure]
	public function option_double(string $name, float $default = 0): float {
		return $this->optionFloat($name, $default);
	}

	/**
	 * Get an option as an array.
	 *
	 * @param string $name Option to retrieve as an array value.
	 * @param mixed $default Value to return if this option is not set or is not an array.
	 * @return array The real value of the option, or $default. The default value is passed back without modification.
	 * @see is_array()
	 * @deprecated 2022-01
	 */
	#[Pure]
	public function option_array(string $name, array $default = []): array {
		return $this->optionArray($name, $default);
	}

	/**
	 * Get an option as a tree-path
	 * @param string $name Option to retrieve as an array value.
	 * @param mixed $default Value to return if this option is not set or is not an array.
	 * @return mixed The real value of the option, or $default. The default value is passed back without modification.
	 * @see is_array()
	 * @deprecated 2022-01
	 */
	public function option_path($path, $default = null, $separator = '.') {
		return $this->optionPath(to_list($path, [], $separator), $default);
	}

	/**
	 * Set an option as a tree-path
	 *
	 * @param string $path
	 * @param mixed $value
	 * @param string $separator String to separate path segments
	 * @return Options
	 */
	public function set_option_path(string|array $path, mixed $value = null, string $separator = '.') {
		return $this->setOptionPath(to_list($path, [], $separator), $value);
	}

	/**
	 * Generate an option key from an option name
	 * @param string $name
	 * @return string normalized key name
	 * @deprecated 2022-01
	 */
	final protected static function _option_key(string|int $name): string {
		return self::_optionKey($name);
	}

	/**
	 * Converts a non-array option into an array, and appends a value to the end.
	 *
	 * Guarantees that future option($name) will return an array.
	 *
	 * @param string $mixed A string of the option name to convert and append.
	 * @param string $value The value to append to the end of the option's value array.
	 * @return Options
	 * @deprecated 2022-01
	 */
	public function option_append_list(string $name, mixed $value) {
		return $this->appendOptionList($name, $value);
	}

	/**
	 * options_include
	 *
	 * @return array A array of options for this object. Keys are all lowercase.
	 * @deprecated 2022-01
	 */
	public function options_include($selected = null): array {
		if ($selected === null) {
			return $this->options;
		}
		return $this->options(to_list($selected));
	}
}
