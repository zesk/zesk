<?php
declare(strict_types=1);

namespace zesk;

/**
 * Output license information for any modules
 *
 * @category Tools
 */
class Command_Licenses extends Command_Base {
	protected array $shortcuts = ['licenses'];

	protected array $option_types = [
		'all' => 'boolean',
		'json' => 'boolean',
	];

	protected array $option_help = [
		'all' => 'Do all modules instead of just those loaded',
		'json' => 'Output JSON structure',
	];

	private array $codeToLabel = [
		'licenses' => 'Licenses',
		'urlProject' => 'Project URL',
		'urlLicense' => 'License URL',
	];

	protected function run(): int {
		$modules = $this->application->modules;
		$moduleData = [];
		if ($this->optionBool('all')) {
			foreach ($modules->availableConfiguration() as $module => $configPath) {
				try {
					$moduleData[$module] = JSON::decode(File::contents($configPath));
				} catch (Exception_Parse $e) {
					$this->error('Unable to parse {module} {configPath} {message}', [
						'module' => $module, 'configPath' => $configPath,
					] + $e->variables());
				}
			}
		} else {
			foreach ($modules->moduleNames() as $moduleName) {
				$moduleData[$moduleName] = $modules->object($moduleName)->moduleConfiguration();
			}
		}
		$result = [];
		foreach ($moduleData as $moduleName => $moduleConfiguration) {
			$info = array_filter(ArrayTools::filterKeys($moduleConfiguration, ['name', 'description']));
			$licenseInfo = array_filter(ArrayTools::filterKeys($moduleConfiguration, [
				'urlProject', 'urlLicense', 'licenses',
			]));
			if (count($licenseInfo)) {
				$result[$moduleName] = $info + $licenseInfo;
			}
		}
		if ($this->optionBool('json')) {
			echo JSON::encodePretty($result);
		} else {
			foreach ($result as $moduleName => $licenseInfo) {
				echo "=== $moduleName ===\n";
				if (array_key_exists('licenses', $licenseInfo)) {
					$licenseInfo['licenses'] = implode(', ', $licenseInfo['licenses']);
				}
				$licenseInfo = ArrayTools::filterKeys($licenseInfo, null, ['name']);
				echo Text::format_pairs(ArrayTools::keysMap($licenseInfo, $this->codeToLabel));
				echo "\n\n";
			}
		}
		return 0;
	}
}
