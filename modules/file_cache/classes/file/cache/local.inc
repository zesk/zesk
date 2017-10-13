<?php
/**
 * 
 */
use zesk\Application;
use zesk\Directory;
use zesk\Timestamp;
use zesk\URL;
use zesk\Cache;
use zesk\Exception_File_Permission;
use zesk\Exception_NotFound;
use zesk\Net_HTTP_Client;
use zesk\File;
use zesk\Net_HTTP;

/**
 * 
 * @author kent
 *
 */
class File_Cache_Local implements Interface_File_Cache {
	/**
	 *
	 * @var Application
	 */
	public $application = null;
	/**
	 * Directory where cache files are stored
	 * @var string
	 */
	private $cache_dir = null;
	
	/**
	 * 
	 * @param Application $application
	 */
	public function __construct(Application $application) {
		$this->application = $application;
	}
	/**
	 * Initialize this object if not inited already
	 * @return string
	 */
	private function _init() {
		/* @var $zesk zesk\Kernel */
		if ($this->cache_dir !== null) {
			return $this->cache_dir;
		}
		$this->cache_dir = $this->application->configuration->path_get('File_Cache::path', $this->application->path('cache/file_cache'));
		Directory::depend($this->cache_dir, 0770);
	}
	
	/**
	 * Get or Set settings for a URL
	 * @param string $url URL to get/set settings for
	 * @param array $value NULL to get, array to set
	 * @throws Exception_File_Permission
	 * @return array of settings set/get
	 */
	private function settings($url, $value = null) {
		$url_hash = md5($url);
		$db_dir = path($this->cache_dir, 'db', substr($url_hash, 0, 1));
		if (!is_dir($db_dir) && $value === null) {
			return array();
		}
		Directory::depend($db_dir, 0770);
		$db_file = path($db_dir, $url_hash . ".settings");
		$mode = "r+";
		$is_new = false;
		if (!is_file($db_file)) {
			if ($value === null) {
				return array();
			}
			$is_new = true;
			$mode = "w+";
		}
		$lock = fopen($db_file, $is_new ? "w" : "r+");
		if (!$lock) {
			throw new Exception_File_Permission($db_file, "fopen with mode $mode");
		}
		if (!flock($lock, LOCK_EX)) {
			fclose($lock);
			throw new Exception_File_Permission("Can not open $db_file exclusively");
		}
		if ($is_new) {
			$settings = array();
		} else {
			fseek($lock, 0);
			$data = "";
			while (!feof($lock)) {
				$data .= fread($lock, 10240);
			}
			$settings = $data ? unserialize($data) : null;
			if (!is_array($settings)) {
				$settings = array();
			}
		}
		if ($value === null) {
			fclose($lock);
			return $settings;
		}
		fseek($lock, 0);
		ftruncate($lock, 0);
		fwrite($lock, serialize($value));
		fclose($lock);
		return $value;
	}
	
	/**
	 *
	 * @var double
	 */
	private $last_request = null;
	
	/**
	 * Download a remote URL
	 * @param string $url URL to download
	 * @return array of remote file name, temp path name
	 */
	private function _fetch_url($url, array $options = array()) {
		$request_interval_milliseconds = avalue($options, "request_interval_milliseconds");
		if ($request_interval_milliseconds > 0 && $this->last_request !== null) {
			$wait_minimum = $request_interval_milliseconds / 1000.0;
			$now = microtime(true);
			$delta = $now - $this->last_request;
			if ($delta < $wait_minimum) {
				$wait_for = $wait_minimum - $delta;
				$microseconds = intval($wait_for * 1000000);
				$this->application->logger->notice("Sleeping for $wait_for seconds ...");
				usleep($microseconds);
			}
		}
		$client = new Net_HTTP_Client($this->application, $url);
		$timeout = avalue($options, "timeout");
		if ($timeout) {
			$client->timeout($timeout);
		}
		$temp_file_name = File::temporary();
		$client->follow_location(true);
		$client->user_agent(avalue($options, "user_agent", 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.7; rv:12.0) Gecko/20100101 Firefox/12.0'));
		$client->destination($temp_file_name);
		$this->application->logger->notice("Downloading $url");
		$client->go();
		$this->last_request = microtime(true);
		return array(
			$client->filename(),
			$temp_file_name,
			$client->response_code()
		);
	}
	
	/**
	 * Retrieve settings for URL
	 * @param string $url URL to retrieve settings
	 * @return array
	 *
	 * @see Interface_File_Cache::_info()
	 */
	public function _info($url) {
		$url = URL::normalize($url);
		$this->_init();
		$url_hash = md5($url);
		return $this->settings($url);
	}
	public function _iterator() {
		$this->_init();
		if (!is_dir($this->cache_dir)) {
			return array();
		}
		return new File_Cache_Local_Iterator($this->cache_dir);
	}
	private function _binary_file_path($hash, $create = true) {
		$dir = path($this->cache_dir, "bits", substr($hash, 0, 1));
		if ($create) {
			Directory::depend($dir, 0770);
		}
		return path($dir, $hash);
	}
	public function _url($url, array $options = array()) {
		$url = URL::normalize($url);
		$this->_init();
		
		$binary_dir = path($this->cache_dir, "bits");
		Directory::depend($binary_dir, 0770);
		
		$settings = self::settings($url);
		$created = $checked = $response_code = $hash = $name = $size = $ttl = null;
		extract($settings, EXTR_IF_EXISTS);
		
		$now = time();
		
		$ttl = intval(avalue($options, "ttl", $ttl));
		if ($ttl <= 0) {
			$ttl = $this->application->configuration->path_get('File_Cache::ttl', Timestamp::units_translation_table("week"));
		}
		$expire = $now + $ttl;
		
		$need_check = $created === null || $name === null || $hash === null;
		if (!$need_check && $hash !== null) {
			$binary_file = $this->_binary_file_path($hash, false);
			$need_check = !file_exists($binary_file);
			if (!$need_check) {
				$need_check = filesize($binary_file) !== $size;
			}
		}
		if (!$need_check && $now > $checked + $ttl) {
			$this->application->logger->debug("File_Cache_Local::_url({url}) TTL expired ({now} > {expire})", compact("now", "expire", "url"));
			$need_check = true;
		}
		if ($need_check) {
			list($name, $temp_file, $new_response_code) = $this->_fetch_url($url, $options);
			$new_hash = md5_file($temp_file);
			$settings['checked'] = time();
			if ($hash !== $new_hash || $response_code !== $new_response_code) {
				if (!empty($hash)) {
					$binary_file = $this->_binary_file_path($hash, false);
					if (file_exists($binary_file)) {
						unlink($binary_file);
					}
				}
				$binary_file = $this->_binary_file_path($new_hash, true);
				$settings['ttl'] = $ttl;
				$settings['hash'] = $new_hash;
				$settings['size'] = filesize($temp_file);
				$settings['status'] = intval($new_response_code) === Net_HTTP::Status_OK ? true : false;
				$settings['created'] = $now;
				$settings['response_code'] = $new_response_code;
				$settings['name'] = $name;
				$settings['url'] = $url;
				$settings['version'] = 1;
				rename($temp_file, $binary_file);
			} else {
				unlink($temp_file);
			}
			self::settings($url, $settings);
		}
		$binary_file = $this->_binary_file_path($settings['hash']);
		if (!$settings['status']) {
			throw new Exception_NotFound("{url} not found {response_code}", $settings);
		}
		return array(
			$binary_file,
			$settings['name']
		);
	}
}
