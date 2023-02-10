<?php
declare(strict_types=1);

namespace zesk;

/**
 *
 */
class IPv4_Test extends UnitTest {
	public function test_from_integer(): void {
		$this->assertEquals('192.168.0.200', IPv4::from_integer(3232235720));
		$this->assertEquals('192.168.0.200', IPv4::from_integer('3232235720'));
	}

	public function test_is_mask(): void {
		$this->assertFalse(IPv4::is_mask('-1.0,0,0'));
		$this->assertFalse(IPv4::is_mask('-1.0.0.0.0'));
		$this->assertFalse(IPv4::is_mask('1.0.0.0.0'));
		$this->assertFalse(IPv4::is_mask('0.0,0,0')); // 0.0 comma 0 comma 0 is not
		$this->assertFalse(IPv4::is_mask('0.0.0.0/0'));
		$this->assertFalse(IPv4::is_mask('0.0.0.0/1'));
		$this->assertFalse(IPv4::is_mask('0.0.0.0/7'));
		for ($i = 8; $i < 32; $i++) {
			$this->assertTrue(IPv4::is_mask("1.0.0.0/$i"), "1.0.0.0/$i is apparently not a mask?");
		}
		$this->assertFalse(IPv4::is_mask('0.0.0.0/33'));
		$this->assertTrue(IPv4::is_mask('0.0.0.*'));
		$this->assertTrue(IPv4::is_mask('0.0.*'));
		$this->assertTrue(IPv4::is_mask('0.*'));
		$this->assertTrue(IPv4::is_mask('255.*'));
		$this->assertFalse(IPv4::is_mask('256.*'));
		$this->assertFalse(IPv4::is_mask('-1.*'));
		$this->assertFalse(IPv4::is_mask('1.*/32'));
		$this->assertFalse(IPv4::is_mask('256.*'));
		$this->assertFalse(IPv4::is_mask('255.255.255.256/32'));
		$this->assertFalse(IPv4::is_mask('255.255.255.256/23'));
		$this->assertTrue(IPv4::is_mask('192.128.0.0/9'));
	}

	public static function data_mask_to_integers(): array {
		return [
			[[null, null], '127.0.0.2.3'],
			[[null, null], '256.0.0'],
			[[null, null], '1.0.-4'],
			[[null, null], '1.0.2343'],
			[[null, null], '1.*.2343'],
			[[null, null], '127.0.0.1/33'],
			[[2130706433.0, 32], '127.0.0.1'],
			[[2130706432.0, 24], '127.0.0'],
			[[2130706432.0, 16], '127.0'],
			[[2130706432.0, 8], '127'],
			[[2130706432.0, 24], '127.0.0.1/24'],
			[[2130706432.0, 16], '127.0.0.1/16'],
			[[167772160.0, 8], '10.0.0.1/8'],
			[[167772160.0, 16], '10.0.0.1/16'],
			[[167772160.0, 24], '10.0.0.1/24'],
			[[167772160.0, 25], '10.0.0.1/25'],
			[[167772160.0, 31], '10.0.0.1/31'],
			[[167772161.0, 32], '10.0.0.1/32'],
		];
	}

	/**
	 * @param array $expected
	 * @param string $string
	 * @return void
	 * @dataProvider data_mask_to_integers
	 */
	public function test_mask_to_integers(array $expected, string $string): void {
		$this->assertEquals($expected, IPv4::mask_to_integers($string));
	}

	public function test_mask_to_integers2(): void {
		$ips = file($this->application->zeskHome('test/test-data/ip.txt'));

		$ip = '192.168.0.248';
		[$a, $b] = IPv4::mask_to_integers("$ip/29");
		$this->assertEquals("$ip/29", IPv4::from_integer($a) . "/$b");

		foreach ($ips as $ip) {
			$ip = trim($ip);
			if (empty($ip)) {
				continue;
			}
			for ($i = 32; $i >= 8; $i--) {
				$string = "$ip/$i";
				[$a, $b] = IPv4::mask_to_integers($string);
				$check_string = IPv4::mask_to_string($a, $b, false);
				[$a1, $b1] = IPv4::mask_to_integers($check_string);
				$check_string1 = IPv4::mask_to_string($a1, $b1, false);
				[$a, $b] = IPv4::mask_to_integers($check_string1);

				$this->assertEquals($check_string, $check_string1, "$string ($a, $a1 => $b, $b1)");
				$this->assertEquals($a, $a1, sprintf('%032b === %032b', $a, $a1));
				$this->assertEquals($b, $b1, sprintf('%032b === %032b', $b, $b1));
			}
		}
	}

