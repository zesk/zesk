<?php
declare(strict_types=1);
/**
 * @copyright &copy; 2025, Market Acumen, Inc.
 */

namespace testZesk;

use zesk\IPv4;

use zesk\Request;

/**
 * @uses IPv4
 * @coversDefaultClass \zesk\IPv4
 */
class IPv4Test extends TestCase
{
	public static function dataIsMask(): array
	{
		$results = [
			[
				false,
				'-1.0,0,0',
			],
			[
				false,
				'-1.0.0.0.0',
			],
			[
				false,
				'1.0.0.0.0',
			],
			[
				false,
				'0.0,0,0',
			],
			// 0.0 comma 0 comma 0 is not
			[
				false,
				'0.0.0.0/0',
			],
			[
				false,
				'0.0.0.0/1',
			],
			[
				false,
				'0.0.0.0/7',
			],
			[
				false,
				'0.0.0.0/33',
			],
			[
				true,
				'0.0.0.*',
			],
			[
				true,
				'0.0.*',
			],
			[
				true,
				'0.*',
			],
			[
				true,
				'255.*',
			],
			[
				false,
				'256.*',
			],
			[
				false,
				'-1.*',
			],
			[
				false,
				'1.*/32',
			],
			[
				false,
				'256.*',
			],
			[
				false,
				'255.255.255.256/32',
			],
			[
				false,
				'255.255.255.256/23',
			],
			[
				true,
				'192.128.0.0/9',
			],

		];
		for ($i = 8; $i < 32; $i++) {
			$results[] = [
				true,
				"1.0.0.0/$i",
			];
		}
		return $results;
	}

	/**
	 * @return void
	 * @covers ::fromInteger
	 */
	public function test_from_integer(): void
	{
		$this->assertEquals('192.168.0.200', IPv4::fromInteger(3232235720));
		$this->assertEquals('192.168.0.200', IPv4::fromInteger('3232235720'));
	}

	/**
	 * @return void
	 * @covers ::isMask
	 * @dataProvider dataIsMask
	 */
	public function test_isMask(bool $expected, string $testValue): void
	{
		$this->assertEquals($expected, IPv4::isMask($testValue));
	}

	public static function data_maskToIntegers(): array
	{
		return [
			[
				[
					null,
					null,
				],
				'127.0.0.2.3',
			],
			[
				[
					null,
					null,
				],
				'256.0.0',
			],
			[
				[
					null,
					null,
				],
				'1.0.-4',
			],
			[
				[
					null,
					null,
				],
				'1.0.2343',
			],
			[
				[
					null,
					null,
				],
				'1.*.2343',
			],
			[
				[
					null,
					null,
				],
				'127.0.0.1/33',
			],
			[
				[
					2130706433.0,
					32,
				],
				'127.0.0.1',
			],
			[
				[
					2130706432.0,
					24,
				],
				'127.0.0',
			],
			[
				[
					2130706432.0,
					16,
				],
				'127.0',
			],
			[
				[
					2130706432.0,
					8,
				],
				'127',
			],
			[
				[
					2130706432.0,
					24,
				],
				'127.0.0.1/24',
			],
			[
				[
					2130706432.0,
					16,
				],
				'127.0.0.1/16',
			],
			[
				[
					167772160.0,
					8,
				],
				'10.0.0.1/8',
			],
			[
				[
					167772160.0,
					16,
				],
				'10.0.0.1/16',
			],
			[
				[
					167772160.0,
					24,
				],
				'10.0.0.1/24',
			],
			[
				[
					167772160.0,
					25,
				],
				'10.0.0.1/25',
			],
			[
				[
					167772160.0,
					31,
				],
				'10.0.0.1/31',
			],
			[
				[
					167772161.0,
					32,
				],
				'10.0.0.1/32',
			],
		];
	}

	/**
	 * @param array $expected
	 * @param string $string
	 * @return void
	 * @dataProvider data_maskToIntegers
	 * @covers ::maskToIntegers
	 */
	public function test_maskToIntegers(array $expected, string $string): void
	{
		$this->assertEquals($expected, IPv4::maskToIntegers($string));
	}

