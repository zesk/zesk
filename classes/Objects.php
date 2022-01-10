<?php declare(strict_types=1);

/**
 *
 */

namespace zesk;

use \ReflectionClass;
use \ReflectionException;
use \LogicException;

/**
 * Manage object creation, singletons, and object sharing across the system
 *
 * @author kent
 * @property $session \Interface_Session
 */
class Objects {
	/**
	 *
	 * @var Interface_Settings
	 */
	public ?Interface_Settings $settings = null;

	/**
	 *
	 * @var Database[]
	 */
	public array $databases = [];

	/**
	 *
	 * @var array
	 */
	private array $singletons = [];

	/**
	 *
	 * @var array
	 */
	private array $singletons_caller = [];

	/**
	 *
	 * @var array
	 */
	private array $read_members;

	/**
	 * If value is true, allow only a single write
	 *
	 * @var boolean[member]
	 */
	private array $write_members;

	/**
	 *
	 * @var array
	 */
	private array $debug = [];

	/**
	 *
	 * @var array
	 */
	private array $mapping = [];

	/**
	 *
	 */
	public function reset(): void {
		$this->settings = null;
		$this->databases = [];
		$this->singletons = [];
		$this->singletons_caller = [];
		$this->debug = [];
		$this->mapping = [];
		$this->read_members = ["application" => true, "settings" => true, "user" => true, "session" => true, ];
		$this->write_members = ["user" => false, "application" => true, "settings" => true, "session" => true, ];
	}

	/**
	 * @return array
	 */
	public function mapping(): array {
		return $this->mapping;
	}

	/**
	 * Provide a mapping for when internal classes need to be overridden by applications.
	 * <code>
	 *
	 * </code>
	 *
	 * @param string $requested_class
	 *            When this class is requested, then ...
	 * @param string $target_class
	 *            Use this class instead
	 * @return self
	 */
	public function map(string $requested_class, string $target_class): self {
		$this->mapping[strtolower($requested_class)] = $target_class;
		return $this;
	}

	/**
	 * Set a series of mappings from $requested_class => $target_class
	 *
	 * @param iterable $iterable
	 * @return $this
	 */
	public function setMap(iterable $iterable): self {
		foreach ($iterable as $requested_class => $target_class) {
			$this->mapping[strtolower($requested_class)] = $target_class;
		}
		return $this;
	}

	/**
	 * Convert from a requested class to the target class
	 *
	 * @param string $requested_class
	 * @return string
	 */
	public function resolve(string $requested_class): string {
		return $this->mapping[strtolower($requested_class)] ?? $requested_class;
	}

	/**
	 *
	 * @param string $member
	 * @return NULL
	 * @throws Exception_Key
	 */
	public function __get(string $member): mixed {
		if (isset($this->read_members[$member])) {
			return $this->$member;
		}

		throw new Exception_Key("Unable to access {member} in {method}", ["member" => $member, "method" => __METHOD__, ]);
	}

	/**
	 *
	 * @param string $member
	 * @param mixed $value
	 * @throws Exception_Key
	 */
	public function __set(string $member, mixed $value): void {
		if (!isset($this->write_members[$member])) {
			throw new Exception_Key("Unable to set {member} in {method}", ["member" => $member, "method" => __METHOD__, ]);
		}
		if (!$this->write_members[$member]) {
			$this->$member = $value;
			return;
		}
		if (!isset($this->$member)) {
			$this->debug['first_call'][$member] = calling_function();
			$this->$member = $value;
			return;
		}

		throw new Exception_Key("Unable to write {member} a second time in {method} (first call from {first_calling_function}", ["member" => $member, "method" => __METHOD__, "first_calling_function" => $this->debug['first_call'][$member], ]);
	}

	/**
	 * Getter/setter for singletons in the system
	 *
	 * @param string $class
	 * @return object
	 */
	public function singleton(string $class): object {
		$arguments = func_get_args();
		$class = array_shift($arguments);
		return $this->singleton_arguments($class, $arguments);
	}