	public function test_mask_to_string(): void {
		$ip = 0.0;
		$ip_bits = 32;
		$this->assertEquals('0.0.0.0', IPv4::mask_to_string($ip, $ip_bits));
		$this->assertEquals('0.0.0.*', IPv4::mask_to_string($ip, 24));
		$this->assertEquals('0.0.*', IPv4::mask_to_string($ip, 16));
		$this->assertEquals('0.*', IPv4::mask_to_string($ip, 8));

		$this->assertEquals('192.168.0.200/29', IPv4::mask_to_string(3232235720, 29));
		$this->assertEquals('192.168.0.200/29', IPv4::mask_to_string(3232235720, 29));
		$this->assertEquals('192.168.0.200/29', IPv4::mask_to_string(3232235720, 29));

		$all_ones = IPv4::to_integer('255.255.255.255');
		$n = 1;
		$m = 0;
		for ($i = 31; $i > 16; $i--) {
			$c = 255 - $m;
			$d = ($m !== 0) ? 0 : 255 - $n;
			//	echo "$n, $m, 255.255.$c.$d\n";

			if ($i === 24) {
				continue;
			}

			$this->assertEquals("255.255.$c.$d/$i", IPv4::mask_to_string($all_ones, $i));

			$n = ($n * 2) + 1;
			if ($m) {
				$m = ($m * 2) + 1;
			}
			if ($n === 255) {
				$m = 1;
			}
		}

		$this->assertEquals('76.12.128.128/29', IPv4::mask_to_string(IPv4::to_integer('76.12.128.129'), 29));
		$this->assertEquals('76.12.128.128/29', IPv4::mask_to_string(IPv4::to_integer('76.12.128.129'), 29));
	}

