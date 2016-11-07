<?php

use zesk\Timestamp;

class IPBan_IP extends Object {

	/**
	 * Black list IPs are banned/blocked/disallowed
	 *
	 * @var integer
	 */
	const status_blacklist = 0;

	/**
	 * White list IPs are allowed always
	 *
	 * @var integer
	 */
	const status_whitelist = 1;

	private static function _list($status, Timestamp $since = null) {
		$query = Object::class_query(__CLASS__)->where("status", $status)->what("*ip", "INET_NTOA(ip)");
		if ($since) {
			$query->where("when|>=", $since);
		}
		return $query->to_array("ip", "ip");
	}

	public static function blacklist(Timestamp $since = null) {
		return self::_list(self::status_blacklist, $since);
	}

	public static function whitelist(Timestamp $since = null) {
		return self::_list(self::status_whitelist, $since);
	}

	public static function add($ip, Timestamp $when = null, $status = self::status_whitelist) {
		if ($when === null) {
			$when = Timestamp::now();
		}
		return Object::factory(__CLASS__, array(
			"ip" => $ip,
			"when" => $when,
			"status" => $status
		))->store();
	}
	public static function add_whitelist($ip, Timestamp $when = null) {
		return self::add($ip, $when, self::status_whitelist);
	}

	public static function add_blacklist($ip, Timestamp $when = null) {
		return self::add($ip, $when, self::status_blacklist);
	}
}
