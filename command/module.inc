<?php
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
		Module::load($this->get_arg("module"));
	}
}