	public function test_maskToIntegers2(): void
	{
		$ips = file($this->application->zeskHome('tests/test-data/ip.txt'));

		$ip = '192.168.0.248';
		[
			$a,
			$b,
		] = IPv4::maskToIntegers("$ip/29");
		$this->assertEquals("$ip/29", IPv4::fromInteger($a) . "/$b");

		foreach ($ips as $ip) {
			$ip = trim($ip);
			if (empty($ip)) {
				continue;
			}
			for ($i = 32; $i >= 8; $i--) {
				$string = "$ip/$i";
				[
					$a,
					$b,
				] = IPv4::maskToIntegers($string);
				$check_string = IPv4::maskToString($a, $b, false);
				[
					$a1,
					$b1,
				] = IPv4::maskToIntegers($check_string);
				$check_string1 = IPv4::maskToString($a1, $b1, false);
				[
					$a,
					$b,
				] = IPv4::maskToIntegers($check_string1);

				$this->assertEquals($check_string, $check_string1, "$string ($a, $a1 => $b, $b1)");
				$this->assertEquals($a, $a1, sprintf('%032b === %032b', $a, $a1));
				$this->assertEquals($b, $b1, sprintf('%032b === %032b', $b, $b1));
			}
		}
	}

	public static function data_maskToString(): array
	{
		$ip = 0.0;
		$ip_bits = 32;

		$results = [
			[
				'0.0.0.0',
				$ip,
				$ip_bits,
			],
			[
				'0.0.0.*',
				$ip,
				24,
			],
			[
				'0.0.*',
				$ip,
				16,
			],
			[
				'0.*',
				$ip,
				8,
			],

			[
				'192.168.0.200/29',
				3232235720,
				29,
			],
			[
				'192.168.0.200/29',
				3232235720,
				29,
			],
			[
				'192.168.0.200/29',
				3232235720,
				29,
			],

			[
				'76.12.128.128/29',
				IPv4::toInteger('76.12.128.129'),
				29,
			],
			[
				'76.12.128.128/29',
				IPv4::toInteger('76.12.128.129'),
				29,
			],
		];

		$all_ones = IPv4::toInteger('255.255.255.255');
		$n = 1;
		$m = 0;
		for ($i = 31; $i > 16; $i--) {
			$c = 255 - $m;
			$d = ($m !== 0) ? 0 : 255 - $n;
			//	echo "$n, $m, 255.255.$c.$d\n";

			if ($i === 24) {
				continue;
			}

			$results[] = [
				"255.255.$c.$d/$i",
				$all_ones,
				$i,
			];

			$n = ($n * 2) + 1;
			if ($m) {
				$m = ($m * 2) + 1;
			}
			if ($n === 255) {
				$m = 1;
			}
		}
		return $results;
	}

	/**
	 * @return void
	 * @dataProvider data_maskToString
	 * @covers ::maskToString
	 */
	public function test_maskToString(string $expected, int|float $ip, int $ip_bits): void
	{
		$this->assertEquals($expected, IPv4::maskToString($ip, $ip_bits));
	}

	public static function data_network(): array
	{
		return [
			[
				'1.0.0.0/16',
				(float) 0x01000000,
				(float) 0x0100FFFF,
			],
			[
				'192.168.*',
				0xC0A80000,
				0xC0A8FFFF,
			],
			[
				'192.168.0.0/16',
				0xC0A80000,
				0xC0A8FFFF,
			],
			[
				'192.168.0.0/17',
				'192.168.0.0',
				'192.168.127.255',
			],
			[
				'192.168.0.0/18',
				'192.168.0.0',
				'192.168.63.255',
			],
			[
				'10.*',
				0x0A000000,
				0x0AFFFFFF,
			],
			[
				'76.12.128.129/26',
				'76.12.128.128',
				1275887807,
			],
		];
	}

