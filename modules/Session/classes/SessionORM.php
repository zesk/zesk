<?php
declare(strict_types=1);

/**
 * Session object is a more powerful, multi-server, database session storage.
 * Dates and times are stored using UTC.
 * @package zesk
 * @subpackage session
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2022, Market Acumen, Inc.
 */

namespace zesk\Session;

use Exception;
use \zesk\Exception as zeskException;
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
use zesk\Exception_Deprecated;
use zesk\Exception_Key;
use zesk\Exception_NotFound;
use zesk\Exception_Parameter;
use zesk\Exception_Parse;
use zesk\Interface_UserLike;
use zesk\ORM\Exception_ORMDuplicate;
use zesk\ORM\Exception_ORMEmpty;
use zesk\ORM\Exception_ORMNotFound;
use zesk\Exception_Semantics;
use zesk\Hooks;
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
		$query->value('*seen', $sql->now())->value('expires', $this->computeExpires())->value('*sequence_index', 'sequence_index+1')->addWhere('id', $this)->setLowPriority(true)->execute();
		$this->callHook('seen');
		return $this;
	}

	/**
	 * Register hooks
	 * @param Application $application
	 */
	public static function hooks(Application $application): void {
		$application->hooks->add(Hooks::HOOK_CONFIGURED, self::configured(...));
	}

	/**
	 *
	 * @param Application $application
	 * @throws Exception_Deprecated
	 * @throws Exception_Semantics
	 */
	public static function configured(Application $application): void {
		// 2017-01-01
		foreach ([
			'Session', 'zesk\\Session',
		] as $class) {
			$application->configuration->deprecated([
				$class, 'cookie_name',
			], [
				"zesk\Application", 'session', 'cookie', 'name',
			]);
			$application->configuration->deprecated([
				$class, 'cookie_expire',
			], [
				"zesk\Application", 'session', 'cookie', 'expire',
			]);
		}
		$application->configuration->deprecated('Session::cookie_expire_round');
		$application->configuration->deprecated('zesk\\Session::cookie_expire_round');
		$application->configuration->deprecated("zesk\Application::session::cookie::expire_round");
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
	private static function _generateCookie(): string {
		try {
			$rand = random_int(PHP_INT_MIN, PHP_INT_MAX);
		} catch (Exception) {
			$rand = random_int(PHP_INT_MIN, PHP_INT_MAX);
		}
		return md5(dechex($rand) . microtime());
	}

	/**
	 * Authenticate user at IP
	 *
	 * @param int|Interface_UserLike $user
	 * @param string $ip
	 * @return void
	 * @throws Exception_Authentication
	 * @see Interface_Session::authenticate()
	 */
	public function authenticate(int|Interface_UserLike $user, string $ip = ''): void {
		try {
			$cookieExpire = $this->cookieExpire();
			$this->setMember('user', ORMBase::mixedToID($user));
			$this->setMember('ip', $ip);
			$this->setMember('expires', Timestamp::now()->addUnit($cookieExpire));
			$this->store();
		} catch (Throwable $t) {
			throw new Exception_Authentication('Failed to store session {exceptionClass} {message}', zeskException::exceptionVariables($t), 0, $t);
		}
	}

	/**
	 * Are we authenticated?
	 *
	 * @see Interface_Session::authenticated()
	 */
	public function authenticated(): bool {
		return !$this->memberIsEmpty('user');
	}

	/**
	 * De-authenticate
	 *
	 * @return void
	 * @throws Exception_Key
	 * @throws Exception_ORMEmpty
	 * @throws Exception_ORMNotFound
	 * @throws Exception_Semantics
	 * @see Interface_Session::relinquish()
	 */
	public function relinquish(): void {
		$this->user()->callHook('logout');
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
		return $this->memberTimestamp('expires');
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
		$now = Timestamp::now();
		$where['expires|<'] = $now;
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
	 *
	 *
	 * @param Request $request
	 * @return $this
	 * @throws Database_Exception_Duplicate
	 * @throws Database_Exception_SQL
	 * @throws Database_Exception_Table_NotFound
	 * @throws Exception_Class_NotFound
	 * @throws Exception_Configuration
	 * @throws Exception_Convert
	 * @throws Exception_Key
	 * @throws Exception_NotFound
	 * @throws Exception_ORMEmpty
	 * @throws Exception_ORMNotFound
	 * @throws Exception_Parse
	 * @throws Exception_Semantics
	 * @see Interface_Session::initializeSession()
	 */
	public function initializeSession(Request $request): self {
		// Very important: Do not use $this->FOO to set variables; it sets the data instead.
		$application = $this->application;
		$cookie_name = $this->cookieName();

		try {
			$cookie_value = $request->cookie($cookie_name);
		} catch (Exception_Key) {
			$cookie_value = '';
		}
		if ($cookie_value && $this->fetchByKey($cookie_value, 'cookie')) {
			$this->seen();
			return $this->found_session();
		}
		if (!$request->isBrowser()) {
			return $this;
		}
		$cookie_value = $this->_generateCookie();
		$expires = $this->computeExpires();
		$this->setMember('cookie', $cookie_value);
		$this->setMember('expires', $expires);
		$this->setMember('ip', $request->ip());
		$this->setMember('data', toArray($this->data) + [
			'uri' => $request->uri(),
		]);
		$cookie_options = $this->cookie_options();
		$application->hooks->add(Response::class . '::headers', function (Response $response) use ($cookie_name, $cookie_value, $cookie_options): void {
			$response->setCookie($cookie_name, $cookie_value, $cookie_options);
		});
		return $this->store();
	}

	/**
	 *
	 * @return string
	 * @throws Exception_Key
	 * @throws Exception_ORMEmpty
	 */
	public function hash(): string {
		return $this->member('cookie');
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
	 * @throws Exception_Semantics
	 */
	public static function oneTimeCreate(User $user, int $expire_seconds = -1): self {
		$app = $user->application;
		if ($expire_seconds < 0) {
			$expire_seconds = toInteger($app->configuration->getPath([
				__CLASS__, 'one_time_expire_seconds',
			], 86400));
		}
		// Only one allowed at any time, I guess.
		$app->ormRegistry(__CLASS__)->queryDelete()->addWhere('is_one_time', true)->addWhere('user', $user)->execute();
		$session = $app->ormFactory(__CLASS__);
		assert($session instanceof self);

		try {
			$ip = $user->application->request()->ip();
		} catch (Exception_Semantics) {
			$ip = null;
		}
		$session->setMembers([
			'cookie' => self::_generateCookie(), 'is_one_time' => true,
			'expires' => Timestamp::now()->addUnit($expire_seconds), 'user' => $user, 'ip' => $ip,
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
				'cookie' => $hash, 'is_one_time' => true,
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
	 * @throws Exception_Convert
	 * @throws Exception_Key
	 * @throws Exception_Semantics
	 */
	public function oneTimeAuthenticate(int $user_id, string $ip = ''): self {
		if (!$this->is_one_time) {
			throw new Exception_Semantics('Not a one-time session');
		}
		$this->cookie = $this->_generateCookie();
		$this->is_one_time = false;
		$this->authenticate($user_id, $ip);
		$this->store();
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
			$user = $this->memberObject('user', $this->inheritOptions());
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
	public function __get(int|string $name): mixed {
		return $this->members['data'][$name] ?? null;
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
	 * @return self
	 */
	public function found_session() {
		return $this;
	}

	/**
	 *
	 * @return array
	 */
	public function cookie_options() {
		return $this->optionArray('cookie');
	}
}
