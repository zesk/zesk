<?php
declare(strict_types=1);

/**
 * @package zesk
 * @subpackage objects
 * @author kent
 * @copyright Copyright &copy; 2022, Market Acumen, Inc.
 * Created on Fri Apr 02 21:00:12 EDT 2010 21:00:12
 */

namespace zesk\ORM;

use Throwable;
use zesk\Application;
use zesk\Exception_Authentication;
use zesk\Exception_Permission;
use zesk\Exception_Unsupported;
use zesk\Hooks;
use zesk\Interface_UserLike;
use zesk\Model;
use zesk\Request;
use zesk\Response;

/**
 * Represents a means of authentication to an application.
 *
 * @see Class_User
 *
 * @author kent
 */
class User extends ORMBase implements Interface_UserLike {
	/**
	 * Boolean value to enable debugging of permissions
	 *
	 * @var string
	 */
	public const OPTION_DEBUG_PERMISSION = 'debug_permission';

	/**
	 *
	 * @var bool
	 */
	public static bool $debug_permission = false;

	/**
	 * Syntactic sygar; types this member.
	 *
	 * @var Class_User
	 */
	protected Class_Base $class;

	/**
	 *
	 * @param Application $application
	 */
	public static function hooks(Application $application): void {
		$application->configuration->path(__CLASS__);
		$application->hooks->add(Hooks::HOOK_CONFIGURED, __CLASS__ . '::configured');
	}

	public function authenticationData(): array {
		return $this->members($this->optionIterable('authenticationDataMembers', [
			$this->column_login(), $this->column_email(),
		]));
	}

	/**
	 *
	 * @param Application $application
	 */
	public static function configured(Application $application): void {
		self::$debug_permission = toBool($application->configuration->getFirstPath([
			[
				__CLASS__, self::OPTION_DEBUG_PERMISSION,
			], [
				'User', self::OPTION_DEBUG_PERMISSION,
			],
		]));
	}

	/**
	 * Session user ID
	 *
	 * @param Request $request
	 * @return int
	 * @throws Exception_Authentication
	 */
	public function sessionUserId(Request $request): int {
		return $this->application->session($request)->userId();
	}

	/**
	 * Retrieve the column used for logging in
	 *
	 * @return string
	 */
	public function column_login(): string {
		return $this->class->column_login;
	}

	/**
	 * Retrieve the password column name
	 *
	 * @return string
	 */
	public function column_password(): string {
		return $this->class->column_password;
	}

	/**
	 * Retrieve the email column name
	 *
	 * @return string
	 */
	public function column_email(): string {
		return $this->class->column_email;
	}

	/**
	 * Get or set the login column value
	 *
	 * @param string $set
	 *
	 * @return User
	 */
	public function setLogin(string $set): self {
		return $this->setMember($this->column_login(), $set);
	}

	/**
	 * @return string
	 */
	public function login(): string {
		try {
			return $this->member($this->column_login());
		} catch (Throwable) {
			return '';
		}
	}

	/**
	 * Get or set the email column value
	 *
	 * @param string $set
	 *
	 * @return User
	 */
	public function setEmail(string $set): self {
		return $this->setMember($this->column_email(), $set);
	}

	/**
	 * Get or set the email column value
	 *
	 * @param string $set
	 *
	 * @return User
	 */
	public function email(): string {
		try {
			return $this->member($this->column_email());
		} catch (Throwable) {
			return '';
		}
	}

	/**
	 * Override in subclasses to perform a final check before loading a user from the Session
	 *
	 * @param Request $request
	 * @return bool
	 */
	public function check_user(Request $request): bool {
		return true;
	}

	/**
	 * Get/set the password field
	 *
	 * @return string
	 */
	public function password(): string {
		try {
			return $this->member($this->column_password());
		} catch (Throwable) {
			return '';
		}
	}

	/**
	 * Get/set the password field
	 *
	 * @param string $set
	 * @param boolean $plaintext When set is non-null, whether value is plain text or not.
	 * @return User
	 */
	public function setPassword(string $set, bool $plaintext = true): self {
		$column = $this->column_password();
		return $this->setMember($column, $plaintext ? $this->_generate_hash($set) : $set);
	}

	/**
	 *
	 * @return string
	 */
	public function password_method(): string {
		return strtolower(trim($this->member($this->class->column_hash_method) ?? $this->class->default_hash_method));
	}

	/**
	 *
	 * @param string $string
	 * @return string
	 */
	private function _generate_hash(string $string): string {
		return $this->generate_hash($string, $this->class->column_password_is_binary);
	}

	/**
	 * Using the
	 * @param string $string
	 * @param boolean $raw_output
	 * @return string
	 * @throws Exception_Unsupported
	 */
	public function generate_hash(string $string, bool $raw_output = true): string {
		$algo = $this->password_method();
		if (in_array($algo, $this->class->allowed_hash_methods)) {
			return hash($algo, $string, $raw_output);
		}

		throw new Exception_Unsupported('Invalid hash algorithm {algo} in User {id}, using default', [
			'algo' => $algo, 'id' => $this->id(), 'default' => $this->class->default_hash_method,
		]);
	}

