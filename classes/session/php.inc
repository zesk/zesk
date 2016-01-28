<?php
/**
 * $URL: https://code.marketacumen.com/zesk/trunk/classes/session/php.inc $
 * @package zesk
 * @subpackage session
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2008, Market Acumen, Inc.
 */
class Session_PHP implements Session_Interface {

	static private function need() {
		if (zesk::get("session_start"))
			return;
		if (!zesk::$console) {
			session_start();
		} else {
			global $_SESSION;
			if (!isset($_SESSION)) {
				$_SESSION = array();
			}
		}
		zesk::set("session_start", true);
	}

	static public function instance($create = true) {
		if (!$create) {
			$name = ini_get('session.name');
			if (!isset($_COOKIE[$name])) {
				return null;
			}
		}
		self::need();
		$x = zesk::get("Session_PHP");
		if ($x instanceof Session_PHP) {
			return $x;
		}
		$x = new Session_PHP();
		zesk::set("Session_PHP", $x);
		return $x;
	}

	public function id() {
		return session_id();
	}

	public function get($name = null, $default = null) {
		self::need();
		if ($name === null) {
			return $_SESSION;
		}
		return avalue($_SESSION, $name, $default);
	}

	public function eget($name, $default = null) {
		return aevalue($_SESSION, $name, $default);
	}

	public function __get($name) {
		self::need();
		return avalue($_SESSION, $name);
	}

	public function __set($name, $value) {
		self::need();
		$_SESSION[$name] = $value;
	}

	public function set($name, $value = null) {
		$this->__set($name, $value);
	}

	public function filter($list = null) {
		if ($list === null) {
			return $_SESSION;
		}
		return arr::filter($_SESSION, $list);
	}

	private function global_session_user_id() {
		return zesk::get("SESSION_USER_ID", "UserID");
	}

	public function user_id() {
		return $this->__get(self::global_session_user_id());
	}

	public function user() {
		return Object::factory("User")->fetch($this->user_id());
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
		return $_SESSION;
	}

	public function delete() {
		if (!zesk::$console) {
			session_destroy();
		}
	}
}
