<?php
declare(strict_types=1);
/**
 * @author kent
 * @copyright Copyright &copy; 2023, Market Acumen, Inc.
 * @package zesk
 * @subpackage model
 */
namespace zesk;

use zesk\Exception\DirectoryCreate;

/**
 *
 * @author kent
 *
 */
class Net_FTP_Client extends Net_Client implements Net_FileSystem
{
	/**
	 * Resource connection to ftp_connect
	 * @var resource
	 */
	protected $ftp = null;

	/**
	 * Passive mode on/off
	 * @var boolean
	 */
	protected $passive = true;

	/**
	 * Connection passive mode (null for unset)
	 * @var boolean
	 */
	protected $ftp_passive = null;

	/**
	 * Local stat cache for "stat" call
	 * @var array
	 */
	private $stat_cache = [];

	/**
	 * Connect to the remote host
	 * @see Net_Client::connect()
	 */
	public function connect(): void
	{
		if ($this->isConnected()) {
			throw new Semantics('Already connected.');
		}
		$host = null;
		$port = 21;
		$path = null;
		extract($this->url_parts, EXTR_IF_EXISTS);
		if (!is_numeric($port)) {
			$port = 21;
		}
		$this->ftp = ftp_connect($host, $port);
		if (!$this->ftp) {
			$ip = gethostbyname($this->Host);
			$this->ftp = ftp_connect($this->ftp);
		}
		// Special case to enable relative paths
		if ($path !== null) {
			if (str_starts_with($path, '/')) {
				$path = substr($path, 1);
			}
			$this->url_parts['path'] = $path;
			$this->cd($path);
		} else {
			$this->url_parts['path'] = $this->pwd();
		}
	}

	/**
	 * Are we connected?
	 * @return boolean
	 * @see Net_Client::connect()
	 */
	public function isConnected()
	{
		return is_resource($this->ftp);
	}

	/**
	 * Disconnect if connected
	 * @return boolean true if disconnected, false if already disconnected
	 * @see Net_Client::connect()
	 */
	public function disconnect()
	{
		if ($this->isConnected()) {
			$this->stat_cache = [];
			ftp_close($this->ftp);
			$this->ftp = null;
			return true;
		}
		return false;
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

	public function ls($path = null)
	{
		$this->_passive();
		$lines = ftp_rawlist($this->ftp, $path === null ? '' : $path);
		$entries = [];
		foreach ($lines as $line) {
			$entry = $this->parse_ls_line($line);
			if (is_array($entry)) {
				$entries[] = $entry;
			}
		}
		return $entries;
	}

	public function mkdir($path)
	{
		if (!ftp_mkdir($this->ftp, $path)) {
			throw new DirectoryCreate($path);
		}
		return true;
	}

	public function rmdir($path)
	{
		return ftp_rmdir($this->ftp, $path);
	}

	public function unlink($path)
	{
		return ftp_delete($this->ftp, $path);
	}

	public function download($remote_path, $local_path)
	{
		$this->_passive();
		return ftp_get($this->ftp, $local_path, $remote_path, FTP_BINARY);
	}

	public function upload($local_path, $remote_path, $temporary = false)
	{
		$this->_passive();
		$result = ftp_put($this->ftp, $remote_path, $local_path, FTP_BINARY);
		if ($result) {
			unlink($local_path);
		}
		return $result;
	}

	public function pwd()
	{
		return ftp_pwd($this->ftp);
	}

	public function cd($path)
	{
		return ftp_chdir($this->ftp, $path);
	}

	public function chmod($path, $mode = 0o770)
	{
		return ftp_chmod($this->ftp, $mode, $path);
	}

	public function stat($path)
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

	public function mtime($path, Timestamp $ts)
	{
		return false;
	}

	public function has_feature($feature)
	{
		return false;
	}
}
