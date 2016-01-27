#!/usr/bin/env php
<?php
/**
 * @version $URL: https://code.marketacumen.com/zesk/trunk/test/classes/net/http/server/Net_HTTP_Server_Request.phpt $
 * @package zesk
 * @subpackage test
 * @author $Author: kent $
 * @copyright Copyright &copy; 2011, Market Acumen, Inc.
 */
if (!defined('ZESK_ROOT')) {
	define('ZESK_ROOT', dirname(dirname(dirname(dirname(dirname(dirname(__FILE__)))))) . '/');
}
require_once ZESK_ROOT . 'zesk.inc';
Test_Unit::init();

$raw_request = "GET / HTTP/1.0\r\nHost: localhost\r\n\r\ndata";
$testx = new Net_HTTP_Server_Request($raw_request);

$name = "header";
$testx->header($name);

$testx->set_globals();

echo basename(__FILE__) . ": success\n";
