<?php
declare(strict_types=1);
/**
 * @package zesk
 * @subpackage kernel
 * @author kent
 * @copyright &copy; 2023, Market Acumen, Inc.
 */

namespace zesk\Application;

use Closure;
use ReflectionClass;
use ReflectionException;
use Throwable;
use zesk\Application;
use zesk\Exception\KeyNotFound;
use zesk\Exception\Semantics;
use zesk\Hookable;
use zesk\HookGroup;
use zesk\Kernel;
use zesk\PHP;
use zesk\StringTools;
use zesk\Types;

/**
 *
 * @author kent
 *
 */
class Hooks {
	/**
	 * For assigning IDs to callables which have no unique name
	 *
	 * @var int
	 */
	private static int $id = 0;

	/**
	 *
	 * @var string
	 */
	public const HOOK_DATABASE_CONFIGURE = 'database_configure';

	/**
	 *
	 * @var string
	 */
	public const HOOK_CONFIGURED = 'configured';

	/**
	 * Reset the entire zesk application context
	 *
	 * @var string
	 */
	public const HOOK_RESET = 'reset';

	/**
	 * Called when the process is going to exit
	 *
	 * @var string
	 */
	public const HOOK_EXIT = 'exit';

	/**
	 * Output a debug log when a class is called with ::hooks but does not implement it
	 *
	 * @var boolean
	 */
	public bool $debug = false;

	/**
	 *
	 * @var Application
	 */
	public Application $application;

	/**
	 * Determine which hooks are looked at/tested for existence.
	 * Retrieve with ->has()
	 *
	 * @var boolean
	 */
	public bool $profileHooks = false;

	/**
	 * System hooks for adding custom functionality throughout the system
	 *
	 * @var array
	 */
	private array $hooks = [];

	/**
	 *
	 * @var array
	 */
	private array $hooksCalled = [];

	/**
	 *
	 * @var array
	 */
	private array $hooksFailed = [];

	/**
	 *
	 * @var array
	 */
	private array $hookCache = [];

	/**
	 * Used to track which top-level classes have been gathered yet
	 *
	 * @var array
	 */
	private array $allHookClasses = [];

	/**
	 *
	 * @param Application $application
	 */
	public function __construct(Application $application) {
		$this->application = $application;
	}

	private static array $fatalErrors = [
		E_USER_ERROR => 'Fatal Error', E_ERROR => 'Fatal Error', E_PARSE => 'Parse Error', E_CORE_ERROR => 'Core Error',
		E_CORE_WARNING => 'Core Warning', E_COMPILE_ERROR => 'Compile Error', E_COMPILE_WARNING => 'Compile Warning',
	];

	/**
	 * Shutdown function to log errors
	 */
	private function _applicationExitCheck(): void {
		$prefix = 'Application Exit Check: ';
		if ($err = error_get_last()) {
			if (isset(self::$fatalErrors[$err['type']])) {
				$msg = self::$fatalErrors[$err['type']] . ': ' . $err['message'] . ' in ';
				$msg .= $err['file'] . ' on line ' . $err['line'];
				error_log($prefix . $msg);
			}
		}
		foreach ($this->hooksFailed as $class => $result) {
			if ($result instanceof Throwable) {
				error_log($prefix . "$class::hooks threw exception " . $result::class);
			}
		}
	}

	/**
	 *
	 * @return array
	 */
	private function resetAllHookClasses(): array {
		$this->hooks = [];
		$this->hooksCalled = [];
		$this->hooksFailed = [];
		$all_hook_classes = $this->allHookClasses;
		$this->allHookClasses = [];
		return $all_hook_classes;
	}

	/**
	 * @todo does this work?
	 *
	 */
	public function reset(): void {
		$this->call(Hooks::HOOK_RESET);
		foreach ($this->resetAllHookClasses() as $class) {
			$this->registerClass($class);
		}
	}

	/**
	 * Clean and normalize a hook name
	 * @param string $name
	 * @return string
	 */
	public static function cleanName(string $name): string {
		return trim($name);
	}

