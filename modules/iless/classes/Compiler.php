<?php
/**
 *
 */
namespace zesk\ILess;

use zesk\File;
use zesk\Directory;
use zesk\Application;
use ILess\Cache\FileSystemCache;
use ILess\Parser;

/**
 *
 * @author kent
 *
 */
class Compiler {
	/**
	 * Wrapper around lessc
	 *
	 * @var ILess\Parser
	 */
	private $iless = null;

	/**
	 *
	 * @var array
	 */
	private $vars = array();

	/**
	 *
	 * @param Application $application
	 */
	public function __construct(Application $application) {
		$cache_dir = $application->cache_path($application->iless_module()->option("cache_suffix", 'iless'));
		Directory::depend($cache_dir);
		$cache = new FileSystemCache($cache_dir);
		$this->iless = new Parser(array(
			"compress" => false,
			"strictMath" => false,
			"source_map" => $application->development(),
		), $cache);
	}

	/**
	 *
	 * @param unknown $set
	 * @return \zesk\ILess\Compiler|array
	 */
	public function variables($set = null) {
		if (is_array($set)) {
			$this->vars += $set;
			$this->iless->setVariables($set);
			return $this;
		}
		return $this->vars;
	}

	/**
	 *
	 * @param string $file
	 * @param string $destination
	 * @return string|unknown
	 */
	public function compile_file($file, $destination = null) {
		$this->iless->parseFile($file);
		$content = $this->iless->getCSS();
		if ($destination !== null) {
			File::put($destination, $content);
			return $destination;
		}
		return $content;
	}

	/**
	 *
	 * @param string $source Source CSS
	 * @param string $destination File to output
	 * @return string|unknown
	 */
	public function compile($source, $destination = null) {
		$this->iless->parseString($source);
		$content = $this->iless->getCSS();
		if ($destination !== null) {
			File::put($destination, $content);
			return $destination;
		}
		return $content;
	}
}
