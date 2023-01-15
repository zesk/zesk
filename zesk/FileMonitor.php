<?php
/**
 * @package zesk
 * @subpackage kernel
 * @author kent
 * @copyright &copy; 2022, Market Acumen, Inc.
 */
declare(strict_types=1);

namespace zesk;

/**
 * @see IncludeFileMonitor
 * @see FilesMonitor
 */
abstract class FileMonitor {
	/**
	 * Array o $filename => filemtime($filename)
	 *
	 * @var array
	 */
	private array $fileModificationTimes;

	/**
	 * Create a new File_Monitor
	 */
	public function __construct() {
		$this->fileModificationTimes = $this->currentModificationTimes();
	}

	/**
	 * Retrieve the current modification times of the current file list
	 *
	 * @return array
	 */
	private function currentModificationTimes(): array {
		$current = [];
		foreach ($this->files() as $f) {
			clearstatcache(false, $f);
			$current[$f] = file_exists($f) ? filemtime($f) : null;
		}
		return $current;
	}

	/**
	 * List of filenames which have been modified since last successful check
	 *
	 * @return string[]
	 */
	public function changedFiles(): array {
		$result = [];
		$current = $this->currentModificationTimes();
		foreach ($this->fileModificationTimes as $filename => $saved_mod_time) {
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
	 * Calling this can possibly expand the list of stored files
	 *
	 * @return boolean
	 */
	public function changed(): bool {
		$current = $this->currentModificationTimes();
		foreach ($this->fileModificationTimes as $filename => $saved_mod_time) {
			if (!isset($current[$filename])) {
				PHP::log('Huh? Existing file monitor file {file} no longer monitored?', [
					'file' => $filename,
				]);
			} else {
				if ($current[$filename] !== $saved_mod_time) {
					return true;
				}
				unset($current[$filename]);
			}
		}
		$this->fileModificationTimes += $current;
		return false;
	}

	/**
	 * Returns an array of absolute file paths
	 *
	 * @return array
	 */
	abstract protected function files(): array;
}
