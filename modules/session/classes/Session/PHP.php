<?php
declare(strict_types=1);

/**
 *
 */

namespace zesk;

/**
 */
class Session_PHP implements Interface_Session {
	/**
	 *
	 * @var string
	 */
	private bool $started;

	/**
	 *
	 * @var Application
	 */
	private Application $application;

	/**
	 *
	 * @param Application $application
	 * @param mixed $mixed
	 * @param array $options
	 */
	public function __construct(Application $application, mixed $mixed = null, array $options = []) {
		$this->application = $application;
		$this->started = false;
	}

	/**
	 * Singleton interface to retrieve current session
	 *
	 * @return self
	 */
	public function initializeSession(Request $request): self {
		$this->need();
		return $this;
	}

	public function need(): void {
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
		if ($name === null) {
			return $_SESSION;
		}
		return $_SESSION[$name] ?? $default;
	}

	/**
	 *
	 * {@inheritdoc}
	 *
	 * @see Interface_Settings::__get()
	 */
	public function __get(string $name): mixed {
		$this->need();
		return $_SESSION[$name] ?? null;
	}

	/**
	 *
	 * {@inheritdoc}
	 *
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
	 * {@inheritdoc}
	 *
	 * @see Interface_Settings::filter()
	 */
	public function filter(string|array $list): array {
		return ArrayTools::filter($_SESSION, $list);
	}

	/**
	 *
	 * @return string
	 */
	private function global_session_userId(): string {
		return $this->application->configuration->path('session')->get('user_id_variable', 'user');
	}

	/**
	 *
	 * {@inheritdoc}
	 *
	 * @see Interface_Session::userId()
	 */
	public function userId(): int {
		$result = $this->__get($this->global_session_userId());
		if (is_int($result)) {
			return $result;
		}

		throw new Exception_NotFound();
	}

	/**
	 *
	 *
	 * @return User
	 * @throws Exception_Authentication
	 * @throws Exception_ORM_Empty
	 * @throws Exception_ORM_NotFound
	 * @see Interface_Session::user()
	 */
	public function user(): User {
		$user_id = $this->userId();
		if (empty($user_id)) {
			throw new Exception_Authentication('Not authenticated');
		}

		try {
			return $this->application->ormFactory(__NAMESPACE__ . '\\' . 'User', $user_id)->fetch();
		} catch (Exception_ORM_NotFound $e) {
			$this->__set($this->global_session_userId(), null);

			throw $e;
		}
	}

	/**
	 *
	 * @param User $user
	 * @param string $ip
	 * @return void
	 * @throws Exception_Deprecated
	 * @see Interface_Session::authenticate()
	 */
	public function authenticate(User $user, string $ip = ''): void {
		$this->__set($this->global_session_userId(), $user->id());
		$this->__set($this->global_session_userId() . '_IP', $ip);
	}

	/**
	 *
	 * {@inheritdoc}
	 *
	 * @see Interface_Session::authenticated()
	 */
	public function authenticated(): bool {
		$user = $this->__get($this->global_session_userId());
		return !empty($user);
	}

	/**
	 *
	 * {@inheritdoc}
	 *
	 * @see Interface_Session::deauthenticate()
	 */
	public function deauthenticate(): void {
		$this->__set($this->global_session_userId(), null);
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
