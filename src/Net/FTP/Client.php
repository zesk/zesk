<?php
declare(strict_types=1);
/**
 * @author kent
 * @copyright Copyright &copy; 2025, Market Acumen, Inc.
 * @package zesk
 * @subpackage model
 */

namespace zesk\Net\FTP;

use FTP\Connection;
use zesk\Exception\DirectoryCreate;
use zesk\Exception\DirectoryNotFound;
use zesk\Exception\FileNotFound;
use zesk\Exception\FilePermission;
use zesk\Exception\FileSystemException;
use zesk\Exception\NotFoundException;
use zesk\Exception\ProtocolException;
use zesk\Exception\UnimplementedException;
use zesk\Net\Client as ClientBase;
use zesk\Net\FileSystem;
use zesk\Timestamp;

/**
 *
 * @author kent
 *
 */
class Client extends ClientBase implements FileSystem
{
	/**
	 * Resource connection to ftp_connect
	 * @var Connection|null
	 */
	protected Connection|null $ftp = null;

	/**
	 * Passive mode on/off
	 * @var boolean
	 */
	protected bool $passive = true;

	/**
	 * Connection passive mode (null for unset)
	 * @var boolean
	 */
	protected ?bool $ftp_passive = null;

	/**
	 * Local stat cache for "stat" call
	 * @var array
	 */
	private array $stat_cache = [];

	/**
	 * Connect to the remote host
	 * @throws ProtocolException
	 * @throws DirectoryNotFound
	 * @see Net_Client::connect()
	 */
	public function connect(): self
	{
		if ($this->isConnected()) {
			throw new ProtocolException('Already connected.');
		}
		$host = $this->urlParts['host'];
		$port = $this->urlParts['port'] ?? 21;
		$path = $this->urlParts['path'] ?? '';
		$this->ftp = ftp_connect($host, $port);
		if (!$this->ftp) {
			$ip = gethostbyname($host);
			$this->ftp = ftp_connect($ip, $port);
		}
		// Special case to enable relative paths
		if ($path !== '') {
			if (str_starts_with($path, '/')) {
				$path = substr($path, 1);
			}
			$this->urlParts['path'] = $path;
			$this->cd($path);
		} else {
			$this->urlParts['path'] = $this->pwd();
		}
		return $this;
	}

	/**
	 * Are we connected?
	 * @return boolean
	 * @see Net_Client::connect()
	 */
	public function isConnected(): bool
	{
		return $this->ftp !== null;
	}

	/**
	 * Disconnect if connected
	 * @return void true if disconnected, false if already disconnected
	 * @see Net_Client::connect()
	 */
	public function disconnect(): void
	{
		if ($this->isConnected()) {
			$this->stat_cache = [];
			ftp_close($this->ftp);
			$this->ftp = null;
		}
	}

	/**
	 * Get the passive mode for the FTP session
	 * @return bool
	 */
	public function passive(): bool
	{
		return $this->passive;
	}

	/**
	 * Set the passive mode for the FTP session
	 * @param boolean $set
	 * @return self boolean
	 */
	public function setPassive(bool $set): self
	{
		$this->passive = $set;
		return $this;
	}

	private function _passive(): void
	{
		if ($this->ftp_passive !== $this->passive) {
			ftp_pasv($this->ftp, $this->passive);
			$this->ftp_passive = $this->passive;
		}
	}

	private function parseFTPListingLine(string $string): array
	{
		return explode(' ', $string, 5);
	}

	public function ls(string $path): array
	{
		$this->_passive();
		$lines = ftp_rawlist($this->ftp, $path);
		$entries = [];
		foreach ($lines as $line) {
			$entries[] = $this->parseFTPListingLine($line);
		}
		return $entries;
	}

	/**
	 * @param string $path
	 * @return void
	 * @throws DirectoryCreate
	 */
	public function mkdir(string $path): void
	{
		if (!ftp_mkdir($this->ftp, $path)) {
			throw new DirectoryCreate($path);
		}
	}

	/**
	 * @throws FilePermission
	 */
	public function rmdir(string $path): void
	{
		if (!ftp_rmdir($this->ftp, $path)) {
			throw new FilePermission($path, 'Unable to remove FTP directory {path}', ['path' => $path]);
		}
	}

	public function unlink(string $path): void
	{
		ftp_delete($this->ftp, $path);
	}

	/**
	 * @throws NotFoundException
	 */
	public function download(string $remotePath, string $localPath): void
	{
		$this->_passive();
		if (!ftp_get($this->ftp, $localPath, $remotePath)) {
			throw new NotFoundException($localPath);
		}
	}

	/**
	 * @throws FilePermission
	 * @throws FileSystemException
	 */
	public function upload(string $localPath, string $remotePath, bool $temporary = false): void
	{
		$this->_passive();
		$result = ftp_put($this->ftp, $remotePath, $localPath);
		if (!$result) {
			throw new FileSystemException($localPath, 'Upload {localPath} to {remotePath} failed', [
				'localPath'  => $localPath,
				'remotePath' => $remotePath,
			]);
		}
		if ($temporary) {
			if (!unlink($localPath)) {
				throw new FilePermission($localPath, 'Deleting {localPath} failed', ['localPath' => $localPath]);
			}
		}
	}

	public function pwd(): string
	{
		return ftp_pwd($this->ftp);
	}

	/**
	 * @throws DirectoryNotFound
	 */
	public function cd(string $path): void
	{
		if (!ftp_chdir($this->ftp, $path)) {
			throw new DirectoryNotFound($path);
		}
	}

	/**
	 * @throws FilePermission
	 */
	public function chmod($path, int $mode = 0o770): void
	{
		if (!ftp_chmod($this->ftp, $mode, $path)) {
			throw new FilePermission($path, 'Unable to set {mode} mode', ['mode' => $mode]);
		}
	}

	public function stat(string $path): array
	{
		$dir = dirname($path);
		$file = basename($path);
		$listing = $this->stat_cache[$dir] ?? null;
		if (!$listing) {
			$this->stat_cache[$dir] = $listing = $this->ls($dir);
		}
		return $listing[$file] ?? [
			'type' => null,
			'name' => $file,
		];
	}

	public function hasFeature($feature): bool
	{
		return match ($feature) {
			self::FEATURE_MODIFICATION_TIME => true,
			default => false,
		};
	}

	/**
	 * @return string
	 */
	public function connectGreeting(): string
	{
		return '';
	}

	/**
	 * @param string $path
	 * @return Timestamp
	 * @throws FileNotFound
	 */
	public function modificationTime(string $path): Timestamp
	{
		$result = ftp_mdtm($this->ftp, $path);
		if ($result > 0) {
			return Timestamp::factory($result);
		}

		throw new FileNotFound($path);
	}

	/**
	 * @param string $path
	 * @param Timestamp $mtime
	 * @return void
	 * @throws UnimplementedException
	 */
	public function setModificationTime(string $path, Timestamp $mtime): void
	{
		throw new UnimplementedException(__METHOD__);
	}
}
