<?php
if (!defined('ZESK_SITE_ROOT')) {
	define('ZESK_SITE_ROOT', dirname(__FILE__) . '/');
}

require_once dirname(dirname(ZESK_SITE_ROOT)) . '/zesk.inc';

zesk::application_class('Application_IPBan');

zesk::autoload_path(ZESK_SITE_ROOT . "/classes");

$application = Application::instance();
$application->configure();

