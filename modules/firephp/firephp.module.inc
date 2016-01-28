<?php

if (!defined('FIREPHP_MODULE_PATH')) {
	define('FIREPHP_MODULE_PATH', dirname(__FILE__) . '/');
}
Module::register_paths();

zesk::register_class("Module_FirePHP");
