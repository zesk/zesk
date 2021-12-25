<?php declare(strict_types=1);
/**
 *
 */
namespace zesk;

/**
 *
 * @author kent
 *
 */
// KMD No XMLRPC

// class Server_Configuration_Client extends Server_Configuration {
// 	private $server_url = null;

// 	/**
// 	 * To communicate with remote system
// 	 *
// 	 * @var XML_RPC_Client
// 	 */
// 	private $client = null;
// 	function __construct(Server_Platform $platform, $options = null) {
// 		parent::__construct($platform, $options);
// 		$this->server_url = $this->option("server_url");
// 		if (!URL::valid($this->server_url)) {
// 			throw new Exception_Syntax("Server_Configuration_Client server_url is not a valid url: $this->server_url");
// 		}
// 		$this->client = new \xmlrpc\Client($this->server_url);
// 	}
// 	function feature_list() {
// 		return $this->client->feature_list();
// 	}
// 	function remote_package($url) {
// 		/*
// 		 * Set up cache directory
// 		 */
// 		$domain = URL::host($url, 'localhost');
// 		$cache_path = path($this->path("CACHE_PATH"), "remote", $domain, md5($url));
// 		$this->platform->require_directory($cache_path);
// 		$file = basename($url);
// 		$full_path = path($cache_path, $file);

// 		/*
// 		 * Did this expire?
// 		 */
// 		// 1 day default
// 		$expire = intval(File::contents($cache_path, "$file.expire", filemtime($full_path) + 86400));
// 		if (time() < $expire) {
// 			return $full_path;
// 		}

// 		/*
// 		 * Ask the server what the alternative URL is (saves bandwidth)
// 		 */
// 		$result = $this->client->remote_package($url);

// 		/*
// 		 * Result is a structure with a url and an expire time
// 		 */
// 		$alternate_url = $result['url'];
// 		$expire = $result['expire'];
// 		$md5 = avalue($result, 'md5');

// 		/*
// 		 * Now, download to the local path from the remote one
// 		 */
// 		$w = fopen($full_path, "wb");
// 		if (!$w) {
// 			throw new Server_Exception_Permission("Can't open $full_path for writing");
// 		}
// 		$r = fopen($alternate_url, "rb");
// 		if (!$w) {
// 			fclose($w);
// 			throw new Server_Exception_Permission("Can't open $alternate_url for reading");
// 		}
// 		while (!feof($r)) {
// 			$read = fread($r, 10240);
// 			if ($read === false) {
// 				break;
// 			}
// 			$nremain = strlen($read);
// 			while ($nremain > 0) {
// 				$written = fwrite($w, $read);
// 				if ($written === false) {
// 					throw new Exception_FileSystem($full_path, "Can not write $read bytes");
// 				}
// 				$nremain -= $written;
// 				if ($nremain > 0) {
// 					$read = substr($read, $written);
// 				}
// 			}
// 		}
// 		fclose($r);
// 		fclose($w);
// 		if ($md5) {
// 			$md5_file = md5_file($full_path);
// 			if ($md5 !== $md5_file) {
// 				throw new Exception_File_Create($full_path, "Download doesn't match checksum: (local) $md5_file !== (remote) $md5");
// 			}
// 		}
// 		/*
// 		 * Expire is now plus expiration seconds, stored in a file next to it
// 		 */
// 		file_put_contents($full_path . ".expire", time() + to_integer($expire, 86400));

// 		return $full_path;
// 	}
// 	public function configuration_files($type, $files, $dest, array $options = array()) {
// 		throw new Exception_Unimplemented(__FUNCTION__);
// 	}
// 	function configure_feature(Server_Feature $feature) {
// 		throw new Exception_Unimplemented(__FUNCTION__);
// 	}
// }
