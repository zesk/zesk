<?php
/**
 * @copyright &copy; 2016 Market Acumen, Inc.
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
	protected $help = "List all possible modules available to the current application.";

	protected $option_types = array(
		"format" => "string",
		"loaded" => "boolean",
	);

	public function run() {
		$only_loaded = $this->option_bool("loaded");
		$loaded_modules = array();
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
