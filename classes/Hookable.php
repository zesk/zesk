<?php
declare(strict_types=1);
/**
 * @package zesk
 * @subpackage kernel
 * @author kent
 * @copyright &copy; 2022 Market Acumen, Inc.
 */

namespace zesk;

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
	 * Save nothing herein. (Explicitly ignores $this->application)
	 *
	 * {@inheritDoc}
	 * @see \zesk\Options::__sleep()
	 */
	public function __sleep() {
		return parent::__sleep();
	}

	/**
	 * Problem with globals.
	 */
	public function __wakeup(): void {
		$this->application = __wakeup_application();
	}

	/**
	 * Invoke a hook on this object if it exists.
	 * Arguments should be passed after the type.
	 *
	 * Using this invocation method, you can not pass a hook callback or a result callback to
	 * process results, so this is best used for triggers which do not require a result.
	 *
	 * @param array|string $types
	 * @return mixed
	 * @see Hookable::hook_array
	 */
	final public function call_hook(array|string $types): mixed {
		if (empty($types)) {
			return $this;
		}
		$args = func_get_args();
		array_shift($args);
		return $this->call_hook_arguments($types, $args, $args[0] ?? null);
	}

	/**
	 * Invoke a hook on this object if it exists.
	 *
	 * Example of functions called for $user->call_hook_arguments("hello") is a User:
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
	 */
	final public function call_hook_arguments(array|string $types, array $args = [], mixed $default = null, callable $hook_callback = null, callable $result_callback = null): mixed {
		$hooks = $this->collect_hooks($types, $args);
		$result = $default;
		foreach ($hooks as $hook) {
			[$callable, $arguments] = $hook;
			$result = self::hook_results($result, $callable, $arguments, $hook_callback, $result_callback);
		}
		return $result;
	}

	/**
	 * Invoke a hook on this object if it exists.
	 *
	 * Example of functions called for $user->call_hook_arguments("hello") is a User:
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
	 * @param array|string $types An array of hooks to call, all hooks found are executed, and you can repeat if
	 *            necessary.
	 * @param array $args Optional. An array of parameters to pass to the hook.
	 * @return array
	 */
	final public function collect_hooks(array|string $types, array $args = []): array {
		if (empty($types)) {
			return [];
		}
		if (!is_array($args)) {
			$args = [
				$args,
			];
		}
		$types = to_list($types);
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
			$method = Hooks::clean_name($type);
			if ($method !== $type) {
				$this->application->deprecated("Hook \"{type}\" cleaned to \"{method}\" - please fix", compact("method", "type"));
			}
			if (method_exists($this, "hook_$method")) {
				$hooks[] = [
					[
						$this,
						"hook_$method",
					],
					$args,
				];
			}
			$methods = $this->_hooks[$type] ?? null;
			if (is_array($methods)) {
				foreach ($methods as $method) {
					$hooks[] = [
						$method,
						$zesk_hook_args,
					];
				}
			}
			$hook_names = ArrayTools::suffix($app->classes->hierarchy($this, __CLASS__), "::$type");
			$hooks = array_merge($hooks, $app->hooks->collect_hooks($hook_names, $zesk_hook_args));
		}
		return $hooks;
	}

	/**
	 *
	 * @param string $type
	 * @param callable $callable
	 * @return $this
	 */
	final public function add_hook(string $type, callable $callable) {
		$type = Hooks::clean_name($type);
		$this->_hooks[$type][] = $callable;
		return $this;
	}

	/**
	 * Does a hook exist for this object?
	 *
	 * @param mixed $types
	 * @param boolean $object_only
	 * @return boolean
	 */
	final public function has_hook(mixed $types, bool $object_only = false): bool {
		$hooks = $this->hook_list($types, $object_only);
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
	 */
	final public function hook_list(string|array $types, bool $object_only = false): array {
		$hooks = $this->application->hooks;
		$types = to_list($types);
		$result = [];
		foreach ($types as $type) {
			$method = Hooks::clean_name($type);
			$hook_method = "hook_$method";
			//echo get_class($this) . " checking for $hook_method\n";
			if (method_exists($this, $hook_method)) {
				$result[] = [
					$this,
					$hook_method,
				];
			}
			$methods = $this->_hooks[$type] ?? null;
			if (is_array($methods)) {
				foreach ($methods as $method) {
					$result[] = Hooks::callable_string($method);
				}
			}
			if (!$object_only) {
				$hook_names = ArrayTools::suffix($this->application->classes->hierarchy($this, __CLASS__), "::$type");
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
	 * @param callable $hook_callback
	 *            A function to call for each hook called.
	 * @param string $result_callback
	 *            A function to process hook results. If false, returns last result unmodified.
	 * @return mixed
	 */
	final public static function hook_results(mixed $previous_result, callable $callable, array $arguments, callable $hook_callback = null, callable $result_callback = null): mixed {
		if ($hook_callback) {
			call_user_func_array($hook_callback, [
				$callable,
				$arguments,
			]);
		}
		$new_result = call_user_func_array($callable, $arguments);
		if ($result_callback !== null) {
			if ($result_callback === false) {
				return $new_result;
			}
			return call_user_func($result_callback, $callable, $previous_result, $new_result, $arguments);
		}
		return self::combine_hook_results($previous_result, $new_result);
	}

	/**
	 * Combine hook results in chained/filter hooks in a predictable manner
	 *
	 *
	 *
	 * @param mixed $previous_result
	 * @param mixed $new_result
	 * @return mixed
	 */
	public static function combine_hook_results(mixed $previous_result, mixed $new_result): mixed {
		// If our old result was empty/void, then return new result
		if ($previous_result === null) {
			return $new_result;
		}
		//
		// KMD 2018-01: Handle when a hook returns NOTHING and a default value is supplied to call_hook_arguments.
		// Will use previous result.
		//
		if ($new_result === null) {
			return $previous_result;
		}
		if (is_array($previous_result) && is_array($new_result)) {
			if (count($previous_result) > 0 && ArrayTools::is_list($previous_result)) {
				return array_merge($previous_result, $new_result);
			} else {
				return $new_result + $previous_result;
			}
		}
		return $new_result;
	}

	/**
	 * Loading references
	 *
	 * @param string $class
	 * @return array
	 * @throws Exception_Lock
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
	 * @throws Exception_Lock
	 */
	public function default_options(string $class): array {
		$class = strtolower($class);
		// Class hierarchy is given from child -> parent
		$config = new Configuration();
		foreach ($this->_defaultOptions($class) as $subclass => $configuration) {
			// Child options override parent options
			$config->merge($configuration, false);
		}
		return $config->to_array();
	}

	/**
	 * Load options for this object based on globals loaded.
	 * Only overwrites values which are NOT set.
	 *
	 * @param ?string $class
	 *            Inherit globals from this class
	 * @return $this
	 * @throws Exception_Lock
	 * @deprecated 2022-01
	 */
	final public function inherit_global_options(string $class = null): self {
		return $this->inheritConfiguration($class);
	}

	/**
	 * Load options for this object based on the application configuration loaded.
	 * Only overwrites values which are NOT set.
	 *
	 * @param ?string $class
	 *            Inherit globals from this class
	 * @return $this
	 * @throws Exception_Lock
	 */
	final public function inheritConfiguration(string $class = null): self {
		if ($class === null) {
			$class = get_class($this);
		}
		$options = $this->default_options($class);
		foreach ($options as $key => $value) {
			$key = self::_optionKey($key);
			if (!isset($this->options[$key])) {
				$this->options[$key] = $value;
			}
		}
		return $this;
	}
}
