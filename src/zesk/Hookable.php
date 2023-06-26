<?php
declare(strict_types=1);
/**
 * @package zesk
 * @subpackage kernel
 * @author kent
 * @copyright &copy; 2023, Market Acumen, Inc.
 */

namespace zesk;

use Closure;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;
use zesk\Exception\DirectoryNotFound;
use zesk\Exception\ParameterException;

/**
 *
 * @todo When we're in a PHP version which is trait compatible, make this a trait
 *
 * @author kent
 */
class Hookable extends Options {
	public Application $application;

	/**
	 *
	 * @param Application $application
	 * @param array $options
	 */
	public function __construct(Application $application, array $options = []) {
		$this->application = $application;
		parent::__construct($options);
		// Decided to NOT place a ->initialize() call here, largely because subclasses who override
		// the constructor of this class need to control the ordering of their initialization such that any method
		// called is operating on initialized object state
	}

	/**
	 * @param Application $application
	 * @return void
	 */
	private static function loadApplication(Application $application): void {
		try {
			$newIncluded = false;
			foreach ($application->hookSources() as $path) {
				if (PHP::includePath($path)) {
					$newIncluded = true;
				}
			}
			if ($newIncluded) {
				$application->classes->register(get_declared_classes());
			}
		} catch (DirectoryNotFound|ParameterException $e) {
			// "Never"
			throw new RuntimeException('Directory {path} not found - someone deleted the source code?', [
				'path' => $application->path(),
			], 0, $e);
		}
	}

	/**
	 * Finds static methods in any object in the system with the class attribute attached
	 *
	 * *** Globally within the application ***
	 *
	 * @param Hookable $hookable
	 * @param string $attributeClassName
	 * @return array:HookableAttribute
	 */
	public static function staticAttributeMethods(Hookable $hookable, string $attributeClassName): array {
		$app = $hookable->application;
		self::loadApplication($app);
		$results = [];
		$flags = ReflectionMethod::IS_PUBLIC | ReflectionMethod::IS_STATIC;
		foreach ($app->classes->subclasses(self::class) as $className) {
			try {
				$reflectionClass = new ReflectionClass($className);
				foreach ($reflectionClass->getMethods($flags) as $method) {
					if (!$method->isStatic()) {
						continue;
					}
					$declaredClassName = $method->getDeclaringClass()->getName();
					if ($declaredClassName !== $className) {
						// TODO optimize this
						continue;
					}
					foreach ($method->getAttributes($attributeClassName) as $reflectionAttribute) {
						$attribute = $reflectionAttribute->newInstance();
						assert($attribute instanceof HookableAttribute);
						$attribute->setMethod($method);
						$results[$declaredClassName . '::' . $method->getName()] = $attribute;
					}
				}
			} catch (ReflectionException $e) {
				$app->error($e);
			}
		}
		return $results;
	}

	/**
	 * Finds methods in this object with the attribute annotation of class
	 *
	 * Usage:
	 *
	 *        foreach ($this->>attributeMethods(DaemonMethod::class) as $method) {
	 *            ...
	 *        }
	 *
	 * @param string $attributeClassName
	 * @return array:HookableAttribute
	 */
	public function attributeMethods(string $attributeClassName): array {
		$reflection = new ReflectionClass($this);
		$flags = ReflectionMethod::IS_PROTECTED | ReflectionMethod::IS_PUBLIC;
		$attributes = [];
		foreach ($reflection->getMethods($flags) as $method) {
			$declaringClass = $method->getDeclaringClass()->getName();
			foreach ($method->getAttributes($attributeClassName) as $reflectionAttribute) {
				$attribute = $reflectionAttribute->newInstance();
				assert($attribute instanceof HookableAttribute);
				$attribute->setMethod($method);
				$attributes[$declaringClass . '::' . $method->getName()] = $attribute;
			}
		}
		return $attributes;
	}

	/**
	 * Find methods in this object with a HookMethod attached with the name $hookName
	 *
	 * @param string $hookName
	 * @return array:HookMethod
	 */
	public function hookMethods(string $hookName): array {
		return array_filter($this->attributeMethods(HookMethod::class), fn (HookMethod $method) => $method->handlesHook($hookName));
	}

