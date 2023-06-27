<?php
declare(strict_types=1);
/**
 * Session object is a more powerful multiple server database session storage.
 *
 * Dates and times are stored using UTC.
 *
 * @package zesk
 * @subpackage Session
 * @author kent
 * @copyright Copyright &copy; 2023, Market Acumen, Inc.
 */

namespace zesk\Session;

use Throwable;
use TypeError;

use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\OneToOne;
use Doctrine\ORM\Mapping\PostLoad;
use Doctrine\ORM\Mapping\PrePersist;
use Doctrine\ORM\Mapping\UniqueConstraint;

use zesk\Application;
use zesk\Cron\Attributes\CronClusterMinute;
use zesk\Doctrine\Model;
use zesk\Doctrine\Trait\AutoID;
use zesk\Doctrine\User;
use zesk\Exception as zeskException;
use zesk\Exception\AuthenticationException;
use zesk\Exception\NotFoundException;
use zesk\Exception\KeyNotFound;
use zesk\Exception\SemanticsException;
use zesk\HTTP;
use zesk\Interface\SessionInterface;
use zesk\Login\Controller;
use zesk\Request;
use zesk\Response;
use zesk\Timestamp;
use zesk\Interface\Userlike;

use zesk\Types;

use function random_int;

/**
 *
 */
#[Entity]
#[UniqueConstraint(name: 'tokenType', columns: ['token', 'type'])]
class Session extends Model implements SessionInterface
{
	/**
	 *
	 */
	public const DEFAULT_COOKIE_NAME = 'sessionToken';

	public const OPTION_METHOD = 'method';

	public const METHOD_COOKIE = 'cookie';

	public const METHOD_AUTHORIZATION = 'authorization';

	public const TYPE_ONE_TIME = 'one-time';

	public const TYPE_COOKIE = 'cookie';

	public const TYPE_AUTHORIZATION_KEY = 'auth-key';

	/*===============================================================================================================*\
		 __  __           _      _
		|  \/  | ___   __| | ___| |
		| |\/| |/ _ \ / _` |/ _ \ |
		| |  | | (_) | (_| |  __/ |
		|_|  |_|\___/ \__,_|\___|_|

	\*===============================================================================================================*/
	use AutoID;

	#[Column(type: 'string', length: 32, nullable: false)]
	public string $token = '';

	#[Column(type: 'string', length: 8, nullable: false)]
	public string $type;

	#[OneToOne]
	#[JoinColumn(name: 'user', nullable: true)]
	public null|User $user = null;

	#[Column(type: 'ip', length: 40, nullable: false)]
	public string $ip;

	#[Column(type: 'timestamp', nullable: false)]
	public Timestamp $created;

	#[Column(type: 'timestamp', nullable: false)]
	public Timestamp $modified;

	#[Column(type: 'timestamp', nullable: false)]
	public Timestamp $expires;

	#[Column(type: 'timestamp', nullable: false)]
	public Timestamp $seen;

	#[Column(type: 'integer', nullable: false)]
	public int $sequenceIndex = 0;

	#[Column(type: 'json', nullable: false)]
	public array $data = [];

	/**
	 * Original session data (to see if things change)
	 *
	 * @var array
	 */
	private array $original = [];

	/**
	 * Something changed?
	 *
	 * @var boolean
	 */
	private bool $changed = false;

	/**
	 * @return void
	 */
	public function initialize(): void
	{
		$this->changed = false;
	}

	#[PostLoad]
	public function justLoaded(): void
	{
		$this->original = $this->data;
		$this->setOptions($this->application->optionArray('session'));
	}

	/**
	 * @return $this
	 * @throws ORMException
	 */
	public function seen(): self
	{
		$this->seen = Timestamp::now();
		$this->em->persist($this);
		return $this;
	}

	/**
	 * @return void
	 */
	#[PrePersist]
	public function beforePersist(): void
	{
		if (!$this->ip) {
			$this->ip = '127.0.0.1';
		}
	}

