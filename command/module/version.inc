<?php
/**
 * Output a list of modules and their current version numbers.
 *
 * @category Modules
 */
class Command_Module_Version extends Command_Base {
	protected $option_types = array(
		'*' => 'string'
	);
	protected $option_help = array(
		'*' => 'List of modules to get version numbers for'
	);
	function run() {
		$app = $this->application;
		$modules = $this->arguments_remaining(true);
		if (count($modules) === 0) {
			$modules = array_keys($app->modules->load());
		}
		
		foreach ($modules as $module) {
			$this->verbose_log("Checking module {module}", compact("module"));
			$version = $app->modules->version($module);
			if ($version === null) {
				$version = "-";
			}
			echo "$module: $version\n";
		}
	}
}
