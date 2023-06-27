<?php
declare(strict_types=1);
/**
 *
 */

namespace zesk\Application;

use LogicException;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;
use stdClass;
use zesk\Exception;
use zesk\Exception\ClassNotFound;
use zesk\Exception\SemanticsException;
use zesk\Hookable;
use zesk\Kernel;
use zesk\PHP;

/**
 * Manage object creation, singletons, and object sharing across the system
 *
 * @author kent
 * @property $session \SessionInterface
 */
class Objects
{
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
	private array $mapping = [];

	/**
	 *
	 */
	public function reset(): void
	{
		$this->singletons = [];
		$this->singletonsCaller = [];
		$this->mapping = [];
	}

	public function shutdown(): void
	{
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
	 * @return array:Hookable
	 */
	public function hookables(): array
	{
		return array_filter($this->singletons, fn ($object) => $object instanceof Hookable);
	}

	/**
	 * @return array
	 */
	public function mapping(): array
	{
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
	public function map(string $requested_class, string $target_class): self
	{
		$this->mapping[strtolower($requested_class)] = $target_class;
		return $this;
	}

	/**
	 * Set a series of mappings from $requested_class => $target_class
	 *
	 * @param iterable $iterable
	 * @return $this
	 */
	public function setMap(iterable $iterable): self
	{
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
	public function resolve(string $requested_class): string
	{
		return $this->mapping[strtolower($requested_class)] ?? $requested_class;
	}

	/**
	 * Get singleton of class, by default uses the static `singleton` method.
	 *
	 * @param string $class
	 * @return object
	 * @throws ClassNotFound
	 */
	public function singleton(string $class): object
	{
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
	 * @throws SemanticsException
	 */
	public function setSingleton(object $object, string $class = null): self
	{
		if ($class === null) {
			$class = $object::class;
		}
		$resolved_class = '';
		$found_object = $this->_getSingleton($class, $resolved_class);
		if ($found_object) {
			if ($found_object === $object) {
				return $this;
			}

			throw new SemanticsException('Singletons should not change existing {found_object} !== set {object}', [
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
	private function _getSingleton(string $class, string &$resolveClass): ?object
	{
		$resolveClass = $this->resolve($class);
		return $this->singletons[$resolveClass] ?? null;
	}

	/**
	 * Set a singleton
	 *
	 * @param mixed $object
	 * @return mixed
	 * @throws SemanticsException
	 */
	private function _setSingleton(object $object, string $resolvedClass): object
	{
		if (isset($this->singletons[$resolvedClass])) {
			throw new SemanticsException('{method}(Object of {class_name}) Can not set singleton {class_name} twice, originally set in {first_caller}', [
				'method' => __METHOD__, 'class_name' => $resolvedClass,
				'first_caller' => $this->singletonsCaller[$resolvedClass],
			]);
		}
		$this->singletonsCaller[$resolvedClass] = Kernel::callingFunction(2);
		$this->singletons[$resolvedClass] = $object;
		return $object;
	}

	/**
	 *
	 * @param string $class
	 * @param array $arguments
	 * @param array $staticMethods List of static methods to look for in class.
	 * @return mixed
	 * @throws ClassNotFound
	 */
	public function singletonArgumentsStatic(string $class, array $arguments = [], array $staticMethods = ['singleton']): mixed
	{
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

			throw new ClassNotFound($resolveClass);
		} catch (ReflectionException|LogicException $e) {
			throw new ClassNotFound($resolveClass, '', [], $e);
		} catch (SemanticsException $e) {
			/* NEVER from _setSingleton */
			throw new ClassNotFound($resolveClass, 'Semantics SHOULD NOT HAPPEN as getSingleton returned
			null', [], $e);
		}
	}

	/**
	 *
	 * @param string $class
	 * @param array $arguments
	 * @return mixed
	 * @throws ClassNotFound
	 */
	public function singletonArguments(string $class, array $arguments = []): mixed
	{
		$resolveClass = '';
		$object = $this->_getSingleton($class, $resolveClass);
		if ($object) {
			return $object;
		}

		try {
			$rc = new ReflectionClass($resolveClass);
			return $this->_setSingleton($rc->newInstanceArgs($arguments), $resolveClass);
		} catch (ReflectionException|LogicException $e) {
			throw new ClassNotFound($resolveClass, '', [], $e);
		} catch (SemanticsException $e) {
			/* NEVER from _setSingleton */
			throw new ClassNotFound($resolveClass, 'Semantics SHOULD NOT HAPPEN as getSingleton returned
			null', [], $e);
		}
	}

	/**
	 * Create a new class based on name
	 *
	 * @param string $class
	 * @return object
	 * @throws ClassNotFound
	 */
	public function factory(string $class): object
	{
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
	 * @throws ClassNotFound
	 */
	public function factoryArguments(string $class, array $arguments): object
	{
		$resolveClass = $this->resolve($class);

		try {
			$rc = new ReflectionClass($resolveClass);
			if ($rc->isAbstract()) {
				throw new ClassNotFound($resolveClass, '{this_method}({class} => {resolve_class}) is abstract - can not instantiate', [
					'this_method' => __METHOD__, 'class' => $class, 'resolve_class' => $resolveClass,
				]);
			}
			return $rc->newInstanceArgs($arguments);
		} catch (LogicException|ReflectionException $e) {
		}

		throw new ClassNotFound($class, "{method}({class} => {resolve_class}, {args}) {message}\nBacktrace: {backtrace}", [
			'method' => __METHOD__, 'class' => $class, 'resolve_class' => $resolveClass,
			'args' => array_keys($arguments), 'message' => $e->getMessage(), 'backtrace' => $e->getTraceAsString(),
		]);
	}
}
