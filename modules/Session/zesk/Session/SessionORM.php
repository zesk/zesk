<?php
declare(strict_types=1);
/**
 * Session object is a more powerful, multi-server, database session storage.
 * Dates and times are stored using UTC.
 * @package zesk
 * @subpackage session
 * @author kent
 * @copyright Copyright &copy; 2023, Market Acumen, Inc.
 */

namespace zesk\Session;

use Exception;
use zesk\Exception as zeskException;
use Throwable;
use zesk\Application;
use zesk\Database_Exception;
use zesk\Database_Exception_Duplicate;
use zesk\Database_Exception_SQL;
use zesk\Database_Exception_Table_NotFound;
use zesk\Exception_Authentication;
use zesk\Exception_Class_NotFound;
use zesk\Exception_Configuration;
use zesk\Exception_Convert;
use zesk\Exception_Key;
use zesk\Exception_NotFound;
use zesk\Exception_Parameter;
use zesk\Exception_Parse;
use zesk\HTTP;
use zesk\Interface_UserLike;
use zesk\ORM\Exception_ORMDuplicate;
use zesk\ORM\Exception_ORMEmpty;
use zesk\ORM\Exception_ORMNotFound;
use zesk\Exception_Semantics;
use zesk\ORM\Exception_Store;
use zesk\Interface_Session;
use zesk\IPv4;
use zesk\ORM\ORMBase;
use zesk\Request;
use zesk\Response;
use zesk\Timestamp;
use zesk\ORM\User;
use function random_int;

/**
 * Sessions inherit some options from the global Application object in the initialize() function
 *
 * @see Class_SessionORM
 * @property int $id
 * @property string $cookie
 * @property boolean $is_one_time
 * @property User $user
 * @property string $ip
 * @property Timestamp $created
 * @property Timestamp $modified
 * @property Timestamp $expires
 * @property Timestamp $seen
 * @property integer $sequence_index
 * @property array $data
 * @author kent
 */
class SessionORM extends ORMBase implements Interface_Session {
	public const OPTION_METHOD = 'method';

	public const METHOD_COOKIE = 'cookie';

	public const METHOD_AUTHORIZATION = 'authorization';

	public const MEMBER_ID = 'id';

	public const MEMBER_TOKEN = 'token';

	public const MEMBER_SEEN = 'seen';

	public const MEMBER_TYPE = 'type';

	public const MEMBER_EXPIRES = 'expires';

	public const MEMBER_IP = 'ip';

	public const MEMBER_DATA = 'data';

	public const MEMBER_USER = 'user';

	public const TYPE_ONE_TIME = 'one-time';

	public const TYPE_COOKIE = 'cookie';

	public const TYPE_AUTHORIZATION_KEY = 'auth-key';

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
	 *
	 * {@inheritdoc}
	 *
	 * @see ORMBase::initialize()
	 */
	public function initialize(mixed $mixed, mixed $initialize = false): self {
		$result = parent::initialize($mixed, $initialize);
		$this->changed = false;
		$this->setOptions($this->application->optionArray('session'));
		return $result;
	}

	/**
	 * @param array $mixed
	 * @return $this
	 * @throws Exception_Convert
	 * @throws Exception_Key
	 * @throws Exception_ORMEmpty
	 * @throws Exception_ORMNotFound
	 * @throws Exception_Semantics
	 * @throws Database_Exception_SQL
	 * @throws Exception_Class_NotFound
	 * @throws Exception_Configuration
	 * @throws Exception_NotFound
	 * @throws Exception_Parameter
	 * @throws Exception_Parse
	 */
	public function fetch(array $mixed = []): self {
		return parent::fetch($mixed)->seen();
	}

	/**
	 * @return $this
	 * @throws Database_Exception_Duplicate
	 * @throws Database_Exception_SQL
	 * @throws Database_Exception_Table_NotFound
	 * @throws Exception_Key
	 * @throws Exception_Semantics
	 */
	public function seen(): self {
		$query = $this->queryUpdate();
		$sql = $query->sql();
		$query->value('*' . self::MEMBER_SEEN, $sql->now())->value(self::MEMBER_EXPIRES, $this->computeExpires())->value('*sequence_index', 'sequence_index+1')->addWhere(self::MEMBER_ID, $this)->setLowPriority(true)->execute();
		$this->callHook('seen');
		return $this;
	}

	/**
	 * Called before actual store
	 *
	 * @return void
	 * @throws Exception_Key
	 * @throws Exception_ORMEmpty
	 */
	public function hook_store(): void {
		$ip = $this->member('ip');
		if (!is_string($ip) || !IPv4::valid($ip)) {
			$this->setMember('ip', '127.0.0.1');
		}
	}

	/**
	 *
	 * @return integer
	 */
	public function cookieExpire(): int {
		return toInteger($this->optionPath(['cookie', 'expire'], 604800));
	}

