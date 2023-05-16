<?php
declare(strict_types=1);

namespace zesk;

use zesk\Exception\ParameterException;

/**
 *
 */
class IPv6Test extends UnitTest {
	static public function dataFromIPv4(): array {
		return [
			['::ffff:192.168.0.200', 3232235720], ['::ffff:127.0.0.1', ip2long('127.0.0.1')],
			['::ffff:192.168.0.200', 3232235720],
		];
	}

	/**
	 * @dataProvider dataFromIPv4
	 * @param string $expected
	 * @param int $ip4
	 * @return void
	 */
	public function testFromIPv4(string $expected, int $ip4): void {
		$this->assertEquals($expected, IPv6::fromIPv4($ip4), "IPv6::fromIPv4($ip4)");
		$this->assertTrue(IPv6::isIPv4($expected), "$expected MUST be a valid IP4 in IP6 address");
	}


	public static function dataValid(): array {
		return [
			[true, '::0'], [true, '0::0'], [true, '0000::0000'], [true, '0:0:0:0:0:0:0:0'],
			[true, '0000:0000:0000:0000:0000:0000:0000:0000'], [false, '0000:0000:0000:0000:0000:0000:0000:0000:'],
			[false, '10000:0000:0000:0000:0000:0000:0000:0000'], [false, 'a10000:0000:0000:0000:0000:0000:0000:0000'],
			[false, 'a10000'],
		];
	}

	/**
	 * @param bool $valid
	 * @param string $address
	 * @return void
	 * @dataProvider dataValid
	 */
	public function testValid(bool $valid, string $address): void {
		$this->assertEquals($valid, IPv6::valid($address));
	}

	public static function dataFromBinary(): array {
		$expectedLength = Ipv6::BINARY_COLUMN_LENGTH;
		for ($i = 0; $i < 9; $i++) {
			$tests[$i] = [
				substr(str_repeat("$i:", $expectedLength / 2), 0, -1),
				str_repeat(chr(0) . chr($i), $expectedLength / 2),
			];
		}
		$tests[0][0] = '::';
		$tests[] = ["", str_repeat("a", $expectedLength + 1)];
		$tests[] = ["", str_repeat("a", 1)];
		$tests[] = ["", str_repeat("a", $expectedLength - 1)];
		$tests[] = ["", str_repeat("a", $expectedLength * 2)];
		$tests[] = ["", ""];
		return $tests;
	}

	/**
	 * @param string $result
	 * @param string $address
	 * @return void
	 * @dataProvider dataFromBinary
	 */
	public function testFromBinary(string $result, string $address): void {
		if ($result === "") {
			$this->expectException(ParameterException::class);
		}
		$this->assertEquals($result, IPv6::fromBinary($address));
	}

	/**
	 * @param string $expected
	 * @param string $ip
	 * @return void
	 * @dataProvider dataExpand
	 */
	public function testExpand(string $expected, string $ip): void {
		$this->assertEquals($expected, IPv6::expand($ip));
	}

	public static function dataExpand(): array {
		return [
			['0000:0000:0000:0000:0000:0000:0000:0000', '::'], ['0000:0000:0000:0000:0000:0000:0000:0000', '0::0'],
			['0000:0000:0000:0000:0000:0000:0000:0000', '0:0::0'],
			['0000:0000:0000:0000:0000:0000:0000:0000', '0:0::0:0'],
			['0000:0000:0000:0000:0000:0000:0000:0000', '0:0::0.0.0.0'],
			['0000:0000:0000:0000:0000:0000:0000:0000', '0:0:0:0:0:0:0.0.0.0'],
		];
	}

	public static function dataIsIPv4(): array {
		return [
			[true, '::ffff:192.168.0.200'], [true, '::ffff:127.0.0.1'], [true, '0:0:0:0:0:ffff:192.168.0.200'],
			[true, '0000:0000:0000:0000:0000:FFFF:192.168.0.200'],
		];
	}

	/**
	 * @dataProvider dataIsIPv4
	 * @param bool $expected
	 * @param string $ip6
	 * @return void
	 */
	public function testIsIPv4(bool $expected, string $ip6): void {
		$this->assertTrue(IPv6::valid($ip6), "IPv6::valid($ip6)");
		$this->assertEquals($expected, IPv6::isIPv4($ip6));
	}

	static public function dataSimplify(): array {
		return [
			['::ffff:192.168.0.200', '::ffff:192.168.0.200'], ['::ffff:127.0.0.1', '::ffff:127.0.0.1'],
			['::ffff:192.168.0.200', '0:0:0:0:0:ffff:192.168.0.200'],
			['::ffff:192.168.0.200', '0000:0000:0000:0000:0000:FFFF:192.168.0.200'],
		];
	}

	/**
	 * @param string $expected
	 * @param string $ip6
	 * @return void
	 * @dataProvider dataSimplify
	 */
	public function testSimplify(string $expected, string $ip6): void {
		$this->assertEquals($expected, Ipv6::simplify($ip6));
	}

	public static function dataClean(): array {
		return [
			['d:e:a:d:b:e:e:f', 'Dist:Exp%:Assign:[]Druix:BlooPY^:Ex:Ex:Filtry(--)'],
		];
	}

	/**
	 * @param string $expected
	 * @param string $address
	 * @return void
	 * @dataProvider dataClean
	 */
	public function testClean(string $expected, string $address): void {
		$this->assertEquals($expected, IPv6::clean($address));
		$this->assertTrue(IPv6::valid(IPv6::clean($address)));
	}
}
