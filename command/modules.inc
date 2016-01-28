<?php

/**
 * List all possible modules available to the current application.
 *
 * @author kent
 * @category Modules
 */
class Command_Modules extends Command_Base {

	protected $help = "List all possible modules available to the current application.";

	function run() {
		$modules = Module::available();
		foreach ($modules as $module => $module_data) {
			$loaded_modules[$module] = avalue($module_data, 'loaded') ? true : false;
		}
		echo text::format_pairs($loaded_modules);
	}
}