	/**
	 * Set Session cookie
	 *
	 * @return string
	 */
	private static function _generateToken(): string {
		try {
			$rand = random_int(PHP_INT_MIN, PHP_INT_MAX);
		} catch (Throwable) {
			$rand = random_int(PHP_INT_MIN, PHP_INT_MAX);
		}
		return md5(dechex($rand) . microtime());
	}

	/**
	 * Authenticate user
	 *
	 * @see Interface_Session::authenticate()
	 * @param Interface_UserLike $user
	 * @param Request $request
	 * @param Response $response
	 * @return void
	 * @throws Exception_Authentication
	 */
	public function authenticate(Interface_UserLike $user, Request $request, Response $response): void {
		try {
			$cookieExpire = $this->cookieExpire();
			$this->setMember('user', ORMBase::mixedToID($user));
			$this->setMember('ip', $request->remoteIP());
			$this->setMember('expires', Timestamp::now()->addUnit($cookieExpire));
			$this->store();
		} catch (Throwable $t) {
			throw new Exception_Authentication('Failed to store session {exceptionClass} {message}', zeskException::exceptionVariables($t), 0, $t);
		}
	}

	/**
	 * Are we authenticated?
	 *
	 * @see Interface_Session::isAuthenticated()
	 * @return bool
	 */
	public function isAuthenticated(): bool {
		return !$this->memberIsEmpty('user');
	}

	/**
	 * De-authenticate
	 *
	 * @return void
	 * @throws Database_Exception_Duplicate
	 * @throws Database_Exception_SQL
	 * @throws Database_Exception_Table_NotFound
	 * @throws Exception_Key
	 * @throws Exception_ORMDuplicate
	 * @throws Exception_ORMEmpty
	 * @throws Exception_Store
	 * @see Interface_Session::relinquish()
	 */
	public function relinquish(): void {
		try {
			$this->user()->callHook('logout');
		} catch (Exception_Authentication) {
		}
		$this->setMember('user', null);
		$this->store();
	}

	/**
	 * Has this session expired?
	 *
	 * @return Timestamp
	 * @throws Exception_Convert
	 * @throws Exception_Key
	 * @throws Exception_ORMEmpty
	 * @throws Exception_Parameter
	 */
	public function expires(): Timestamp {
		return $this->memberTimestamp(self::MEMBER_EXPIRES);
	}

	/**
	 * Logout expired, run hook
	 */
	private function logoutExpire(): void {
		try {
			$user = $this->user();
			$user->callHook('logoutExpire');
		} catch (Exception_Key|Exception_Semantics|Exception_ORMEmpty|Exception_ORMNotFound $e) {
			// User deleted
			$this->application->logger->error($e);
		}
	}

	/**
	 * Run once a minute
	 */
	public static function cron_cluster_minute(Application $application): void {
		$where = [self::MEMBER_EXPIRES . '|<' => Timestamp::now()];
		$iter = $application->ormRegistry(__CLASS__)->querySelect()->appendWhere($where)->ormIterator();
		foreach ($iter as $session) {
			/* @var $session SessionORM */
			$session->logoutExpire();
		}

		try {
			$application->ormRegistry(__CLASS__)->queryDelete()->appendWhere($where)->execute();
		} catch (Database_Exception_Table_NotFound|Database_Exception_Duplicate|Database_Exception_SQL|Database_Exception|Exception_Semantics $e) {
			$application->logger->error('{method} {message}', $e->variables() + ['method' => __METHOD__]);
		}
	}

	/**
	 *
	 * @return Timestamp
	 * @throws Exception_Key
	 * @throws Exception_Semantics
	 */
	private function computeExpires(): Timestamp {
		$expire = $this->cookieExpire();
		return Timestamp::now()->addUnit($expire);
	}

	/**
	 *
	 * @return string
	 */
	private function cookieName(): string {
		return $this->optionPath(['cookie', 'name'], 'ZCOOKIE');
	}

	/**
	 * @return $this
	 */
	public function foundSession(): self {
		return $this;
	}

	/**
	 *
	 *
	 * @return $this
	 * @throws Exception_ORMNotFound
	 * @see Interface_Session::initializeSession()
	 */
	public function fetchSession(string $token, string $type): self {
		// Very important: Do not use $this->FOO to set variables; it sets the data instead.
		try {
			if ($token && ($session = $this->fetch([
				self::MEMBER_TOKEN => $token, self::MEMBER_TYPE => $type,
			]))) {
				return $session->foundSession();
			}
		} catch (Throwable) {
		}

		throw new Exception_ORMNotFound(self::class);
	}

