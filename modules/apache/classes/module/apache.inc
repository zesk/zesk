<?php
/**
 * $URL: https://code.marketacumen.com/zesk/trunk/modules/apache/classes/module/apache.inc $
 * 
 * @package zesk
 * @subpackage system
 * @author Kent M. Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2008, Market Acumen, Inc.
 */
use zesk\Directory;

/**
 * Apache module integrates with Apache server and your web application, specifically - Generation
 * of a .htaccess to allow for pretty URLs in the $application->document_root() - Generation of an
 * Apache Include to set up aliases for the share directories within the system for faster serving
 * (avoids Controller_Share)
 * 
 * @author kent
 */
class Module_Apache extends zesk\Module {
	/**
	 * Implement hook cron_minute
	 */
	public function hook_cron_minute() {
		if ($this->option_bool('generate_htaccess')) {
			$this->generate_htaccess();
		}
		if ($this->option_bool('generate_alias_include')) {
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
	 * Generate '.htaccess' file for site
	 * 
	 * @return boolean
	 */
	private function generate_htaccess() {
		$docroot = $this->application->document_root();
		if (!is_dir($docroot)) {
			return false;
		}
		$mtime = filemtime($this->application->template->find_path('htaccess.tpl'));
		$htaccess_name = $this->option("htaccess_name", ".htaccess");
		$index_file = $this->option_list("directory_index", "index.php", " ");
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
		$share_paths = $this->application->share_path();
		$this_hash = md5(serialize($share_paths));
		$alias_include_dir = $this->application->data_path("httpd");
		$alias_include = path($alias_include_dir, "aliases.conf");
		Directory::depend($alias_include_dir);
		
		$file_hash = self::retrieve_hash($alias_include);
		if (strcasecmp($this_hash, $file_hash) === 0) {
			return false;
		}
		$contents = $this->application->theme('alias-include', array(
			'share_prefix' => rtrim($this->application->document_root_prefix(), '/') . '/share',
			'share_paths' => $share_paths,
			'hash' => $this_hash,
			'path' => $alias_include
		));
		file_put_contents($alias_include, $contents);
		return true;
	}

/**
 * Parse an Apache log file time/date format: 19/Jul/2007:12:43:32 -0700
 * 
 * @param string $ts
 *        	Apache log file time to parse
 * @return timestamp (in UTC) of the time
 */
	// function parse_apache_time($time_string)
	// {
	// $format = "%d/%b/%Y:%H:%M:%S";
	// $x = strptime($time_string, $format);
	// $dt = ($x['tm_year'] + 1900)
	// ."-".str::zero_pad($x['tm_mon']+1)."-".str::zero_pad($x['tm_mday']).'
	// '.str::zero_pad($x['tm_hour']).':'.str::zero_pad($x['tm_min']).':'.str::zero_pad($x['tm_sec']);
	// $ts = utc_parse_time($dt);
	// $tz = trim($x['unparsed']);
	// if (empty($tz)) return $ts;
	// $tz = intval($tz);
	// $ts = $ts - (($tz / 100) * 3600);
	// return $ts;
	// }
}

