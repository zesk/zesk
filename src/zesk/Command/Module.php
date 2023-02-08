<?php
declare(strict_types=1);

namespace zesk;

/**
 * Load a module or list all modules available.
 *
 * @category Modules
 */
class Command_Module extends Command_Base {
	protected array $shortcuts = ['module', 'm'];

	protected array $option_types = [
		'format' => 'string', 'loaded' => 'boolean', '*' => 'string',
	];

	/**
	 * @return int
	 */
	public function run(): int {
		if ($this->hasArgument()) {
			return $this->loadArgumentModules();
		} else {
			$this->listModules();
			return 0;
		}
	}

	/**
	 * @return int
	 * @throws Exception_Semantics
	 */
	private function loadArgumentModules(): int {
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

	public function listModules(): int {
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
