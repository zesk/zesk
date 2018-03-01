<?php
namespace zesk\Response;

use zesk\Response;
use zesk\File;
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
	 * @see \zesk\Response\Type::passthru()
	 */
	function output($content) {
		if ($this->file) {
			$fp = fopen($this->file, "r");
			fpassthru($fp);
			fclose($fp);
		} else {
			echo $content;
		}
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
		$this->parent->output_handler(Response::CONTENT_TYPE_RAW);
		$this->parent->content_type(MIME::from_filename($file));
		$this->file = $file;
		return $this->parent;
	}
	
	/**
	 * Download a file
	 *
	 * @param string $file
	 *        	Full path to file to download
	 * @param string $name
	 *        	File name given to browser to save the file
	 * @param string $type
	 *        	Content disposition type (attachment)
	 * @return \zesk\Response
	 */
	final public function download($file, $name = null, $type = null) {
		if ($name === null) {
			$name = basename($file);
		}
		$name = File::name_clean($name);
		if ($type === null) {
			$type = "attachment";
		}
		$this->file($file);
		return $this->parent->header("Content-Disposition", "$type; filename=\"$name\"")->nocache();
	}
}
