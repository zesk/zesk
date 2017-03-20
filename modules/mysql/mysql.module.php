<?php
if (false) {
	$zesk = zesk();
	$application = app();
}

$application->modules->register_paths();

$zesk->classes->register("MySQL\\Database");
$zesk->hooks->register_class("MySQL\\Database");
