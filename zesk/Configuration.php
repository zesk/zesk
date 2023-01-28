<?php
declare(strict_types=1);
/**
 *
 */

namespace zesk;

use ArrayAccess;
use Countable;
use Iterator;

/**
 *
 * @author kent
 *
 */
class Configuration implements Iterator, Countable, ArrayAccess {
	/**
	 * When we pass strings into methods as paths, this sequence of characters is equivalent to a
	 * traversal from parent Configuration to child Configuration object.
	 *
	 * @var string
	 */
	public const key_separator = '::';

	/**
	 * Path to get to this configuration location
	 */
	protected array $_path = [];

	/**
	 * Our datum, datum, datum, datum, datum, datum, datum, dat-u-u-u-um.
	 *
	 * Key value pairs. Lots of Configuration objects, or non-Configuration values
	 *
	 * @var Configuration[]
	 */
	protected array $_data;

	/**
	 * Current iterator index. For iterating, silly.
	 *
	 * Does this mean that PHP is not re-entrant? PHP will probably just add a keyword for 'per-thread'.
	 * Yikes.
	 */
	protected int $_index;

	/**
	 * Number of configuration items
	 */
	protected int $_count;

	/**
	 * Skip the next iteration
	 */
	protected bool $_skip_next = false;

	/**
	 * Configuration constructor.
	 * @param array $array
	 * @param array $path
	 */
	public function __construct(array $array = [], array $path = []) {
		$this->_path = $path;
		foreach ($array as $key => $value) {
			if (is_array($value)) {
				$array[$key] = new self($value, $this->_addPath($key));
			}
		}
		$this->_data = $array;
		$this->_index = 0;
		$this->_count = count($this->_data);
	}

	public function normalizeKey(string $key): string {
		return implode(ZESK_GLOBAL_KEY_SEPARATOR, _zesk_global_key($key));
	}

	/**
	 *
	 * @param array $array
	 * @param array $path
	 * @return self
	 */
	public static function factory(array $array = [], array $path = []): self {
		return new self($array, $path);
	}

	/**
	 * Copy a configuration
	 */
	public function __clone() {
		$array = [];
		foreach ($this->_data as $key => $value) {
			if ($value instanceof self) {
				$array[$key] = clone $value;
			} else {
				$array[$key] = $value;
			}
		}
		$this->_data = $array;
	}

	/**
	 * Merge two configurations.
	 * Passed in configuration will, by default, override similar keys and paths in
	 * current object.
	 *
	 * @param Configuration $config
	 * @param boolean $overwrite
	 * @return self
	 */
	public function merge(Configuration $config, bool $overwrite = true): self {
		foreach ($config as $key => $value) {
			if (isset($this->_data[$key])) {
				$this_value = $this->_data[$key];
				if ($value instanceof self && $this_value instanceof self) {
					$this_value->merge($value, $overwrite);
				} elseif ($overwrite) {
					$this->_data[$key] = $value instanceof self ? clone $value : $value;
				}
			} else {
				$this->_data[$key] = $value instanceof self ? clone $value : $value;
			}
		}
		$this->_count = count($this->_data);
		return $this;
	}

	/**
	 * Does this configuration value exist?
	 *
	 * @param string $key
	 * @return boolean
	 */
	public function has(string $key): bool {
		return $this->__isset($key);
	}

	/**
	 *
	 * @param string $key
	 * @return boolean
	 */
	public function __isset(string $key): bool {
		return isset($this->_data[$key]);
	}

	/**
	 * @param string $key
	 * @return void
	 */
	public function __unset(string $key): void {
		if (isset($this->_data[$key])) {
			unset($this->_data[$key]);
			$this->_count = count($this->_data);
		}
	}

	/**
	 * @param string|array $key
	 * @param mixed|null $value
	 * @return $this
	 */
	public function set(string|array $key, mixed $value = null): self {
		if (is_array($key)) {
			foreach ($key as $k => $v) {
				$this->__set($k, $v);
			}
			return $this;
		}
		$this->__set($key, $value);
		return $this;
	}

	/**
	 * Set a value in a configuration object
	 *
	 * @param string $key
	 * @param mixed $value
	 * @return void
	 */
	public function __set(mixed $key, mixed $value): void {
		if (is_array($value)) {
			$value = new self($value, $this->_addPath($key));
		}
		$this->_data[$key] = $value;
		$this->_count = count($this->_data);
	}

	/**
	 * Same as __get but allows a default value
	 *
	 * @param string $key
	 *            Key to retrieve
	 * @param mixed $default
	 * @return mixed
	 */
	public function get(string $key, mixed $default = null): mixed {
		if (strpos($key, '-') && !isset($this->_data[$key])) {
			error_log(map('Fetching MISSING key {key} with dash from {func}', [
				'key' => $key,
				'func' => calling_function(1),
			]));
		}
		return $this->_data[$key] ?? $default;
	}

