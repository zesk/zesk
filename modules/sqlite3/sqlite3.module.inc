<?php
if (false) {
	$zesk = zesk();
	$application = app();
}

$application->modules->register_paths();

$application->register_class("sqlite3\Database");
