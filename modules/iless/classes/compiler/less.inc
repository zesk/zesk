<?php

class Compiler_Less {

	/**
	 * Wrapper around lessc
	 *
	 * @var ILess_Parser
	 */
	private $iless = null;

	/**
	 *
	 * @var array
	 */
	private $vars = array();

	public function __construct() {
		$path = path(Module::path('iless'), 'library/Autoloader.php');
		if (is_file($path)) {
			require_once $path;
		}
		$cache_dir = zesk::application_root('cache/iless');
		dir::depend($cache_dir);
		$cache = new ILess_Cache_FileSystem($cache_dir);
		//$cache = new ILess_Cache_None();
		$this->iless = new ILess_Parser(array(
			"compress" => false,
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
