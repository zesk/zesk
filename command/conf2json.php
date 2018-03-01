<?php
namespace zesk;

use \SplFileInfo;

/**
 * Convert a .conf file to a .json configuration file
 *
 * @category Tools
 * @author kent
 *
 */
class Command_CONF2JSON extends Command_Iterator_File {
	protected $extensions = array(
		"conf"
	);
	function initialize() {
		$this->option_types += array(
			"dry-run" => "boolean",
			"noclobber" => "boolean"
		);
		$this->option_help += array(
			"dry-run" => "Don't modify the file system",
			"noclobber" => "Do not overwrite existing files"
		);
		parent::initialize();
	}
	protected function start() {
	}
	protected function process_file(SplFileInfo $file) {
		$source_name = $file->getPathname();
		$target_name = File::extension_change($source_name, "json");
		
		$result = array();
		$adapter = new Adapter_Settings_Array($result);
		Configuration_Parser::factory("conf", file_get_contents($source_name), $adapter)->process();
		
		$target_exists = file_exists($target_name);
		$n = count($result);
		if ($this->dry_run) {
			if ($n === 0) {
				$message = "No entries found in {source_name} for {target_name}";
			} else if ($this->noclobber && $target_exists) {
				$message = "Will not overwrite {target_name}";
			} else {
				$message = "Would write {target_name} with {n} {entries}";
			}
			$this->log($message, array(
				"source_name" => $source_name,
				"target_name" => $target_name,
				"n" => $n,
				"entries" => $this->application->locale->plural("entry", $n)
			));
			return;
		}
		if (count($result) > 0) {
			file_put_contents($target_name, JSON::encode_pretty($result));
		}
	}
	protected function finish() {
	}
}
