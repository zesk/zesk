<?php
/**
 * Integration with operating system. Host name, process ID, system services, load averages, volume info.
 *
 * $URL: https://code.marketacumen.com/zesk/trunk/classes/system.inc $
 * @package zesk
 * @subpackage default
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2006, Market Acumen, Inc.
 */

/**
 *
 * @author kent
 *
 */
class system {
	public static function host_id() {
		return zesk::get('HOST', aevalue($_ENV, 'HOST', aevalue($_SERVER, 'HOST', 'localhost')));
	}
	public static function uname() {
		$name = zesk::get('uname');
		if ($name) {
			return $name;
		}
		$name = zesk::hook_array("uname", array(), php_uname('n'));
		zesk::set('uname', $name);
		return $name;
	}
	
	/**
	 * Get current process ID
	 * 
	 * @return integer
	 */
	public static function process_id() {
		return zesk::pid();
	}
	
	/**
	 * Load IP addresses for this sytem
	 * 
	 * @return array of interface => $ip
	 */
	public static function ip_addresses() {
		$ifconfig = system::ifconfig("inet;ether");
		$ips = array();
		foreach ($ifconfig as $interface => $values) {
			if (array_key_exists('inet', $values)) {
				$ip = avalue(array_keys($values['inet']), 0);
				if (IPv4::valid($ip)) {
					$ips[$interface] = $ip;
				}
			}
		}
		return $ips;
	}
	
	/**
	 * Load MAC addresses for this sytem
	 * 
	 * @return array of interface => $ip
	 */
	public static function mac_addresses() {
		$ifconfig = system::ifconfig("inet;ether");
		$macs = array();
		foreach ($ifconfig as $interface => $values) {
			if (array_key_exists('ether', $values)) {
				$mac = avalue(array_keys($values['ether']), 0);
				$macs[$interface] = $mac;
			}
		}
		return $macs;
	}
	
	/**
	 * Run ifconfig configuration utility and parse results
	 * 
	 * @param string $filter
	 * @return array
	 */
	public static function ifconfig($filter = null) {
		$result = array();
		try {
			$cache = Cache::register(__METHOD__)->expire_after(60);
			if ($cache->command) {
				$command = $cache->command;
			} else {
				$command = $cache->command = zesk::execute("ifconfig");
			}
			$interface = null;
			$flags = null;
			foreach ($command as $line) {
				if (preg_match('/^[^\s]/', $line)) {
					list($interface, $flags) = pair($line, " ");
					$interface = rtrim($interface, ":");
					$result[$interface] = array(
						'flags' => ltrim($flags)
					);
				} else {
					$line = trim($line);
					$pairs = explode(" ", $line);
					$type = rtrim(array_shift($pairs), ":");
					$id = str::unprefix(array_shift($pairs), "addr:");
					$result[$interface][$type][$id] = array(
						"value" => $id,
						$type => $id
					);
					while (count($pairs) > 1) {
						$name = rtrim(array_shift($pairs), ":");
						$value = array_shift($pairs);
						$result[$interface][$type][$id][$name] = $value;
					}
				}
			}
		} catch (Exception $e) {
			$result = array(
				"localhost" => array(
					"127.0.0.1" => array(
						"inet" => "127.0.0.1",
						"inet6" => "::1"
					)
				)
			);
		}
		if ($filter !== null) {
			foreach ($result as $interface => $values) {
				if (is_array($values)) {
					if (!arr::has_any($values, $filter)) {
						unset($result[$interface]);
					}
				}
			}
		}
		return $result;
	}
	
	/**
	 * Determine system current load averages using /proc/loadavg or system call to uptime
	 *
	 * @return array:float Uptime averages for the past 1 minute, 5 minutes, and 15 minutes (typically)
	 */
	public static function load_averages() {
		$uptime_string = null;
		if (file_exists("/proc/loadavg")) {
			$uptime_string = explode(" ", zesk\file::contents("/proc/loadavg", ""));
		} else {
			ob_start();
			system("/usr/bin/uptime");
			$uptime = trim(ob_get_clean());
			$pattern = ":";
			$pos = strrpos($uptime, $pattern);
			if ($pos !== false) {
				$uptime_string = explode(" ", str_replace(",", "", trim(substr($uptime, $pos + strlen($pattern)))));
			}
		}
		$loads = array(
			floatval($uptime_string[0]),
			floatval($uptime_string[1]),
			floatval($uptime_string[2])
		);
		return $loads;
	}
	
