<?php
declare(strict_types=1);
/**
 * Define an interface to name/value pairs
 */

namespace zesk\Interface;

/**
 *
 * @author kent
 *
 */
interface SettingsInterface {
	/**
	 * Is a value set in this object?
	 * @param string|int $name
	 * @return boolean
	 */
	public function has(string|int $name): bool;

	/**
	 * Retrieve a value from the settings, returning a default value if not set
	 * @param mixed $name A string or key value (integer, float)
	 * @param mixed $default A value to return if the session value is null
	 * @return mixed The value of the session variable, or $default if nothing set
	 */
	public function get(string|int $name, mixed $default = null): mixed;

	/**
	 * Magic get method
	 *
	 * @param string|int $name
	 * @return mixed
	 */
	public function __get(string|int $name): mixed;

	/**
	 * Magic isset method
	 *
	 * @param string|int $name
	 * @return bool
	 */
	public function __isset(string|int $name): bool;

	/**
	 * Magic set method
	 *
	 * @param string|int $name
	 * @param mixed $value
	 * @return void
	 */
	public function __set(string|int $name, mixed $value): void;

	/**
	 * Store a value to a settings
	 *
	 * @param mixed $name A string or key value (integer, float)
	 * @param mixed $value Value to save. As a general rule, best to use scalar types
	 * @return self
	 */
	public function set(string|int $name, mixed $value): self;

	/**
	 * Retrieve a list of all settings variables as an array
	 *
	 * @return iterable
	 */
	public function variables(): iterable;
}
