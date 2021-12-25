<?php declare(strict_types=1);
/**
 * @version $URL: https://code.marketacumen.com/zesk/trunk/classes/Net/HTTP/Server/Response.php $
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2011, Market Acumen, Inc.
 * @package zesk
 * @subpackage system
 */
namespace zesk;

class Net_HTTP_Server_Response {
	public $status = Net_HTTP::STATUS_OK;

	public $status_text = null;

	public $headers = [];

	public $content = "";

	public $filename = null;

	public $file = null;

	public function __construct() {
		$this->header("Server", "Zesk Net_HTTP_Server 1.0");
		//$this->header("Connection", "close");
		$this->header("Date", gmdate("D, d M y H:i:s", time()) . " GMT");
	}

	public function __destruct() {
		$this->close_file();
	}

	public function header($name, $value = null, $replace = false): void {
		if (array_key_exists($name, $this->headers) && !$replace) {
			return;
		}
		if ($value === null) {
			[$name, $value] = pair($name, ":", $name, null);
			if ($value === null) {
				throw new Exception_Syntax("Incorrect header value: $name");
			}
			$value = trim($value);
		}
		$this->headers[$name] = $value;
	}

	public function raw_headers() {
		$raw_headers = [];
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

	public function filename($filename = null) {
		if ($filename === null) {
			return $this->filename;
		}
		$this->filename = $filename;
		$this->content = "";
		return $this;
	}

	private function file_headers(): void {
		if (!$this->filename) {
			return;
		}
		$this->header("Content-Type", MIME::from_filename($this->filename, "application/octet-stream"), false);
		if (file_exists($this->filename)) {
			$this->header("Content-Length", filesize($this->filename), true);
		}
	}

	public function file() {
		if ($this->file) {
			return $this->file;
		}
		if ($this->filename) {
			if (!file_exists($this->filename)) {
				$this->status = Net_HTTP::STATUS_FILE_NOT_FOUND;
			}
			$this->file = fopen($this->filename, "rb");
			return $this->file;
		}
		return null;
	}

	public function close_file(): void {
		if ($this->file) {
			fclose($this->file);
			$this->file = null;
		}
	}

	public function content() {
		return $this->content;
	}
}
