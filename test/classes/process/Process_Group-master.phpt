#!/usr/bin/env php
<?php
/**
 * $URL: https://code.marketacumen.com/zesk/trunk/test/classes/process/Process_Group-master.phpt $
 * @package zesk
 * @subpackage test
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2010, Market Acumen, Inc.
 */
if (!defined('ZESK_ROOT')) define('ZESK_ROOT', dirname(dirname(dirname(dirname(__FILE__)))).'/');
require_once ZESK_ROOT . 'zesk.inc';
Test_Unit::init();

$name = false;
Process_Group::master($name);
echo basename(__FILE__) . ": success\n";
