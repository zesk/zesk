<?php

/**
 * @package zesk
 * @subpackage cleaner
 * @author kent
 * @copyright &copy; 2017 Market Acumen, Inc.
 */
namespace zesk\Cleaner;

use zesk\File;
use zesk\Directory;
use zesk\TimeSpan;

/**
 *
 * @author kent
 *
 */
class Module extends \zesk\Module {
	public function initialize() {
		parent::initialize();
	}
	
	/**
	 * Support old class name
	 */
	public function hook_configured() {
		$this->application->configuration->deprecated("zesk\\Module_Cleaner", self::class);
	}
	
	/**
	 * Run every hour and clean things up
	 */
	public function hook_cron_hour() {
		$directories = $this->option_array("directories");
		foreach ($directories as $code => $settings) {
			$path = $extensions = $lifetime = null;
			extract($settings, EXTR_IF_EXISTS);
			if (!$path) {
				$this->application->logger->warning("{class}::directories::{code}::path is not set, skipping entry", array(
					"class" => $this->class,
					"code" => $code
				));
				continue;
			}
			$path = File::is_absolute($path) ? $path : $this->application->path($path);
			if (!$extensions) {
				$extensions = null;
			} else {
				$extensions = to_list($extensions);
			}
			$span = new TimeSpan($lifetime);
			if (!$span->valid()) {
				$this->application->logger->warning("{class}::directories::{code}::lifetime is not a valid time span value ({lifetime} is type {lifetime-type}), skipping entry", array(
					"class" => __CLASS__,
					"code" => $code,
					"lifetime" => $lifetime,
					"lifetime-type" => type($lifetime)
				));
				continue;
			}
			$this->clean_path($path, $extensions, $span->seconds());
		}
	}
	
	/**
	 * Remove old files in a path
	 *
	 * @param string $extension
	 */
	public function clean_path($path, $extensions = null, $lifetime_seconds = 604800) {
		$list_attributes = array(
			"rules_directory_walk" => true,
			"rules_directory" => false,
			"add_path" => true
		);
		if (is_array($extensions) && count($extensions) > 0) {
			$list_attributes["rules_file"] = array(
				"#\.(" . implode("|", $extensions) . ")$#" => true,
				false
			);
		} else {
			$list_attributes['rules_file'] = true;
		}
		
		$files = Directory::list_recursive($path, $list_attributes);
		$now = time();
		$modified_after = $now - $lifetime_seconds;
		$deleted = array();
		foreach ($files as $file) {
			if (!is_file($file)) {
				continue;
			}
			$filemtime = filemtime($file);
			if ($filemtime < $modified_after) {
				$this->application->logger->debug("Deleting old file {file} modified on {when}, more than {delta} seconds ago", array(
					"file" => $file,
					"when" => date("Y-m-d H:i:s"),
					"delta" => $now - $filemtime
				));
				@unlink($file);
				$deleted[] = $file;
			}
		}
		$this->application->logger->notice("Deleted {deleted} files from {path} (Expire after {expire_seconds} seconds)", array(
			"deleted" => count($deleted),
			"path" => $path,
			"expire_seconds" => $lifetime_seconds
		));
		return $deleted;
	}
}
