<?php
if (!defined('ZESK_SITE_ROOT')) {
	define('ZESK_SITE_ROOT', dirname(__FILE__) . '/');
}

/* @var $zesk \zesk\Kernel */
$zesk = require_once dirname(dirname(ZESK_SITE_ROOT)) . '/autoload.php';

$zesk->application_class = 'Application_IPBan';
$zesk->autoloader->path(ZESK_SITE_ROOT . "/classes");

return app()->configure();

