<?php
/**
 * 
 */
use zesk\Directory;

/**
 *
 * @author kent
 *
 */
class IPBan_FS extends Options {

	private $fs_path = null;

	function __construct(array $options = null) {
		parent::__construct($options);
		$this->inherit_global_options();

		$this->fs_path = $this->option('path');
		if (empty($this->fs_path)) {
			throw new Exception_Configuration("IPBan_FS::path", "No path set for IPBan_FS::path");
		}
		Directory::depend($this->fs_path, 0755);
		$this->php_inc_create();
	}

	private function ip_path($ip) {
		if (!IPv4::valid($ip) && !IPv4::is_mask($ip)) {
			return null;
		}
		return path($this->fs_path, implode("/", explode(".", $ip)));
	}

	private function _ip_drop_allow($ip, $func) {
		if (IPv4::valid($ip)) {
			return $this->$func($ip);
		}
		if (!IPv4::is_mask($ip)) {
			return 0;
		}
		list($low_ip, $nbits) = IPv4::mask_to_integers($ip);
		list($low, $high) = IPv4::network($ip);
		if ($nbits > 24) {
			$n = 0;
			for($i = $low; $i <= $high; $i++) {
				$n += $this->$func(IPv4::from_integer($i));
			}
		} else {
			$npops = $nbits <= 16 ? 2 : 1;
			$delta = $nbits <= 16 ? 256 * 256 : 256;
			for($i = $low; $i <= $high; $i += $delta) {
				$ip = IPv4::from_integer($i);
				$ip = explode(".", $ip);
				for($n = 0; $n < $npops; $n++) {
					array_pop($ip);
				}
				$ip[] = "*";
				$ip = implode(".", $ip);
				//echo "Blocking $ip\n";
				if ($this->$func($ip)) {
					$n += $delta;
				}
			}
		}
		return $n;
	}

	private function _ips_drop_allow($ips, $func) {
		if (is_array($ips)) {
			$n = 0;
			foreach ($ips as $ip) {
				$n += $this->_ip_drop_allow($ip, $func);
			}
			return $n;
		}
		return $this->_ip_drop_allow($ips, $func);
	}

	function drop($ips) {
		return $this->_ips_drop_allow($ips, "drop_ip");
	}
	function allow($ips) {
		return $this->_ips_drop_allow($ips, "allow_ip");
	}

	function drop_ip($ips) {
		if (is_array($ips)) {
			$n = 0;
			foreach ($ips as $ip) {
				$n += $this->drop_ip($ip);
			}
			return $n;
		}
		$ff = $this->ip_path($ips);
		if (!$ff) {
			return 0;
		}
		if (file_exists($ff)) {
			return 1;
		}
		$dir = dirname($ff);
		try {
			Directory::depend($dir, 0755);
			file_put_contents($ff, time());
			chmod($ff, 0644);
			return 1;
		} catch (Exception_Directory_Create $e) {
			log::error("Unable to create directory {dir} to block IP {ips}", array(
				"dir" => $dir,
				"ips" => $ips
			));
		}
		return 0;
	}

	function allow_ip($ips) {
		if (is_array($ips)) {
			$n = 0;
			foreach ($ips as $ip) {
				$n += $this->allow_ip($ip);
			}
			return $n;
		}
		$ff = $this->ip_path($ips);
		if (!$ff) {
			return 0;
		}
		if (file_exists($ff)) {
			unlink($ff);
			return 1;
		}
		return 0;
	}

	function clean_empties() {
		$deleted = 0;
		foreach (Directory::ls($this->fs_path, null, true) as $class_a) {
			if (Directory::is_empty($class_a)) {
				Directory::delete($class_a);
				$deleted++;
			}
		}
		return $deleted;
	}

	private function php_inc_create() {
		file_put_contents(path($this->fs_path, "ipban.inc"), theme('ipban-php', array(
			"fs_path" => $this->fs_path
		)));
	}
}
