<?php
/**
 * Model is a generic object which can be accessed by other objects.
 * Used in the Widget system.
 * It can be accessed as an array as well.
 * @version $URL$
 * @author kent
 * @copyright &copy; 2015 Market Acumen, Inc.
 */

/**
 * Our basic model.
 *
 * @author kent
 */
class Model extends Hookable implements ArrayAccess {
	protected $_inited = false;
	
	/**
	 * 
	 * @var Application
	 */
	public $application = null;
	function __construct($mixed = null, $options = null, Application $application = null) {
		$this->application = $application ? $application : Application::instance();
		if (is_array($mixed)) {
			foreach ($mixed as $k => $v) {
				$this->set($k, $v);
			}
		}
		parent::__construct($options);
	}
	
	/**
	 *
	 * @param string $class
	 * @param mixed $value
	 * @throws Exception_Semantics
	 * @return Model
	 */
	public static function factory($class, $value = null, $options = null, Application $application = null) {
		$object = zesk::factory($class, $value, $options, $application);
		if (!$object instanceof Model) {
			throw new Exception_Semantics("Class $class is not of type Model");
		}
		if (is_array($value)) {
			foreach ($value as $k => $v) {
				$object->__set($k, $v);
			}
		} else if (is_scalar($value)) {
			$object->__set("content", $value);
		}
		return $object;
	}
	function is_new() {
		return $this->inited();
	}
	/**
	 * Variables to apply to a template, for example
	 *
	 * @return array
	 */
	function variables() {
		$result = array();
		foreach (get_object_vars($this) as $k => $v) {
			if ($k[0] !== '_') {
				$result[$k] = $v;
			}
		}
		return $result;
	}
	
	/**
	 * Convert values in this object with map
	 */
	function map(array $map) {
		$this->set(map($this->variables(), $map));
		return $this;
	}
	
	/**
	 * Convert other values using this Model as the map
	 */
	final function apply_map($mixed) {
		if ($mixed instanceof Model) {
			return $mixed->map($this->variables());
		}
		if (is_bool($mixed) || is_null($mixed) || is_numeric($mixed) || is_resource($mixed)) {
			return $mixed;
		}
		return map($mixed, $this->variables());
	}
	
	/**
	 * ArrayAccess offsetExists
	 * @param mixed $offset
	 * @return boolean
	 */
	public function offsetExists($offset) {
		return $this->__isset($offset);
	}
	
	/**
	 * ArrayAccess offsetGet
	 * @param mixed $offset
	 * @return mixed
	 */
	public function offsetGet($offset) {
		return $this->__get($offset);
	}
	
	/**
	 * ArrayAccess offsetSet
	 * @param mixed $offset
	 * @param mixed $value
	 * @return void
	 */
	public function offsetSet($offset, $value) {
		$this->__set($offset, $value);
	}
	
	/**
	 * ArrayAccess offsetUnset
	 * @param mixed $offset
	 * @return void
	 */
	public function offsetUnset($offset) {
		$this->__unset($offset);
	}
	
	/**
	 *
	 * @return boolean
	 */
	public function store() {
		return $this;
	}
	
	/**
	 *
	 * @param mixed $mixed Settings to retrieve a model from somewhere
	 * @return Model Or null if can not be found
	 */
	function fetch($mixed = null) {
		return $this;
	}
	
	/**
	 *
	 * @param mixed $mixed Model value to retrieve
	 * @param mixed $default Value to return if not found
	 * @return mixed
	 */
	public function get($mixed = null, $default = null) {
		if (is_array($mixed)) {
			$result = array();
			foreach ($mixed as $k => $v) {
				$result[$k] = $this->get($k, $v);
			}
			return $result;
		} else if (!is_scalar($mixed)) {
			throw new Exception_Parameter("Not sure how to handle type " . gettype($mixed));
		}
		if (!$this->__isset($mixed)) {
			return $default;
		}
		return $this->__get($mixed);
	}
	public function has($mixed = null) {
		if (is_array($mixed)) {
			foreach ($mixed as $k) {
				if ($this->has($k)) {
					return true;
				}
			}
			return false;
		}
		if (is_scalar($mixed)) {
			return $this->__isset($mixed);
		}
		return false;
	}
	
	/**
	 * Returns true if model has been initialized with valid values
	 *
	 * @return boolean
	 */
	public function inited() {
		return $this->_inited;
	}
	
	/**
	 *
	 * @param mixed $mixed Model value to set
	 * @param mixed $value Value to set
	 * @return Model $this
	 */
	public function set($mixed, $value = null) {
		if (is_array($mixed)) {
			foreach ($mixed as $k => $v) {
				$this->set($k, $v);
			}
		} else if (is_object($mixed)) {
			$this->set(get_class($mixed), $mixed);
		} else if (is_scalar($mixed) && !empty($mixed)) {
			$this->__set($mixed, $value);
		} else {
			throw new Exception_Parameter("Model::set({mixed}, {value}) Not sure how to handle 1st parameter", array(
				"mixed" => json::encode($mixed),
				"value" => php::dump($value)
			));
		}
		return $this;
	}
	public function __toString() {
		return php::dump($this->options);
	}
	
	/*
	 * Only place to access ->$name is here
	 */
	public function __get($name) {
		if (isset($this->$name)) {
			return $this->$name;
		}
		return null;
	}
	public function __set($name, $value) {
		$this->$name = $value;
		$this->_inited = true;
	}
	public function __unset($name) {
		unset($this->$name);
	}
	public function __isset($name) {
		return isset($this->$name);
	}
	public function json(array $options = array()) {
		return json::encode($this->variables());
	}
}
