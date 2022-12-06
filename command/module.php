<?php
declare(strict_types=1);

namespace zesk;

/**
 * Load a module or list all modules available.
 *
 * @category Modules
 */
class Command_Module extends Command_Base {
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

	private function loadArgumentModules(): int {
		do {
			try {
				$this->application->modules->load($this->getArgument('module'));
			} catch (\Exception $e) {
				$this->error($e);
				return 1;
			}
		} while ($this->hasArgument());
		return 0;
	}

	public function listModules(): int {
		$only_loaded = $this->optionBool('loaded');
		$loaded_modules = [];
		$modules = $this->application->modules->available();
		foreach ($modules as $module => $module_data) {
			$loaded = $module_data['loaded'];
			if (!$only_loaded || $loaded) {
				$loaded_modules[$module] = $loaded;
			}
		}
		return $this->renderFormat($loaded_modules) ? 0 : 1;
	}
}
