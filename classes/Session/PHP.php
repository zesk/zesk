<?php
/**
 * 
 */
namespace zesk;

/**
 * 
 */
class Session_PHP implements Interface_Session {
	private $started = false;
	private $application = null;
	function __construct($mixed = null, $options = false, Application $application = null) {
		$this->application = $application;
	}
	
	/**
	 * Singleton interface to retrieve current session
	 * @return Session
	 */
	public function initialize_session(Request $request) {
		$this->need();
	}
	public function need() {
		global $zesk;
		/* @var $zesk zesk\Kernel */
		if ($this->started) {
			return;
		}
		if (!$zesk->console) {
			session_start();
		} else {
			global $_SESSION;
			if (!isset($_SESSION)) {
				$_SESSION = array();
			}
		}
		$this->started = true;
	}
	/**
	 *
	 * {@inheritdoc}
	 *
	 * @see Interface_Session::id()
	 */
	public function id() {
		return session_id();
	}
	public function has($name) {
		return $this->__isset($name);
	}
	public function __isset($name) {
		$this->need();
		return isset($_SESSION[$name]);
	}
	
	/**
	 *
	 * {@inheritdoc}
	 *
	 * @see Interface_Settings::get()
	 */
	public function get($name = null, $default = null) {
		$this->need();
		if ($name === null) {
			return $_SESSION;
		}
		return avalue($_SESSION, $name, $default);
	}
	
	/**
	 *
	 * {@inheritdoc}
	 *
	 * @see Interface_Settings::eget()
	 */
	public function eget($name, $default = null) {
		return aevalue($_SESSION, $name, $default);
	}
	
	/**
	 *
	 * {@inheritdoc}
	 *
	 * @see Interface_Settings::__get()
	 */
	public function __get($name) {
		$this->need();
		return avalue($_SESSION, $name);
	}
	
	/**
	 *
	 * {@inheritdoc}
	 *
	 * @see Interface_Settings::__set()
	 */
	public function __set($name, $value) {
		$this->need();
		$_SESSION[$name] = $value;
	}
	
	/**
	 *
	 * {@inheritdoc}
	 *
	 * @see Interface_Settings::set()
	 */
	public function set($name, $value = null) {
		$this->__set($name, $value);
	}
	
	/**
	 *
	 * {@inheritdoc}
	 *
	 * @see Interface_Settings::filter()
	 */
	public function filter($list = null) {
		if ($list === null) {
			return $_SESSION;
		}
		return arr::filter($_SESSION, $list);
	}
	
	/**
	 *
	 * @return mixed|mixed[]|\zesk\Configuration
	 */
	private function global_session_user_id() {
		global $zesk;
		/* @var $zesk zesk\Kernel */
		return $zesk->configuration->pave("session")->get("user_id_variable", "user");
	}
	
	/**
	 *
	 * {@inheritdoc}
	 *
	 * @see Interface_Session::user_id()
	 */
	public function user_id() {
		return $this->__get(self::global_session_user_id());
	}
	
	/**
	 *
	 * {@inheritdoc}
	 *
	 * @see Interface_Session::user()
	 */
	public function user() {
		return Object::factory("User")->fetch($this->user_id());
	}
	
	/**
	 *
	 * {@inheritdoc}
	 *
	 * @see Interface_Session::authenticate()
	 */
	public function authenticate($id, $ip = false) {
		$this->__set(self::global_session_user_id(), Object::mixed_to_id($id));
		$this->__set(self::global_session_user_id() . "_IP", $ip);
	}
	
	/**
	 *
	 * {@inheritdoc}
	 *
	 * @see Interface_Session::authenticated()
	 */
	public function authenticated() {
		$user = $this->__get(self::global_session_user_id());
		return !empty($user);
	}
	
	/**
	 *
	 * {@inheritdoc}
	 *
	 * @see Interface_Session::deauthenticate()
	 */
	public function deauthenticate() {
		$this->__set(self::global_session_user_id(), null);
	}
	
	/**
	 *
	 * {@inheritdoc}
	 *
	 * @see Interface_Settings::variables()
	 */
	public function variables() {
		return $_SESSION;
	}
	
	/**
	 *
	 * {@inheritdoc}
	 *
	 * @see Interface_Session::delete()
	 */
	public function delete() {
		global $zesk;
		/* @var $zesk zesk\Kernel */
		if (!$zesk->console) {
			session_destroy();
		}
	}
}
