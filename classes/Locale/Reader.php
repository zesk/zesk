<?php
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
	const ERROR_NOT_ARRAY = "ERROR_NOT_ARRAY";
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
	private $paths = array();
	
	/**
	 *
	 * @var array
	 */
	private $extensions = array();
	
	/**
	 *
	 * @var array
	 */
	private $errors = array();
	
	/**
	 *
	 * @var array
	 */
	private $loaded = array();
	
	/**
	 *
	 * @var array
	 */
	private $missing = array();
	
	/**
	 *
	 * @param array $paths
	 * @param unknown $locale
	 * @param array $extensions
	 * @return \zesk\Locale\Reader
	 */
	static function factory(array $paths, $id, array $extensions = array()) {
		return new self($paths, $id, $extensions);
	}
	/**
	 *
	 * @param array $paths
	 * @param unknown $language
	 * @param unknown $dialect
	 * @param array $extensions
	 */
	function __construct(array $paths, $id, array $extensions = array()) {
		$this->paths = $paths;
		list($language, $dialect) = Locale::parse($id);
		$this->id = Locale::normalize($id);
		$this->language = $language;
		$this->dialect = $dialect;
		$this->extensions = count($extensions) ? $extensions : array(
			"php",
			"inc",
			"json"
		);
	}
	/**
	 * Return list of files which were missing upon execute
	 *
	 * @return array
	 */
	function missing() {
		return $this->missing;
	}
	/**
	 * Return list of files which were loaded upon execute
	 *
	 * @return array
	 */
	function loaded() {
		return $this->loaded;
	}
	
	/**
	 * Return list of files which produced errors upon execute.
	 * Returns file => error
	 *
	 * @return string[string]
	 */
	function errors() {
		return $this->errors;
	}
	
	/**
	 *
	 * @param Application $application
	 * @param array $options
	 * @return Locale
	 */
	function locale(Application $application, array $options = array()) {
		return Locale::factory($application, $this->id, $options)->translations($this->execute());
	}
	/**
	 * Load it
	 *
	 * @return array
	 */
	function execute() {
		$this->loaded = array();
		$this->errors = array();
		$this->missing = array();
		$results = array();
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
	function files() {
		$files = array();
		$prefixes = array(
			"all",
			$this->language
		
		);
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
		if (in_array($extension, array(
			"php",
			"inc"
		))) {
			return $this->_require($file);
		}
		if ($extension === "json") {
			return JSON::decode(File::contents($file));
		}
		throw new Exception_Unsupported("Locale file {file} extension {extension} not supported", array(
			"file" => $file,
			"extension" => $extension
		));
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