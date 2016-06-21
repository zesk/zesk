<?php
/**
 * Main entry point for Bootstrap module
 *
 * Register how to find things
 */
if (false) {
	$application = Application::instance();
}
$application->autoload_path(dirname(__FILE__) . '/classes');
$application->theme_path(dirname(__FILE__) . '/theme');

$application->share_path(dirname(__FILE__) . '/share', 'bootstrap-x');

