<?php
declare(strict_types=1);
/**
 *
 */

namespace zesk\Adapter;

use zesk\Interface\SettingsInterface;

/**
 * SettingsInterface adapter
 */
class SettingsArrayNoCase implements SettingsInterface
{
	/**
	 *
	 */
	protected array $data;

	/**
	 *
	 */
	public function __construct(array &$array)
	{
		$this->data = &$array;
	}

	/**
	 * Is a value set in this object?
	 *
	 * @param int|string $key
	 * @return bool
	 */
	public function __isset(int|string $key): bool
	{
		return isset($this->data[strtolower($key)]);
	}

	/**
	 * Is a value set in this object?
	 *
	 * @param int|string $key
	 * @return boolean
	 */
	public function has(int|string $key): bool
	{
		return $this->__isset($key);
	}

	/**
	 * Retrieve a value from the settings
	 * @param mixed $key A string or key value (integer, float)
	 * @return mixed The value of the session variable, or null if nothing set
	 */
	public function __get(int|string $key): mixed
	{
		$key = strtolower($key);
		return $this->data[$key] ?? null;
	}

	/**
	 * Retrieve a value from the settings, returning a default value if not set
	 * @param mixed $key A string or key value (integer, float)
	 * @param mixed $default A value to return if the session value is null
	 * @return mixed The value of the session variable, or $default if nothing set
	 */
	public function get(int|string $key, mixed $default = null): mixed
	{
		$key = strtolower($key);
		return $this->data[$key] ?? $default;
	}

	/**
	 * Store a value to a settings
	 *
	 * @param int|string $key A string or key value (integer, float)
	 * @param mixed $value Value to save. As a general rule, best to use scalar types
	 * @see SettingsInterface::__set
	 */
	public function __set(int|string $key, mixed $value): void
	{
		$this->data[strtolower($key)] = $value;
	}

	/**
	 * Store a value to a settings
	 *
	 * @param mixed $key A string or key value (integer, float)
	 * @param mixed $value Value to save. As a general rule, best to use scalar types
	 * @return self
	 */
	public function set(int|string $key, mixed $value = null): self
	{
		$this->data[strtolower($key)] = $value;
		return $this;
	}

	/**
	 * Retrieve a list of all settings variables as an array
	 *
	 * @return array
	 */
	public function variables(): array
	{
		return $this->data;
	}
}