	/**
	 * Retrieve volume information in a parsed manner from system call to "df"
	 *
	 * @param string $volume Request for a specific volume (passed to df)
	 * @return array:array
	 */
	public static function volume_info($volume = "") {
		ob_start();
		$max_tokens = 10;
		$args = $volume ? ' ' . escapeshellarg($volume) : '';
		$result = system("/bin/df -lk$args 2> /dev/null");
		$volume_info = trim(ob_get_clean());
		if (!$result) {
			return array();
		}
		$volume_info = explode("\n", preg_replace('/[ \t]+/', ' ', $volume_info));
		$headers = explode(" ", array_shift($volume_info), $max_tokens);
		// FreeBSD:	Filesystem  1024-blocks     Used Avail 		Capacity 	Mounted on
		// Debian:	Filesystem  1K-blocks       Used Available	Use%		Mounted on
		// Linux:	Filesystem  1K-blocks       Used Available	Use%		Mounted on
		$normalized_headers = array(
			"1024-blocks" => "total",
			"1k-blocks" => "total",
			"avail" => "free",
			"available" => "free",
			"use%" => "used_percent",
			"capacity" => "used_percent",
			"mounted" => "path"
		);
		foreach ($headers as $i => $h) {
			$h = strtolower($h);
			$headers[$i] = avalue($normalized_headers, $h, $h);
		}
		$result = array();
		foreach ($volume_info as $line) {
			$columns = explode(" ", $line, $max_tokens);
			$row = array();
			foreach ($columns as $i => $column) {
				$row[$headers[$i]] = $column;
			}
			$row['total'] *= 1024;
			$row['used'] *= 1024;
			$row['free'] *= 1024;
			$row['used_percent'] = intval(substr($row['used_percent'], 0, -1));
			$result[$row['path']] = $row;
		}
		return $result;
	}
	
	/**
	 * When outputting to terminal windows which support them, this generates a growl message on the
	 * host
	 * terminal machine.
	 *
	 * Usage:
	 * <code>echo system::growl("At 32, Mozart had three symphonies.");</code>
	 *
	 * @param string $message
	 * @return string
	 */
	public static function growl($message) {
		return chr(033) . "]9;'$message'" . chr(007);
	}
	
	/**
	 * Based on http://www.novell.com/coolsolutions/feature/11251.html
	 *
	 * Determine the distribution we're running on.
	 *
	 * Returns an array with three keys:
	 *
	 * - brand - The brand for this system "Debian", "Fedora", etc.
	 * - distro - The system release name
	 * - release - The release version number
	 *
	 * @return array:array
	 */
	public static function distro() {
		static $distro_tips = array(
			"Novell SUSE" => "/etc/SUSE-release",
			"Red Hat" => array(
				"/etc/redhat-release",
				"/etc/redhat_version"
			),
			"Fedora" => "/etc/fedora-release",
			"Slackware" => array(
				"/etc/slackware-release",
				"/etc/slackware-version"
			),
			"Debian" => array(
				"/etc/debian_release",
				"/etc/debian_version"
			),
			"Mandrake" => "/etc/mandrake-release",
			"Yellow dog" => "/etc/yellowdog-release",
			"Sun JDS" => "/etc/sun-release",
			"Solaris/Sparc" => "/etc/release",
			"Gentoo" => "/etc/gentoo-release",
			"UnitedLinux" => "/etc/UnitedLinux-release",
			"ubuntu" => "/etc/lsb-release"
		);
		static $sysnames = array(
			"Darwin" => "Mac OS X"
		);
		$relname = php_uname('r');
		foreach ($distro_tips as $distro => $files) {
			$files = to_list($files);
			foreach ($files as $file) {
				if (file_exists($file)) {
					return array(
						"brand" => $distro,
						"distro" => trim(file_get_contents($file)),
						"release" => $relname
					);
				}
			}
		}
		$sysname = php_uname('s');
		return array(
			"brand" => avalue($sysnames, $sysname, $sysname),
			"distro" => $sysname,
			"release" => $relname
		);
	}
	public static function memory_limit() {
		$limit = ini_get('memory_limit');
		if (intval($limit) < 0) {
			return intval(0xFFFFFFFF);
		}
		return number::parse_bytes($limit);
	}
	
