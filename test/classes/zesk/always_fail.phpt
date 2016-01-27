#!/usr/bin/env php
<?php
/**
 * $URL: https://code.marketacumen.com/zesk/trunk/test/classes/zesk/always_fail.phpt $
 * @package zesk
 * @subpackage test
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2008, Market Acumen, Inc.
 */
if (!defined('ZESK_ROOT')) {
	define('ZESK_ROOT', dirname(dirname(dirname(__FILE__))) . '/');
}
require_once ZESK_ROOT . 'zesk.inc';
Test_Unit::init();

assert(false);

// ALWAYS_FAIL


exit(1);
