<?php

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

	public function __construct() {
		$cache_dir = zesk::application_root('cache/iless');
		dir::depend($cache_dir);
		$cache = new ILess\Cache\FileSystemCache($cache_dir);
		//$cache = new ILess_Cache_None();
		$this->iless = new ILess\Parser(array(
			"compress" => false,
			"strictMath" => false,
			"source_map" => Application::instance()->development()
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
			file::put($destination, $content);
			return $destination;
		}
		return $content;
	}

	public function compile($source, $destination = null) {
		$this->iless->parseString($source);
		$content = $this->iless->getCSS();
		if ($destination !== null) {
			file::put($destination, $content);
			return $destination;
		}
		return $content;
	}
}
