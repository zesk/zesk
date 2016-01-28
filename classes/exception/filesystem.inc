<?php
/**
 * $URL: https://code.marketacumen.com/zesk/trunk/classes/exception/filesystem.inc $
 * @package zesk
 * @subpackage system
 * @author kent
 * @copyright Copyright &copy; 2010, Market Acumen, Inc.
 */
class Exception_FileSystem extends Zesk_Exception {

	protected $filename;

	function __construct($filename = null, $message = "", array $arguments = array(), $code = 0) {
		$this->filename = $filename;
		if (strpos($message, "{filename}") === false) {
			$message = "{filename}: $message";
		}
		parent::__construct($message, array(
			"filename" => $filename
		) + $arguments, $code);
	}

	function filename() {
		return $this->filename;
	}

	function __toString() {
		return "filename: " . $this->filename . "\n" . parent::__toString();
	}
}

