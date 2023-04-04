<?php
declare(strict_types=1);
/**
 * @package zesk
 * @subpackage objects
 * @author kent
 * @copyright Copyright &copy; 2023, Market Acumen, Inc.
 * Created on Fri Apr 02 21:00:12 EDT 2010 21:00:12
 */

namespace zesk\Doctrine;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Throwable;
use zesk\Application;
use zesk\Exception;
use zesk\Model as BaseModel;
use zesk\Doctrine\Trait\AutoID;
use zesk\Exception\Authentication;
use zesk\Exception\KeyNotFound;
use zesk\Exception\ParameterException;
use zesk\Exception\PermissionDenied;
use zesk\Exception\Unsupported;
use zesk\Interface\Userlike;
use zesk\ORM\Exception\ORMEmpty;
use zesk\ORM\Exception\ORMNotFound;
use zesk\Request;
use zesk\Response;
use zesk\Timestamp;
use zesk\Types;

/**
 * Represents a means of authentication to an application.
 *
 * @see Class_User
 *
 * @author kent
 */
#[Entity]
class User extends Model implements Userlike {

	/**
	 * Boolean value to enable debugging of permissions
	 *
	 * @var string
	 */
	public const OPTION_DEBUG_PERMISSION = 'debugPermission';

	static array $allowedMethods = [
		'md5', 'sha1', 'sha512', 'sha256', 'ripemd128', 'ripemd160', 'ripemd320',
	];
	use AutoID;

	#[Column(type: 'string', length: 128, nullable: false)]
	public string $email;
	#[Column(type: 'string', length: 16, nullable: false)]
	public string $passwordMethod;
	#[Column(type: 'string', length: 128, nullable: false)]
	public string $passwordData;
	#[Column(type: 'string', length: 64, nullable: false)]
	public string $nameFirst;
	#[Column(type: 'string', length: 64, nullable: false)]
	public string $nameLast;
	#[Column(type: 'tinyint', nullable: false)]
	public bool $isActive;
	#[Column(type: 'timestamp', nullable: true)]
	public Timestamp $lastLogin;
	#[Column(type: 'timestamp', nullable: true)]
	public Timestamp $validated;
	#[Column(type: 'timestamp', nullable: true)]
	public Timestamp $agreed;
	#[Column(type: 'timestamp', nullable: true)]
	public Timestamp $created;
	#[Column(type: 'timestamp', nullable: true)]
	public Timestamp $modified;

	/**
	 */
	public function authenticationData(): array {
		return [
			'email' => $this->email, 'nameFirst' => $this->nameFirst, 'nameLast' => $this->nameLast,
			'lastLogin' => $this->lastLogin, 'validated' => $this->validated,
		];
	}

	/**
	 * Session user ID
	 *
	 * @param Request $request
	 * @return int
	 * @throws Authentication
	 */
	public function sessionUserId(Request $request): int {
		return $this->application->session($request)->userId();
	}

	/**
	 * Get/set the password field
	 *
	 * @param string $set
	 * @param boolean $plaintext When set is non-null, whether value is plain text or not.
	 * @return User
	 * @throws KeyNotFound
	 * @throws ORMEmpty
	 * @throws ORMNotFound
	 * @throws Unsupported
	 */
	public function setPassword(string $set, string $method): self {
		if (!in_array($method, self::$allowedMethods)) {
			throw new ParameterException("Invalid method {method}", ['method' => $method]);
		}
		$this->passwordMethod = $method;
		$this->passwordData = $this->_generate_hash($set);

		return $this;
	}

	/**
	 *
	 * @param string $string
	 * @return string
	 */
	private function _generate_hash(string $string, bool $binary = false): string {
		return hash($this->passwordMethod, $string, $binary);
	}

	/**
	 * Check a username and password.
	 * Will not authenticate user until ->authenticated($request, $response) is called.
	 *
	 * @param string $password
	 * @return self
	 * @throws Authentication
	 */
	public function authenticate(string $password): self {
		if (strcasecmp($this->_generate_hash($password), $this->passwordData) === 0) {
			return $this;
		}
		throw new Authentication($this->email);
	}

