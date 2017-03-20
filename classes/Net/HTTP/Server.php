<?php
/**
 * @version $URL: https://code.marketacumen.com/zesk/trunk/classes/Net/HTTP/Server.php $
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2011, Market Acumen, Inc.
 * @package zesk
 * @subpackage system
 */
namespace zesk;

abstract class Net_HTTP_Server extends Net_Server {
	function __construct($host = null, $port = 80, $driver = null) {
		parent::__construct($host, $port, $driver);
		$this->driver->read_end_char("\r\n\r\n");
	}

	final function hook_receive($client_id = 0, $data = "") {
		global $zesk;
		$response = new Net_HTTP_Server_Response();
		try {
			$this->handle_request(new Net_HTTP_Server_Request($data), $response);
		} catch (Net_HTTP_Server_Exception $e) {
			$zesk->hooks->call("exception", $e);
			$this->error_response($response, $e);
		} catch (Exception $e) {
			$zesk->hooks->call("exception", $e);
			$this->error_response($response, new Net_HTTP_Server_Exception(Net_HTTP::Status_Internal_Server_Error, null, $e->getMessage()));
		}
		$this->send_response($client_id, $response);
		$this->close($client_id);
	}

	abstract protected function handle_request(Net_HTTP_Server_Request $request, Net_HTTP_Server_Response $response);

	final private function send_response($client_id, Net_HTTP_Server_Response $response) {
		$f = $response->file();
		if ($f) {
			$this->send($client_id, $response->raw_headers());
			while (!feof($f)) {
				$data = fread($f, 4096);
				$this->send($client_id, $data);
			}
			$response->close_file();
		} else {
			$response->header("Content-Length", strlen($response->content));
			$this->send($client_id, $response->raw_headers() . $response->content);
		}
	}

	final private function error_response(Net_HTTP_Server_Response $response, Net_HTTP_Server_Exception $e) {
		$response->status = $e->status;
		$response->status_text = $e->status_text;
		$response->content($e->getMessage());
	}
}
