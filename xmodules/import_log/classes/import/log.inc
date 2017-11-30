<?php
/**
 * $URL: https://code.marketacumen.com/zesk/trunk/modules/import_log/classes/import/log.inc $
 * @package zesk
 * @subpackage system
 * @author kent
 * @copyright Copyright &copy; 2009, Market Acumen, Inc.
 * Created on Wed Feb 24 15:47:45 EST 2010 15:47:45
 */
namespace zesk;

abstract class Import_Log extends Options implements \Iterator {
	private $_filename;
	private $_fp;
	protected $header_read_size = 1024;
	protected $line_read_size = 10240;
	protected $current = null;
	protected $valid = true;
	protected $rewind_tell = 0;
	protected $line_number = 0;
	function __construct($filename, array $options = array()) {
		$this->_filename = $filename;
		parent::__construct($options);
	}
	final function open() {
		if (is_resource($this->_fp)) {
			return true;
		}
		$filename = $this->_filename;
		if (!is_file($filename)) {
			throw new Exception_File_NotFound($filename, "Retrieving import log");
		}
		$this->_fp = fopen($filename, "r");
		if (!$this->_fp) {
			throw new Exception_File_Permission($filename, "Can not open $filename for reading");
		}
		return $this->read_header();
	}
	final protected function read_header() {
		if ($this->header_read_size <= 0) {
			return true;
		}
		$this->open();
		$tell = ftell($this->_fp);
		if ($tell === false) {
			throw new Exception_Unimplemented("Import_Log::read_header: Can not tell in file " . $this->_filename);
		}
		$header = fread($this->_fp, $this->header_read_size);
		$header_offset = $this->_read_header($header);
		$this->rewind_tell = $tell + $header_offset;
		if (fseek($this->_fp, $header_offset) < 0) {
			throw new Exception_Unimplemented("Import_Log::read_header: Can not seek to position $header_offset in file " . $this->_filename);
		}
		return true;
	}
	protected function _read_header($header_data) {
		return 0;
	}
	protected function _read_line() {
		$this->open();
		$line = fgets($this->_fp, $this->line_read_size);
		if ($line === false) {
			return null;
		}
		$this->line_number++;
		return $line;
	}
	abstract public function read_line();
	function rewind() {
		$this->open();
		fseek($this->_fp, $this->rewind_tell);
		$this->line_number = 0;
		$this->current = $this->read_line();
	}
	function current() {
		return $this->current;
	}
	function key() {
		return ftell($this->_fp);
	}
	function next() {
		$this->current = $this->read_line();
	}
	function valid() {
		return (!empty($this->current));
	}
}
