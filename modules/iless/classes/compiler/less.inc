<?php
/**
 *
 */
use zesk\Directory;
use zesk\Application;
use ILess\Cache\FileSystemCache;
use ILess\Parser;

/**
 * 
 * @author kent
 *
 */
class Compiler_Less {
	
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
	public function __construct(Application $application) {
		$cache_dir = $application->path('cache/iless');
		Directory::depend($cache_dir);
		$cache = new FileSystemCache($cache_dir);
		$this->iless = new Parser(array(
			"compress" => false,
			"strictMath" => false,
			"source_map" => $application->development()
		), $cache);
	}
	public function variables($set = null) {
		if (is_array($set)) {
			$this->vars += $set;
			$this->iless->setVariables($set);
			return $this;
		}
		return $this->vars;
	}
	public function compile_file($file, $destination = null) {
		$this->iless->parseFile($file);
		$content = $this->iless->getCSS();
		if ($destination !== null) {
			zesk\file::put($destination, $content);
			return $destination;
		}
		return $content;
	}
	public function compile($source, $destination = null) {
		$this->iless->parseString($source);
		$content = $this->iless->getCSS();
		if ($destination !== null) {
			zesk\file::put($destination, $content);
			return $destination;
		}
		return $content;
	}
}
