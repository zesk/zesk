<?php
declare(strict_types=1);
/**
 * @package zesk
 * @subpackage kernel
 * @author kent
 * @copyright &copy; 2022, Market Acumen, Inc.
 */

namespace zesk;

/**
 *
 * @author kent
 *
 */
class Hooks {
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
	 *
	 * @var string
	 */
	public const HOOK_RESET = 'reset';

	/**
	 *
	 * @var string
	 */
	public const HOOK_EXIT = 'exit';

	/**
	 * Output a debug log when a class is called with ::hooks but does not implement it
	 *
	 * @var boolean
	 */
	public $debug = false;

	/**
	 *
	 * @var Kernel
	 */
	public $kernel = null;

	/**
	 * Determine which hooks are looked at/tested for existence.
	 * Retrieve with ->has()
	 *
	 * @var boolean
	 */
	public $profile_hooks = false;

	/**
	 * System hooks for adding custom functionality throughout the system
	 *
	 * @var array
	 */
	private $hooks = [];

	/**
	 * Hook alias table for old-call to new-call.
	 *
	 * @var array of oldname => newname
	 */
	private $hook_aliases = [];

	/**
	 * Argument definitions for hooks
	 *
	 * @var array
	 */
	private $hook_definitions = [];

	/**
	 *
	 * @var array
	 */
	private $hooks_called = [];

	/**
	 *
	 * @var array
	 */
	private $hook_cache = [];

	/**
	 * Used to track which top-level classes have been gathered yet
	 *
	 * @var array
	 */
	private $all_hook_classes = [];

	/**
	 *
	 * @param Kernel $kernel
	 */
	public function __construct(Kernel $kernel) {
		$this->kernel = $kernel;

		/*  TODO PHP7 use closure */
		register_shutdown_function([$this, '_app_call', ], self::HOOK_EXIT);

		/* @deprecated Shutdown TODO PHP7 use closure */
		register_shutdown_function([$this, '_app_call', ], 'shutdown');

		register_shutdown_function([$this, '_app_check_error', ]);
	}

	private static $fatals = [
		E_USER_ERROR => 'Fatal Error',
		E_ERROR => 'Fatal Error',
		E_PARSE => 'Parse Error',
		E_CORE_ERROR => 'Core Error',
		E_CORE_WARNING => 'Core Warning',
		E_COMPILE_ERROR => 'Compile Error',
		E_COMPILE_WARNING => 'Compile Warning',
	];

	/**
	 * Shutdown functino to log errors
	 */
	public function _app_check_error(): void {
		if (!$err = error_get_last()) {
			return;
		}
		if (isset(self::$fatals[$err['type']])) {
			$msg = __METHOD__ . ': ' . self::$fatals[$err['type']] . ': ' . $err['message'] . ' in ';
			$msg .= $err['file'] . ' on line ' . $err['line'];
			error_log($msg);
		}
	}

	/**
	 *
	 * @param string $hook
	 */
	public function _app_call(string $hook): void {
		try {
			$this->call($hook, $this->kernel->application());
		} catch (Exception_Semantics $e) {
			// Be the river.
		}
	}

