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
	 * @var Configuration
	 */
	protected Configuration $configuration;

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
	public function __isset(string $name): bool {
		return $this->configuration->pathExists($name);
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
		return $this->configuration->getPath($name);
	}

	/**
	 * Retrieve a value from the settings, returning a default value if not set
	 * @param mixed $name A string or key value (integer, float)
	 * @param mixed $default A value to return if the session value is null
	 * @return mixed The value of the session variable, or $default if nothing set
	 */
	public function get(string $name = null, mixed $default = null): mixed {
		return $this->configuration->getPath($name, $default);
	}

	/**
	 * Store a value to a settings
	 *
	 * @param mixed $name A string or key value (integer, float)
	 * @param mixed $value Value to save. As a general rule, best to use scalar types
	 */
	public function __set(string $name, mixed $value): void {
		$this->configuration->setPath($name, $value);
	}

	/**
	 * Store a value to a settings
	 *
	 * @param mixed $name A string or key value (integer, float)
	 * @param mixed $value Value to save. As a general rule, best to use scalar types
	 * @return Interface_Settings
	 */
	public function set(string $name, mixed $value = null): self {
		$this->configuration->setPath($name, $value);
		return $this;
	}

	/**
	 * Retrieve a list of all settings variables as an array
	 *
	 * @return Iterator
	 */
	public function variables(): iterable {
		return $this->configuration->toArray();
	}
}
