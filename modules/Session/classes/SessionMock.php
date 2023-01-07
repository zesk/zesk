<?php
declare(strict_types=1);

/**
 *
 */

namespace zesk\Session;

use Throwable;
use zesk\Application;
use zesk\Exception_Authentication;
use zesk\Hookable;
use zesk\Interface_UserLike;
use zesk\Interface_Session;
use zesk\ORM\ORMBase;
use zesk\ORM\User;
use zesk\Request;

/**
 */
class SessionMock extends Hookable implements Interface_Session {
	/**
	 *
	 * @var string
	 */
	protected string $id;

	/**
	 *
	 * @var array
	 */
	private array $data = [];

	/**
	 *
	 * @param Application $application
	 * @param mixed $mixed
	 * @param array $options
	 */
	public function __construct(Application $application, mixed $mixed = null, array $options = []) {
		parent::__construct($application, $options);
		$this->inheritConfiguration();
		$this->application = $application;
		$this->id = md5(microtime());
		if (is_array($mixed)) {
			$this->data = $mixed;
		}
	}

	/**
	 * Singleton interface to retrieve current session
	 *
	 * @param Request $request
	 * @return self
	 */
	public function initializeSession(Request $request): self {
		return $this;
	}

	/**
	 *
	 * {@inheritdoc}
	 *
	 * @see Interface_Session::id()
	 */
	public function id(): int|string|array {
		return $this->id;
	}

	public function has(int|string $name): bool {
		return $this->__isset($name);
	}

	public function __isset(int|string $key): bool {
		return isset($this->data[$key]);
	}

	/**
	 *
	 * {@inheritdoc}
	 *
	 * @see Interface_Settings::get()
	 */
	public function get(int|string|null $name = null, mixed $default = null): mixed {
		if ($name === null) {
			return $this->data;
		}
		return $this->data[$name] ?? $default;
	}

	/**
	 *
	 * {@inheritdoc}
	 *
	 * @see Interface_Settings::__get()
	 */
	public function __get(int|string $key): mixed {
		return $this->data[$key] ?? null;
	}

	/**
	 *
	 * {@inheritdoc}
	 *
	 * @see Interface_Settings::__set()
	 */
	public function __set(int|string $key, mixed $value): void {
		$this->data[$key] = $value;
	}

	/**
	 *
	 * {@inheritdoc}
	 *
	 * @see Interface_Settings::set()
	 */
	public function set(int|string $name, mixed $value = null): self {
		$this->__set($name, $value);
		return $this;
	}

	/**
	 *
	 * @return string
	 */
	private function global_session_userId(): string {
		return $this->application->configuration->getPath([__CLASS__, 'user_id_variable'], 'user');
	}

	/**
	 *
	 * {@inheritdoc}
	 *
	 * @see Interface_Session::userId()
	 */
	public function userId(): int {
		return $this->__get($this->global_session_userId());
	}

	/**
	 *
	 * {@inheritdoc}
	 *
	 * @see Interface_Session::user()
	 */
	public function user(): Interface_UserLike {
		$user_id = $this->userId();
		if (empty($user_id)) {
			throw new Exception_Authentication('No user ID');
		}

		try {
			$user = $this->application->ormFactory(User::class, $user_id)->fetch();
			assert($user instanceof Interface_UserLike);
			return $user;
		} catch (Throwable $e) {
			$this->__set($this->global_session_userId(), null);

			throw new Exception_Authentication('User not found {user_id}', ['user_id' => $user_id], 0, $e);
		}
	}

	/**
	 *
	 * {@inheritdoc}
	 *
	 * @see Interface_Session::authenticate()
	 */
	public function authenticate(Interface_UserLike $user, $ip = false): void {
		$this->__set($this->global_session_userId(), ORMBase::mixedToID($user));
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
	 * @see Interface_Session::relinquish()
	 */
	public function relinquish(): void {
		$this->__set($this->global_session_userId(), null);
	}

	/**
	 *
	 * {@inheritdoc}
	 *
	 * @see Interface_Settings::variables()
	 */
	public function variables(): array {
		return $this->data;
	}

	/**
	 *
	 * {@inheritdoc}
	 *
	 * @see Interface_Session::delete()
	 */
	public function delete(): void {
		$this->data = [];
	}
}
