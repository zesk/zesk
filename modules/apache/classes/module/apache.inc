<?php
/**
 * $URL: https://code.marketacumen.com/zesk/trunk/modules/apache/classes/module/apache.inc $
 * @package zesk
 * @subpackage system
 * @author Kent M. Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2008, Market Acumen, Inc.
 */
class Module_Apache extends Module {
	/**
	 * Implement hook cron_minute
	 */
	public function hook_cron_minute() {
		if (zesk::getb(__CLASS__ . '::generate_htaccess')) {
			$this->generate_htaccess();
		}
		if (zesk::getb(__CLASS__ . '::generate_alias_include')) {
			$this->generate_alias_include();
		}
	}

	/**
	 * Retrieve hash
	 *
	 * @param string $file
	 * @return string
	 */
	private static function retrieve_hash($file) {
		if (!file_exists($file)) {
			return null;
		}
		$contents = file_get_contents($file);
		$matches = null;
		if (preg_match('/Hash: ([a-z0-9A-Z]{32})/', $contents, $matches)) {
			return $matches[1];
		}
		return null;
	}

	/**
	 * Generate .htaccess file for site
	 *
	 * @return boolean
	 */
	private function generate_htaccess() {
		$docroot = zesk::document_root();
		if (!is_dir($docroot)) {
			return false;
		}
		$mtime = filemtime($this->application->template->find_path('htaccess.tpl'));
		$htaccess_name = zesk::get(__CLASS__ . "::htaccess_name", ".htaccess");
		$index_file = zesk::getl(__CLASS__ . "::directory_index", "index.php", " ");
		$file = path($docroot, $htaccess_name);
		$file_mtime = is_file($file) ? filemtime($file) : null;
		if ($mtime < $file_mtime) {
			return false;
		}
		$contents = $this->application->theme('htaccess', array(
			'directory_index' => implode(" ", $index_file)
		));
		file_put_contents($file, $contents);
		return true;
	}

	/**
	 * Create alias include file
	 *
	 * @return boolean
	 */
	private function generate_alias_include() {
		$share_paths = zesk::share_path();
		$this_hash = md5(serialize($share_paths));
		$alias_include_dir = zesk::data_path("httpd");
		$alias_include = path($alias_include_dir, "aliases.conf");
		dir::depend($alias_include_dir);

		$file_hash = self::retrieve_hash($alias_include);
		if (strcasecmp($this_hash, $file_hash) === 0) {
			return false;
		}
		$contents = $this->application->theme('alias_include', array(
			'share_prefix' => rtrim(zesk::document_root_prefix(), '/') . '/share',
			'share_paths' => $share_paths,
			'hash' => $this_hash,
			'path' => $alias_include
		));
		file_put_contents($alias_include, $contents);
		return true;
	}

/**
 * Parse an Apache log file time/date format:
 *
 * 19/Jul/2007:12:43:32 -0700
 *
 * @param string $ts Apache log file time to parse
 * @return timestamp (in UTC) of the time
 */
	//function parse_apache_time($time_string)
	//{
	//	$format = "%d/%b/%Y:%H:%M:%S";
	//	$x = strptime($time_string, $format);
	//	$dt = ($x['tm_year'] + 1900) ."-".str::zero_pad($x['tm_mon']+1)."-".str::zero_pad($x['tm_mday']).' '.str::zero_pad($x['tm_hour']).':'.str::zero_pad($x['tm_min']).':'.str::zero_pad($x['tm_sec']);
	//	$ts = utc_parse_time($dt);
	//	$tz = trim($x['unparsed']);
	//	if (empty($tz)) return $ts;
	//	$tz = intval($tz);
	//	$ts = $ts - (($tz / 100) * 3600);
	//	return $ts;
	//}
}