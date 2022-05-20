<?php declare(strict_types=1);

/**
 * @package zesk
 * @subpackage objects
 * @author kent
 * @copyright Copyright &copy; 2009, Market Acumen, Inc.
 * Created on Fri Apr 02 21:00:12 EDT 2010 21:00:12
 */
namespace zesk;

/**
 * Represents a means of authentication to an application.
 *
 * @see Class_User
 *
 * @author kent
 */
class User extends ORM {
	/**
	 * Boolean value to enable debugging of permissions
	 *
	 * @var string
	 */
	public const option_debug_permission = 'debug_permission';

	/**
	 *
	 * @var string
	 */
	public static $debug_permission = false;

	/**
	 * Syntactic sygar; types this member.
	 *
	 * @var Class_User
	 */
	protected Class_ORM $class;

	/**
	 *
	 * @param Kernel $application
	 */
	public static function hooks(Application $application): void {
		$application->configuration->path(__CLASS__);
		$application->hooks->add(Hooks::HOOK_CONFIGURED, __CLASS__ . '::configured');
	}

	/**
	 *
	 * @param Application $application
	 */
	public static function configured(Application $application): void {
		self::$debug_permission = to_bool($application->configuration->path_get_first([
			[
				__CLASS__,
				self::option_debug_permission,
			],
			[
				'User',
				self::option_debug_permission,
			],
		]));
	}

	/**
	 * Session user ID
	 *
	 * @return integer
	 */
	public function session_user_id(Request $request) {
		try {
			$session = $this->application->session($request);
		} catch (Exception $e) {
			return null;
		}
		if (!$session instanceof Interface_Session) {
			error_log(__METHOD__ . ' $this->application->session() returned non-session? ' . type($session) . ' ' . strval($session));
			return null;
		}
		return $session->user_id();
	}

	/**
	 * Retrieve the column used for logging in
	 *
	 * @return string
	 */
	public function column_login() {
		return $this->class->column_login;
	}

	/**
	 * Retrieve the password column name
	 *
	 * @return string
	 */
	public function column_password() {
		return $this->class->column_password;
	}

	/**
	 * Retrieve the email column name
	 *
	 * @return string
	 */
	public function column_email() {
		return $this->class->column_email;
	}

	/**
	 * Get or set the login column value
	 *
	 * @param string $set
	 *
	 * @return User
	 */
	public function login($set = null) {
		$column = $this->column_login();
		if ($set !== null) {
			return $this->set_member($column, $set);
		}
		return $this->member($column);
	}

	/**
	 * Get or set the email column value
	 *
	 * @param string $set
	 *
	 * @return User
	 */
	public function email($set = null) {
		$column = $this->column_email();
		if (!$column) {
			throw new Exception_Semantics('No email column in {class}', [
				'class' => get_class($this),
			]);
		}
		if ($set !== null) {
			return $this->set_member($column, $set);
		}
		return $this->member($column);
	}

	/**
	 * Override in subclasses to perform a final check before loading a user from the Session
	 *
	 * @return boolean
	 */
	public function check_user() {
		return true;
	}

	/**
	 * Get/set the password field
	 *
	 * @param string $set
	 * @param boolean $plaintext When set is non-null, whether value is plain text or not.
	 * @return string|User
	 */
	public function password($set = null, $plaintext = true) {
		$column = $this->column_password();
		if ($set !== null) {
			return $this->set_member($column, $plaintext ? $this->_generate_hash($set) : $set);
		}
		return $this->member($column);
	}

	/**
	 *
	 * @return string
	 */
	public function password_method() {
		return strtolower(trim($this->member($this->class->column_hash_method, $this->class->default_hash_method)));
	}

	/**
	 *
	 * @param string $plaintext
	 * @return string
	 */
	private function _generate_hash($string) {
		return $this->generate_hash($string, $this->class->column_password_is_binary);
	}

	/**
	 * Using the
	 * @param string $string
	 * @param boolean $raw_output
	 * @return string
	 */
	public function generate_hash(string $string, bool $raw_output = true): string {
		$algo = $this->password_method();
		if (in_array($algo, $this->class->allowed_hash_methods)) {
			return hash($algo, $string, $raw_output);
		}
		$this->application->error('Invalid hash algorithm {algo} in User {id}, using default', [
			'algo' => $algo,
			'id' => $this->id(),
			'default' => $this->class->default_hash_method,
		]);
	}

