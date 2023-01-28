<?php declare(strict_types=1);
/**
 *
 */
namespace Server\classes\Server\Exception\Group;

use Server\classes\Server\Server_Exception;

/**
 *
 * @author kent
 *
 */
class Server_Exception_Group_NotFound extends Server_Exception {
	public $group = null;

	public function __construct($group, $message = null, $code = 0, $previous = null) {
		$message = $message === null ? "Group not found $group" : $message;
		$this->group = $group;
		parent::__construct($message, $code, $previous);
	}
}
