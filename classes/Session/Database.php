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
namespace zesk;

/**
 * Sessions inherit some options from the global Application object in the initialize() function
 * 
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
	 * @deprecated 2016-12
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
	 * 
	 * @return Object
	 */
	function really_store() {
		if ($this->member_is_empty("cookie")) {
			$this->delete();
		} else {
			return parent::store();
		}
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see Object::initialize()
	 */
	function initialize($value, $from_database = false) {
		$result = parent::initialize($value, $from_database);
		$this->changed = false;
		$this->set_option($this->application->option_array("session"));
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
	public static function hooks(Kernel $zesk) {
		//		$zesk->hooks->add('zesk\Response::headers', __CLASS__ . '::response_headers');
		$zesk->hooks->add(Hooks::hook_configured, __CLASS__ . '::configured');
		$zesk->hooks->add('exit', __CLASS__ . '::save');
	}
	public static function configured(Application $application) {
		// 2017-01-01
		foreach (array(
			"Session",
			"zesk\\Session"
		) as $class) {
			$application->configuration->deprecated(array(
				$class,
				"cookie_name"
			), array(
				"zesk\Application",
				"session",
				"cookie",
				"name"
			));
			$application->configuration->deprecated(array(
				$class,
				"cookie_expire"
			), array(
				"zesk\Application",
				"session",
				"cookie",
				"expire"
			));
		}
		$application->configuration->deprecated("Session::cookie_expire_round");
		$application->configuration->deprecated("zesk\\Session::cookie_expire_round");
		$application->configuration->deprecated("zesk\Application::session::cookie::expire_round");
	}
	function store() {
		if (self::$nosession) {
			return null;
		}
		$session = $this->application->session(false);
		if (!$session instanceof self || $this->id() !== $session->id()) {
			$result = parent::store();
			if ($result) {
				$this->changed = false;
			}
			return $result;
		}
		return $this;
	}
	public function cookie_expire() {
		return to_integer($this->option_path("cookie.expire"), 604800);
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
	 * @see Interface_Session::authenticate()
	 */
	public function authenticate($user_id, $ip = null) {
		$cookieExpire = $this->cookie_expire();
		$this->set_member("user", $user_id);
		if ($ip === null) {
			$ip = $this->application->request()->ip();
		}
		$this->set_member("ip", $ip);
		$this->set_member("expires", Timestamp::now()->add_unit("second", $cookieExpire));
		return $this->store();
	}
	
	/**
	 * Are we authenticated?
	 * @see Interface_Session::authenticated()
	 */
	public function authenticated() {
		return $this->member_is_empty('User');
	}
	
	/**
	 * De-authenticate
	 * @see Interface_Session::deauthenticate()
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
	public static function cron_cluster_minute(Application $application) {
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
	 * @return Timestamp
	 */
	private function compute_expires() {
		$expire = $this->cookie_expire();
		$expires = Timestamp::now()->add_unit("second", $expire);
		return $expires;
	}
	
	/**
	 * Set sessions enabled or not (default are enabled)
	 * 
	 * @param boolean $set Optional
	 * @return boolean
	 */
	public static function enabled($set = null) {
		global $zesk;
		/* @var $zesk Kernel */
		if ($set === null) {
			return !$zesk->configuration->session->disabled;
		}
		$set = !to_bool($set);
		$zesk->configuration->session->disabled = $set;
		return $set;
	}
	private function cookie_name() {
		return $this->option_path("cookie.name", "ZCOOKIE");
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \zesk\Interface_Session::initialize_session()
	 */
	public function initialize_session(Request $request) {
		// Very important: Do not use $this->FOO to set variables; it sets the data instead.
		$application = $this->application;
		$cookie_name = $this->cookie_name();
		$cookie_value = $request->cookie($cookie_name);
		if ($cookie_value && $this->fetch_by_key($cookie_value, "cookie")) {
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
		$this->set_member('data', to_array($this->data) + array(
			'uri' => $application->request()->uri()
		));
		
		$application->response()->cookie($cookie_name, $cookie_value, $this->cookie_options());
		
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
			/* @var $zesk Kernel */
			$expire_seconds = to_integer($zesk->configuration->Session->one_time_expire_seconds, 86400);
		}
		$app = app();
		
		$app->query_delete(__CLASS__)
			->where('is_one_time', true)
			->where('user', $user)
			->execute();
		$session = $app->object_factory(__CLASS__);
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
		$onetime = app()->object_factory(__CLASS__);
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
	
	/**
	 * Count all other sessions seen within the seconds window provided
	 * 
	 * @param number $nSeconds
	 * @return integer
	 */
	public function session_count($nSeconds = 600) {
		$where['seen|>='] = Timestamp::now()->add_unit("minute", -$nSeconds);
		$where['id|!='] = $this->id();
		return $this->query_select()
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
	 * @see Interface_Session::filter()
	 */
	public function filter($list = null) {
		if ($list === null) {
			return $this->members['data'];
		}
		return arr::filter($this->members['data'], $list);
	}
	/**
	 * Rerieve all of the variables
	 * @see Interface_Session::variables()
	 */
	public function variables() {
		return $this->members['data'];
	}
	/**
	 * Delete the session
	 *
	 * @see Object::delete()
	 */
	// 	public function delete() {
	// 		$master = self::instance();
	// 		$master_id = $master ? $master->id() : null;
	// 		if ($master_id === $this->id()) {
	// 			$master->set_member('cookie', null);
	// 		}
	// 		parent::delete();
	// 	}
	public function found_session() {
		return $this;
	}
	
	/**
	 * Really save
	 */
	public static function save() {
		try {
			$session = app()->session(false);
			if ($session instanceof self) {
				$session->really_store();
			}
		} catch (Database_Exception_Connect $e) {
		} catch (Database_Exception_Table_NotFound $e) {
		}
	}
	
	/**
	 *
	 * @return array
	 */
	public function cookie_options() {
		return $this->option_array("cookie");
	}
}

