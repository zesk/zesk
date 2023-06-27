<?php
declare(strict_types=1);
/**
 *
 */

namespace zesk\Session;

use Throwable;
use zesk\Application;
use zesk\Authentication;
use zesk\Hookable;
use zesk\Userlike;
use zesk\Interface\SessionInterface;
use zesk\ORM\ORMBase;
use zesk\ORM\User;
use zesk\Request;
use zesk\Response;

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

	public function has(int|string $name): bool
	{
		return $this->__isset($name);
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
	public function get(int|string|null $name = null, mixed $default = null): mixed
	{
		if ($name === null) {
			return $this->data;
		}
		return $this->data[$name] ?? $default;
	}

	/**
	 *
	 * {@inheritdoc}
	 *
	 * @see SettingsInterface::__get()
	 */
	public function __get(int|string $key): mixed
	{
		return $this->data[$key] ?? null;
	}

	/**
	 *
	 * {@inheritdoc}
	 *
	 * @see SettingsInterface::__set()
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
	public function set(int|string $name, mixed $value = null): self
	{
		$this->__set($name, $value);
		return $this;
	}

	/**
	 *
	 * @return string
	 */
	private function global_session_userId(): string
	{
		return $this->application->configuration->getPath([__CLASS__, 'user_id_variable'], 'user');
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
		$user_id = $this->userId();
		if (empty($user_id)) {
			throw new Authentication('No user ID');
		}

		try {
			$user = $this->application->ormFactory(User::class, $user_id)->fetch();
			assert($user instanceof Userlike);
			return $user;
		} catch (Throwable $e) {
			$this->__set($this->global_session_userId(), null);

			throw new Authentication('User not found {user_id}', ['user_id' => $user_id], 0, $e);
		}
	}

	/**
	 *
	 * {@inheritdoc}
	 *
	 * @see SessionInterface::authenticate()
	 */
	public function authenticate(Userlike $user, Request $request, Response $response): void
	{
		$this->__set($this->global_session_userId(), ORMBase::mixedToID($user));
		$this->__set($this->global_session_userId() . '_IP', $request->remoteIP());
	}

	/**
	 *
	 * @see SessionInterface::isAuthenticated()
	 * @return bool
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
