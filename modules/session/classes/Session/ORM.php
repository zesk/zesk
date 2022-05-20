<?php
declare(strict_types=1);

/**
 * Session object is a more powerful, multi-server, database session storage.
 * Dates and times are stored using UTC.
 * @package zesk
 * @subpackage session
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2011, Market Acumen, Inc.
 */

namespace zesk;

/**
 * Sessions inherit some options from the global Application object in the initialize() function
 *
 * @see Class_Session_ORM
 * @property id $id
 * @property string $cookie
 * @property boolean $is_one_time
 * @property User $user
 * @property ip4 $ip
 * @property Timestamp $created
 * @property Timestamp $modified
 * @property datetime $expires
 * @property datetime $seen
 * @property integer $sequence_index
 * @property array $data
 * @author kent
 */
class Session_ORM extends ORM implements Interface_Session {
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
	 * @see ORM::initialize()
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
	 * @throws Exception_Deprecated
	 * @throws Exception_ORM_Empty
	 * @throws Exception_ORM_NotFound
	 */
	public function fetch(array $mixed = []): self {
		$result = parent::fetch($mixed);
		if ($result instanceof self) {
			$result->seen();
		}
		return $result;
	}

	/**
	 * @return $this
	 * @throws Exception_Semantics
	 */
	public function seen() {
		$query = $this->query_update();
		$sql = $query->sql();
		$query->value('*seen', $sql->now())->value('expires', $this->compute_expires())->value('*sequence_index', 'sequence_index+1')->where('id', $this)->low_priority(true)->execute();
		$this->call_hook('seen');
		return $this;
	}

	/**
	 * Register hooks
	 * @param Application $application
	 */
	public static function hooks(Application $application): void {
		$application->hooks->add(Hooks::HOOK_CONFIGURED, \Closure::fromCallable(__CLASS__ . '::configured'));
	}

	/**
	 *
	 * @param Application $application
	 */
	public static function configured(Application $application): void {
		// 2017-01-01
		foreach ([
					 'Session',
					 'zesk\\Session',
				 ] as $class) {
			$application->configuration->deprecated([
				$class,
				'cookie_name',
			], [
				"zesk\Application",
				'session',
				'cookie',
				'name',
			]);
			$application->configuration->deprecated([
				$class,
				'cookie_expire',
			], [
				"zesk\Application",
				'session',
				'cookie',
				'expire',
			]);
		}
		$application->configuration->deprecated('Session::cookie_expire_round');
		$application->configuration->deprecated('zesk\\Session::cookie_expire_round');
		$application->configuration->deprecated("zesk\Application::session::cookie::expire_round");
	}

	/**
	 * Called before actual store
	 */
	public function hook_store(): void {
		$ip = $this->member('ip');
		if (!IPv4::valid($ip)) {
			$this->set_member('ip', '127.0.0.1');
		}
	}

	/**
	 *
	 * @return integer
	 */
	public function cookie_expire(): int {
		return to_integer($this->optionPath(['cookie', 'expire'], 604800));
	}

	/**
	 * Set Session cookie
	 *
	 * @param string $cookie
	 * @return string
	 */
	private static function _generate_cookie(): string {
		return md5(dechex(\random_int(PHP_INT_MIN, PHP_INT_MAX)) . microtime());
	}

	/**
	 * Authenticate user at IP
	 *
	 * @see Interface_Session::authenticate()
	 */
	public function authenticate($user_id, $ip = null): void {
		$cookieExpire = $this->cookie_expire();
		$this->set_member('user', ORM::mixed_to_id($user_id));
		if ($ip === null) {
			// This is not necessary, probably should remove TODO KMD 2018-01
			$request = $this->application->request();
			if ($request) {
				$ip = $request->ip();
			}
		}
		$this->set_member('ip', $ip);
		$this->set_member('expires', Timestamp::now()->add_unit($cookieExpire, Timestamp::UNIT_SECOND));
		$this->store();
	}

	/**
	 * Are we authenticated?
	 *
	 * @see Interface_Session::authenticated()
	 */
	public function authenticated() {
		return $this->member_is_empty('user');
	}

	/**
	 * De-authenticate
	 *
	 * @see Interface_Session::deauthenticate()
	 */
	public function deauthenticate() {
		if ($this->user()) {
			$this->user()->call_hook('logout');
		}
		$this->set_member('user', null);
		return $this->store();
	}

	/**
	 * Is this session expired?
	 *
	 * @return
	 *
	 */
	public function expires() {
		return $this->member_timestamp('expires');
	}

	/**
	 * Logout expired, run hook
	 */
	private function logout_expire(): void {
		try {
			$user = $this->user();
			if ($user) {
				$user->call_hook('logout_expire');
			}
		} catch (Exception_ORM_NotFound $e) {
			// User deleted
		}
	}

	/**
	 * Run once a minute
	 */
	public static function cron_cluster_minute(Application $application): void {
		$now = Timestamp::now();
		$where['expires|<'] = $now;
		$iter = $application->orm_registry(__CLASS__)->query_select()->where($where)->orm_iterator();
		foreach ($iter as $session) {
			/* @var $session Session_ORM */
			$session->logout_expire();
		}
		$application->orm_registry(__CLASS__)->query_delete()->where($where)->execute();
	}

	/**
	 *
	 * @return Timestamp
	 */
	private function compute_expires(): Timestamp {
		$expire = $this->cookie_expire();
		$expires = Timestamp::now()->add_unit($expire, Timestamp::UNIT_SECOND);
		return $expires;
	}

