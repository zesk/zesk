<?php
declare(strict_types=1);
/**
 * @package zesk
 * @subpackage system
 * @author kent
 */

namespace zesk;

trait GetTyped
{
	/**
	 * Check for a value and check optionally that it is not empty.
	 *
	 * @param string $key
	 * @param boolean $check_empty
	 * @return boolean
	 */
	public function has(string $key, bool $check_empty = false): bool
	{
		if (!$this->__isset($key)) {
			return false;
		}
		if ($check_empty) {
			$value = $this->__get($key);
			return !empty($value);
		}
		return true;
	}

	/**
	 * Get a variable name, with a default
	 *
	 * @param string|int $k
	 * @param mixed $default
	 * @return mixed
	 */
	public function get(string|int $k, mixed $default = null): mixed
	{
		return $this->__get($k) ?? $default;
	}

	/**
	 * Get first key value matching, or default
	 *
	 * @param string|array $keys
	 * @param mixed $default
	 * @return mixed
	 */
	public function getFirst(string|array $keys, mixed $default = null): mixed
	{
		foreach (toList($keys) as $key) {
			if ($this->__isset($key)) {
				return $this->__get($key);
			}
		}
		return $default;
	}

	/**
	 * Retrieve a non-empty value
	 *
	 * @param string $key Value to retrieve.
	 * @param mixed $default
	 * @return mixed
	 */
	public function getNotEmpty(string $key, mixed $default = null): mixed
	{
		if (!$this->__isset($key)) {
			return $default;
		}
		$value = $this->__get($key);
		return empty($value) ? $default : $value;
	}

	/**
	 * Get a value and convert it to an integer, or return $default
	 *
	 * @param string|int $key
	 * @param int $default
	 * @return integer
	 */
	public function getInt(string|int $key, int $default = 0): int
	{
		return toInteger($this->__get($key), $default);
	}

	/**
	 * Get a value and convert it to a string, or return $default
	 *
	 * @param string|int $key
	 * @param string $default
	 * @return string
	 */
	public function getString(string|int $key, string $default = ''): string
	{
		$r = $this->__get($key);
		if ($r !== null) {
			return strval($r);
		}
		return $default;
	}

	/**
	 * Retrieve a variable as a double value
	 *
	 * @param string $name
	 * @param float $default
	 * @return float
	 */
	public function getFloat(string $name, float $default = 0.0): float
	{
		return toFloat($this->get($name), $default);
	}

	/**
	 * Get a value and convert it to a boolean value, or return $default
	 *
	 * @param string|int $key
	 * @param mixed $default
	 * @return boolean
	 */
	public function getBool(string|int $key, bool $default = false): bool
	{
		return toBool($this->__get($key), $default);
	}

	/**
	 * Get a value if it's an array, or return $default
	 *
	 * @param string|int $key
	 * @param mixed $default
	 * @return array
	 */
	public function getArray(string|int $key, array $default = []): array
	{
		$value = $this->__get($key);
		if (is_array($value)) {
			return $value;
		}
		return $default;
	}

	/**
	 * Retrieve a value as an array value
	 *
	 * @param string $name
	 * @param mixed $default
	 * @param string $sep
	 *            For string values, split on this character
	 * @return array
	 */
	public function getList(string $name, array $default = [], string $sep = ';'): array
	{
		$x = $this->__get($name);
		if (is_array($x)) {
			return $x;
		}
		if (!is_string($x)) {
			return $default;
		}
		if ($sep === '') {
			return str_split($x);
		}
		return explode($sep, $x);
	}
}
