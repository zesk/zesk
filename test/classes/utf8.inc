<?php
class test_utf8 extends Test_Unit {

	function test_to_iso8859() {
		$mixed = null;
		utf8::to_iso8859($mixed);
	}

	function test_from_charset() {
		$mixed = null;
		$charset = null;
		utf8::from_charset($mixed, "iso-8859-1");
	}
}
