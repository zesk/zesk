#!/usr/bin/env php
<?php
namespace zesk;

define("ZESK_ROOT", dirname(dirname(dirname(dirname(__FILE__)))) . "/");

if (file_exists(ZESK_ROOT . 'vendor/autoload.php')) {
	require_once ZESK_ROOT . 'vendor/autoload.php';
	$zesk = Kernel::singleton();
} else {
	$zesk = require_once ZESK_ROOT . 'autoload.php';
}

$app = $zesk->create_application();
$app->set_application_root(ZESK_ROOT);
$app->modules->load("markdown");

$app->objects->factory("zesk\\Command_Markdown", $app)->go();
