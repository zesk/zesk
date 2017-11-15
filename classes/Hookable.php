<?php

/**
 *
 */
namespace zesk;

/**
 * 2017-10 All Hookables must pass an $application as the first parameter for __construct now.
 *
 * The reason this can't be called "Hook" as a class is because we want the method "hook" to work.
 * But PHP 4.x supports same-name constructors, so, we're stuck with Hookable for now. Much easier
 * to deprecate class names than method names, IMHO.
 *
 * @todo When we're in a PHP version which is trait compatible, make this a trait
 *
 * @author kent
 */
class Hookable extends \zesk\Options {
	/**
	 *
	 * @var Application
	 */
	public $application = null;

	/**
	 *
	 * @param Application $application
	 * @param array $options
	 */
	function __construct(Application $application, array $options = array()) {
		$this->application = $application;
		parent::__construct($options);
	}
	
	function __wakeup() {
		// Only case where this is OK
		$this->application = zesk()->application();
	}

	/**
	 * Invoke a hook on this object if it exists.
	 * Arguments should be passed after the type.
	 *
	 * Using this invokation method, you can not pass a hook callback or a result callback to
	 * process results, so this is
	 * best used for triggers which do not require a result.
	 *
	 * @see Hookable::hook_array
	 * @deprecated 2016-09
	 * @param string $type
	 */
	public final function hook($type) {
		$this->application->deprecated();
		if (empty($type)) {
			return $this;
		}
		$args = func_get_args();
		array_shift($args);
		$default = avalue($args, 0);
		$result = $this->call_hook_arguments($type, $args, $default);
		return $result;
	}

	/**
	 *
	 * @deprecated 2016-08
	 * @see zesk\Hookable::call_hook_arguments
	 * @param unknown $types
	 * @param array $args
	 * @param unknown $default
	 * @param unknown $hook_callback
	 * @param unknown $result_callback
	 */
	public final function hook_array($types, $args = array(), $default = null, $hook_callback = null, $result_callback = null, $return_hint = null) {
		$this->application->deprecated();
		return $this->call_hook_arguments($types, $args, $default, $hook_callback, $result_callback, $return_hint);
	}

