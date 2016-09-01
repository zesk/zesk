<?php
/**
 * $URL: https://code.marketacumen.com/zesk/trunk/modules/ipban/classes/ipban.inc $
 *
 * @package zesk
 * @subpackage ipban
 * @copyright (c) 2013 Market Acumen, Inc.
 */
use zesk\Timestamp as Timestamp;

/**
 * @see Class_IPBan
 * @author kent
 *
 */
class IPBan extends Object {

	/**
	 * Server admin bans.
	 * Highest level of confidence.
	 *
	 * @var integer
	 */
	const severity_known = 0;

	/**
	 * Visible hacking attempts
	 *
	 * @var ingeger
	 */
	const severity_hacking = 1;

	/**
	 * Suspicious behavior
	 *
	 * @var integer
	 */
	const severity_suspicious = 2;

	/**
	 * Noticed behavior
	 *
	 * @var integer
	 */
	const severity_notice = 3;

	private static $severities = array(
		"hacking" => self::severity_hacking,
		"known" => self::severity_known,
		"notice" => self::severity_notice,
		"suspicious" => self::severity_suspicious
	);

	public static function severity_from_string($string) {
		return avalue(self::$severities, preg_replace('/[^a-z]/', '', strtolower($string)), self::severity_hacking);
	}

	public static function severity_to_string($severity) {
		return avalue(array_flip(self::$severities), $severity, null);
	}

	/**
	 * Lodge a complaint
	 *
	 * @param string $ip
	 *        	IP
	 * @param integer $severity
	 *        	const_hacking, const_suspicious
	 * @param string $message
	 *        	Message regarding what's up
	 * @param array $arguments
	 *        	Optional arguments to place in the message (logged)
	 * @return NULL number
	 */
	public static function complain($ip, $severity, $message = "", array $arguments = array()) {
		if (!IPv4::valid($ip)) {
			log::debug("Invalid IP {ip} passed to {class}::complain", array(
				"class" => __CLASS__,
				"ip" => $ip
			));
			return null;
		}
		$ipi = IPv4::to_integer($ip);
		$severity = intval($severity);
		$count = max(avalue($arguments, 'count', 1), 1);
		$complaints = Object::class_query(__CLASS__)->what("complaints", "complaints")->where("ip", $ipi)->one_integer("complaints");
		if ($complaints > 0) {
			Object::class_query_update(__CLASS__)->value(array(
				"*recent" => "UTC_TIMESTAMP()",
				"*severity" => "LEAST(severity, $severity)",
				"*complaints" => "complaints + $count"
			))->where("ip", $ipi)->execute();
		} else {
			Object::factory(__CLASS__, array(
				"ip" => $ip,
				"server" => IPv4::server(),
				"severity" => intval($severity),
				"complaints" => $count
			))->store();
		}
		$complaints++;
		log::warning("IP {ip} Complaint #{complaints}: $message", array(
			"ip" => $ip,
			"complaints" => $complaints
		) + $arguments);
		return $complaints;
	}

	/**
	 * Banned since when?
	 *
	 * @param unknown $when
	 * @param array $options
	 * @return Ambigous <Ambigous, string, multitype:unknown Ambigous <mixed, array> >
	 */
	public static function ban_since($when = null, array $options = array()) {
		$query = Object::class_query(__CLASS__)->what("*ip", "INET_NTOA(ip)")->where(array(
			array(
				array(
					"complaints|>=" => avalue($options, "known", 1),
					"severity" => self::severity_known
				),
				array(
					"complaints|>=" => avalue($options, "hacks", 2),
					"severity" => self::severity_hacking
				),
				array(
					"complaints|>=" => avalue($options, "suspicious", 3),
					"severity" => self::severity_suspicious
				),
				array(
					"complaints|>=" => avalue($options, "notice", 3),
					"severity" => self::severity_notice
				)
			)
		));
		if ($when) {
			$query->where(null, map("(created >= {when} OR recent >= {when})", array(
				"when" => $query->sql()->quote_text($when)
			)));
		}
		log::debug("ban_since: {query}", array(
			"query" => $query->__toString()
		));
		return $query->to_array("ip", "ip");
	}

	/**
	 * Allowed since last check
	 *
	 * @param mixed $when
	 * @param array $options
	 * @return array
	 */
	public static function allow_since(Timestamp $when = null, array $options) {
		if (!$when) {
			return array();
		}
		$ips = array();
		foreach (array(
			self::severity_hacking => avalue($options, "hacking_expire_seconds", 7 * 24 * 3600),
			self::severity_suspicious => avalue($options, "suspicious_expire_seconds", 24 * 3600),
			self::severity_notice => avalue($options, "notice_expire_seconds", 8 * 3600)
		) as $severity => $seconds) {
			$before = clone $when;
			$before->add_unit("second", -abs($seconds));
			$where = array(
				"severity" => $severity,
				"recent|<=" => $before
			);
			$ips += $found_ips = Object::class_query(__CLASS__)->what("*ip", "INET_NTOA(ip)")->where($where)->to_array("ip", "ip");
			log::debug("Allow IPs {severity} {ips}", array(
				"severity" => $severity,
				"ips" => $found_ips
			));
			$delete = Object::class_query_delete(__CLASS__)->where($where);
			//log::notice($delete->__toString());
			$delete->execute();
		}
		return $ips;
	}
}