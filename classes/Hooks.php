<?php
/**
 * @package zesk
 * @subpackage kernel
 * @author kent
 * @copyright &copy; 2018 Market Acumen, Inc.
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
	const hook_database_configure = "database_configure";

	/**
	 *
	 * @var string
	 */
	const HOOK_CONFIGURED = "configured";

	/**
	 *
	 * @var string
	 */
	const hook_configured = "configured";

	/**
	 *
	 * @var string
	 */
	const hook_reset = "reset";

	/**
	 *
	 * @var string
	 */
	const hook_exit = "exit";

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
	 * @var HookGroup[string]
	 */
	private $hooks = array();

	/**
	 * Hook alias table for old-call to new-call.
	 *
	 * @var array of oldname => newname
	 */
	private $hook_aliases = array();

	/**
	 * Argument definitions for hooks
	 *
	 * @var array
	 */
	private $hook_definitions = array();

	/**
	 *
	 * @var array
	 */
	private $hooks_called = array();

	/**
	 *
	 * @var array
	 */
	private $hook_cache = array();

	/**
	 * Used to track which top-level classes have been gathered yet
	 *
	 * @var array
	 */
	private $all_hook_classes = array();

	/**
	 *
	 * @param Kernel $kernel
	 */
	public function __construct(Kernel $kernel) {
		$this->kernel = $kernel;

		/*  TODO PHP7 use closure */
		register_shutdown_function(array(
			$this,
			"_app_call"
		), self::hook_exit);

		/* @deprecated Shutdown TODO PHP7 use closure */
		register_shutdown_function(array(
			$this,
			"_app_call"
		), 'shutdown');
	}

	/**
	 *
	 * @param string $hook
	 */
	public function _app_call($hook) {
		$this->call(self::hook_exit, $this->kernel->application());
	}

	/**
	 *
	 * @return array
	 */
	public function initialize() {
		$this->hooks = array();
		$this->hook_aliases = array();
		$this->hook_definitions = array();
		$this->hooks_called = array();
		$all_hook_classes = $this->all_hook_classes;
		$this->all_hook_classes = array();
		return $all_hook_classes;
	}

	/**
	 * @todo does this work?
	 *
	 */
	public function reset() {
		$this->call(Hooks::hook_reset);
		foreach ($this->initialize() as $class) {
			$this->register_class($class);
		}
	}

	/**
	 * Given a passed-in hook name, normalize it and return the internal name
	 *
	 * @param string $name
	 *        	Hook name
	 * @param boolean $alias
	 */
	private function _hook_name($name, $alias = false) {
		if (!is_string($name)) {
			throw new Exception_Parameter("{method}({name},...) {type} is not string", array(
				"method" => __METHOD__,
				"name" => _dump($name),
				"type" => type($name)
			));
		}
		// For now, we just make it lower case
		$name = strtolower($name);
		return !$alias ? $name : (isset($this->hook_aliases[$name]) ? $this->hook_aliases[$name] : $name);
	}

	/**
	 * Remove hooks - use with caution
	 *
	 * @param string $hook
	 */
	public function unhook($hook) {
		$hook = $this->_hook_name($hook, true);
		unset($this->hooks[$hook]);
	}

	/**
	 *
	 * @param unknown $hooks
	 * @return mixed
	 */
	private function hook_load_definitions($hooks) {
		$definitions = array();
		$found = array();
		foreach (to_list($hooks) as $hook) {
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
	 * enforced by calling $hooks->register_class('dependency1;dependency2'); as the first line to
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
	 * if ($this->getb('foo::enabled')) {
	 * // Do something important
	 * }
	 * }
	 * }
	 * </code>
	 *
	 * @param mixed $classes
	 *        	List of classes to invoke the static "hooks" method for.
	 *
	 * @return array Hook class name eith the time invoked, or an Exception if an error occurred.
	 */
	public function register_class($class = null, $options = null) {
		if ($class === null) {
			return $this->hooks_called;
		}
		if (is_string($class)) {
			return $this->_register_class_hooks($class);
		}
		$classes = to_list($class, array());
		$result = true;
		foreach ($classes as $class) {
			if (!$this->register_class($class, $options)) {
				$result = false;
			}
		}
		return $result;
	}

	/**
	 *
	 * @param unknown $class
	 * @return boolean|Exception
	 */
	private function _register_class_hooks($class) {
		$lowclass = strtolower($class);
		if (isset($this->hooks_called[$lowclass])) {
			return false;
		}
		if (method_exists($class, "hooks")) {
			try {
				call_user_func(array(
					$class,
					"hooks"
				), $this->kernel->application());
				$this->hooks_called[$lowclass] = $result[$class] = microtime(true);
				return true;
			} catch (\Exception $e) {
				$this->call("exception", $e);
				$this->hooks_called[$lowclass] = $result[$class] = $e;
				return $e;
			}
		} else if ($this->debug) {
			$this->kernel->logger->debug("{__CLASS__}::{__FUNCTION__} Class {class} does not have method hooks", array(
				"__CLASS__" => __CLASS__,
				"__FUNCTION__" => __FUNCTION__,
				"class" => $class
			));
			$this->hooks_called[$lowclass] = false;
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
	 *        	A hook name, or a list of hooks, separated by ";", or an array of hook names
	 * @return true If any hook exists. If null passed then returns an array of keys => arrays
	 *         described above.
	 */
	public function has($hooks = null) {
		if ($hooks === null) {
			return $this->hook_cache;
		}
		if (is_string($hooks)) {
			$hook = $this->_hook_name($hooks);
			if ($this->profile_hooks) {
				$ding = microtime(true);
				if (!array_key_exists($hook, $this->hook_cache)) {
					$this->hook_cache[$hook] = array(
						1,
						$ding,
						$ding
					);
				} else {
					$this->hook_cache[$hook][0]++;
					$this->hook_cache[$hook][2] = $ding;
				}
			}
			return array_key_exists($hook, $this->hooks);
		}
		if (is_array($hooks)) {
			foreach ($hooks as $hook) {
				$ding = microtime(true);
				$result = $this->has($hook);
				if ($result) {
					return $result;
				}
			}
			return null;
		}
		return false;
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
	 *        	Hook name. Can be any string. Typically of the form CLASS::method
	 * @param mixed $function
	 *        	A function or class name, or an array to specify an object method or object static
	 *        	method.
	 * @param array $options
	 *        	Return value handling, ordering, arguments.
	 *
	 */
	public function add($hook, $function = null, $options = array()) {
		if ($hook === null) {
			return;
		}
		if (is_string($options)) {
			$options = array(
				$options => true
			);
		} else if (!is_array($options)) {
			$options = array();
		}
		$hook = $this->_hook_name($hook, true);
		if (!array_key_exists($hook, $this->hooks)) {
			$hook_group = new HookGroup();
			$this->hooks[$hook] = $hook_group;
		} else {
			$hook_group = $this->hooks[$hook];
		}
		if (!is_callable($function)) {
			throw new Exception_Semantics($this->callable_string($function) . " is not callable");
		}
		$callable_string = $this->callable_string($function);
		if (array_key_exists($callable_string, $this->hooks[$hook])) {
			$this->kernel->logger->debug("Duplicate registration of hook {callable}", array(
				"callable" => $callable_string
			));
			return;
		}
		$options['callable'] = ($function === null ? $hook : $function);
		$n = count($this->hooks[$hook]);
		if (isset($options['first'])) {
			$hook_group->first = array_merge(array(
				$callable_string => $options
			), $hook_group->first);
		} else if (isset($options['last'])) {
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
	 *        	List of methods (array or ;-separated string)
	 */
	public function find_all($methods) {
		$class_methods = to_list($methods);
		$methods = array();
		foreach ($class_methods as $class_method) {
			list($class, $method) = pair($class_method, "::", null, $class_method);
			if ($class === null) {
				continue;
			}
			$lowclass = strtolower($class);
			if (!array_key_exists($lowclass, $this->all_hook_classes) && $method !== "hooks") {
				$this->all_hook_classes[$lowclass] = true;
				$this->_register_all_hooks($class, $this->kernel->application());
			}
			$classes = $this->kernel->classes->subclasses($class);
			if ($classes === null) {
				continue;
			}
			//echo "register_class($class) -> "; dump($classes);
			foreach ($classes as $class) {
				try {
					$refl = new \ReflectionClass($class);
				} catch (\Exception $e) {
					$this->kernel->logger->warning("{class} not found {eclass}: {emessage}", array(
						"class" => $class,
						"eclass" => get_class($e),
						"emessage" => $e->getMessage()
					));
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
	public function remove($hook) {
		$hook = $this->_hook_name($hook);
		if (isset($this->hooks[$hook])) {
			unset($this->hooks[$hook]);
			return true;
		}
		return false;
	}

	/**
	 * Allow easy migration from old names to new
	 * Retrieve all aliases:
	 * <code>
	 * $all_aliases = $application->hooks->alias();
	 * </code>
	 * Retrieve a single alias:
	 * <code>
	 * $alias = $application->hooks->alias('aliasname');
	 * </code>
	 * Delete an alias:
	 * <code>
	 * $old_alias = $application->hooks->alias('aliasname', false);
	 * </code>
	 * Add an alias:
	 * <code>
	 * $previous_alias = $application->hooks->alias('oldname', 'newname');
	 * </code>
	 * Bulk actions:
	 * <code>
	 * $results = $application->hooks->alias(
	 * array(
	 * 'setone' => 'newvalue',
	 * 'getone' => null,
	 * 'getanother' => null,
	 * 'unsetone' => false,
	 * )
	 * );
	 * </code>
	 *
	 * @param string $oldname
	 * @param string $newname
	 * @return mixed
	 */
	public function alias($oldname = null, $newname = null) {
		if (is_array($oldname)) {
			$result = array();
			foreach ($oldname as $old => $new) {
				$result[$old] = $this->hook_alias($old, $new);
			}
			return $result;
		}
		if ($oldname === null) {
			return $this->hook_aliases;
		} else if ($newname === null) {
			$oldname = $this->_hook_name($oldname, false);
			return avalue($this->hook_aliases, $oldname);
		} else {
			$previous = avalue($this->hook_aliases, $oldname);
			if ($newname === false) {
				unset($this->hook_aliases[$oldname]);
			} else {
				$newname = $this->_hook_name($newname);
				if ($oldname === $newname) {
					return $previous;
				}
				$this->hook_aliases[$oldname] = $newname;
				if (array_key_exists($oldname, $this->hooks)) {
					$oldhooks = $this->hooks[$oldname];
					if (isset($this->hooks[$newname])) {
						$this->hooks[$newname]->merge($oldhooks);
						unset($this->hooks[$oldname]);
					}
				}
			}
			return $previous;
		}
	}

	/**
	 *
	 * @param unknown $class
	 */
	private function _register_all_hooks($class, Application $application) {
		$refl = new \ReflectionClass($class);
		$method = 'register_all_hooks';
		if ($refl->hasMethod($method)) {
			$refl->getMethod($method)->invokeArgs(null, array(
				$application
			));
		}
		$this->call("$class::register_all_hooks", $application);
	}

	/**
	 * Call a hook, with optional additional arguments
	 *
	 * @param string|list $hooks
	 *        	Hooks to call
	 * @return mixed
	 */
	public function call($hook) {
		$arguments = func_get_args();
		array_shift($arguments);
		return $this->call_arguments($hook, $arguments);
	}

	/**
	 *
	 * @param string|list $hooks
	 *        	Hooks to call
	 * @param array $arguments
	 *        	Arguments to pass to the first hook
	 * @param unknown $default
	 * @param unknown $hook_callback
	 * @param unknown $result_callback
	 * @param unknown $return_hint
	 *        	deprecated 2017-11
	 * @return string|NULL
	 */
	public function call_arguments($hooks, $arguments = array(), $default = null, $hook_callback = null, $result_callback = null, $return_hint = null) {
		if ($return_hint !== null) {
			$this->kernel->deprecated("\$return_hint passed to {method}", array(
				"method" => __METHOD__
			));
		}
		$hooks = $this->collect_hooks($hooks, $arguments);
		$result = $default;
		foreach ($hooks as $hook) {
			list($callable, $arguments) = $hook;
			$result = Hookable::hook_results($result, $callable, $arguments, $hook_callback, $result_callback);
		}
		return $result;
	}

	/**
	 *
	 * @param string|list $hooks
	 *        	Hooks to call
	 * @param array $arguments
	 *        	Arguments to pass to the first hook
	 * @param unknown $default
	 * @param unknown $hook_callback
	 * @param unknown $result_callback
	 * @param unknown $return_hint
	 *        	deprecated 2017-11
	 * @return string|NULL
	 */
	public function collect_hooks($hooks, $arguments = array()) {
		$definitions = $this->hook_load_definitions($hooks);
		$hooks = array();
		if (count($definitions) === 0) {
			return $hooks;
		}
		foreach ($definitions as $callable_string => $options) {
			$options_arguments = to_array(avalue($options, 'arguments'));
			$hooks[] = array(
				$options['callable'],
				count($options_arguments) > 0 ? array_merge($options_arguments, $arguments) : $arguments
			);
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
	public function all_call_arguments($methods, array $arguments = array(), $default = null, $hook_callback = null, $result_callback = null) {
		$methods = $this->find_all($methods);
		$result = $default;
		foreach ($methods as $class_method) {
			// Note: These two lines were reversed when this was zesk--all_hook_arguments
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
	public static function callable_string($callable) {
		if (is_array($callable)) {
			return is_object($callable[0]) ? strtolower(get_class($callable[0])) . "::" . $callable[1] : implode("::", $callable);
		} else if (is_string($callable)) {
			return $callable;
		} else if (gettype($callable) === "function") {
			return "Closure: " . strval($callable);
		}
		return "Unknown: " . type($callable);
	}

	/**
	 * Utility function to convert an array of callable strings into an array of strings
	 *
	 * @param Callable[] $callables
	 * @return string[]
	 */
	public static function callable_strings(array $callables) {
		$result = array();
		foreach ($callables as $callable) {
			$result[] = self::callable_string($callable);
		}
		return $result;
	}
}
