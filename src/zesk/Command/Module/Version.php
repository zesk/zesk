<?php declare(strict_types=1);
namespace zesk;

/**
 * Output a list of modules and their current version numbers.
 *
 * @category Modules
 */
class Command_Module_Version extends Command_Base {
	protected array $shortcuts = ['module-version'];

	protected array $option_types = [
		'*' => 'string',
	];

	protected array $option_help = [
		'*' => 'List of modules to get version numbers for',
	];

	public function run(): int {
		$app = $this->application;
		$modules = $this->argumentsRemaining();
		if (count($modules) === 0) {
			$modules = array_keys($app->modules->moduleNames());
		}
		$exit = self::EXIT_CODE_SUCCESS;
		foreach ($modules as $module) {
			$this->verboseLog('Checking module {module}', compact('module'));

			try {
				$version = $app->modules->module($module)->version();
				if ($version === '') {
					$version = 'none';
				}
				echo "$module: $version\n";
			} catch (Exception_NotFound) {
				echo "$module: no such module";
				$exit = self::EXIT_CODE_ARGUMENTS;
			}
		}
		return $exit;
	}
}
