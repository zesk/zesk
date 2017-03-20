#!/usr/bin/env php
<?php
/**
 * $URL: https://code.marketacumen.com/zesk/trunk/modules/xmlrpc/test/XML_RPC_Server.phpt $
 * @package zesk
 * @subpackage test
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2008, Market Acumen, Inc.
 */
if (!defined('ZESK_ROOT')) define('ZESK_ROOT', dirname(dirname(dirname(dirname(dirname(__FILE__))))).'/');
require_once ZESK_ROOT . 'zesk.inc';
Test_Unit::init();

$methods = false;
$x = new XML_RPC_Server($methods);

$data = false;
$x->serve($data);

$methodName = null;
$args = null;
$x->call($methodName, $args);

$error = null;
$message = false;
$x->error($error, $message);

$xml = null;
$x->response($xml);

$method = null;
$x->hasMethod($method);

$arr = null;
$x->registerMethods($arr);

$name = null;
$returnType = null;
$phpMethod = null;
$parameterTypes = false;
$help = false;
$parameterHelp = false;
$x->registerMethod($name, $returnType, $phpMethod, $parameterTypes, $help, $parameterHelp);

$name = null;
$specURL = null;
$specVersion = 1;
$x->addCapability($name, $specURL, $specVersion);

$x->rpc_getCapabilities();

$x->rpc_listMethods();

$methodCalls = null;
$x->rpc_multicall($methodCalls);

$method = null;
$x->rpc_methodSignature($method);

$method = null;
$x->rpc_methodHelp($method);

echo basename(__FILE__) . ": success\n";