	/**
	 * Do static and application hooks exist? (unrelated to `$this`)
	 *
	 * @param string $hookName
	 * @return bool
	 */
	public function hasHooks(string $hookName): bool {
		return count(self::_hooksFor($hookName)) !== 0;
	}

	/**
	 * Do object hooks exist for this object? (related to `$this`)
	 *
	 * @param string $hookName
	 * @param bool $isFilter
	 * @return bool
	 */
	public function hasObjectHooks(string $hookName, bool $isFilter = false): bool {
		return count(self::objectHookMethods([$this], $hookName, $isFilter)) !== 0;
	}

	/**
	 * Do object hooks exist for this object? (related to `$this`)
	 *
	 * @param string $hookName
	 * @return bool
	 */
	public function hasObjectFilters(string $hookName): bool {
		return $this->hasObjectHooks($hookName, true);
	}

	/**
	 * List of hooks found
	 *
	 * @param string $hookName
	 * @param bool $isFilter
	 * @return array
	 */
	protected function _hooksFor(string $hookName, bool $isFilter = false): array {
		return array_merge(self::staticHooksFor($this, $hookName, $isFilter), self::applicationHookMethods($this, $hookName, [$this], $isFilter), $this->application->hooks->peekHooks($hookName, $isFilter));
	}

	/**
	 * Run static hooks and object hooks
	 *
	 * @param string $hookName
	 * @param array $arguments
	 * @return void
	 */
	public function invokeHooks(string $hookName, array $arguments = []): void {
		$hooks = $this->_hooksFor($hookName);
		foreach ($hooks as $method) {
			$method->run($arguments);
		}
	}

	/**
	 * Run static hooks and object hooks until a non-null result is returned
	 *
	 * @param string $hookName
	 * @param array $arguments
	 * @return mixed
	 */
	public function invokeHooksUntil(string $hookName, array $arguments = []): mixed {
		$hooks = $this->_hooksFor($hookName);
		foreach ($hooks as $method) {
			$result = $method->run($arguments);
			if ($result !== null) {
				return $result;
			}
		}
		return null;
	}

	/**
	 * Run static hooks and object hooks
	 *
	 * @param string $hookName
	 * @param array $arguments
	 * @param bool $isFilter
	 * @return void
	 * @throws ParameterException
	 */
	public function invokeObjectHooks(string $hookName, array $arguments = [], bool $isFilter = false): void {
		$hooks = self::objectHookMethods([$this], $hookName, $isFilter);
		foreach ($hooks as $method) {
			$method->run($arguments);
		}
	}

	/**
	 * @param HookMethod[] $hookMethods
	 * @param mixed $mixed
	 * @param array $arguments
	 * @param int $filterArgumentIndex
	 * @return mixed
	 * @throws ParameterException
	 */
	private static function _invokeFilters(array $hookMethods, mixed $mixed, array $arguments = [], int $filterArgumentIndex = -1): mixed {
		if ($filterArgumentIndex < 0) {
			$filterArgumentIndex = count($arguments);
		}
		foreach ($hookMethods as $hookMethod) {
			/* @var $hookMethod HookMethod */
			$arguments[$filterArgumentIndex] = $mixed;
			$mixed = $hookMethod->run($arguments);
		}
		return $mixed;
	}

	/**
	 * Invokes a filter and ensures that $mixed remains the same type throughout
	 *
	 * @param string $hookName
	 * @param array $hookMethods
	 * @param mixed $mixed
	 * @param Closure|null $loopEndTest
	 * @param array $arguments
	 * @param int $filterArgumentIndex
	 * @return mixed
	 * @throws ParameterException
	 */
	private function _invokeTypedFilters(string $hookName, array $hookMethods, mixed $mixed, Closure|null $loopEndTest, array $arguments = [], int $filterArgumentIndex = -1): mixed {
		$type = Types::type($mixed);
		if ($filterArgumentIndex < 0) {
			$filterArgumentIndex = count($arguments);
		}
		foreach ($hookMethods as $index => $method) {
			/* @var $method HookMethod */
			$arguments[$filterArgumentIndex] = $mixed;
			$mixed = $method->run($arguments);
			if (Types::type($mixed) !== $type) {
				throw new RuntimeException('{hookName} failed on step {index} with mismatched type expected {type} !== actual {actual}. Method name is {name}', [
					'hookName' => $hookName, 'index' => $index, 'type' => $type, 'actual' => Types::type($mixed),
					'name' => $method->name(),
				]);
			}
			if ($loopEndTest !== null && $loopEndTest->call($this->application, $mixed)) {
				break;
			}
		}
		return $mixed;
	}

