<?php declare(strict_types=1);
/**
 *
 */
namespace zesk;

/**
 *
 * @author kent
 *
 */
class Server_Exception_User_NotFound extends Server_Exception {
	public $user = null;

	public function __construct($user, $message = null, $code = 0, $previous = null) {
		$message = $message === null ? "User not found $user" : $message;
		$this->user = $user;
		parent::__construct($message, $code, $previous);
	}
}
