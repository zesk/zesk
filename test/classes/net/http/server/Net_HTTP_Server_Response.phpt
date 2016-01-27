#!/usr/bin/env php
<?php
/**
 * @version $URL: https://code.marketacumen.com/zesk/trunk/test/classes/net/http/server/Net_HTTP_Server_Response.phpt $
 * @package zesk
 * @subpackage test
 * @author $Author: kent $
 * @copyright Copyright &copy; 2011, Market Acumen, Inc.
 */
if (!defined('ZESK_ROOT')) define('ZESK_ROOT', dirname(dirname(dirname(dirname(dirname(dirname(__FILE__)))))).'/');
require_once ZESK_ROOT . 'zesk.inc';
Test_Unit::init();

$testx = new Net_HTTP_Server_Response();

$name = "Location";
$value = "/";
$replace = false;
$testx->header($name, $value, $replace);

$testx->raw_headers();

$filename = null;
$testx->filename($filename);

$testx->file();

$testx->close_file();

$testx->content();

echo basename(__FILE__) . ": success\n";
