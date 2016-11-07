<?php

class Server_Exception_Group_NotFound extends Server_Exception {
	public $group = null;

	function __construct($group, $message=null, $code=0, $previous=null) {
		$message = $message === null ? "Group not found $group" : $message;
		$this->group = $group;
		parent::__construct($message, $code, $previous);
	}

}
