<?php

/**
 *
 *
 */
namespace zesk;

/**
 * Add a module to zesk, creating basic class names and configuration files.
 *
 * @category Modules
 */
class Command_Module_New extends Command {
	protected $option_types = array(
		'app' => 'boolean',
		'zesk' => 'boolean',
		'*' => 'string',
	);

	protected $option_help = array(
		'app' => 'Create module in the application (default)',
		'zesk' => 'Create module in zesk',
		'*' => "Names of the modules to create",
	);

	public function run() {
		$names = $this->arguments_remaining(true);
		if (count($names) === 0) {
			$this->usage("Must specify module names to create");
		}
		$modules = $this->application->modules;
		foreach ($names as $name) {
			$module = $modules->clean_name($name);
			if ($modules->exists($module)) {
				$this->error("Module $module already exists");

				continue;
			}
			$path = $this->new_module_path($module);

			try {
				Directory::depend($path);
				$this->questionnaire($name, $path);
			} catch (Exception $e) {
				$this->error($e);

				continue;
			}
		}
	}

	public function new_module_path($module) {
		$is_zesk = $this->option_bool("zesk");
		$is_app = $this->option_bool("app", !$is_zesk);

		if (!$is_app && !$is_zesk) {
			$is_app = true;
		}
		$app_root = $this->application->path();
		$zesk_root = $this->application->zesk_root();
		$module_paths = $this->application->module_path();
		foreach ($module_paths as $module_path) {
			$path = path($module_path, $module);
			if ($is_app && begins($path, $app_root)) {
				return $path;
			}
			if ($is_zesk && begins($path, $zesk_root)) {
				return $path;
			}
		}
		return null;
	}

	public function questionnaire($name, $path) {
		$module = $this->application->modules->clean_name($name);
		$conf = array();
		$conf['name'] = $this->prompt("Human-readable name for your module? ", $name);
		$namespace = $this->prompt("Namespace for this module? ", "zesk");
		$namespace_line = "";
		if ($namespace) {
			$namespace = rtrim($namespace, "\\");
			$conf['autoload_options'] = array(
				"class_prefix" => $namespace . "\\",
			);
			$namespace_line = "namespace $namespace;\n";
		}

		$tpl_path = path(__DIR__, 'templates');
		$module_class = PHP::clean_function("Module_$name");
		if ($this->prompt_yes_no("Create $module_class?")) {
			$inc_path = explode("/", str_replace("_", "/", $name));
			array_unshift($inc_path, 'module');
			array_unshift($inc_path, 'classes');
			$inc_name = strtolower(array_pop($inc_path)) . ".php";
			$inc_path = strtolower(path($path, implode("/", $inc_path)));
			Directory::create($inc_path);

			$tpl = file_get_contents(path($tpl_path, 'module.php.txt'));
			$p = path($inc_path, $inc_name);
			$this->log("Created $p");
			file_put_contents($p, map($tpl, array(
				"module_class" => $module_class,
				"namespace_line" => $namespace_line,
			)));
		}
		$conf['module_class'] = "$namespace\\$module_class";
		$conf_path = path($path, "$module.module.json");
		if (!file_exists($conf_path)) {
			file_put_contents($conf_path, JSON::encode_pretty($conf));
		}
		$this->log("Created $conf_path");
	}
}
