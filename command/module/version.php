<?php declare(strict_types=1);
namespace zesk;

/**
 * Output a list of modules and their current version numbers.
 *
 * @category Modules
 */
class Command_Module_Version extends Command_Base {
	protected array $option_types = [
		'*' => 'string',
	];

	protected array $option_help = [
		'*' => 'List of modules to get version numbers for',
	];

	public function run(): void {
		$app = $this->application;
		$modules = $this->arguments_remaining(true);
		if (count($modules) === 0) {
			$modules = array_keys($app->modules->load());
		}

		foreach ($modules as $module) {
			$this->verbose_log('Checking module {module}', compact('module'));
			$version = $app->modules->version($module);
			if ($version === null) {
				$version = '-';
			}
			echo "$module: $version\n";
		}
	}
}
