<?php

namespace zesk;

class Configuration implements \Iterator, \Countable, \ArrayAccess {
	const key_separator = "::";
	
	/**
	 * Path to get to this configuration location
	 */
	protected $_path = array();
	/**
	 *
	 * @var array
	 */
	protected $_data = null;
	
	/**
	 *
	 * @var boolean $locked
	 */
	protected $_locked = false;
	
	/**
	 * Current iterator index
	 *
	 * @var integer
	 */
	protected $_index;
	
	/**
	 * Number of configuration items
	 *
	 * @var integer
	 */
	protected $_count;
	
	/**
	 *
	 * @var boolean
	 */
	protected $_skip_next = false;
	
	/**
	 *
	 * @param Kernel $kernel        	
	 * @param array $value        	
	 */
	public function __construct(array $array = array(), $locked = false, array $path = array()) {
		$this->_path = $path;
		$this->_locked = $locked;
		foreach ($array as $key => $value) {
			if (is_array($value)) {
				$array[$key] = new self($value, $locked, $this->_add_path($key));
			}
		}
		$this->_data = array_change_key_case($array);
		$this->_index = 0;
		$this->_count = count($this->_data);
	}
	
	/**
	 *
	 * @param array $array        	
	 * @param string $locked        	
	 * @param array $path        	
	 * @return self
	 */
	public static function factory(array $array, $locked = false, array $path = array()) {
		return new self($array, $locked, $path);
	}
	/**
	 * Copy a configuration
	 */
	public function __clone() {
		$array = array();
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
				} else if ($overwrite) {
					$this->_data[$key] = clone $value;
				}
			} else if ($value instanceof self) {
				$this->_data[$key] = clone $value;
			} else {
				$this->_data[$key] = $value;
			}
		}
		$this->_count = count($this->_data);
		return $this;
	}
	
	/**
	 * Does this configruation value exist?
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
	 *
	 * @param string $key        	
	 * @return void
	 */
	public function __unset($key) {
		$key = strtolower($key);
		if ($this->_locked) {
			$this->_locked($key, "delete");
		}
		if (isset($this->_data[$key])) {
			unset($this->_data[$key]);
			$this->_count = count($this->_data);
		}
	}
	
	/**
	 *
	 * @param string $key        	
	 * @param string $value        	
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
	 * @param unknown $value        	
	 */
	public function __set($key, $value) {
		if ($this->_locked) {
			$this->_locked($key, "set");
		}
		if (is_array($value)) {
			$value = new self($value, $this->_locked, $this->_add_path($key));
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
	 *        	Key to retrieve
	 * @param mixed $default        	
	 * @return mixed
	 */
	public function get($key, $default = null) {
		$key = strtolower($key);
		return isset($this->_data[$key]) ? $this->_data[$key] : $default;
	}
	
	/**
	 * Given a list of paths into the configuration tree, return the first one which has a value
	 *
	 * @param array $paths        	
	 * @param mixed $default        	
	 * @return mixed
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
	 * @return \zesk\Configuration[]
	 */
	public function paths_set(array $paths) {
		$result = array();
		foreach ($paths as $path => $value) {
			$result[$path] = $this->pave_set($path, $value);
		}
		return $result;
	}
	/**
	 * Retrieve a path using self::key_separator
	 *
	 * @param string $path        	
	 * @param mixed $default        
	 * @return mixed	
	 */
	public function path_get($path, $default = null) {
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
		return $result instanceof self ? $result->to_array() : $result;
	}
	
	/**
	 * Does a path exist? 
	 *
	 * @param string $path        	
	 * @return boolean        	
	 */
	public function path_exists($path) {
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
		return isset($this->_data[$key]) ? $this->_data[$key] : null;
	}
	public function pave_set($path, $value = null) {
		// 		if (is_array($path)) {
		// 			$result = array();
		// 			foreach ($path as $k => $v) {
		// 				$result[$k] = $this->pave_set($k, $v);
		// 			}
		// 			return $result;
		// 		}
		$path = is_array($path) ? $path : explode(self::key_separator, $path);
		$key = array_pop($path);
		if (count($key) > 0) {
			$current = $this->pave($path);
			$current->$key = $value;
			return $current;
		} else {
			$this->$key = $value;
			return $this;
		}
	}
	/**
	 * Ensure configuration path is available
	 *
	 * @param array $keys        	
	 * @return self
	 */
	public function pave($keys) {
		$keys = is_array($keys) ? $keys : explode(self::key_separator, $keys);
		$current = $this;
		while (count($keys) > 0) {
			$next = array_shift($keys);
			if (!$current->$next instanceof self) {
				$current = $current->set($next, array());
			} else {
				$current = $current->$next;
			}
		}
		return $current;
	}
	/**
	 * Walk configuration
	 *
	 * @param string|list $keys        	
	 * @param mixed $default        	
	 * @return mixed
	 */
	public function walk($keys, $default = null) {
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
	public function lock() {
		$this->_locked = true;
	}
	
	/**
	 * Retrieve value
	 */
	public function value() {
		return $this->_data;
	}
	
	/**
	 * Convert entire structure to an array, recursively
	 *
	 * @return array
	 */
	public function to_array($depth = null) {
		$result = array();
		foreach ($this->_data as $key => $value) {
			if ($value instanceof self) {
				if ($depth === null || $depth > 0) {
					$result[$key] = $value->to_array($depth > 0 ? $depth - 1 : null);
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
	public function to_list() {
		$result = array();
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
	 */
	public function current() {
		$this->_skip_next = false;
		return current($this->_data);
	}
	
	/**
	 *
	 * @see Iterator
	 *
	 * @return mixed
	 */
	public function key() {
		return key($this->_data);
	}
	
	/**
	 *
	 * @see Iterator
	 */
	public function next() {
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
	public function rewind() {
		$this->_skip_next = false;
		reset($this->_data);
		$this->_index = 0;
	}
	
	/**
	 *
	 * @see Iterator
	 *
	 * @return boolean
	 */
	public function valid() {
		return $this->_index < $this->_count;
	}
	
	/**
	 *
	 * @return integer
	 */
	public function count() {
		return $this->_count;
	}
	
	/**
	 *
	 * @see ArrayAccess
	 * @param mixed $offset        	
	 * @return boolean
	 */
	public function offsetExists($offset) {
		return $this->__isset($offset);
	}
	
	/**
	 *
	 * @see ArrayAccess
	 * @param mixed $offset        	
	 */
	public function offsetGet($offset) {
		return $this->__get($offset);
	}
	
	/**
	 *
	 * @see ArrayAccess
	 * @param mixed $offset        	
	 * @param mixed $offset        	
	 */
	public function offsetSet($offset, $value) {
		$this->__set($offset, $value);
	}
	
	/**
	 *
	 * @see ArrayAccess
	 * @param mixed $offset        	
	 */
	public function offsetUnset($offset) {
		$this->__unset($offset);
	}
	
	/**
	 * Add key to existing path and return a new path
	 *
	 * @param string $key        	
	 * @return array
	 */
	private function _add_path($key) {
		return array_merge($this->_path, array(
			$key
		));
	}
	
	/**
	 * Throw exception when locked
	 *
	 * @param string $key
	 *        	Key attempting to modify
	 * @param string $verb
	 *        	Action attempting to do (debug)
	 * @throws \Exception_Lock
	 */
	private function _locked($key, $verb) {
		throw new \Exception_Lock("Unable to $verb key {key} at {path}", array(
			"key" => $key,
			"path" => $this->_path
		));
	}
	
	/**
	 * Returns true if old configuration option is still being used
	 * 
	 * @param list|string $old_path
	 * @param list|string $new_path
	 * @return boolean
	 */
	public final function deprecated($old_path, $new_path) {
		if ($this->path_exists($old_path) && !$this->path_exists($new_path)) {
			$this->pave_set($new_path, $this->path_get($old_path));
			zesk()->logger->warning("Global configuration option {old_path} is deprecated, use {new_path}", compact("old_path", "new_path"));
			\zesk::deprecated();
		}
	}
}