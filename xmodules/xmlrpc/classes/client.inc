<?php

/**
 * $URL: https://code.marketacumen.com/zesk/trunk/modules/xmlrpc/classes/xmlrpc/client.inc $
 * @package zesk
 * @subpackage system
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2005, Market Acumen, Inc.
 */
namespace xmlrpc;

use zesk\Net_HTTP_Client;
use zesk\Net_HTTP;
use zesk\Application;

class Client extends Net_HTTP_Client {
	const VERSION = 1.0;
	private $Response = false;
	private $LastError = false;
	private $call_map = array();
	function __construct(Application $application, $url) {
		parent::__construct($application, $url);
		
		$this->user_agent('Zesk XML-RPC v' . self::VERSION);
		$this->set_option("Content-Type", "text/xml");
		$this->set_option("Accept", "text/xml");
	}
	function setCallMap($map) {
		$this->call_map = $map;
	}
	private function request($method, $args) {
		$xml = array();
		$xml[] = '<?xml version="1.0" ?>';
		$xml[] = '<methodCall>';
		$xml[] = "<methodName>$method</methodName>";
		$xml[] = '<params>';
		foreach ($args as $arg) {
			$v = new Value($arg);
			$xml[] = '<param><value>' . $v->toXML() . '</value></param>';
		}
		$xml[] = '</params>';
		$xml[] = '</methodCall>';
		
		return implode("\n", $xml);
	}
	function query() {
		$args = func_get_args();
		$method = array_shift($args);
		$result = self::__call($method, $args);
		if (!$result) {
			return $this->LastError;
		}
		return $result;
	}
	function __call($method, $args) {
		if (count($this->call_map)) {
			$method = str_replace(array_keys($this->call_map), array_values($this->call_map), $method);
		}
		
		$this->LastError = false;
		$request = $this->request($method, $args);
		
		if ($this->option("debug") || $this->option("debug-request-xml")) {
			echo "<h1>Invoked $method</h1>\n";
			dump($request);
		}
		$this->method(Net_HTTP::Method_POST);
		$this->data($request);
		
		$contents = $this->go();
		
		$n = strpos($contents, "<?xml");
		if ($n === false) {
			if ($this->option("debug")) {
				echo "Contents:::\n";
				dump($contents);
			}
			throw new Exception(Exception::ERROR_PARSE_INVALID_CHARACTER, "No XML header?");
		}
		$contents = substr($contents, $n);
		$contents = str_replace('<?xml version="1.0" encoding="" ?>', '<?xml version="1.0" encoding="utf8" ?>', $contents);
		if ($this->option("debug") || $this->option("debug-response-xml")) {
			echo "<h1>$method Response</h1>\n";
			dump($contents);
		}
		// Parse the XML result
		$result = null;
		try {
			$parser = new Parser($contents);
			$result = $parser->parse();
		} catch (\Exception $e) {
			$this->LastError = $e;
			throw new Exception(Exception::ERROR_PARSE_NOT_WELL_FORMED, "Malformed XML: " . $e->getMessage() . "<br />\n" . $contents);
		}
		// Was the response an error?
		if ($result->isFault()) {
			$this->LastError = $result->faultError();
			throw $this->LastError;
		}
		
		$this->Response = $result->Parameters;
		$r = $this->Response[0];
		
		return $r;
	}
	public function isFault() {
		return (is_object($this->LastError));
	}
}

