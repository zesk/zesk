<?php
declare(strict_types=1);

namespace zesk;

abstract class File_Monitor {
	/**
	 * Array o $filename => filemtime($filename)
	 *
	 * @var array
	 */
	private array $file_mtimes = [];

	/**
	 * Create a new File_Monitor
	 */
	public function __construct() {
		$this->file_mtimes = $this->current_mtimes();
	}

	/**
	 * Retrieve the current modification times of the current file list
	 *
	 * @return array
	 */
	private function current_mtimes(): array {
		$current = [];
		foreach ($this->files() as $f) {
			clearstatcache(false, $f);
			$current[$f] = @filemtime($f);
		}
		return $current;
	}

	/**
	 * List of filenames which have been modified since last successful check
	 *
	 * @return string[]
	 */
	public function changed_files(): array {
		$result = [];
		$current = $this->current_mtimes();
		foreach ($this->file_mtimes as $filename => $saved_mod_time) {
			if (!isset($current[$filename])) {
				error_log(map('Huh? Existing file monitor file {file} no longer monitored?', [
					'file' => $filename,
				]));
			} else {
				if ($current[$filename] !== $saved_mod_time) {
					$result[] = $filename;
				}
			}
		}
		return $result;
	}

	/**
	 * Did any of the files change?
	 *
	 * Calling this may possibly expand the list of stored files
	 *
	 * @return boolean
	 */
	public function changed(): bool {
		$current = $this->current_mtimes();
		foreach ($this->file_mtimes as $filename => $saved_mod_time) {
			if (!isset($current[$filename])) {
				error_log(map('Huh? Existing file monitor file {file} no longer monitored?', [
					'file' => $filename,
				]));
			} else {
				if ($current[$filename] !== $saved_mod_time) {
					return true;
				}
				unset($current[$filename]);
			}
		}
		$this->file_mtimes += $current;
		return false;
	}

	/**
	 * Returns an array of absolute file paths
	 *
	 * @return array
	 */
	abstract protected function files(): array;
}
