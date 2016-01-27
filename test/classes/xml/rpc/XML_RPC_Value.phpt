#!/usr/bin/env php
<?php
/**
 * $URL: https://code.marketacumen.com/zesk/trunk/test/classes/xml/rpc/XML_RPC_Value.phpt $
 * @package zesk
 * @subpackage test
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2008, Market Acumen, Inc.
 */
if (!defined('ZESK_ROOT')) define('ZESK_ROOT', dirname(dirname(dirname(dirname(dirname(__FILE__))))).'/');
require_once ZESK_ROOT . 'zesk.inc';
Test_Unit::init();

$data = null;
$type = false;
$x = new XML_RPC_Value($data, $type);

$x->toXML();

echo basename(__FILE__) . ": success\n";
