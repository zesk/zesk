#!/usr/bin/env php
<?php
/**
 * $URL: https://code.marketacumen.com/zesk/trunk/test/classes/database/exception/Database_Exception_Schema.phpt $
 * @package zesk
 * @subpackage test
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2010, Market Acumen, Inc.
 */
if (!defined('ZESK_ROOT')) define('ZESK_ROOT', dirname(dirname(dirname(dirname(dirname(__FILE__))))).'/');
require_once ZESK_ROOT . 'zesk.inc';

Test_Unit::init();

$db = null;
$sql = null;
$errno = 0;
$message = null;
$testx = new Database_Exception_Schema($db, $sql, $errno);

$testx->__toString();

$testx->getMessage();

$testx->getCode();

$testx->getFile();

$testx->getLine();

$testx->getTrace();

$testx->getPrevious();

$testx->getTraceAsString();

echo basename(__FILE__) . ": success\n";
