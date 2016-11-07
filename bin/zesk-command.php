#!/usr/bin/env php
<?php
/**
 * Very important: Do not call any Zesk calls until after application laods.
 *
 * $URL$
 *
 * @package zesk
 * @subpackage bin
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2011, Market Acumen, Inc.
 */
define('ZESK_ROOT', dirname(dirname(__FILE__)) . '/' . (strpos(__FILE__, 'vendor/bin') !== false ? 'zesk/zesk/' : ''));

/**
 * Load the bare minimum
 */
require_once ZESK_ROOT . 'classes/zesk/functions.inc';
require_once ZESK_ROOT . 'classes/zesk/command/loader.inc';

/**
 * Run a zesk command and exit
 */
exit(zesk\Command_Loader::instance()->run());
