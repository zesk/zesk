<?php
namespace zesk;

/**
 * 
 */
class IPv4_Test extends Test_Unit {
	function test_from_integer() {
		$ipid = null;
		$this->assert(IPv4::from_integer(3232235720) === "192.168.0.200");
		$this->assert(IPv4::from_integer("3232235720") === "192.168.0.200");
	}
	function test_is_mask() {
		$string = null;
		$this->assert(IPv4::is_mask("-1.0,0,0") === false);
		$this->assert(IPv4::is_mask("-1.0.0.0.0") === false);
		$this->assert(IPv4::is_mask("1.0.0.0.0") === false);
		$this->assert(IPv4::is_mask("0.0,0,0") === false);
		$this->assert(IPv4::is_mask("0.0.0.0/0") === false);
		$this->assert(IPv4::is_mask("0.0.0.0/1") === false);
		$this->assert(IPv4::is_mask("0.0.0.0/7") === false);
		for ($i = 8; $i < 32; $i++) {
			$this->assert(IPv4::is_mask("1.0.0.0/$i") === true, "1.0.0.0/$i is apparently not a mask?");
		}
		$this->assert(IPv4::is_mask("0.0.0.0/33") === false);
		$this->assert(IPv4::is_mask("0.0.0.*") === true);
		$this->assert(IPv4::is_mask("0.0.*") === true);
		$this->assert(IPv4::is_mask("0.*") === true);
		$this->assert(IPv4::is_mask("-1.*") === false);
		$this->assert(IPv4::is_mask("1.*/32") === false);
		$this->assert(IPv4::is_mask("256.*") === false);
		$this->assert(IPv4::is_mask("255.255.255.256/32") === false);
		$this->assert(IPv4::is_mask("255.255.255.256/23") === false);
		$this->assert(IPv4::is_mask("192.128.0.0/9") === true, 'IPv4::is_mask("192.128.0.0/9") === true');
	}
	function test_mask_to_integers() {
		$ips = file($this->application->zesk_root('test/test-data/ip.txt'));
		
		$ip = "192.168.0.248";
		list($a, $b) = IPv4::mask_to_integers("$ip/29");
		$this->assert(IPv4::from_integer($a) . "/$b" === "$ip/29");
		
		foreach ($ips as $ip) {
			if (empty($ip)) {
				continue;
			}
			for ($i = 32; $i >= 8; $i--) {
				$string = "$ip/$i";
				list($a, $b) = IPv4::mask_to_integers($string);
				$check_string = IPv4::mask_to_string($a, $b);
				list($a1, $b1) = IPv4::mask_to_integers($check_string);
				$check_string1 = IPv4::mask_to_string($a1, $b1);
				list($a, $b) = IPv4::mask_to_integers($check_string1);
				
				$this->assert($check_string === $check_string1, "$string: $check_string === $check_string1 ($a, $a1 => $b, $b1)");
				$this->assert($a === $a1 && $b === $b1, "$string: $a === $a1 && $b === $b1 " . sprintf("%032b === %032b", $a, $a1));
			}
		}
	}
	function test_mask_to_string() {
		$ip = null;
		$ipbits = 32;
		$this->assert(IPv4::mask_to_string($ip, $ipbits) === "0.0.0.0");
		$this->assert(IPv4::mask_to_string($ip, 24) === "0.0.0.*");
		$this->assert(IPv4::mask_to_string($ip, 16) === "0.0.*");
		$this->assert(IPv4::mask_to_string($ip, 8) === "0.*");
		
		$this->assert(IPv4::mask_to_string(3232235720, 29) === "192.168.0.200/29");
		$this->assert(IPv4::mask_to_string(3232235720, "29") === "192.168.0.200/29");
		$this->assert(IPv4::mask_to_string("3232235720", "29") === "192.168.0.200/29", "IPv4::mask_to_string(\"3232235720\", \"29\") === \"" . IPv4::mask_to_string("3232235720", "29") . "\" === \"192.168.0.200/29\"");
		
		$all_ones = IPv4::to_integer("255.255.255.255");
		$n = 1;
		$m = 0;
		for ($i = 31; $i > 16; $i--) {
			$c = 255 - $m;
			$d = ($m !== 0) ? 0 : 255 - $n;
			//	echo "$n, $m, 255.255.$c.$d\n";
			
			if ($i === 24)
				continue;
			
			$this->assert(IPv4::mask_to_string($all_ones, $i) === "255.255.$c.$d/$i", IPv4::mask_to_string($all_ones, $i) . " === 255.255.$c.$d/$i");
			
			$n = ($n * 2) + 1;
			if ($m) {
				$m = ($m * 2) + 1;
			}
			if ($n === 255) {
				$m = 1;
			}
		}
		
		$this->assert(IPv4::mask_to_string(IPv4::to_integer("76.12.128.129"), 29) === "76.12.128.128/29", IPv4::mask_to_string(IPv4::to_integer("76.12.128.129"), 29) . " === 76.12.128.128/29");
		$this->assert(IPv4::mask_to_string(IPv4::to_integer("76.12.128.129"), 29) === "76.12.128.128/29", IPv4::mask_to_string(IPv4::to_integer("76.12.128.129"), 29) . " === 76.12.128.128/29");
	}
	function test_network() {
		$network = null;
		IPv4::network($network);
		
		$tests = array(
			"192.168.*" => array(
				0xC0A80000,
				0xC0A8FFFF
			),
			"192.168.0.0/16" => array(
				0xC0A80000,
				0xC0A8FFFF
			),
			"192.168.0.0/17" => array(
				'192.168.0.0',
				'192.168.127.255'
			),
			"192.168.0.0/18" => array(
				'192.168.0.0',
				'192.168.63.255'
			),
			"10.*" => array(
				0x0A000000,
				0x0AFFFFFF
			),
			"76.12.128.129/26" => array(
				'76.12.128.128',
				1275887807
			)
		);
		
		list($ip_low, $ip_high) = IPv4::network('1.0.0.0/16');
		$this->assert($ip_low === (double) 0x01000000);
		$this->assert($ip_high === (double) 0x0100FFFF);
		
		foreach ($tests as $ipmask => $network) {
			list($ip_check_low, $ip_check_high) = $network;
			if (is_string($ip_check_low)) {
				$ip_check_low = IPv4::to_integer($ip_check_low);
			}
			if (is_string($ip_check_high)) {
				$ip_check_high = IPv4::to_integer($ip_check_high);
			}
			list($ip_low, $ip_high) = IPv4::network($ipmask);
			$this->assert("$ip_low === $ip_check_low && $ip_high === $ip_check_high", "\nTEST: $ipmask => array('" . IPv4::from_integer($ip_check_low) . "','" . IPv4::from_integer($ip_check_high) . "')" . "\nFUNC: $ipmask => array('" . IPv4::from_integer($ip_low) . "','" . IPv4::from_integer($ip_high) . "')");
		}
	}
	function test_remote() {
		$default = '0.0.0.0';
		$this->assert_null($this->application->request()->ip());
	}
	function test_subnet_bits() {
		$ips = file($this->application->zesk_root('test/test-data/ip.txt'));
		foreach ($ips as $ip) {
			if (empty($ip))
				continue;
			$ipi = IPv4::to_integer($ip);
			$this->assert(IPv4::subnet_bits($ipi, 32) === $ipi, IPv4::subnet_bits($ipi, 32) . "=== IPv4::subnet_bits($ipi, 32) === $ipi");
			$delta = ($ipi & 1) ? 1 : 0;
			$this->assert(IPv4::subnet_bits($ipi, 31) === $ipi - $delta, IPv4::subnet_bits($ipi, 31) . " === IPv4::subnet_bits($ipi, 31) === $ipi - $delta");
			$delta = ($ipi & 3);
			$this->assert(IPv4::subnet_bits($ipi, 30) === $ipi - $delta, IPv4::subnet_bits($ipi, 30) . " === IPv4::subnet_bits($ipi, 31) === $ipi - $delta");
			$delta = ($ipi & 0xFFFF);
			$this->assert(IPv4::subnet_bits($ipi, 16) === $ipi - $delta, IPv4::subnet_bits($ipi, 16) . " === IPv4::subnet_bits($ipi, 16) === $ipi - $delta");
		}
	}
	function test_subnet_mask() {
		for ($n = 1; $n <= 32; $n++) {
			$b = sprintf("%032b", IPv4::subnet_mask($n));
			$check = str_repeat("1", $n) . str_repeat("0", 32 - $n);
			$this->assert($b === $check, "IPv4::subnet_mask($n) === $b === $check");
		}
	}
	function test_subnet_mask_not() {
		$ipbits = null;
		IPv4::subnet_mask_not($ipbits);
	}
	function test_to_integer() {
		$mixed = null;
		IPv4::to_integer($mixed);
		$this->assert("" . IPv4::to_integer('192.168.0.216') === "3232235736");
		$this->assert("" . IPv4::to_integer('255.255.255.248') === "4294967288");
	}
	function test_valid() {
		$tests = array(
			"192.168.0.1" => true,
			"192.168.0.0" => false,
			"0.168.0.1" => false,
			"1.168.0.1" => true,
			"256.168.0.1" => false,
			"-1.168.0.1" => false,
			"192.168.0.0.1" => false,
			"10.0.0.1" => true,
			"10.0.256.1" => false,
			"10.0.-1.1" => false,
			"10.0.42A.1" => false,
			"10.0.42A.1" => false,
			"10.0.42A.1" => false,
			"255.255.255.255" => true,
			"256.255.255.255" => false,
			"255.256.255.255" => false,
			"255.255.256.255" => false,
			"255.255.255.256" => false
		);
		foreach ($tests as $string => $result) {
			$this->assert(IPv4::valid($string) === $result, "$string should be " . ($result ? "valid ip" : "invalid ip"));
		}
	}
	function test_within_network() {
		$ip = null;
		$network = null;
		IPv4::within_network($ip, $network);
		
		$tests = array(
			array(
				'76.12.128.128',
				'76.12.128.128/26',
				true
			),
			array(
				'76.12.128.127',
				'76.12.128.128/26',
				false
			),
			array(
				'76.12.128.191',
				'76.12.128.128/26',
				true
			),
			array(
				'76.12.128.192',
				'76.12.128.128/26',
				false
			),
			array(
				'10.0.0.1',
				'10.*',
				true
			),
			array(
				'10.255.255.255',
				'10.*',
				true
			),
			array(
				'11.255.255.255',
				'10.*',
				false
			),
			array(
				'10.255.255.255',
				'10.254.*',
				false
			),
			array(
				'10.0.255.255',
				'10.0.*',
				true
			),
			array(
				'10.0.255.255',
				'10.1.*',
				false
			)
		);
		
		foreach ($tests as $parms) {
			list($ip, $network, $result) = $parms;
			$this->assert(IPv4::within_network($ip, $network) === $result, "IPv4::within_network($ip, $network) === " . str::from_bool($result));
		}
	}
}
