<?php
namespace zesk\WebApp;

use zesk\Exception_Configuration;
use zesk\Directory;

class Module extends \zesk\Module {
	/**
	 *
	 * @var string
	 */
	private $app_root = null;

	/**
	 *
	 * {@inheritDoc}
	 * @see \zesk\Module::initialize()
	 */
	public function initialize() {
		$this->app_root = $this->application->paths->expand($this->option("path"));
		if (empty($this->app_root)) {
			throw new Exception_Configuration(__CLASS__ . "::path", "Requires the app root path to be set in order to work.");
		}
		if (!is_dir($this->app_root)) {
			throw new Exception_Configuration(__CLASS__ . "::path", "Requires the app root path to be a directory in order to work.");
		}
	}

	/**
	 *
	 * @return string
	 */
	public function app_root_path() {
		return $this->app_root;
	}

	/**
	 *
	 */
	public function scan_for_apps() {
		// Include *.application.php, do not walk through . directories, or /vendor/, do not include directories in results
		$rules = array(
			"rules_file" => array(
				"#/[-a-zA-Z0-9]*\.application\.php$#" => true,
				"#/webapp.json$#" => true,
				false,
			),
			"rules_directory_walk" => array(
				"#/\.#" => false,
				"#/vendor/#" => false,
				"#/node_modules/#" => false,
				true,
			),
			"rules_directory" => false,
		);
		$files = Directory::list_recursive($this->app_root, $rules);
		return $files;
	}
}
