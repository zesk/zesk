#!/usr/bin/env php
<?php
/**
 * $URL: https://code.marketacumen.com/zesk/trunk/test/classes/exception/Exception_FileSystem.phpt $
 * 
 * @package zesk
 * @subpackage test
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2010, Market Acumen, Inc.
 */
if (!defined('ZESK_ROOT'))
	define('ZESK_ROOT', dirname(dirname(dirname(dirname(__FILE__)))) . '/');
require_once ZESK_ROOT . 'zesk.inc';

Test_Unit::init();

$filename = null;
$message = '';
$arguments = array();
$code = 0;
$testx = new Exception_FileSystem($filename, $message, $arguments, $code);

$testx->getMessage();

$testx->getCode();

$testx->getFile();

$testx->getLine();

$testx->getTrace();

$testx->getPrevious();

$testx->getTraceAsString();

$testx->__toString();

echo basename(__FILE__) . ": success\n";
