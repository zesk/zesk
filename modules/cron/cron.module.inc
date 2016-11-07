<?php
/**
 * 
 */
if (false) {
	$zesk = zesk();
	$application = app();
}

$application->modules->register_paths();
$zesk->classes->register('Module_Cron');
