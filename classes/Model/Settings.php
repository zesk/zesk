<?php
/**
 * @copyright &copy; 2016 Market Acumen, Inc.
 */
namespace zesk;

class Model_Settings extends Model {
	protected $_changed = array();
	protected $_accessor = array();
	protected $_access_cache = array();
	protected $ignore_variables = array();
	
	/**
	 * Array of key => default
	 * 
	 * @var array $variables
	 */
	protected $variables = array();
	public function construct() {
		$this->configuration = $this->application->configuration;
	}
	
	/**
	 * 
	 * @param mixed $mixed
	 */
	public function ignore_variable($mixed = null) {
		if ($mixed === null) {
			return $this->ignore_variables;
		}
		$mixed = to_list($mixed);
		foreach ($mixed as $item) {
			if (!in_array($item, $this->ignore_variables)) {
				$this->ignore_variables[] = $item;
			}
		}
		return $this;
	}
	function __isset($key) {
		return $this->application->configuration->path_exists($key, false);
	}
	protected function _internal_get($key) {
		if (array_key_exists($key, $this->_changed)) {
			return $this->_changed[$key];
		}
		$result = $this->configuration->path_get($key);
		return $result instanceof Configuration ? $result->to_array() : $result;
	}
	private function _ignore_variable($key) {
		return in_array($key, $this->ignore_variables);
	}
	protected function _internal_set($key, $value) {
		if (!array_key_exists($key, $this->_changed)) {
			if (!$this->_ignore_variable($key)) {
				$this->_changed[$key] = $value;
			}
			$this->configuration->path_get($key, $value);
			return $this;
		}
		$old = $this->configuration->path_get($key);
		if ($old !== $value) {
			$this->_changed[$key] = $value;
			$this->configuration->pave_set($key, $value);
		}
		return $this;
	}
	function variables() {
		$result = $this->configuration->paths_get($this->variables);
		foreach ($result as $k => $v) {
			if ($v instanceof Configuration) {
				$result[$k] = to_array($v->to_array());
			}
		}
		return $result;
	}
	function __get($key) {
		if (array_key_exists($key, $this->_accessor)) {
			return call_user_func(array(
				$this,
				$this->_accessor[$key]
			));
		}
		return $this->_internal_get($key);
	}
	function __set($key, $value) {
		if (array_key_exists($key, $this->_accessor)) {
			call_user_func(array(
				$this,
				$this->_accessor[$key]
			), $value);
			return $this;
		}
		return $this->_internal_set($key, $value);
	}
	function __unset($key) {
		$old = $this->__get($key);
		if ($old !== null) {
			$this->_changed[$key] = null;
			$this->__set($key, null);
		}
	}
	function store() {
		$settings = Settings::instance();
		foreach ($this->_changed as $key => $value) {
			$settings->set($key, $value);
		}
		$this->_changed = array();
		return parent::store();
	}
	function access_class_member($name, $class, $set = null) {
		if ($set === null) {
			if (array_key_exists($name, $this->_access_cache)) {
				return $this->_access_cache[$name];
			}
			$value = $this->_internal_get($name);
			if (is_numeric($value) && intval($value) !== 0) {
				try {
					return $this->_access_cache[$name] = Object::factory($class, $value)->fetch();
				} catch (Exception_Object_NotFound $e) {
				}
				return $this->_access_cache[$name] = null;
			}
		}
		if ($set instanceof $class) {
			$this->_access_cache[$name] = $set;
			return $this->_internal_set($name, $set->id());
		}
		unset($this->_access_cache[$name]);
		return $this->_internal_set($name, $set);
	}
}
