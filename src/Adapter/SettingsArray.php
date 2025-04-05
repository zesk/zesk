<?php
declare(strict_types=1);
/**
 *
 */
namespace zesk\Adapter;

use zesk\ArrayTools;
use zesk\Interface\SettingsInterface;
use zesk\Types;

/**
 * SettingsInterface adapter
 */
class SettingsArray implements SettingsInterface
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

	private function _keyPath(int|string $key): array
	{
		return Types::toList(strval($key), [], ZESK_GLOBAL_KEY_SEPARATOR);
	}

	/**
	 * Is a value set in this object?
	 * @param int|string $name
	 * @return boolean
	 */
	public function __isset(int|string $name): bool
	{
		return ArrayTools::path($this->data, self::_keyPath($name)) !== null;
	}

	/**
	 * Is a value set in this object?
	 * @param int|string $name
	 * @return boolean
	 */
	public function has(int|string $name): bool
	{
		return $this->__isset($name);
	}

	/**
	 * Retrieve a value from the settings
	 * @param int|string $name A string or key value (integer, float)
	 * @return mixed The value of the session variable, or null if nothing set
	 */
	public function __get(int|string $name): mixed
	{
		return $this->get($name);
	}

	/**
	 * Retrieve a value from the settings, returning a default value if not set
	 * @param int|string $name A string or key value (integer, float)
	 * @param mixed|null $default A value to return if the session value is null
	 * @return mixed The value of the session variable, or $default if nothing set
	 */
	public function get(int|string $name, mixed $default = null): mixed
	{
		return ArrayTools::path($this->data, self::_keyPath($name), $default);
	}

	/**
	 * Store a value to a settings
	 *
	 * @param mixed $name A string or key value (integer, float)
	 * @param mixed $value Value to save. As a general rule, best to use scalar types
	 */
	public function __set(int|string $name, mixed $value): void
	{
		ArrayTools::setPath($this->data, self::_keyPath($name), $value);
	}

	/**
	 * Store a value to a settings
	 *
	 * @param int|string $name A string or key value (integer, float)
	 * @param mixed $value Value to save. As a general rule, best to use scalar types
	 * @return self
	 */
	public function set(int|string $name, mixed $value): self
	{
		$this->__set($name, $value);
		return $this;
	}

	/**
	 * Retrieve a list of all settings variables as an array
	 *
	 * @return iterable
	 */
	public function variables(): iterable
	{
		return $this->data;
	}
}
