<?php
/**
 * $URL: https://code.marketacumen.com/zesk/trunk/test/test.application.inc $
 * @package zesk
 * @subpackage core
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2012, Market Acumen, Inc.
 */
/* @var $zesk zesk\Kernel */
$zesk = require_once dirname(dirname(__FILE__)) . '/zesk.inc';

define('APPLICATION_NAME', 'test');

$zesk->autoloader->no_exception = true;

Application::instance()->configure(array(
	"path" => array(
		$zesk->paths->application("etc"),
		$zesk->paths->uid()
	)
));
