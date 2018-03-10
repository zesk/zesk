<?php
/**
 * @version $URL: https://code.marketacumen.com/zesk/trunk/classes/Net/HTTP/Server/Request.php $
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2011, Market Acumen, Inc.
 * @package zesk
 * @subpackage system
 */
namespace zesk;

class Net_HTTP_Server_Request {
	public $method;
	public $raw_uri;
	public $protocol;
	public $uri;
	public $query_string;
	public $headers = array();
	public $content = '';
	public function __construct($raw_request) {
		$lines = explode("\r\n", $raw_request);
		$raw_request_line = array_shift($lines);
		$regs = null;
		if (!preg_match("'([^ ]+) ([^ ]+) (HTTP/[^ ]+)'", $raw_request_line, $regs)) {
			throw new Net_HTTP_Server_Exception(Net_HTTP::STATUS_BAD_REQUEST, null, $raw_request_line);
		}
		
		list($this->method, $this->raw_uri, $this->protocol) = $regs;
		
		$cur_line = null;
		while (count($lines) > 0) {
			$line = array_shift($lines);
			if ($line === "") {
				break;
			}
			$first_char = $line[0];
			if ($first_char === " " || $first_char === "\t") {
				$cur_line = ($cur_line === null) ? $line : $cur_line . $line;
			} else {
				$this->add_header($cur_line);
				$cur_line = $line;
			}
		}
		$this->add_header($cur_line);
		$this->content = implode("\r\n", $lines);
		
		list($this->uri, $this->query_string) = pair($this->raw_uri, "?", $this->raw_uri, "");
	}
	private function add_header($raw_header) {
		if ($raw_header === null) {
			return;
		}
		list($name, $value) = pair($raw_header, ":", $raw_header, null);
		if ($value === null) {
			throw new Net_HTTP_Server_Exception(Net_HTTP::STATUS_BAD_REQUEST, "Bad header", $raw_header);
		}
		$name = strtolower($name);
		ArrayTools::append($this->headers, $name, ltrim($value));
	}
	function header($name) {
		return avalue($this->headers, strtolower($name));
	}
	function set_globals() {
		foreach ($this->headers as $name => $value) {
			$_SERVER['HTTP_' . str_replace('-', '_', strtoupper($name))] = $value;
		}
		$host = $this->header('Host');
		if ($host) {
			list($_SERVER['HTTP_HOST'], $_SERVER['SERVER_PORT']) = pair($host, ':', $host, 80);
		}
		
		$_SERVER['QUERY_STRING'] = $this->query_string;
		$_SERVER['SERVER_PROTOCOL'] = $this->protocol;
		$_SERVER['REQUEST_METHOD'] = $this->method;
		$_SERVER['REQUEST_URI'] = $this->uri;
		
		$_GET = array();
		parse_str($this->query_string, $_GET);
		if (count($_GET) == 0) {
			$_SERVER['argc'] = 0;
			$_SERVER['argv'] = array();
		} else {
			$_SERVER['argc'] = 1;
			$_SERVER['argv'] = array(
				$this->query_string
			);
		}
	}
}
