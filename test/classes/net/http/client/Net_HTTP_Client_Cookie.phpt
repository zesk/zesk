#!/usr/bin/env php
<?php
/**
 * $URL: https://code.marketacumen.com/zesk/trunk/test/classes/net/http/client/Net_HTTP_Client_Cookie.phpt $
 * @package zesk
 * @subpackage test
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2008, Market Acumen, Inc.
 */
if (!defined('ZESK_ROOT')) define('ZESK_ROOT', dirname(dirname(dirname(dirname(dirname(dirname(__FILE__)))))).'/');
require_once ZESK_ROOT . 'zesk.inc';
Test_Unit::init();

$name = null;
$value = null;
$domain = null;
$path = null;
$expires = false;
$secure = false;
$x = new Net_HTTP_Client_Cookie($name, $value, $domain, $path, $expires, $secure);

$x->name();

$x->value();

$x->isSecure();

$expires = null;
$x->setExpires($expires);

$domain = null;
$path = null;
$x->matches($domain, $path);

$value = null;
$expires = false;
$x->update($value, $expires);

echo basename(__FILE__) . ": success\n";
