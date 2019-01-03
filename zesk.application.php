<?php

/**
 * @package zesk
 * @subpackage core
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2012, Market Acumen, Inc.
 */
namespace zesk;

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
class ApplicationConfigurator {
	public static function configure() {
		$zesk = Kernel::singleton();
		$zesk->paths->set_application(__DIR__);

		$application = $zesk->create_application();

		$application->configure_include(array(
			"/etc/zesk.json",
			$application->path("/etc/zesk.json"),
			$application->path("etc/host/" . System::uname() . ".json"),
			$application->paths->uid("zesk.json"),
		));
		$modules = array(
			"GitHub",
		);
		if (defined("PHPUNIT")) {
			$modules[] = "phpunit";
		}
		if (defined("ZESK_EXTRA_MODULES")) {
			$modules = array_merge($modules, to_list(ZESK_EXTRA_MODULES));
		}
		$application->set_option("modules", $modules);
		$application->configure();

		$application->set_option("version", Version::release());

		return $application;
	}
}

return ApplicationConfigurator::configure();
