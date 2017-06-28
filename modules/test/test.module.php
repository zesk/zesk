<?php
backtrace();

/* @var $application \zesk\Application */
define('ZESK_MODULE_TEST_ROOT', dirname(__FILE__) . '/');

$application->autoload_path(ZESK_MODULE_TEST_ROOT . 'classes');
$application->theme_path(ZESK_MODULE_TEST_ROOT . 'theme');
