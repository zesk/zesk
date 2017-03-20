<?php

if (!defined('ZESK_APPLICATION_ROOT')) {
	define('ZESK_APPLICATION_ROOT', dirname(__FILE__) . '/');
}

require_once dirname(dirname(ZESK_APPLICATION_ROOT)) . "/zesk.inc";

Application_Server::configure();

