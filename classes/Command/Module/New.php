<?php declare(strict_types=1);

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
	protected array $shortcuts = ['module-new'];

	protected array $option_types = [
		'app' => 'boolean',
		'zesk' => 'boolean',
		'*' => 'string',
	];

	protected array $option_help = [
		'app' => 'Create module in the application (default)',
		'zesk' => 'Create module in zesk',
		'*' => 'Names of the modules to create',
	];

	public function run(): int {
		$names = $this->argumentsRemaining();
		if (count($names) === 0) {
			$this->usage('Must specify module names to create');
		}
		$modules = $this->application->modules;
		foreach ($names as $name) {
			$module = $modules->cleanName($name);
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
		return $this->hasErrors() ? 99 : 0;
	}

	/**
	 * @param $module
	 * @return string|null
	 */
	public function new_module_path($module): ?string {
		$is_zesk = $this->optionBool('zesk');
		$is_app = $this->optionBool('app', !$is_zesk);

		if (!$is_app && !$is_zesk) {
			$is_app = true;
		}
		$app_root = $this->application->path();
		$zesk_root = $this->application->zeskHome();
		$module_paths = $this->application->modulePath();
		foreach ($module_paths as $module_path) {
			$path = path($module_path, $module);
			if ($is_app && str_starts_with($path, $app_root)) {
				return $path;
			}
			if ($is_zesk && str_starts_with($path, $zesk_root)) {
				return $path;
			}
		}
		return null;
	}

	public function questionnaire($name, $path): void {
		$module = $this->application->modules->cleanName($name);
		$conf = [];
		$conf['name'] = $this->prompt('Human-readable name for your module? ', $name);
		$namespace = $this->prompt('Namespace for this module? ', 'zesk');
		$namespace_line = '';
		if ($namespace) {
			$namespace = rtrim($namespace, '\\');
			$conf['autoload'] = [
				'classPrefix' => $namespace . '\\',
			];
			$namespace_line = "namespace $namespace;\n";
		}

		$tpl_path = path(__DIR__, 'templates');
		$module_class = PHP::cleanFunction("Module_$name");
		if ($this->promptYesNo("Create $module_class?")) {
			$inc_path = explode('/', str_replace('_', '/', $name));
			array_unshift($inc_path, 'module');
			array_unshift($inc_path, 'classes');
			$inc_name = strtolower(array_pop($inc_path)) . '.php';
			$inc_path = strtolower(path($path, implode('/', $inc_path)));
			Directory::create($inc_path);

			$tpl = file_get_contents(path($tpl_path, 'module.php.txt'));
			$p = path($inc_path, $inc_name);
			$this->log("Created $p");
			file_put_contents($p, map($tpl, [
				'module_class' => $module_class,
				'namespace_line' => $namespace_line,
			]));
		}
		$conf['module_class'] = "$namespace\\$module_class";
		$conf_path = path($path, "$module.module.json");
		if (!file_exists($conf_path)) {
			file_put_contents($conf_path, JSON::encodePretty($conf));
		}
		$this->log("Created $conf_path");
	}
}
