<?php

class Compiler_Less {

	/**
	 * Wrapper around lessc
	 *
	 * @var lessc
	 */
	private $lessc = null;

	/**
	 *
	 * @var array
	 */
	private $vars = array();

	public function __construct() {
		$path = path(Module::path('lessphp'), 'library/lessc.inc.php');
		if (is_file($path)) {
			require_once $path;
		}
		$this->lessc = new lessc();
	}

	public function variables($set = null) {
		if (is_array($set)) {
			$this->vars += $set;
			$this->lessc->setVariables($set);
			return $this;
		}
		return $this->vars;
	}

	public function compile_file($file, $destination = null) {
		return $this->lessc->compileFile($file, $destination);
	}

	public function compile($source, $destination = null) {
		return $this->lessc->compile($source, $destination);
	}
}
