<?php
namespace zesk;

use \SplFileInfo;

/**
 * Examines all PHP files and lists only those which have whitespace at the top
 * @category Debugging
 * @author kent
 */
class Command_File_White extends Command_Iterator_File {
	protected $extensions = array(
		"php",
		"phpt",
		"inc",
		"php4",
		"php5",
	);

	protected $parsed = 0;

	protected $whites = 0;

	protected function start() {
		$this->parsed = 0;
		$this->whites = 0;
	}

	protected function process_file(SplFileInfo $file) {
		$name = $file->getPathname();
		$this->verbose_log("whitespace in $name");
		ob_start();
		$contents = file_get_contents($name);
		if (!preg_match('/^<\?php/', $contents)) {
			$this->log($name);
			$this->whites++;
		}
		$this->parsed++;
	}

	protected function finish() {
		$this->log("Completed: {parsed} parsed, {whites} with whitespace", array(
			"parsed" => $this->parsed,
			"whites" => $this->whites,
		));
	}
}
