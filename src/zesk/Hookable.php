<?php
declare(strict_types=1);
/**
 * @package zesk
 * @subpackage kernel
 * @author kent
 * @copyright &copy; 2023, Market Acumen, Inc.
 */

namespace zesk;

use ReflectionClass;
use ReflectionException;
use ReflectionMethod;
use zesk\Application\Hooks;
use zesk\Exception\Deprecated;
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
	 * Per-object hooks. Removed from options.
	 *
	 * @var array
	 */
	private array $_hooks = [];

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
				$app->logger->error($e);
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
	 * @return bool
	 */
	public function hasObjectHooks(string $hookName): bool {
		return count(self::objectHookMethods([$this], $hookName)) !== 0;
	}

	/**
	 * List of invoke hooks found
	 *
	 * @param string $hookName
	 * @return array
	 */
	protected function _hooksFor(string $hookName): array {
		return array_merge(self::staticHooksFor($this, $hookName), self::applicationHookMethods($this, $hookName, [$this]), $this->application->hooks->peekHooks($hookName));
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
	 * @return void
	 */
	public function invokeObjectHooks(string $hookName, array $arguments = []): void {
		$hooks = self::objectHookMethods([$this], $hookName);
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
	 * @param mixed $mixed
	 * @param array $arguments
	 * @param int $filterArgumentIndex
	 * @return mixed
	 * @throws ParameterException
	 */
	private function _invokeTypedFilters(string $hookName, array $hookMethods, mixed $mixed, array $arguments = [], int $filterArgumentIndex = -1): mixed {
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
	 */
	public function invokeFilters(string $hookName, mixed $mixed, array $arguments = [], int $filterArgumentIndex = -1): mixed {
		$hooks = $this->_hooksFor($hookName);
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
		$hooks = $this->_hooksFor($hookName);
		return self::_invokeTypedFilters($hookName, $hooks, $mixed, $arguments, $filterArgumentIndex);
	}

	/**
	 * Run just hooks on this object
	 *
	 * @param string $hookName
	 * @param mixed $mixed
	 * @param array $arguments
	 * @param int $filterArgumentIndex
	 * @return mixed
	 */
	public function invokeObjectFilters(string $hookName, mixed $mixed, array $arguments = [], int $filterArgumentIndex = -1): mixed {
		$hooks = self::objectHookMethods([$this], $hookName);
		return self::_invokeFilters($hooks, $mixed, $arguments, $filterArgumentIndex);
	}

	/**
	 * Finds the HookMethod attached to a list of Hookables and return them. Each hookable may have one or more
	 * hookName method.
	 *
	 * @param Hookable[] $hookables
	 * @param string $hookName
	 * @return HookMethod[]
	 */
	public static function objectHookMethods(array $hookables, string $hookName): array {
		$hookMethods = [];
		foreach ($hookables as $hookable) {
			foreach ($hookable->hookMethods($hookName) as $name => $hookMethod) {
				/* @var $hookMethod HookMethod */
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
	 * @return HookMethod[]
	 */
	public static function applicationHookMethods(Hookable $hookable, string $hookName, array $hookables = []): array {
		return self::objectHookMethods(array_merge($hookable->hookables(), $hookables), $hookName);
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
	 * Retrieves a list of all static methods tagged with the HookMethod attribute
	 *
	 * @param Hookable $hookable
	 * @param string $name
	 * @return array:HookMethod
	 * @see HookMethod
	 */
	public static function staticHooksFor(Hookable $hookable, string $name): array {
		return array_filter(self::staticAttributeMethods($hookable, HookMethod::class), fn (HookMethod $method) => $method->handlesHook($name));
	}

	/**
	 * Save nothing herein. (Explicitly ignores $this->application)
	 *
	 * @return string[]
	 */
	public function __sleep() {
		return parent::__sleep();
	}

	/**
	 * Problem with globals.
	 */
	public function __wakeup(): void {
		$this->application = Kernel::wakeupApplication();
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
	/*=================================================================================================================
	 *  ____                                _           _
	 * |  _ \  ___ _ __  _ __ ___  ___ __ _| |_ ___  __| |
	 * | | | |/ _ \ '_ \| '__/ _ \/ __/ _` | __/ _ \/ _` |
	 * | |_| |  __/ |_) | | |  __/ (_| (_| | ||  __/ (_| |
	 * |____/ \___| .__/|_|  \___|\___\__,_|\__\___|\__,_|
	 *			   |_|
	 */

	/**
	 * Invoke a hook on this object if it exists.
	 * Arguments should be passed after the type.
	 *
	 * Using this invocation method, you can not pass a hook callback or a result callback to
	 * process results, so this is best used for triggers which do not require a result.
	 *
	 * @param array|string $types
	 * @return mixed
	 * @deprecated 2023-05
	 */
	final public function callHook(array|string $types): mixed {
		$args = func_get_args();
		array_shift($args);
		return $this->callHookArguments($types, $args, $args[0] ?? null);
	}

	/**
	 * Invoke a hook on this object if it exists.
	 *
	 * Example of functions called for $user->callHookArguments("hello") is a User:
	 *
	 * $user->hook_hello (if it exists)
	 * callable stored in $this->options['hooks']['hello'] (if it exists)
	 * Any zesk hooks registered as (in order):
	 * 1. User::hello
	 * 2. zesk\User::hello
	 * 3. zesk\ORM::hello
	 * 3. zesk\Model::hello
	 * 4. Hookable::hello
	 *
	 * Arguments passed as an array
	 *
	 * @param array|string $types
	 *            An array of hooks to call, all hooks found are executed, and you can repeat if
	 *            necessary.
	 * @param array $args
	 *            Optional. An array of parameters to pass to the hook.
	 * @param mixed|null $default
	 *            Optional. The value to return if the final result returned by a hook is NULL.
	 * @param callable|null $hook_callback
	 *            Optional. A callable in the form `function ($callable, array $arguments) { ... }`
	 * @param callable|null $result_callback
	 *            Optional. A callable in the form `function ($callable, $previous_result,
	 *            $new_result) { ... }`
	 * @return mixed
	 * @deprecated 2023-05
	 */
	final public function callHookArguments(array|string $types, array $args = [], mixed $default = null, callable $hook_callback = null, callable $result_callback = null): mixed {
		$hooks = $this->collectHooks($types, $args);
		$result = $default;
		foreach ($hooks as $hook) {
			[$callable, $arguments] = $hook;
			$result = self::hookResults($result, $callable, $arguments, $hook_callback, $result_callback);
		}
		return $result;
	}

	/**
	 * Invoke a hook on this object if it exists.
	 *
	 * Example of functions called for $user->callHookArguments("hello") is a User:
	 *
	 * $user->hook_hello (if it exists)
	 * callable stored in $this->options['hooks']['hello'] (if it exists)
	 * Any zesk hooks registered as (in order):
	 * 1. User::hello
	 * 2. zesk\ORM\User::hello
	 * 3. zesk\ORM::hello
	 * 3. zesk\Model::hello
	 * 4. Hookable::hello
	 *
	 * Arguments passed as an array
	 *
	 * @param array|string $types An array of hooks to call, all hooks found are executed, and you can repeat if
	 *            necessary.
	 * @param array $args Optional. An array of parameters to pass to the hook.
	 * @return array
	 * @throws Deprecated
	 * @deprecated 2023-05
	 */
	final public function collectHooks(array|string $types, array $args = []): array {
		if (empty($types)) {
			return [];
		}
		if (!is_array($args)) {
			$args = [
				$args,
			];
		}
		$types = Types::toList($types);
		/*
		 * Add $this for system hooks
		 */
		$zesk_hook_args = $args;
		array_unshift($zesk_hook_args, $this);

		/*
		 * For each hook, call internal hook, then options-based hook, then system hook.
		 */
		$app = $this->application;
		$hooks = [];
		foreach ($types as $type) {
			$method = Hooks::cleanName($type);
			if ($method !== $type) {
				$this->application->deprecated('Hook "{type}" cleaned to "{method}" - please fix', compact('method', 'type'));
			}
			if (method_exists($this, "hook_$method")) {
				$hooks[] = [
					[
						$this, "hook_$method",
					], $args,
				];
			}
			$methods = $this->_hooks[$type] ?? null;
			if (is_array($methods)) {
				foreach ($methods as $method) {
					$hooks[] = [
						$method, $zesk_hook_args,
					];
				}
			}
			$hook_names = ArrayTools::suffixValues($app->classes->hierarchy($this, __CLASS__), "::$type");
			$hooks = array_merge($hooks, $app->hooks->collectHooks($hook_names, $zesk_hook_args));
		}
		return $hooks;
	}

	/**
	 *
	 * @param string $type
	 * @param callable $callable
	 * @return $this
	 * @deprecated 2023-05
	 */
	final public function addHook(string $type, callable $callable): self {
		$type = Hooks::cleanName($type);
		$this->_hooks[$type][] = $callable;
		return $this;
	}

	/**
	 * Does a hook exist for this object?
	 *
	 * @param mixed $types
	 * @param boolean $object_only
	 * @return boolean
	 * @deprecated 2023-05
	 */
	final public function hasHook(mixed $types, bool $object_only = false): bool {
		$hooks = $this->listHooks($types, $object_only);
		return count($hooks) !== 0;
	}

	/**
	 * List functions to be invoked by a hook on this object if it exists.
	 * Arguments passed as an array
	 *
	 * @param string|array $types
	 *            An array of hooks to call, first one found is executed, or a string of the hook to
	 *            call
	 * @param boolean $object_only
	 * @return array
	 * @deprecated 2023-05
	 */
	final public function listHooks(string|array $types, bool $object_only = false): array {
		$hooks = $this->application->hooks;
		$types = Types::toList($types);
		$result = [];
		foreach ($types as $type) {
			$method = Hooks::cleanName($type);
			$hook_method = "hook_$method";
			//echo get_class($this) . " checking for $hook_method\n";
			if (method_exists($this, $hook_method)) {
				$result[] = [
					$this, $hook_method,
				];
			}
			$methods = $this->_hooks[$type] ?? null;
			if (is_array($methods)) {
				foreach ($methods as $method) {
					$result[] = Hooks::callableString($method);
				}
			}
			if (!$object_only) {
				$hook_names = ArrayTools::suffixValues($this->application->classes->hierarchy($this, __CLASS__), "::$type");
				if ($hooks->has($hook_names)) {
					foreach ($hook_names as $hook_name) {
						if ($hooks->has($hook_name)) {
							$result[] = $hook_name;
						}
					}
				}
			}
		}
		return $result;
	}

	/**
	 * Combine hook results in a consistent manner when more than one hook applies to a call.
	 *
	 * The only mechanism which modifies hook results is `Arrays`: list-style arrays are concatenated, key-value arrays are merged with later values overriding earlier values.
	 *
	 * @param mixed $previous_result
	 *            Previous hook result. Default to null for first call.
	 * @param callable $callable
	 *            Function
	 * @param array $arguments
	 * @param ?callable $hook_callback A function to call for each hook called.
	 * @param ?callable $result_callback A function to process hook results. If false, returns last result unmodified.
	 * @return mixed
	 * @deprecated 2023-05
	 */
	final public static function hookResults(mixed $previous_result, callable $callable, array $arguments, callable $hook_callback = null, callable $result_callback = null): mixed {
		if ($hook_callback) {
			call_user_func_array($hook_callback, [
				$callable, $arguments,
			]);
		}
		$new_result = call_user_func_array($callable, $arguments);
		if ($result_callback !== null) {
			return call_user_func($result_callback, $callable, $previous_result, $new_result, $arguments);
		}
		return self::combineHookResults($previous_result, $new_result);
	}

	/**
	 * Combine hook results in chained/filter hooks in a predictable manner
	 *
	 *
	 *
	 * @param mixed $previous_result
	 * @param mixed $new_result
	 * @return mixed
	 * @deprecated 2023-05
	 */
	public static function combineHookResults(mixed $previous_result, mixed $new_result): mixed {
		// If our old result was empty/void, then return new result
		if ($previous_result === null) {
			return $new_result;
		}
		//
		// KMD 2018-01: Handle when a hook returns NOTHING and a default value is supplied to callHookArguments.
		// Will use previous result.
		//
		if ($new_result === null) {
			return $previous_result;
		}
		if (is_array($previous_result) && is_array($new_result)) {
			if (count($previous_result) > 0 && ArrayTools::isList($previous_result)) {
				return array_merge($previous_result, $new_result);
			} else {
				return $new_result + $previous_result;
			}
		}
		return $new_result;
	}
}