	/**
	 * Check a username and password.
	 * Will not authenticate user until ->authenticated($request, $response) is called.
	 *
	 * @param string $username
	 * @param string $password
	 * @param string $use_hash
	 * @param string $case_sensitive
	 * @return boolean
	 */
	public function authenticate($username, $password, $use_hash = true, $case_sensitive = true) {
		// TODO: This will break if everyone else uses the class property. Update ->column_login, or don't use option
		$column_login = $this->option('column_login', $this->column_login());
		$this->set_member($column_login, $username);
		if (!$this->fetch_by_key($username, $column_login)) {
			return false;
		}
		$this_password = $this->password();
		if ($use_hash) {
			$auth_test = strcasecmp($this->generate_hash($password, false), $this_password) === 0;
		} else {
			$auth_test = $case_sensitive ? ($password === $this_password) : strcasecmp($password, $this_password) === 0;
		}
		if ($auth_test) {
			return true;
		}
		return false;
	}

	/**
	 * Get/set authentication status
	 *
	 * @param Request $request Required. Request to match this user against.
	 * @param Response $response Optional. If supplied, authenticates this user in the associated response (generally, by setting a cookie.)
	 * @return NULL|\zesk\User
	 */
	public function authenticated(Request $request, Response $response = null) {
		$matches = ($this->session_user_id($request) === $this->id());
		if ($response === null) {
			if ($this->is_new()) {
				return null;
			}
			if ($matches) {
				return $this;
			}
			return null;
		}
		if ($matches) {
			return $this;
		}
		$session = $this->application->session($request);
		$session->authenticate($this->id(), $request->ip());
		$this->call_hook('authenticated', $request, $response, $session);
		$this->application->call_hook('user_authenticated', $this, $request, $response, $session);
		$this->application->modules->all_hook('user_authenticated', $this, $request, $response, $session);
		return $this;
	}

	/**
	 * Similar to $user->can(...) but instead throws an Exception_Permission on failure
	 *
	 * Checks that user can perform action optionally on object
	 *
	 * @param string $action
	 * @param Model $context
	 * @param array $options
	 * @throws Exception_Permission
	 * @return User
	 */
	public function must($action, Model $context = null, array $options = []) {
		if (!$this->can($action, $context, $options)) {
			throw new Exception_Permission($this, $action, $context, $options);
		}
		return $this;
	}

	final public static function clean_permission($string) {
		return strtolower(strtr($string, [
			' ' => '_',
			'.' => '_',
			'-' => '_',
			'__' => '::',
		]));
	}

