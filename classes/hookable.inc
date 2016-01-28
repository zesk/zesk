<?php
/**
 *
 * @version $URL$
 * @package zesk
 * @subpackage system
 * @author $Author: kent $
 * @copyright Copyright &copy; 2011, Market Acumen, Inc.
 */

/**
 * The reason this can't be called "Hook" as a class is because we want the method "hook" to work.
 * But PHP 4.x supports same-name constructors, so, we're stuck with Hookable for now. Much easier to
 * deprecate class names than method names, IMHO.
 * @author kent
 */
class Hookable extends Options {
	/**
	 * Invoke a hook on this object if it exists.
	 * Arguments should be passed after the type.
	 *
	 * Using this invokation method, you can not pass a hook callback or a result callback to process results, so this is
	 * best used for triggers which do not require a result.
	 *
	 * @see Hookable::hook_array
	 * @param string $type
	 */
	public final function hook($type) {
		if (empty($type)) {
			return $this;
		}
		$args = func_get_args();
		array_shift($args);
		$default = avalue($args, 0);
		$result = $this->hook_array($type, $args, $default);
		return $result;
	}

	/**
	 * Invoke a hook on this object if it exists.
	 *
	 * Example of functions called for $user->hook_array("hello") is a User:
	 *
	 * $user->hook_hello (if it exists)
	 * callable stored in $this->options['hooks']['hello'] (if it exists)
	 * Any zesk hooks registered as (in order):
	 *   1. User::hello
	 *   2. Zesk_User::hello
	 *   3. Object::hello
	 *   4. Hookable::hello
	 *
	 * Arguments passed as an array
	 * @param mixed $type An array of hooks to call, all hooks found are executed, and you can repeat if necessary.
	 * @param array $args Optional. An array of parameters to pass to the hook.
	 * @param mixed $default Optional. The value to return if the final result returned by a hook is NULL.
	 * @param callable $hook_callback Optional. A callable in the form `function ($callable, array $arguments) { ... }`
	 * @param callable $result_callback Optional. A callable in the form `function ($callable, $previous_result, $new_result) { ... }`
	 */
	public final function hook_array($types, $args = array(), $default = null, $hook_callback = null, $result_callback = null) {
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
		 * Add $this to zesk:: hooks
		 */
		$zesk_hook_args = $args;
		array_unshift($zesk_hook_args, $this);

		/*
		 * For each hook, call internal hook, then options-based hook, then zesk:: hook.
		 */
		$result = null;
		foreach ($types as $type) {
			$method = zesk::clean_function($type);
			if (method_exists($this, "hook_$method")) {
				$result = self::hook_results($result, array(
					$this,
					"hook_$method"
				), $args, $hook_callback, $result_callback);
			}
			$func = apath($this->options, "hooks.$method");
			if ($func) {
				$result = self::hook_results($result, $func, $args, $hook_callback, $result_callback);
			}
			$hooks = arr::suffix(zesk::class_hierarchy($this, __CLASS__), "::$type");
			$result = zesk::hook_array($hooks, $zesk_hook_args, $result, $hook_callback, $result_callback);
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
	 * @param mixed $type An array of hooks to call, first one found is executed, or a string of the hook to call
	 * @param array $args An array of parameters to pass to the hook.
	 */
	public final function hook_list($types, $object_only = false) {
		$types = to_list($types);
		$result = array();
		foreach ($types as $type) {
			$method = zesk::clean_function($type);
			if (method_exists($this, "hook_$method")) {
				$result[] = array(
					$this,
					"hook_$method"
				);
			}
			$func = arr::path($this->options, "hooks.$method");
			if ($func) {
				$result[] = $func;
			}
			if (!$object_only) {
				$hooks = arr::suffix(zesk::class_hierarchy($this), "::$type");
				$result = array_merge($result, zesk::find_all_hooks($hooks));
			}
		}
		return $result;
	}

	/**
	 * Combine hook results in a consistent manner when more than one hook applies to a call.
	 *
	 * @param mixed $previous_result Previous hook result. Default to null for first call.
	 * @param mixed $callable Function
	 * @param array $arguments
	 * @param callable $hook_callback A function to call for each hook called.
	 * @param string $result_callback A function to process hook results
	 * @return mixed
	 */
	public static final function hook_results($previous_result, $callable, array &$arguments, $hook_callback = null, $result_callback = null, $return_hint = null) {
		//echo "callable=" . zesk::callable_string($callable) . " callback=" . zesk::callable_string($hook_callback) . "\n";
		if ($hook_callback) {
			call_user_func_array($hook_callback, array(
				$callable,
				$arguments
			));
		}
		//log::debug("hook_results: Calling " . zesk::callable_string($callable));
		$new_result = call_user_func_array($callable, $arguments);
		if ($result_callback !== null) {
			return call_user_func($result_callback, $callable, $previous_result, $new_result);
		}
		return self::combine_hook_results($previous_result, $new_result, $return_hint);
	}
	
	public static function combine_hook_results($previous_result, $new_result, $return_hint = null) {
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

}
