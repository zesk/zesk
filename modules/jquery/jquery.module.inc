<?php
if (false) {
	$zesk = zesk();
	$application = app();
}
$application->module->register_paths();

$application->share_path(dirname(__FILE__) . '/share-tools', 'jquery-tools');