<?php
namespace zesk;

/**
 * Load a module
 *
 * @category Modules
 */
class Command_Module extends Command_Base {
	protected $help = "Load a module.";
	protected $option_types = array(
		'+' => "string"
	);
	function run() {
		$this->application->modules->load($this->get_arg("module"));
		return 0;
	}
}
