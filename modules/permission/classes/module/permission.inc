<?php
/**
 * 
 */
use zesk\Application;
use zesk\Cache;
use zesk\Object;
use zesk\arr;
use zesk\File;
use zesk\Class_Object;
use zesk\Model;
use zesk\Exception_Semantics;

/**
 * Module to handle per-object, role-based permissions
 *
 * @author kent
 */
class Module_Permission extends Module {
	
	/**
	 * Permissions cache
	 *
	 * @var array
	 */
	private static $_permissions = null;
	
	/**
	 * Role Permissions cache
	 *
	 * @var array of [rid][action] => boolean
	 */
	private static $_role_permissions = null;
	
	/**
	 * Implement Module::classes
	 *
	 * @return array
	 */
	protected $classes = array(
		"Role",
		"User_Role",
		"Permission"
	);
	public static function hooks(zesk\Kernel $zesk) {
		$zesk->hooks->add("User::can", array(
			__CLASS__,
			"user_can"
		));
	}
	public function initialize() {
		Class_Object::link_many("User", 'roles', array(
			'table' => 'User_Role',
			'far_key' => 'role',
			'foreign_key' => 'user',
			'class' => 'Role'
		));
		parent::initialize();
	}
	/**
	 * Implements Module::user_can
	 *
	 * @param User $user
	 * @param string $action
	 * @param Model $context
	 * @param unknown $options
	 * @return boolean Ambigous NULL, unknown>|Ambigous <The, mixed, boolean, multitype:Ambigous
	 *         <The, mixed, boolean> >
	 */
	public static function user_can(User $user, $action, Model $context = null, $options) {
		global $zesk;
		/* @var $zesk zesk\Kernel */
		
		self::prepare_user($user);
		
		if ($user->is_root) {
			return true;
		}
		if (!is_string($action)) {
			throw new Exception_Semantics("{method} {type} {action} is not a string", array(
				"method" => __METHOD__,
				"action" => $action,
				"type" => type($action)
			));
		}
		$a = self::normalize_permission($action);
		list($class, $permission) = pair($a, "::", strtolower(get_class($context)), $a);
		
		$cache_key = "$class::$permission";
		$user_cache = $user->object_cache("permissions");
		if (!$context && $user_cache->has($cache_key)) {
			return $user_cache->$cache_key;
		}
		$perms = self::_permissions($user->application);
		
		$parent_classes = empty($class) ? array() : arr::change_value_case($zesk->classes->hierarchy($class, "Model"));
		$parent_classes[] = "*";
		foreach ($parent_classes as $parent_class) {
			$perm = apath($perms, array(
				'class',
				$parent_class,
				$permission
			));
			if ($perm instanceof Permission) {
				$result = $perm->check($user, $parent_class . "::" . $permission, $context, $options);
				if (is_bool($result)) {
					if ($result === false) {
						$zesk->logger->info("{user} denied {permission} (parent of {class})", array(
							"user" => $user->login(),
							"permission" => $parent_class . "::" . $permission,
							"class" => $class
						));
					}
					return $result;
				}
			}
		}
		
		$rids = self::user_roles($user);
		foreach ($rids as $rid) {
			$result = apath($perms, array(
				'role',
				$rid,
				"$class::$permission"
			));
			if (is_bool($result)) {
				if ($result === false) {
					$zesk->logger->info("{user} denied {permission} (role)", array(
						"user" => $user->login(),
						"permission" => $class . "::" . $permission
					));
				}
				$user_cache->$cache_key = $result;
				return $result;
			}
		}
		$result = boolval($zesk->configuration->User->can);
		if ($result === false) {
			$zesk->logger->info("{user} denied {permission} (not granted)", array(
				"user" => $user->login(),
				"permission" => $class . "::" . $permission
			));
		}
		$user_cache->$cache_key = $result;
		return $result;
	}
	
	/**
	 * 
	 * @param User $user
	 */
	private static function prepare_user(User $user) {
		if (is_array($user->_roles)) {
			return;
		}
		if ($user->is_new()) {
			$roles = $user->application->query_select('Role')
				->where('is_default', true)
				->object_iterator()
				->to_array('id');
		} else {
			// Load user role settings into user before checking
			$roles = $user->member_query("roles")->object_iterator()->to_array("id");
		}
		$role_ids = array();
		/* @var $role Role */
		foreach ($roles as $id => $role) {
			if ($role->is_root()) {
				$user->is_root = true;
			}
			if ($role->is_default()) {
				$user->is_default = true;
			}
			$role_ids[] = $id;
		}
		$user->_roles = $role_ids;
	}
	/**
	 * Load user roles into User object for caching
	 *
	 * @param User $user
	 */
	private static function user_roles(User $user) {
		if (is_array($user->_roles)) {
			return $user->_roles;
		}
		$user->_roles = $user->application->query_select("User_Role")
			->what("Role", "Role")
			->where("User", $user)
			->to_array(null, "Role", array());
		return $user->_roles;
	}
	
