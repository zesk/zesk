<?php

/**
 * 
 */
namespace zesk;

/**
 * @author kent
 *
 */
class Exception_Command extends Exception {
	public $command = null;
	
	/**
	 * 
	 * @var array
	 */
	public $output = null;
	function __construct($command, $resultcode, array $output) {
		parent::__construct("{command} exited with result {resultcode}\nOUTPUT:\n{output}\nEND OUTPUT", array(
			"resultcode" => $resultcode,
			"command" => $command,
			"output" => $output
		), $resultcode);
		$this->command = $command;
		$this->output = $output;
	}
}
