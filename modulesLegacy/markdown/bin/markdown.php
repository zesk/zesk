#!/usr/bin/env php
<?php

namespace zesk;

define('ZESK_ROOT', dirname(__DIR__, 3) . '/');

if (file_exists(ZESK_ROOT . 'vendor/autoload.php')) {
	require_once ZESK_ROOT . 'vendor/autoload.php';
	$zesk = Kernel::singleton();
} else {
	$zesk = require_once ZESK_ROOT . 'autoload.php';
}

$app = $zesk->createApplication();
$app->setApplicationRoot($app->zeskHome());
$app->modules->load('markdown');

$app->objects->factory(Command_Markdown::class, $app)->go();
