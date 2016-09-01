<?php

/**
 * 
 */
namespace zesk;

use \ReflectionClass;
use \Exception_Parameter;
use \Exception_Semantics;
use \ReflectionException;
use \LogicException;
use \Exception_Class_NotFound;

/**
 * Manage object creation, singletons, and object sharing 
 * 
 * @author kent
 *
 */
class Objects {
	
	/**
	 *
	 * @var Application
	 */
	public $application = null;
	
	/**
	 *
	 * @var \Database[]
	 */
	public $databases = array();
	
	/**
	 *
	 * @var User
	 */
	public $user = null;
	
	/**
	 *
	 * @var \Session_Interface
	 */
	public $session = null;
	
	/**
	 * 
	 * @var array
	 */
	private $singletons = array();
	
	/**
	 *
	 * @param Kernel $zesk        	
	 */
	public function __construct(Kernel $zesk) {
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
	public function singleton_arguments($class, array $arguments = array()) {
		if (!is_string($class)) {
			throw new \Exception_Parameter("{class}::factory({arg_class}) not a class name", array(
				"class" => __CLASS__,
				"arg_class" => $class
			));
		}
		$low_class = strtolower($class);
		if (array_key_exists($low_class, $this->singletons)) {
			return $this->singletons[$low_class];
		}
		try {
			$static_methods = array(
				"singleton",
				"instance",
				"master"
			);
			$rc = new ReflectionClass($class);
			foreach ($static_methods as $method) {
				if ($rc->hasMethod($method)) {
					$method = $rc->getMethod($method);
					/* @var $method ReflectionMethod */
					if ($method->isStatic()) {
						return $this->singletons[$low_class] = $object = $method->invokeArgs(null, $arguments);
					}
				}
			}
			return $this->singletons[$low_class] = $rc->newInstance();
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
			throw new \Exception_Parameter("{method}({class}) not a class name", array(
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