	/**
	 *
	 * @return string
	 */
	private function cookie_name(): string {
		return $this->optionPath('cookie.name', 'ZCOOKIE');
	}

	/**
	 *
	 * {@inheritdoc}
	 *
	 * @see \zesk\Interface_Session::initialize_session()
	 */
	public function initialize_session(Request $request) {
		// Very important: Do not use $this->FOO to set variables; it sets the data instead.
		$application = $this->application;
		$cookie_name = $this->cookie_name();
		$cookie_value = $request->cookie($cookie_name);
		if ($cookie_value && $this->fetch_by_key($cookie_value, 'cookie')) {
			$this->seen();
			return $this->found_session();
		}
		if (!$request->is_browser()) {
			return $this;
		}
		$cookie_value = $this->_generate_cookie();
		$expires = $this->compute_expires();
		$this->set_member('cookie', $cookie_value);
		$this->set_member('expires', $expires);
		$this->set_member('ip', $request->ip());
		$this->set_member('data', to_array($this->data) + [
				'uri' => $request->uri(),
			]);
		$cookie_options = $this->cookie_options();
		$application->hooks->add(Response::class . '::headers', function (Response $response) use ($cookie_name, $cookie_value, $cookie_options): void {
			$response->cookie($cookie_name, $cookie_value, $cookie_options);
		});
		return $this->store();
	}

	/**
	 *
	 * @return string
	 */
	public function hash(): string {
		return $this->member('cookie');
	}

	/**
	 *
	 * @param User $user
	 * @param integer $expire_seconds Expiration time in seconds, inherits from
	 *    'zesk\Session_ORM::one_time_expire_seconds' if not set. Defaults to 1 day (86400 seconds).
	 *
	 * @return Session_ORM
	 */
	public static function one_time_create(User $user, $expire_seconds = null) {
		$app = $user->application;
		if ($expire_seconds === null) {
			$expire_seconds = to_integer($app->configuration->path_get([
				__CLASS__,
				'one_time_expire_seconds',
			], 86400));
		}
		// Only one allowed at any time, I guess.
		$app->orm_registry(__CLASS__)->query_delete()->where('is_one_time', true)->where('user', $user)->execute();
		$session = $app->orm_factory(__CLASS__);
		$request = $user->application->request();
		$ip = $request ? $request->ip() : null;
		$session->set_member([
			'cookie' => self::_generate_cookie(),
			'is_one_time' => true,
			'expires' => Timestamp::now()->add_unit($expire_seconds, Timestamp::UNIT_SECOND),
			'user' => $user,
			'ip' => $ip,
		]);
		$session->store();
		return $session;
	}

	/**
	 * Given a hash, find the one-time Session
	 *
	 * @param Application $application
	 * @param unknown $hash
	 * @return \zesk\ORM|boolean
	 */
	public static function one_time_find(Application $application, $hash) {
		$hash = trim($hash);
		$onetime = $application->orm_factory(__CLASS__);
		if ($onetime->find([
			'cookie' => $hash,
			'is_one_time' => true,
		])) {
			return $onetime;
		}
		return false;
	}

	public function one_time_authenticate($user_id, $ip = null) {
		if (!$this->is_one_time) {
			return null;
		}
		$this->cookie = $this->_generate_cookie();
		$this->is_one_time = false;
		$this->authenticate($user_id, $ip);
		$this->store();
		return $this;
	}

	/**
	 * Count all other sessions seen within the seconds window provided
	 *
	 * @param number $nSeconds
	 * @return integer
	 */
	public function session_count($nSeconds = 600) {
		$where['seen|>='] = Timestamp::now()->add_unit(-$nSeconds, Timestamp::UNIT_SECOND);
		$where['id|!='] = $this->id();
		return $this->query_select()->what('*X', 'COUNT(id)')->where($where)->one_integer('X');
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
	 * @return Object
	 */
	public function store(): self {
		if ($this->member_is_empty('cookie')) {
			$this->delete();
			return $this;
		} else {
			return parent::store();
		}
	}

	public function user_id() {
		return $this->member_integer('user');
	}

	public function user() {
		return $this->member_object('user', $this->inherit_options());
	}

	public function get($name = null, $default = null) {
		if ($name === null) {
			return $this->members['data'];
		}
		return avalue($this->members['data'], $name, $default);
	}

	public function eget($name, $default = null) {
		$value = $this->get($name, null);
		if (!empty($value)) {
			return $value;
		}
		return $default;
	}

	/**
	 * Session variables are special
	 *
	 * @see ORM::__get($member)
	 */
	public function __get($name): mixed {
		return $this->members['data'][$name] ?? null;
	}

	/**
	 * (non-PHPdoc)
	 *
	 * @see ORM::__set($member, $value)
	 */
	public function __set($name, $value): void {
		if ($value === null) {
			unset($this->members['data'][$name]);
		} else {
			$this->members['data'][$name] = $value;
		}
		if ($value !== ($this->original[$name] ?? null)) {
			$this->changed = true;
			$this->store();
		}
	}

	public function set($name, $value = null): void {
		$this->__set($name, $value);
	}

	public function changed($members = null): bool {
		return $this->changed;
	}

	/**
	 * Retrieve some of the values
	 *
	 * @see Interface_Session::filter()
	 */
	public function filter($list = null) {
		if ($list === null) {
			return $this->members['data'];
		}
		return ArrayTools::filter($this->members['data'], $list);
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
		return $this->option_array('cookie');
	}
}
