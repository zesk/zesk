<?php declare(strict_types=1);
namespace zesk;

class Base26_Test extends UnitTest {
	public function test_from_integer(): void {
		$i = null;
		$nChars = null;
		Base26::from_integer($i, $nChars);

		$this->assertEquals(Base26::from_integer(0, 1), 'A');
		$this->assertEquals(Base26::from_integer(0, 2), 'AA');
		$this->assertEquals(Base26::from_integer(0, 5), 'AAAAA');
		$this->assertEquals(Base26::from_integer(1, 5), 'AAAAB');
		$this->assertEquals(Base26::from_integer(4649370, 1), 'KENTY');
		$this->assertEquals(Base26::from_integer(4649370, 5), 'KENTY');
	}

	public function test_to_integer(): void {
		$this->assertEquals(Base26::to_integer('A'), 0);
		$this->assertEquals(Base26::to_integer('AA'), 0);
		$this->assertEquals(Base26::to_integer('AAAAAAAAAAAA'), 0);
		$this->assertEquals(Base26::to_integer('AAAAB'), 1);
		$this->assertEquals(Base26::to_integer('KENTY'), 4649370);
		$this->assertEquals(Base26::to_integer('KENTY'), 4649370);
	}
}