	/**
	 * @param string $mask
	 * @param string|int $ip_check_low
	 * @param string|int $ip_check_high
	 * @return void
	 * @dataProvider data_network
	 * @covers ::toInteger ::fromInteger ::network
	 */
	public function test_network(string $mask, string|int $ip_check_low, string|int $ip_check_high): void
	{
		if (is_string($ip_check_low)) {
			$ip_check_low = IPv4::toInteger($ip_check_low);
		}
		if (is_string($ip_check_high)) {
			$ip_check_high = IPv4::toInteger($ip_check_high);
		}
		[
			$ip_low,
			$ip_high,
		] = IPv4::network($mask);
		$this->assertEquals($ip_low, $ip_check_low, 'Low Check: ' . IPv4::fromInteger($ip_low) . ' !== ' . IPv4::fromInteger($ip_check_low));
		$this->assertEquals($ip_high, $ip_check_high, 'High Check: ' . IPv4::fromInteger($ip_high) . ' !== ' . IPv4::fromInteger($ip_check_high));
	}

	public static function data_to_integer(): array
	{
		return [
			[
				floatval(1),
				1,
			],
			[
				floatval(9999),
				9999,
			],
			[
				floatval(0xFFFFFFFF),
				0xFFFFFFFF,
			],
			[
				123123145.0,
				123123145.0,
			],
			[
				floatval(0xFFFFFFFF),
				'255.255.255.255',
			],
			[
				0,
				'',
			],
			[
				0,
				false,
			],
			[
				0,
				[],
			],
			[
				0,
				null,
			],
			[
				16909060.0,
				'1.2.3.4',
			],
			[
				33752065.0,
				'2.3.4.1',
			],
			[
				99.0,
				'0.0.0.99',
			],
			[
				0,
				'1.0.0.256',
			],
			[
				0,
				'1.0.256.0',
			],
			[
				0,
				'1.0.256.0',
			],
			[
				0,
				'1.256.0.0',
			],
			[
				0,
				'256.0.0.0',
			],
			[
				3232235736.0,
				'192.168.0.216',
			],
			[
				4294967288.0,
				'255.255.255.248',
			],
		];
	}

	/**
	 * @param float $expected
	 * @param       $mixed
	 * @return void
	 * @dataProvider data_to_integer
	 * @covers ::toInteger ::fromInteger
	 */
	public function test_to_integer(float $expected, $mixed): void
	{
		$this->assertEquals($expected, IpV4::toInteger($mixed));
		if (is_string($mixed) && !empty($mixed) && !empty($expected)) {
			$this->assertEquals($mixed, IpV4::fromInteger($expected));
		}
	}

	/**
	 * @return void
	 * @covers ::subnetBits
	 */
	public function test_subnetBits(): void
	{
		$ips = file($this->application->zeskHome('tests/test-data/ip.txt'));
		foreach ($ips as $ip) {
			if (empty($ip)) {
				continue;
			}
			// echo "$ip\n";
			$ipi = IPv4::toInteger($ip);
			$this->assertEquals($ipi, IPv4::subnetBits($ipi, 32));
			$delta = ($ipi & 1) ? 1 : 0;
			$this->assertEquals($ipi - $delta, IPv4::subnetBits($ipi, 31));
			$delta = ($ipi & 3);
			$this->assertEquals($ipi - $delta, IPv4::subnetBits($ipi, 30));
			$delta = ($ipi & 0xFFFF);
			$this->assertEquals($ipi - $delta, IPv4::subnetBits($ipi, 16));
		}
	}

	public function test_subnetMask(): void
	{
		for ($n = 1; $n <= 32; $n++) {
			$b = sprintf('%032b', IPv4::subnetMask($n));
			$check = str_repeat('1', $n) . str_repeat('0', 32 - $n);
			$this->assertEquals($check, $b);
		}
	}

	public static function data_subnetMaskNot(): array
	{
		return [
			[
				0,
				32,
			],
			[
				1,
				31,
			],
			[
				3,
				30,
			],
			[
				127,
				25,
			],
			[
				1023,
				22,
			],
			// 10 bits
			[
				16383,
				18,
			],
			[
				65535,
				16,
			],
			[
				16777215,
				8,
			],
			[
				268435455,
				4,
			],
			[
				1073741823,
				2,
			],
			[
				2147483647,
				1,
			],
			[
				4294967295,
				0,
			],
		];
	}

