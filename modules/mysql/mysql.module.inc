<?php
if (false) {
	$zesk = zesk();
	$application = app();
}

$application->modules->register_paths();

$zesk->classes->register("mysql\Database");
$zesk->hooks->register_class('mysql\Database');
