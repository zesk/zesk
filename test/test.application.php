<?php
/**
 * $URL: https://code.marketacumen.com/zesk/trunk/test/test.application.inc $
 * @package zesk
 * @subpackage core
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2012, Market Acumen, Inc.
 */
require_once __DIR__ . '/vendor/autoload.php';

$kernel = zesk\Kernel::factory();

$kernel->autoloader->no_exception = true;

$application = $kernel->create_application()->set_application_root(__DIR__);
$files = array(
	$application->path("etc/test.json"),
	$application->path("etc/test.conf"),
	$application->paths->uid("test.conf"),
	$application->paths->uid("test.json")
);
$application->configure_include($files)->configure();
