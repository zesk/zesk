<?php
/**
 * $URL: https://code.marketacumen.com/zesk/trunk/classes/Net/FTP/Client.php $
 * @author Kent M. Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2005, Market Acumen, Inc.
 * @package zesk
 * @subpackage model
 */

namespace zesk;

/**
 * 
 * @author kent
 *
 */
class Net_FTP_Client extends Net_Client implements Net_FileSystem {
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
	private $stat_cache = array();
	
	/**
	 * Connect to the remote host
	 * @see Net_Client::connect()
	 */
	public function connect() {
		if ($this->is_connected()) {
			throw new Exception_Semantics("Already connected.");
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
			if ($path{0} === '/') {
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
	public function is_connected() {
		return is_resource($this->ftp);
	}
	/**
	 * Disconnect if connected
	 * @return boolean true if disconnected, false if already disconnected
	 * @see Net_Client::connect()
	 */
	public function disconnect() {
		if ($this->is_connected()) {
			$this->stat_cache = array();
			ftp_close($this->ftp);
			$this->ftp = null;
			return true;
		}
		return false;
	}
	
	/**
	 * Get/set the passive mode for the FTP session
	 * @param boolean $set
	 * @return Net_FTP_Client boolean
	 */
	function passive($set = null) {
		if ($set !== null) {
			$this->passive = to_bool($set);
			return $this;
		}
		return $this->passive;
	}
	private function _passive() {
		if ($this->ftp_passive !== $this->passive) {
			ftp_pasv($this->ftp, $this->passive);
			$this->ftp_passive = $this->passive;
		}
	}
	function ls($path = null) {
		$this->_passive();
		$lines = ftp_rawlist($this->ftp, $path === null ? "" : $path);
		$entries = array();
		foreach ($lines as $line) {
			$entry = $this->parse_ls_line($line);
			if (is_array($entry)) {
				$entries[] = $entry;
			}
		}
		return $entries;
	}
	function mkdir($path) {
		if (!ftp_mkdir($this->ftp, $path)) {
			throw new Exception_Directory_Create($path);
		}
		return true;
	}
	function rmdir($path) {
		return ftp_rmdir($this->ftp, $path);
	}
	function unlink($path) {
		return ftp_delete($this->ftp, $path);
	}
	function download($remote_path, $local_path) {
		$this->_passive();
		return ftp_get($this->ftp, $local_path, $remote_path, FTP_BINARY);
	}
	function upload($local_path, $remote_path, $temporary = false) {
		$this->_passive();
		$result = ftp_put($this->ftp, $remote_path, $local_path, FTP_BINARY);
		if ($result) {
			unlink($local_path);
		}
		return $result;
	}
	function pwd() {
		return ftp_pwd($this->ftp);
	}
	function cd($path) {
		return ftp_chdir($this->ftp, $path);
	}
	function chmod($path, $mode = 0770) {
		return ftp_chmod($this->ftp, $mode, $path);
	}
	function stat($path) {
		$dir = dirname($path);
		$file = basename($path);
		$listing = avalue($this->stat_cache, $dir, null);
		if (!$listing) {
			$this->stat_cache[$dir] = $listing = $this->ls($dir);
		}
		return avalue($listing, $file, array(
			'type' => null, 
			'name' => $file
		));
	}
	function mtime($path, Timestamp $ts) {
		return false;
	}
	function has_feature($feature) {
		switch ($feature) {
			case self::feature_mtime:
				return false;
			default:
				return false;
		}
	}
}