	/**
	 * @param Request $request
	 * @return $this
	 */
	public function initializeSession(Request $request): self {
		$methods = [
			self::METHOD_COOKIE => $this->initializeCookieSession(...),
			self::METHOD_AUTHORIZATION => $this->initializeAuthorizationSession(...),
		];
		$method = $methods[$this->option(self::OPTION_METHOD)] ?? null;
		if ($method) {
			return $method($request);
		}
		$this->application->logger->warning('{class}::{option} is not set to one of {methods} - no session will load', [
			'methods' => array_keys($methods), 'class' => self::class, 'option' => self::OPTION_METHOD,
		]);
		return $this;
	}

	public function newSession(Request $request, string $type): self {
		$this->setMember(self::MEMBER_IP, $request->ip());
		$this->setMember(self::MEMBER_TOKEN, $this->_generateToken());
		$this->setMember(self::MEMBER_TYPE, $type);
		$this->setMember(self::MEMBER_EXPIRES, $this->computeExpires());
		return $this;
	}

	protected function initializeCookieSession(Request $request): self {
		$type = self::TYPE_COOKIE;
		$cookie_name = $this->cookieName();

		try {
			$cookie_value = $request->cookie($cookie_name);
			return $this->fetchSession($cookie_value, $type);
		} catch (Exception_ORMNotFound|Exception_Key) {
		}
		$this->newSession($request, $type);

		$cookie_options = $this->cookieOptions();
		$cookie_value = $this->member(self::MEMBER_TOKEN);
		$session = $this;
		$this->application->hooks->add(Response::class . '::headers', function (Response $response) use (
			$cookie_name,
			$cookie_value,
			$cookie_options,
			$session
		): void {
			$response->setCookie($cookie_name, $cookie_value, $cookie_options);
			$session->store();
		});
		return $session;
	}

	/**
	 * Loads, never saves.
	 *
	 * @param Request $request
	 * @return $this
	 */
	protected function initializeAuthorizationSession(Request $request): self {
		$type = self::TYPE_AUTHORIZATION_KEY;

		try {
			$token = $request->header(HTTP::REQUEST_AUTHORIZATION);
			return $this->fetchSession($token, $type);
		} catch (Exception_ORMNotFound|Exception_Key) {
		}
		$this->setMember(self::MEMBER_IP, $request->ip());
		return $this;
	}

	/**
	 * @param Request $request
	 * @return $this
	 * @throws Database_Exception_Duplicate
	 * @throws Database_Exception_SQL
	 * @throws Database_Exception_Table_NotFound
	 * @throws Exception_Key
	 * @throws Exception_ORMDuplicate
	 * @throws Exception_ORMEmpty
	 * @throws Exception_Store
	 */
	public function createAuthorizationSession(Request $request): self {
		return $this->newSession($request, self::TYPE_AUTHORIZATION_KEY)->store();
	}

	/**
	 *
	 * @return string
	 * @throws Exception_Key
	 * @throws Exception_ORMEmpty
	 */
	public function hash(): string {
		return $this->member(self::MEMBER_TOKEN);
	}

	/**
	 * @return string
	 * @throws Exception_Key
	 * @throws Exception_ORMEmpty
	 */
	public function token(): string {
		return $this->member(self::MEMBER_TOKEN);
	}

	/**
	 *
	 * @param User $user
	 * @param int $expire_seconds Expiration time in seconds, inherits from
	 *    'zesk\SessionORM::one_time_expire_seconds' if not set. Defaults to 1 day (86400 seconds).
	 *
	 * @return SessionORM
	 * @throws Database_Exception
	 * @throws Database_Exception_Duplicate
	 * @throws Database_Exception_SQL
	 * @throws Database_Exception_Table_NotFound
	 * @throws Exception_Key
	 * @throws Exception_ORMDuplicate
	 * @throws Exception_ORMEmpty
	 * @throws Exception_Semantics
	 * @throws Exception_Store
	 */
	public static function oneTimeCreate(User $user, string $ip, int $expire_seconds = -1): self {
		$app = $user->application;
		if ($expire_seconds < 0) {
			$expire_seconds = toInteger($app->configuration->getPath([
				__CLASS__, 'one_time_expire_seconds',
			], 86400));
		}
		// Only one allowed at any time, I guess.
		$app->ormRegistry(__CLASS__)->queryDelete()->appendWhere([
			self::MEMBER_TYPE => self::TYPE_ONE_TIME, self::MEMBER_USER => $user,
		])->execute();
		$session = $app->ormFactory(__CLASS__);
		assert($session instanceof self);
		$session->setMembers([
			self::MEMBER_TOKEN => self::_generateToken(), self::MEMBER_TYPE => self::TYPE_ONE_TIME,
			self::MEMBER_EXPIRES => Timestamp::now()->addUnit($expire_seconds), self::MEMBER_USER => $user, $ip => $ip,
		]);
		$session->store();
		return $session;
	}

