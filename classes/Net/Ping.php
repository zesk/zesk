<?php
namespace zesk;

class Net_Ping {
    private static $icmp_proto = null;

    public static function _init() {
        if (self::$icmp_proto === null) {
            self::$icmp_proto = getprotobyname("icmp");
        }
    }

    /**
     * Note: on most systems you must be root to create raw sockets and call this function.
     *
     * @param string $host IP address of hostname to ping
     * @param double $timeout_seconds Seconds or fractional seconds to require a response by remote host. By default timeout is 1 second.
     * @return double Ping response time, or false if host did not reply in time/unreachable.
     */
    public static function ping($host, $timeout_seconds = 1) {
        self::_init();
        /* ICMP ping packet with a pre-calculated checksum */
        $package = "\x08\x00\x7d\x4b\x00\x00\x00\x00PingHost";
        $socket = socket_create(AF_INET, SOCK_RAW, self::$icmp_proto);
        $seconds = intval($timeout_seconds);
        $microseconds = ($timeout_seconds - $seconds) * 1000000;
        socket_set_option($socket, SOL_SOCKET, SO_RCVTIMEO, array(
            'sec' => $seconds,
            'usec' => $microseconds,
        ));
        socket_connect($socket, $host, null);
        $ts = microtime(true);
        socket_send($socket, $package, strlen($package), 0);
        if (socket_read($socket, 255)) {
            $result = microtime(true) - $ts;
        } else {
            $result = false;
        }
        socket_close($socket);
        return $result;
    }
}
