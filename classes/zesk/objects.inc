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
 * @property $session \Session_Interface
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
	 * @param Kernel $zesk        	
	 */
	public function __construct(Kernel $zesk) {
	}
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
		$low_class = strtolower($class);
		if (array_key_exists($low_class, $this->singletons)) {
			return $this->singletons[$low_class];
		}
		try {
			$rc = new ReflectionClass($class);
			if ($use_static) {
				$static_methods = array(
					"singleton",
					"instance",
					"master"
				);
				foreach ($static_methods as $method) {
					if ($rc->hasMethod($method)) {
						$method = $rc->getMethod($method);
						/* @var $method ReflectionMethod */
						if ($method->isStatic()) {
							return $this->singletons[$low_class] = $object = $method->invokeArgs(null, $arguments);
						}
					}
				}
			}
			return $this->singletons[$low_class] = $rc->newInstanceArgs($arguments);
		} catch (ReflectionException $e) {
			throw new Exception_Class_NotFound($class, null, $e);
		} catch (LogicException $e) {
			throw new Exception_Class_NotFound($class, null, $e);
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
		return $this->factory_arguments($class, $arguments);
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
		try {
			$rc = new ReflectionClass($class);
			if ($rc->isAbstract()) {
				throw new Exception_Semantics("{this_method}({class}) is abstract - can not instantiate", array(
					"this_method" => __METHOD__,
					"class" => $class
				));
			}
			return $rc->newInstanceArgs($arguments);
		} catch (ReflectionException $e) {
		} catch (LogicException $e) {
		}
		throw new Exception_Class_NotFound($class, "{method}({class}, {args}) {message}", array(
			"method" => __METHOD__,
			"class" => $class,
			"args" => array_keys($arguments),
			"message" => $e->getMessage()
		));
	}
}