	/**
	 * Refresh the permissions cache as often as needed
	 */
	public static function cron_minute(Application $application) {
		$cache = self::_cache();
		// Is there a cache? If not, don't bother - may be disabled, etc.
		if (!$cache->exists()) {
			return;
		}
		// We have a cache. Does it match what it should?
		$cached = self::_permissions_cached();
		$computed = self::_permissions_computed($application);
		if ($cached !== $computed) {
			// Nope. Update it.
			$cache->__set(__CLASS__, $computed);
			zesk()->logger->notice("Refreshed permissions cache");
		}
	}
	
	/**
	 *
	 * @return Cache
	 */
	private static function _cache() {
		$cache = Cache::register(__CLASS__);
		if (zesk()->configuration->path_get(__CLASS__ . '::disable_cache')) {
			return $cache->erase();
		}
		return $cache;
	}
	
	/**
	 *
	 * @return array
	 */
	private static function _permissions_cached(array $set = null) {
		$cache = self::_cache();
		if (!$cache) {
			return null;
		}
		if ($set) {
			$cache->__set(__CLASS__, $set);
			return;
		}
		$perms = $cache->__get(__CLASS__);
		return is_array($perms) ? $perms : null;
	}
	
	/**
	 * Retrieve
	 *
	 * @return multitype:
	 */
	public static function _permissions(Application $application) {
		if (self::$_permissions !== null) {
			return self::$_permissions;
		}
		self::$_permissions = self::_permissions_cached();
		if (is_array(self::$_permissions)) {
			return self::$_permissions;
		}
		self::$_permissions = self::_permissions_computed($application);
		self::_permissions_cached(self::$_permissions);
		return self::$_permissions;
	}
	public static function normalize_permission($mixed) {
		if (is_bool($mixed) || $mixed === null || is_numeric($mixed)) {
			return $mixed;
		}
		if (is_array($mixed)) {
			$result = array();
			foreach ($mixed as $index => $permission) {
				$result[self::normalize_permission($index)] = self::normalize_permission($permission);
			}
			return $result;
		}
		return strtr(strtolower($mixed), array(
			" " => "_",
			"." => "_",
			"/" => "_",
			"-" => "_"
		));
	}
	
	/**
	 *
	 * @return array
	 */
	public static function _permissions_computed(zesk\Application $application) {
		global $zesk;
		
		/* @var $zesk zesk\Kernel */
		$lock = Lock::require_lock(__METHOD__, 10);
		$result = array();
		$result['class'] = $zesk->hooks->all_call_arguments(array(
			"zesk\Application::permissions",
			"Module::permissions",
			"zesk\Object::permissions"
		), array(), array(), null, array(
			__CLASS__,
			'_combine_permissions'
		));
		$roles = $application->query_select('Role', 'X')
			->what(array(
			"id" => "X.id",
			"code" => "X.code"
		))
			->order_by("X.id")
			->to_array("id", "code", array());
		$options = array(
			'overwrite' => true,
			'trim' => true,
			'lower' => true,
			'variables' => array()
		);
		$paths = to_list($application->configuration->path_get('Module_Permission::role_paths', $application->application_root('etc/role')));
		foreach ($roles as $rid => $code) {
			$code = strtolower(File::clean_path($code));
			$filename = $code . ".role.conf";
			$result['role'][$rid] = $config = self::normalize_permission(conf::load_inherit($filename, $paths, array()));
			$zesk->logger->debug("Loading {filename} resulted in {n_config} permissions", array(
				"filename" => $filename,
				"n_config" => count($config)
			));
		}
		$lock->release();
		return $result;
	}
	
	/**
	 * Hook call to add up permissions and create the permissions structure from the hooks in the
	 * system.
	 *
	 * @param string $method
	 *        	Hook called method
	 * @param array $state
	 *        	Current state of our build
	 * @param mixed $result
	 *        	Return value of above hook
	 * @throws Exception_Semantics
	 * @return array
	 */
	public static function _combine_permissions($method, array $state, $result) {
		if (!is_array($result)) {
			return $state;
		}
		$method = User::clean_permission($method);
		$class = str::left($method, "::");
		$class_perms = array();
		/* @var $perm_class Class_Permission */
		$perm_class = Object::cache_class("Permission", "class");
		$perm_columns = $perm_class->column_types;
		foreach ($result as $k => $v) {
			if (is_string($v)) {
				$v = array(
					'title' => $v
				);
			} else if (!is_array($v)) {
				throw new Exception_Semantics("Hookable::permissions() should return array of string => string, or string => array");
			}
			// Move extra fields into options
			$v['options'] = avalue($v, 'options', array());
			foreach ($v as $vk => $vv) {
				if (!array_key_exists($vk, $perm_columns)) {
					$v['options'][$vk] = $vv;
					unset($v[$vk]);
				}
			}
			$k = User::clean_permission($k);
			$v['name'] = $class . "::" . $k;
			$class_perms[$k] = Permission::register_permission($v);
		}
		$state[strtolower($class)] = $class_perms; // + array("*class" => $class);
		return $state;
	}
	protected function hook_cache_clear() {
		$this->application->query_delete("Permission")->truncate(true)->execute();
	}
}
