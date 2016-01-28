<?php
class Exception_Configuration extends Zesk_Exception {
	public $name = "";

	public function __construct($name, $message, array $arguments = array(), Exception $previous = null) {
		$this->name = $name;
		parent::__construct($message, array(
			"name" => $name
		) + $arguments, 0, $previous);
	}
}
