<?php

/**
 * $URL: http://code.marketacumen.com/zesk/trunk/zesk.inc $
 * @package zesk
 * @subpackage core
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2012, Market Acumen, Inc.
 */
namespace zesk;

if (!isset($GLOBALS['__composer_autoload_files'])) {
	$zesk_root = dirname(__FILE__);
	if (is_file($zesk_root . '/vendor/autoload.php')) {
		require_once $zesk_root . '/vendor/autoload.php';
	}
	$zesk = Kernel::singleton();
} else {
	$zesk = require_once dirname(__FILE__) . '/autoload.php';
}
/* @var $zesk Kernel */
$zesk->paths->set_application(dirname(__FILE__));

$application = $zesk->create_application();

$application->configure_include_path(array(
	'/etc',
	$zesk->paths->application('etc'),
	$zesk->paths->uid()
));
$application->configure_include(array(
	"zesk.json",
	"host/" . System::uname() . ".json"
));
$application->set_option("modules", array(
	"GitHub"
));
$application->configure();

$application->set_option("version", Version::release());

return $application;
