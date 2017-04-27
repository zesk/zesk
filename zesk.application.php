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
	$zesk = zesk();
} else {
	/* @var $zesk zesk\Kernel */
	$zesk = require_once dirname(__FILE__) . '/zesk.inc';
}
return Application::instance()->configure(array(
	"path" => array(
		$zesk->paths->application('etc'),
		$zesk->paths->uid()
	)
));
