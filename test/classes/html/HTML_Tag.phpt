#!/usr/bin/env php
<?php
/**
 * $URL: https://code.marketacumen.com/zesk/trunk/test/classes/html/HTML_Tag.phpt $
 * @package zesk
 * @subpackage test
 * @author $Author: kent $
 * @copyright Copyright &copy; 2010, Market Acumen, Inc.
 */
if (!defined('ZESK_ROOT')) define('ZESK_ROOT', dirname(dirname(dirname(dirname(__FILE__)))).'/');
require_once ZESK_ROOT . 'zesk.inc';

Test_Unit::init();

$name = null;
$attributes = false;
$contents = false;
$testx = new HTML_Tag($name, $attributes, $contents);

$testx->contents();

$testx->inner_html();

$contents = null;
$testx->inner_html($contents);

$testx->outer_html();

$testx->outer_html("<tag>");

echo basename(__FILE__) . ": success\n";
