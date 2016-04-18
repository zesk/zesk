<?php
/**
 * $URL: https://code.marketacumen.com/zesk/trunk/classes/zesk/user.inc $
 * @package zesk
 * @subpackage objects
 * @author kent
 * @copyright Copyright &copy; 2009, Market Acumen, Inc.
 * Created on Fri Apr 02 21:00:12 EDT 2010 21:00:12
 */

/**
 * @see Class_Zesk_User
 *
 * @author kent
 *
 */
abstract class Zesk_User extends Object {
	const global_instance = "User::instance";
	/**
	 *
	 * @var Class_Zesk_User
	 */
	protected $class = null;
	
	/**
	 * Session user ID
	 *
	 * @return integer
	 */
	static function session_user_id() {
		try {
			$session = Session::instance();
		} catch (Exception $e) {
			return null;
		}
		if (!$session instanceof Interface_Session) {
			error_log("Session::instance returned non-session? " . type($session) . " " . strval($session));
			return null;
		}
		return $session->user_id();
	}
	
	/**
	 * Session lock and validation information
	 */
	private function check_session() {
		/*
		 $logout_uri = zesk::get("Site.LogoutURI", "/logout.php");
		 
		 $session = Session::instance();
		 
		 TODO: Fix this, it's not working with new sessions
		 *
		 $user_ip = $session->get(zesk::get("SESSION_USER_IP", "UserIP"));
		 if ($user_ip !== $_SERVER['REMOTE_ADDR']) {
		 http::redirect($logout_uri, "You have been logged out automatically because your IP address changed. Please login again.");
		 }
		 $last_page_view = $session->last_page_view;
		 $now			= time();
		 if ($now > $last_page_view + Session::inactivity_seconds()) {
		 http::redirect($logout_uri, "You have been logged out automatically because of inactivity. Please login again.");
		 }
		 $session->last_page_view = $now;
		 $first_page_view = $session->first_page_view;
		 if ($now > $first_page_view + Session::max_duration()) {
		 http::redirect($logout_uri, "You have been logged out automatically because you've been logged in too long. Please login again.");
		 }
		 */
	}
	
	/**
	 * Current user
	 *
	 * @param boolean $want_object
	 *        	Don't return null, return an empty object if true
	 * @return User
	 */
	static function instance($want_object = true) {
		$user = zesk::get(self::global_instance);
		if ($user instanceof User) {
			return $user;
		}
		$user = new User();
		if (!$user->_from_session()) {
			if (!$want_object) {
				return null;
			} else {
				$user = new User();
			}
		}
		self::set_instance($user);
		return $user;
	}
	
	/**
	 * Set global user instance
	 *
	 * @param User $user
	 * @return User
	 */
	static function set_instance(User $user) {
		zesk::set(self::global_instance, $user);
		return $user;
	}
	
	/**
	 * Retrieve the current logged in user ID
	 *
	 * @return integer
	 */
	static function instance_id() {
		$user = User::instance();
		return $user->id();
	}
	
