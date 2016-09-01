<?php
if (false) {
	$zesk = zesk();
	$application = app();
}

$application->module->register_paths();

$zesk->classes->register("sqlite3\Database");
$zesk->hooks->register_class('sqlite3\Database');
