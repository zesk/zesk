<?php

/**
 *
 *
 */
namespace zesk;

/**
 * Output all command arguments as a JSON-encoded array
 *
 * @category Debugging
 * @param array $arguments        	
 * @return unknown
 */
class Command_Arguments extends Command {
	public $option_types = array(
		"*" => "string"
	);
	protected function run() {
		$arguments = $this->arguments_remaining(true);
		echo json_encode($arguments) . "\n";
		return 0;
	}
}

