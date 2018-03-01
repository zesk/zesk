<?php

/**
 * @package zesk
 * @subpackage core
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2012, Market Acumen, Inc.
 */
namespace zesk;

if (!isset($GLOBALS['__composer_autoload_files'])) {
	$zesk_root = __DIR__;
	if (is_file($zesk_root . '/vendor/autoload.php')) {
		require_once $zesk_root . '/vendor/autoload.php';
	}
	$zesk = Kernel::singleton();
} else {
	$zesk = require_once __DIR__ . '/autoload.php';
}
/* @var $zesk Kernel */
$zesk->paths->set_application(__DIR__);

$application = $zesk->create_application();

$application->configure_include(array(
	"/etc/zesk.json",
	$application->path("/etc/zesk.json"),
	$application->path("etc/host/" . System::uname() . ".json"),
	$application->paths->uid("zesk.json")
));
$application->set_option("modules", array(
	"GitHub"
));
$application->configure();

$application->set_option("version", Version::release());

return $application;
