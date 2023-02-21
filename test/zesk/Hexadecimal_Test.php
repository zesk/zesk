<?php
declare(strict_types=1);
/**
 *
 */
namespace zesk;

/**
 *
 * @author kent
 *
 */
class Hexadecimal_Test extends UnitTest {
	public function test_decode(): void {
		$decoded = Hexadecimal::decode('DEADBEEF');
		$x = null;
		$this->assertEquals(str_split($decoded), [
			chr(222),
			chr(173),
			chr(190),
			chr(239),
		]);
	}

	public function test_encode(): void {
		$this->assertEquals(Hexadecimal::encode(chr(222) . chr(173) . chr(190) . chr(239)), 'DEADBEEF');
	}
}
