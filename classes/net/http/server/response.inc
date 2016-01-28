<?php
/**
 * @version $URL: https://code.marketacumen.com/zesk/trunk/classes/net/http/server/response.inc $
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2011, Market Acumen, Inc.
 * @package zesk
 * @subpackage system
 */

class Net_HTTP_Server_Response {
	public $status = Net_HTTP::Status_OK;
	public $status_text = null;
	public $headers = array();
	
	public $content = "";
	public $filename = null;
	public $file = null;
	
	function __construct() {
		$this->header("Server", "Zesk Net_HTTP_Server 1.0");
		//$this->header("Connection", "close");
		$this->header("Date", gmdate("D, d M y H:i:s", time()) . " GMT");
	}
	
	function __destruct() {
		$this->close_file();
	}
	function header($name, $value = null, $replace = false) {
		if (array_key_exists($name, $this->headers) && !$replace) {
			return;
		}
		if ($value === null) {
			list ($name, $value) = pair($name, ":", $name, null);
			if ($value === null) {
				throw new Exception_Syntax("Incorrect header value: $name");
			}
			$value = trim($value);
		}
		$this->headers[$name] = $value;
	}
	
	function raw_headers() {
		$raw_headers = array();
		$status_text = ($this->status_text === null) ? avalue(Net_HTTP::$status_text, $this->status, "Unknown status") : $this->status_text;
		$raw_headers[] = "HTTP/1.0 " . $this->status . " " . $status_text;
		$this->file_headers();
		$this->header("Content-Type", "text/html", false);
		foreach ($this->headers as $key => $value) {
			if (is_array($value)) {
				foreach ($value as $value_line) {
					$raw_headers[] = "$key: $value_line";
				}
			} else {
				$raw_headers[] = "$key: $value";
			}
		}
		return implode("\r\n", $raw_headers) . "\r\n\r\n";
	
	}
	
	function filename($filename=null)
	{
		if ($filename === null) {
			return $this->filename;
		}
		$this->filename = $filename;
		$this->content = "";
		return $this;
	}
	
	private function file_headers()
	{
		if (!$this->filename) {
			return;
		}
		$this->header("Content-Type", mime::from_filename($this->filename, "application/octet-stream"), false);
		if (file_exists($this->filename)) {
			$this->header("Content-Length", filesize($this->filename), true);
		}
	}
	
	function file() {
		if ($this->file) {
			return $this->file;
		}
		if ($this->filename) {
			if (!file_exists($this->filename)) {
				$this->status = Net_HTTP::Status_File_Not_Found;
			}	
			$this->file = fopen($this->filename, "r");
			return $this->file;
		}
		return null;
	}
	
	function close_file() {
		if ($this->file) {
			fclose($this->file);
			$this->file = null;
		}
	}
	function content() {
		return $this->content;
	}
}