	/**
	 * Check a username and password.
	 * Will not authenticate user until ->authenticated($request, $response) is called.
	 *
	 * @param string $password
	 * @param bool $use_hash
	 * @param bool $case_sensitive
	 * @return self
	 * @throws Exception_Authentication
	 * @throws Exception_Unsupported
	 */
	public function authenticate(string $password, bool $use_hash = true, bool $case_sensitive = true): self {
		$this_password = $this->password();
		if ($use_hash) {
			$auth_test = strcasecmp($this->generate_hash($password, false), $this_password) === 0;
		} else {
			$auth_test = $case_sensitive ? ($password === $this_password) : strcasecmp($password, $this_password) === 0;
		}
		if ($auth_test) {
			return $this;
		}

		throw new Exception_Authentication($this->login());
	}

	/**
	 * Get/set authentication status
	 *
	 * @param Request $request Required. Request to match this user against.
	 * @param null|Response $response Optional. If supplied, authenticates this user in the associated response
	 * (generally, by setting a cookie.)
	 * @return NULL|User
	 */
	public function authenticated(Request $request, Response $response = null): ?User {
		$matches = ($this->sessionUserId($request) === $this->id());
		if ($response === null) {
			if ($this->isNew()) {
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
		$session = $this->application->requireSession($request);
		$session->authenticate($this, $request, $response);
		$this->callHook('authenticated', $request, $response, $session);
		$this->application->callHook('user_authenticated', $this, $request, $response, $session);
		$this->application->modules->allHook('user_authenticated', $this, $request, $response, $session);
		return $this;
	}

	/**
	 * Similar to $user->can(...) but instead throws an Exception_Permission on failure
	 *
	 * Checks that user can perform action optionally on object
	 *
	 * @param string $action
	 * @param ORMBase $context
	 * @param array $options
	 * @return User
	 * @throws Exception_Permission
	 */
	public function must(string $action, ORMBase $context = null, array $options = []) {
		if (!$this->can($action, $context, $options)) {
			throw new Exception_Permission($this, $action, $context, $options);
		}
		return $this;
	}

	final public static function clean_permission($string) {
		return strtolower(strtr($string, [
			' ' => '_', '.' => '_', '-' => '_', '__' => '::',
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
	 *
	 * @param ?ORMBase $context
	 *
	 * @param mixed $object
	 *
	 * @return boolean
	 */
	/**
	 * @param string|array $actions Can be an array of actions, all of which must pass, or a string of actions whose
	 *            separator determines if the meaning is "AND" or "OR" for each permission.
	 * @param ORMBase|null $context ORM on which to act
	 * @param array $options Extra optional settings, permission-specific
	 * @return bool
	 */
	public function can(string|array $actions, ORMBase $context = null, array $options = []) {
		$result = false; // By default, don't allow anything
		// Allow multiple actions
		$is_or = is_string($actions) && strpos($actions, '|');
		$actions = toList($actions, [], $is_or ? '|' : ';');
		$default_result = $this->option('can', false);
		foreach ($actions as $action) {
			$action = self::clean_permission($action);
			$skipLog = false;

			try {
				$result = $this->callHookArguments('can', [
					$action, $context, $options,
				], $default_result);
			} catch (\Exception $e) {
				$skipLog = true;
				$this->application->logger->error("User::can({action},{context}) = {result} (Roles {roles}): Exception {exceptionClass} {message}\n{backtrace}", [
					'action' => $action, 'context' => $context, 'result' => false, 'roles' => $this->_roles,
				] + Exception::exceptionVariables($e));
			}
			if (self::$debug_permission && !$skipLog) {
				$this->application->logger->debug('User::can({action},{context}) = {result} (Roles {roles}) ({extra})', [
					'action' => $action, 'context' => $context, 'result' => $result, 'roles' => $this->_roles,
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
		$hook = $options[$hook_option_name] ?? $this->option($hook_option_name);
		if ($hook !== null) {
			$this->callHookArguments(is_string($hook) ? $hook : $default_hook, [
				$actions, $context, $options,
			]);
		}

		return (bool) $result;
	}

	/**
	 * Check if a user can edit an object
	 *
	 * @param ORMBase $object
	 * @return boolean
	 */
	public function canEdit(ORMBase $object): bool {
		return $this->can('edit', $object);
	}

	/**
	 * Check if a user can view an object
	 *
	 * @param ORMBase $object
	 * @return boolean
	 */
	public function canView(ORMBase $object): bool {
		return $this->can('view', $object);
	}

	/**
	 *
	 * {@inheritdoc}
	 *
	 * @see \zesk\ORMBase::displayName()
	 */
	public function displayName(): string {
		return $this->member($this->column_login());
	}

	/**
	 * Check if a user can delete an object
	 *
	 * @param ORMBase $object
	 * @return boolean
	 */
	public function canDelete(ORMBase $object): bool {
		return $this->can('delete', $object);
	}

	/**
	 * Implement ORM::permissions
	 *
	 * @return array
	 */
	public static function permissions(Application $application): array {
		return parent::default_permissions($application, __CLASS__) + [
			__CLASS__ . '::become' => [
				'title' => __('Become another user'), 'class' => 'User',
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
	 *            Default context to pass to "can" function
	 * @param array $options
	 *            Default options to pass to "can" function
	 * @return array
	 */
	public function filterActions(array $actions, Model $context = null, array $options = []): array {
		$actions_passed = [];
		foreach ($actions as $href => $attributes) {
			if (is_array($attributes) && array_key_exists('permission', $attributes)) {
				$permission = $attributes['permission'];
				unset($attributes['permission']);
				if (is_array($permission)) {
					[$a_permission, $a_context, $a_options] = $permission + ([
						null, null, [],
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
