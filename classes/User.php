<?php
/**
 * $URL: https://code.marketacumen.com/zesk/trunk/classes/User.php $
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
class User extends Object {
	/**
	 * Boolean value to enable debugging of permissions
	 *
	 * @var string
	 */
	const option_debug_permission = "debug_permission";
	
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
	protected $class = null;
	
	/**
	 * 
	 * @param Kernel $zesk
	 */
	public static function hooks(Kernel $zesk) {
		$zesk->configuration->path(__CLASS__);
		$zesk->hooks->add("configured", __CLASS__ . "::configured");
	}
	
	/**
	 * 
	 * @param Application $application
	 */
	public static function configured(Application $application) {
		self::$debug_permission = to_bool($application->configuration->path_get_first(array(
			array(
				__CLASS__,
				self::option_debug_permission
			),
			array(
				'User',
				self::option_debug_permission
			)
		)));
	}
	
	/**
	 * Session user ID
	 *
	 * @return integer
	 */
	function session_user_id() {
		try {
			$session = $this->application->session();
		} catch (Exception $e) {
			return null;
		}
		if (!$session instanceof Interface_Session) {
			error_log(__METHOD__ . " \$this->application->session() returned non-session? " . type($session) . " " . strval($session));
			return null;
		}
		return $session->user_id();
	}
	
	/**
	 * Retrieve the column used for logging in
	 *
	 * @return string
	 */
	function column_login() {
		return $this->class->column_login;
	}
	
	/**
	 * Retrieve the password column name
	 *
	 * @return string
	 */
	function column_password() {
		return $this->class->column_password;
	}
	
	/**
	 * Retrieve the email column name
	 *
	 * @return string
	 */
	function column_email() {
		return $this->class->column_email;
	}
	
	/**
	 * Get or set the login column value
	 *
	 * @param string $set        	
	 *
	 * @return User
	 */
	function login($set = null) {
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
	function email($set = null) {
		$column = $this->column_email();
		if (!$column) {
			throw new Exception_Semantics("No email column in {class}", array(
				"class" => get_class($this)
			));
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
	function check_user() {
		return true;
	}
	
	/**
	 * Get/set the password field
	 *
	 * @param string $set        	
	 * @return string|User
	 */
	function password($set = null) {
		$column = $this->column_password();
		if ($set !== null) {
			return $this->set_member($column, $set);
		}
		return $this->member($column);
	}
	
	/**
	 * Check a username and password.
	 * Will not authenticate user until ->authenticated(true) is called.
	 *
	 * @param string $username        	
	 * @param string $password        	
	 * @param string $use_hash        	
	 * @param string $case_sensitive        	
	 * @return boolean
	 */
	function authenticate($username, $password, $use_hash = true, $case_sensitive = true) {
		// TODO: This will break if everyone else uses the class property. Update ->column_login, or don't use option
		$column_login = $this->option("column_login", $this->column_login());
		$this->set_member($column_login, $username);
		if (!$this->fetch_by_key($username, $column_login)) {
			return false;
		}
		$this_password = $this->password();
		if ($use_hash) {
			$auth_test = strcasecmp(md5($password), $this_password) === 0;
		} else {
			$auth_test = $case_sensitive ? ($password === $this_password) : strcasecmp($password, $this_password) === 0;
		}
		if ($auth_test) {
			//$this->authenticated(true);
			return true;
		}
		return false;
	}
	
	/**
	 * Get/set authentication status
	 *
	 * @param string $set        	
	 * @return boolean User
	 */
	function authenticated($set = null) {
		$matches = ($this->session_user_id() === $this->id());
		if ($set === null) {
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
		$changed = false;
		$session = $this->application->session();
		$session->authenticate($this->id(), $_SERVER['REMOTE_ADDR']);
		User::set_current($this);
		$this->call_hook("authenticated", $session);
		$this->application->call_hook("user_authenticated", $this, $session);
		$this->application->modules->all_hook("user_authenticated", $this, $session);
		if ($changed) {
			$this->store();
		}
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
	public function must($action, Model $context = null, array $options = array()) {
		if (!$this->can($action, $context, $options)) {
			throw new Exception_Permission($this, $action, $context, $options);
		}
		return $this;
	}
	public static final function clean_permission($string) {
		return strtolower(strtr($string, array(
			' ' => '_',
			'.' => '_',
			'-' => '_',
			'__' => '::'
		)));
	}
	
	/**
	 * The core of the permissions system
	 *
	 * <code>
	 * $user = $this->application->user();
	 * $yes = $user->can("write checks"); // Simple invokation
	 * // Invoke with an object - the following two lines are identical
	 * $yes = $yes && $user->can("edit", $account); // Object invokation
	 * // Invoke with additional arguments
	 * $yes = $yes && $user->can("transfer", $checking, array("target" => $savings)));
	 * </code>
	 *
	 * To test if ANY permission is allowed, use the | as separator for all permissions passed, like
	 * so:
	 *
	 * <code>
	 * $user = $this->application->user();
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
	 *        	Object on which to act
	 * @param mixed $object
	 *        	Extra optional settings, permission-specific
	 * @return boolean
	 */
	public function can($actions, $context = null, array $options = array()) {
		// Sanitize input
		if ($actions instanceof Model) {
			list($actions, $context) = array(
				$context,
				$actions
			);
		}
		if ($context && !$context instanceof Model) {
			zesk()->logger->warning("Non model passed as \$context to {method} ({type})", array(
				"method" => __METHOD__,
				"type" => type($context)
			));
			$context = null;
		}
		
		$result = false; // By default, don't allow anything
		// Allow multiple actions
		$is_or = is_string($actions) && strpos($actions, '|');
		$actions = to_list($actions, array(), $is_or ? '|' : ';');
		$default_result = $this->option("can", false);
		foreach ($actions as $action) {
			$action = self::clean_permission($action);
			$result = $this->call_hook_arguments("can", array(
				$action,
				$context,
				$options
			), $default_result);
			if (self::$debug_permission) {
				zesk()->logger->debug("User::can({action},{context}) = {result} (Roles {roles})", array(
					"action" => $action,
					"context" => $context,
					"result" => $result,
					"roles" => $this->_roles
				));
			}
			if ($is_or) {
				// One must be allowed to continue
				if ($result === true) {
					return $result;
				}
			} else {
				// All must be allowed to continue
				if ($result === false) {
					return $result;
				}
			}
		}
		return $result;
	}
	
	/**
	 * Check if a user can edit an object
	 *
	 * @param Object $object        	
	 * @return boolean
	 */
	function can_edit($object) {
		return $this->can("edit", $object);
	}
	/**
	 * Check if a user can view an object
	 *
	 * @param Object $object        	
	 * @return boolean
	 */
	function can_view($object) {
		return $this->can("view", $object);
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \zesk\Object::display_name()
	 */
	function display_name() {
		return $this->member($this->column_login());
	}
	
	/**
	 * Check if a user can delete an object
	 *
	 * @param Object $object        	
	 * @return boolean
	 */
	function can_delete($object) {
		return $this->can("delete", $object);
	}
	
	/**
	 * Implement Object::permissions
	 *
	 * @return array
	 */
	static function permissions(Application $application) {
		return parent::default_permissions($application, __CLASS__) + array(
			__CLASS__ . '::become' => array(
				'title' => __("Become another user"),
				"class" => "User"
			)
		);
	}
	
	/**
	 * Takes an array which can be formatted with theme("actions") and filters based on permissions.
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
	public function filter_actions(array $actions, Model $context = null, array $options = array()) {
		$actions_passed = array();
		foreach ($actions as $href => $attributes) {
			if (is_array($attributes) && array_key_exists("permission", $attributes)) {
				$permission = $attributes['permission'];
				unset($attributes['permission']);
				if (is_array($permission)) {
					list($a_permission, $a_context, $a_options) = $permission + (array(
						null,
						null,
						array()
					));
					if ($this->can($a_permission, $a_context, $a_options)) {
						$actions_passed[$href] = $attributes;
					}
				} else if ($this->can($permission, $context, $options)) {
					$actions_passed[$href] = $attributes;
				}
			} else {
				$actions_passed[$href] = $attributes;
			}
		}
		return $actions_passed;
	}
	
	/**
	 * "Instance" means an object. We really mean "singleton" when we've been using instance. Deprecate this meaning of "instance."
	 *
	 * @deprecated 2016-12 
	 * @see User::current
	 * @param boolean $want_object
	 *        	Don't return null, return an empty object if true
	 * @return User
	 */
	static function instance($want_object = true) {
		zesk()->deprecated();
		return self::current($want_object);
	}
	
	/**
	 * Set global user instance
	 * @deprecated 2016-12 
	 *
	 * @param User $user
	 * @return User
	 */
	static function set_instance(User $user) {
		zesk()->deprecated();
		return self::set_current($user);
	}
	
	/**
	 * Retrieve the current logged in user ID
	 *
	 * @deprecated 2016-12 
	 * @return integer
	 */
	static function instance_id() {
		zesk()->deprecated();
		return self::instance_id();
	}
	
	/**
	 * Current user
	 *
	 * @param boolean $want_object
	 *        	Don't return null, return an empty object if true
	 * @return User
	 * @deprecated 2017-08 Remove global-related stuff from almost every object unless absolutely necessary. This should be included in application state, not here.
	 */
	static function current($want_object = true) {
		global $zesk;
		/* @var $zesk Kernel */
		
		zesk()->deprecated();
		$user = $zesk->objects->user;
		if ($user instanceof User) {
			return $user;
		}
		$class = $zesk->configuration->path_get_first(array(
			"zesk\\User::class",
			"User::class"
		), __CLASS__);
		$user = $zesk->objects->factory($class);
		/* @var $user \zesk\User */
		if (!$user->_from_session()) {
			if (!$want_object) {
				return null;
			}
		}
		self::set_current($user);
		return $user;
	}
	
	/**
	 * Set global user instance
	 *
	 * @param User $user
	 * @return User
	 * @deprecated 2017-08 Remove global-related stuff from almost every object unless absolutely necessary. This should be included in application state, not here.
	 */
	static function set_current(User $user) {
		global $zesk;
		zesk()->deprecated();
		/* @var $zesk Kernel */
		$zesk->objects->user = $user;
		return $user;
	}
	
	/**
	 * Retrieve the current logged in user ID
	 *
	 * @return integer
	 * @deprecated 2017-08 Remove global-related stuff from almost every object unless absolutely necessary. This should be included in application state, not here.
	 */
	static function current_id() {
		$user = User::current();
		return $user->id();
	}
	
	/**
	 * @deprecated 2017-08
	 * Session lock and validation information
	 */
	private function check_session() {
		return $this->call_hook_arguments("check_session", array(), $this);
	}
	
	/**
	 * @deprecated 2017-08
	 * Load user from session ID
	 */
	protected function _from_session() {
		// Command means no browser - perhaps change semantic to be "has browser with a session?"
		if (Command::running()) {
			return false;
		}
		$user_id = $this->session_user_id();
		if (empty($user_id)) {
			return false;
		}
		$this->initialize($user_id);
		try {
			if ($this->fetch()) {
				if ($this->check_session()) {
					if ($this->check_user()) {
						zesk()->objects->user = $this;
						return true;
					}
				}
			}
		} catch (Exception_Object_NotFound $e) {
		}
		return false;
	}
}

