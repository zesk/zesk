<?php

/**
 *
 */
namespace zesk;

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
	private $_permissions = null;
	
	/**
	 * Role Permissions cache
	 *
	 * @var array of [rid][action] => boolean
	 */
	private $_role_permissions = null;
	
	/**
	 *
	 * @var array
	 */
	static $hook_methods = array(
		"zesk\\Application::permissions",
		"zesk\\Module::permissions",
		"zesk\\Object::permissions"
	);
	
	/**
	 * Implement Module::classes
	 *
	 * @return array
	 */
	protected $model_classes = array(
		"zesk\\Role",
		"zesk\\User_Role",
		"zesk\\Permission"
	);
	public function initialize() {
		$this->application->hooks->add(User::class . "::can", array(
			$this,
			"user_can"
		));
		Class_ORM::link_many(User::class, 'roles', array(
			'link_class' => 'zesk\\User_Role',
			'far_key' => 'role',
			'foreign_key' => 'user',
			'class' => 'zesk\\Role'
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
	public function user_can(User $user, $action, Model $context = null, $options) {
		$application = $this->application;
		$this->prepare_user($user);
		
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
		$default_class = $context ? $this->model_permission_class($context) : null;
		list($class, $permission) = pair($a, "::", $default_class, $a);
		$lowclass = strtolower($class);
		
		$cache_key = "$lowclass::$permission";
		$user_cache = $user->object_cache("permissions");
		if (!$context && $user_cache->has($cache_key)) {
			return $user_cache->$cache_key;
		}
		$perms = $this->permissions();
		
		$parent_classes = empty($class) ? array() : arr::change_value_case($application->classes->hierarchy($class, "Model"));
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
						$application->logger->info("{user} denied {permission} (parent of {class})", array(
							"user" => $user->login(),
							"permission" => $parent_class . "::" . $permission,
							"class" => $class,
							"calling_function" => calling_function(2)
						));
					}
					return $result;
				}
			}
		}
		
		$rids = $this->user_roles($user);
		foreach ($rids as $rid) {
			$result = apath($perms, array(
				'role',
				$rid,
				$lowclass,
				$permission
			));
			if (is_bool($result)) {
				if ($result === false) {
					$application->logger->info("{user} denied {permission} (role)", array(
						"user" => $user->login(),
						"permission" => $class . "::" . $permission
					));
				}
				$user_cache->$cache_key = $result;
				return $result;
			}
		}
		$result = boolval($application->configuration->path_get_first([
			'zesk\\User::can',
			'User::can'
		]));
		if ($result === false) {
			$application->logger->info("{user} denied {permission} (not granted) called from {calling_function} (Roles: {roles})", array(
				"user" => $user->login(),
				"permission" => $class . "::" . $permission,
				"calling_function" => calling_function(5),
				"roles" => $user->_roles
			));
		}
		$user_cache->$cache_key = $result;
		return $result;
	}
	
	/**
	 *
	 * @param User $user
	 */
	private function prepare_user(User $user) {
		if (is_array($user->_roles)) {
			return;
		}
		if ($user->is_new()) {
			$roles = $this->application->orm_registry('Role')
				->query_select()
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
	private function user_roles(User $user) {
		if (is_array($user->_roles)) {
			return $user->_roles;
		}
		$user->_roles = $this->application->orm_registry("User_Role")
			->query_select()
			->what("Role", "Role")
			->where("User", $user)
			->to_array(null, "Role", array());
		return $user->_roles;
	}
	
	/**
	 * Refresh the permissions cache as often as needed
	 */
	public function hook_cron_minute() {
		$application = $this->application;
		$cache = $this->_cache();
		// Is there a cache? If not, don't bother - may be disabled, etc.
		if (!$cache->exists()) {
			$application->logger->debug("No cache");
			return;
		}
		// We have a cache. Does it match what it should?
		$cached = $this->_permissions_cached();
		$computed = $this->_permissions_computed();
		if ($cached !== $computed) {
			// Nope. Update it.
			$cache->__set(__CLASS__, $computed);
			$application->logger->notice("Refreshed permissions cache");
		} else {
			$application->logger->debug("Cache matches computed");
		}
	}
	
	/**
	 *
	 * @return Cache
	 */
	private function _cache() {
		$cache = Cache::register(__CLASS__);
		if ($this->application->configuration->path_get(__CLASS__ . '::disable_cache')) {
			return $cache->erase();
		}
		return $cache;
	}
	
	/**
	 *
	 * @return array
	 */
	private function _permissions_cached(array $set = null) {
		$cache = $this->_cache();
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
	public function permissions() {
		if ($this->_permissions !== null) {
			return $this->_permissions;
		}
		$this->_permissions = $this->_permissions_cached();
		if (is_array($this->_permissions)) {
			return $this->_permissions;
		}
		$this->_permissions = $this->_permissions_computed();
		$this->_permissions_cached($this->_permissions);
		return $this->_permissions;
	}
	
	/**
	 *
	 * @param mixed $mixed
	 * @return mixed
	 */
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
	 * @return array|string
	 */
	public function hook_methods() {
		return $this->application->hooks->find_all(self::$hook_methods);
	}
	/**
	 *
	 * @return array
	 */
	private function _permissions_computed() {
		$application = $this->application;
		$lock = Lock::instance($this->application, __CLASS__);
		try {
			$lock->expect(10);
		} catch (Exception_Timeout $e) {
			$lock->crack();
			$lock->expect(10);
		}
		$result = array();
		$result['class'] = $application->hooks->all_call_arguments(self::$hook_methods, array(
			$application
		), array(), null, array(
			$this,
			'_combine_permissions'
		));
		$roles = $application->orm_registry('Role')
			->query_select('X')
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
		foreach ($roles as $rid => $code) {
			$result['role'][$rid] = $config = $this->_role_permissions($code);
		}
		$lock->release();
		return $result;
	}
	
	/**
	 *
	 * @param string $code
	 * @return unknown|string|unknown[]|string[]
	 */
	private function _role_permissions($code) {
		$application = $this->application;
		$paths = $this->option_list('role_paths', $application->path('etc/role'));
		$filename = $code . ".role.conf";
		$config = array();
		$loader = new Configuration_Loader($paths, array(
			$filename
		), new Adapter_Settings_Array($config));
		$loader->load();
		$config = self::normalize_permission($config);
		$application->logger->debug("Loading {filename} resulted in {n_config} permissions", array(
			"filename" => $filename,
			"n_config" => count($config)
		));
		return $config;
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
	public function _combine_permissions($method, array $state, $result) {
		if (!is_array($result)) {
			return $state;
		}
		$method = User::clean_permission($method);
		// Use calling function as a hint when class not supplied (deprecated in 0.8.2)
		$default_class = str::left($method, "::");
		$class_perms = array();
		/* @var $perm_class \zesk\Class_Permission */
		$perm_class = $this->application->class_object("zesk\\Permission");
		$perm_columns = $perm_class->column_types;
		foreach ($result as $action => $permission_options) {
			if (is_string($permission_options)) {
				$permission_options = array(
					'title' => $permission_options
				);
			} else if (!is_array($permission_options)) {
				throw new Exception_Semantics("Hookable::permissions() should return array of string => string, or string => array");
			}
			// Move extra fields into options
			$permission_options['options'] = avalue($permission_options, 'options', array());
			foreach ($permission_options as $perm_option => $perm_value) {
				if (!array_key_exists($perm_option, $perm_columns)) {
					$permission_options['options'][$perm_option] = $perm_value;
					unset($permission_options[$perm_option]);
				}
			}
			$action = User::clean_permission($action);
			list($class, $action) = pair($action, "::", $default_class, $action);
			$permission_options['name'] = $class . "::" . $action;
			$class_perms[$action] = Permission::register_permission($this->application, $permission_options);
		}
		$state[strtolower($class)] = $class_perms; // + array("*class" => $class);
		return $state;
	}
	
	/**
	 */
	protected function hook_cache_clear() {
		$this->application->query_delete('zesk\\Permission')->truncate(true)->execute();
	}
	
	/**
	 *
	 * @param Model $context
	 * @return string
	 */
	private function model_permission_class(Model $context) {
		$default = get_class($context);
		if ($context instanceof Object) {
			return $context->class_object()->option("permission_class", $default);
		}
		return $default;
	}
}