	/**
	 * Given a hash, find the one-time Session
	 *
	 * @param Application $application
	 * @param string $hash
	 * @return self
	 * @throws Exception_ORMNotFound
	 */
	public static function oneTimeFind(Application $application, string $hash): self {
		$hash = trim($hash);
		$onetime = $application->ormFactory(__CLASS__);
		assert($onetime instanceof self);

		try {
			return $onetime->find([
				self::MEMBER_TOKEN => $hash, self::MEMBER_TYPE => self::TYPE_ONE_TIME,
			]);
		} catch (Throwable $t) {
			throw new Exception_ORMNotFound(__CLASS__, 'No session with hash {hash}', ['hash' => $hash], $t);
		}
	}

	/**
	 *
	 * @param int $user_id
	 * @param string $ip
	 * @return $this
	 * @throws Database_Exception_Duplicate
	 * @throws Database_Exception_SQL
	 * @throws Database_Exception_Table_NotFound
	 * @throws Exception_Authentication
	 * @throws Exception_Key
	 * @throws Exception_ORMDuplicate
	 * @throws Exception_ORMEmpty
	 * @throws Exception_Semantics
	 * @throws Exception_Store
	 */
	public function oneTimeAuthenticate(int $user_id, string $ip = ''): self {
		if (!$this->is_one_time) {
			throw new Exception_Semantics('Not a one-time session');
		}
		$this->setMember(self::MEMBER_TOKEN, $this->_generateToken());
		$this->setMember(self::MEMBER_TYPE, self::TYPE_COOKIE);
		$this->authenticate($user_id, $ip);
		return $this;
	}

	/**
	 * Count all other sessions seen within the seconds window provided
	 *
	 * @param int $nSeconds
	 * @return integer
	 * @throws Database_Exception_SQL
	 * @throws Exception_Key
	 * @throws Exception_ORMEmpty
	 * @throws Exception_Semantics
	 */
	public function sessionCount(int $nSeconds = 600): int {
		$where['seen|>='] = Timestamp::now()->addUnit(-$nSeconds);
		$where['id|!='] = $this->id();
		return $this->querySelect()->addWhat('*X', 'COUNT(id)')->appendWhere($where)->integer('X');
	}

	/*
	 * Get/Set session valuesfrom Object
	 *
	 */
	protected function hook_initialized(): void {
		if (!is_array($this->members['data'])) {
			$this->members['data'] = [];
		}
		$this->original = $this->members['data'];
	}

	/**
	 *
	 * @return SessionORM
	 * @throws Database_Exception_Duplicate
	 * @throws Database_Exception_SQL
	 * @throws Database_Exception_Table_NotFound
	 * @throws Exception_Key
	 * @throws Exception_ORMDuplicate
	 * @throws Exception_ORMEmpty
	 * @throws Exception_Store
	 */
	public function store(): self {
		if ($this->memberIsEmpty('cookie')) {
			$this->delete();
			return $this;
		} else {
			return parent::store();
		}
	}

	/**
	 * @return int
	 * @throws Exception_Key
	 * @throws Exception_ORMEmpty
	 * @throws Exception_ORMNotFound
	 * @throws Exception_Semantics
	 */
	public function userId(): int {
		return $this->user()->id();
	}

	/**
	 * @return User
	 * @throws Exception_Authentication
	 */
	public function user(): User {
		try {
			$user = $this->memberObject(self::MEMBER_USER, $this->inheritOptions());
			assert($user instanceof User);
			return $user;
		} catch (Throwable $t) {
			throw new Exception_Authentication('Session user {message}', ['message' => $t->getMessage()], 0, $t);
		}
	}

	public function get(int|string $name, mixed $default = null): mixed {
		return $this->members['data'][$name] ?? $default;
	}

	/**
	 * Session variables are special
	 *
	 * @see ORMBase::__get($member)
	 */
	public function __get(int|string $key): mixed {
		return $this->members['data'][$key] ?? null;
	}

	/**
	 * (non-PHPdoc)
	 *
	 * @see ORMBase::__set($member, $value)
	 */
	public function __set(int|string $key, mixed $value): void {
		if ($value === null) {
			unset($this->members['data'][$key]);
		} else {
			$this->members['data'][$key] = $value;
		}
		if ($value !== ($this->original[$key] ?? null)) {
			$this->changed = true;
			$this->store();
		}
	}

	public function set(int|string $name, mixed $value = null): self {
		$this->__set($name, $value);
		return $this;
	}

	public function changed($members = null): bool {
		return $this->changed;
	}

	/**
	 * Rerieve all of the variables
	 *
	 * @see Interface_Session::variables()
	 */
	public function variables(): array {
		return $this->members['data'];
	}

	/**
	 *
	 * @return array
	 */
	public function cookieOptions(): array {
		return $this->optionArray('cookie');
	}
}