	/**
	 * Invoke a hook on this object if it exists.
	 * Arguments should be passed after the type.
	 *
	 * Using this invokation method, you can not pass a hook callback or a result callback to
	 * process results, so this is
	 * best used for triggers which do not require a result.
	 *
	 * @see Hookable::hook_array
	 * @param string $type
	 */
	public final function call_hook($type) {
		if (empty($type)) {
			return $this;
		}
		$args = func_get_args();
		array_shift($args);
		$default = avalue($args, 0);
		$result = $this->call_hook_arguments($type, $args, $default);
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
	 * 3. Object::hello
	 * 4. Hookable::hello
	 *
	 * Arguments passed as an array
	 *
	 * @param mixed $type
	 *        	An array of hooks to call, all hooks found are executed, and you can repeat if
	 *        	necessary.
	 * @param array $args
	 *        	Optional. An array of parameters to pass to the hook.
	 * @param mixed $default
	 *        	Optional. The value to return if the final result returned by a hook is NULL.
	 * @param callable $hook_callback
	 *        	Optional. A callable in the form `function ($callable, array $arguments) { ... }`
	 * @param callable $result_callback
	 *        	Optional. A callable in the form `function ($callable, $previous_result,
	 *        	$new_result) { ... }`
	 */
	public final function call_hook_arguments($types, $args = array(), $default = null, $hook_callback = null, $result_callback = null, $return_hint = null) {
		if (empty($types)) {
			return $default;
		}
		if (!is_array($args)) {
			$args = array(
				$args
			);
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
		$result = null;
		foreach ($types as $type) {
			$method = PHP::clean_function($type);
			if (method_exists($this, "hook_$method")) {
				$result = self::hook_results($result, array(
					$this,
					"hook_$method"
				), $args, $hook_callback, $result_callback);
			}
			$func = apath($this->options, "hooks.$method");
			if ($func) {
				$result = self::hook_results($result, $func, $args, $hook_callback, $result_callback, $return_hint);
			}
			$hooks = arr::suffix($app->classes->hierarchy($this, __CLASS__), "::$type");
			$result = $app->hooks->call_arguments($hooks, $zesk_hook_args, $result, $hook_callback, $result_callback, $return_hint);
		}
		return ($result === null) ? $default : $result;
	}

	/**
	 * Does a hook exist for this object?
	 *
	 * @param mixed $types
	 * @param boolean $object_only
	 * @return boolean
	 */
	public final function has_hook($types, $object_only = false) {
		$hooks = $this->hook_list($types, $object_only);
		return count($hooks) !== 0;
	}

	/**
	 * List functions to be invoked by a hook on this object if it exists.
	 * Arguments passed as an array
	 *
	 * @param mixed $type
	 *        	An array of hooks to call, first one found is executed, or a string of the hook to
	 *        	call
	 * @param array $args
	 *        	An array of parameters to pass to the hook.
	 */
	public final function hook_list($types, $object_only = false) {
		global $zesk;
		$hooks = $zesk->hooks;
		$types = to_list($types);
		$result = array();
		foreach ($types as $type) {
			$method = PHP::clean_function($type);
			$hook_method = "hook_$method";
			//echo get_class($this) . " checking for $hook_method\n";
			if (method_exists($this, $hook_method)) {
				$result[] = array(
					$this,
					$hook_method
				);
			}
			$func = apath($this->options, "hooks.$method");
			if ($func) {
				$result[] = $func;
			}
			if (!$object_only) {
				$hook_names = arr::suffix($zesk->classes->hierarchy($this, __CLASS__), "::$type");
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
	 * @param mixed $previous_result
	 *        	Previous hook result. Default to null for first call.
	 * @param mixed $callable
	 *        	Function
	 * @param array $arguments
	 * @param callable $hook_callback
	 *        	A function to call for each hook called.
	 * @param string $result_callback
	 *        	A function to process hook results
	 * @return mixed
	 */
	public static final function hook_results($previous_result, $callable, array &$arguments, $hook_callback = null, $result_callback = null, $return_hint = null) {
		if ($hook_callback) {
			call_user_func_array($hook_callback, array(
				$callable,
				$arguments
			));
		}
		$new_result = call_user_func_array($callable, $arguments);
		if ($result_callback !== null) {
			return call_user_func($result_callback, $callable, $previous_result, $new_result, $arguments, $return_hint);
		}
		return self::combine_hook_results($previous_result, $new_result, $arguments, $return_hint);
	}

	/**
	 * Combine hook results in chained/filter hooks in a predictable manner
	 *
	 * @param mixed $previous_result
	 * @param mixed $new_result
	 * @param mixed $return_hint
	 * @return mixed
	 */
	public static function combine_hook_results($previous_result, $new_result, array &$arguments, $return_hint = null) {
		if ($previous_result === null) {
			return $new_result;
		}
		if (is_numeric($return_hint)) {
			$arguments[$return_hint] = $previous_result;
			return $new_result;
		}
		if (is_string($previous_result) && is_string($new_result)) {
			return $previous_result . $new_result;
		}
		if (is_array($previous_result) && is_array($new_result)) {
			if (arr::is_list($previous_result)) {
				return array_merge($previous_result, $new_result);
			} else {
				return $new_result + $previous_result;
			}
		}
		// No way to combine
		return $new_result;
	}

	/**
	 *
	 * @todo global remove
	 * @var array
	 */
	private static $option_references = array();

	/**
	 * Loading references
	 *
	 * @param unknown $class
	 * @return mixed
	 */
	private function _default_options($class) {
		$references = array();
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
	 * zesk::geta("Control_Thing_Example::name1");
	 * zesk::geta("Control_Thing::name1");
	 * zesk::geta("Control::name1");
	 * zesk::geta("Options::name1");
	 *
	 * @param string $class
	 * @return array
	 */
	function default_options($class) {
		$class = strtolower($class);
		$opts = array();
		// Class hierarchy is given from child -> parent
		$config = new Configuration();
		foreach ($this->_default_options($class) as $subclass => $configuration) {
			// Child options override parent options
			$config->merge($configuration, false);
		}
		return $config->to_array();
	}

	/**
	 * Load options for this object based on globals loaded.
	 * Only overwrites values which are NOT set.
	 *
	 * @param string $class
	 *        	Inherit globals from this class
	 * @return Command
	 */
	final function inherit_global_options($class = null) {
		if ($class instanceof Application) {
			$this->application->deprecated("{method} no longer takes application", array(
				"method" => __METHOD__
			));
			$class = func_get_arg(1);
		}
		if ($class === null) {
			$class = get_class($this);
		}
		if (is_object($class)) {
			$class = get_class($class);
		}
		$options = $this->default_options($class);
		foreach ($options as $key => $value) {
			$key = self::_option_key($key);
			if (!isset($this->options[$key])) {
				$this->options[$key] = $value;
			}
		}
		return $this;
	}
}
