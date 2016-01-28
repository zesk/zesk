<?php

class Server_Exception_User_Create extends Server_Exception {
	public $user = null;
	public $parameters = null;

	function __construct($user, array $parameters, $message = null, $code = 0, $previous = null) {
		$message = $message === null ? "User not found $user" : $message;
		$this->user = $user;
		$this->parameters = $parameters;
		parent::__construct($message, $code, $previous);
	}

}