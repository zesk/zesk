<?php
/**
 * @copyright &copy; 2016 Market Acumen, Inc.
 */
namespace zesk;

/**
 * 
 * @author kent
 *
 */
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
	 * @var Configuration
	 */
	protected $configuration = null;
	
	/**
	 * 
	 * @var array
	 */
	protected $state = array();
	/**
	 * 
	 * {@inheritDoc}
	 */
	public function hook_construct() {
		$this->configuration = $this->application->configuration;
		$this->inherit_global_options();
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
	/**
	 * 
	 * @param mixed $mixed
	 */
	public function allow_variable($mixed = null) {
		if ($mixed === null) {
			return $this->variables;
		}
		$mixed = to_list($mixed);
		foreach ($mixed as $item) {
			if (!in_array($item, $this->variables)) {
				$this->variables[$item] = null;
				if ($this->option_bool("debug_variables")) {
					$this->application->logger->debug("Adding permitted {variable} to {class}", array(
						"variable" => $item,
						"class" => get_class($this)
					));
				}
			}
		}
		return $this;
	}
	function __isset($key) {
		if (array_key_exists($key, $this->_changed)) {
			return true;
		}
		if (array_key_exists($key, $this->variables)) {
			return $this->application->configuration->path_exists($key, false);
		}
		return isset($this->state[$key]);
	}
	private function _ignore_variable($key) {
		return in_array($key, $this->ignore_variables);
	}
	protected function _internal_set($key, $value) {
		$old = $this->_internal_get($key);
		if ($value === $old) {
			return;
		}
		// Value has definitely changed
		if (!$this->_ignore_variable($key)) {
			if ($this->option_bool("debug_changes")) {
				$this->application->logger->debug("{method} new value for key {key} {new_value} (old value was {old_value})", array(
					"key" => $key,
					"old_value" => $old,
					"new_value" => $value,
					"method" => __METHOD__
				));
			}
			$this->_changed[$key] = $value;
		} else if (array_key_exists($key, $this->variables)) {
			$this->configuration->path_set($key, $value);
		} else {
			if ($this->option_bool("debug_variables")) {
				$this->application->logger->warning("{method} STATE ONLY value for key {key} {new_value} (old value was {old_value})", array(
					"key" => $key,
					"old_value" => $old,
					"new_value" => $value,
					"method" => __METHOD__
				));
			}
			$this->state[$key] = $value;
		}
		return $this;
	}
	
	/**
	 * Get a value from this model
	 * 
	 * @param string $key
	 * @return mixed
	 */
	protected function _internal_get($key) {
		if (array_key_exists($key, $this->_changed)) {
			return $this->_changed[$key];
		}
		if (array_key_exists($key, $this->variables)) {
			$result = $this->configuration->path_get($key);
			return $result instanceof Configuration ? $result->to_array() : $result;
		}
		$this->application->logger->debug("{variable} not permitted in {class}, using local state instead", array(
			"variable" => $key,
			"class" => get_class($this)
		));
		return avalue($this->state, $key);
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
		$this->__set($key, null);
	}
	function store() {
		$this->application->logger->debug("{method} called", array(
			"method" => __METHOD__
		));
		$settings = Settings::instance();
		foreach ($this->_changed as $key => $value) {
			if ($this->option_bool("debug_save")) {
				$this->application->logger->debug("{method} Saving {key}={value} ({type})", array(
					"method" => __METHOD__,
					"key" => $key,
					"value" => PHP::dump($value),
					"type" => type($value)
				));
			}
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
					return $this->_access_cache[$name] = ORM::factory($class, $value)->fetch();
				} catch (Exception_ORM_NotFound $e) {
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