	/**
	 * Get a value and ensure it's a string
	 *
	 * @param string $key
	 * @return string
	 * @throws Exception_Semantics
	 */
	public function getString(string $key): string {
		$node = $this->get($key);
		if (!is_scalar($node)) {
			throw new Exception_Semantics('Value can not be converted to string {type}', ['type' => gettype($node)]);
		}
		return strval($node);
	}

	/**
	 * Given a list of paths into the configuration tree, return the first one which has a value
	 *
	 * @param array $paths
	 * @param mixed $default
	 * @return mixed
	 */
	public function getFirstPath(array $paths, mixed $default = null): mixed {
		foreach ($paths as $path) {
			if (($result = $this->getPath($path)) !== null) {
				return $result;
			}
		}
		return $default;
	}

	/**
	 * Set multiple paths to multiple values
	 *
	 * @param array $paths
	 * @return Configuration[]
	 */
	public function setPaths(array $paths): array {
		$result = [];
		foreach ($paths as $path => $value) {
			$result[$path] = $this->setPath(toList($path, [], self::key_separator), $value);
		}
		return $result;
	}

	/**
	 * Retrieve a path using self::key_separator
	 *
	 * @param string|array $path
	 * @param mixed $default
	 * @return mixed
	 */
	public function getPath(string|array $path, mixed $default = null): mixed {
		$path = is_array($path) ? $path : explode(self::key_separator, $path);
		$current = $this;
		$key = array_pop($path);
		foreach ($path as $section) {
			$current = $current->$section;
			if (!$current instanceof self) {
				return $default;
			}
		}
		$result = $current->get($key, $default);
		return $result instanceof self ? $result->toArray() : $result;
	}

	/**
	 * Retrieve a path and fetch a string value
	 *
	 * @param string|array $path
	 * @param string $default
	 * @return string
	 */
	public function getPathString(string|array $path, string $default = ''): string {
		$path = $this->getPath($path, null);
		return is_string($path) ? $path : $default;
	}

	/**
	 * Does a path exist?
	 * @param string|array $path
	 * @return bool
	 */
	public function pathExists(string|array $path): bool {
		$path = is_array($path) ? $path : explode(self::key_separator, $path);
		$current = $this;
		$key = array_pop($path);
		foreach ($path as $section) {
			$current = $current->$section;
			if (!$current instanceof self) {
				return false;
			}
		}
		return $current->has($key);
	}

	/**
	 *
	 * @param string $key
	 * @return self
	 */
	public function __get(string $key) {
		return $this->_data[$key] ?? null;
	}

	/**
	 * Given a path into the configuration tree, set a value
	 *
	 * @param string|array $path
	 * @param mixed $value
	 * @return self returns self always
	 */
	public function setPath(string|array $path, mixed $value = null): self {
		$path = is_array($path) ? $path : explode(self::key_separator, $path);
		$key = array_pop($path);
		if (count($path) > 0) {
			$this->path($path)->setPath($key, $value);
			return $this;
		}
		$this->$key = $value;
		return $this;
	}

	/**
	 * Ensure configuration path is available
	 * @param array|string $keys
	 * @return self
	 */
	public function path(string|array $keys): self {
		$keys = is_array($keys) ? $keys : explode(self::key_separator, $keys);
		$current = $this;
		while (count($keys) > 0) {
			$next = array_shift($keys);
			if (!$current->$next instanceof self) {
				$current->set($next, []);
			}
			$current = $current->$next;
		}
		return $current;
	}

	/**
	 * Walk configuration and return found value, or default if not found
	 *
	 * @param string|array $keys
	 * @param mixed $default
	 * @return mixed
	 */
	public function walk(string|array $keys, mixed $default = null): mixed {
		$keys = is_array($keys) ? $keys : explode(self::key_separator, $keys);
		$current = $this;
		while (count($keys) > 0) {
			if (!$current instanceof self) {
				return $default;
			}
			$next = array_shift($keys);
			$current = $current->__get($next);
		}
		return $current;
	}

	/**
	 * Retrieve value
	 */
	public function value(): array {
		return $this->_data;
	}

	/**
	 * Convert entire structure to an array, recursively
	 *
	 * @param int|null $depth How deep to traverse (null for infinite)
	 * @return array
	 */
	public function toArray(int $depth = null): array {
		$result = [];
		foreach ($this->_data as $key => $value) {
			if ($value instanceof self) {
				if ($depth === null || $depth > 0) {
					$result[$key] = $value->toArray($depth > 0 ? $depth - 1 : null);
				}
			} else {
				$result[$key] = $value;
			}
		}
		return $result;
	}

