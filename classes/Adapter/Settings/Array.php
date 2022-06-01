<?php declare(strict_types=1);
/**
 *
 */
namespace zesk;

use RRule\Iterator;

/**
 * Interface_Settings adapter
 */
class Adapter_Settings_Array implements Interface_Settings {
	/**
	 *
	 */
	protected array $data;

	/**
	 *
	 */
	public function __construct(array &$array) {
		$this->data = &$array;
	}

	/**
	 * Is a value set in this object?
	 * @return boolean
	 */
	public function __isset(string $name): bool {
		return apath($this->data, $name, null, ZESK_GLOBAL_KEY_SEPARATOR) !== null;
	}

	/**
	 * Is a value set in this object?
	 * @return boolean
	 */
	public function has(string $name): bool {
		return $this->__isset($name);
	}

	/**
	 * Retrieve a value from the settings
	 * @param mixed $name A string or key value (integer, float)
	 * @return mixed The value of the session variable, or null if nothing set
	 */
	public function __get(string $name): mixed {
		return $this->get($name);
	}

	/**
	 * Retrieve a value from the settings, returning a default value if not set
	 * @param mixed $name A string or key value (integer, float)
	 * @param mixed $default A value to return if the session value is null
	 * @return mixed The value of the session variable, or $default if nothing set
	 */
	public function get(string $name = null, $default = null): mixed {
		return apath($this->data, $name, $default, ZESK_GLOBAL_KEY_SEPARATOR);
	}

	/**
	 * Retrieve a value from the settings, returning a default value if empty or not set
	 * @param mixed $name A string or key value (integer, float)
	 * @param mixed $default A value to return if the session value is null
	 * @return mixed The value of the session variable, or $default if nothing set
	 */
	public function eget(string $name, $default = null): mixed {
		$value = $this->get($name);
		return empty($value) ? $default : $value;
	}

	/**
	 * Store a value to a settings
	 *
	 * @param mixed $name A string or key value (integer, float)
	 * @param mixed $value Value to save. As a general rule, best to use scalar types
	 */
	public function __set(string $name, mixed $value): void {
		apath_set($this->data, $name, $value, ZESK_GLOBAL_KEY_SEPARATOR);
	}

	/**
	 * Store a value to a settings
	 *
	 * @param mixed $name A string or key value (integer, float)
	 * @param mixed $value Value to save. As a general rule, best to use scalar types
	 * @return Interface_Settings
	 */
	public function set(string $name, mixed $value = null): self {
		$this->__set($name, $value);
		return $this;
	}

	/**
	 * Retrieve a list of all settings variables as an array
	 *
	 * @return iterable
	 */
	public function variables(): iterable {
		return $this->data;
	}
}