	/**
	 * @todo Move to an implementation-specific location
	 * @deprecated 2014-01-01
	 * @param unknown $message
	 */
	public static function critical_alert($message) {
		$message = trim($message);
		if (empty($message)) {
			return;
		}
		$servers = arr::trim_clean(to_list(zesk::get('MONITOR_SERVICES_SERVERS'), null, " "));
		if (count($servers) === 0) {
			syslog::emergency("critical_alert: MONITOR_SERVICES_SERVERS is empty, can not deliver \"$message\"");
			exit(1);
		}
		$nfs_home = zesk::get('NFS_CLUSTER_HOME');
		if (empty($nfs_home)) {
			syslog::emergency("critical_alert: NFS_CLUSTER_HOME is empty, can not deliver \"$message\"");
			exit(2);
		}
		$hash = md5($message);
		foreach ($servers as $server) {
			$spath = path($nfs_home, "host", $server, 'monitor-services/messages/');
			if (!is_dir($spath)) {
				if (!mkdir($spath, 0770, true)) {
					syslog::emergency("critical_alert: can not create $spath, can not deliver \"$message\"");
					exit(3);
				}
			}
			if (!file_put_contents(path($spath, $hash), $message)) {
				syslog::emergency("critical_alert: can not create $spath, can not deliver \"$message\"");
			}
		}
	}
	
	/**
	 * Retrieve daemontools service status. Move to daemontools module, probably.
	 *
	 * @todo Move this to an implementation-specific class
	 * @deprecated 2015-01-01
	 */
	public static function services_status() {
		$path = path(zesk::get('MONITOR_SERVICES_PATH'), 'status/services');
		$status = zesk\file::contents($path, null);
		if ($status === null) {
			return $status;
		}
		$status = explode("\n", $status);
		$result = array();
		foreach ($status as $status_line) {
			// monitor-services/log: up (pid 16879) 2675829 seconds
			// monitor-services: up (pid 16906) 2675829 seconds
			// ruler-reader: up (pid 59124) 1209 seconds, want down
			// ruler-reader: down 3 seconds, normally up
			$matches = false;
			if (preg_match('/([^:]+): (\w+) .* ([0-9]+) seconds/', $status_line, $matches)) {
				$name = $matches[1];
				$service = array();
				$service['name'] = $name;
				$service['state'] = $matches[2];
				$service['seconds'] = $matches[3];
				$result[$name] = $service;
			}
		}
		return $result;
	}
	
	/**
	 * Retrieve an ID used for a daemon which may be running in a different installation than the original
	 *
	 * @todo Move this to application-specific location
	 * @deprecated 2015-12-30
	 * @return string
	 */
	public static function daemon_host_id() {
		$host = self::host_id();
		$path = substr(md5(__FILE__), 0, 8);
		if (strpos(__FILE__, "test") !== false)
			return "$host-$path-test";
		return "$host-$path";
	}
	
	/**
	 * Retrieve an ID used for a daemon which may be running in a different installation than the original
	 *
	 * @todo Move this to application-specific location
	 * @deprecated 2015-12-30
	 * @return string
	 */
	public static function group_host_id() {
		$path_contains_test = strpos(__FILE__, zesk::get('LIVE_TEST_PATH', "test/"));
		if (zesk::production()) {
			return $path_contains_test !== false ? "test" : "live";
		} else {
			return 'test';
		}
	}
}

