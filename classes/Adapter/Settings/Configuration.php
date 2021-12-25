<?php declare(strict_types=1);
/**
 *
 */
namespace zesk;

/**
 *
 */
class Adapter_Settings_Configuration implements Interface_Settings {
	/**
	 * @var \zesk\Configuration
	 */
	protected $configuration = null;

	/**
	 *
	 */
	public function __construct(Configuration $configuration) {
		$this->configuration = $configuration;
	}

	/**
	 * Is a value set in this object?
	 * @return boolean
	 */
	public function __isset($name) {
		return $this->configuration->path_exists($name);
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
		return $this->configuration->path_get($name);
	}

	/**
	 * Retrieve a value from the settings, returning a default value if not set
	 * @param mixed $name A string or key value (integer, float)
	 * @param mixed $default A value to return if the session value is null
	 * @return mixed The value of the session variable, or $default if nothing set
	 */
	public function get($name = null, $default = null) {
		return $this->configuration->path_get($name, $default);
	}

	/**
	 * Store a value to a settings
	 *
	 * @param mixed $name A string or key value (integer, float)
	 * @param mixed $value Value to save. As a general rule, best to use scalar types
	 */
	public function __set($name, $value): void {
		$this->configuration->path_set($name, $value);
	}

	/**
	 * Store a value to a settings
	 *
	 * @param mixed $name A string or key value (integer, float)
	 * @param mixed $value Value to save. As a general rule, best to use scalar types
	 * @return Interface_Settings
	 */
	public function set($name, $value = null) {
		$this->configuration->path_set($name, $value);
	}

	/**
	 * Retrieve a list of all settings variables as an array
	 *
	 * @return Iterator
	 */
	public function variables() {
		return $this->configuration->to_array();
	}
}
