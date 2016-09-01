<?php
/**
 * $URL: http://code.marketacumen.com/zesk/trunk/zesk.inc $
 * @package zesk
 * @subpackage core
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2012, Market Acumen, Inc.
 */
/* @var $zesk zesk\Kernel */
define('APPLICATION_NAME', 'zesk');

$zesk = require_once dirname(__FILE__) . '/zesk.inc';

Application::instance()->configure(array(
	"path" => array(
		$zesk->paths->application('etc'),
		$zesk->paths->uid()
	)
));
