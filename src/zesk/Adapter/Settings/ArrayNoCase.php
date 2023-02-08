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
	protected array $data;

	/**
	 *
	 */
	public function __construct(array &$array) {
		$this->data = &$array;
	}

	/**
	 * Is a value set in this object?
	 *
	 * @param int|string $name
	 * @return bool
	 */
	public function __isset(int|string $name): bool {
		return isset($this->data[strtolower($name)]);
	}

	/**
	 * Is a value set in this object?
	 *
	 * @param int|string $name
	 * @return boolean
	 */
	public function has(int|string $name): bool {
		return $this->__isset($name);
	}

	/**
	 * Retrieve a value from the settings
	 * @param mixed $name A string or key value (integer, float)
	 * @return mixed The value of the session variable, or null if nothing set
	 */
	public function __get(int|string $name): mixed {
		$name = strtolower($name);
		return $this->data[$name] ?? null;
	}

	/**
	 * Retrieve a value from the settings, returning a default value if not set
	 * @param mixed $name A string or key value (integer, float)
	 * @param mixed $default A value to return if the session value is null
	 * @return mixed The value of the session variable, or $default if nothing set
	 */
	public function get(int|string $name, mixed $default = null): mixed {
		$name = strtolower($name);
		return $this->data[$name] ?? $default;
	}

	/**
	 * Store a value to a settings
	 *
	 * @param int|string $name A string or key value (integer, float)
	 * @param mixed $value Value to save. As a general rule, best to use scalar types
	 * @see Interface_Settings::__set
	 */
	public function __set(int|string $name, mixed $value): void {
		$this->data[strtolower($name)] = $value;
	}

	/**
	 * Store a value to a settings
	 *
	 * @param mixed $name A string or key value (integer, float)
	 * @param mixed $value Value to save. As a general rule, best to use scalar types
	 * @return self
	 */
	public function set(int|string $name, mixed $value = null): self {
		$this->data[strtolower($name)] = $value;
		return $this;
	}

	/**
	 * Retrieve a list of all settings variables as an array
	 *
	 * @return array
	 */
	public function variables(): array {
		return $this->data;
	}
}
