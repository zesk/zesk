#!/usr/bin/env php
<?php
/**
 * @version $URL: https://code.marketacumen.com/zesk/trunk/test/classes/net/http/server/Net_HTTP_Server_Exception.phpt $
 * @package zesk
 * @subpackage test
 * @author $Author: kent $
 * @copyright Copyright &copy; 2011, Market Acumen, Inc.
 */
if (!defined('ZESK_ROOT'))
	define('ZESK_ROOT', dirname(dirname(dirname(dirname(dirname(dirname(__FILE__)))))) . '/');
require_once ZESK_ROOT . 'zesk.inc';
Test_Unit::init();

$http_status = null;
$http_message = null;
$content = null;
$testx = new Net_HTTP_Server_Exception($http_status, $http_message, $content);

$testx->getMessage();

$testx->getCode();

$testx->getFile();

$testx->getLine();

$testx->getTrace();

$testx->getPrevious();

$testx->getTraceAsString();

$testx->__toString();

echo basename(__FILE__) . ": success\n";