	public static function data_network(): array {
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
	 * @param $ipmask
	 * @param $ip_check_low
	 * @param $ip_check_high
	 * @return void
	 * @dataProvider data_network
	 */
	public function test_network($ipmask, $ip_check_low, $ip_check_high): void {
		if (is_string($ip_check_low)) {
			$ip_check_low = IPv4::to_integer($ip_check_low);
		}
		if (is_string($ip_check_high)) {
			$ip_check_high = IPv4::to_integer($ip_check_high);
		}
		[$ip_low, $ip_high] = IPv4::network($ipmask);
		$this->assertEquals($ip_low, $ip_check_low, 'Low Check: ' . IPv4::from_integer($ip_low) . ' !== ' . IPv4::from_integer($ip_check_low));
		$this->assertEquals($ip_high, $ip_check_high, 'High Check: ' . IPv4::from_integer($ip_high) . ' !== ' . IPv4::from_integer($ip_check_high));
	}

	public static function data_to_integer(): array {
		return [
			[floatval(1), 1],
			[floatval(9999), 9999],
			[floatval(0xFFFFFFFF), 0xFFFFFFFF],
			[123123145.0, 123123145.0],
			[floatval(0xFFFFFFFF), '255.255.255.255'],
			[0, ''],
			[0, false],
			[0, []],
			[0, null],
			[16909060.0, '1.2.3.4'],
			[33752065.0, '2.3.4.1'],
			[99.0, '0.0.0.99'],
			[0, '1.0.0.256'],
			[0, '1.0.256.0'],
			[0, '1.0.256.0'],
			[0, '1.256.0.0'],
			[0, '256.0.0.0'],
			[3232235736.0, '192.168.0.216'],
			[4294967288.0, '255.255.255.248'],
		];
	}

	/**
	 * @param float $expected
	 * @param $mixed
	 * @return void
	 * @dataProvider data_to_integer
	 */
	public function test_to_integer(float $expected, $mixed): void {
		$this->assertEquals($expected, IpV4::to_integer($mixed));
		if (is_string($mixed) && !empty($mixed) && !empty($expected)) {
			$this->assertEquals($mixed, IpV4::from_integer($expected));
		}
	}

	public function test_remote(): void {
		$request = new Request($this->application);
		$default = '0.0.0.0';
		$this->assertEquals($default, $request->ip());
	}

	public function test_subnet_bits(): void {
		$ips = file($this->application->zeskHome('test/test-data/ip.txt'));
		foreach ($ips as $ip) {
			if (empty($ip)) {
				continue;
			}
			// echo "$ip\n";
			$ipi = IPv4::to_integer($ip);
			$this->assertEquals($ipi, IPv4::subnet_bits($ipi, 32));
			$delta = ($ipi & 1) ? 1 : 0;
			$this->assertEquals($ipi - $delta, IPv4::subnet_bits($ipi, 31));
			$delta = ($ipi & 3);
			$this->assertEquals($ipi - $delta, IPv4::subnet_bits($ipi, 30));
			$delta = ($ipi & 0xFFFF);
			$this->assertEquals($ipi - $delta, IPv4::subnet_bits($ipi, 16));
		}
	}

	public function test_subnet_mask(): void {
		for ($n = 1; $n <= 32; $n++) {
			$b = sprintf('%032b', IPv4::subnet_mask($n));
			$check = str_repeat('1', $n) . str_repeat('0', 32 - $n);
			$this->assertEquals($check, $b);
		}
	}

	public static function data_subnet_mask_not(): array {
		return [
			[0, 32],
			[1, 31],
			[3, 30],
			[127, 25],
			[1023, 22], // 10 bits
			[16383, 18],
			[65535, 16],
			[16777215, 8],
			[268435455, 4],
			[1073741823, 2],
			[2147483647, 1],
			[4294967295, 0],
		];
	}

	/**
	 * @return void
	 * @dataProvider data_subnet_mask_not
	 */
	public function test_subnet_mask_not($expected, $ip_bits): void {
		$this->assertEquals($expected, IPv4::subnet_mask_not($ip_bits));
	}

	public static function data_valid(): array {
		return [
			['192.168.0.1', true, ],
			['192.168.0.0', false, ],
			['0.168.0.1', false, ],
			['1.168.0.1', true, ],
			['256.168.0.1', false, ],
			['-1.168.0.1', false, ],
			['192.168.0.0.1', false, ],
			['10.0.0.1', true, ],
			['10.0.256.1', false, ],
			['10.0.-1.1', false, ],
			['10.0.42A.1', false, ],
			['10.0.42A.1', false, ],
			['10.0.42A.1', false, ],
			['255.255.255.255', true, ],
			['256.255.255.255', false, ],
			['255.256.255.255', false, ],
			['255.255.256.255', false, ],
			['255.255.255.256', false, ],
		];
	}

	/**
	 * @param string $ip
	 * @param bool $valid
	 * @return void
	 * @dataProvider data_valid
	 */
	public function test_valid(string $ip, bool $valid): void {
		$this->assertEquals($valid, IPv4::valid($ip), "$ip should be " . ($valid ? 'valid ip' : 'invalid ip'));
	}

	public static function data_within_network() {
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
	public function test_within_network($ip, $network, $result): void {
		$this->assertEquals($result, IPv4::within_network($ip, $network), "IPv4::within_network($ip, $network) === " . StringTools::fromBool($result));
	}

	public static function data_is_private(): array {
		$r0 = $this->randomInteger(0, 255);
		$r1 = $this->randomInteger(0, 255);
		$r2 = $this->randomInteger(0, 255);
		return [
			[true, '10.2.3.4'],
			[true, '10.255.3.4'],
			[true, '10.2.32.4'],
			[true, '10.2.3.41'],
			[true, '0.2.3.41'],
			[true, '100.64.4.255'],
			[true, '100.65.255.255'],
			[false, '100.192.255.255'],
			[true, "127.$r0.$r1.$r2"],
			[true, "169.254.$r0.$r1"],
			[true, "169.254.$r1.$r2"],
			[false, "169.255.$r1.$r2"],
			[true, "224.$r1.$r2.$r2"],
		];
	}

	/**
	 * @param $expected
	 * @param $ip
	 * @return void
	 * @dataProvider data_is_private
	 */
	public function test_is_private($expected, $ip): void {
		$this->assertEquals($expected, IPv4::is_private($ip));
	}
}
