<?php
declare(strict_types=1);
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
	 * @param int|string $name
	 * @return boolean
	 */
	public function __isset(int|string $name): bool {
		return $this->configuration->pathExists($name);
	}

	/**
	 * Is a value set in this object?
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
		return $this->configuration->getPath($name);
	}

	/**
	 * Retrieve a value from the settings, returning a default value if not set
	 * @param mixed $name A string or key value (integer, float)
	 * @param mixed $default A value to return if the session value is null
	 * @return mixed The value of the session variable, or $default if nothing set
	 */
	public function get(int|string $name, mixed $default = null): mixed {
		return $this->configuration->getPath($name, $default);
	}

	/**
	 * Store a value to a settings
	 *
	 * @param mixed $name A string or key value (integer, float)
	 * @param mixed $value Value to save. As a general rule, best to use scalar types
	 */
	public function __set(int|string $name, mixed $value): void {
		$this->configuration->setPath($name, $value);
	}

	/**
	 * Store a value to a settings
	 *
	 * @param mixed $name A string or key value (integer, float)
	 * @param mixed $value Value to save. As a general rule, best to use scalar types
	 * @return self
	 */
	public function set(int|string $name, mixed $value = null): self {
		$this->configuration->setPath($name, $value);
		return $this;
	}

	/**
	 * Retrieve a list of all settings variables as an array
	 *
	 * @return iterable
	 */
	public function variables(): iterable {
		return $this->configuration->toArray();
	}
}
