<?php
/**
 * 
 */
use zesk\Directory;
use zesk\Module;
use zesk\File;

/**
 * 
 * @author kent
 *
 */
class Module_Cleaner extends zesk\Module {
	public function hook_cron_hour() {
		global $zesk;
		/* @var $zesk \zesk\Kernel */
		
		$directories = $this->option_array("directories");
		foreach ($directories as $code => $settings) {
			$path = $extensions = $lifetime = null;
			extract($settings, EXTR_IF_EXISTS);
			if (!$path) {
				$zesk->logger->warning("{class}::directories::{code}::path is not set, skipping entry", array(
					"class" => $this->class,
					"code" => $code
				));
				continue;
			}
			$path = File::is_absolute($path) ? $path : $this->application->application_root($path);
			if (!$extensions) {
				$extensions = null;
			} else {
				$extensions = to_list($extensions);
			}
			if (is_numeric($lifetime) || $lifetime < 0) {
				$zesk->logger->warning("{class}::directories::{code}::lifetime is not an integer or negative ({lifetime} is type {lifetime-type}), skipping entry", array(
					"class" => $this->class,
					"code" => $code,
					"lifetime" => $lifetime,
					"lifetime-type" => type($lifetime)
				));
				continue;
			}
			$this->clean_path($path, $extensions, $lifetime);
		}
	}
	
	/**
	 * Remove old files in a path
	 * 
	 * @param string $extension
	 */
	public function clean_path($path, $extensions = null, $lifetime_seconds = 604800) {
		global $zesk;
		
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
		
		/* @var $zesk Kernel */
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
				$zesk->logger->debug("Deleting old file {file} modified on {when}, more than {delta} seconds ago", array(
					"file" => $file,
					"when" => date("Y-m-d H:i:s"),
					"delta" => $now - $filemtime
				));
				@unlink($file);
				$deleted[] = $file;
			}
		}
		$zesk->logger->notice("Deleted {deleted} files from {path} (Expire after {expire_seconds} seconds)", array(
			"deleted" => count($deleted),
			"path" => $path,
			"expire_seconds" => $lifetime_seconds
		));
		return $deleted;
	}
}
