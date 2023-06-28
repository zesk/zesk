<?php
declare(strict_types=1);
namespace zesk;

class Base26Test extends UnitTest
{
	public function test_from_integer(): void
	{
		$this->assertEquals('AAAAAC', Base26::fromInteger(2, 6));

		$this->assertEquals(Base26::fromInteger(0, 1), 'A');
		$this->assertEquals(Base26::fromInteger(0, 2), 'AA');
		$this->assertEquals(Base26::fromInteger(0, 5), 'AAAAA');
		$this->assertEquals(Base26::fromInteger(1, 5), 'AAAAB');
		$this->assertEquals(Base26::fromInteger(4649370, 1), 'KENTY');
		$this->assertEquals(Base26::fromInteger(4649370, 5), 'KENTY');
	}

	public function test_to_integer(): void
	{
		$this->assertEquals(Base26::toInteger('A'), 0);
		$this->assertEquals(Base26::toInteger('AA'), 0);
		$this->assertEquals(Base26::toInteger('AAAAAAAAAAAA'), 0);
		$this->assertEquals(Base26::toInteger('AAAAB'), 1);
		$this->assertEquals(Base26::toInteger('KENTY'), 4649370);
		$this->assertEquals(Base26::toInteger('KENTY'), 4649370);
	}
}
