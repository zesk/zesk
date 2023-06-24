<?php
declare(strict_types=1);
/**
 *
 */

namespace zesk\Session;

use Throwable;
use zesk\Application;
use zesk\Exception\AuthenticationException;
use zesk\Interface\SessionInterface;
use zesk\Interface\Userlike;
use zesk\ORM\User;
use zesk\Request;

/**
 */
class SessionPHP implements SessionInterface {
	/**
	 * Have we started the session yet?
	 *
	 * @var bool
	 */
	private bool $started;

	/**
	 *
	 * @var Application
	 */
	private Application $application;

	/**
	 * @var array
	 */
	private array $init;

	/**
	 *
	 * @param Application $application
	 * @param mixed $mixed
	 * @param array $options
	 */
	public function __construct(Application $application, mixed $mixed = null, array $options = []) {
		$this->application = $application;
		$this->started = false;
		$this->init = is_array($mixed) ? $mixed : [];
	}

	/**
	 * Configure session connected to the Request
	 * @param Request $request
	 * @return self
	 */
	public function initializeSession(Request $request): self {
		$this->need();
		return $this;
	}

	private function need(): void {
		if ($this->started) {
			return;
		}
		if (!$this->application->console()) {
			session_start();
		} else {
			global $_SESSION;
			if (!isset($_SESSION)) {
				$_SESSION = [];
			}
			$_SESSION = array_merge($_SESSION, $this->init);
		}
		$this->started = true;
	}

	/**
	 *
	 *
	 * @see SessionInterface::id()
	 */
	public function id(): int|string|array {
		return session_id();
	}

	public function has(int|string $name): bool {
		return $this->__isset($name);
	}

	public function __isset(int|string $name): bool {
		$this->need();
		return isset($_SESSION[$name]);
	}

	/**
	 *
	 * @see SettingsInterface::get()
	 */
	public function get(int|string $name, mixed $default = null): mixed {
		$this->need();
		return $_SESSION[$name] ?? $default;
	}

	/**
	 *
	 * @param int|string $name
	 * @return mixed
	 * @see SettingsInterface::__get()
	 */
	public function __get(int|string $name): mixed {
		$this->need();
		return $_SESSION[$name] ?? null;
	}

	/**
	 *
	 * @param int|string $name
	 * @param mixed $value
	 * @return void
	 * @see SettingsInterface::__set()
	 */
	public function __set(int|string $name, mixed $value): void {
		$this->need();
		$_SESSION[$name] = $value;
	}

	/**
	 *
	 * {@inheritdoc}
	 *
	 * @see SettingsInterface::set()
	 */
	public function set(int|string $name, $value = null): self {
		$this->__set($name, $value);
		return $this;
	}

	/**
	 *
	 * @return string
	 */
	private function globalSessionUserId(): string {
		return $this->application->configuration->path('session')->get('user_id_variable', 'user');
	}

	/**
	 *
	 * @return int
	 * @throws AuthenticationException
	 * @see SessionInterface::userId()
	 */
	public function userId(): int {
		$result = $this->__get($this->globalSessionUserId());
		if (is_int($result)) {
			return $result;
		}

		throw new AuthenticationException('No session ID');
	}

	/**
	 *
	 *
	 * @return Userlike
	 * @throws AuthenticationException
	 * @see SessionInterface::user()
	 */
	public function user(): Userlike {
		$userId = $this->userId();
		if (empty($userId)) {
			throw new AuthenticationException('Not authenticated');
		}

		try {
			$result = $this->application->ormFactory(User::class, $userId)->fetch();
			assert($result instanceof Userlike);
			return $result;
		} catch (Throwable $e) {
			$this->__set($this->globalSessionUserId(), null);

			throw new AuthenticationException(
				'No user fetched for user id "{userId}"',
				['userId' => $userId],
				0,
				$e
			);
		}
	}

	/**
	 *
	 * @param Userlike $user
	 * @param Request $request
	 * @return void
	 * @throws AuthenticationException
	 * @see SessionInterface::authenticate()
	 */
	public function authenticate(Userlike $user, Request $request): void {
		try {
			$this->__set($this->globalSessionUserId(), $user->id());
		} catch (Throwable $t) {
			throw new AuthenticationException('Unable to authenticate user - no ID', [], 0, $t);
		}
		$this->__set($this->globalSessionUserId() . '_IP', $request->remoteIP());
	}

	/**
	 *
	 * {@inheritdoc}
	 *
	 * @see SessionInterface::isAuthenticated()
	 */
	public function isAuthenticated(): bool {
		$user = $this->__get($this->globalSessionUserId());
		return !empty($user);
	}

	/**
	 *
	 * {@inheritdoc}
	 *
	 * @see SessionInterface::relinquish()
	 */
	public function relinquish(): void {
		$this->__set($this->globalSessionUserId(), null);
	}

	/**
	 *
	 * {@inheritdoc}
	 *
	 * @see SettingsInterface::variables()
	 */
	public function variables(): array {
		return $_SESSION;
	}

	/**
	 *
	 * {@inheritdoc}
	 *
	 * @see SessionInterface::delete()
	 */
	public function delete(): void {
		if (!$this->application->console()) {
			session_destroy();
		}
	}
}
