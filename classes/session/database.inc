<?php
/**
 * Session object is a more powerful, multi-server, database session storage.
 * Dates and times are stored using UTC.
 * $URL: https://code.marketacumen.com/zesk/trunk/classes/session/database.inc $
 * @package zesk
 * @subpackage session
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2011, Market Acumen, Inc.
 */

/**
 * @see Class_Session_Database
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
class Session_Database extends Object implements Session_Interface {
	/**
	 * Current execution does not allow sessions, do not touch database
	 *
	 * @var boolean
	 */
	protected static $nosession = false;

	/**
	 * Original session data (to see if things change)
	 *
	 * @var array
	 */
	private $original;

	/**
	 * Something changed?
	 *
	 * @var boolean
	 */
	private $changed = false;

	/**
	 * Implements Object::settings
	 *
	 * @return multitype:string
	 */
	public static function settings() {
		return array(
			"Session::cookie_name" => "string The cookie name set in the browser for this session object.",
			"Session::cookie_expire" => "integer The number of seconds after which the session expires.",
			"Session::domain_cookie" => "string The domain name used for this cookie session in the browser.",
			"Session::cookie_secure" => "boolean Whether the session cookie should only be sent for secure sessions. Use with caution!",
			""
		);
	}
	function really_store() {
		if ($this->member_is_empty("cookie")) {
			$this->delete();
		} else {
			return parent::store();
		}
	}
	function initialize($value, $from_database = false) {
		$result = parent::initialize($value, $from_database);
		$this->changed = false;
		return $result;
	}
	function fetch($mixed = null) {
		$result = parent::fetch($mixed);
		if ($result instanceof Object) {
			$result->seen();
		}
		return $result;
	}

	function seen() {
		$query = $this->query_update();
		$sql = $query->sql();
		$query->value("*seen", $sql->now())
			->value("expires", $this->compute_expires())
			->value("*sequence_index", "sequence_index+1")
			->where("id", $this)
			->low_priority(true)
			->execute();
		$this->hook('seen');
		return $this;
	}

	public static function hooks() {
		zesk::add_hook('Response::headers', __CLASS__ . '::response_headers');
		zesk::add_hook('exit', __CLASS__ . '::save');
	}
	function store() {
		if (self::$nosession) {
			return null;
		}
		if ($this->id() !== self::master_id()) {
			$result = parent::store();
			if ($result) {
				$this->changed = false;
			}
			return $result;
		}
		if (zesk::getb("Session_Database_Save")) {
			return $this;
		}
		zesk::hooks(__CLASS__);
		return $this;
	}

	public static function global_cookie_name() {
		return zesk::get("Session::cookie_name", "ZCOOKIE");
	}
	public static function global_cookie_expire() {
		return zesk::get("Session::cookie_expire", 604800); // 1 week is default
	}

	/**
	 * Global which indicates to what unit of time we should round the cookie expiration date.
	 * This avoids having the cookie appear to have "changed" in FireBug every time a request is
	 * made to the server.
	 *
	 * @return integer Seconds by which to round the cookie expiration date
	 */
	public static function global_cookie_expire_round() {
		return zesk::get("Session::cookie_expire_round", 3600); // 1 hour is default
	}

	/**
	 * Set Session cookie
	 *
	 * @param string $cookie
	 * @return string
	 */
	private static function _generate_cookie() {
		return md5("" . mt_rand(0, 999999999) . microtime());
	}

	/**
	 * Authenticate user at IP
	 * @see Session_Interface::authenticate()
	 */
	public function authenticate($user_id, $ip = null) {
		$cookieExpire = self::global_cookie_expire();
		$this->set_member("user", $user_id);
		if ($ip === null) {
			$ip = ip::remote();
		}
		$this->set_member("ip", $ip);
		$this->set_member("expires", Timestamp::now()->add_unit("second", $cookieExpire));
		return $this->store();
	}

	/**
	 * Are we authenticated?
	 * @see Session_Interface::authenticated()
	 */
	public function authenticated() {
		return $this->member_is_empty('User');
	}

	/**
	 * De-authenticate
	 * @see Session_Interface::deauthenticate()
	 */
	public function deauthenticate() {
		if ($this->user()) {
			$this->user()->hook("logout");
		}
		$this->set_member("user", null);
		return $this->store();
	}

	/**
	 * Is this session expired?
	 * @return
	 */
	public function expires() {
		return $this->member_timestamp("expires");
	}

	private function logout_expire() {
		try {
			$user = $this->user();
			if ($user) {
				$user->hook("logout_expire");
			}
		} catch (Exception_Object_NotFound $e) {
			// User deleted
		}
	}
	/**
	 * Run once a minute
	 */
	public static function cron_cluster_minute() {
		$now = Timestamp::now();
		$where['expires|<'] = $now;
		$iter = Object::class_query(__CLASS__)->where($where)->object_iterator();
		/* @var $session Session_Database */
		foreach ($iter as $session) {
			$session->logout_expire();
		}
		Object::class_query_delete(__CLASS__)->where($where)->execute();
	}

	private static function compute_expires() {
		$expire = self::global_cookie_expire();
		$expires = Timestamp::now()->add_unit("second", $expire);
		return $expires;
	}
	private function _initialize_instance() {
		if (!array_key_exists('HTTP_USER_AGENT', $_SERVER)) {
			self::$nosession = true;
			return false;
		}

		$name = self::global_cookie_name();
		$value = avalue($_COOKIE, $name);

		$expires = self::compute_expires();
		try {
			if (!$value) {
				$cookie = $this->_generate_cookie();
			} else if ($this->set_member('cookie', $value)->find()) {
				$now = Timestamp::now();
				$cookie = $value;
				if ($this->expires()->after($now)) {
					$this->seen();
					return;
				}
				$this->logout_expire();
				$this->delete();
			} else {
				$cookie = $this->_generate_cookie();
			}
		} catch (Database_Exception_Connect $e) {
			zesk::hook("exception", $e);
			self::$nosession = true;
			return false;
		}
		$this->set_member('cookie', $cookie);
		$this->set_member('expires', $expires);
		$this->set_member('ip', IPv4::remote());
		$this->data = to_array($this->data) + array(
			'uri' => Request::instance()->uri()
		);
		try {
			$this->store();
		} catch (Database_Exception_Table_NotFound $e) {
			// During bootstrapping, this may happen, I think. Not sure.
		}
		$_COOKIE[$name] = $cookie;
	}
	public static function enabled($set = null) {
		return $set === null ? zesk::get("session_enabled", true) : zesk::set("session_enabled", to_bool($set));
	}

	public static function instance($create = true) {
		if (!$create) {
			$cookieName = self::global_cookie_name();
			$cookieValue = avalue($_COOKIE, $cookieName);
			if (!$cookieValue) {
				return null;
			}
		}
		$session = zesk::get("Session_Database::instance", null);
		if ($session instanceof Session_Database) {
			return $session;
		}
		zesk::hooks(__CLASS__);
		$session = new Session_Database();
		zesk::set("Session_Database::instance", $session);
		if (!self::enabled()) {
			return $session;
		}
		$session->_initialize_instance();
		return $session;
	}

	// public function id()
	// {
	// return $this->member('Cookie');
	// }
	public static function master_id() {
		$session = self::instance(false);
		if (!$session)
			return null;
		return $session->id();
	}
	public function set_master() {
		zesk::set("Session_Database", $this);
		$this->set_cookie($this->hash());
	}
	function hash() {
		return $this->member("cookie");
	}
	function toString() {
		return $this->id();
	}
	public static function one_time_create($user, $expire_seconds = null) {
		Object::class_query_delete('Session_Database')->where('is_one_time', true)->where('user', $user)->execute();
		$session = new Session_Database();
		$session->set_member(array(
			'cookie' => self::_generate_cookie(),
			'is_one_time' => true,
			'expires' => Timestamp::now()->add_unit('second', $expire_seconds === null ? zesk::get('Session::one_time_expire_seconds', 864000) : $expire_seconds),
			'user' => $user
		));
		$session->really_store();
		return $session;
	}

	public static function one_time_find($hash) {
		$hash = trim($hash);
		$onetime = new Session_Database();
		if ($onetime->find(array(
			"cookie" => $hash,
			"is_one_time" => true
		))) {
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
		$this->really_store();
		$this->set_master();
		return $this;
	}

	public static function session_count($nSeconds = 600) {
		$session = Session_Database::master();
		$where['seen|>='] = Timestamp::now()->add_unit("minute", -$nSeconds);
		$where['id|!='] = $session->id();
		return Object::class_query(__CLASS__)->what("*X", "COUNT(id)")->where($where)->one_integer("X");
	}

	/*
	 * Get/Set session valuesfrom Object
	 *
	 */
	protected function hook_initialized() {
		if (!is_array($this->members['data'])) {
			$this->members['data'] = array();
		}
		$this->original = $this->members['data'];
	}

	public function user_id() {
		return $this->member_integer("user");
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
	 * @see Object::__get($member)
	 */
	public function __get($name) {
		return avalue($this->members['data'], $name);
	}

	/**
	 * (non-PHPdoc)
	 * @see Object::__set($member, $value)
	 */
	public function __set($name, $value) {
		if ($value === null) {
			unset($this->members['data'][$name]);
		} else {
			$this->members['data'][$name] = $value;
		}
		if ($value !== avalue($this->original, $name)) {
			$this->changed = true;
			$this->store();
		}
	}

	public function set($name, $value = null) {
		$this->__set($name, $value);
	}

	public function changed($members = null) {
		return $this->changed;
	}

	/**
	 * Retrieve some of the values
	 *
	 * @see Session_Interface::filter()
	 */
	public function filter($list = null) {
		if ($list === null) {
			return $this->members['data'];
		}
		return arr::filter($this->members['data'], $list);
	}
	/**
	 * Rerieve all of the variables
	 * @see Session_Interface::variables()
	 */
	public function variables() {
		return $this->members['data'];
	}
	/**
	 * Delete the session
	 *
	 * @see Object::delete()
	 */
	public function delete() {
		$master = self::instance();
		$master_id = $master ? $master->id() : null;
		if ($master_id === $this->id()) {
			$master->set_member('cookie', null);
		}
		parent::delete();
	}

	/**
	 * @see instance
	 * @deprecated 2014-01
	 * @param string $create
	 * @return Ambigous <NULL, Session_Database, unknown>
	 */
	public static function master($create = true) {
		return self::instance($create);
	}

	/**
	 * zesk::hook('exit')
	 *
	 * Really save
	 */
	public static function save() {
		try {
			$session = Session_Database::instance();
			$session->really_store();
		} catch (Database_Exception_Connect $e) {

		} catch (Database_Exception_Table_NotFound $e) {

		}
	}

	/**
	 * zesk::hook('headers')
	 *
	 * @param Request $request
	 * @param Response $response
	 * @return void
	 */
	static public function response_headers(Response $response) {
		$session = self::instance();
		$cookie = $session->member('cookie');
		$cookieName = self::global_cookie_name();
		$round = self::global_cookie_expire_round();
		$cookieExpire = self::global_cookie_expire();
		$tld = zesk::get("Session::domain_cookie", true);
		if ($tld === true) {
			$tld = http::tld(url::current_host());
		} else if ($tld === false) {
			$tld = url::current_host();
		}
		$expire_time = time() + $cookieExpire;
		$expire_time = intval(round($expire_time / $round) * $round);
		if (!zesk::$console) {
			setcookie($cookieName, null);
			if (!empty($cookie)) {
				setcookie($cookieName, $cookie, $expire_time, "/", $tld, zesk::getb("Session::cookie_secure", false));
			}
		}
		return $cookie;
	}

}

