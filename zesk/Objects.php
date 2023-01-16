<?php
declare(strict_types=1);

/**
 *
 */

namespace zesk;

use ReflectionClass;
use ReflectionException;
use LogicException;
use ReflectionMethod;
use stdClass;

/**
 * Manage object creation, singletons, and object sharing across the system
 *
 * @author kent
 * @property $session \Interface_Session
 */
class Objects {
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
	private array $singletonsCaller = [];

	/**
	 *
	 * @var array
	 */
	private array $read_members;

	/**
	 * If value is true, allow only a single write
	 *
	 * @var array
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
		$this->databases = [];
		$this->singletons = [];
		$this->singletonsCaller = [];
		$this->debug = [];
		$this->mapping = [];
		$this->read_members = ['application' => true, 'settings' => true, 'user' => true, 'session' => true, ];
		$this->write_members = ['user' => false, 'application' => true, 'settings' => true, 'session' => true, ];
	}

	public function shutdown(): void {
		foreach ($this->singletons as $singleton) {
			try {
				if (method_exists($singleton, 'shutdown')) {
					$singleton->shutdown();
				}
			} catch (\Exception $e) {
				PHP::log('{method} {message}}', ['method' => __METHOD__] + Exception::exceptionVariables($e));
			}
		}
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

		throw new Exception_Key('Unable to access {member} in {method}', [
			'member' => $member, 'method' => __METHOD__,
		]);
	}

	/**
	 *
	 * @param string $member
	 * @param mixed $value
	 * @throws Exception_Key
	 */
	public function __set(string $member, mixed $value): void {
		if (!isset($this->write_members[$member])) {
			throw new Exception_Key('Unable to set {member} in {method}', [
				'member' => $member, 'method' => __METHOD__,
			]);
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

		throw new Exception_Key('Unable to write {member} a second time in {method} (first call from {first_calling_function}', [
			'member' => $member, 'method' => __METHOD__,
			'first_calling_function' => $this->debug['first_call'][$member],
		]);
	}

	/**
	 * Get singleton of class, by default uses the static `singleton` method.
	 *
	 * @param string $class
	 * @return object
	 * @throws Exception_Class_NotFound
	 */
	public function singleton(string $class): object {
		$arguments = func_get_args();
		$class = array_shift($arguments);
		return $this->singletonArgumentsStatic($class, $arguments);
	}

	/**
	 * Set singleton of class
	 *
	 * @param object $object
	 * @param string|null $class The resolved class to save this class under.
	 * @return $this
	 * @throws Exception_Semantics
	 */
	public function setSingleton(object $object, string $class = null): self {
		if ($class === null) {
			$class = $object::class;
		}
		$resolved_class = '';
		$found_object = $this->_getSingleton($class, $resolved_class);
		if ($found_object) {
			if ($found_object === $object) {
				return $this;
			}

			throw new Exception_Semantics('Singletons should not change existing {found_object} !== set {object}', [
				'found_object' => $found_object, 'object' => $found_object,
			]);
		}
		$this->_setSingleton($object, $resolved_class);
		return $this;
	}

	/**
	 * Get a singleton
	 *
	 * @param string $class
	 * @param string $resolveClass
	 * @return ?object
	 */
	private function _getSingleton(string $class, string &$resolveClass): ?object {
		$resolveClass = $this->resolve($class);
		return $this->singletons[$resolveClass] ?? null;
	}

	/**
	 * Set a singleton
	 *
	 * @param mixed $object
	 * @return mixed
	 * @throws Exception_Semantics
	 */
	private function _setSingleton(object $object, string $resolvedClass): object {
		if (isset($this->singletons[$resolvedClass])) {
			throw new Exception_Semantics('{method}(Object of {class_name}) Can not set singleton {class_name} twice, originally set in {first_caller}', [
				'method' => __METHOD__, 'class_name' => $resolvedClass,
				'first_caller' => $this->singletonsCaller[$resolvedClass],
			]);
		}
		$this->singletonsCaller[$resolvedClass] = calling_function(2);
		$this->singletons[$resolvedClass] = $object;
		return $object;
	}

	/**
	 *
	 * @param string $class
	 * @param array $arguments
	 * @param array $staticMethods List of static methods to look for in class.
	 * @return mixed
	 * @throws Exception_Class_NotFound
	 */
	public function singletonArgumentsStatic(string $class, array $arguments = [], array $staticMethods = ['singleton']): mixed {
		$resolveClass = '';
		$object = $this->_getSingleton($class, $resolveClass);
		if ($object) {
			return $object;
		}

		try {
			$rc = new ReflectionClass($resolveClass);
			foreach ($staticMethods as $method) {
				if ($rc->hasMethod($method)) {
					$reflectionMethod = $rc->getMethod($method);
					/* @var $method ReflectionMethod */
					if ($reflectionMethod->isStatic()) {
						return $this->_setSingleton($reflectionMethod->invokeArgs(null, $arguments), $resolveClass);
					}
				}
			}

			throw new Exception_Class_NotFound($resolveClass);
		} catch (ReflectionException|LogicException $e) {
			throw new Exception_Class_NotFound($resolveClass, '', [], $e);
		} catch (Exception_Semantics $e) {
			/* NEVER from _setSingleton */
			throw new Exception_Class_NotFound($resolveClass, 'Semantics SHOULD NOT HAPPEN as getSingleton returned
			null', [], $e);
		}
	}

	/**
	 *
	 * @param string $class
	 * @param array $arguments
	 * @return mixed
	 * @throws Exception_Class_NotFound
	 */
	public function singletonArguments(string $class, array $arguments = []): mixed {
		$resolveClass = '';
		$object = $this->_getSingleton($class, $resolveClass);
		if ($object) {
			return $object;
		}

		try {
			$rc = new ReflectionClass($resolveClass);
			return $this->_setSingleton($rc->newInstanceArgs($arguments), $resolveClass);
		} catch (ReflectionException|LogicException $e) {
			throw new Exception_Class_NotFound($resolveClass, '', [], $e);
		} catch (Exception_Semantics $e) {
			/* NEVER from _setSingleton */
			throw new Exception_Class_NotFound($resolveClass, 'Semantics SHOULD NOT HAPPEN as getSingleton returned
			null', [], $e);
		}
	}

	/**
	 * Create a new class based on name
	 *
	 * @param string $class
	 * @return object
	 * @throws Exception_Class_NotFound
	 */
	public function factory(string $class): object {
		$arguments = func_get_args();
		array_shift($arguments);
		return $this->factoryArguments($class, $arguments);
	}

	/**
	 * Create a new class based on name. All class names are resolved here.
	 *
	 * @param string $class
	 * @param array $arguments
	 * @return stdClass
	 * @throws Exception_Class_NotFound
	 */
	public function factoryArguments(string $class, array $arguments): object {
		$resolveClass = $this->resolve($class);

		try {
			$rc = new ReflectionClass($resolveClass);
			if ($rc->isAbstract()) {
				throw new Exception_Class_NotFound($resolveClass, '{this_method}({class} => {resolve_class}) is abstract - can not instantiate', [
					'this_method' => __METHOD__, 'class' => $class, 'resolve_class' => $resolveClass,
				]);
			}
			return $rc->newInstanceArgs($arguments);
		} catch (LogicException|ReflectionException $e) {
		}

		throw new Exception_Class_NotFound($class, "{method}({class} => {resolve_class}, {args}) {message}\nBacktrace: {backtrace}", [
			'method' => __METHOD__, 'class' => $class, 'resolve_class' => $resolveClass,
			'args' => array_keys($arguments), 'message' => $e->getMessage(), 'backtrace' => $e->getTraceAsString(),
		]);
	}
}
