<?php
/**
 * $URL: https://code.marketacumen.com/zesk/trunk/modules/syslog/classes/syslog.inc $
 * @package zesk
 * @subpackage system
 * @author kent
 * @copyright Copyright &copy; 2009, Market Acumen, Inc.
 * Created on Sun Apr 05 17:48:50 EDT 2009 17:48:50
 *
 * Simple syslog implementation, RFC 3164
 * Facility values:
 * 0 kernel messages
 * 1 user-level messages
 * 2 mail system
 * 3 system daemons
 * 4 security/authorization messages
 * 5 messages generated internally by syslogd
 * 6 line printer subsystem
 * 7 network news subsystem
 * 8 UUCP subsystem
 * 9 clock daemon
 * 10 security/authorization messages
 * 11 FTP daemon
 * 12 NTP subsystem
 * 13 log audit
 * 14 log alert
 * 15 clock daemon
 * 16 local user 0 (local0) (default value)
 * 17 local user 1 (local1)
 * 18 local user 2 (local2)
 * 19 local user 3 (local3)
 * 20 local user 4 (local4)
 * 21 local user 5 (local5)
 * 22 local user 6 (local6)
 * 23 local user 7 (local7)
 *
 * Severity values:
 * 0 Emergency: system is unusable
 * 1 Alert: action must be taken immediately
 * 2 Critical: critical conditions
 * 3 Error: error conditions
 * 4 Warning: warning conditions
 * 5 Notice: normal but significant condition (default value)
 * 6 Informational: informational messages
 * 7 Debug: debug-level messages
 */

if (!defined('ZESK_ROOT'))
	die('ZESK_ROOT must be defined prior to including ' . __FILE__);


class syslog {
	const facility_kernel = 0;
	const facility_user = 1;
	const facility_mail = 2;
	const facility_system_daemons = 3;
	const facility_security = 4;
	const facility_syslogd = 5;
	const facility_lpr = 6;
	const facility_news = 7;
	const facility_uucp = 8;
	const facility_clock = 9;
	const facility_security1 = 10;
	const facility_ftp = 11;
	const facility_ntp = 12;
	const facility_log_audit = 13;
	const facility_log_alert = 14;
	const facility_clock1 = 15;
	const facility_local0 = 16;
	const facility_local1 = 17;
	const facility_local2 = 18;
	const facility_local3 = 19;
	const facility_local4 = 20;
	const facility_local5 = 21;
	const facility_local6 = 22;
	const facility_local7 = 23;
	
	const severity_emergency = 0;
	const severity_alert = 1;
	const severity_critical = 2;
	const severity_error = 3;
	const severity_warning = 4;
	const severity_notice = 5;
	const severity_info = 6;
	const severity_debug = 7;
	
	private function __construct() {
	
	}
	
	static function send($message, $host = null, $facility = null, $severity = self::severity_error) {
		$facility = ($facility === null) ? zesk::get('self::facility', self::facility_local0) : $facility;
		$host = ($host === null) ? zesk::get('SYSLOG_HOSTS', "127.0.0.1") : $host;
		
		$hosts = to_list($host, $host);
		
		$prefix = "<" . ($facility * 8 + $severity) . ">";
		
		$now = time();
		$timestamp = date("M", $now) . " " . substr(" " . date("j", $now), -2) . " " . date("H:i:s", $now);
		
		$msg = substr("$prefix$timestamp $message", 0, 1024);
		foreach ($hosts as $host) {
			$errno = false;
			$errstr = false;
			list($host, $port) = pair($host, ":", $host, 514);
			$port = intval($port);
			$fp = fsockopen("udp://$host", $port, $errno, $errstr);
			fwrite($fp, $msg);
			fclose($fp);
		}
		
		return $msg;
	}
	
	public static function emergency($message, $host = null, $facility = null) {
		self::send($message, $host, $facility, self::severity_emergency);
	}
	
	public static function alert($message, $host = null, $facility = null) {
		self::send($message, $host, $facility, self::severity_alert);
	}
	
	public static function critical($message, $host = null, $facility = null) {
		self::send($message, $host, $facility, self::severity_critical);
	}
	
	public static function error($message, $host = null, $facility = null) {
		self::send($message, $host, $facility, self::severity_error);
	}
	
	public static function warning($message, $host = null, $facility = null) {
		self::send($message, $host, $facility, self::severity_warning);
	}
	
	public static function notice($message, $host = null, $facility = null) {
		self::send($message, $host, $facility, self::severity_notice);
	}
	
	public static function info($message, $host = null, $facility = null) {
		self::send($message, $host, $facility, self::severity_info);
	}
	
	public static function debug($message, $host = null, $facility = null) {
		self::send($message, $host, $facility, self::severity_info);
	}
}

