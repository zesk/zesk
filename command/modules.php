<?php declare(strict_types=1);
/**
 * @copyright &copy; 2022 Market Acumen, Inc.
 */
namespace zesk;

/**
 * List all possible modules available to the current application.
 *
 * @author kent
 * @category Modules
 */
class Command_Modules extends Command_Base {
	/**
	 *
	 * @var string
	 */
	protected $help = 'List all possible modules available to the current application.';

	protected array $option_types = [
		'format' => 'string',
		'loaded' => 'boolean',
	];

	public function run(): void {
		$only_loaded = $this->optionBool('loaded');
		$loaded_modules = [];
		$modules = $this->application->modules->available();
		foreach ($modules as $module => $module_data) {
			$loaded = avalue($module_data, 'loaded') ? true : false;
			if (!$only_loaded || $loaded) {
				$loaded_modules[$module] = $loaded;
			}
		}
		$this->render_format($loaded_modules);
	}
}
