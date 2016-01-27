<?php
class test_hex extends Test_Unit {

	function test_decode() {
		$decoded = hex::decode("DEADBEEF");
		dump($decoded);
		dump(str::explode_chars($decoded));
		$x = null;
		$this->assert_arrays_equal(str::explode_chars($decoded), array(
			chr(222),
			chr(173),
			chr(190),
			chr(239)
		));
	}

	function test_encode() {
		$x = null;
		hex::encode($x);

		$this->assert_equal(hex::encode(chr(222) . chr(173) . chr(190) . chr(239)), "DEADBEEF");
	}
}