	/**
	 * @param $expected
	 * @param $ip_bits
	 * @return void
	 * @dataProvider data_subnetMaskNot
	 */
	public function test_subnetMaskNot($expected, $ip_bits): void
	{
		$this->assertEquals($expected, IPv4::subnetMaskNot($ip_bits));
	}

	public static function data_valid(): array
	{
		return [
			[
				'192.168.0.1',
				true,
			],
			[
				'192.168.0.0',
				false,
			],
			[
				'0.168.0.1',
				false,
			],
			[
				'1.168.0.1',
				true,
			],
			[
				'256.168.0.1',
				false,
			],
			[
				'-1.168.0.1',
				false,
			],
			[
				'192.168.0.0.1',
				false,
			],
			[
				'10.0.0.1',
				true,
			],
			[
				'10.0.256.1',
				false,
			],
			[
				'10.0.-1.1',
				false,
			],
			[
				'10.0.42A.1',
				false,
			],
			[
				'10.0.42A.1',
				false,
			],
			[
				'10.0.42A.1',
				false,
			],
			[
				'255.255.255.255',
				true,
			],
			[
				'256.255.255.255',
				false,
			],
			[
				'255.256.255.255',
				false,
			],
			[
				'255.255.256.255',
				false,
			],
			[
				'255.255.255.256',
				false,
			],
		];
	}

	/**
	 * @param string $ip
	 * @param bool $valid
	 * @return void
	 * @dataProvider data_valid
	 */
	public function test_valid(string $ip, bool $valid): void
	{
		$this->assertEquals($valid, IPv4::valid($ip), "$ip should be " . ($valid ? 'valid ip' : 'invalid ip'));
	}

	public static function data_within_network(): array
	{
		return [
			[
				'76.12.128.128',
				'76.12.128.128/26',
				true,
			],
			[
				'76.12.128.127',
				'76.12.128.128/26',
				false,
			],
			[
				'76.12.128.191',
				'76.12.128.128/26',
				true,
			],
			[
				'76.12.128.192',
				'76.12.128.128/26',
				false,
			],
			[
				'10.0.0.1',
				'10.*',
				true,
			],
			[
				'10.255.255.255',
				'10.*',
				true,
			],
			[
				'11.255.255.255',
				'10.*',
				false,
			],
			[
				'10.255.255.255',
				'10.254.*',
				false,
			],
			[
				'10.0.255.255',
				'10.0.*',
				true,
			],
			[
				'10.0.255.255',
				'10.1.*',
				false,
			],
		];
	}

	/**
	 * @param $ip
	 * @param $network
	 * @param $result
	 * @return void
	 * @dataProvider data_within_network
	 */
	public function test_within_network($ip, $network, $result): void
	{
		$this->assertEquals($result, IPv4::withinNetwork($ip, $network), "IPv4::within_network($ip, $network) === " . StringTools::fromBool($result));
	}

	public static function data_is_private(): array
	{
		$r0 = self::randomInteger(0, 255);
		$r1 = self::randomInteger(0, 255);
		$r2 = self::randomInteger(0, 255);
		return [
			[
				true,
				'10.2.3.4',
			],
			[
				true,
				'10.255.3.4',
			],
			[
				true,
				'10.2.32.4',
			],
			[
				true,
				'10.2.3.41',
			],
			[
				true,
				'0.2.3.41',
			],
			[
				true,
				'100.64.4.255',
			],
			[
				true,
				'100.65.255.255',
			],
			[
				false,
				'100.192.255.255',
			],
			[
				true,
				"127.$r0.$r1.$r2",
			],
			[
				true,
				"169.254.$r0.$r1",
			],
			[
				true,
				"169.254.$r1.$r2",
			],
			[
				false,
				"169.255.$r1.$r2",
			],
			[
				true,
				"224.$r1.$r2.$r2",
			],
		];
	}

	/**
	 * @param $expected
	 * @param $ip
	 * @return void
	 * @dataProvider data_is_private
	 */
	public function test_is_private($expected, $ip): void
	{
		$this->assertEquals($expected, IPv4::isPrivate($ip));
	}
}
