<?php
/**
 * Add a module to zesk, creating basic class names and configuration files.
 *
 * @category Modules
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
			$class = zesk::clean_function($class);
			try {
				$object = $this->application->object_factory($class);
				$this->error("Class $class already exists");
				continue;
			} catch (Exception_Class_NotFound $e) {
			}
			$paths = $this->new_class_paths($class);
			try {
				dir::depend($path);
				$this->questionnaire($name, $path);
			} catch (Exception $e) {
				$this->error($e);
				continue;
			}
		}
	}

	function new_module_path($module) {
		$zesk = $this->option_bool("zesk");
		$app = $this->option_bool("app", !$zesk);

		if (!$app && !$zesk) {
			$app = true;
		}
		$app_root = zesk::application_root();
		$zesk_root = zesk::root();
		$module_paths = zesk::module_path();
		foreach ($module_paths as $module_path) {
			$path = path($module_path, $module);
			if ($app && begins($path, $app_root)) {
				return $path;
			}
			if ($zesk && begins($path, $zesk_root)) {
				return $path;
			}
		}
		return null;
	}

	function questionnaire($name, $path) {
		$module = Module::clean_name($name);
		$conf = array();
		$conf['NAME'] = $this->prompt("Human-readable name for your module? ", $name);

		$tpl_path = path(dirname(__FILE__), 'templates');
		$module_class = "Module_$name";
		if ($this->prompt_yes_no("Create $module_class?")) {
			$inc_path = explode("/", str_replace("_", "/", $name));
			array_unshift($inc_path, 'module');
			array_unshift($inc_path, 'classes');
			$inc_name = strtolower(array_pop($inc_path)) . ".inc";
			$inc_path = path($path, implode("/", $inc_path));
			dir::create($inc_path);

			$tpl = file_get_contents(path($tpl_path, 'module.inc.txt'));
			$p = path($inc_path, $inc_name);
			$this->log("Created $p");
			file_put_contents($p, map($tpl, array(
				"module_class" => $module_class
			)));

			$tpl = file_get_contents(path($tpl_path, 'module.module.inc.txt'));
			$p = path($path, "$module.module.inc");
			$this->log("Created $p");
			file_put_contents($p, map($tpl, array(
				"module_class" => $module_class
			)));
		}
		$conf_path = path($path, "$module.module.conf");
		if (!file_exists($conf_path)) {
			file_put_contents($conf_path, "");
		}
		conf::edit($conf_path, $conf);
		$this->log("Created $conf_path");
	}
}
