<?php
declare(strict_types=1);

namespace zesk;

/*
IPv6 address formats

The size and format of the IPv6 address expand addressing capability.

The IPv6 address size is 128 bits.

The preferred IPv6 address representation is: x:x:x:x:x:x:x:x, where each x is the hexadecimal values of the eight 16-bit pieces of the address.

IPv6 addresses range from 0000:0000:0000:0000:0000:0000:0000:0000 to ffff:ffff:ffff:ffff:ffff:ffff:ffff:ffff.

In addition to this preferred format, IPv6 addresses might be specified in two other shortened formats:

Omit leading zeros:

Specify IPv6 addresses by omitting leading zeros.
For example, IPv6 address 1050:0000:0000:0000:0005:0600:300c:326b can be written as 1050:0:0:0:5:600:300c:326b.

Double colon:

Specify IPv6 addresses by using double colons (`::`) in place of a series of zeros.
6
For example, IPv6 address ff06:0:0:0:0:0:0:c3 can be written as ff06::c3. Double colons can be used only once in an IP address.

An alternative format for IPv6 addresses combines the colon and dotted notation, so the IPv4 address can be embedded in the IPv6 address.

Hexadecimal values are specified for the left-most 96 bits, and decimal values are specified for the right-most 32 bits indicating the embedded IPv4 address.

This format ensures compatibility between IPv6 nodes and IPv4 nodes when you are working in a mixed network environment.

IPv4-mapped IPv6 address uses this alternative format. This type of address is used to represent IPv4 nodes as IPv6 addresses.

It allows IPv6 applications to communicate directly with IPv4 applications. For example, `0:0:0:0:0:ffff:192.1.56.10` and `::ffff:192.1.56.10/96` (shortened format).

All of these formats are valid IPv6 address formats.

*/

use zesk\Exception\ParameterException;

class IPv6
{
	public const IP6PREFIXIP4 = '::ffff:';

	/**
	 *
	 */
	public const TEXT_COLUMN_LENGTH = (8 * 4) + (8 - 1);

	/**
	 *
	 */
	public const BINARY_COLUMN_LENGTH = self::BITS / 8;

	/**
	 *
	 */
	public const BITS = 128;

	/**
	 * @param string $address
	 * @return bool
	 */
	public static function valid(string $address): bool
	{
		/* | FILTER_FLAG_GLOBAL_RANGE */
		return filter_var($address, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6 | FILTER_NULL_ON_FAILURE) !== null;
	}

	/**
	 * Cleans all non-address characters
	 *
	 * @param string $address
	 * @return string
	 */
	public static function clean(string $address): string
	{
		return preg_replace('/[^:.0-9a-f]/', '', strtolower($address));
	}

	/**
	 * @param string $binary
	 * @return string
	 * @throws ParameterException
	 */
	public static function fromBinary(string $binary): string
	{
		if (strlen($binary) !== self::BINARY_COLUMN_LENGTH) {
			throw new ParameterException('Need string of {n} bytes, {actual} passed', [
				'n' => self::BINARY_COLUMN_LENGTH, 'actual' => strlen($binary),
			]);
		}
		return inet_ntop(pack('A' . strlen($binary), $binary));
	}

	/**
	 * @param string $ip
	 * @return string
	 * @throws ParameterException
	 */
	public static function toBinary(string $ip): string
	{
		if (self::valid($ip)) {
			return inet_pton($ip);
		}

		throw new ParameterException('Invalid IPv6 address: {ip}', ['ip' => $ip]);
	}

	public static function expand(string $ip): string
	{
		$hex = unpack('H*hex', inet_pton($ip));
		return substr(preg_replace('/([A-f0-9]{4})/', '$1:', $hex['hex']), 0, -1);
	}

	/**
	 * @param mixed $ip4
	 * @return string
	 */
	public static function fromIPv4(mixed $ip4): string
	{
		return self::IP6PREFIXIP4 . IPv4::fromInteger($ip4);
	}

	/**
	 * @param string $ip6
	 * @return bool
	 */
	public static function isIPv4(string $ip6): bool
	{
		$simplified = self::simplify($ip6);
		$items = explode(':', $simplified);
		return IPv4::valid($items[count($items) - 1]);
	}

	/**
	 * @param string $address
	 * @return string
	 */
	public static function simplify(string $address): string
	{
		$address = strtolower($address);
		$address = substr(preg_replace('/:0+([0-9a-f])/', ':$1', ":$address"), 1);
		$address = preg_replace('/^(0:)+|:(0:)+/', '::', $address);
		return $address;
	}
}
