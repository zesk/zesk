<?php
namespace zesk;

/**
 * IP Address tools
 *
 * @author kent
 * @see IPv4
 * @see IPv6
 */
class IPv4 {
	public static $private_addresses = array(
		array(
			'0.0.0.0/8',
			0,
			16777215,
		),
		array(
			'10.0.0.0/8',
			167772160,
			184549375,
		),
		array(
			'100.64.0.0/10',
			1681915904,
			1686110207,
		),
		array(
			'127.0.0.0/8',
			2130706432,
			2147483647,
		),
		array(
			'169.254.0.0/16',
			2851995648,
			2852061183,
		),
		array(
			'172.16.0.0/12',
			2886729728,
			2887778303,
		),
		array(
			'192.0.0.0/29',
			3221225472,
			3221225479,
		),
		array(
			'192.0.2.0/24',
			3221225984,
			3221226239,
		),
		array(
			'192.88.99.0/24',
			3227017984,
			3227018239,
		),
		array(
			'192.168.0.0/16',
			3232235520,
			3232301055,
		),
		array(
			'198.18.0.0/15',
			3323068416,
			3323199487,
		),
		array(
			'198.51.100.0/24',
			3325256704,
			3325256959,
		),
		array(
			'203.0.113.0/24',
			3405803776,
			3405804031,
		),
		array(
			'224.0.0.0/4',
			3758096384,
			4026531839,
		),
		array(
			'240.0.0.0/4',
			4026531840,
			4294967295,
		),
		array(
			'255.255.255.255/32',
			4294967295,
			4294967295,
		),
	);

	/**
	 * Number of bits
	 * @var integer
	 */
	const BITS = 32;

	/**
	 * Returns integer value of subnet
	 *
	 * @param integer $ipbits
	 *        	Number of bits between 0 and 32
	 * @return integer
	 */
	public static function subnet_mask($ipbits) {
		$ipbits = clamp(0, $ipbits, self::BITS);
		return bindec(str_repeat("1", $ipbits) . str_repeat("0", self::BITS - $ipbits));
	}

	/**
	 * Returns integer value of subnet available bits
	 *
	 * @param integer $ipbits
	 *        	Number of bits between 0 and 32
	 * @return integer
	 */
	public static function subnet_mask_not($ipbits) {
		$ipbits = clamp(0, $ipbits, self::BITS);
		return bindec(str_repeat("1", self::BITS - $ipbits));
	}

	/**
	 * Given an IP integer address, convert to "low" IP integer in subnet
	 *
	 * @param integer $ipint
	 * @param integer $ipbits
	 * @return unknown|number
	 */
	public static function subnet_bits($ipint, $ipbits) {
		if ($ipbits >= self::BITS) {
			return $ipint;
		}
		$ipint = $ipint - ($ipint & self::subnet_mask_not($ipbits));
		return $ipint;
	}

	/**
	 * Is given string a subnet mask?
	 *
	 * @param string $string
	 * @return boolean
	 */
	public static function is_mask($string) {
		list($ip, $bits) = pair($string, "/", $string, self::BITS);
		if (integer_between(8, $bits, self::BITS) && self::_valid($ip)) {
			return true;
		}
		$x = explode(".", $string);
		if (count($x) > 4) {
			return false;
		}
		$last = array_pop($x);
		if ($last !== "*" && !integer_between(0, $last, 255)) {
			return false;
		}
		foreach ($x as $ipi) {
			if (!integer_between(0, $ipi, 255)) {
				return false;
			}
		}
		return true;
	}

	/**
	 * Convert a mask to an integer IP/subnet bits
	 *
	 * @param string $string
	 * @return array
	 */
	public static function mask_to_integers($string) {
		if (!self::is_mask($string)) {
			return array(
				false,
				false,
			);
		}
		list($ip, $bits) = pair($string, "/", $string, self::BITS);

		if (is_numeric($bits) && self::_valid($ip)) {
			$bits = clamp(8, intval($bits), self::BITS);
		} else {
			$x = explode(".", $string);
			$last = array_pop($x);
			if (integer_between(0, $last, 255)) {
				$x[] = intval($last);
			}
			$n = count($x);
			for ($i = 4; $i > $n; $i--) {
				$x[] = 0;
			}
			assert('count($x) === 4');
			$ip = implode(".", $x);
			$bits = $n * 8;
		}
		return array(
			self::subnet_bits(self::to_integer($ip), $bits),
			$bits,
		);
	}

