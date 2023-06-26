<?php
declare(strict_types=1);
/**
 * @package zesk
 * @subpackage Command
 * @author kent
 * @copyright Copyright &copy; 2023, Market Acumen, Inc.
 */

namespace zesk\Command;

use zesk\Exception\NotFoundException;

/**
 * Output a list of modules and their current version numbers.
 *
 * @category Modules
 */
class ModuleVersion extends SimpleCommand {
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
			$modules = $app->modules->moduleNames();
		}
		$exit = self::EXIT_CODE_SUCCESS;
		foreach ($modules as $module) {
			$this->verboseLog('Checking module {module}', ['module' => $module]);

			try {
				$version = $app->modules->module($module)->version();
				if ($version === '') {
					$version = 'none';
				}
				echo "$module: $version\n";
			} catch (NotFoundException) {
				echo "$module: no such module";
				$exit = self::EXIT_CODE_ARGUMENTS;
			}
		}
		return $exit;
	}
}
