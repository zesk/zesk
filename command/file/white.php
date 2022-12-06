<?php declare(strict_types=1);
namespace zesk;

use \SplFileInfo;

/**
 * Examines all PHP files and lists only those which have whitespace at the top
 * @category Debugging
 * @author kent
 */
class Command_File_White extends Command_Iterator_File {
	protected $extensions = [
		'php',
		'phpt',
		'inc',
		'php4',
		'php5',
	];

	protected $parsed = 0;

	protected $whites = 0;

	protected function start(): void {
		$this->parsed = 0;
		$this->whites = 0;
	}

	protected function process_file(SplFileInfo $file): void {
		$name = $file->getPathname();
		$this->verboseLog("whitespace in $name");
		ob_start();
		$contents = file_get_contents($name);
		if (!preg_match('/^<\?php/', $contents)) {
			$this->log($name);
			$this->whites++;
		}
		$this->parsed++;
	}

	protected function finish(): void {
		$this->log('Completed: {parsed} parsed, {whites} with whitespace', [
			'parsed' => $this->parsed,
			'whites' => $this->whites,
		]);
	}
}
