<?php
declare(strict_types=1);
/**
 * @copyright &copy; 2025, Market Acumen, Inc.
 */

namespace testZesk;

use zesk\Base26;

class Base26Test extends TestCase
{
	public function test_from_integer(): void
	{
		$this->assertEquals('AAAAAC', Base26::fromInteger(2, 6));

		$this->assertEquals('A', Base26::fromInteger(0, 1));
		$this->assertEquals('AA', Base26::fromInteger(0, 2));
		$this->assertEquals('AAAAA', Base26::fromInteger(0, 5));
		$this->assertEquals('AAAAB', Base26::fromInteger(1, 5));
		$this->assertEquals('KENTY', Base26::fromInteger(4649370, 1));
		$this->assertEquals('KENTY', Base26::fromInteger(4649370, 5));
	}

	public function test_to_integer(): void
	{
		$this->assertEquals(0, Base26::toInteger('A'));
		$this->assertEquals(0, Base26::toInteger('AA'));
		$this->assertEquals(0, Base26::toInteger('AAAAAAAAAAAA'));
		$this->assertEquals(1, Base26::toInteger('AAAAB'));
		$this->assertEquals(4649370, Base26::toInteger('KENTY'));
		$this->assertEquals(4649370, Base26::toInteger('KENTY'));
	}
}
