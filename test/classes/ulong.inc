<?php

namespace zesk;

class test_ulong extends Test_Unit {

	function test_ulong() {
		$x = 0;
		$testx = new ulong($x);

		$x = 1;
		$copy = false;
		ulong::to_ulong($x, $copy);

		$testx->get();

		$x = 51234;
		$testx->set($x);

		$n = 51234;
		$testx->byte($n);

		$x = 51234;
		$testx->add($x);

		$x = 51234;
		$testx->sub($x);

		$x = 51234;
		$testx->bit_and($x);

		$x = 51234;
		$testx->bit_or($x);

		$x = 51234;
		$testx->bit_xor($x);

		$n = 51234;
		$testx->lshift($n);

		$n = 51234;
		$testx->rshift($n);

		echo basename(__FILE__) . ": success\n";
	}

	function test_to_ulong() {
		$x = 1234123;
		$copy = false;
		ulong::to_ulong($x, $copy);
	}
}
