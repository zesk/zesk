<?php
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
		return zesk::has($key, false);
	}
	protected function _internal_get($key) {
		return array_key_exists($key, $this->_changed) ? $this->_changed[$key] : zesk::get($key);
	}
	private function _ignore_variable($key) {
		return in_array($key, $this->ignore_variables);
	}
	protected function _internal_set($key, $value) {
		if (!array_key_exists($key, $this->_changed)) {
			if (!$this->_ignore_variable($key)) {
				$this->_changed[$key] = $value;
			}
			zesk::set($key, $value);
			return $this;
		}
		$old = zesk::get($key);
		if ($old !== $value) {
			$this->_changed[$key] = $value;
			zesk::set($key, $value);
		}
		return $this;
	}
	function variables() {
		return zesk::get($this->variables);
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
		$old = zesk::get($key);
		if ($old !== null) {
			$this->_changed[$key] = null;
			zesk::set($key, null);
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