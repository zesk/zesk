<?php
/**
 * 
 */
if (false) {
	$zesk = zesk();
	$application = app();
}

$application->module->register_paths();
$zesk->classes->register('Module_Cron');
