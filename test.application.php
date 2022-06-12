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
 * Test Application
 */
class TestApplicationFactory {
	public static function factory(): Application {
		try {
			$zesk = Kernel::singleton();
			$zesk->paths->setApplication(__DIR__);
			$application = $zesk->createApplication();
		} catch (Exception_Semantics) {
			die(__CLASS__ . ' is incorrectly configured');
		}

		$files = [
			$application->path('docker/etc/test.conf'),
			$application->paths->uid('test.conf'),
			$application->paths->uid('test.json'),
		];
		$application->configureInclude($files)->configure();
		$modules = [];
		if (defined('PHPUNIT')) {
			$modules[] = 'phpunit';
		}
		if (isset($_SERVER['ZESK_EXTRA_MODULES'])) {
			$modules = array_merge($modules, toList($_SERVER['ZESK_EXTRA_MODULES']));
		}
		$application->setOption('modules', array_merge($application->optionArray('modules'), $modules));
		$application->setOption('version', Version::release());

		return $application->configure();
	}
}

return TestApplicationFactory::factory();
