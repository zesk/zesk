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
	 * Is this tree locked? Meaning, I can't edit it at all?
	 */
	protected bool $_locked = false;

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
	 * @param boolean $locked
	 * @param array $path
	 */
	public function __construct(array $array = [], $locked = false, array $path = []) {
		$this->_path = $path;
		$this->_locked = $locked;
		foreach ($array as $key => $value) {
			if (is_array($value)) {
				$array[$key] = new self($value, $locked, $this->_addPath($key));
			}
		}
		$this->_data = array_change_key_case($array);
		$this->_index = 0;
		$this->_count = count($this->_data);
	}

	/**
	 *
	 * @param array $array
	 * @param boolean $locked
	 * @param array $path
	 * @return self
	 */
	public static function factory(array $array = [], $locked = false, array $path = []) {
		return new self($array, $locked, $path);
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
	public function merge(Configuration $config, $overwrite = true) {
		foreach ($config as $key => $value) {
			$key = strtolower($key);
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
	public function has($key) {
		$key = strtolower($key);
		return isset($this->_data[$key]);
	}

	/**
	 *
	 * @param string $key
	 * @return boolean
	 */
	public function __isset($key) {
		$key = strtolower($key);
		return isset($this->_data[$key]);
	}

	/**
	 * @param $key
	 * @throws Exception_Lock
	 */
	public function __unset($key): void {
		$key = strtolower($key);
		if ($this->_locked) {
			$this->_locked($key, 'delete');
		}
		if (isset($this->_data[$key])) {
			unset($this->_data[$key]);
			$this->_count = count($this->_data);
		}
	}

	/**
	 *
	 * @param string $key
	 * @param mixed $value
	 * @return self|mixed
	 * @throws Exception_Lock
	 */
	public function set($key, $value = null) {
		if (is_array($key)) {
			foreach ($key as $k => $v) {
				$this->__set($k, $v);
			}
			return $this;
		}
		return $this->__set($key, $value);
	}

	/**
	 * Set a value in a configuration object
	 *
	 * @param string $key
	 * @param mixed $value
	 * @return mixed
	 * @throws Exception_Lock
	 */
	public function __set($key, $value) {
		if ($this->_locked) {
			$this->_locked($key, 'set');
		}
		if (is_array($value)) {
			$value = new self($value, $this->_locked, $this->_addPath($key));
		}
		$key = strtolower($key);
		$this->_data[$key] = $value;
		$this->_count = count($this->_data);
		return $value;
	}

	/**
	 * Same as __get but allows a default value
	 *
	 * @param string $key
	 *            Key to retrieve
	 * @param mixed $default
	 * @return mixed
	 */
	public function get($key, $default = null) {
		$key = strtolower($key);
		if (strpos($key, '-') && !isset($this->_data[$key])) {
			error_log(map('Fetching MISSING key {key} with dash from {func}', [
				'key' => $key,
				'func' => calling_function(1),
			]));
		}
		return $this->_data[$key] ?? $default;
	}

	/**
	 * Given a list of paths into the configuration tree, return the first one which has a value
	 *
	 * @param array $paths
	 * @param mixed $default
	 * @return mixed
	 * @throws Exception_Lock
	 */
	public function path_get_first(array $paths, $default = null) {
		foreach ($paths as $path) {
			if (($result = $this->path_get($path)) !== null) {
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
	 * @throws Exception_Lock
	 */
	public function paths_set(array $paths) {
		$result = [];
		foreach ($paths as $path => $value) {
			$result[$path] = $this->path_set(to_list($path, [], self::key_separator), $value);
		}
		return $result;
	}

	/**
	 * Get multiple paths at once, using the key value as the default value
	 *
	 * @param array $paths
	 * @return array
	 */
	public function paths_get(array $paths) {
		$result = [];
		foreach ($paths as $path => $default) {
			$result[$path] = $this->path_get($path, $default);
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
	public function path_get(string|array $path, mixed $default = null): mixed {
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
	public function __get($key) {
		$key = strtolower($key);
		return $this->_data[$key] ?? null;
	}

	/**
	 * Given a path into the configuration tree, set a value
	 *
	 * @param string|array $path
	 * @param mixed $value
	 * @return Configuration parent node of final value set
	 * @throws Exception_Lock
	 */
	public function path_set(string|array $path, $value = null) {
		$path = is_array($path) ? $path : explode(self::key_separator, $path);
		$key = array_pop($path);
		if (count($path) > 0) {
			$current = $this->path($path);
			$current->$key = $value;
			return $current;
		} else {
			$this->$key = $value;
			return $this;
		}
	}

	/**
	 * Ensure configuration path is available
	 * @param array|string $keys
	 * @return self
	 * @throws Exception_Lock
	 */
	public function path(string|array $keys): self {
		$keys = is_array($keys) ? $keys : explode(self::key_separator, $keys);
		$current = $this;
		while (count($keys) > 0) {
			$next = array_shift($keys);
			if (!$current->$next instanceof self) {
				$current = $current->set($next, []);
			} else {
				$current = $current->$next;
			}
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
	public function walk(string|array $keys, $default = null) {
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
	 * Lock a Configuration so it can not be modified
	 */
	public function lock(): void {
		$this->_locked = true;
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
	 * @param int $depth How deep to traverse (null for infinite)
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
	public function toList() {
		$result = [];
		foreach ($this->_data as $key => $value) {
			if ($value instanceof self) {
				continue;
			} else {
				$result[] = $value;
			}
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
	 * @return mixed
	 * @see Iterator
	 *
	 */
	public function key(): mixed {
		//[\ReturnTypeWillChange]
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
	public function offsetGet($offset): mixed {
		return $this->__get($offset);
	}

	/**
	 *
	 * @param mixed $offset
	 * @param mixed $value
	 * @throws Exception_Lock
	 * @see ArrayAccess
	 */
	public function offsetSet($offset, $value): void {
		$this->__set($offset, $value);
	}

	/**
	 *
	 * @param mixed $offset
	 * @throws Exception_Lock
	 * @see ArrayAccess
	 */
	public function offsetUnset($offset): void {
		$this->__unset($offset);
	}

	/**
	 * Add key to existing path and return a new path
	 *
	 * @param string $key
	 * @return array
	 */
	private function _addPath($key) {
		return array_merge($this->_path, [
			$key,
		]);
	}

	/**
	 * Throw exception when locked
	 *
	 * @param string $key
	 *            Key attempting to modify
	 * @param string $verb
	 *            Action attempting to do (debug)
	 * @throws Exception_Lock
	 */
	private function _locked($key, $verb): void {
		throw new Exception_Lock("Unable to $verb key {key} at {path}", [
			'key' => $key,
			'path' => $this->_path,
		]);
	}

	/**
	 * Returns true if old configuration option is still being used
	 *
	 * @param array|string $old_path
	 * @param array|string $new_path
	 * @return boolean Returns true if OLD value still found and (optionally) mapped to new
	 * @throws Exception_Lock|Exception_Semantics|Exception_Deprecated
	 */
	final public function deprecated($old_path, $new_path = null) {
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
			$this->path_set($new_path, $old_value);
			$message = 'Global configuration option "{old_path}" is deprecated ({old_value}), use existing "{new_path}"';
			$message_args['old_value'] = toArray($old_value);
		} else {
			$new_value = $this->walk($new_path);
			if ($new_value instanceof self && $old_value instanceof self) {
				$message = 'Global configuration option {old_path} is deprecated ({old_value}), use existing "{new_path}" (merged)';
				$new_value->merge($old_value);
				$this->path_set($old_path, null);
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
	 * Does a path exist?
	 *
	 * @param string|array $path
	 * @return boolean
	 * @deprecated 2022-02 PSR
	 */
	public function path_exists(string|array $path) {
		return $this->pathExists($path);
	}

	/**
	 *
	 * @param $depth
	 * @return mixed
	 * @deprecated 2022-02
	 */
	public function to_array(int $depth = null): array {
		return $this->toArray($depth);
	}

	/**
	 * Convert entire structure to a list
	 *
	 * @return array
	 * @deprecated 2022-02
	 */
	public function to_list() {
		return $this->toList();
	}
}
