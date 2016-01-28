<?php
/**
 * $URL: https://code.marketacumen.com/zesk/trunk/classes/alerter.inc $
 * @package zesk
 * @subpackage system
 * @author kent
 * @copyright Copyright &copy; 2010, Market Acumen, Inc.
 */

class alerter {
	public static function exists() {
		$servers = self::servers();
		if (empty($servers)) {
			die("MONITOR_SERVICES_SERVERS is empty");
		}
		foreach ($servers as $server) {
			$spath = self::path_messages($server);
			if (!is_dir($spath)) {
				continue;
			}
			if (!dir::is_empty($spath)) {
				return true;
			}
		}
		return false;
	}

	public static function servers($set = null) {
		if ($set !== null) {
			$set = to_list($set, array(), ";");
			zesk::set('MONITOR_SERVICES_SERVERS', implode(" ", $set));
		}
		return explode(" ", zesk::get('MONITOR_SERVICES_SERVERS'));
	}

	public static function path_messages($server) {
		return path(zesk::get('NFS_CLUSTER_HOME'), 'host', $server, 'monitor-services/messages');
	}

	public static function delete_all() {
		$servers = self::servers();
		if (empty($servers)) {
			die("MONITOR_SERVICES_SERVERS is empty");
		}
		$result = true;
		foreach ($servers as $server) {
			$spath = self::path_messages($server);
			if (!is_dir($spath)) {
				continue;
			}
			if (!dir::delete_contents($spath)) {
				$result = false;
			}
		}
		return $result;
	}

	public static function notify($message) {
		$servers = self::servers();
		if (empty($servers)) {
			die("MONITOR_SERVICES_SERVERS is empty");
		}

		$message = trim($message);
		$message_hash = md5($message);
		foreach ($servers as $server) {
			$spath = self::path_messages($server);
			if (!is_dir($spath)) {
				mkdir($spath, 0770, true);
			}
			file_put_contents(path($spath, $message_hash), $message);
		}
	}

	/*
	* System Critical Notifications
	*/
	public static function notify_intermittently($tracking_file, $after_count, $message) {
		if (empty($tracking_file)) {
			throw new Exception_Parameter("\$tracking_file is empty");
		}
		if (!file_exists($tracking_file)) {
			file_put_contents($tracking_file, 1);
			return;
		}
		$alerter_file = $tracking_file . "-alert.txt";
		$count = file::atomic_increment($tracking_file);
		$do_send = false;

		if ($count % $after_count !== 0) {
			return;
		}
		if (!file_exists($alerter_file)) {
			$do_send = true;
			$map['reason'] = "first";
		} else {
			$mtime = filemtime($alerter_file);
			if ($mtime < time() - 15 * 60) {
				$do_send = true;
				$map['reason'] = "last alert " . date('Y/m/d H-i-s', $mtime);
			}
		}
		if ($do_send) {
			alerter::notify($message);
		}
	}

	/*
	* System Critical Notifications
	*/
	public static function mail($tracking_file, $after_count, $to, $from, $subject, $template_file, $map, $cc = false) {
		if (empty($tracking_file)) {
			throw new Exception_Parameter("\$tracking_file is empty");
		}
		if (!file_exists($tracking_file)) {
			file_put_contents($tracking_file, 1);
			return;
		}
		$alerter_file = $tracking_file . "-alert.txt";
		$count = file::atomic_increment($tracking_file);
		$do_send = false;

		if ($count % $after_count !== 0) {
			return;
		}
		if (!file_exists($alerter_file)) {
			$do_send = true;
			$map['reason'] = "first";
		} else {
			$mtime = filemtime($alerter_file);
			if ($mtime < time() - 15 * 60) {
				$do_send = true;
				$map['reason'] = "last alert " . date('Y/m/d H-i-s', $mtime);
			}
		}
		if ($do_send) {
			$map['host'] = aevalue($_ENV, "HOST", aevalue($_SERVER, 'HTTP_HOST', "unknown"));
			$map['count'] = $count;
			$map['tracking_file'] = $tracking_file;
			$map['when'] = date('Y/m/d H-i-s');
			$map['backtrace'] = _backtrace();
			mail::map($to, $from, $subject, $template_file, $map, $cc);
			$alerter_file_data = map($subject, $map) . "\n\n" . map(file_get_contents($template_file), $map);
			file_put_contents($alerter_file, $alerter_file_data);
		}
	}
}
