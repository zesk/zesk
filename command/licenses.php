<?php declare(strict_types=1);
namespace zesk;

/**
 * Output license information for any modules
 *
 * @category Tools
 */
class Command_Licenses extends Command_Base {
	protected array $option_types = [
		'all' => 'boolean',
	];

	protected array $option_help = [
		'all' => "Do all modules instead of just those loaded",
	];

	protected function run(): void {
		$modules = $this->application->modules;
		$modules = $this->optionBool('all') ? $modules->available() : $modules->load();
		foreach ($modules as $name => $module_data) {
			$configuration = to_array(avalue($module_data, 'configuration'));

			$url_license = $url_project = $project_url = $licenses = $description = null;
			extract($configuration, EXTR_IF_EXISTS);
			if ($project_url !== null) {
				$this->application->logger->notice("Module {name} uses deprecated setting PROJECT_URL update to URL_PROJECT", compact("name"));
			}
			if ($url_license || $licenses) {
				$desc = $description ? ": $description" : "";
				echo "$name$desc\n";
				if ($url_license) {
					echo "License information: " . $url_license . "\n";
				}
				if ($licenses) {
					echo "      License types: " . implode(", ", to_list($licenses)) . "\n";
				}
				echo "\n";
			}
		}
	}
}