	/**
	 * Given a passed-in hook name, normalize it and return the internal name
	 *
	 * @param string $name Hook name
	 * @return string
	 */
	private function _hookName(string $name): string {
		return self::cleanName($name);
	}

	/**
	 * Remove hooks - use with caution
	 *
	 * @param string $hook
	 */
	public function unhook(string $hook): void {
		$hook = $this->_hookName($hook);
		unset($this->hooks[$hook]);
	}

	/**
	 *
	 * @param array $hooks
	 * @return array
	 */
	private function hookLoadDefinitions(iterable $hooks): array {
		$definitions = [];
		$found = [];
		foreach ($hooks as $hook) {
			$hook = $this->_hookName($hook);
			if (isset($found[$hook])) {
				continue;
			}
			$found[$hook] = true;
			if (isset($this->hooks[$hook])) {
				$definitions += $this->hooks[$hook]->definitions();
			}
		}
		return $definitions;
	}

	/**
	 * Called on classes which may register hooks in Zesk using $hooks->add().
	 *
	 * A list of classes are loaded using the Autoloader and then ::hooks is called for each class
	 * if it exists.
	 *
	 * Every call is called once and only once per-Application, order must not matter, but can be
	 * enforced by calling $hooks->registerClass('dependency1;dependency2'); as the first line to
	 * your hooks registration call.
	 *
	 * Note that the chosen "::hooks" calls should pretty much do one thing: call`$hooks->add(...)`
	 * and that's it,
	 * and should do the bare minimum registration to operate correctly.
	 *
	 * Generally, classes will do:
	 * <code>
	 * class foo {
	 * public static function hooks(zesk\Kernel $kernel) {
	 * $kernel->hooks->add('configured', __CLASS__ . "::configured");
	 * }
	 * public static function configured() {
	 * if ($this->getBool('foo::enabled')) {
	 * // Do something important
	 * }
	 * }
	 * }
	 * </code>
	 *
	 * @param mixed $classes
	 *            List of classes to invoke the static "hooks" method for.
	 *
	 * @return self
	 */
	public function registerClass(string|array $classes): self {
		if (is_array($classes)) {
			foreach ($classes as $class) {
				$this->registerClass($class);
			}
		} else {
			$this->_registerClassHooks($classes);
		}
		return $this;
	}

	/**
	 * @return array
	 */
	public function hooksCalled(): array {
		return $this->hooksCalled;
	}

	/**
	 *
	 * @param string $class
	 * @return void
	 */
	private function _registerClassHooks(string $class): void {
		$lowClass = strtolower($class);
		if (isset($this->hooksCalled[$lowClass])) {
			return;
		}
		if (method_exists($class, 'hooks')) {
			try {
				call_user_func([$class, 'hooks', ], $this->application);
				$this->hooksCalled[$lowClass] = microtime(true);
			} catch (Throwable $e) {
				$this->hooksCalled[$lowClass] = $e;
				$this->hooksFailed[$class] = $e;
				$this->call('exception', $e);
			}
		} elseif ($this->debug) {
			$args = [
				'__CLASS__' => __CLASS__, '__FUNCTION__' => __FUNCTION__, 'class' => $class,
			];
			$this->application->logger->debug('{__CLASS__}::{__FUNCTION__} Class {class} does not have method hooks', $args);
			$this->hooksCalled[$lowClass] = false;
		}
	}

	/**
	 * Does a hook exist? Logs all hook name requests.
	 * To retrieve them just call $hook->has() to get the currently
	 * requested list. Returns an array with hook name and the number of times called, the time it
	 * was requested the
	 * first time and last time, e.g. $hook => array(45, 0, 10.42)
	 *
	 * @param mixed $hooks
	 *            A hook name, or a list of hooks, separated by ";", or an array of hook names
	 * @return true If any hook exists. If null passed then returns an array of keys => arrays
	 *         described above.
	 */
	public function has(string|array $hooks): bool {
		if (is_string($hooks)) {
			$hook = $this->_hookName($hooks);
			if ($this->profileHooks) {
				$ding = microtime(true);
				if (!isset($this->hookCache[$hook])) {
					$this->hookCache[$hook] = [1, $ding, $ding, ];
				} else {
					$this->hookCache[$hook][0]++;
					$this->hookCache[$hook][2] = $ding;
				}
			}
			return isset($this->hooks[$hook]);
		} else {
			assert(is_array($hooks));
			foreach ($hooks as $hook) {
				$result = $this->has($hook);
				if ($result) {
					return true;
				}
			}
			return false;
		}
	}

