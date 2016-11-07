<?php
/**
 * $URL: https://code.marketacumen.com/zesk/trunk/classes/net/file/client.inc $
 * @author Kent M. Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2005, Market Acumen, Inc.
 * @package zesk
 * @subpackage model
 */
use zesk\File;
use zesk\Timestamp;
use zesk\Directory;
use zesk\Exception_Semantics;
use zesk\Exception_File_Permission;

/**
 * 
 * @author kent
 *
 */
class Net_File_Client extends Net_Client implements Net_FileSystem {
	/**
	 * Are we "connected"? Simply for semantic integrity between clients.
	 * @var boolean
	 */
	private $connected = false;
	
	/**
	 * Connect to the remote host
	 * @see Net_Client::connect()
	 */
	public function connect() {
		if ($this->connected) {
			throw new Exception_Semantics("Already connected");
		}
		$this->connected = true;
		return true;
	}
	/**
	 * Are we connected?
	 * @return boolean
	 * @see Net_Client::connect()
	 */
	public function is_connected() {
		return $this->connected;
	}
	/**
	 * Disconnect if connected
	 * @return boolean true if disconnected, false if already disconnected
	 * @see Net_Client::connect()
	 */
	public function disconnect() {
		$old_connected = $this->connected;
		$this->connected = false;
		return $old_connected;
	}
	function ls($path = null) {
		if ($path === null) {
			$path = getcwd();
		}
		$files = Directory::ls($path);
		foreach ($files as $name) {
			if ($name === "." || $name === "..") {
				continue;
			}
			$full_path = path($path, $name);
			$stats = file::stat($full_path);
			$entry['mode'] = $stats['perms']['string'];
			$entry['type'] = $stats['filetype']['type'];
			$entry['owner'] = $stats['owner']['owner'];
			$entry['group'] = $stats['owner']['group'];
			$entry['size'] = $stats['size']['size'];
			$entry['mtime'] = Timestamp::factory()->unix_timestamp($stats['time']['mtime']);
			$entry['mtime_granularity'] = 'second';
			$entry['name'] = $name;
			$entries[] = $entry;
		}
		return $entries;
	}
	function mkdir($path) {
		return mkdir($path);
	}
	function rmdir($path) {
		return rmdir($path);
	}
	function unlink($path) {
		return unlink($path);
	}
	function download($remote_path, $local_path) {
		return copy($remote_path, $local_path);
	}
	function upload($local_path, $remote_path, $temporary = false) {
		if ($temporary) {
			$temp = null;
			if (is_file($remote_path)) {
				$temp = $remote_path . '.rename-' . zesk()->process->id();
				if (!rename($remote_path, $temp)) {
					throw new Exception_File_Permission($remote_path);
				}
			}
			$result = rename($local_path, $remote_path);
			if ($temp) {
				if ($result) {
					unlink($temp);
				} else {
					rename($temp, $remote_path);
				}
			}
			return $result;
		} else {
			return copy($local_path, $remote_path);
		}
	}
	function pwd() {
		return getcwd();
	}
	function cd($path) {
		return chdir($path);
	}
	function chmod($path, $mode = 0770) {
		return chmod($path, $mode);
	}
	function stat($path) {
		$entry = array();
		$entry['name'] = basename($path);
		try {
			$stats = file::stat($path);
		} catch (Exception_File_NotFound $e) {
			return $entry + array(
				'type' => null
			);
		}
		$entry['mode'] = $stats['perms']['string'];
		$entry['type'] = $stats['filetype']['type'];
		$entry['owner'] = $stats['owner']['owner'];
		$entry['group'] = $stats['owner']['group'];
		$entry['size'] = $stats['size']['size'];
		$entry['mtime'] = Timestamp::factory()->unix_timestamp($stats['time']['mtime']);
		$entry['mtime_granularity'] = 'second';
		return $entry;
	}
	function mtime($path, Timestamp $ts) {
		return touch($path, $ts->unix_timestamp());
	}
	function has_feature($feature) {
		switch ($feature) {
			case self::feature_mtime:
				return true;
			default :
				return false;
		}
	}
}

