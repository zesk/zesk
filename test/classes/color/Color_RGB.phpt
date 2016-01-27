#!/usr/bin/env php
<?php
/**
 * $URL: https://code.marketacumen.com/zesk/trunk/test/classes/color/Color_RGB.phpt $
 * @package zesk
 * @subpackage test
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2008, Market Acumen, Inc.
 */
if (!defined('ZESK_ROOT')) {
	define('ZESK_ROOT', dirname(dirname(dirname(dirname(__FILE__)))).'/');
}
require_once ZESK_ROOT . 'zesk.inc';
Test_Unit::init();

$r = 1;
$g = 2;
$b = 255;
$x = new Color_RGB($r, $g, $b);

echo $x->__toString();
echo strval($x);

echo basename(__FILE__) . ": success\n";