	/*===============================================================================================================*\
	 ____                _             ___       _             __
	/ ___|  ___  ___ ___(_) ___  _ __ |_ _|_ __ | |_ ___ _ __ / _| __ _  ___ ___
	\___ \ / _ \/ __/ __| |/ _ \| '_ \ | || '_ \| __/ _ \ '__| |_ / _` |/ __/ _ \
	 ___) |  __/\__ \__ \ | (_) | | | || || | | | ||  __/ |  |  _| (_| | (_|  __/
	|____/ \___||___/___/_|\___/|_| |_|___|_| |_|\__\___|_|  |_|  \__,_|\___\___|
	\*===============================================================================================================*/
	/**
	 * @return int|string|array
	 */
	public function id(): int|string|array
	{
		return $this->id;
	}

	/**
	 * @param int|string $key
	 * @return bool
	 */
	public function has(int|string $key): bool
	{
		return array_key_exists($key, $this->data);
	}

	/**
	 * @param int|string $key
	 * @return mixed
	 * @throws KeyNotFound
	 */
	public function get(int|string $key): mixed
	{
		if (array_key_exists($key, $this->data)) {
			return $this->data[$key];
		}

		throw new KeyNotFound($key);
	}

	/**
	 * @param int|string $key
	 * @param mixed $value
	 * @return $this
	 * @throws TypeError Requires values to be serializable
	 */
	public function set(int|string $key, mixed $value): self
	{
		$this->data[$key] = Types::simplify($value);
		return $this;
	}


	/*===============================================================================================================*\
		 ____                _
		/ ___|  ___  ___ ___(_) ___  _ __
		\___ \ / _ \/ __/ __| |/ _ \| '_ \
		 ___) |  __/\__ \__ \ | (_) | | | |
		|____/ \___||___/___/_|\___/|_| |_|
	\*===============================================================================================================*/

	/**
	 *
	 * @return integer
	 */
	public function cookieExpire(): int
	{
		return Types::toInteger($this->optionPath(['cookie', 'expire'], 604800));
	}

	/**
	 * Set Session cookie
	 *
	 * @return string
	 */
	private static function _generateToken(): string
	{
		try {
			$rand = random_int(PHP_INT_MIN, PHP_INT_MAX);
		} catch (Throwable) {
			$func = 'mt' . '_rand'; // Does this throw? Hide it so it does not get deleted.
			$rand = $func(PHP_INT_MIN, PHP_INT_MAX);
		}
		return md5(dechex($rand) . microtime());
	}

	/**
	 * Authenticate user
	 *
	 * @param Userlike $user
	 * @param Request $request
	 * @return void
	 * @throws AuthenticationException
	 * @see SessionInterface::authenticate()
	 */
	public function authenticate(Userlike $user, Request $request): void
	{
		try {
			$cookieExpire = $this->cookieExpire();
			$this->user = $user;
			$this->ip = $request->remoteIP();
			$this->expires = Timestamp::now()->addUnit($cookieExpire);
			$this->em->persist($this);
		} catch (Throwable $t) {
			throw new AuthenticationException('Failed to store session {exceptionClass} {message}', zeskException::exceptionVariables($t), 0, $t);
		}
	}

	/**
	 * Are we authenticated?
	 *
	 * @return bool
	 * @see SessionInterface::isAuthenticated()
	 */
	public function isAuthenticated(): bool
	{
		return $this->user !== null;
	}

	/**
	 * De-authenticate
	 *
	 * @return void
	 * @throws AuthenticationException
	 */
	public function relinquish(): void
	{
		try {
			$this->user()->invokeHooks(User::HOOK_LOGOUT);
		} catch (AuthenticationException) {
		}
		$this->user = null;

		try {
			$this->em->persist($this);
		} catch (ORMException) {
			throw new AuthenticationException('Unable to persist session');
		}
	}

	/**
	 * Has this session expired?
	 *
	 * @return bool
	 */
	public function expired(): bool
	{
		return $this->expires->before(Timestamp::now());
	}

	/**
	 * Logout expired, run hook
	 */
	private function logoutExpire(): void
	{
		try {
			$user = $this->user();
			$user->invokeHooks(User::HOOK_LOGOUT_EXPIRE);
		} catch (Throwable $e) {
			// User deleted
			$this->application->error($e);
		}
	}

