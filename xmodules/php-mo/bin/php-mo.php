#!/usr/bin/env php
<?php
/* @var $zesk \zesk\Kernel */
$zesk = require_once dirname(dirname(dirname(dirname(__FILE__)))) . '/autoload.php';
$application = $zesk->create_application();
$application->modules->load('php-mo');
$zesk->objects->factory("Command_PHP_MO", $application)->go();
