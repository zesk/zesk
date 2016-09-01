<?php
/**
 * $URL: https://code.marketacumen.com/zesk/trunk/classes/cache/file.inc $
* @package zesk
* @subpackage default
* @author Kent Davidson <kent@marketacumen.com>
* @copyright Copyright &copy; 2006, Market Acumen, Inc.
*/

use zesk\file as file;

/**
 * Cache class is used to store files which need to remain persistent across web page requests
* To use, do:
* <code>
* $cache = Cache::register("permissions");
* $cache->A = "goo";
* $cache->B = "dee";
* </code>
* The data will persist across connections. Please note that this is not a reentrant cache, meaning your data is not
* guaranteed to be saved. If two connections happen simultaneously which modify the same cache value, a race-condition
* exists, and one set of data will be lost.
* Best usage is for data which does not change often, and is tied to a particular user, for example.
* @package zesk
* @subpackage system
*/
class Cache_File extends Cache {

	/**
	 *
	 * @var string
	 */
	static $caches_path = null;

	/**
	 * Check at flush time whether the "disabled" file exists.
	 * We check once on startup, and once at exit
	 *
	 * @var Boolean
	 */

	protected static function static_preregister() {
		if (count(self::$caches) > 0 && ($dir = self::cache_directory()) !== self::$caches_path) {
			self::$caches = array();
			self::$caches_path = $dir;
		}
	}
	protected static function static_initialize() {
		if (!self::$disabled) {
			self::$disabled = self::_disabled_file_exists();
		}
		self::$caches_path = self::cache_directory();
	}

	protected static function static_exists($name) {
		if (self::$disabled) {
			return false;
		}
		$path = self::_cache_file_path($name, false);
		return is_file($path);
	}

	protected static function static_exit() {
		if (!self::$disabled) {
			self::$disabled = self::_disabled_file_exists();
		}
	}

	public function exists() {
		if (self::$disabled) {
			return false;
		}
		return file_exists($this->cache_file_path());
	}

	private static function cache_directory() {
		return zesk::cache_path('data');
	}

	private static function _cache_file_path($name, $create = true) {
		$file_path = self::cache_directory();

		$file_path = path($file_path, implode("/", file::name_clean(explode("/", $name))) . ".cache");
		$dir_path = dirname($file_path);
		if ($create) {
			dir::depend($dir_path, 0770);
		}
		return $file_path;
	}

	/**
	 * The complete file name of the cache file.
	 * To override the cache directory on a site-wide
	 * basis, set global "Cache::directory" to a absolute path
	 * Cache files always end with ".cache"
	 * @return string A file name
	 */
	public function cache_file_path($create = false) {
		return self::_cache_file_path($this->_name, $create);
	}

	public function expire_after($n_seconds) {
		if ($this->exists()) {
			$mtime = filemtime($this->cache_file_path());
			if ($mtime + $n_seconds < time()) {
				$this->delete();
				$this->initialize();
				return $this;
			}
		}
		if ($this->_created + $n_seconds < time()) {
			$this->delete();
			$this->initialize();
		}
		$this->_internal['expire_after'] = array(
			$n_seconds
		);
		return $this;
	}

	protected function fetch() {
		return file::atomic($this->cache_file_path());
	}
	protected function store($data) {
		zesk::profiler();
		return file::atomic($this->cache_file_path(true), $data);
	}

	/**
	 * Delete the cache file
	 */
	public function delete() {
		if ($this->exists()) {
			@unlink($this->cache_file_path());
			clearstatcache(null, $this->cache_file_path());
		}
	}
	private static function _disabled_file_exists() {
		return file_exists(path(self::$caches_path, 'disabled'));
	}
}

