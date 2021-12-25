<?php declare(strict_types=1);
/**
 * @package zesk
 * @subpackage Locale
 * @author kent
 * @copyright &copy; 2018 Market Acumen, Inc.
 */
namespace zesk\Locale;

use zesk\File;
use zesk\JSON;
use zesk\Exception_Unsupported;
use zesk\Locale;
use zesk\Application;

/**
 * @author kent
 */
class Reader {
	/**
	 *
	 * @var string
	 */
	public const ERROR_NOT_ARRAY = "ERROR_NOT_ARRAY";

	/**
	 *
	 * @var string
	 */
	private $id = null;

	/**
	 *
	 * @var string
	 */
	private $language = null;

	/**
	 *
	 * @var string
	 */
	private $dialect = null;

	/**
	 *
	 * @var array
	 */
	private $paths = [];

	/**
	 *
	 * @var array
	 */
	private $extensions = [];

	/**
	 *
	 * @var array
	 */
	private $errors = [];

	/**
	 *
	 * @var array
	 */
	private $loaded = [];

	/**
	 *
	 * @var array
	 */
	private $missing = [];

	/**
	 *
	 * @param array $paths
	 * @param unknown $locale
	 * @param array $extensions
	 * @return \zesk\Locale\Reader
	 */
	public static function factory(array $paths, $id, array $extensions = []) {
		return new self($paths, $id, $extensions);
	}

	/**
	 *
	 * @param array $paths
	 * @param unknown $language
	 * @param unknown $dialect
	 * @param array $extensions
	 */
	public function __construct(array $paths, $id, array $extensions = []) {
		$this->paths = $paths;
		[$language, $dialect] = Locale::parse($id);
		$this->id = Locale::normalize($id);
		$this->language = $language;
		$this->dialect = $dialect;
		$this->extensions = count($extensions) ? $extensions : [
			"php",
			"inc",
			"json",
		];
	}

	/**
	 * Return list of files which were missing upon execute
	 *
	 * @return array
	 */
	public function missing() {
		return $this->missing;
	}

	/**
	 * Return list of files which were loaded upon execute
	 *
	 * @return array
	 */
	public function loaded() {
		return $this->loaded;
	}

	/**
	 * Return list of files which produced errors upon execute.
	 * Returns file => error
	 *
	 * @return string[string]
	 */
	public function errors() {
		return $this->errors;
	}

	/**
	 *
	 * @param Application $application
	 * @param array $options
	 * @return Locale
	 */
	public function locale(Application $application, array $options = []) {
		return Locale::factory($application, $this->id, $options)->translations($this->execute());
	}

	/**
	 * Load it
	 *
	 * @return array
	 */
	public function execute() {
		$this->loaded = [];
		$this->errors = [];
		$this->missing = [];
		$results = [];
		foreach ($this->files() as $file) {
			if (file_exists($file)) {
				try {
					$result = $this->load($file);
					if (!is_array($result)) {
						$this->errors[$file] = self::ERROR_NOT_ARRAY;
					} else {
						$results += $result;
					}
				} catch (\Exception $e) {
					$this->errors[$file] = get_class($e);
				}
			} else {
				$this->missing[] = $file;
			}
		}
		return $results;
	}

	/**
	 *
	 * @return string[]
	 */
	public function files() {
		$files = [];
		$prefixes = [
			"all",
			$this->language,

		];
		if ($this->dialect) {
			$prefixes[] = $this->language . "_" . $this->dialect;
		}
		foreach ($this->paths as $path) {
			foreach ($prefixes as $prefix) {
				foreach ($this->extensions as $ext) {
					$files[] = path($path, "$prefix.$ext");
				}
			}
		}
		return $files;
	}

	/**
	 * Load a file
	 * @param string $file
	 * @return array
	 * @throws Exception_Unsupported
	 * @return \zesk\Locale\unknown|\zesk\the
	 */
	private function load($file) {
		$extension = File::extension($file);
		if (in_array($extension, [
			"php",
			"inc",
		])) {
			return $this->_require($file);
		}
		if ($extension === "json") {
			return JSON::decode(File::contents($file));
		}

		throw new Exception_Unsupported("Locale file {file} extension {extension} not supported", [
			"file" => $file,
			"extension" => $extension,
		]);
	}

	/**
	 *
	 * @param unknown $file
	 * @return unknown
	 */
	private function _require($file) {
		return require $file;
	}
}
