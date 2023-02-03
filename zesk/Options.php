<?php
declare(strict_types=1);
/**
 * @author kent
 * @copyright Copyright &copy; 2023, Market Acumen, Inc.
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
 * default behavior modified via configuration settings (globals) in the application, typically
 * through the Hookable subclass.
 *
 * @see Hookable
 * @package zesk
 * @subpackage system
 */
class Options {
	/**
	 * Character used for space
	 * @var string
	 */
	public const CHARACTER_OPTION_SPACE = '_';

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
	 * Does any option exist (and check empty, optionally)?
	 *
	 * @param string|iterable $name The name of the option key to check
	 * @param bool $checkEmpty True if you want to ensure that the value is non-empty (e.g. not null, 0, "0", "", array(), or false)
	 * @return bool
	 * @see empty()
	 */
	public function hasAnyOption(string|iterable $name, bool $checkEmpty = false): bool {
		foreach (toIterable($name) as $k) {
			if ($this->hasOption($k, $checkEmpty)) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Checks an option to see if it is set and optionally if it has a non-empty value.
	 *
	 * @param string $name The name of the option key to check
	 * @param bool $checkEmpty True if you want to ensure that the value is non-empty (e.g. not null, 0, "0", "", array(), or false)
	 * @return bool
	 * @see empty()
	 */
	public function hasOption(string $name, bool $checkEmpty = false): bool {
		$name = self::_optionKey($name);
		if (!array_key_exists($name, $this->options)) {
			return false;
		}
		return !$checkEmpty || !empty($this->options[$name]);
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
	 * @param string $name A string of the option name to convert and append.
	 * @param mixed $value The value to append to the end of the option's value array.
	 * @return Options
	 */
	public function optionAppend(string $name, mixed $value): self {
		$name = self::_optionKey($name);
		$current_value = $this->options[$name] ?? null;
		if (is_scalar($current_value) && !empty($current_value)) {
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
	public function option(string $name, mixed $default = null): mixed {
		return $this->options[self::_optionKey($name)] ?? $default;
	}

	/**
	 * @param string $name
	 * @param mixed|null $default
	 * @return mixed
	 */
	public function optionString(string $name, string $default = ''): string {
		return strval($this->options[self::_optionKey($name)] ?? $default);
	}

	/**
	 * Returns first option found
	 *
	 * @param iterable $names
	 * @param mixed $default The default value to return of the option is not found
	 * @return mixed The retrieved option
	 */
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
	 * @param string|int $name
	 * @return string normalized key name
	 */
	final protected static function _optionKey(string|int $name): string {
		return strtr(trim(strval($name)), [
			'-' => self::CHARACTER_OPTION_SPACE,
			'_' => self::CHARACTER_OPTION_SPACE,
			' ' => self::CHARACTER_OPTION_SPACE,
		]);
	}

	/**
	 * Clean option keys
	 *
	 * @param array $options
	 * @param array $target
	 * @return array
	 */
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
	public function optionBool(string $name, bool $default = false): bool {
		return toBool($this->options[self::_optionKey($name)] ?? $default);
	}

	/**
	 * @param string $name
	 * @param int $default
	 * @return int
	 */
	public function optionInt(string $name, int $default = 0): int {
		return toInteger($this->options[self::_optionKey($name)] ?? $default, $default);
	}

	/**
	 * Get an option as a numeric (floating-point or integer) value.
	 *
	 * @param string $name Option to retrieve as a real value.
	 * @param mixed $default Value to return if this option is not set or is not is_numeric.
	 * @return float The real value of the option, or $default. The default value is passed back without modification.
	 * @see is_numeric()
	 */
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
	public function optionArray(string $name, array $default = []): array {
		$name = self::_optionKey($name);
		if (isset($this->options[$name]) && is_array($this->options[$name])) {
			return $this->options[$name];
		}
		return $default;
	}

	/**
	 * Get an option as a tree-path
	 * @param array $path Option to retrieve as an array value.
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
	 * @param array $path
	 * @param mixed $value
	 * @return Options
	 */
	public function setOptionPath(array $path, mixed $value): self {
		$path[0] = self::_optionKey($path[0]);
		apath_set($this->options, $path, $value);
		return $this;
	}

	/**
	 * Set an option as a tree-path
	 *
	 * @param array $path
	 * @return Options
	 */
	public function unsetOptionPath(array $path): self {
		$path[0] = self::_optionKey($path[0]);
		apath_unset($this->options, $path);
		return $this;
	}

	/**
	 * Get an option as a zero-indexed array, or list array("Bob","Rajiv","Franz")
	 *
	 * @param string $name Option to retrieve as an array value.
	 * @param mixed $default Value to return if this option is not set, is not a string or is not an array.
	 * @return array The string exploded by $delimiter, or the array value. The default value is passed back without modification.
	 * @see is_array(), explode()
	 */
	public function optionIterable(string $name, ?iterable $default = []): iterable {
		$name = self::_optionKey($name);
		if (!isset($this->options[$name])) {
			return toIterable($default);
		}
		return toIterable($this->options[$name]);
	}

	/**
	 * Convert to string
	 *
	 * @return string
	 */
	public function __toString() {
		return PHP::dump($this->options);
	}
}