	/**
	 * @return array
	 */
	public function all(): array {
		return $this->hooks;
	}

	/**
	 *
	 * @return array
	 */
	public function hookCache(): array {
		return $this->hookCache;
	}

	private function nextCallableId(): string {
		return 'anonymous-' . self::$id++;
	}

	/**
	 * Hooks are very flexible, and each hook determines how it is combined with the next hook.
	 *
	 * Valid options are:
	 *
	 * - 'first' - boolean. Optional. Invoke this hook first before all other hooks
	 * - 'last' - boolean. Optional. Invoke this hook last after all other hooks
	 * - 'arguments' - array. A list of arguments to pass to the hook. Any additional arguments are
	 * passed after these.
	 *
	 * @param string $hook
	 *            Hook name. Can be any string, typically CLASS::method
	 * @param mixed $function
	 *            A function or class name, or an array to specify an object method or object static
	 *            method.
	 * @param array $options
	 *            Return value handling, ordering, arguments.
	 *
	 * @return void
	 * @throws Semantics
	 */
	public function add(string $hook, Closure|callable $function, array $options = []): void {
		$id = $options['id'] ?? self::callable_string($function) ?: Kernel::callingFunction(1);
		$hookGroup = $this->_group($hook);
		if (($options['no-duplicates'] ?? false) && $hookGroup->has($id)) {
			throw new Semantics('Duplicate registration of hook {id}', [
				'id' => $id,
			]);
		}
		$options['id'] = $id;
		$options['callable'] = $function;
		$this->hooks[$hook] = $hookGroup->add($options);
	}

	/**
	 * Get or create a HookGroup for the hook name
	 *
	 * @param string $hook
	 * @return HookGroup
	 */
	private function _group(string $hook): HookGroup {
		$hook = $this->_hookName($hook);
		if (!array_key_exists($hook, $this->hooks)) {
			return new HookGroup();
		} else {
			return $this->hooks[$hook];
		}
	}

	/**
	 * Find all hooks given a class::method string - finds all items of class which have method
	 *
	 * @param array|string $class_methods
	 * @return array
	 */
	public function findAll(array|string $class_methods): array {
		$methods = [];

		$application = $this->application;
		foreach (Types::toList($class_methods) as $class_method) {
			[$class, $method] = StringTools::pair($class_method, '::', '', $class_method);
			if ($class === '') {
				continue;
			}
			$application->hooks->registerClass($class);
			$application->classes->register($class);
			foreach ($application->classes->subclasses($class) as $class) {
				try {
					$reflectionClass = new ReflectionClass($class);
				} catch (ReflectionException) {
					continue;
				}
				if (!$reflectionClass->hasMethod($method)) {
					/* Class does not have this method, just skip it */
					continue;
				}
				$reflectionMethod = $reflectionClass->getMethod($method);
				if (!$reflectionMethod->isStatic()) {
					/* Method is static, also just skip it */
					continue;
				}
				$declaring = $reflectionMethod->getDeclaringClass()->name;
				$methods["$declaring::$method"] = [$declaring, $method];
			}
		}
		return $methods;
	}

	/**
	 * Remove hooks - use with caution
	 *
	 * @param string $hook
	 * @throws KeyNotFound
	 */
	public function remove(string $hook): void {
		$hook = $this->_hookName($hook);
		if (!isset($this->hooks[$hook])) {
			throw new KeyNotFound($hook);
		}
		unset($this->hooks[$hook]);
	}

