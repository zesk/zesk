<?php
/**
 * $URL: https://code.marketacumen.com/zesk/trunk/modules/nfs/classes/nfs/cluster.inc $
 * @package zesk
 * @subpackage system
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2010, Market Acumen, Inc.
 */
class nfs_cluster {

	public static function hosts() {
		$nfs_cluster_conf = zesk::get("NFS_CLUSTER_CONF", "/etc/nfs-cluster.conf");
		return arr::trim_clean(unquote(file($nfs_cluster_conf)));
	}

	public static function mtime_near($a, $b) {
		return abs($a - $b) < zesk::get('nfs_cluster_time_epsilon', 2);
	}

	public static function home() {
		$nfs_home = zesk::get('NFS_CLUSTER_HOME');
		if (empty($nfs_home)) {
			throw new Exception("NFS_CLUSTER_HOME is not set");
		}
		return $nfs_home;
	}

	public static function replicate_check($source, $force = false) {
		$nfs_home = self::home();
		$nfs_path = path($nfs_home, "nfs");
		if (!begins($source, $nfs_path)) {
			return true;
		}
		return self::replicate($source, $force);
	}

	public static function unlink_check($source) {
		$nfs_home = self::home();
		$nfs_path = path($nfs_home, "nfs");
		if (!begins($source, $nfs_path)) {
			return true;
		}
		return self::unlink($source);
	}

	public static function host_file($source, $host) {
		$nfs_home = self::home();
		$nfs_path = path($nfs_home, "nfs");
		$host_path = path($nfs_home, "host");
		
		if (begins($source, $nfs_path)) {
			return path($host_path, $host, substr($source, strlen($nfs_path)));
		}
		return null;
	}

	private static function failure_log($command, $comment = "") {
		$log_file = path(self::home(), 'nfs/failure-log.sh');
		error_log('# ' . gmdate('Y-m-d H:i:s') . ($comment ? " $comment" : ""), 0, $log_file);
		error_log($command, 0, $log_file);
	}

	public static function replicate($source, $force = false) {
		$nfs_home = self::home();
		$nfs_path = path($nfs_home, "nfs");
		$host_path = path($nfs_home, "host");
		$host = zesk::get('HOST');
		$hosts = self::hosts();
		if (!in_array($host, $hosts)) {
			throw new Exception("nfs_cluster::replicate: $host not in nfs-cluster.conf file: " . implode(", ", $hosts));
		}
		if (!begins($source, $nfs_path)) {
			throw new Exception("nfs_cluster::replicate: $source does not begin with $nfs_path");
		}
		$suffix = substr($source, strlen($nfs_path));
		$source_mtime = filemtime($source);
		$source_size = filesize($source);
		$result = array();
		foreach ($hosts as $chost) {
			if ($chost === $host)
				continue;
			$dest = path($host_path, $chost, $suffix);
			$dest_path = dirname($dest);
			if (!is_dir($dest_path)) {
				if (!mkdir($dest_path, 0770, true)) {
					$result[$chost] = "Failed to create $dest_path to copy $dest";
					self::failure_log("mkdir -p \"$dest_path\"");
					self::failure_log("chmod 0770 \"$dest_path\"");
					self::failure_log("cp \"$source\" \"$dest\"");
					continue;
				}
			}
			if (!$force && is_file($dest)) {
				$dest_mtime = filemtime($dest);
				$dest_size = filemtime($dest);
				if (self::mtime_near($dest_mtime, $source_mtime) && $dest_size === $source_size) {
					continue;
				}
			}
			if (!copy($source, $dest)) {
				self::failure_log("cp \"$source\" \"$dest\"");
				$result[$chost] = "Failed to copy $source to $dest";
			} else {
				touch($dest, $source_mtime);
			}
		}
		touch($source, $source_mtime);
		if (count($result) === 0) {
			return true;
		}
		return $result;
	}

	public static function unlink($source, $use_queue = true) {
		$nfs_home = self::home();
		$nfs_path = path($nfs_home, "nfs");
		$host_path = path($nfs_home, "host");
		$host = zesk::get('HOST');
		$hosts = self::hosts();
		if (!in_array($host, $hosts)) {
			throw new Exception_NotFound("nfs_cluster::replicate: $host not in nfs-cluster.conf file: " . implode(", ", $hosts));
		}
		if (!begins($source, $nfs_path)) {
			throw new Exception_File_Permission($source, "nfs_cluster::replicate: $source does not begin with $nfs_path");
		}
		$suffix = substr($source, strlen($nfs_path));
		$result = array();
		if ($use_queue) {
			$delete_queue = path($nfs_home, "nfs/.delete-queue");
			$f = fopen($delete_queue, "a");
			if (!$f) {
				return array(
					"*" => "Can't open $delete_queue"
				);
			}
			foreach ($hosts as $chost) {
				fwrite($f, path($host_path, $chost, $suffix) . "\n");
			}
			fclose($f);
		} else {
			foreach ($hosts as $chost) {
				$dest = path($host_path, $chost, $suffix);
				if (is_file($dest)) {
					if (!unlink($dest)) {
						self::failure_log("rm -f \"$dest\"");
						$result[$chost] = "Failed to delete $dest";
					}
				}
			}
		}
		if (count($result) === 0) {
			return true;
		}
		return $result;
	}
}