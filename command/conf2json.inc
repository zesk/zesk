<?php
use zesk\File;
class Command_CONF2JSON extends Command_Iterator_File {
	protected $extensions = array(
		"conf"
	);
	private $conf_options = array();
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
		$this->conf_options = conf::options_default(array(
			"variables" => array()
		) + $this->options);
	}
	protected function process_file(SplFileInfo $file) {
		$source_name = $file->getPathname();
		$target_name = file::extension_change($source_name, "json");
		$result = conf::load($source_name, $this->conf_options);
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
				"entries" => zesk\Locale::plural("entry", $n)
			));
			return;
		}
		if (count($result) > 0) {
			file_put_contents($target_name, json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
		}
	}
	protected function finish() {
	}
}
