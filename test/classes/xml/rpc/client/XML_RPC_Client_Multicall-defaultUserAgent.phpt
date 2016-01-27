#!/usr/bin/env php
<?php
/**
 * $URL: https://code.marketacumen.com/zesk/trunk/test/classes/xml/rpc/client/XML_RPC_Client_Multicall-defaultUserAgent.phpt $
 * @package zesk
 * @subpackage test
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2010, Market Acumen, Inc.
 */
if (!defined('ZESK_ROOT')) define('ZESK_ROOT', dirname(dirname(dirname(dirname(dirname(dirname(__FILE__)))))).'/');
require_once ZESK_ROOT . 'zesk.inc';
Test_Unit::init();

XML_RPC_Client_Multicall::defaultUserAgent();
echo basename(__FILE__) . ": success\n";
