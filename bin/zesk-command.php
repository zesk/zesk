#!/usr/bin/env php
<?php
/**
 * Very important: Do not call any Zesk calls until after application laods.
 *
 * @package zesk
 * @subpackage bin
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2018, Market Acumen, Inc.
 */
define('ZESK_ROOT', dirname(dirname(__FILE__)) . '/' . (strpos(__FILE__, 'vendor/bin') !== false ? 'zesk/zesk/' : ''));

/**
 * Load the bare minimum
 */
require_once ZESK_ROOT . 'classes/functions.php';
require_once ZESK_ROOT . 'classes/Command/Loader.php';

/**
 * Run a zesk command and exit
 */
exit(zesk\Command_Loader::factory()->run());
