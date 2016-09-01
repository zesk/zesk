<?php

if (!defined('FIREPHP_MODULE_PATH')) {
	define('FIREPHP_MODULE_PATH', dirname(__FILE__) . '/');
}
Module::register_paths();

zesk\Classes::register("Module_FirePHP");
