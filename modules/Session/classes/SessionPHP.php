<?php
declare(strict_types=1);

/**
 *
 */

namespace zesk\Session;

use Throwable;
use zesk\Application;
use zesk\Exception_Authentication;
use zesk\Interface_Session;
use zesk\Interface_UserLike;
use zesk\ORM\User;
use zesk\Request;

/**
 */
class SessionPHP implements Interface_Session {
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
	 * {@inheritdoc}
	 *
	 * @see Interface_Session::id()
	 */
	public function id(): int|string|array {
		return session_id();
	}

	public function has(string $name): bool {
		return $this->__isset($name);
	}

	public function __isset(string $name): bool {
		$this->need();
		return isset($_SESSION[$name]);
	}

	/**
	 *
	 * {@inheritdoc}
	 *
	 * @see Interface_Settings::get()
	 */
	public function get(string $name, mixed $default = null): mixed {
		$this->need();
		return $_SESSION[$name] ?? $default;
	}

	/**
	 *
	 * @param string $name
	 * @return mixed
	 * @see Interface_Settings::__get()
	 */
	public function __get(string $name): mixed {
		$this->need();
		return $_SESSION[$name] ?? null;
	}

	/**
	 *
	 * @param string $name
	 * @param mixed $value
	 * @return void
	 * @see Interface_Settings::__set()
	 */
	public function __set(string $name, mixed $value): void {
		$this->need();
		$_SESSION[$name] = $value;
	}

	/**
	 *
	 * {@inheritdoc}
	 *
	 * @see Interface_Settings::set()
	 */
	public function set(string $name, $value = null): self {
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
	 * @throws Exception_Authentication
	 * @see Interface_Session::userId()
	 */
	public function userId(): int {
		$result = $this->__get($this->globalSessionUserId());
		if (is_int($result)) {
			return $result;
		}

		throw new Exception_Authentication('No session ID');
	}

	/**
	 *
	 *
	 * @return Interface_UserLike
	 * @throws Exception_Authentication
	 * @see Interface_Session::user()
	 */
	public function user(): Interface_UserLike {
		$userId = $this->userId();
		if (empty($userId)) {
			throw new Exception_Authentication('Not authenticated');
		}

		try {
			$result = $this->application->ormFactory(User::class, $userId)->fetch();
			assert($result instanceof Interface_UserLike);
			return $result;
		} catch (Throwable $e) {
			$this->__set($this->globalSessionUserId(), null);

			throw new Exception_Authentication(
				'No user fetched for user id "{userId}"',
				['userId' => $userId],
				0,
				$e
			);
		}
	}

	/**
	 *
	 * @param User $user
	 * @param string $ip
	 * @throws Exception_Authentication
	 * @return void
	 * @see Interface_Session::authenticate()
	 */
	public function authenticate(Interface_UserLike $user, string $ip = ''): void {
		try {
			$this->__set($this->globalSessionUserId(), $user->id());
		} catch (Throwable $t) {
			throw new Exception_Authentication('Unable to authenticate user - no ID', [], 0, $t);
		}
		$this->__set($this->globalSessionUserId() . '_IP', $ip);
	}

	/**
	 *
	 * {@inheritdoc}
	 *
	 * @see Interface_Session::authenticated()
	 */
	public function authenticated(): bool {
		$user = $this->__get($this->globalSessionUserId());
		return !empty($user);
	}

	/**
	 *
	 * {@inheritdoc}
	 *
	 * @see Interface_Session::relinquish()
	 */
	public function relinquish(): void {
		$this->__set($this->globalSessionUserId(), null);
	}

	/**
	 *
	 * {@inheritdoc}
	 *
	 * @see Interface_Settings::variables()
	 */
	public function variables(): array {
		return $_SESSION;
	}

	/**
	 *
	 * {@inheritdoc}
	 *
	 * @see Interface_Session::delete()
	 */
	public function delete(): void {
		if (!$this->application->console()) {
			session_destroy();
		}
	}
}
