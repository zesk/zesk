<?php
declare(strict_types=1);

namespace test\zesk;

class UnsignedLongTestCase extends TestCase
{
	public function test_ulong(): void
	{
		$x = 0;
		$testObject = new UnsignedLong($x);

		$x = 1;
		$copy = false;
		UnsignedLong::factory($x, $copy);

		$testObject->get();

		$x = 51234;
		$testObject->set($x);

		$n = 51234;
		$testObject->byte($n);

		$x = 51234;
		$testObject->add($x);

		$x = 51234;
		$testObject->sub($x);

		$x = 51234;
		$testObject->bit_and($x);

		$x = 51234;
		$testObject->bit_or($x);

		$x = 51234;
		$testObject->bit_xor($x);

		$n = 4;
		$testObject->leftShift($n);

		$n = 4;
		$testObject->rightShift($n);
	}

	public function test_to_ulong(): void
	{
		$x = 1234123;
		$copy = false;
		UnsignedLong::factory($x, $copy);
	}
}
