<?php
/**
 * @version $URL: https://code.marketacumen.com/zesk/trunk/modules/markdown/markdown.module.inc $
 */
if (false) {
	$application = Application::instance();
}
define("ZESK_MODULE_MARKDOWN_ROOT", dirname(__FILE__) . "/");

$application->autoload_path(ZESK_MODULE_MARKDOWN_ROOT . "classes");
$application->zesk_command_path(ZESK_MODULE_MARKDOWN_ROOT . "classes/command");

$application->share_path(ZESK_MODULE_MARKDOWN_ROOT . 'share', 'markdown');
$application->theme_path(ZESK_MODULE_MARKDOWN_ROOT . 'theme');