	/**
	 * Run static and application objects hooks and run a filter function on an argument which is
	 * returned by each hook. Type is not enforced.
	 *
	 * @param string $hookName
	 * @param mixed $mixed
	 * @param array $arguments
	 * @param int $filterArgumentIndex
	 * @return mixed
	 * @throws ParameterException
	 */
	public function invokeFilters(string $hookName, mixed $mixed, array $arguments = [], int $filterArgumentIndex = -1): mixed {
		$hooks = $this->_hooksFor($hookName, true);
		return self::_invokeFilters($hooks, $mixed, $arguments, $filterArgumentIndex);
	}

	/**
	 * Run static and application objects hooks and run a filter function on an argument which is
	 * returned by each hook. Enforce the return type to match upon each function call.
	 *
	 * @param string $hookName
	 * @param mixed $mixed
	 * @param array $arguments
	 * @param int $filterArgumentIndex
	 * @return mixed
	 * @throws ParameterException
	 */
	public function invokeTypedFilters(string $hookName, mixed $mixed, array $arguments = [], int $filterArgumentIndex = -1): mixed {
		$hooks = $this->_hooksFor($hookName, true);
		return self::_invokeTypedFilters($hookName, $hooks, $mixed, null, $arguments, $filterArgumentIndex);
	}

	/**
	 * Run static and application objects hooks and run a filter function on an argument which is
	 * returned by each hook. Enforce the return type to match upon each function call.
	 *
	 * @param string $hookName
	 * @param mixed $mixed
	 * @param Closure|bool|int|string|null $untilValue
	 * @param array $arguments
	 * @param int $filterArgumentIndex
	 * @return mixed
	 * @throws ParameterException
	 */
	public function invokeTypedFiltersUntil(string $hookName, mixed $mixed, Closure|null|bool|int|string $untilValue, array $arguments = [], int $filterArgumentIndex = -1): mixed {
		$hooks = $this->_hooksFor($hookName, true);
		if ($untilValue instanceof Closure) {
			$loopEndTest = $untilValue;
		} else {
			$loopEndTest = fn ($value) => $value === $untilValue;
		}
		return self::_invokeTypedFilters($hookName, $hooks, $mixed, $loopEndTest, $arguments, $filterArgumentIndex);
	}

	/**
	 * Run just hooks on this object
	 *
	 * @param string $hookName
	 * @param mixed $mixed
	 * @param array $arguments
	 * @param int $filterArgumentIndex
	 * @return mixed
	 * @throws ParameterException
	 */
	public function invokeObjectFilters(string $hookName, mixed $mixed, array $arguments = [], int $filterArgumentIndex = -1): mixed {
		$hooks = self::objectHookMethods([$this], $hookName, true);
		return self::_invokeFilters($hooks, $mixed, $arguments, $filterArgumentIndex);
	}

	/**
	 * Finds the HookMethod attached to a list of Hookables and return them. Each hookable may have one or more
	 * hookName method.
	 *
	 * @param Hookable[] $hookables
	 * @param string $hookName
	 * @param bool $isFilter
	 * @return array
	 */
	public static function objectHookMethods(array $hookables, string $hookName, bool $isFilter = false): array {
		$hookMethods = [];
		foreach ($hookables as $hookable) {
			foreach ($hookable->hookMethods($hookName) as $name => $hookMethod) {
				/* @var $hookMethod HookMethod */
				if ($hookMethod->isFilter() !== $isFilter) {
					continue;
				}
				$hookMethod->setObject($hookable);
				$hookMethods[$name] = $hookMethod;
			}
		}
		return $hookMethods;
	}

