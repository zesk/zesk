<?php
declare(strict_types=1);
/**
 * @package zesk
 * @subpackage Command
 * @author kent
 * @copyright Copyright &copy; 2025, Market Acumen, Inc.
 */


namespace zesk\Command;

use zesk\ArrayTools;
use zesk\Exception;
use zesk\Exception\ParseException;
use zesk\File;
use zesk\JSON;
use zesk\Text;

/**
 * Output license information for any modules
 *
 * @category Tools
 */
class Licenses extends SimpleCommand
{
	protected array $shortcuts = ['licenses'];

	protected array $option_types = [
		'all'  => 'boolean',
		'json' => 'boolean',
	];

	protected array $option_help = [
		'all'  => 'Do all modules instead of just those loaded',
		'json' => 'Output JSON structure',
	];

	private array $codeToLabel = [
		'description' => 'Description',
		'licenses'    => 'Licenses',
		'urlProject'  => 'Project URL',
		'urlLicense'  => 'License URL',
	];

	protected function run(): int
	{
		$modules = $this->application->modules;
		$moduleData = [];
		if ($this->optionBool('all')) {
			foreach ($modules->availableConfiguration() as $module => $className) {
				try {
					$moduleData[$module] = $moduleObject = $this->application->factory($className, $this->application);
					$moduleObject->initialize();
				} catch (Exception $e) {
					$this->error('Unable to create {module} {className} {message}', [
						'module'    => $module,
						'className' => $className,
					] + $e->variables());
				}
			}
		} else {
			foreach ($modules->moduleNames() as $moduleName) {
				$moduleData[$moduleName] = $modules->object($moduleName);
			}
		}
		$result = [];
		foreach ($moduleData as $moduleName => $moduleObject) {
			$moduleConfiguration = $moduleObject->options() + $moduleObject->moduleConfiguration()->toArray();
			$info = array_filter(ArrayTools::filterKeys($moduleConfiguration, [
				'name',
				'description',
			]));
			$licenseInfo = array_filter(ArrayTools::filterKeys($moduleConfiguration, [
				'urlProject',
				'urlLicense',
				'licenses',
			]));
			if (count($licenseInfo)) {
				$result[$moduleName] = $info + $licenseInfo;
			}
		}
		ksort($result);
		if ($this->optionBool('json')) {
			echo JSON::encodePretty($result);
		} else {
			foreach ($result as $moduleName => $licenseInfo) {
				echo "=== $moduleName ===\n";
				if (array_key_exists('licenses', $licenseInfo)) {
					$licenseInfo['licenses'] = implode(', ', $licenseInfo['licenses']);
				}
				$licenseInfo = ArrayTools::filterKeys($licenseInfo, null, ['name']);
				echo Text::formatPairs(ArrayTools::keysMap($licenseInfo, $this->codeToLabel));
			}
		}
		return 0;
	}
}
