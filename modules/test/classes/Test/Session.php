<?php

/**
 * $URL: https://code.marketacumen.com/zesk/trunk/test/classes/session.inc $
 * @package zesk
 * @subpackage session
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2008, Market Acumen, Inc.
 */
namespace zesk;

/**
 *
 * @author kent
 *
 */
class Test_Session implements Interface_Session {
	protected $ID;
	/**
	 * Session data
	 *
	 * @var Cache
	 */
	protected $Cache;

	/**
	 *
	 * @var Application
	 */
	protected $application = null;
	/**
	 *
	 * @param Application $application
	 */
	function __construct(Application $application) {
		$this->application = $application;
		$this->ID = mt_rand(0, 0x7FFFFFFF);
		$this->Cache = Cache::register("Session_Test-" . $this->ID);
	}
	static public function instance($create = true) {
		if (!$create) {
			return null;
		}
		$x = zesk::get("Session_Test");
		if ($x instanceof Session_Test)
			return $x;
		$x = new Session_Test();
		zesk::set("Session_Test", $x);
		return $x;
	}
	public function id() {
		return $this->ID;
	}
	public function get($name = null, $default = null) {
		$value = $this->Cache->__get($name);
		if ($value === null)
			return $default;
		return $value;
	}
	public function eget($name, $default = null) {
		$v = $this->get($name, null);
		if (empty($v)) {
			return $default;
		}
		return $v;
	}
	public function __get($name) {
		return $this->get($name);
	}
	public function __set($name, $value) {
		$this->Cache->__set($name, $value);
	}
	public function __isset($name) {
		$this->Cache->__isset($name);
	}
	public function has($name) {
		$this->Cache->has($name);
	}
	public function set($name, $value = null) {
		$this->Cache->__set($name, $value);
	}
	public function filter($list = null) {
		return $this->Cache->filter($list);
	}
	private function global_session_user_id() {
		return zesk::get("SESSION_USER_ID", "User");
	}
	public function user_id() {
		return $this->__get(self::global_session_user_id());
	}
	public function user() {
		return $this->application->object_factory("User")->fetch($this->user_id());
	}
	public function authenticate($id, $ip = false) {
		$this->__set(self::global_session_user_id(), Object::mixed_to_id($id));
		$this->__set(self::global_session_user_id() . "_IP", $ip);
	}
	public function authenticated() {
		$user = $this->__get(self::global_session_user_id());
		return !empty($user);
	}
	public function deauthenticate() {
		$this->__set(self::global_session_user_id(), null);
	}
	public function variables() {
		return $this->Cache->filter();
	}
	public function delete() {
		$path = $this->Cache->cache_file_path();
		if (file_exists($path)) {
			unlink($path);
		}
	}
}