	/**
	 * @param Hookable $hookable
	 * @param string $hookName
	 * @param array $hookables
	 * @param bool $isFilter
	 * @return HookMethod[]
	 */
	public static function applicationHookMethods(Hookable $hookable, string $hookName, array $hookables = [], bool $isFilter = false): array {
		return self::objectHookMethods(array_merge($hookable->hookables(), $hookables), $hookName, $isFilter);
	}

	/**
	 * List of application hookables which is, in order:
	 *
	 * - the application object
	 * - the locale
	 * - the router
	 * - all modules
	 * - any hookables in $application->objects
	 *
	 * @return array:self
	 */
	final public function hookables(): array {
		return array_merge([
			$this->application, $this->application->locale, $this->application->router,
		], $this->application->modules->all(), $this->application->objects->hookables());
	}

	/**
	 * Retrieves a list of all static methods tagged with the HookMethod attribute which are not filters (plain hooks)
	 *
	 * @param Hookable $hookable
	 * @param string $name
	 * @param bool $isFilter
	 * @return array:HookMethod
	 * @see HookMethod
	 */
	public static function staticHooksFor(Hookable $hookable, string $name, bool $isFilter): array {
		return self::_staticHookMethodsFor($hookable, $name, $isFilter);
	}

	/**
	 * Retrieves a list of all static methods tagged with the HookMethod attribute
	 *
	 * @param Hookable $hookable
	 * @param string $name
	 * @param bool $isFilter
	 * @return array
	 */
	public static function _staticHookMethodsFor(Hookable $hookable, string $name, bool $isFilter): array {
		return array_filter(self::staticAttributeMethods($hookable, HookMethod::class), fn (HookMethod $method) => $method->handlesHook($name) && $method->isFilter() === $isFilter);
	}

	/**
	 * Save nothing herein. (Explicitly ignores $this->application)
	 *
	 * @return string[]
	 */
	public function __serialize(): array {
		return parent::__serialize();
	}

	/**
	 * Problem with globals.
	 */
	public function __unserialize(array $data): void {
		$this->application = Kernel::wakeupApplication();
		parent::__unserialize($data);
	}

	/**
	 * Loading references
	 *
	 * @param string $class
	 * @return array
	 */
	private function _defaultOptions(string $class): array {
		$references = [];
		// Class hierarchy is given from child -> parent
		$config = $this->application->configuration;
		foreach ($this->application->classes->hierarchy($class) as $subclass) {
			// Child options override parent options
			$references[$subclass] = $config->path($subclass);
		}
		return $references;
	}

	/**
	 * Load default options for an object.
	 * Leaf-class options override parent options.
	 *
	 * For class Control_Thing_Example, loads globals from:
	 *
	 * - `zesk\Control_Thing_Example::name1`
	 * - `zesk\Control_Thing::name1`
	 * - `zesk\Control::name1`
	 * - `zesk\Options::name1`
	 *
	 * @param string $class
	 * @return array
	 */
	public function defaultOptions(string $class): array {
		// Class hierarchy is given from child -> parent
		$config = new Configuration();
		foreach ($this->_defaultOptions($class) as $configuration) {
			// Child options override parent options
			$config->merge($configuration, false);
		}
		return $config->toArray();
	}

	/**
	 * Set options based on the application configuration.
	 *
	 * Only sets options if not set already.
	 *
	 * @param string $class Class to inherit configuration from
	 * @return $this
	 */
	final public function inheritConfiguration(string $class = ''): self {
		return $this->_configure($class, false);
	}

	private function _configure(string $class = '', bool $overwrite = true): self {
		return $this->setOptions($this->defaultOptions($class ?: get_class($this)), $overwrite);
	}

	/**
	 * Set options based on the application configuration.
	 *
	 * Overwrites all options from global configuration.
	 *
	 * @return $this
	 */
	final public function setConfiguration(string $class = ''): self {
		return $this->_configure($class);
	}
}
