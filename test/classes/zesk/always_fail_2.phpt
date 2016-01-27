#!/usr/bin/env php
<?php
/**
 * $URL: https://code.marketacumen.com/zesk/trunk/test/classes/zesk/always_fail_2.phpt $
 * @package zesk
 * @subpackage test
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2008, Market Acumen, Inc.
 * @strict true
 */
echo base_convert(error_reporting(), 10, 2) . "\n";
echo base_convert(E_ALL, 10, 2) . "\n";
echo base_convert(E_STRICT, 10, 2) . "\n";
echo base_convert((E_STRICT | E_ALL), 10, 2) . "\n";

function zesk_inc() {
	$dir = dirname(__FILE__);
	while (!is_file("$dir/zesk.application.inc")) {
		$dir = dirname($dir);
	}
	require_once "$dir/zesk.inc";
}

zesk_inc();

Test_Unit::init();

class ZTest_A {

	public function doit($param = true) {
	}

	function find($where = false) {
		$where;
	}
}
class ZTest_B extends ZTest_A {

	public function doit($a = true, $b = false) {
	}
}
class ZTest_C extends ZTest_B {

	public function doit() {
	}

	function find($dude = null) {
		parent::find();
	}
}

$b = new ZTest_C();
// ALWAYS_FAIL due to strict errors


exit(0);
