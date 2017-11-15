<?php

/**
 *
 */
namespace zesk;

/**
 */
class Session_Mock extends Hookable implements Interface_Session {
	/**
	 *
	 * @var Application
	 */
	public $application = null;
	
	/**
	 *
	 * @var string
	 */
	protected $id;
	/**
	 *
	 * @var array
	 */
	private $data = array();
	
	/**
	 *
	 * @param Application $application
	 * @param unknown $mixed
	 * @param string $options
	 */
	function __construct(Application $application, $mixed = null, array $options = array()) {
		parent::__construct($application, $options);
		$this->inherit_global_options();
		$this->application = $application;
		$this->id = md5(microtime(false));
		if (is_array($mixed)) {
			$this->data = $mixed;
		}
	}
	
	/**
	 * Singleton interface to retrieve current session
	 *
	 * @return Session
	 */
	public function initialize_session(Request $request) {
		return $this;
	}
	
	/**
	 *
	 * {@inheritdoc}
	 *
	 * @see Interface_Session::id()
	 */
	public function id() {
		return $this->id;
	}
	public function has($name) {
		return $this->__isset($name);
	}
	public function __isset($name) {
		return isset($this->data[$name]);
	}
	
	/**
	 *
	 * {@inheritdoc}
	 *
	 * @see Interface_Settings::get()
	 */
	public function get($name = null, $default = null) {
		if ($name === null) {
			return $this->data;
		}
		return avalue($this->data, $name, $default);
	}
	
	/**
	 *
	 * {@inheritdoc}
	 *
	 * @see Interface_Settings::eget()
	 */
	public function eget($name, $default = null) {
		return aevalue($this->data, $name, $default);
	}
	
	/**
	 *
	 * {@inheritdoc}
	 *
	 * @see Interface_Settings::__get()
	 */
	public function __get($name) {
		$this->need();
		return avalue($this->data, $name);
	}
	
	/**
	 *
	 * {@inheritdoc}
	 *
	 * @see Interface_Settings::__set()
	 */
	public function __set($name, $value) {
		$this->data[$name] = $value;
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
			return $this->data;
		}
		return arr::filter($this->data, $list);
	}
	
	/**
	 *
	 * @return mixed|mixed[]|\zesk\Configuration
	 */
	private function global_session_user_id() {
		/* @var $zesk zesk\Kernel */
		return $this->application->configuration->path(__CLASS__)->get("user_id_variable", "user");
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
		$user_id = $this->user_id();
		if (empty($user_id)) {
			return null;
		}
		try {
			return $this->application->object_factory(__NAMESPACE__ . "\\" . "User", $user_id)->fetch();
		} catch (Exception_Object_NotFound $e) {
			$this->__set(self::global_session_user_id(), null);
			return null;
		}
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
		return $this->data;
	}
	
	/**
	 *
	 * {@inheritdoc}
	 *
	 * @see Interface_Session::delete()
	 */
	public function delete() {
		$this->data = array();
	}
}
