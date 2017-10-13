<?php
/**
 * 
 */
use zesk\Application;

/**
 * 
 * @author kent
 *
 */
class File_Cache {
	
	/**
	 * Retrieve singleton File_Cache
	 * @return File_Cache
	 */
	public static function instance(Application $application) {
		static $singleton = null;
		if ($singleton instanceof Interface_File_Cache) {
			return $singleton;
		}
		$singleton = $application->factory($application->configuration->path_get(__CLASS__ . "::class", "File_Cache_Local"), $application);
		return $singleton;
	}
	/**
	 * Returns an array of [ 'path/to/file', 'filename' ]
	 *
	 * @param unknown $url
	 * @param array $options
	 * @return array
	 * @throws Exception_NotFound
	 */
	public static function url(Application $application, $url, array $options = array()) {
		return self::instance($application)->_url($url, $options);
	}
	public static function info(Application $application, $url) {
		return self::instance($application)->_info($url);
	}
	public static function iterator(Application $application) {
		return self::instance($application)->_iterator();
	}
}
