<?php
declare(strict_types=1);
/**
 * Define an interface to name/value pairs
 */

namespace zesk\Interface;

use zesk\Exception\KeyNotFound;

/**
 *
 * @author kent
 *
 */
interface SettingsInterface {
	/**
	 * Is a value set in this object?
	 * @param string|int $key
	 * @return boolean
	 */
	public function has(string|int $key): bool;

	/**
	 * Retrieve a value from the settings, returning a default value if not set
	 * @param mixed $key A string or key value (integer, float)
	 * @return mixed The value of the session variable, or $default if nothing set
	 * @throws KeyNotFound
	 */
	public function get(string|int $key): mixed;

	/**
	 * Store a value to a settings
	 *
	 * @param mixed $key A string or key value (integer, float)
	 * @param mixed $value Value to save. As a general rule, best to use scalar types
	 * @return self
	 */
	public function set(string|int $key, mixed $value): self;

	/**
	 * Retrieve a list of all settings variables as an array
	 *
	 * @return iterable
	 */
	public function variables(): iterable;
}