	/**
	 * Run once a minute
	 * @see self::expireSessions
	 * @see \zesk\Cron\Module
	 */
	#[CronClusterMinute]
	public static function expireSessions(Application $application): void
	{
		$ex = Criteria::expr();
		$criteria = Criteria::create()->where($ex->lt('expires', Timestamp::now()));
		$em = $application->entityManager();
		$sessions = $em->getRepository(self::class)->findBy([$criteria]);
		foreach ($sessions as $session) {
			/* @var $session Session */
			$session->logoutExpire();

			try {
				$em->remove($session);
			} catch (ORMException) {
				// pass
			}
		}
		$em->flush();
	}

	/**
	 *
	 * @return Timestamp
	 */
	private function computeExpires(): Timestamp
	{
		$expire = $this->cookieExpire();
		return Timestamp::now()->addUnit($expire);
	}

	/**
	 *
	 * @return string
	 */
	private function cookieName(): string
	{
		return $this->optionPath(['cookie', 'name'], self::DEFAULT_COOKIE_NAME);
	}

	/**
	 * @return $this
	 */
	public function foundSession(): self
	{
		return $this;
	}

	/**
	 *
	 *
	 * @return $this
	 * @throws NotFoundException
	 * @see SessionInterface::initializeSession()
	 */
	public function fetchSession(string $token, string $type): self
	{
		$session = $this->em->getRepository(self::class)->findOneBy(['token' => $token, 'type' => $type]);
		if (!$session) {
			throw new NotFoundException("No session with $token and $type");
		}
		assert($session instanceof self);
		return $session;
	}

	/**
	 * @param Request $request
	 * @return $this
	 */
	public function initializeSession(Request $request): self
	{
		$methods = [
			self::METHOD_COOKIE => $this->initializeCookieSession(...),
			self::METHOD_AUTHORIZATION => $this->initializeAuthorizationSession(...),
		];
		$method = $methods[$this->option(self::OPTION_METHOD)] ?? null;
		if ($method) {
			return $method($request);
		}
		$this->application->warning('{class}::{option} is not set to one of {methods} - no session will load', [
			'methods' => array_keys($methods), 'class' => self::class, 'option' => self::OPTION_METHOD,
		]);
		return $this;
	}

	public function newSession(Request $request, string $type): self
	{
		$this->ip = $request->ip();
		$this->token = $this->_generateToken();
		assert($this->checkCookie($this->token) === true);
		$this->type = $type;
		$this->expires = $this->computeExpires();
		return $this;
	}

	public function checkCookie(string $cookie): bool
	{
		if (preg_match('/[^A-Za-z0-9]+/', $cookie)) {
			return false;
		}
		return true;
	}

	/**
	 *
	 * @param Request $request
	 * @return Session
	 * @throws KeyNotFound
	 * @throws ORMException
	 * @throws SemanticsException
	 */
	protected function initializeCookieSession(Request $request): self
	{
		$type = self::TYPE_COOKIE;
		$cookie_name = $this->cookieName();

		try {
			$cookie_value = $request->cookie($cookie_name);
			if ($this->checkCookie($cookie_value)) {
				return $this->fetchSession($cookie_value, $type);
			}
		} catch (NotFoundException) {
		}
		$this->newSession($request, $type);

		$cookie_options = $this->cookieOptions();
		$cookie_value = $this->token;
		$this->application->hooks->registerHook(Response::HOOK_BEFORE_HEADERS, function (Response $response) use (
			$cookie_name,
			$cookie_value,
			$cookie_options
		): void {
			$response->setCookie($cookie_name, $cookie_value, $cookie_options);
		});
		$this->em->persist($this);
		return $this;
	}

	/**
	 * Loads, never saves.
	 *
	 * @param Request $request
	 * @return $this
	 */
	protected function initializeAuthorizationSession(Request $request): self
	{
		$type = self::TYPE_AUTHORIZATION_KEY;

		try {
			$token = $request->header(HTTP::REQUEST_AUTHORIZATION);
			return $this->fetchSession($token, $type);
		} catch (KeyNotFound|NotFoundException) {
		}
		$this->ip = $request->ip();
		return $this;
	}

