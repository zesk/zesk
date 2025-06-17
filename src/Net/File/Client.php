<?php
declare(strict_types=1);
/**
 * @author     kent
 * @copyright  Copyright &copy; 2025, Market Acumen, Inc.
 * @package    zesk
 * @subpackage model
 */

namespace zesk\Net\File;

use zesk\Directory;
use zesk\Exception\DirectoryNotFound;
use zesk\Exception\FileNotFound;
use zesk\Exception\FilePermission;
use zesk\Exception\SemanticsException;
use zesk\File;
use zesk\Net\Client as NetClient;
use zesk\Net\FileSystem;
use zesk\Timestamp;

/**
 *
 * @author kent
 *
 */
class Client extends NetClient implements FileSystem {
	
	/**
	 * Are we "connected"? Simply for semantic integrity between clients.
	 *
	 * @var boolean
	 */
	private bool $connected = false;
	
	/**
	 * Connect to the remote host
	 *
	 * @see Net_Client::connect()
	 */
	public function connect(): self
	{
		if ($this->connected) {
			throw new SemanticsException('Already connected');
		}
		$this->connected = true;
		return $this;
	}
	
	/**
	 * Are we connected?
	 *
	 * @return boolean
	 */
	public function isConnected(): bool
	{
		return $this->connected;
	}
	
	/**
	 * Disconnect if connected
	 *
	 */
	public function disconnect(): void
	{
		$this->connected = false;
	}
	
	public function ls(string $path): array
	{
		if ($path === null) {
			$path = getcwd();
		}
		$files = Directory::ls($path);
		foreach ($files as $name) {
			if ($name === '.' || $name === '..') {
				continue;
			}
			$full_path = Directory::path($path, $name);
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
	
	public function mkdir(string $path): void
	{
		Directory::depend($path);
	}
	
	public function rmdir(string $path): void
	{
		Directory::delete($path);
	}
	
	public function unlink(string $path): void
	{
		File::unlink($path);
	}
	
	public function download(string $remotePath, string $localPath): void
	{
		File::copy($remotePath, $localPath);
	}
	
	public function upload(string $localPath, string $remotePath, bool $temporary = false): void
	{
		if ($temporary) {
			$temp = null;
			if (is_file($remotePath)) {
				$temp = $remotePath . '.rename-' . $this->application->process->id();
				if (!rename($remotePath, $temp)) {
					throw new FilePermission($remotePath);
				}
			}
			$result = rename($localPath, $remotePath);
			if ($temp) {
				if ($result) {
					unlink($temp);
				} else {
					rename($temp, $remotePath);
				}
			}
			if (!$result) {
				throw new FilePermission($remotePath);
			}
		} else {
			File::copy($localPath, $remotePath);
		}
	}
	
	public function pwd(): string
	{
		return getcwd();
	}
	
	public function cd($path): void
	{
		if (!chdir($path)) {
			throw new DirectoryNotFound($path);
		}
	}
	
	public function chmod(string $path, int $mode = 0o770): void
	{
		if (!chmod($path, $mode)) {
			throw new FilePermission($path);
		}
	}
	
	/**
	 * @param string $path
	 * @return array|null[]
	 */
	public function stat(string $path): array
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
	
	public function setModificationTime(string $path, Timestamp $mtime): void
	{
		touch($path, $mtime->unixTimestamp());
	}
	
	public function hasFeature(string $feature): bool
	{
		switch ($feature) {
			case self::FEATURE_MODIFICATION_TIME:
				return true;
			default:
				return false;
		}
	}
	
	/**
	 * @return string
	 */
	public function connectGreeting(): string
	{
		// TODO: Implement connectGreeting() method.
		return php_uname('n');
	}
	
	/**
	 * @param string $path
	 * @return Timestamp
	 */
	public function modificationTime(string $path): Timestamp
	{
		return File::modified($path);
	}
}
