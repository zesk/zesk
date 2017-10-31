<?php

/**
 *
 */
namespace zesk;

/**
 *
 * @author kent
 *
 */
class Command_Test_Generate extends Command_Iterator_File {
	protected $extensions = array(
		"php",
		"inc"
	);

	/**
	 *
	 * @var array
	 */
	private $autoload_paths = null;

	/**
	 * (non-PHPdoc)
	 *
	 * @see Command_Base::initialize()
	 */
	function initialize() {
		parent::initialize();
		$this->option_types += array(
			"target" => 'dir'
		);
		$this->option_help += array(
			"target" => "Path to create generated test files"
		);
	}

	/**
	 */
	protected function start() {
		$this->autoload_paths = $this->application->autoloader->path();
		$this->target = $this->option("target");
		if (!$this->target) {
			$this->usage("--target is required");
		}
		Directory::depend($this->target);
	}

	/**
	 *
	 * @param SplFileInfo $file
	 * @return boolean Return false to stop processing all further files
	 */
	protected function process_file(\SplFileInfo $file) {
		$filename = $file->getFilename();
		$fullpath = $file->getRealPath();
		$suffix = $this->in_autoload_path($fullpath);
		if (!$suffix) {
			$this->verbose_log("{fullpath} not in autoload path", array(
				"fullpath" => $fullpath
			));
			return true;
		}
		include_once $fullpath;
		$target_file = path($this->target, $suffix);
		$target_dir = dirname($target_file);
		$target_file = basename($target_file);
		$target_file = File::extension_change($target_file, null);
		$target_file = path($target_dir, File::extension_change($target_file . '_Test', "php"));
		echo "Would create $target_file\n";

		Test_Generator::factory($this->application, $fullpath, $target_file);

		return true;
	}

	/**
	 */
	protected function finish() {
	}

	/**
	 *
	 * @return null|string
	 */
	private function in_autoload_path($file) {
		if (!$this->first) {
			$this->first = true;
		}
		foreach ($this->autoload_paths as $path => $options) {
			$path = rtrim($path, "/") . "/";
			if (begins($file, $path)) {
				return substr($file, strlen($path));
			}
		}
		return false;
	}
}