	/**
	 * Convert entire structure to a list
	 *
	 * @return array
	 */
	public function toList(): array {
		$result = [];
		foreach ($this->_data as $value) {
			if ($value instanceof self) {
				continue;
			}
			$result[] = $value;
		}
		return $result;
	}

	/**
	 * Defined by Iterator interface
	 *
	 * @return mixed
	 *
	 */
	public function current(): mixed {
		$this->_skip_next = false;
		return current($this->_data);
	}

	/**
	 *
	 * @return string|int|null
	 * @see Iterator
	 *
	 */
	public function key(): string|int|null {
		return key($this->_data);
	}

	/**
	 *
	 * @see Iterator
	 */
	public function next(): void {
		if ($this->_skip_next) {
			$this->_skip_next = false;
			return;
		}
		next($this->_data);
		$this->_index++;
	}

	/**
	 *
	 * @see Iterator
	 */
	public function rewind(): void {
		$this->_skip_next = false;
		reset($this->_data);
		$this->_index = 0;
	}

	/**
	 *
	 * @return boolean
	 * @see Iterator
	 *
	 */
	public function valid(): bool {
		return $this->_index < $this->_count;
	}

	/**
	 *
	 * @return integer
	 */
	public function count(): int {
		return $this->_count;
	}

	/**
	 *
	 * @param mixed $offset
	 * @return boolean
	 * @see ArrayAccess
	 */
	public function offsetExists(mixed $offset): bool {
		return $this->__isset($offset);
	}

	/**
	 *
	 * @param mixed $offset
	 * @return mixed
	 * @see ArrayAccess
	 */
	public function offsetGet(mixed $offset): mixed {
		return $this->__get($offset);
	}

	/**
	 *
	 * @param mixed $offset
	 * @param mixed $value
	 * @see ArrayAccess
	 */
	public function offsetSet(mixed $offset, mixed $value): void {
		$this->__set($offset, $value);
	}

	/**
	 *
	 * @param mixed $offset
	 * @see ArrayAccess
	 */
	public function offsetUnset(mixed $offset): void {
		$this->__unset($offset);
	}

	/**
	 * Add key to existing path and return a new path
	 *
	 * @param string $key
	 * @return array
	 */
	private function _addPath(string $key): array {
		return array_merge($this->_path, [
			$key,
		]);
	}

	/**
	 * Returns true if old configuration option is still being used
	 *
	 * @param array|string $old_path
	 * @param string|array|null $new_path
	 * @return boolean Returns true if OLD value still found and (optionally) mapped to new
	 * @throws Exception_Semantics|Exception_Deprecated
	 */
	final public function deprecated(string|array $old_path, string|array $new_path = null): bool {
		$old_value = $this->walk($old_path);
		if ($old_value === null) {
			return false;
		}
		$logger = Kernel::singleton()->application()->logger;
		if ($new_path == null) {
			$logger->warning('Global configuration option {old_path} is deprecated, remove it', compact('old_path'));
			return true;
		}
		$message_args = [];
		if (!$this->pathExists($new_path)) {
			$this->setPath($new_path, $old_value);
			$message = 'Global configuration option "{old_path}" is deprecated ({old_value}), use existing "{new_path}"';
			$message_args['old_value'] = toArray($old_value);
		} else {
			$new_value = $this->walk($new_path);
			if ($new_value instanceof self && $old_value instanceof self) {
				$message = 'Global configuration option {old_path} is deprecated ({old_value}), use existing "{new_path}" (merged)';
				$new_value->merge($old_value);
				$this->setPath($old_path, null);
				$message_args['old_value'] = toArray($old_value);
			} else {
				$message = 'Global configuration option {old_path} ({old_type}) is deprecated, use existing {new_path} (NOT merged)';
			}
		}
		if (is_array($old_path)) {
			$old_path = implode(self::key_separator, $old_path);
		}
		if (is_array($new_path)) {
			$new_path = implode(self::key_separator, $new_path);
		}
		$message_args += compact('old_path', 'new_path') + [
			'old_type' => type($old_value),
		];
		$logger->warning($message, $message_args);
		Kernel::singleton()->deprecated($message, $message_args);
		return true;
	}

	/**
	 * Retrieve a path using self::key_separator
	 *
	 * @param string|array $path
	 * @param mixed $default
	 * @return mixed
	 * @deprecated 2022-12
	 */
	public function path_get(string|array $path, mixed $default = null): mixed {
		zesk()->deprecated(__METHOD__);
		return $this->getPath($path, $default);
	}
}