	/**
	 * Call a hook, with optional additional arguments
	 *
	 * @param string|array $hooks Hooks to call
	 * @return mixed
	 * @deprecated 2023-05 Move to attribute-based system
	 */
	public function call(mixed $hooks): mixed {
		$arguments = func_get_args();
		array_shift($arguments);
		return $this->callArguments($hooks, $arguments);
	}

	/**
	 *
	 * @param string|array $hooks
	 *            Hooks to call
	 * @param array $arguments
	 *            Arguments to pass to the first hook
	 * @return array[array]
	 */
	public function collectHooks(mixed $hooks, array $arguments = []): array {
		$definitions = $this->hookLoadDefinitions(Types::toIterable($hooks));
		$hooks = [];
		if (count($definitions) === 0) {
			return $hooks;
		}
		foreach ($definitions as $options) {
			$options_arguments = $options['arguments'] ?? [];
			$hooks[] = [
				$options['callable'],
				count($options_arguments) > 0 ? array_merge($options_arguments, $arguments) : $arguments,
			];
		}
		return $hooks;
	}

	/**
	 * Invoke a global hook by type
	 *
	 * @param array|string $methods
	 * @return mixed
	 */
	public function allCall(array|string $methods): mixed {
		$args = func_get_args();
		$methods = array_shift($args);
		return $this->allCallArguments($methods, $args);
	}

	/**
	 * Invoke a global hook by type
	 *
	 * @param array|string $methods
	 * @param array $arguments
	 * @param mixed $default
	 * @param callable|null $hook_callback
	 * @param callable|null $result_callback
	 * @return mixed
	 * @see self::findAll
	 */
	public function allCallArguments(array|string $methods, array $arguments = [], mixed $default = null, callable $hook_callback = null, callable $result_callback = null): mixed {
		$methods = $this->findAll($methods);
		$result = $default;
		foreach ($methods as $class_method) {
			$result = $this->callArguments($class_method, $arguments, $result, $hook_callback, $result_callback);
			$result = Hookable::hookResults($result, $class_method, $arguments, $hook_callback, $result_callback);
		}
		return $result;
	}

	/**
	 * @param mixed $hooks
	 * @param array $arguments
	 * @param mixed|null $default
	 * @param callable|null $hook_callback
	 * @param callable|null $result_callback
	 * @return mixed
	 */
	public function callArguments(mixed $hooks, array $arguments = [], mixed $default = null, callable $hook_callback = null, callable $result_callback = null): mixed {
		$hooks = $this->collectHooks($hooks, $arguments);
		$result = $default;
		foreach ($hooks as $hook) {
			[$callable, $arguments] = $hook;
			$result = Hookable::hookResults($result, $callable, $arguments, $hook_callback, $result_callback);
		}
		return $result;
	}

	/**
	 * Convert a callable to a string for output/debugging. Blank for anything which
	 * is not a unique ID.
	 *
	 * @param mixed $callable
	 * @return string
	 */
	public static function callable_string(mixed $callable): string {
		if (is_array($callable)) {
			return is_object($callable[0]) ? strtolower(get_class($callable[0])) . '::' . $callable[1] : implode('::', $callable);
		} elseif (is_string($callable)) {
			return $callable;
		} elseif ($callable instanceof Closure) {
			return '';
		}
		return '';
	}

	/**
	 * Utility function to convert an array of callable strings into an array of strings
	 *
	 * @param Callable[] $callables
	 * @return string[]
	 */
	public static function callable_strings(array $callables): array {
		$result = [];
		foreach ($callables as $callable) {
			$result[] = self::callable_string($callable);
		}
		return $result;
	}

	public function shutdown(): void {
		try {
			$this->call(Hooks::HOOK_EXIT, $this->application);
		} catch (Throwable $e) {
			PHP::log($e);
		}
		$this->_applicationExitCheck();
		$this->hooks = [];
		$this->hooksCalled = [];
		$this->hooksFailed = [];
		$this->hookCache = [];
		$this->allHookClasses = [];
	}
}
