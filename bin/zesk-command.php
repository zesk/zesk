#!/usr/bin/env php -d display_startup_errors=off
<?php
/**
 * Very important: Do not call any Zesk calls until after application laods.
 *
 * 2019-03-04 - Added "display_startup_errors=off" above as when zesk invoked via zesk\WebApp\Type_Zesk - it would use
 * an alternate PHP version and display a ton of shared library errors in the version (Mac OS X/ZendServer). So adding the above
 * hid those errors from output. Probably not a bad practice. -KMD
 *
 * @package zesk
 * @subpackage bin
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2019, Market Acumen, Inc.
 */
define('ZESK_ROOT', dirname(__DIR__) . '/' . (strpos(__FILE__, 'vendor/bin') !== false ? 'zesk/zesk/' : ''));

/**
 * Load the bare minimum
 */
require_once ZESK_ROOT . 'classes/functions.php';
require_once ZESK_ROOT . 'classes/Command/Loader.php';

/**
 * Run a zesk command and exit
 */
exit(zesk\Command_Loader::factory()->run());
