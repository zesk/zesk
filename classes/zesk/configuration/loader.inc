<?php

namespace zesk;

class Configuration_Loader {
	/**
	 * 
	 * @var \string
	 */
	private $name = null;
	/**
	 * Paths to search for configuration files to process
	 * 
	 * @var \string[]
	 */
	private $paths = array();
	/**
	 * Files to process
	 *
	 * @var \string[]
	 */
	private $files = array();
	
	/**
	 * Files which could be loaded, but do not exist
	 * 
	 * @var \array
	 */
	private $missing_files = array();
	/**
	 * Files which could be loaded, but do not exist
	 * 
	 * @var \array
	 */
	private $processed_files = array();
	/**
	 * Files which could be loaded, but do not exist
	 * 
	 * @var \array
	 */
	private $skipped_files = array();
	/**
	 * 
	 * @var File_Monitor_List
	 */
	private $file_monitor = null;
	
	/**
	 * 
	 * @var \Interface_Settings
	 */
	private $settings = null;
	
	/**
	 * 
	 * @var Configuration_Dependency
	 */
	private $dependency = null;
	
	/**
	 * 
	 * @param array $files
	 * @param array $paths
	 * @param \Interface_Settings $context
	 */
	public function __construct($name, array $paths, array $files, \Interface_Settings $settings) {
		$available_targets = array();
		$possible_targets = array();
		$this->paths = $paths;
		foreach ($paths as $path) {
			if (!is_dir($path)) {
				foreach ($files as $file) {
					$this->missing_files[] = path($path, $file);
				}
			} else {
				foreach ($files as $file) {
					$file = path($path, $file);
					$possible_targets[] = $file;
					if (is_readable($file)) {
						$available_targets[] = $file;
					} else {
						$this->missing_files[] = $file;
					}
				}
			}
		}
		$this->settings = $settings;
		$this->files = $available_targets;
		$this->file_monitor = new File_Monitor_List($files);
		$this->dependency = new Configuration_Dependency();
	}
	
	/**
	 * @return string[]
	 */
	public function paths() {
		return $this->paths;
	}
	
	/**
	 * Add additional files to load
	 * 
	 * @param array $files
	 * @return \zesk\Configuration_Loader
	 */
	public function append_files(array $files) {
		$this->files = array_merge($this->files, $files);
		return $this;
	}
	/**
	 * 
	 * @return number|unknown
	 */
	public function load() {
		while (count($this->files) > 0) {
			$file = array_shift($this->files);
			
			$extension = strtoupper(file::extension($file));
			$content = file_get_contents($file);
			$parser = Configuration_Parser::factory($extension, $content, $this->settings);
			if (!$parser) {
				$this->skipped_files[] = $file;
				zesk()->logger->warning("Unable to parse configuration file {file} - no parser", compact("file"));
				continue;
			}
			$parser->configuration_dependency($this->dependency);
			$parser->configuration_loader($this);
			$parser->process();
			
			$this->processed_files[] = $file;
		}
	}
	public function variables() {
		return array(
			"processed" => $this->processed_files,
			"missing" => $this->missing_files,
			"skipped" => $this->skipped_files
		);
	}
}