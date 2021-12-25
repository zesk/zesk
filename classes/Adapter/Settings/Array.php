<?php declare(strict_types=1);
/**
 *
 */
namespace zesk;

/**
 * Interface_Settings adapter
 */
class Adapter_Settings_Array implements Interface_Settings {
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
		return apath($this->data, $name, null, ZESK_GLOBAL_KEY_SEPARATOR) !== null;
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
		return $this->get($name);
	}

	/**
	 * Retrieve a value from the settings, returning a default value if not set
	 * @param mixed $name A string or key value (integer, float)
	 * @param mixed $default A value to return if the session value is null
	 * @return mixed The value of the session variable, or $default if nothing set
	 */
	public function get($name = null, $default = null) {
		return apath($this->data, $name, $default, ZESK_GLOBAL_KEY_SEPARATOR);
	}

	/**
	 * Retrieve a value from the settings, returning a default value if empty or not set
	 * @param mixed $name A string or key value (integer, float)
	 * @param mixed $default A value to return if the session value is null
	 * @return mixed The value of the session variable, or $default if nothing set
	 */
	public function eget($name, $default = null) {
		$value = $this->get($name);
		return empty($value) ? $default : $value;
	}

	/**
	 * Store a value to a settings
	 *
	 * @param mixed $name A string or key value (integer, float)
	 * @param mixed $value Value to save. As a general rule, best to use scalar types
	 */
	public function __set($name, $value): void {
		apath_set($this->data, $name, $value, ZESK_GLOBAL_KEY_SEPARATOR);
	}

	/**
	 * Store a value to a settings
	 *
	 * @param mixed $name A string or key value (integer, float)
	 * @param mixed $value Value to save. As a general rule, best to use scalar types
	 * @return Interface_Settings
	 */
	public function set($name, $value = null) {
		$this->__set($name, $value);
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
