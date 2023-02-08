<?php
/**
 * @package zesk
 * @subpackage kernel
 * @author kent
 * @copyright &copy; 2023, Market Acumen, Inc.
 */
declare(strict_types=1);

namespace zesk;

use Closure;

/**
 * @see IncludeFilesMonitor
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
	 * @var array
	 */
	private array $deletedFiles = [];

	/**
	 * Callback for
	 * @var Closure|null
	 */
	private null|Closure $onDeleted = null;

	/**
	 * Create a new File_Monitor
	 */
	public function __construct() {
		$this->initialize();
	}

	/**
	 * Initialize the object
	 *
	 * @return $this
	 */
	protected function initialize(): self {
		$this->fileModificationTimes = $this->currentModificationTimes();
		$this->deletedFiles = [];
		return $this;
	}

	/**
	 * @param callable|Closure $callable
	 * @return self
	 */
	public function setOnDeleted(callable|Closure $callable): self {
		$this->onDeleted = $callable instanceof Closure ? $callable : $callable(...);
		return $this;
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
	 * Returns list of string of files which changed
	 *
	 * @return array
	 */
	public function _changedFiles(array $current, $stopOnFirst = false): array {
		$result = [];
		foreach ($this->fileModificationTimes as $filename => $saved_mod_time) {
			if (!isset($current[$filename])) {
				// Code file disappeared
				$this->deletedFiles[$filename] = $saved_mod_time;
				unset($this->fileModificationTimes[$filename]);
				$onDeleted = $this->onDeleted;
				if ($onDeleted) {
					$onDeleted($filename);
				}
				$result[] = $filename;
				if ($stopOnFirst) {
					return $result;
				}
			} else {
				if (array_key_exists($filename, $this->deletedFiles)) {
					// Code file reappeared
					unset($this->deletedFiles[$filename]);
				}
				if ($current[$filename] !== $saved_mod_time) {
					$result[] = $filename;
					if ($stopOnFirst) {
						return $result;
					}
				}
			}
		}
		return $result;
	}

	/**
	 * List of filenames which have been modified since last successful check
	 *
	 * @return string[]
	 */
	public function changedFiles(): array {
		return $this->_changedFiles($this->currentModificationTimes());
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
		$changed = $this->_changedFiles($current, true);
		$this->fileModificationTimes += $current;
		return count($changed) !== 0;
	}

	/**
	 * Did any of the files get deleted?
	 *
	 * @return array
	 */
	public function deleted(): array {
		return array_keys($this->deletedFiles);
	}

	/**
	 * Returns an array of absolute file paths
	 *
	 * @return array
	 */
	abstract protected function files(): array;
}
