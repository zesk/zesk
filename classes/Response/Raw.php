<?php
namespace zesk\Response;

use zesk\Response;
use zesk\MIME;
use zesk\Exception_File_NotFound;

class Raw extends Type {

	/**
	 *
	 * @var string
	 */
	private $file = null;

	/**
	 *
	 * @var string
	 */
	private $binary = null;

	/**
	 *
	 */
	function initialize() {
	}

	/**
	 *
	 */
	function headers() {
		if ($this->file) {
			$this->parent->content_type(MIME::from_filename($this->file));
			$this->header("Last-Modified", gmdate('D, d M Y H:i:s \G\M\T', filemtime($this->file)));
			$this->header("Content-Length", filesize($this->file));
		}
	}

	/**
	 *
	 * {@inheritDoc}
	 * @see \zesk\Response\Type::render()
	 */
	function render($content) {
		ignore_user_abort(1);
		ini_set("max_execution_time", 5000 /* seconds */);
		return $content;
	}

	/**
	 *
	 * {@inheritDoc}
	 * @see \zesk\Response\Type::passthru()
	 */
	function passthru($content) {
		echo $this->render($content);
	}
	/**
	 *
	 */
	function file($file = null) {
		if ($file === null) {
			return $this->file;
		}
		if (!file_exists($file)) {
			throw new Exception_File_NotFound($file);
		}
		$this->file = $file;
		return $this->parent;
	}
}
