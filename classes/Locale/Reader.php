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

/**
 * @author kent
 */
class Reader {
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
	 * @param array $paths
	 * @param unknown $language
	 * @param unknown $dialect
	 * @param array $extensions
	 */
	function __construct(array $paths, $language, $dialect, array $extensions = null) {
		$this->paths = $paths;
		$this->language = strtoupper($language);
		$this->dialect = strtoupper($dialect);
		$this->extensions = $extensions ? $extensions : array(
			"php",
			"inc",
			"json"
		);
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