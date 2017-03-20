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
			16777215
		),
		array(
			'10.0.0.0/8',
			167772160,
			184549375
		),
		array(
			'100.64.0.0/10',
			1681915904,
			1686110207
		),
		array(
			'127.0.0.0/8',
			2130706432,
			2147483647
		),
		array(
			'169.254.0.0/16',
			2851995648,
			2852061183
		),
		array(
			'172.16.0.0/12',
			2886729728,
			2887778303
		),
		array(
			'192.0.0.0/29',
			3221225472,
			3221225479
		),
		array(
			'192.0.2.0/24',
			3221225984,
			3221226239
		),
		array(
			'192.88.99.0/24',
			3227017984,
			3227018239
		),
		array(
			'192.168.0.0/16',
			3232235520,
			3232301055
		),
		array(
			'198.18.0.0/15',
			3323068416,
			3323199487
		),
		array(
			'198.51.100.0/24',
			3325256704,
			3325256959
		),
		array(
			'203.0.113.0/24',
			3405803776,
			3405804031
		),
		array(
			'224.0.0.0/4',
			3758096384,
			4026531839
		),
		array(
			'240.0.0.0/4',
			4026531840,
			4294967295
		),
		array(
			'255.255.255.255/32',
			4294967295,
			4294967295
		)
	);
	
	/**
	 * Returns integer value of subnet
	 *
	 * @param integer $ipbits
	 *        	Number of bits between 0 and 32
	 * @return integer
	 */
	public static function subnet_mask($ipbits) {
		$ipbits = clamp(0, $ipbits, 32);
		return bindec(str_repeat("1", $ipbits) . str_repeat("0", 32 - $ipbits));
	}
	
	/**
	 * Returns integer value of subnet available bits
	 *
	 * @param integer $ipbits
	 *        	Number of bits between 0 and 32
	 * @return integer
	 */
	public static function subnet_mask_not($ipbits) {
		$ipbits = clamp(0, $ipbits, 32);
		return bindec(str_repeat("1", 32 - $ipbits));
	}
	public static function subnet_bits($ipint, $ipbits) {
		if ($ipbits >= 32) {
			return $ipint;
		}
		$ipint = $ipint - ($ipint & self::subnet_mask_not($ipbits));
		return $ipint;
	}
	
	/**
	 * Is given string a submet mask?
	 *
	 * @param string $string
	 * @return boolean
	 */
	public static function is_mask($string) {
		list($ip, $bits) = pair($string, "/", $string, 32);
		if (integer_between(8, $bits, 32) && self::_valid($ip)) {
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
				false
			);
		}
		list($ip, $bits) = pair($string, "/", $string, 32);
		
		if (is_numeric($bits) && self::_valid($ip)) {
			$bits = clamp(8, intval($bits), 32);
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
			$bits
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
	public static function mask_to_string($ip, $ipbits = 32, $star_notation = true) {
		$ipbits = to_integer($ipbits, 32);
		$ip = doubleval($ip);
		if ($ipbits === 32)
			return self::from_integer($ip);
		if ($star_notation) {
			if ($ipbits === 24 || $ipbits === 16 || $ipbits === 8)
				return implode(".", array_slice(explode(".", self::from_integer($ip)), 0, ($ipbits / 8))) . ".*";
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
			$high_ip
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
		return ((((doubleval($a) * 256) + $b) * 256 + $c) * 256 + $d);
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
	public static function valid($string) {
		return self::_valid($string, 1);
	}
	private static function _valid($string, $low_low = 0) {
		if (!is_string($string)) {
			dump($string);
			backtrace();
		}
		$x = explode(".", $string);
		if (count($x) != 4) {
			return false;
		}
		list($a, $b, $c, $d) = $x;
		return integer_between(1, $a, 255) && integer_between(0, $b, 255) && integer_between(0, $c, 255) && integer_between($low_low, $d, 255);
	}
	
	/**
	 * Helper function for self::remote.
	 * Searches an array for a valid IP address.
	 *
	 * @param array $arr
	 *        	An array to search for certain keys
	 * @return an IP address if found, or false
	 */
	private static function _find_remote_key($arr) {
		$ks = array(
			"HTTP_CLIENT_IP",
			"HTTP_X_FORWARDED_FOR",
			"REMOTE_ADDR"
		);
		foreach ($ks as $k) {
			if (!isset($arr[$k])) {
				continue;
			}
			$ip = $arr[$k];
			if ($ip === "unknown") {
				continue;
			}
			if (empty($ip)) {
				continue;
			}
			$match = false;
			if (preg_match('/[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}/', $ip, $match)) {
				return $match[0];
			}
		}
		return false;
	}
	
	/**
	 * Returns the remote IP address, interpreting web server/proxy server intermediate IP addresses
	 * if necessary.
	 * Looks in $_ENV and $_SERVER for IP addresses.
	 *
	 * @param string $default
	 *        	A default IP address to return if none is found
	 * @return The found IP address, or $default if not found
	 * @see self::_find_remote_key
	 */
	public static function remote($default = "0.0.0.0", array $context = null) {
		$contexts = $context === null ? array(
			$_SERVER,
			$_ENV
		) : array(
			$context
		);
		foreach ($contexts as $context) {
			$ip = self::_find_remote_key($context);
			if ($ip !== false) {
				return $ip;
			}
		}
		return $default;
	}
	/**
	 * Returns the remote IP address, interpreting web server/proxy server intermediate IP addresses
	 * if necessary.
	 * Looks in $_ENV and $_SERVER for IP addresses.
	 *
	 * @param string $default
	 *        	A default IP address to return if none is found
	 * @return The found IP address, or $default if not found
	 * @see self::_find_ip_key
	 */
	public static function server($default = "0.0.0.0") {
		return avalue($_SERVER, 'SERVER_ADDR', $default);
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

