<?php
/**
 *
 */
namespace zesk;

/**
 *
 * @author kent
 *
 */
class Hexadecimal_Test extends Test_Unit {
	public function test_decode() {
		$decoded = Hexadecimal::decode("DEADBEEF");
		$x = null;
		$this->assert_arrays_equal(str_split($decoded), array(
			chr(222),
			chr(173),
			chr(190),
			chr(239),
		));
	}

	public function test_encode() {
		$x = null;
		Hexadecimal::encode($x);

		$this->assert_equal(Hexadecimal::encode(chr(222) . chr(173) . chr(190) . chr(239)), "DEADBEEF");
	}
}
