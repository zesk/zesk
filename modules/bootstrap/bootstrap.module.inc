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

$application->share_path(dirname(__FILE__) . '/share-x', 'bootstrap-x');

zesk::set("Module_jQuery::composer_version", array(
	'components/jquery' => ">=1.9.1 <3.0"
));