	/**
	 * The core of the permissions system
	 *
	 * <code>
	 * $yes = $user->can("write checks"); // Simple invokation
	 * // Invoke with an object - the following two lines are identical
	 * $yes = $yes && $user->can("edit", $account); // ORM invokation
	 * // Invoke with additional arguments
	 * $yes = $yes && $user->can("transfer", $checking, array("target" => $savings)));
	 * </code>
	 *
	 * To test if ANY permission is allowed, use the | as separator for all permissions passed, like
	 * so:
	 *
	 * <code>
	 * if ($user->can("edit|view|delete", $other_user)) {
	 * echo $menus; // User can edit, view, or delete
	 * }
	 * </code>
	 *
	 * Using the default list separator ";" means the meaning is ALL ACTIONS must be permitted to
	 * continue.
	 *
	 * @param mixed $action
	 *        	Can be an array of actions, all of which must pass, or a string of actions whose
	 *        	separator determines if the meaning is "AND" or "OR" for each permission.
	 * @param mixed $context
	 *        	ORM on which to act
	 * @param mixed $object
	 *        	Extra optional settings, permission-specific
	 * @return boolean
	 */
	public function can($actions, $context = null, array $options = []) {
		// Sanitize input
		if ($actions instanceof Model) {
			[$actions, $context] = [
				$context,
				$actions,
			];
		}
		if ($context && !$context instanceof Model) {
			$this->application->logger->warning('Non model passed as $context to {method} ({type})', [
				'method' => __METHOD__,
				'type' => type($context),
			]);
			$context = null;
		}

		$result = false; // By default, don't allow anything
		// Allow multiple actions
		$is_or = is_string($actions) && strpos($actions, '|');
		$actions = to_list($actions, [], $is_or ? '|' : ';');
		$default_result = $this->option('can', false);
		foreach ($actions as $action) {
			$action = self::clean_permission($action);
			$skiplog = false;

			try {
				$result = $this->call_hook_arguments('can', [
					$action,
					$context,
					$options,
				], $default_result);
			} catch (\Exception $e) {
				$result = false;
				$skiplog = true;
				$this->application->logger->error("User::can({action},{context}) = {result} (Roles {roles}): Exception {exception_class} {message}\n{backtrace}", [
					'action' => $action,
					'context' => $context,
					'result' => $result,
					'roles' => $this->_roles,
				] + Exception::exception_variables($e));
			}
			if (self::$debug_permission && !$skiplog) {
				$this->application->logger->debug('User::can({action},{context}) = {result} (Roles {roles}) ({extra})', [
					'action' => $action,
					'context' => $context,
					'result' => $result,
					'roles' => $this->_roles,
				]);
			}
			if ($is_or) {
				// One must be allowed to continue
				if ($result === true) {
					break;
				}
			} else {
				// All must be allowed to continue
				if ($result === false) {
					break;
				}
			}
		}
		if ($result) {
			$default_hook = 'permission_pass';
		} else {
			$default_hook = 'permission_fail';
		}
		$hook_option_name = $default_hook . '_hook';
		if (($hook = avalue($options, $hook_option_name, $this->option($hook_option_name))) !== null) {
			$this->call_hook_arguments(is_string($hook) ? $hook : $default_hook, [
				$actions,
				$context,
				$options,
			]);
		}

		return $result;
	}

	/**
	 * Check if a user can edit an object
	 *
	 * @param ORM $object
	 * @return boolean
	 */
	public function can_edit($object) {
		return $this->can('edit', $object);
	}

	/**
	 * Check if a user can view an object
	 *
	 * @param ORM $object
	 * @return boolean
	 */
	public function can_view($object) {
		return $this->can('view', $object);
	}

	/**
	 *
	 * {@inheritdoc}
	 *
	 * @see \zesk\ORM::display_name()
	 */
	public function display_name() {
		return $this->member($this->column_login());
	}

	/**
	 * Check if a user can delete an object
	 *
	 * @param ORM $object
	 * @return boolean
	 */
	public function can_delete($object) {
		return $this->can('delete', $object);
	}

	/**
	 * Implement ORM::permissions
	 *
	 * @return array
	 */
	public static function permissions(Application $application) {
		return parent::default_permissions($application, __CLASS__) + [
			__CLASS__ . '::become' => [
				'title' => __('Become another user'),
				'class' => 'User',
			],
		];
	}

	/**
	 * Takes an array which can be formatted with $application->theme("actions") and filters based on permissions.
	 * Use the key "permission" in value to specify a permission to check. It can be a string, or an
	 * array of ($action, $context, $options) to check.
	 *
	 * @param array $actions
	 * @param Model $context
	 *        	Default context to pass to "can" function
	 * @param array $options
	 *        	Default options to pass to "can" function
	 * @return array
	 */
	public function filter_actions(array $actions, Model $context = null, array $options = []) {
		$actions_passed = [];
		foreach ($actions as $href => $attributes) {
			if (is_array($attributes) && array_key_exists('permission', $attributes)) {
				$permission = $attributes['permission'];
				unset($attributes['permission']);
				if (is_array($permission)) {
					[$a_permission, $a_context, $a_options] = $permission + ([
						null,
						null,
						[],
					]);
					if ($this->can($a_permission, $a_context, $a_options)) {
						$actions_passed[$href] = $attributes;
					}
				} elseif ($this->can($permission, $context, $options)) {
					$actions_passed[$href] = $attributes;
				}
			} else {
				$actions_passed[$href] = $attributes;
			}
		}
		return $actions_passed;
	}
}
