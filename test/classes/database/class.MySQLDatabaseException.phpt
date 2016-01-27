#!/usr/bin/env php
<?php
/**
 * $URL: https://code.marketacumen.com/zesk/trunk/test/classes/database/class.MySQLDatabaseException.phpt $
 * @package zesk
 * @subpackage test
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2008, Market Acumen, Inc.
 */
if (!defined('ZESK_ROOT')) define('ZESK_ROOT', dirname(dirname(dirname(dirname(__FILE__)))).'/');
require_once ZESK_ROOT . 'zesk.inc';
Test_Unit::init();

$message = null;
$query = null;
$mysql_res = false;
$x = new Database_Exception_MySQL($message, $query, $mysql_res);

$x->__toString();

$x->getMessage();

$x->getCode();

$x->getFile();

$x->getLine();

$x->getTrace();

$x->getTraceAsString();

echo basename(__FILE__) . ": success\n";
