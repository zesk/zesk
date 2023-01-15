<?php
declare(strict_types=1);
/**
 * @package zesk
 * @subpackage core
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2022, Market Acumen, Inc.
 */

namespace zesk;

include __DIR__ . '/xdebug.php';
require_once __DIR__ . '/.autoload-load.php';

/**
 * The Zesk
 */
class ApplicationGenerator {
	public static function generate(): Application {
		try {
			$zesk = Kernel::factory();
			$zesk->paths->setApplication(__DIR__);
			$application = $zesk->createApplication();
		} catch (Exception_Semantics) {
			die(__CLASS__ . ' is incorrectly configured');
		}
		$include_files = [
			'/etc/zesk.json',
			$application->path('/etc/zesk.json'),
			$application->path('etc/host/' . System::uname() . '.json'),
			$application->paths->uid('zesk.json'),
		];
		if (isset($_SERVER['ZESK_EXTRA_INCLUDES'])) {
			$include_files = array_merge($include_files, toList($_SERVER['ZESK_EXTRA_INCLUDES'], [], ':'));
		}
		$application->configureInclude($include_files);
		$modules = [];
		if (isset($_SERVER['ZESK_EXTRA_MODULES'])) {
			$modules = array_merge($modules, toList($_SERVER['ZESK_EXTRA_MODULES']));
		}
		$application->setOption('modules', array_merge($application->optionArray('modules'), $modules));
		$application->setOption('version', Version::release());

		return $application->configure();
	}
}

return ApplicationGenerator::generate();
