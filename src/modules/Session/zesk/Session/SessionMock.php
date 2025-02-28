<?php
declare(strict_types=1);
/**
 *
 */

namespace zesk\Session;

use Throwable;
use zesk\Application;
use zesk\Exception\AuthenticationException;
use zesk\Hookable;
use zesk\Interface\Userlike;
use zesk\Interface\SessionInterface;
use zesk\Request;

/**
 */
class SessionMock extends Hookable implements SessionInterface
{
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
	public function __construct(Application $application, mixed $mixed = null, array $options = [])
	{
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
	public function initializeSession(Request $request): self
	{
		return $this;
	}

	/**
	 *
	 * {@inheritdoc}
	 *
	 * @see SessionInterface::id()
	 */
	public function id(): int|string|array
	{
		return $this->id;
	}

	public function has(int|string $key): bool
	{
		return $this->__isset($key);
	}

	public function __isset(int|string $key): bool
	{
		return isset($this->data[$key]);
	}

	/**
	 *
	 * {@inheritdoc}
	 *
	 * @see SettingsInterface::get()
	 */
	public function get(int|string|null $key = null, mixed $default = null): mixed
	{
		if ($key === null) {
			return $this->data;
		}
		return $this->data[$key] ?? $default;
	}

	/**
	 *
	 */
	public function __get(int|string $key): mixed
	{
		return $this->data[$key] ?? null;
	}

	/**
	 *
	 */
	public function __set(int|string $key, mixed $value): void
	{
		$this->data[$key] = $value;
	}

	/**
	 *
	 * {@inheritdoc}
	 *
	 * @see SettingsInterface::set()
	 */
	public function set(int|string $key, mixed $value = null): self
	{
		$this->__set($key, $value);
		return $this;
	}

	/**
	 *
	 * @return string
	 */
	private function global_session_userId(): string
	{
		return $this->application->configuration->getPath([
			__CLASS__,
			'userId_variable',
		], 'user');
	}

	/**
	 *
	 * {@inheritdoc}
	 *
	 * @see SessionInterface::userId()
	 */
	public function userId(): int
	{
		return $this->__get($this->global_session_userId());
	}

	/**
	 *
	 * {@inheritdoc}
	 *
	 * @see SessionInterface::user()
	 */
	public function user(): Userlike
	{
		$userId = $this->userId();
		if (empty($userId)) {
			throw new AuthenticationException('No user ID');
		}

		try {
			$user = $this->application->userFactory();
			assert($user instanceof Userlike);
			return $user->fetch($userId);
		} catch (Throwable $e) {
			$this->__set($this->global_session_userId(), null);

			throw new AuthenticationException('User not found {userId}', ['userId' => $userId], 0, $e);
		}
	}

	/**
	 *
	 * {@inheritdoc}
	 *
	 * @see SessionInterface::authenticate()
	 */
	public function authenticate(Userlike $user, Request $request): void
	{
		$this->__set($this->global_session_userId(), $user->id());
		$this->__set($this->global_session_userId() . '_IP', $request->remoteIP());
	}

	/**
	 *
	 * @return bool
	 * @see SessionInterface::isAuthenticated()
	 */
	public function isAuthenticated(): bool
	{
		$user = $this->__get($this->global_session_userId());
		return !empty($user);
	}

	/**
	 *
	 * {@inheritdoc}
	 *
	 * @see SessionInterface::relinquish()
	 */
	public function relinquish(): void
	{
		$this->__set($this->global_session_userId(), null);
	}

	/**
	 *
	 * {@inheritdoc}
	 *
	 * @see SettingsInterface::variables()
	 */
	public function variables(): array
	{
		return $this->data;
	}

	/**
	 *
	 * {@inheritdoc}
	 *
	 * @see SessionInterface::delete()
	 */
	public function delete(): void
	{
		$this->data = [];
	}
}
