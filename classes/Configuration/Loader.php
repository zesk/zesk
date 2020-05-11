<?php
namespace zesk;

class Configuration_Loader {
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
	 * @var Interface_Settings
	 */
	private $settings = null;

	/**
	 *
	 * @var Configuration_Dependency
	 */
	private $dependency = null;

	/**
	 * Current file being processed
	 *
	 * @var string
	 */
	private $current = null;

	/**
	 *
	 * @param array $files
	 * @param array $paths
	 * @param Interface_Settings $context
	 */
	public function __construct(array $files, Interface_Settings $settings) {
		$available_targets = array();
		foreach ($files as $file) {
			if (is_readable($file)) {
				$available_targets[] = $file;
			} else {
				$this->missing_files[] = $file;
			}
		}
		$this->settings = $settings;
		$this->files = $available_targets;
		$this->file_monitor = new File_Monitor_List($this->files);
		$this->dependency = new Configuration_Dependency();
	}

	/**
	 * Add additional files to load
	 *
	 * @param array $files
	 * @return \zesk\Configuration_Loader
	 */
	public function append_files(array $files, array $missing = null) {
		$this->files = array_merge($this->files, $files);
		if (is_array($missing)) {
			$this->missing_files = array_merge($this->missing_files, $missing);
		}
		return $this;
	}

	public function current() {
		return $this->current;
	}

	/**
	 *
	 * @return number|unknown
	 */
	public function load() {
		while (count($this->files) > 0) {
			$file = array_shift($this->files);

			try {
				$this->current = $file;
				$this->load_one($file);
				$this->current = null;
			} catch (Exception_File_Format $e) {
				error_log(map("Unable to parse configuration file {file} - no parser", compact("file")));
			}
			$this->current = null;
		}
	}

	/**
	 * Load a single file
	 *
	 * @param string $file Path to file to load
	 * @param string $handler Extension to use to load class (CONF, SH, JSON)
	 * @throws Exception_File_Format
	 * @return self|null Return null if file not found
	 */
	public function load_one($file, $handler = null) {
		if (!file_exists($file)) {
			$this->skipped_files[] = $file;
			return null;
		}
		$content = file_get_contents($file);
		$parser = Configuration_Parser::factory($handler ? $handler : File::extension($file), $content, $this->settings);
		if (!$parser) {
			$this->skipped_files[] = $file;

			throw new Exception_File_Format($file, "Unable to parse configuration file {file} - no parser", compact("file"));
		}
		$parser->configuration_dependency($this->dependency);
		$parser->configuration_loader($this);
		$parser->process();
		$this->processed_files[] = $file;
		return $this;
	}

	/**
	 * Return a list of dependency variables which are external to the loaded configuration files.
	 *
	 * @return string[]
	 */
	public function externals() {
		return $this->dependency->externals();
	}

	/**
	 *
	 * @var string
	 */
	const PROCESSED = "processed";

	/**
	 *
	 * @var string
	 */
	const MISSING = "missing";

	/**
	 *
	 * @var string
	 */
	const SKIPPED = "skipped";

	/**
	 *
	 * @var string
	 */
	const EXTERNALS = "externals";

	/**
	 *
	 * @return string[string]
	 */
	public function variables() {
		return array(
			self::PROCESSED => $this->processed_files,
			self::MISSING => $this->missing_files,
			self::SKIPPED => $this->skipped_files,
			self::EXTERNALS => $this->externals(),
		);
	}
}
