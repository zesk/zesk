<?php
declare(strict_types=1);
/**
 * @author kent
 * @copyright Copyright &copy; 2023, Market Acumen, Inc.
 * @package zesk
 * @subpackage model
 */
namespace zesk\Net\File;

use zesk\Net\Client as NetClient;

use zesk\Net\FileSystem;

/**
 *
 * @author kent
 *
 */
class Client extends NetClient implements FileSystem
{
	/**
	 * Are we "connected"? Simply for semantic integrity between clients.
	 * @var boolean
	 */
	private $connected = false;

	/**
	 * Connect to the remote host
	 * @see Net_Client::connect()
	 */
	public function connect(): self
	{
		if ($this->connected) {
			throw new Semantics('Already connected');
		}
		$this->connected = true;
		return $this;
	}

	/**
	 * Are we connected?
	 * @return boolean
	 * @see Net_Client::connect()
	 */
	public function isConnected()
	{
		return $this->connected;
	}

	/**
	 * Disconnect if connected
	 * @return boolean true if disconnected, false if already disconnected
	 * @see Net_Client::connect()
	 */
	public function disconnect()
	{
		$old_connected = $this->connected;
		$this->connected = false;
		return $old_connected;
	}

	public function ls($path = null)
	{
		if ($path === null) {
			$path = getcwd();
		}
		$files = Directory::ls($path);
		foreach ($files as $name) {
			if ($name === '.' || $name === '..') {
				continue;
			}
			$full_path = path($path, $name);
			$stats = File::stat($full_path);
			$entry['mode'] = $stats['perms']['string'];
			$entry['type'] = $stats['filetype']['type'];
			$entry['owner'] = $stats['owner']['owner'];
			$entry['group'] = $stats['owner']['group'];
			$entry['size'] = $stats['size']['size'];
			$entry['mtime'] = Timestamp::factory()->setUnixTimestamp($stats['time']['mtime']);
			$entry['mtime_granularity'] = 'second';
			$entry['name'] = $name;
			$entries[] = $entry;
		}
		return $entries;
	}

	public function mkdir($path)
	{
		return mkdir($path);
	}

	public function rmdir($path)
	{
		return rmdir($path);
	}

	public function unlink($path)
	{
		return unlink($path);
	}

	public function download($remote_path, $local_path)
	{
		return copy($remote_path, $local_path);
	}

	public function upload($local_path, $remote_path, $temporary = false)
	{
		if ($temporary) {
			$temp = null;
			if (is_file($remote_path)) {
				$temp = $remote_path . '.rename-' . $this->application->process->id();
				if (!rename($remote_path, $temp)) {
					throw new FilePermission($remote_path);
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

	public function pwd()
	{
		return getcwd();
	}

	public function cd($path)
	{
		return chdir($path);
	}

	public function chmod($path, $mode = 0o770)
	{
		return chmod($path, $mode);
	}

	public function stat($path)
	{
		$entry = [];
		$entry['name'] = basename($path);

		try {
			$stats = File::stat($path);
		} catch (FileNotFound $e) {
			return $entry + [
				'type' => null,
			];
		}
		$entry['mode'] = $stats['perms']['string'];
		$entry['type'] = $stats['filetype']['type'];
		$entry['owner'] = $stats['owner']['owner'];
		$entry['group'] = $stats['owner']['group'];
		$entry['size'] = $stats['size']['size'];
		$entry['mtime'] = Timestamp::factory()->setUnixTimestamp($stats['time']['mtime']);
		$entry['mtime_granularity'] = 'second';
		return $entry;
	}

	public function mtime($path, Timestamp $ts)
	{
		return touch($path, $ts->unixTimestamp());
	}

	public function has_feature($feature)
	{
		switch ($feature) {
			case self::feature_mtime:
				return true;
			default:
				return false;
		}
	}
}
