<?php
/**
 * $URL: https://code.marketacumen.com/zesk/trunk/modules/server/classes/cluster.inc $
 * @package zesk
 * @subpackage system
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2010, Market Acumen, Inc.
 * @deprecated Move to specific module
 */

use zesk\file as file;

/**
 * Roles are defined in the system environment file /etc/environment.sh
 *
 * One server is defined as a master web server, another as the master database server
 * @deprecated 2014-01
 */
class cluster {
	public static function resolver_domain() {
		static $resolver_domain = null;
		if ($resolver_domain !== null) {
			return $resolver_domain;
		}
		$lines = file(zesk::get('RESOLV_CONF_PATH', '/etc/resolv.conf'));
		foreach ($lines as $line) {
			$line = trim($line);
			if ($line === "" || $line[0] === '#') {
				continue;
			}
			list($label, $value) = pair($line, " ", $line, null);
			if ($label === 'domain') {
				$resolver_domain = $value;
				return $value;
			}
		}
		$resolver_domain = false;
		return false;
	}
	
	public static function host_to_short_name($h) {
		$d = self::resolver_domain();
		if (str::ends($h, $d)) {
			$h = rtrim(substr($h, 0, -strlen($d)), '.');
		}
		return $h;
	}
	
	public static function host_to_internal_name($h) {
		$d = self::resolver_domain();
		if (!str::ends($h, $d)) {
			$h = domain($h, $d);
		}
		$hosts = cluster::hosts();
		foreach ($hosts as $host) {
			if (str::ends($host, $h)) {
				return $host;
			}
		}
		return false;
	}
	
	public static function web_master() {
		$result = trim(zesk::get('MASTER_SERVER_WEB', ''));
		if (empty($result))
			die("No master web server name identified via global MASTER_SERVER_WEB");
		return $result;
	}
	
	public static function database_slaves() {
		return arr::trim_clean(to_list(zesk::get('DATABASE_SLAVES'), '', ' '));
	}
	
	public static function database_master() {
		$result = trim(zesk::get('MASTER_SERVER_DB', ''));
		if (empty($result))
			die("No master database server name identified via global MASTER_SERVER_DB");
		return $result;
	}
	
	public static function is_web_master() {
		return cluster::is_me(cluster::web_master());
	}
	
	public static function is_me($host_id) {
		$my_name = system::host_id();
		if (!IPv4::valid($host_id)) {
			return ($host_id === $my_name) || ($host_id == "lan.$my_name");
		}
		$hosts = cluster::hosts($host_id);
		foreach ($hosts as $name) {
			if ($name === $my_name || $name === "lan.$my_name") {
				return true;
			}
		}
		return false;
	}
	
	public static function is_database_master() {
		return cluster::is_me(self::database_master());
	}
	
	public static function is_database_slave() {
		$slaves = self::database_slaves();
		if (!is_array($slaves)) {
			return false;
		}
		foreach ($slaves as $slave) {
			if (cluster::is_me($slave)) {
				return true;
			}
		}
		return false;
	}
	
	public static function hosts_file() {
		return zesk::get('HOSTS_FILE', "/etc/hosts");
	}
	
	public static function hosts($match = null, $filename = null) {
		if ($filename === null) {
			$filename = self::hosts_file();
		}
		try {
			$hosts = file::lines($filename);
		} catch (Exception_File_NotFound $e) {
			return array();
		}
		$result = array();
		if ($match !== null) {
			$match = to_list($match, array());
		}
		foreach ($hosts as $host_line) {
			if ($host_line[0] === '#')
				continue;
			if (is_array($match)) {
				foreach ($match as $match_string) {
					if (strpos($host_line, $match_string) === false) {
						continue 2;
					}
				}
			}
			$host_line = preg_replace('/\s+/', " ", trim($host_line));
			if (empty($host_line)) {
				continue;
			}
			if ($host_line[0] === '#') {
				continue;
			}
			$host_name = null;
			list($ip, $host_name) = explode(" ", $host_line);
			$result[$ip] = $host_name;
		}
		$result = zesk()->hooks->call_arguments("cluster::hosts_alter", array($result), $result);
		return $result;
	}
	
	public static function hosts_trusted() {
		$trusted = self::hosts("TRUSTED");
		return zesk()->hooks->call_arguments("cluster::hosts_trusted_alter", array($trusted), $trusted);
	}
	
	public static function hosts_web() {
		return zesk()->hooks->call("cluster::hosts_web_alter", self::hosts("TRUSTED"));
	}
	
	public static function command($query, $from_host = null, $include_me = true) {
		$hosts = self::hosts_web();
		if (empty($hosts)) {
			return array();
		}
		$me = system::host_id();
		$salt = md5(mt_rand() . '|' . microtime());
		$query["key"] = md5(zesk::get("web_key") . '-' . $salt . '-' . $me);
		$query['host'] = $me;
		$query['salt'] = $salt;
		$result = array();
		if ($from_host) {
			$i = arr::strstr($from_host, $hosts);
			if ($i !== false) {
				$hosts = array(
					$hosts[$i]
				);
			} else {
				return false;
			}
		}
		foreach ($hosts as $host) {
			if (!$include_me && $host === $me) {
				continue;
			}
			$url = url::query_format("http://$host/manage.php", $query);
			if (zesk::getb('cluster::command.debug')) {
				echo "$url\n";
			}
			$client = new Net_HTTP_Client($url);
			$content = $client->go();
			if ($from_host) {
				return $content;
			}
			$result[$host] = $content;
		}
		return $result;
	}
	
	public static function unlink($f) {
		if (!file_exists($f)) {
			return false;
		}
		$f = realpath($f);
		$result = @unlink($f);
		$result = self::command(array(
			"action" => "delete", 
			"delete" => $f
		), null, true);
		return $result;
	}
	
	public static function service_sync_files($on_off) {
		return self::command(array(
			"action" => "sync_files_control", 
			"sync_files" => str::from_bool($on_off)
		));
	}
	
	public static function service_restart($service) {
		if (empty($service)) {
			throw new Exception("cluster::service_restart: \$service should not be null or empty");
		}
		if ($service[0] !== '/') {
			$service_path = path("/service", $service, "supervise");
			if (!is_dir($service_path)) {
				throw new Exception("$service_path does not exist, skipping ...");
			}
		} else if (begins("/service", $service)) {
			throw new Exception("You must specify a service name, or a path from /service ...");
		} else if (!is_dir($service)) {
			throw new Exception("$service does not appear to be a valid service ...");
		} else if (!is_dir(path($service, "supervise"))) {
			throw new Exception(path($service, "supervise") . " does not exist, skipping ...");
		} else {
			$service = basename($service);
		}
		return self::command(array(
			"action" => "service_control", 
			"service" => $service, 
			"command" => "restart"
		));
	}
}
