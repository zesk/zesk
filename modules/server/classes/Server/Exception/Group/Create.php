<?php
/**
 *
 */
namespace zesk;

/**
 *
 * @author kent
 *
 */
class Server_Exception_Group_Create extends Server_Exception {
	public $parameters = null;
	function __construct(array $parameters, $message = null, $code = 0, $previous = null) {
		$user = $parameters['user'];
		$message = $message === null ? "User not found $user" : $message;
		$this->parameters = $parameters;
		parent::__construct($message, $code, $previous);
	}
}
