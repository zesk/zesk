<?php
declare(strict_types=1);
namespace zesk;

class UnsignedLongTest extends UnitTest {
	public function test_ulong(): void {
		$x = 0;
		$testx = new UnsignedLong($x);

		$x = 1;
		$copy = false;
		UnsignedLong::factory($x, $copy);

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

		$n = 4;
		$testx->leftShift($n);

		$n = 4;
		$testx->rightShift($n);
	}

	public function test_to_ulong(): void {
		$x = 1234123;
		$copy = false;
		UnsignedLong::factory($x, $copy);
	}
}