	/**
	 * Getter/setter for singletons in the system
	 *
	 * @param object $class
	 * @return object|self
	 */
	public function setSingleton(object $object, string $class = null): self {
		if ($class === null) {
			$class = get_class($object);
		}
		$resolved_class = "";
		$found_object = $this->_getSingleton($class, $resolved_class);
		if ($found_object) {
			if ($found_object === $object) {
				return $object;
			}

			throw new Exception_Semantics("Singletons should not change existing {found_object} !== set {object}", ["found_object" => $found_object, "object" => $found_object]);
		}
		$this->_setSingleton($object, $class);
		return $this;
	}

	/**
	 * Set a singleton
	 *
	 * @param string $class
	 * @param string $resolve_class
	 * @return ?object
	 */
	private function _getSingleton(string $class, string &$resolve_class): ?object {
		$resolve_class = $this->resolve($class);
		$low_class = strtolower($resolve_class);
		return $this->singletons[$low_class] ?? null;
	}

	/**
	 *
	 * @param mixed $object
	 * @return mixed
	 * @throws Exception_Semantics
	 */
	private function _setSingleton(object $object, string $class_name): object {
		$class = $this->resolve($class_name);
		$low_class = strtolower($class);
		if (isset($this->singletons[$low_class])) {
			throw new Exception_Semantics("{method}(Object of {class_name}) Can not set singleton {class_name} twice, originally set in {first_caller}", ["method" => __METHOD__, "class_name" => $class_name, "first_caller" => $this->singletons_caller[$low_class], ]);
		}
		$this->singletons_caller[$low_class] = calling_function(2);
		$this->singletons[$low_class] = $object;
		return $object;
	}

	/**
	 *
	 * @param string $class
	 * @param array $arguments
	 * @return object
	 * @throws Exception_Class_NotFound|Exception_Semantics
	 */
	public function singleton_arguments(string $class, array $arguments = [], $use_static = true): object {
		$resolve_class = "";
		$object = $this->_getSingleton($class, $resolve_class);
		if ($object) {
			return $object;
		}

		try {
			$rc = new ReflectionClass($resolve_class);
			if ($use_static) {
				$static_methods = ["singleton", "master", ];
				foreach ($static_methods as $method) {
					if ($rc->hasMethod($method)) {
						$refl_method = $rc->getMethod($method);
						/* @var $method ReflectionMethod */
						if ($refl_method->isStatic()) {
							return $this->_setSingleton($refl_method->invokeArgs(null, $arguments));
						}
					}
				}
			}
			return $this->_setSingleton($rc->newInstanceArgs($arguments));
		} catch (ReflectionException|LogicException $e) {
			throw new Exception_Class_NotFound($resolve_class, null, null, $e);
		}
	}

	/**
	 * Create a new class based on name
	 *
	 * @param string $class
	 * @return object
	 * @throws Exception
	 */
	public function factory(string $class): object {
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
	 * @throws Exception_Semantics|Exception_Class_NotFound|Exception_Parameter
	 */
	public function factory_arguments(string $class, array $arguments): object {
		$resolve_class = $this->resolve($class);

		try {
			$rc = new ReflectionClass($resolve_class);
			if ($rc->isAbstract()) {
				throw new Exception_Semantics("{this_method}({class} => {resolve_class}) is abstract - can not instantiate", ["this_method" => __METHOD__, "class" => $class, "resolve_class" => $resolve_class, ]);
			}
			return $rc->newInstanceArgs($arguments);
		} catch (LogicException|ReflectionException $e) {
		}

		throw new Exception_Class_NotFound($class, "{method}({class} => {resolve_class}, {args}) {message}\nBacktrace: {backtrace}", ["method" => __METHOD__, "class" => $class, "resolve_class" => $resolve_class, "args" => array_keys($arguments), "message" => $e->getMessage(), "backtrace" => $e->getTraceAsString(), ]);
	}
}
