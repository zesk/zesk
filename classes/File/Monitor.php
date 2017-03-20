<?php

namespace zesk;

abstract class File_Monitor {
	/**
	 * Array o $filename => filemtime($filename)
	 *
	 * @var array
	 */
	private $file_mtimes = array();
	
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
	private function current_mtimes() {
		clearstatcache();
		$current = array();
		foreach ($this->files() as $f) {
			if (file_exists($f)) {
				$current[$f] = filemtime($f);
			} else {
				$current[$f] = "missing";
			}
		}
		return $current;
	}
	/**
	 * Did any of the files change?
	 * 
	 * Calling this may possibly expand the list of stored files
	 * 
	 * @return boolean
	 */
	public function changed() {
		global $zesk;
		/* @var $zesk zesk\Kernel */
		$current = $this->current_mtimes();
		foreach ($this->file_mtimes as $filename => $saved_mod_time) {
			if (!isset($current[$filename])) {
				$zesk->logger->error("Huh? Existing file monitor file {file} no longer monitored?", array(
					"file" => $filename
				));
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
	abstract protected function files();
}
