<?php declare(strict_types=1);

/**
 *
 */
namespace zesk;

use Psr\Cache\CacheItemInterface;

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
	public static $hook_methods = [
		'zesk\\Application::permissions',
		'zesk\\Module::permissions',
		'zesk\\ORM::permissions',
	];

	/**
	 * Implement Module::classes
	 *
	 * @return array
	 */
	protected array $modelClasses = [
		Role::class,
		User_Role::class,
		Permission::class,
	];

	public function initialize(): void {
		$this->application->hooks->add(User::class . '::can', [
			$this,
			'user_can',
		]);
		$this->application->hooks->add(User::class . '::store', [
			$this,
			'user_store',
		]);

		try {
			// Add link if not added already
			Class_Base::link_many(User::class, 'roles', [
				'link_class' => User_Role::class,
				'far_key' => 'role',
				'foreign_key' => 'user',
				'class' => Role::class,
			]);
		} catch (Exception_Key $e) {
		}
		parent::initialize();
	}

	/**
	 * Hook when storing a user
	 *
	 * @param User $user
	 */
	public function user_store(User $user): void {
		$user->_roles = null;
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
	public function userCan(User $user, $action, Model $context = null, $options) {
		$application = $this->application;
		$this->prepare_user($user);
		$options = toArray($options);
		$warning = toBool($options['warning'] ?? $this->option('warning'));
		if ($user->is_root) {
			return true;
		}
		if (!is_string($action)) {
			throw new Exception_Semantics('{method} {type} {action} is not a string', [
				'method' => __METHOD__,
				'action' => $action,
				'type' => type($action),
			]);
		}
		$a = self::normalize_permission($action);
		$default_class = $context ? $this->model_permission_class($context) : null;
		[$class, $permission] = pair($a, '::', $default_class, $a);
		$lowclass = strtolower($class);

		$cache_key = "$lowclass::$permission";
		$user_cache = $user->object_cache('permission');
		$user_cached_permissions = $user_cache->isHit() ? $user_cache->get() : [];
		if (!$context && array_key_exists($cache_key, $user_cached_permissions)) {
			return $user_cached_permissions[$cache_key];
		}
		$perms = $this->permissions();

		$parent_classes = empty($class) ? [] : ArrayTools::changeValueCase($application->classes->hierarchy($class, 'Model'));
		$parent_classes[] = '*';
		foreach ($parent_classes as $parent_class) {
			$perm = apath($perms, [
				'class',
				$parent_class,
				$permission,
			]);
			if ($perm instanceof Permission) {
				$result = $perm->check($user, $parent_class . '::' . $permission, $context, $options);
				if (is_bool($result)) {
					if ($warning && $result === false) {
						$application->logger->warning('{user} denied {permission} (parent of {class})', [
							'user' => $user->login(),
							'permission' => $parent_class . '::' . $permission,
							'class' => $class,
							'calling_function' => calling_function(2),
						]);
					}
					return $result;
				}
			}
		}

		$rids = $this->user_roles($user);
		foreach ($rids as $rid) {
			$result = apath($perms, [
				'role',
				$rid,
				$lowclass,
				$permission,
			]);
			if (is_bool($result)) {
				if ($result === false) {
					$application->logger->info('{user} denied {permission} (role)', [
						'user' => $user->login(),
						'permission' => $class . '::' . $permission,
					]);
				}
				$user_cached_permissions[$cache_key] = $result;
				$application->cache->saveDeferred($user_cache->set($user_cached_permissions));
				return $result;
			}
		}
		$result = boolval($user->option('can'));
		if ($warning && $result === false) {
			$application->logger->warning('{user} denied {permission} (not granted) called from {calling_function} (Roles: {roles})', [
				'user' => $user->login(),
				'permission' => $class . '::' . $permission,
				'calling_function' => calling_function(6),
				'roles' => $user->_roles,
			]);
		}
		$user_cached_permissions[$cache_key] = $result;
		$application->cache->saveDeferred($user_cache->set($user_cached_permissions));
		return $result;
	}

	/**
	 *
	 * @param User $user
	 */
	private function prepare_user(User $user): void {
		if (is_array($user->_roles)) {
			return;
		}
		if ($user->isNew()) {
			$roles = $this->application->ormRegistry(Role::class)
				->querySelect()
				->addWhere('is_default', true)
				->ormIterator()
				->toArray('id');
		} else {
			// Load user role settings into user before checking
			$roles = $user->memberQuery('roles')->ormIterator()->toArray('id');
		}
		$role_ids = [];
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
		$user->_roles = $this->application->ormRegistry('User_Role')
			->querySelect()
			->addWhat('Role', 'Role')
			->addWhere('User', $user)
			->toArray(null, 'Role', []);
		return $user->_roles;
	}

	/**
	 * Refresh the permissions cache as often as needed
	 */
	public function hook_cron_minute(): void {
		$application = $this->application;
		$cache = $this->_cache();
		// Is there a cache? If not, don't bother - may be disabled, etc.
		if (!$cache->isHit()) {
			$application->logger->debug('No cache');
			return;
		}
		// We have a cache. Does it match what it should?
		$cached = $this->_permissions_cached();
		$computed = $this->_permissions_computed();
		if ($cached !== $computed) {
			// Nope. Update it.
			$this->_permissions_cached($computed);
			$application->logger->notice('Refreshed permissions cache');
		} else {
			$application->logger->debug('Cache matches computed');
		}
	}

	/**
	 *
	 * @return CacheItemInterface
	 */
	private function _cache() {
		return $this->application->cache->getItem(__CLASS__);
	}

	/**
	 * Optionally save cached item
	 * @param CacheItemInterface $item
	 */
	private function _cache_changed(CacheItemInterface $item): void {
		if ($this->optionBool('disable_cache')) {
			return;
		}
		$this->application->cache->saveDeferred($item);
	}

	/**
	 *
	 * @return array|null
	 */
	private function _permissions_cached(array $set = null) {
		$cache = $this->_cache();
		if (is_array($set)) {
			$cache->set($set);
			$this->_cache_changed($cache);
			return $this;
		}
		if ($this->optionBool('disable_cache')) {
			return null;
		}
		if (!$cache->isHit()) {
			return null;
		}
		$perms = $cache->get();
		return is_array($perms) ? $perms : null;
	}

	/**
	 * Retrieve
	 *
	 * @return array
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
			$result = [];
			foreach ($mixed as $index => $permission) {
				$result[self::normalize_permission($index)] = self::normalize_permission($permission);
			}
			return $result;
		}
		return strtr(strtolower($mixed), [
			' ' => '_',
			'.' => '_',
			'/' => '_',
			'-' => '_',
		]);
	}

	/**
	 *
	 * @return array|string
	 */
	public function hook_methods() {
		return $this->application->hooks->findAll(self::$hook_methods);
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
		$result = [];
		$result['class'] = $application->hooks->allCallArguments(self::$hook_methods, [
			$application,
		], [], null, [
			$this,
			'_combine_permissions',
		]);
		$roles = $application->ormRegistry('Role')
			->querySelect('X')
			->addWhatIterable([
				'id' => 'X.id',
				'code' => 'X.code',
			])
			->order_by('X.id')
			->toArray('id', 'code', []);
		$options = [
			'overwrite' => true,
			'trim' => true,
			'lower' => true,
			'variables' => [],
		];
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
		$paths = $this->optionIterable('role_paths', [
			'./etc/role',
		]);
		if (count($paths) === 0) {
			return [];
		}
		$files = [];
		foreach ($paths as $path) {
			foreach ([
				'conf',
				'json',
			] as $ext) {
				$basename = $code . ".role.$ext";
				$files[] = $application->paths->expand(path($path, $basename));
			}
		}
		$config = [];
		$loader = new Configuration_Loader($files, new Adapter_Settings_Array($config));
		$loader->load();
		$vars = $loader->variables();
		$processed = $vars[Configuration_Loader::PROCESSED];
		$missing = $vars[Configuration_Loader::MISSING];
		$config = self::normalize_permission($config);
		$application->logger->debug('Loading {processed} files resulted in {n_config} permissions (skipped missing {missing})', [
			'processed' => $processed,
			'missing' => $missing,
			'n_config' => count($config),
		]);
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
		$default_class = StringTools::left($method, '::');
		$class_perms = [];
		/* @var $perm_class \zesk\Class_Permission */
		$perm_class = $this->application->class_ormRegistry(Permission::class);
		$perm_columns = $perm_class->column_types;
		foreach ($result as $action => $permission_options) {
			if (is_string($permission_options)) {
				$permission_options = [
					'title' => $permission_options,
				];
			} elseif (!is_array($permission_options)) {
				throw new Exception_Semantics('Hookable::permissions() should return array of string => string, or string => array');
			}
			// Move extra fields into options
			$permission_options['options'] ??= [];
			foreach ($permission_options as $perm_option => $perm_value) {
				if (!array_key_exists($perm_option, $perm_columns)) {
					$permission_options['options'][$perm_option] = $perm_value;
					unset($permission_options[$perm_option]);
				}
			}
			$action = User::clean_permission($action);
			[$class, $action] = pair($action, '::', $default_class, $action);
			$permission_options['name'] = $class . '::' . $action;
			$class_perms[$action] = Permission::register_permission($this->application, $permission_options);
		}
		$state[strtolower($class)] = $class_perms; // + array("*class" => $class);
		return $state;
	}

	/**
	 */
	protected function hook_cacheClear(): void {
		$this->application->ormRegistry(Permission::class)
			->queryDelete()
			->truncate(true)
			->execute();
	}

	/**
	 *
	 * @param Model $context
	 * @return string
	 */
	private function model_permission_class(Model $context) {
		$default = $context::class;
		if ($context instanceof ORMBase) {
			return $context->class_orm()->option('permission_class', $default);
		}
		return $default;
	}
}