	/**
	 * Convert an integer IP and number of bits to its equivalent string representation
	 *
	 * @param integer $ip
	 *        	An IP integer
	 * @param integer $ipbits
	 *        	An IP number of bits in the mask (from 0 to 32)
	 * @param boolean $star_notation
	 *        	When true, returns masks of 8, 16 and 24 bits using stars instead of slashes, e.g.
	 *        	"192.168.*"
	 * @return string An IP and subnet notation string
	 */
	public static function mask_to_string($ip, $ipbits = self::BITS, $star_notation = true) {
		$ipbits = to_integer($ipbits, self::BITS);
		$ip = doubleval($ip);
		if ($ipbits === self::BITS) {
			return self::from_integer($ip);
		}
		if ($star_notation) {
			if ($ipbits === 24 || $ipbits === 16 || $ipbits === 8) {
				return implode(".", array_slice(explode(".", self::from_integer($ip)), 0, ($ipbits / 8))) . ".*";
			}
		}
		return self::from_integer(self::subnet_bits($ip, $ipbits)) . "/$ipbits";
	}

	/**
	 * Returns the low and high IPs as integers for a given network
	 *
	 * Usage:
	 * list($low_ip, $high_ip) = self::network("192.168.0.0/24");
	 *
	 * @param string $network
	 *        	An IP network string (see self::mask_to_integers)
	 * @return array of
	 * @see self::mask_to_integers
	 */
	public static function network($network) {
		list($low_ip, $nbits) = self::mask_to_integers($network);
		//	echo long2ip($low_ip) . ":" . $nbits . "\n";
		//	echo long2ip(self::subnet_mask_not($nbits)) . "\n";
		$high_ip = $low_ip + self::subnet_mask_not($nbits);
		return array(
			$low_ip,
			$high_ip,
		);
	}

	/**
	 * Returns true if an IP address falls within an available network, or false if not
	 *
	 * Usage:
	 * <code>
	 * if (IPv4::within_network($ip, "192.168.0.0/24")) {
	 * echo "LANward bound!\n";
	 * }
	 * </code>
	 *
	 * @param string $network
	 *        	An IP network string (see self::mask_to_integers)
	 * @return boolean
	 * @see self::network
	 */
	public static function within_network($ip, $network) {
		$ip = self::to_integer($ip);
		list($low_ip, $high_ip) = self::network($network);
		return ($ip >= $low_ip && $ip <= $high_ip);
	}

	/**
	 * Convert an IP address to a big-endian integer
	 *
	 * @param mixed $mixed
	 * @return unknown
	 */
	public static function to_integer($mixed) {
		if (is_integer($mixed)) {
			return $mixed;
		}
		if (is_double($mixed)) {
			return $mixed;
		}
		if (empty($mixed)) {
			return null;
		}
		list($a, $b, $c, $d) = explode(".", $mixed, 4) + array_fill(0, 4, 0);
		return ((((doubleval($a) * 256) + doubleval($b)) * 256 + doubleval($c)) * 256 + doubleval($d));
	}

	/**
	 * Convert a big-endian long integer into an IP address
	 *
	 * @param double $ipid
	 *        	A long integer
	 * @return string An ip address
	 */
	public static function from_integer($ipid) {
		$ipid = doubleval($ipid);
		$d = fmod($ipid, 256);
		$ipid = intval($ipid / 256);
		$c = $ipid & 0xFF;
		$ipid >>= 8;
		$b = $ipid & 0xFF;
		$ipid >>= 8;
		$a = $ipid & 0xFF;
		return "$a.$b.$c.$d";
	}

	/**
	 * Is this a valid IPv4 address?
	 *
	 * @param string $string
	 * @return boolean
	 */
	public static function valid($string) {
		return self::_valid($string, 1);
	}

	/**
	 * Internal function to check IP address with optional check for final IP byte to be 0 or another number (usually 1)
	 *
	 * @param string $string
	 * @param number $low_low
	 * @return boolean
	 */
	private static function _valid($string, $low_low = 0) {
		if (!is_string($string)) {
			return false;
		}
		$x = explode(".", $string);
		if (count($x) != 4) {
			return false;
		}
		list($a, $b, $c, $d) = $x;
		return integer_between(1, $a, 255) && integer_between(0, $b, 255) && integer_between(0, $c, 255) && integer_between($low_low, $d, 255);
	}

	/**
	 * Does this IP address represent a private, internal network?
	 *
	 * @see https://en.wikipedia.org/wiki/Private_network
	 * @param mixed $ip
	 * @return boolean
	 */
	public static function is_private($ip) {
		$ipi = self::to_integer($ip);
		foreach (self::$private_addresses as $address) {
			if ($ipi >= $address[1] && $ipi <= $address[2]) {
				return true;
			}
		}
		return false;
	}
}