	/**
	 * @param Request $request
	 * @return $this
	 */
	public function createAuthorizationSession(Request $request): self
	{
		$this->em->persist($this->newSession($request, self::TYPE_AUTHORIZATION_KEY));
		return $this;
	}

	/**
	 *
	 * @return string
	 */
	public function hash(): string
	{
		return $this->token;
	}

	/**
	 * @return string
	 */
	public function token(): string
	{
		return $this->token;
	}

	/**
	 *
	 * @param User $user
	 * @param string $ip
	 * @param int $expire_seconds Expiration time in seconds, inherits from
	 *    'zesk\SessionORM::one_time_expire_seconds' if not set. Defaults to 1 day (86400 seconds).
	 *
	 * @return Session
	 * @throws ORMException
	 */
	public static function oneTimeCreate(User $user, string $ip, int $expire_seconds = -1): self
	{
		$app = $user->application;
		if ($expire_seconds < 0) {
			$expire_seconds = Types::toInteger($app->configuration->getPath([
				__CLASS__, 'one_time_expire_seconds',
			], 86400));
		}
		// Only one allowed at any time, I guess.
		$em = $app->entityManager();
		$delete = $em->createQuery('DELETE FROM ' . self::class . ' WHERE type=:type and user=:user');
		$delete->setParameters(['type' => self::TYPE_ONE_TIME, 'user' => $user]);
		$delete->execute();

		$session = new self($app);
		assert($session instanceof self);
		$session->token = self::_generateToken();
		$session->type = self::TYPE_ONE_TIME;
		$session->expires = Timestamp::now()->addUnit($expire_seconds);
		$session->user = $user;
		$session->ip = $ip;
		$app->entityManager()->persist($session);
		return $session;
	}

	/**
	 * Given a hash, find the one-time Session
	 *
	 * @param Application $application
	 * @param string $hash
	 * @return self
	 * @throws NotFoundException
	 */
	public static function oneTimeFind(Application $application, string $hash): self
	{
		$hash = trim($hash);
		$criteria = [
			'token' => $hash, 'type' => self::TYPE_ONE_TIME,
		];
		$oneTime = $application->entityManager()->getRepository(self::class)->findOneBy($criteria);
		if ($oneTime instanceof self) {
			return $oneTime;
		}

		throw new NotFoundException('No {type} found with token {token}', $criteria);
	}

	/**
	 * Converts a 'one-time' to a regular cookie session with the same ID
	 *
	 * @param Userlike $user
	 * @param Request $request
	 * @return $this
	 * @throws AuthenticationException
	 * @throws SemanticsException
	 */
	public function oneTimeAuthenticate(Userlike $user, Request $request): self
	{
		if ($this->type !== self::TYPE_ONE_TIME) {
			throw new SemanticsException('Not a one-time session');
		}
		$this->token = $this->_generateToken();
		$this->type = self::TYPE_COOKIE;
		$this->authenticate($user, $request);
		return $this;
	}

	/**
	 * Count all other sessions seen within the seconds window provided
	 *
	 * @param int $nSeconds
	 * @return integer
	 */
	public function sessionCount(int $nSeconds = 600): int
	{
		$ex = Criteria::expr();
		$criteria = Criteria::create()->where($ex->gt('seen', Timestamp::now()->addUnit(-$nSeconds)))->andWhere($ex->neq('id', $this->id()));
		return $this->em->getRepository(self::class)->count([$criteria]);
	}

	/**
	 * @return int
	 * @throws AuthenticationException
	 */
	public function userId(): int
	{
		return $this->user()->id();
	}

	/**
	 * @return User
	 * @throws AuthenticationException
	 */
	public function user(): User
	{
		if (!$this->user) {
			throw new AuthenticationException('Not authenticated session {token}', ['token' => $this->token]);
		}
		return $this->user;
	}

	public function changed($members = null): bool
	{
		return $this->changed;
	}

	/**
	 * Retrieve all variables
	 *
	 * @see SessionInterface::variables()
	 */
	public function variables(): array
	{
		return $this->members['data'];
	}

	/**
	 *
	 * @return array
	 */
	public function cookieOptions(): array
	{
		return $this->optionArray('cookie');
	}
}
