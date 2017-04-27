<?php

/**
 * 
 */
namespace zesk;

use \ReflectionClass;
use \ReflectionException;
use \LogicException;

/**
 * Manage object creation, singletons, and object sharing 
 * 
 * @author kent
 * @property $session \Interface_Session
 */
class Objects {
	
	/**
	 *
	 * @var Application
	 */
	private $application = null;
	
	/**
	 *
	 * @var Interface_Settings
	 */
	private $settings = null;
	
	/**
	 *
	 * @var \Database[]
	 */
	public $databases = array();
	
	/**
	 *
	 * @var User
	 */
	private $user = null;
	
	/**
	 *
	 * @var \Interface_Session
	 */
	private $session = null;
	
	/**
	 * 
	 * @var array
	 */
	private $singletons = array();
	
	/**
	 * 
	 * @var array
	 */
	private $read_members = array(
		"application" => true,
		"settings" => true,
		"user" => true,
		"session" => true
	);
	
	/**
	 * If value is true, allow only a single write
	 * 
	 * @var boolean[member]
	 */
	private $write_members = array(
		"user" => false,
		"application" => true,
		"settings" => true,
		"session" => true
	);
	
	/**
	 * @var array 
	 */
	private $debug = array();
	
	/**
	 * 
	 * @var array
	 */
	private $mapping = array();
	/**
	 *
	 * @param Kernel $zesk        	
	 */
	public function __construct(Kernel $zesk) {
	}
	
	public function reset() {
		$this->settings = null;
		$this->databases = array();
		$this->user = null;
		$this->session = null;
		$this->singletons = array();
		$this->debug = array();
		$this->mapping = array();
	}
	/**
	 * Provide a mapping for when internal classes need to be overridden by applications.
	 * <code>
	 * 
	 * </code>
	 * @param string $requested_class When this class is requested, then ...
	 * @param string $target_class Use this class instead
	 * @return \zesk\Objects
	 */
	public function map($requested_class, $target_class = null) {
		if (is_array($requested_class)) {
			foreach ($requested_class as $requested => $target) {
				$this->mapping[strtolower($requested)] = $target;
			}
			return $this;
		}
		if (!is_string($target_class)) {
			throw new Exception_Parameter("target_class must be a string: {type} {value}", array(
				"type" => type($target_class),
				"value" => $target_class
			));
		}
		$this->mapping[strtolower($requested_class)] = $target_class;
		return $this;
	}
	
	/**
	 * Convert from a requested class to the target class
	 * 
	 * @param string $requested_class
	 * @return string
	 */
	function resolve($requested_class) {
		return avalue($this->mapping, strtolower($requested_class), $requested_class);
	}
	
	/**
	 * 
	 * @param unknown $member
	 * @throws Exception_Key
	 * @return NULL
	 */
	public function __get($member) {
		if (isset($this->read_members[$member])) {
			return $this->$member;
		}
		throw new Exception_Key("Unable to access {member} in {method}", array(
			"member" => $member,
			"method" => __METHOD__
		));
		return null;
	}
	
	/**
	 * 
	 * @param string $member
	 * @param mixed $value
	 * @throws Exception_Key
	 */
	public function __set($member, $value) {
		if (!isset($this->write_members[$member])) {
			throw new Exception_Key("Unable to set {member} in {method}", array(
				"member" => $member,
				"method" => __METHOD__
			));
		}
		if ($this->write_members[$member]) {
			if (!isset($this->$member)) {
				$this->debug['first_call'][$member] = calling_function();
				$this->$member = $value;
				return;
			}
			throw new Exception_Key("Unable to write {member} a second time in {method} (first call from {first_calling_function}", array(
				"member" => $member,
				"method" => __METHOD__,
				"first_calling_function" => $this->debug['first_call'][$member]
			));
		} else {
			$this->$member = $value;
		}
	}
	/**
	 * 
	 * @param string $class
	 * @return object
	 */
	public function singleton($class) {
		$arguments = func_get_args();
		$class = array_shift($arguments);
		return $this->singleton_arguments($class, $arguments);
	}
	
	/**
	 * 
	 * @param string $class
	 * @param array $arguments
	 * @return object
	 */
	public function singleton_arguments($class, array $arguments = array(), $use_static = true) {
		if (!is_string($class)) {
			throw new Exception_Parameter("{class}::factory({arg_class}) not a class name", array(
				"class" => __CLASS__,
				"arg_class" => $class
			));
		}
		$resolve_class = $this->resolve($class);
		$low_class = strtolower($resolve_class);
		if (array_key_exists($low_class, $this->singletons)) {
			return $this->singletons[$low_class];
		}
		try {
			$rc = new ReflectionClass($resolve_class);
			if ($use_static) {
				$static_methods = array(
					"singleton",
					"instance",
					"master"
				);
				foreach ($static_methods as $method) {
					if ($rc->hasMethod($method)) {
						$refl_method = $rc->getMethod($method);
						/* @var $method ReflectionMethod */
						if ($refl_method->isStatic()) {
							if ($method === "instance") {
								zesk()->deprecated("$resolve_class::$method will no longer be allowed for singleton creation");
							}
							return $this->singletons[$low_class] = $object = $refl_method->invokeArgs(null, $arguments);
						}
					}
				}
			}
			return $this->singletons[$low_class] = $rc->newInstanceArgs($arguments);
		} catch (ReflectionException $e) {
			throw new Exception_Class_NotFound($resolve_class, null, null, $e);
		} catch (LogicException $e) {
			throw new Exception_Class_NotFound($resolve_class, null, null, $e);
		}
	}
	/**
	 * Create a new class based on name
	 *
	 * @param string $class
	 * @return stdClass
	 * @throws Exception
	 */
	public function factory($class) {
		$arguments = func_get_args();
		array_shift($arguments);
		return $this->factory_arguments($this->resolve($class), $arguments);
	}
	
	/**
	 * Create a new class based on name
	 *
	 * @param string $class
	 * @param array $arguments
	 * @return stdClass
	 * @throws Exception
	 */
	public function factory_arguments($class, array $arguments) {
		if (!is_string($class)) {
			throw new Exception_Parameter("{method}({class}) not a class name", array(
				"method" => __METHOD__,
				"class" => _dump($class)
			));
		}
		$resolve_class = $this->resolve($class);
		try {
			$rc = new ReflectionClass($resolve_class);
			if ($rc->isAbstract()) {
				throw new Exception_Semantics("{this_method}({class} => {resolve_class}) is abstract - can not instantiate", array(
					"this_method" => __METHOD__,
					"class" => $class,
					"resolve_class" => $resolve_class
				));
			}
			return $rc->newInstanceArgs($arguments);
		} catch (ReflectionException $e) {
		} catch (LogicException $e) {
		}
		throw new Exception_Class_NotFound($class, "{method}({class} => {resolve_class}, {args}) {message}\nBacktrace: {backtrace}", array(
			"method" => __METHOD__,
			"class" => $class,
			"resolve_class" => $resolve_class,
			"args" => array_keys($arguments),
			"message" => $e->getMessage(),
			"backtrace" => $e->getTraceAsString()
		));
	}
}
