<?php
/**
 * $URL: http://code.marketacumen.com/zesk/trunk/zesk.inc $
 * @package zesk
 * @subpackage core
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2012, Market Acumen, Inc.
 */
require_once dirname(__FILE__) . '/zesk.inc';

define('APPLICATION_NAME', 'zesk');

zesk::set('Application::configuration_path', array(
	zesk::application_root('etc'),
	array_key_exists("HOME", $_SERVER) ? path($_SERVER['HOME'], '.zesk') : null
));

Application::instance()->configure();
