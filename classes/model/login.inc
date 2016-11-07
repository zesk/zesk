<?php
class Model_Login extends zesk\Model {

	protected $login = null;

	protected $login_password = null;

	protected $login_password_hash = null;

	/**
	 *
	 * @var User
	 */
	protected $user = null;

	function login($login = null) {
		if ($login !== null) {
			$this->login = $login;
			return $this;
		}
		return $this->login;
	}

	function __set($name, $value) {
		if ($name === "login_password") {
			$this->login_password_hash = strtoupper(md5($value));
			$this->login_password = $value;
			return;
		}
		parent::__set($name, $value);
	}

	function __unset($name) {
		if ($name === "login_password") {
			$this->login_password = null;
			$this->login_password_hash = null;
		} else {
			parent::__unset($name);
		}
	}
}
