#!/usr/bin/env php
<?php
namespace zesk;

define("ZESK_ROOT", dirname(dirname(dirname(dirname(__FILE__)))) . "/");

if (file_exists(ZESK_ROOT . 'vendor/autoload.php')) {
	require_once ZESK_ROOT . 'vendor/autoload.php';
} else {
	require_once ZESK_ROOT . 'zesk.inc';
}

app()->modules->load("markdown");

CDN::add('/share/', '/share/', ZESK_ROOT . 'share/');

app()->objects->factory("zesk\\Command_Markdown")->go();
