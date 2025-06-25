<?php
declare(strict_types=1);
/**
 * @copyright &copy; 2025, Market Acumen, Inc.
 */

namespace zesk\Net;

class Ping
{
	private static $icmp_proto = null;

	public static function _init(): void
	{
		if (self::$icmp_proto === null) {
			self::$icmp_proto = getprotobyname('icmp');
		}
	}

	/**
	 * Note: on most systems you must be root to create raw sockets and call this function.
	 *
	 * @param string $host IP address of hostname to ping
	 * @param flaot $timeout Seconds or fractional seconds to require a response by remote host. By default timeout is 1
	 *                        second.
	 * @return float Ping response time
	 * @throws Exception if host did not reply in time/unreachable.
	 */
	public static function ping(string $host, float $timeout = 1): float
	{
		self::_init();
		/* ICMP ping packet with a pre-calculated checksum */
		$package = "\x08\x00\x7d\x4b\x00\x00\x00\x00PingHost";
		$socket = socket_create(AF_INET, SOCK_RAW, self::$icmp_proto);
		$seconds = intval($timeout);
		$microseconds = ($timeout - $seconds) * 1000000;
		socket_set_option($socket, SOL_SOCKET, SO_RCVTIMEO, [
			'sec' => $seconds,
			'usec' => $microseconds,
		]);
		socket_connect($socket, $host, null);
		$ts = microtime(true);
		socket_send($socket, $package, strlen($package), 0);
		if (socket_read($socket, 255)) {
			$result = microtime(true) - $ts;
		} else {
			throw new Exception('Failed to read from socket');
		}
		socket_close($socket);
		return $result;
	}
}
