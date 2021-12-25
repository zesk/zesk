<?php declare(strict_types=1);
/**
 *
 */
namespace zesk;

/**
 * Interface_Settings adapter
 */
class Adapter_Settings_ArrayNoCase implements Interface_Settings {
	/**
	 *
	 */
	protected $data = null;

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
	public function __isset($name) {
		return isset($this->data[strtolower($name)]);
	}

	/**
	 * Is a value set in this object?
	 * @return boolean
	 */
	public function has($name) {
		return $this->__isset($name);
	}

	/**
	 * Retrieve a value from the settings
	 * @param mixed $name A string or key value (integer, float)
	 * @return mixed The value of the session variable, or null if nothing set
	 */
	public function __get($name) {
		$name = strtolower($name);
		return $this->data[$name] ?? null;
	}

	/**
	 * Retrieve a value from the settings, returning a default value if not set
	 * @param mixed $name A string or key value (integer, float)
	 * @param mixed $default A value to return if the session value is null
	 * @return mixed The value of the session variable, or $default if nothing set
	 */
	public function get($name = null, $default = null) {
		$name = strtolower($name);
		return $this->data[$name] ?? $default;
	}

	/**
	 * Retrieve a value from the settings, returning a default value if empty or not set
	 * @param mixed $name A string or key value (integer, float)
	 * @param mixed $default A value to return if the session value is null
	 * @return mixed The value of the session variable, or $default if nothing set
	 */
	public function eget($name, $default = null) {
		$name = strtolower($name);
		return isset($this->data[$name]) && !empty($this->data[$name]) ? $this->data[$name] : $default;
	}

	/**
	 * Store a value to a settings
	 *
	 * @param mixed $name A string or key value (integer, float)
	 * @param mixed $value Value to save. As a general rule, best to use scalar types
	 */
	public function __set($name, $value): void {
		$this->data[strtolower($name)] = $value;
	}

	/**
	 * Store a value to a settings
	 *
	 * @param mixed $name A string or key value (integer, float)
	 * @param mixed $value Value to save. As a general rule, best to use scalar types
	 * @return Interface_Settings
	 */
	public function set($name, $value = null) {
		$this->data[strtolower($name)] = $value;
	}

	/**
	 * Retrieve a list of all settings variables as an array
	 *
	 * @return Iterator
	 */
	public function variables() {
		return $this->data;
	}
}
