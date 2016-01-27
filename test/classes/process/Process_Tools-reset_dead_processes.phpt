#!/usr/bin/env php
<?php
/**
 * $URL: https://code.marketacumen.com/zesk/trunk/test/classes/process/Process_Tools-reset_dead_processes.phpt $
 * @package zesk
 * @subpackage test
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2010, Market Acumen, Inc.
 */
if (!defined('ZESK_ROOT')) define('ZESK_ROOT', dirname(dirname(dirname(dirname(__FILE__)))).'/');
require_once ZESK_ROOT . 'zesk.inc';
Test_Unit::init();

$table = null;
$where = false;
$pid_field = 'PID';
//TODO Process_Tools::reset_dead_processes($table, $where, $pid_field);
echo basename(__FILE__) . ": success\n";
