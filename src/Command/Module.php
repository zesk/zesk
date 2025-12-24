<?php
declare(strict_types=1);
/**
 * @package    zesk
 * @subpackage Command
 * @author     kent
 * @copyright  Copyright &copy; 2025, Market Acumen, Inc.
 */

namespace zesk\Command;

use zesk\Exception;
use zesk\Exception\SemanticsException;

/**
 * Load a module or list all modules available.
 *
 * @category Modules
 */
class Module extends SimpleCommand
{
	protected array $shortcuts = [
		'module',
		'm',
	];

	protected array $argumentTypes = [
		'format' => 'string',
		'loaded' => 'boolean',
		'*' => 'string',
	];

	/**
	 * @return int
	 * @throws SemanticsException
	 */
	public function run(): int
	{
		if ($this->hasArgument()) {
			return $this->loadArgumentModules();
		} else {
			try {
				return $this->listModules();
			} catch (Exception $e) {
				$this->error($e->getMessage());
				return 1;
			}
		}
	}

	/**
	 * @return int
	 * @throws SemanticsException
	 */
	private function loadArgumentModules(): int
	{
		do {
			$module = $this->getArgument('module');

			try {
				$this->application->modules->load($module);
			} catch (Exception $e) {
				$this->error('Failed loading module: {message}', $e->variables());
				return 1;
			}
		} while ($this->hasArgument());
		return 0;
	}

	/**
	 * @return int
	 */
	public function listModules(): int
	{
		$onlyLoaded = $this->optionBool('loaded');
		$modules = $this->application->modules;
		$loadedModules = [];
		$moduleNames = $onlyLoaded ? $modules->moduleNames() : $modules->available();
		sort($moduleNames);
		foreach ($moduleNames as $moduleName) {
			$loadedModules[$moduleName] = $modules->loaded($moduleName);
		}
		return $this->renderFormat($loadedModules) ? 0 : 1;
	}
}
