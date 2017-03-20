<?php

/**
 * 
 */
namespace zesk;

/**
 * 
 */
class Exception_Connect extends Exception {
	public $host = null;
	function __construct($host, $message = null, array $arguments = array(), $previous = null) {
		parent::__construct($message, $arguments, null, $previous);
		$this->host = $host;
	}
	function __toString() {
		return $this->host . "\n" . parent::__toString();
	}
}
