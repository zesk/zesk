#!/usr/bin/env php
<?php
/**
 * $URL: https://code.marketacumen.com/zesk/trunk/test/classes/database/class.DatabaseSchemaException.phpt $
 * @package zesk
 * @subpackage test
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2008, Market Acumen, Inc.
 */
if (!defined('ZESK_ROOT')) define('ZESK_ROOT', dirname(dirname(dirname(dirname(__FILE__)))).'/');
require_once ZESK_ROOT . 'zesk.inc';
Test_Unit::init();

$message = null;
$code = null;
$x = new Database_Exception_Schema($message, $code);

$x->getMessage();

$x->getCode();

$x->getFile();

$x->getLine();

$x->getTrace();

$x->getTraceAsString();

$x->__toString();

echo basename(__FILE__) . ": success\n";
