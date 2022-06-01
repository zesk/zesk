<?php
declare(strict_types=1);
/**
 * @package zesk
 * @subpackage core
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2022, Market Acumen, Inc.
 */

namespace zesk;

if (isset($_SERVER['XDEBUG_ENABLED']) && function_exists('xdebug_break')) {
	call_user_func('xdebug_break');
}
if (!isset($GLOBALS['__composer_autoload_files'])) {
	if (is_file(__DIR__ . '/vendor/autoload.php')) {
		require_once __DIR__ . '/vendor/autoload.php';
	} else {
		fprintf(STDERR, "Missing vendor directory\n");
		exit(1);
	}
} else {
	require_once __DIR__ . '/autoload.php';
}

class ApplicationGenerator {
	public static function generate(): Application {
		try {
			$zesk = Kernel::singleton();
			$zesk->paths->setApplication(__DIR__);
			$application = $zesk->createApplication();
		} catch (Exception_Semantics) {
			die(__CLASS__ . ' is incorrectly configured');
		}

		$application->configureInclude([
			'/etc/zesk.json',
			$application->path('/etc/zesk.json'),
			$application->path('etc/host/' . System::uname() . '.json'),
			$application->paths->uid('zesk.json'),
		]);
		$modules = [
			'GitHub',
		];
		if (defined('PHPUNIT')) {
			$modules[] = 'phpunit';
		}
		if (isset($_SERVER['ZESK_EXTRA_MODULES'])) {
			$modules = array_merge($modules, toList($_SERVER['ZESK_EXTRA_MODULES']));
		}
		$application->setOption('modules', array_merge($application->optionArray('modules'), $modules));
		$application->setOption('version', Version::release());

		return $application;
	}
}

return ApplicationGenerator::generate();
