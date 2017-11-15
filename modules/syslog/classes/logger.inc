<?php

/**
 * $URL: https://code.marketacumen.com/zesk/trunk/modules/syslog/classes/syslog/logger.inc $
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
namespace syslog;

use Psr\Log\LogLevel;
use Psr\Log\LoggerInterface;
use zesk\Application;
use zesk\Exception_Parameter;

/**
 *
 * @author kent
 */
class Logger extends \zesk\Logger implements LoggerInterface {
	
	/**
	 *
	 * @var integer
	 */
	const facility_kernel = 0;
	/**
	 *
	 * @var integer
	 */
	const facility_user = 1;
	/**
	 *
	 * @var integer
	 */
	const facility_mail = 2;
	/**
	 *
	 * @var integer
	 */
	const facility_system_daemons = 3;
	/**
	 *
	 * @var integer
	 */
	const facility_security = 4;
	/**
	 *
	 * @var integer
	 */
	const facility_syslogd = 5;
	/**
	 *
	 * @var integer
	 */
	const facility_lpr = 6;
	/**
	 *
	 * @var integer
	 */
	const facility_news = 7;
	/**
	 *
	 * @var integer
	 */
	const facility_uucp = 8;
	/**
	 *
	 * @var integer
	 */
	const facility_clock = 9;
	/**
	 *
	 * @var integer
	 */
	const facility_security1 = 10;
	/**
	 *
	 * @var integer
	 */
	const facility_ftp = 11;
	/**
	 *
	 * @var integer
	 */
	const facility_ntp = 12;
	/**
	 *
	 * @var integer
	 */
	const facility_log_audit = 13;
	/**
	 *
	 * @var integer
	 */
	const facility_log_alert = 14;
	/**
	 *
	 * @var integer
	 */
	const facility_clock1 = 15;
	/**
	 *
	 * @var integer
	 */
	const facility_local0 = 16;
	/**
	 *
	 * @var integer
	 */
	const facility_local1 = 17;
	/**
	 *
	 * @var integer
	 */
	const facility_local2 = 18;
	/**
	 *
	 * @var integer
	 */
	const facility_local3 = 19;
	/**
	 *
	 * @var integer
	 */
	const facility_local4 = 20;
	/**
	 *
	 * @var integer
	 */
	const facility_local5 = 21;
	/**
	 *
	 * @var integer
	 */
	const facility_local6 = 22;
	/**
	 *
	 * @var integer
	 */
	const facility_local7 = 23;
	/**
	 *
	 * @var integer
	 */
	const severity_emergency = 0;
	/**
	 *
	 * @var integer
	 */
	const severity_alert = 1;
	/**
	 *
	 * @var integer
	 */
	const severity_critical = 2;
	/**
	 *
	 * @var integer
	 */
	const severity_error = 3;
	/**
	 *
	 * @var integer
	 */
	const severity_warning = 4;
	/**
	 *
	 * @var integer
	 */
	const severity_notice = 5;
	/**
	 *
	 * @var integer
	 */
	const severity_info = 6;
	/**
	 *
	 * @var integer
	 */
	const severity_debug = 7;
	
	/**
	 *
	 * @var integer
	 */
	protected $facility = self::facility_local0;
	
	/**
	 * Convert from LogLevel to internal severity
	 *
	 * @var array
	 */
	static $level_map = array(
		LogLevel::EMERGENCY => self::severity_emergency,
		LogLevel::ALERT => self::severity_alert,
		LogLevel::CRITICAL => self::severity_critical,
		LogLevel::ERROR => self::severity_error,
		LogLevel::WARNING => self::severity_warning,
		LogLevel::NOTICE => self::severity_notice,
		LogLevel::INFO => self::severity_info,
		LogLevel::DEBUG => self::severity_debug
	);
	
	/**
	 *
	 * @param array $hosts
	 */
	public function __construct(Application $application, array $hosts = null, $default_facility = null) {
		if ($hosts === null) {
			$hosts = $application->configuration->path_get(__CLASS__ . "::hosts");
		}
		if (!is_array($hosts)) {
			throw new Exception_Parameter("Must supply valid list of hosts: {hosts}", array(
				"hosts" => $hosts
			));
		}
		$this->hosts = $hosts;
		if ($default_facility !== null) {
			$this->facility = $default_facility;
		} else {
			$this->facility = self::facility_local0;
		}
	}
	
	/**
	 *
	 * {@inheritdoc}
	 *
	 * @see \zesk\Logger::log()
	 */
	public function log($level, $message, array $context = array()) {
		$facility = isset($context['syslog_facility']) ? intval($context['syslog_facility']) : $this->facility;
		$hosts = isset($context['syslog_host']) ? to_list($context['syslog_host']) : $this->hosts;
		$severity = avalue(self::$level_map, $level, self::severity_emergency);
		
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
}

