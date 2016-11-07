<?php
/**
 * 
 */
namespace zesk;

/**
 * Encapsulation of a permission
 *
 * If the member "class" is set - it applies to an object
 * If the member "hook" is set - it uses that as the object hook to test the permission
 * The member "options" is reserved for future use, likely for common use cases for testing permissions
 *
 * @author kent
 * @see Class_Permission
 * @see Module_Permission
 * @copyright &copy; 2013 Market Acumen, Inc.
 */
class Permission extends Object {
	
	/**
	 * 
	 * @var boolean
	 */
	public static $debug = false;
	
	/**
	 * 
	 * @return string
	 */
	public function action() {
		$name = $this->member('name');
		return str::right($name, "::", $name);
	}
	
	/**
	 * 
	 * @return string
	 */
	public function object_class() {
		$name = $this->member('name');
		return str::left($name, "::", $name);
	}
	/**
	 * Hook for testing if another permission is allowed, then this permission is allowed.
	 *
	 * Useful for implementing "edit all" permissions.
	 *
	 * @param User $user
	 * @param array $perms
	 * @return boolean NULL
	 */
	private function _hook_if_all(User $user, $perms, $pass, $fail) {
		$perms = to_list($perms);
		foreach ($perms as $perm) {
			if (!$user->can($perm)) {
				return $fail;
			}
		}
		return $pass;
	}
	private function _hook_if_any(User $user, $perms, $pass, $fail) {
		$perms = to_list($perms);
		foreach ($perms as $perm) {
			if ($user->can($perm)) {
				return $pass;
			}
		}
		return $fail;
	}
	protected function hook_allowed_if_all(User $user, array $perms) {
		return $this->_hook_if_all($user, $perms, true, null);
	}
	protected function hook_denied_if_all(User $user, $perms) {
		return $this->_hook_if_all($user, $perms, false, null);
	}
	protected function hook_allowed_if_any(User $user, $perms) {
		return $this->_hook_if_any($user, $perms, true, null);
	}
	protected function hook_denied_if_any(User $user, $perms) {
		return $this->_hook_if_any($user, $perms, false, null);
	}
	
	/**
	 * Run alternate checking schemes for a permission: Mostly this handles
	 * checking if other permissions are present and, if so, granting/denying the permission
	 * requested.
	 *
	 * Add
	 * <code>
	 * "before_hook" => array("allowed_if_any" => array("perm1", "perm2"))
	 * </code>
	 * to your permission to enable this behaviour.
	 *
	 * The allowed phrases are ordered and the first true or false response found wins. Phrases are:
	 *
	 * "allowed_if_any" if any permission in the list is allowed, then true
	 * "allowed_if_all" if all permission in the list are allowed, then true
	 * "denied_if_any" if any permission in the list is allowed, then false
	 * "denied_if_all" if all permissions in the list is allowed, then false
	 *
	 * @param User $user
	 * @param array $options
	 * @return NULL Ambigous array, string, number>
	 */
	private function before_hook(User $user, array $options) {
		// Short-circuit tests
		$before_hook = avalue((array) $this->member('options'), 'before_hook');
		if (!is_array($before_hook)) {
			return null;
		}
		foreach ($before_hook as $hook => $argument) {
			$result = $this->call_hook_arguments($hook, array(
				$user,
				$argument
			), null);
			if (is_bool($result)) {
				return $result;
			}
		}
		return null;
	}
	
	/**
	 * Given a user, action, context, and options and a found permission in our permission cache -
	 * is access granted?
	 *
	 * For each object, calls
	 *
	 * <code>
	 * $object->call_hook($this->hook, User $user, Permission $permission, array $options, )
	 * </code>
	 *
	 * And expects a boolean response. If your call can not decide the permission, then return null
	 * and the default
	 * behavior will be granted.
	 *
	 * For all permissions, the default is the global "User::can" which defaults to false.
	 *
	 * @param User $user
	 * @param string $action
	 * @param Model $context
	 * @param array $options
	 * @param array $permission
	 * @return boolean unknown NULL
	 */
	public function check(User $user, $action, Model $context = null, array $options) {
		global $zesk;
		/* @var $zesk \zesk\Kernel */
		$class = $this->member('class');
		if ($class !== null) {
			if (!$context instanceof $class) {
				if (self::$debug) {
					$zesk->logger->debug("Permission::check: {context} not instanceof {class}", array(
						"context" => get_class($context),
						"class" => $class
					));
				}
				return false;
			}
			$hook = $this->membere("hook", "permission");
			$result = $this->before_hook($user, $options);
			if (is_bool($result)) {
				if (self::$debug) {
					$zesk->logger->debug("Permission::check: before hook for {action} returned {value}", array(
						"action" => $action,
						"value" => $result ? "true" : "false"
					));
				}
				return $result;
			}
			$result = $context->call_hook_arguments($hook, array(
				$user,
				$this,
				$options
			), null);
			if (is_bool($result)) {
				if (self::$debug) {
					$zesk->logger->debug("Permission::check: hook_array {hook} for {action} returned {value}", array(
						"action" => $action,
						"value" => $result ? "true" : "false",
						"hook" => $hook
					));
				}
				return $result;
			}
		}
		return null;
	}
	
	/**
	 * Register a permission in the system
	 *
	 * @param array $fields
	 * @return Permission
	 */
	public static function register_permission(array $fields) {
		static $cache = array();
		$name = $fields['name'];
		if (array_key_exists($name, $cache)) {
			return $cache[$name];
		}
		return $cache[$name] = Object::factory("Permission", $fields)->register();
	}
}
