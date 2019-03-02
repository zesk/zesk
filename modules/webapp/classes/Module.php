<?php
namespace zesk\WebApp;

use zesk\Exception_Configuration;
use zesk\Directory;
use zesk\Kernel\Loader;
use zesk\Configuration_Loader;
use zesk\File;

class Module extends \zesk\Module {
	/**
	 *
	 * @var string
	 */
	private $app_root = null;
	protected $model_classes = array(
		Instance::class,
		Host::class,
		Domain::class,
		Cluster::class
	);
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
	 * Where the binary to manage this application is located (generated by `$this->generate_binary()`)
	 *
	 * @return string
	 */
	public function binary() {
		return $this->application->paths->cache("webapp/public/index.php");
	}

	/**
	 * Generates the binary structure for serving up the webapp management module
	 */
	public function generate_binary() {
		$configurations = avalue($this->application->loader->variables(), Configuration_Loader::PROCESSED);

		$path = $this->application->paths->cache("webapp/public/");
		Directory::depend($path, 0775);

		File::put(path($path, "index.php"), "<?php\n\$app = require_once \"../webapp.config.php\");\n\$app->index();\n");

		$path = dirname($path);
		File::put(path($path, "webapp.config.php"), file_get_contents($this->path("theme/webapp.config.tpl")));
		File::put(path($path, "configuration.json"), json_encode($configurations));

		return true;
	}
	/**
	 *
	 */
	public function scan_for_apps() {
		// Include *.application.php, do not walk through . directories, or /vendor/, do not include directories in results
		$rules = array(
			"rules_file" => array(
				"#/webapp.json$#" => true,
				false
			),
			"rules_directory_walk" => array(
				"#/\.#" => false,
				"#/vendor/#" => false,
				"#/node_modules/#" => false,
				true
			),
			"rules_directory" => false
		);
		$files = Directory::list_recursive($this->app_root, $rules);
		foreach ($files as $webapp) {
		}
		return $files;
	}
}