	/**
	 *
	 * @return array
	 */
	private function resetAllHookClasses(): array {
		$this->hooks = [];
		$this->hook_aliases = [];
		$this->hook_definitions = [];
		$this->hooks_called = [];
		$all_hook_classes = $this->all_hook_classes;
		$this->all_hook_classes = [];
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
	public static function clean_name($name) {
		return trim(strtolower($name));
	}

	/**
	 * Given a passed-in hook name, normalize it and return the internal name
	 *
	 * @param string $name
	 *            Hook name
	 * @param boolean $alias
	 */
	private function _hook_name(string $name, bool $alias = false): string {
		$name = self::clean_name($name);
		return !$alias ? $name : ($this->hook_aliases[$name] ?? $name);
	}

	/**
	 * Remove hooks - use with caution
	 *
	 * @param string $hook
	 */
	public function unhook(string $hook): void {
		$hook = $this->_hook_name($hook, true);
		unset($this->hooks[$hook]);
	}

	/**
	 *
	 * @param array $hooks
	 * @return array
	 */
	private function hook_load_definitions(iterable $hooks): array {
		$definitions = [];
		$found = [];
		foreach ($hooks as $hook) {
			$hook = $this->_hook_name($hook);
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
	 * A list of classes is passed in which are autoloaded and
	 * then ::hooks is called for them. Every call is called once and only once, order must not
	 * matter, but can be
	 * enforced by calling $hooks->registerClass('dependency1;dependency2'); as the first line to
	 * your hooks
	 * registration call.
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
			$this->_register_class_hooks($classes);
		}
		return $this;
	}

	/**
	 * @return array
	 */
	public function hooksCalled(): array {
		return $this->hooks_called;
	}

	/**
	 *
	 * @param string $class
	 * @return bool
	 */
	private function _register_class_hooks(string $class): bool {
		$lowClass = strtolower($class);
		if (isset($this->hooks_called[$lowClass])) {
			return false;
		}
		if (method_exists($class, 'hooks')) {
			try {
				call_user_func([$class, 'hooks', ], $this->kernel->application());
				$this->hooks_called[$lowClass] = $result[$class] = microtime(true);
				return true;
			} catch (\Exception $e) {
				$this->call('exception', $e);
				$this->hooks_called[$lowClass] = $result[$class] = $e;
				return false;
			}
		} elseif ($this->debug) {
			$this->kernel->logger->debug('{__CLASS__}::{__FUNCTION__} Class {class} does not have method hooks', [
				'__CLASS__' => __CLASS__,
				'__FUNCTION__' => __FUNCTION__,
				'class' => $class,
			]);
			$this->hooks_called[$lowClass] = false;
			return true;
		}
		return false;
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
			$hook = $this->_hook_name($hooks);
			if ($this->profile_hooks) {
				$ding = microtime(true);
				if (!isset($this->hook_cache[$hook])) {
					$this->hook_cache[$hook] = [1, $ding, $ding, ];
				} else {
					$this->hook_cache[$hook][0]++;
					$this->hook_cache[$hook][2] = $ding;
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
	public function hookCache() {
		return $this->hook_cache;
	}

	/**
	 * Hooks are very flexible, and each hook determines how it is combined with the next hook.
	 *
	 * Valid options are:
	 *
	 * - 'first' - boolean. Optional. Invoke this hook first before all other hooks
	 * - 'last' - boolean. Optional. Invoke this hook last after all other hooks
	 * - 'arguments' - array. A list of arguments to pass to the hook. Any additional argments are
	 * passed after these.
	 *
	 * @param string $hook
	 *            Hook name. Can be any string. Typically of the form CLASS::method
	 * @param mixed $function
	 *            A function or class name, or an array to specify an object method or object static
	 *            method.
	 * @param array $options
	 *            Return value handling, ordering, arguments.
	 *
	 * @return void
	 */
	public function add(string $hook, callable $function, array $options = []): void {
		$hook = $this->_hook_name($hook, true);
		if (!array_key_exists($hook, $this->hooks)) {
			$hook_group = new HookGroup();
			$this->hooks[$hook] = $hook_group;
		} else {
			$hook_group = $this->hooks[$hook];
		}
		$callable_string = $this->callable_string($function);
		if ($hook_group->has($callable_string)) {
			$this->kernel->logger->debug('Duplicate registration of hook {callable}', ['callable' => $callable_string, ]);
			return;
		}
		$options['callable'] = $function;
		if (isset($options['first'])) {
			$hook_group->first = array_merge([$callable_string => $options, ], $hook_group->first);
		} elseif (isset($options['last'])) {
			$hook_group->last[$callable_string] = $options;
		} else {
			$hook_group->middle[$callable_string] = $options;
		}
	}

	/**
	 * Find all hooks given a class::method string - finds all items of class which have method
	 * method
	 *
	 * @param mixed $methods
	 *            List of methods (array or ;-separated string)
	 */
	public function find_all(array $class_methods): array {
		$methods = [];
		foreach ($class_methods as $class_method) {
			[$class, $method] = pair($class_method, '::', '', $class_method);
			if ($class === '') {
				continue;
			}
			$low_class = strtolower($class);
			if (!array_key_exists($low_class, $this->all_hook_classes) && $method !== 'hooks') {
				$this->all_hook_classes[$low_class] = true;
				$this->_register_all_hooks($class, $this->kernel->application());
			}
			$classes = $this->kernel->classes->subclasses($class);
			if ($classes === null) {
				continue;
			}
			//echo "registerClass($class) -> "; dump($classes);
			foreach ($classes as $class) {
				try {
					$refl = new \ReflectionClass($class);
				} catch (\Exception $e) {
					$this->kernel->logger->warning('{class} not found {eclass}: {emessage}', [
						'class' => $class,
						'eclass' => $e::class,
						'emessage' => $e->getMessage(),
					]);

					continue;
				}
				if (!$refl->hasMethod($method)) {
					//echo "$class - no $method\n";
					continue;
				}
				$refl_method = $refl->getMethod($method);
				if (!$refl_method->isStatic()) {
					// Only run on static methods
					//					echo " - $method exists but is NOT static\n";
					continue;
				}
				//echo " - has $method\n";
				/* @var $refl_method ReflectionMethod */
				$declaring = $refl_method->getDeclaringClass()->name;
				//	echo "$class ($declaring) -> ";
				if (strcasecmp($declaring, $class) === 0) {
					//$methods[] = "$declaring*$class::$method";
					$full_method = "$class::$method";
				} else {
					$full_method = "$declaring::$method";
				}
				$methods[$full_method] = $full_method;
			}
		}
		return $methods;
	}

	/**
	 * Remove hooks - use with caution
	 *
	 * @param string $hook
	 * @return boolean true if removed, false if not found
	 */
	public function keysRemove(string $hook): bool {
		$hook = $this->_hook_name($hook);
		if (isset($this->hooks[$hook])) {
			unset($this->hooks[$hook]);
			return true;
		}
		return false;
	}

	/**
	 * Add an alias
	 *
	 * @param string $old_name
	 * @param string $new_name
	 * @return ?string old alias
	 */
	public function setAlias(string $old_name, string $new_name): ?string {
		$previous = $this->hook_aliases[$old_name] ?? null;
		$new_name = $this->_hook_name($new_name);
		if ($old_name === $new_name) {
			return $previous;
		}
		$this->hook_aliases[$old_name] = $new_name;
		if (array_key_exists($old_name, $this->hooks)) {
			$old_hooks = $this->hooks[$old_name];
			if (isset($this->hooks[$new_name])) {
				$this->hooks[$new_name]->merge($old_hooks);
				unset($this->hooks[$old_name]);
			}
		}
		return $previous;
	}

	/**
	 * Delete an alias
	 *
	 * @param string $name
	 * @return bool
	 */
	public function clearAlias(string $name): bool {
		$name = $this->_hook_name($name, false);
		if (isset($this->hook_aliases[$name])) {
			unset($this->hook_aliases[$name]);
			return true;
		}
		return false;
	}

	/**
	 * Retrieve a single alias
	 * @param string $name
	 * @return string|null
	 */
	public function getAlias(string $name): ?string {
		$name = $this->_hook_name($name, false);
		return $this->hook_aliases[$name] ?? null;
	}

	/**
	 * Retrieve all aliases (old => new)
	 *
	 * @return array
	 */
	public function aliases(): array {
		return $this->hook_aliases;
	}

	/**
	 *
	 * @param unknown $class
	 */
	private function _register_all_hooks($class, Application $application): void {
		$refl = new \ReflectionClass($class);
		$method = 'register_all_hooks';
		if ($refl->hasMethod($method)) {
			$refl->getMethod($method)->invokeArgs(null, [$application, ]);
		}
		$this->call("$class::register_all_hooks", $application);
	}

	/**
	 * Call a hook, with optional additional arguments
	 *
	 * @param string|list $hooks
	 *            Hooks to call
	 * @return mixed
	 */
	public function call(mixed $hook): mixed {
		$arguments = func_get_args();
		array_shift($arguments);
		return $this->call_arguments($hook, $arguments);
	}

	/**
	 *
	 * @param string|list $hooks
	 *            Hooks to call
	 * @param array $arguments
	 *            Arguments to pass to the first hook
	 * @param unknown $default
	 * @param unknown $hook_callback
	 * @param unknown $result_callback
	 * @param unknown $return_hint
	 *            deprecated 2017-11
	 * @return string|NULL
	 */
	public function call_arguments(mixed $hooks, array $arguments = [], mixed $default = null, callable $hook_callback = null, callable $result_callback = null, mixed $return_hint = null) {
		if ($return_hint !== null) {
			$this->kernel->deprecated('$return_hint passed to {method}', ['method' => __METHOD__, ]);
		}
		$hooks = $this->collect_hooks($hooks, $arguments);
		$result = $default;
		foreach ($hooks as $hook) {
			[$callable, $arguments] = $hook;
			$result = Hookable::hook_results($result, $callable, $arguments, $hook_callback, $result_callback);
		}
		return $result;
	}

	/**
	 *
	 * @param string|array $hooks
	 *            Hooks to call
	 * @param array $arguments
	 *            Arguments to pass to the first hook
	 * @return array[array]
	 */
	public function collect_hooks(mixed $hooks, array $arguments = []): array {
		$definitions = $this->hook_load_definitions(to_iterable($hooks));
		$hooks = [];
		if (count($definitions) === 0) {
			return $hooks;
		}
		foreach ($definitions as $callable_string => $options) {
			$options_arguments = to_array(avalue($options, 'arguments'));
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
	 * @param list|string $methods
	 * @param array $arguments
	 * @param mixed $default
	 * @param callable $hook_callback
	 * @param callable $result_callback
	 * @return mixed
	 */
	public function all_call($methods) {
		$args = func_get_args();
		$methods = array_shift($args);
		return $this->all_call_arguments($methods, $args);
	}

	/**
	 * Invoke a global hook by type
	 *
	 * @param list|string $methods
	 * @param array $arguments
	 * @param mixed $default
	 * @param callable $hook_callback
	 * @param callable $result_callback
	 * @return mixed
	 * @see self::find_all
	 */
	public function all_call_arguments(array $methods, array $arguments = [], mixed $default = null, callable $hook_callback = null, callable $result_callback = null): mixed {
		$methods = $this->find_all($methods);
		$result = $default;
		foreach ($methods as $class_method) {
			$result = $this->call_arguments($class_method, $arguments, $result, $hook_callback, $result_callback);
			$result = Hookable::hook_results($result, $class_method, $arguments, $hook_callback, $result_callback);
		}
		return $result;
	}

	/**
	 * Convert a callable to a string for output/debugging
	 *
	 * @param mixed $callable
	 * @return string
	 */
	public static function callable_string(mixed $callable): string {
		if (is_array($callable)) {
			return is_object($callable[0]) ? strtolower(get_class($callable[0])) . '::' . $callable[1] : implode('::', $callable);
		} elseif (is_string($callable)) {
			return $callable;
		} elseif (gettype($callable) === 'function') {
			return 'Closure: ' . strval($callable);
		}
		return 'Unknown: ' . type($callable);
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

	/**
	 *
	 * @deprecated 2019-07
	 * @var string
	 */
	public const hook_database_configure = self::HOOK_DATABASE_CONFIGURE;

	/**
	 * @deprecated 2019-07
	 * @var string
	 */
	public const hook_reset = self::HOOK_RESET;

	/**
	 *
	 * @deprecated 2019-07
	 * @var string
	 */
	public const hook_exit = self::HOOK_EXIT;

	/**
	 *
	 * @deprecated 2019-07
	 * @var string
	 */
	public const hook_configured = 'configured';
}
