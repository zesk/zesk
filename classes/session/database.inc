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
use zesk\Timestamp;
use \zesk as zesk;
use zesk\URL;
use zesk\Object;
use zesk\Domain;
use zesk\Request;
use zesk\Response;
use zesk\Database_Exception_Connect;
use zesk\Database_Exception_Table_NotFound;
use zesk\Interface_Session;

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
class Session_Database extends Object implements Interface_Session {
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
	private $original = array();
	
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
		$this->call_hook('seen');
		return $this;
	}
	public static function hooks(zesk\Kernel $zesk) {
		$zesk->configuration->pave("Session");
		$zesk->hooks->add('zesk\Response::headers', __CLASS__ . '::response_headers');
		$zesk->hooks->add('exit', __CLASS__ . '::save');
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
		$this->application->hooks->register_class(__CLASS__);
		return $this;
	}
	public static function global_cookie_name() {
		global $zesk;
		/* @var $zesk zesk\Kernel */
		return $zesk->configuration->Session->get("cookie_name", "ZCOOKIE");
	}
	public static function global_cookie_expire() {
		global $zesk;
		/* @var $zesk zesk\Kernel */
		return $zesk->configuration->Session->get("cookie_expire", 604800);
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
			$ip = IPv4::remote();
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
			$this->user()->call_hook("logout");
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
	
	/**
	 * Logout expired, run hook
	 */
	private function logout_expire() {
		try {
			$user = $this->user();
			if ($user) {
				$user->call_hook("logout_expire");
			}
		} catch (Exception_Object_NotFound $e) {
			// User deleted
		}
	}
	/**
	 * Run once a minute
	 */
	public static function cron_cluster_minute(zesk\Application $application) {
		$now = Timestamp::now();
		$where['expires|<'] = $now;
		$iter = $application->query_select(__CLASS__)->where($where)->object_iterator();
		/* @var $session Session_Database */
		foreach ($iter as $session) {
			$session->logout_expire();
		}
		$application->query_delete(__CLASS__)->where($where)->execute();
	}
	
	/**
	 * 
	 * @return \zesk\Timestamp
	 */
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
			$this->application->hooks->call("exception", $e);
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
	
	/**
	 * Set sessions enabled or not (default are enabled)
	 * 
	 * @param boolean $set Optional
	 * @return boolean
	 */
	public static function enabled($set = null) {
		global $zesk;
		/* @var $zesk zesk\Kernel */
		if ($set === null) {
			return !$zesk->configuration->session->disabled;
		}
		$set = !to_bool($set);
		$zesk->configuration->session->disabled = $set;
		return $set;
	}
	
	/**
	 * 
	 * @param string $create
	 * @return NULL|Session_Database
	 */
	public static function instance($create = true) {
		global $zesk;
		/* @var $zesk zesk\Kernel */
		if (!$create) {
			$cookieName = self::global_cookie_name();
			$cookieValue = avalue($_COOKIE, $cookieName);
			if (!$cookieValue) {
				return null;
			}
		}
		$session = $zesk->objects->session;
		if ($session instanceof Session_Database) {
			return $session;
		}
		
		$session = new Session_Database();
		$zesk->objects->session = $session;
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
	
	/**
	 * @too Should take Response to set cookie
	 * 
	 * @return Session_Database
	 */
	public function set_master() {
		global $zesk;
		/* @var $zesk zesk\Kernel */
		$zesk->objects->session = $this;
		$this->set_cookie($this->hash());
		return $this;
	}
	
	/**
	 * @return string
	 */
	function hash() {
		return $this->member("cookie");
	}
	
	/**
	 * 
	 * @param unknown $user
	 * @param unknown $expire_seconds
	 * @return Session_Database
	 */
	public static function one_time_create($user, $expire_seconds = null) {
		if ($expire_seconds === null) {
			global $zesk;
			/* @var $zesk zesk\Kernel */
			$expire_seconds = to_integer($zesk->configuration->Session->one_time_expire_seconds, 86400);
		}
		
		Object::class_query_delete('Session_Database')->where('is_one_time', true)->where('user', $user)->execute();
		$session = new Session_Database();
		$session->set_member(array(
			'cookie' => self::_generate_cookie(),
			'is_one_time' => true,
			'expires' => Timestamp::now()->add_unit('second', $expire_seconds),
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
		$session = Session_Database::instance();
		$where['seen|>='] = Timestamp::now()->add_unit("minute", -$nSeconds);
		$where['id|!='] = $session->id();
		return $session->query_select(__CLASS__)
			->what("*X", "COUNT(id)")
			->where($where)
			->one_integer("X");
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
	 *
	 * @param Request $request
	 * @param Response $response
	 * @return void
	 */
	static public function response_headers(Response $response) {
		global $zesk;
		
		/* @var $zesk zesk\Kernel */
		$session = self::instance();
		$cookie = $session->member('cookie');
		$cookieName = self::global_cookie_name();
		$round = self::global_cookie_expire_round();
		$cookieExpire = self::global_cookie_expire();
		$host = URL::current_host();
		$cookie_domain = $zesk->configuration->path_get("Session::domain_cookie", $session->option("domain_cookie", true));
		if ($cookie_domain === true) {
			$cookie_domain = Domain::domain_factory($host)->compute_cookie_domain();
		} 
		$cookie_domain = ltrim($cookie_domain, ".");
		if (!ends($host, $cookie_domain)) {
			$zesk->logger->warning("Unable to set cookie {cookie_domain} on host {host}", array(
				"cookie_domain" => $cookie_domain,
				"host" => $host
			));
		}
		
		$expire_time = time() + $cookieExpire;
		$expire_time = intval(round($expire_time / $round) * $round);
		if (!$zesk->console) {
			setcookie($cookieName, null);
			if (!empty($cookie)) {
				setcookie($cookieName, $cookie, $expire_time, "/", ".$cookie_domain", $session->option_bool("cookie_secure", false));
			}
		}
		return $cookie;
	}
}