	/**
	 * Get/set authentication status
	 *
	 * @param Request $request Required. Request to match this user against.
	 * @param null|Response $response Optional. If supplied, authenticates this user in the associated response
	 * (generally, by setting a cookie.)
	 * @return NULL|User
	 * @throws Authentication
	 */
	public function authenticated(Request $request, Response $response = null): ?User {
		if (empty($this->id)) {
			return null;
		}
		$matches = ($this->sessionUserId($request) === $this->id);
		if ($matches) {
			return $this;
		}
		if ($response === null) {
			return null;
		}
		$session = $this->application->requireSession($request);
		$session->authenticate($this, $request);
		$this->callHook('authenticated', $request, $response, $session);
		$this->application->callHook('userAuthenticated', $this, $request, $response, $session);
		$this->application->modules->allHook('userAuthenticated', $this, $request, $response, $session);
		return $this;
	}

	/**
	 * Similar to $user->can(...) but instead throws an PermissionDenied on failure
	 *
	 * Checks that user can perform action optionally on object
	 *
	 * @param array|string $actions
	 * @param Model|null $context
	 * @param array $options
	 * @return void
	 * @throws PermissionDenied
	 */
	public function must(array|string $actions, BaseModel $context = null, array $options = []): void {
		if (!$this->can($actions, $context, $options)) {
			throw new PermissionDenied($this, $actions, $context, $options);
		}
	}

	final public static function clean_permission($string): string {
		return strtolower(strtr($string, [
			' ' => '_', '.' => '_', '-' => '_', '__' => '::',
		]));
	}

	/**
	 * The core of the permissions system
	 *
	 * <code>
	 * $yes = $user->can("write checks"); // Simple invocation
	 * // Invoke with an object - the following two lines are identical
	 * $yes = $yes && $user->can("edit", $account); // ORM invocation
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
	 * @param Model $context
	 *
	 * @param mixed $object
	 *
	 * @return boolean
	 */
	/**
	 * @param string|array $actions Can be an array of actions, all of which must pass, or a string of actions whose
	 *            uniformly used separator determines if the meaning is "AND" (`;`) or "OR" (`|`)
	 * @param Model|null $context Model on which to act
	 * @param array $options Extra optional settings, permission-specific
	 * @return bool
	 * @see Userlike::can()
	 */
	public function can(string|array $actions, BaseModel $context = null, array $options = []): bool {
		$result = false; // By default, don't allow anything
		// Allow multiple actions
		$is_or = is_string($actions) && strpos($actions, '|');
		$actions = Types::toList($actions, [], $is_or ? '|' : ';');
		$default_result = $this->option('can', false);
		foreach ($actions as $action) {
			$action = self::clean_permission($action);
			$skipLog = false;

			try {
				$result = $this->callHookArguments('can', [
					$action, $context, $options,
				], $default_result);
			} catch (Throwable $e) {
				$skipLog = true;
				$this->application->logger->error("User::can({action},{context}) = {result} (Roles {roles}): Exception {throwableClass} {message}\n{backtrace}", [
						'action' => $action, 'context' => $context, 'result' => false, 'roles' => $this->_roles,
					] + Exception::exceptionVariables($e));
			}
			if ($this->optionBool(self::OPTION_DEBUG_PERMISSION) && !$skipLog) {
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

		return (bool)$result;
	}

	/**
	 * Check if a user can edit an object
	 *
	 * @param Model $object
	 * @return boolean
	 */
	public function canEdit(Model $object): bool {
		return $this->can('edit', $object);
	}

	/**
	 * Check if a user can view an object
	 *
	 * @param Model $object
	 * @return boolean
	 */
	public function canView(Model $object): bool {
		return $this->can('view', $object);
	}

	/**
	 *
	 *
	 * @return string
	 */
	public function displayName(): string {
		return $this->email;
	}

	/**
	 * Check if a user can delete an object
	 *
	 * @param Model $object
	 * @return boolean
	 */
	public function canDelete(Model $object): bool {
		return $this->can('delete', $object);
	}

	/**
	 * Implement ORM::permissions
	 *
	 * @param Application $application
	 * @return array
	 */
	public static function permissions(Application $application): array {
		return parent::default_permissions($application, __CLASS__) + [
				__CLASS__ . '::become' => [
					'title' => $application->locale->__('Become another user'), 'class' => 'User',
				],
			];
	}

	/**
	 * Takes an array which can be formatted with $application->theme("actions") and filters based on permissions.
	 * Use the key "permission" in value to specify a permission to check. It can be a string, or an
	 * array of ($action, $context, $options) to check.
	 *
	 * @param array $actions
	 * @param Model|null $context
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
