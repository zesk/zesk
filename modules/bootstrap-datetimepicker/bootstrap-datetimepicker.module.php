<?php
if (false) {
	$zesk = zesk();
	$application = app();
}

$zesk->autoloader->path(__DIR__ . '/classes');
$application->theme_path(__DIR__ . '/theme');

