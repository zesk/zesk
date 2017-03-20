<?php
class test_utf16 extends Test_Unit {

	function test_decode() {
		$str = null;
		$be = null;
		UTF16::decode($str, $be);
		echo basename(__FILE__) . ": success\n";
	}

	function test_encode() {
		$str = null;
		$be = true;
		$add_bom = true;
		UTF16::encode($str, $be, $add_bom);
		echo basename(__FILE__) . ": success\n";
	}

	function test_to_iso8859() {
		$mixed = null;
		$be = null;
		UTF16::to_iso8859($mixed, $be);
		echo basename(__FILE__) . ": success\n";
	}

	function test_to_utf8() {
		$str = null;
		$be = null;
		UTF16::to_utf8($str, $be);
		echo basename(__FILE__) . ": success\n";
	}
}
