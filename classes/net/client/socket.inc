<?php
/**
 * $URL: https://code.marketacumen.com/zesk/trunk/classes/net/client/socket.inc $
 * @package zesk
 * @subpackage system
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2012, Market Acumen, Inc.
 */
class Net_Client_Socket extends Net_Client {
	protected $EOL = "\r\n";
	
	/**
	 * Connected socket
	 * 
	 * @var resource
	 */
	private $socket;
	
	/**
	 * The class name of the exception to throw when errors occur
	 * 
	 * @var unknown_type
	 */
	protected $default_port = -1;
	
	/**
	 * Buffered data
	 * 
	 * @var string
	 */
	protected $buffer = "";
	
	/**
	 * Connect to the socket
	 * 
	 * @see Net_Client::connect()
	 */
	public function connect() {
		if ($this->has_option("eol")) {
			$this->EOL = $this->option('eol', $this->EOL);
		}
		if ($this->is_connected()) {
			return $this->greeting;
		}
		
		$host = avalue($this->url_parts, 'host', 'localhost');
		$port = aevalue($this->url_parts, 'port', $this->default_port);
		$timeout = $this->option_integer('timeout', 30);
		
		$errno = false;
		$errstr = false;
		
		$this->log("Connecting to $host:$port");
		$this->socket = @fsockopen($host, $port, $errno, $errstr, $timeout);
		if (!is_resource($this->socket)) {
			throw new Exception_Connect("$host:$port", "Could not connect to $host:$port $errstr");
		}
		stream_set_timeout($this->socket, $timeout, 0);
		stream_set_blocking($this->socket, $this->option_bool('blocking', true) ? 1 : 0);
		$this->log("Connected.");
		$this->greeting = $this->read();
		return true;
	}
	
	/**
	 * Disconnect
	 * 
	 * @see Net_Client::disconnect()
	 */
	public function disconnect() {
		if (is_resource($this->socket)) {
			fclose($this->socket);
			$this->socket = null;
		}
	}
	/**
	 *
	 * @see Net_Client::is_connected()
	 */
	public function is_connected() {
		return is_resource($this->socket);
	}
	/**
	 * Make sure we're connected
	 * 
	 * @throws Exception_Semantics
	 */
	protected function _check() {
		if (!$this->is_connected()) {
			throw new Exception_Semantics("Not connected to server");
		}
	}
	
	/**
	 * Execute a command
	 * 
	 * @param string $command
	 *        	Command to run
	 * @param string $expect
	 *        	String to expect from the other side as a response
	 * @throws Exception_Protocol
	 * @return boolean
	 */
	protected function command($command, $expect = null) {
		$this->write(trim($command));
		
		if (!is_string($expect)) {
			return true;
		}
		$result = false;
		if (!$this->expect($expect, $result)) {
			throw new Exception_Protocol("$command expected $expect, received $result");
		}
		return $result;
	}
	
	/**
	 * Expect some data back
	 * 
	 * @param string $expect
	 *        	String to match (beginning only)
	 * @param string $result
	 *        	Found string
	 * @return boolean
	 */
	protected function expect($expect, &$result) {
		$result = array();
		do {
			$line = $this->read();
			$result[] = $line;
		} while (substr($line, 3, 1) === '-');
		$result = implode("\n", $result);
		return begins(trim($result), $expect);
	}
	
	/**
	 * Write data to socket
	 * 
	 * @param string $data        	
	 * @return number of bytes written
	 */
	public function write($data) {
		$this->_check();
		$this->log($data, "> ");
		return $this->write_data($data . $this->EOL);
	}
	public function write_data($data) {
		return fwrite($this->socket, $data, strlen($data));
	}
	public function read_wait($milliseconds = 600000) {
		$timeout = microtime(true) + $milliseconds;
		do {
			$status = socket_get_status($this->socket);
			$bytes = $status["unread_bytes"];
			if ($bytes === 0) {
				sleep(100);
			} else {
				return $this->read();
			}
		} while (microtime(true) < $timeout);
		throw new Exception("read_wait timed out after $milliseconds milliseconds");
	}
	
	/**
	 * Read data from socket
	 * 
	 * @throws Exception_Protocol
	 * @return string
	 */
	public function read() {
		if (($pos = strpos($this->buffer, $this->EOL)) === false) {
			$this->buffer .= $this->_read($this->option_integer('read_buffer_size', 10240));
			$pos = strpos($this->buffer, $this->EOL);
			if ($pos === false) {
				throw new Exception_Protocol("Server returned a line not ending in EOL: $this->buffer");
			}
		}
		$eol_len = strlen($this->EOL);
		$line = substr($this->buffer, 0, $pos + $eol_len);
		if (($this->buffer = substr($this->buffer, $pos + $eol_len)) === false) {
			$this->buffer = '';
		}
		return $line;
	}
	public function read_data($length) {
		if (strlen($this->buffer) > $length) {
			$data = substr($this->buffer, 0, $length);
			$this->buffer = substr($this->buffer, $length);
			return $data;
		}
		$read_length = $length - strlen($this->buffer);
		$data = $this->buffer;
		$this->buffer = "";
		return $data . $this->_read($read_length);
	}
	/**
	 * Internal read data (unbuffered)
	 * 
	 * @param inteeger $n_chars
	 *        	Number of characters
	 * @throws Exception_Protocol
	 * @return string
	 */
	protected function _read($n_chars = 1024) {
		$this->_check();
		if ($this->option_bool('read_debug')) {
			echo "->read($n_chars) = ";
		}
		$result = fread($this->socket, $n_chars);
		if ($this->option_bool('read_debug')) {
			echo ($result === false ? "false" : strlen($result)) . " $result\n";
		}
		if (strlen($result) === 0) {
			throw new Exception_Protocol("fread returned empty: $result");
		}
		$this->log($result, "< ");
		return $result;
	}
}
