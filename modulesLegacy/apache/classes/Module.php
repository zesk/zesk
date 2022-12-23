<?php declare(strict_types=1);
/**
 *
 * @package zesk
 * @subpackage system
 * @author Kent M. Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2022, Market Acumen, Inc.
 */
namespace zesk\Apache;

use zesk\Exception_Directory_NotFound;
use zesk\Exception_File_Permission;
use zesk\Exception_Semantics;

/**
 * Apache module integrates with Apache server and your web application, specifically - Generation
 * of a .htaccess to allow for pretty URLs in the $application->documentRoot() - Generation of an
 * Apache Include to set up aliases for the share directories within the system for faster serving
 * (avoids Controller_Share)
 *
 * @author kent
 */
class Module extends \zesk\Module {
	/**
	 * Implement hook cron_minute
	 */
	public function hook_cron_minute(): void {
		if ($this->optionBool('generate_htaccess')) {
			$this->generate_htaccess();
		}
	}

	/**
	 * Generate '.htaccess' file for site
	 *
	 * Try
	 *
	 * zesk eval '$app->apache_module()->generate_htaccess()'
	 *
	 * @return boolean True if file was written, false if cached version exists.
	 * @throws Exception_Directory_NotFound
	 * @throws Exception_File_Permission
	 * @throws Exception_Semantics
	 */
	public function generate_htaccess(): bool {
		$document_root = $this->application->documentRoot();
		if (!is_dir($document_root)) {
			throw new Exception_Directory_NotFound($document_root, 'Document root missing');
		}
		$mtime = filemtime($this->application->template->findPath('htaccess.tpl'));
		$htaccess_name = $this->option('htaccess_name', '.htaccess');
		$index_file = $this->optionIterable('directory_index', 'index.php', ' ');
		$file = path($document_root, $htaccess_name);
		$file_mtime = is_file($file) ? filemtime($file) : null;
		if ($mtime < $file_mtime) {
			return false;
		}
		$contents = $this->application->theme('htaccess', [
			'directory_index' => implode(' ', $index_file),
		]);
		return File::put($file, $contents);
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
	// ."-".StringTools::zero_pad($x['tm_mon']+1)."-".StringTools::zero_pad($x['tm_mday']).'
	// '.StringTools::zero_pad($x['tm_hour']).':'.StringTools::zero_pad($x['tm_min']).':'.StringTools::zero_pad($x['tm_sec']);
	// $ts = utc_parse_time($dt);
	// $tz = trim($x['unparsed']);
	// if (empty($tz)) return $ts;
	// $tz = intval($tz);
	// $ts = $ts - (($tz / 100) * 3600);
	// return $ts;
	// }
}
