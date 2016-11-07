<?php
/**
 * $URL: https://code.marketacumen.com/zesk/trunk/classes/session/mock.inc $
 * @package zesk
 * @subpackage session
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2008, Market Acumen, Inc.
 */
use zesk\Cache;

/**
 * 
 * @author kent
 *
 */
class Session_Mock implements Session_Interface {
	protected $ID;
	/**
	 * Session data
	 *
	 * @var Cache
	 */
	protected $Cache;
	function __construct() {
		$this->ID = mt_rand(0, 0x7FFFFFFF);
		$this->Cache = Cache::register("Session_Mock-" . $this->ID);
	}
	static public function instance($create = true) {
		global $zesk;
		/* @var $zesk zesk\Kernel */
		if ($zesk->objects->session instanceof self) {
			return $zesk->objects->session;
		}
		if (!$create) {
			return null;
		}
		return $zesk->objects->session = new Session_Mock();
	}
	public function id() {
		return $this->ID;
	}
	public function has($name) {
		return $this->__isset($name);
	}
	public function __isset($name) {
		return $this->Cache->__isset($name);
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
	public function set($name, $value = null) {
		$this->Cache->__set($name, $value);
	}
	public function filter($list = null) {
		return $this->Cache->filter($list);
	}
	private function global_session_user_id() {
		return "user";
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
		return $this->Cache->filter();
	}
	public function delete() {
		$this->Cache->erase();
	}
}
