<?php
if (false) {
	$zesk = zesk();
	$application = app();
}

$application->module->register_paths();

$zesk->classes->register("mysql\Database");
$zesk->hooks->register_class('mysql\Database');
