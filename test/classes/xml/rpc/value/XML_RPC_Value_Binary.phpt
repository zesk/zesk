#!/usr/bin/env php
<?php
/**
 * $URL: https://code.marketacumen.com/zesk/trunk/test/classes/xml/rpc/value/XML_RPC_Value_Binary.phpt $
 * @package zesk
 * @subpackage test
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2008, Market Acumen, Inc.
 */
if (!defined('ZESK_ROOT')) define('ZESK_ROOT', dirname(dirname(dirname(dirname(dirname(dirname(__FILE__)))))).'/');
require_once ZESK_ROOT . 'zesk.inc';
Test_Unit::init();

$data = null;
$isXML = false;
$x = new XML_RPC_Value_Binary($data, $isXML);

$x->toXML();

$value = null;
$x->fromXML($value);

echo basename(__FILE__) . ": success\n";
