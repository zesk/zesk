<?php

namespace zesk;

class test_utf8 extends Test_Unit {

	function test_to_iso8859() {
		$mixed = null;
		UTF8::to_iso8859($mixed);
	}

	function test_from_charset() {
		$mixed = null;
		$charset = null;
		UTF8::from_charset($mixed, "iso-8859-1");
	}
}
