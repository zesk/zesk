<?php
class File_Cache {

	/**
	 * Retrieve singleton File_Cache
	 * @return File_Cache
	 */
	public static function instance() {
		global $zesk;
		/* @var $zesk zesk\Kernel */
		static $singleton = null;
		if ($singleton instanceof Interface_File_Cache) {
			return $singleton;
		}
		$singleton = $zesk->objects->factory($zesk->configuration->path_get("File_Cache::class", "File_Cache_Local"));
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
	public static function url($url, array $options = array()) {
		return self::instance()->_url($url, $options);
	}
	public static function info($url) {
		return self::instance()->_info($url);
	}
	public static function iterator() {
		return self::instance()->_iterator();
	}
}
