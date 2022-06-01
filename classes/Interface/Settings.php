<?php
declare(strict_types=1);
/**
 * Define an interface to name/value pairs
 */

namespace zesk;

/**
 *
 * @author kent
 *
 */
interface Interface_Settings {
	/**
	 * Is a value set in this object?
	 * @param string $name
	 * @return bool
	 */
	public function __isset(string $name): bool;

	/**
	 * Is a value set in this object?
	 * @return boolean
	 */
	public function has(string $name): bool;

	/**
	 * Retrieve a value from the settings
	 * @param mixed $name A string or key value (integer, float)
	 * @return mixed The value of the session variable, or null if nothing set
	 */
	public function __get(string $name): mixed;

	/**
	 * Retrieve a value from the settings, returning a default value if not set
	 * @param mixed $name A string or key value (integer, float)
	 * @param mixed $default A value to return if the session value is null
	 * @return mixed The value of the session variable, or $default if nothing set
	 */
	public function get(string $name, mixed $default = null): mixed;

	/**
	 * Store a value to a settings
	 *
	 * @param mixed $name A string or key value (integer, float)
	 * @param mixed $value Value to save. As a general rule, best to use scalar types
	 */
	public function __set(string $name, mixed $value): void;

	/**
	 * Store a value to a settings
	 *
	 * @param mixed $name A string or key value (integer, float)
	 * @param mixed $value Value to save. As a general rule, best to use scalar types
	 * @return self
	 */
	public function set(string $name, mixed $value = null): self;

	/**
	 * Retrieve a list of all settings variables as an array
	 *
	 * @return iterable
	 */
	public function variables(): iterable;
}
