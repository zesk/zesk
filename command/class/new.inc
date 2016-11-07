<?php
/**
 * Add a module to zesk, creating basic class names and configuration files.
 *
 * @category Modules
 */
use zesk\PHP;

/**
 *
 * @author kent
 *        
 */
class Command_Class_New extends Command {
	protected $option_types = array(
		'app' => 'boolean',
		'zesk' => 'boolean',
		'sql' => 'boolean',
		'schema' => 'boolean',
		'*' => 'string'
	);
	protected $option_help = array(
		'app' => 'Create class in the application (default)',
		'zesk' => 'Create classin zesk',
		'sql' => 'Create SQL file instead of a Schema file (default)',
		'schema' => 'Create Schema class instead of a SQL file',
		'*' => "Names of the classes to create (capitalization matters)"
	);
	function run() {
		$names = $this->arguments_remaining(true);
		if (count($names) === 0) {
			$this->usage("Must specify class names to create");
		}
		foreach ($names as $class) {
			if (!PHP::valid_class($class)) {
				$this->error("Class $class is not a valid class name");
				continue;
			}
			try {
				$object = $this->application->object_factory($class);
				$this->error("Class $class already exists");
				continue;
			} catch (Exception_Class_NotFound $e) {
			}
			try {
				$this->questionnaire($class);
			} catch (Exception $e) {
				$this->error($e);
				continue;
			}
		}
	}
	// 	function new_class_paths($module) {
	// 		$option_zesk = $this->option_bool("zesk");
	// 		$app = $this->option_bool("app", !$option_zesk);
	

	// 		if (!$app && !$option_zesk) {
	// 			$app = true;
	// 		}
	// 		$app_root = $this->application->application_root();
	// 		$zesk_root = zesk::root();
	// 		$module_paths = zesk::module_path();
	// 		foreach ($module_paths as $module_path) {
	// 			$path = path($module_path, $module);
	// 			if ($app && begins($path, $app_root)) {
	// 				return $path;
	// 			}
	// 			if ($zesk && begins($path, $zesk_root)) {
	// 				return $path;
	// 			}
	// 		}
	// 		return null;
	// 	}
	function questionnaire($class) {
		throw new zesk\Exception_Unimplemented(__METHOD__);
	}
}