	/**
	 * Load user from session ID
	 */
	protected function _from_session() {
		$u = zesk::get(self::global_instance);
		if (is_array($u)) {
			$this->initialize($u);
			return true;
		}
		// Command means no browser - perhaps change semantic to be "has browser with a session?"
		if (!Command::running()) {
			$user_id = self::session_user_id();
			if (empty($user_id)) {
				return false;
			}
			$this->initialize($user_id);
			try {
				if ($this->fetch()) {
					$this->check_session();
					if ($this->check_user()) {
						zesk::set("User", $this);
						return true;
					}
				}
			} catch (Exception_Object_NotFound $e) {
				return false;
			}
		}
		return false;
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
	 * @return Zesk_User
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
	 * @return Zesk_User
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
	 * @return boolean
	 */
	function check_user() {
		return true;
	}
	
	/**
	 * Return the user login name
	 *
	 * @return string
	 */
	function current_name() {
		if ($this->_from_session()) {
			return $this->display_name();
		} else {
			return __("No user.");
		}
	}
	
	/**
	 * @deprecated 2014-02-07
	 * @return integer|null
	 */
	private function current_id() {
		if ($this->_from_session()) {
			return $this->id();
		}
		return null;
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
	 * Check a username and password. Will not authenticate user until ->authenticated(true) is called.
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
		if ($use_hash) {
			$auth_test = strcasecmp(md5($password), $this->password()) === 0;
		} else {
			$auth_test = $case_sensitive ? ($password === $this->password()) : strcasecmp($password, $this->password()) === 0;
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
	 * @return boolean Zesk_User
	 */
	function authenticated($set = null) {
		$matches = (self::session_user_id() === $this->id());
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
		$session = Session::instance();
		$session->authenticate($this->id(), $_SERVER['REMOTE_ADDR']);
		$this->hook("authenticated", $session);
		zesk::all_hook("Module::user_authenticated;Application::user_authenticated", $this, $session);
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
	 * $user = User::instance();
	 * $yes = $user->can("write checks"); // Simple invokation
	 * // Invoke with an object - the following two lines are identical
	 * $yes = $yes && $user->can("edit", $account); // Object invokation
	 * // Invoke with additional arguments
	 * $yes = $yes && $user->can("transfer", $checking, array("target" => $savings)));
	 * </code>
	 *
	 * To test if ANY permission is allowed, use the | as separator for all permissions passed, like so:
	 *
	 * <code>
	 * $user = User::instance();
	 * if ($user->can("edit|view|delete", $other_user)) {
	 * 		echo $menus; // User can edit, view, or delete
	 * }
	 * </code>
	 *
	 * Using the default list separator ";" means the meaning is ALL ACTIONS must be permitted to continue.
	 *
	 * @param mixed $action Can be an array of actions, all of which must pass, or a string of actions whose separator determines if the meaning is "AND" or "OR" for each permission.
	 * @param mixed $context Object on which to act
	 * @param mixed $object Extra optional settings, permission-specific
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
		if (!$context instanceof Model) {
			$context = null;
		}
		
		$result = false; // By default, don't allow anything
		// Allow multiple actions
		$is_or = is_string($actions) && strpos($actions, '|');
		$actions = to_list($actions, array(), $is_or ? '|' : ';');
		$default_result = zesk::getb("User::can", false);
		foreach ($actions as $action) {
			$action = self::clean_permission($action);
			if (is_string($context)) {
				$context = self::clean_permission($context);
			}
			$result = $this->hook_array("can", array(
				$action,
				$context,
				$options
			), $default_result);
			if (zesk::getb('User::debug_permission')) {
				log::debug("User::can({action},{context}) = {result}", array(
					"action" => $action,
					"context" => $context,
					"result" => $result
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
	 * Get control for User objects. TODO move this elsewhere - doesn't belong here - too specific to User structure.
	 *
	 * @param string $name
	 * @param string $label
	 * @param string $required
	 * @param string $active_only
	 * @return Control_Select_Object
	 */
	static function control($name, $label, $required = false, $active_only = true) {
		$w = new Control_Select_Object();
		$w->names($name, $label);
		$w->required($required);
		$options = array(
			"table" => Object::class_table_name("User"),
			"textcolumn" => "FirstName,LastName",
			"format" => "{LastName}, {FirstName}",
			'default' => User::instance_id()
		);
		$where = array();
		if ($active_only) {
			$where["IsActive"] = 'true';
		}
		$user = User::master();
		if (!$user->can("User::view all")) {
			$where['Account'] = $user->Account;
		}
		$options['where'] = $where;
		$options['default'] = self::masterID();
		$w->set_option($options);
		return $w;
	}
	
	/**
	 * Implement Object::permissions
	 *
	 * @return array
	 */
	static function permissions() {
		return parent::default_permissions("User") + array(
			'become' => array(
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
	 * @param Model $context Default context to pass to "can" function
	 * @param array $options Default options to pass to "can" function
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
}

