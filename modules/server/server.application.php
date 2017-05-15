<?php
if (!defined('ZESK_APPLICATION_ROOT')) {
	define('ZESK_APPLICATION_ROOT', dirname(__FILE__) . '/');
}

$zesk = require_once dirname(dirname(ZESK_APPLICATION_ROOT)) . "/autoload.php";

/* @var $zesk \zesk\Kernel */

$zesk->application_class = "zesk\\Application_Server";

return \zesk\Application::instance()->configure();

