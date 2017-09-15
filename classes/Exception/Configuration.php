<?php
namespace zesk;

class Exception_Configuration extends Exception {
	/**
	 * 
	 * @var string
	 */
	public $name = "";
	/**
	 * 
	 * @param unknown $name
	 * @param unknown $message
	 * @param array $arguments
	 * @param Exception $previous
	 */
	public function __construct($name, $message, array $arguments = array(), Exception $previous = null) {
		$this->name = $name;
		parent::__construct($message, array(
			"name" => $name
		) + $arguments, 0, $previous);
	}
}

