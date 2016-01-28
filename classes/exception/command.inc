<?php
class Exception_Command extends Zesk_Exception {

	public $command = null;

	/**
	 * 
	 * @var array
	 */
	public $output = null;

	function __construct($command, $resultcode, array $output) {
		parent::__construct("{command} exited with result {resultcode}", array(
			"resultcode" => $resultcode, 
			"command" => $command, 
			"output" => $output
		), $resultcode);
		$this->command = $command;
		$this->output = $output;
	}
}