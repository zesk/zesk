<?php
if (!defined('ZESK_APPLICATION_ROOT')) {
	define('ZESK_APPLICATION_ROOT', dirname(__FILE__) . '/');
}

if (!defined('ZESK_ROOT')) {
	define('ZESK_ROOT', dirname(dirname(dirname(__FILE__))) . '/');
}

require_once ZESK_ROOT . 'zesk.inc';

zesk::autoload_path(path(dirname(__FILE__), 'classes'));

Application::instance()->configure();
