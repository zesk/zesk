<?php declare(strict_types=1);
/**
 * @package zesk
 * @subpackage system
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2022, Market Acumen, Inc.
 */
namespace zesk;

class Net_Client_Socket extends Net_Client {
	protected $EOL = "\r\n";

	/**
	 * Connected socket
	 *
	 * @var ?resource
	 */
	private ?resource $socket;

	/**
	 * Port number to connect
	 *
	 * @var int
	 */
	protected int $default_port = -1;

	/**
	 * Buffered data
	 *
	 * @var string
	 */
	protected string $buffer = '';

	/**
	 * @var string
	 */
	protected string $greeting = '';

	/**
	 * Connect to the socket
	 *
	 * @see Net_Client::connect()
	 */
	public function connect(): string {
		if ($this->hasOption('eol')) {
			$this->EOL = $this->option('eol', $this->EOL);
		}
		if ($this->is_connected()) {
			return $this->greeting;
		}

		$host = $this->url_parts ['host'] ?? 'localhost';
		$port = $this->url_parts ['port'] ?? $this->default_port;
		$timeout = $this->optionInt('timeout', 30);

		$errno = false;
		$errstr = false;

		$this->log("Connecting to $host:$port");
		$this->socket = @fsockopen($host, $port, $errno, $errstr, $timeout);
		if (!is_resource($this->socket)) {
			throw new Exception_Connect("$host:$port", "Could not connect to $host:$port $errstr");
		}
		stream_set_timeout($this->socket, $timeout, 0);
		stream_set_blocking($this->socket, $this->optionBool('blocking', true));
		$this->log('Connected.');
		$this->greeting = $this->read();
		return $this->greeting;
	}

	/**
	 * Disconnect
	 *
	 * @see Net_Client::disconnect()
	 */
	public function disconnect(): void {
		if ($this->socket) {
			fclose($this->socket);
			$this->socket = null;
		}
	}

	/**
	 *
	 * @see Net_Client::is_connected()
	 */
	public function is_connected() {
		return $this->socket !== null;
	}

	/**
	 * Make sure we're connected
	 *
	 * @throws Exception_Semantics
	 */
	protected function _check(): void {
		if (!$this->is_connected()) {
			throw new Exception_Semantics('Not connected to server');
		}
	}

	/**
	 * Execute a command
	 *
	 * @param string $command
	 *        	Command to run
	 * @param ?string $expect
	 *        	String to expect from the other side as a response
	 * @throws Exception_Protocol
	 * @return string
	 */
	protected function command(string $command, string $expect = null): string {
		$this->write(trim($command));

		if (!is_string($expect)) {
			return '';
		}
		$result = '';
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
	protected function expect(string $expect, string &$result): bool {
		$result = [];
		do {
			$line = $this->read();
			$result[] = $line;
		} while (substr($line, 3, 1) === '-');
		$result = implode("\n", $result);
		return str_starts_with(trim($result), $expect);
	}

	/**
	 * Write data to socket
	 *
	 * @param string $data
	 * @return number of bytes written
	 */
	public function write(string $data): int {
		$this->_check();
		$this->log($data, '> ');
		return $this->write_data($data . $this->EOL);
	}

	/**
	 * @param string $data
	 * @return int
	 * @throws Exception_Connect
	 */
	public function write_data(string $data): int {
		$result = fwrite($this->socket, $data, strlen($data));
		if ($result === false) {
			throw new Exception_Connect('Disconnected');
		}
		return $result;
	}

	/**
	 * @param $milliseconds
	 * @return string
	 * @throws Exception_Protocol
	 * @throws Exception_Semantics
	 * @throws Exception_Timeout
	 */
	public function read_wait(int $milliseconds = 600000): string {
		$timeout = microtime(true) + $milliseconds;
		do {
			$status = stream_get_meta_data($this->socket);
			$bytes = $status['unread_bytes'];
			if ($bytes === 0) {
				sleep(100);
			} else {
				return $this->read();
			}
		} while (microtime(true) < $timeout);

		throw new Exception_Timeout("read_wait timed out after $milliseconds milliseconds");
	}

	/**
	 * Read data from socket
	 *
	 * @return string
	 * @throws Exception_Protocol
	 * @throws Exception_Semantics
	 */
	public function read(): string {
		if (($pos = strpos($this->buffer, $this->EOL)) === false) {
			$this->buffer .= $this->_read($this->optionInt('read_buffer_size', 10240));
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

	public function read_data($length): string {
		if (strlen($this->buffer) > $length) {
			$data = substr($this->buffer, 0, $length);
			$this->buffer = substr($this->buffer, $length);
			return $data;
		}
		$read_length = $length - strlen($this->buffer);
		$data = $this->buffer;
		$this->buffer = '';
		return $data . $this->_read($read_length);
	}

	/**
	 * Internal read data (unbuffered)
	 *
	 * @param int $n_chars
	 * @return string
	 * @throws Exception_Protocol
	 * @throws Exception_Semantics
	 */
	protected function _read(int $n_chars = 1024): string {
		$this->_check();
		if ($this->optionBool('read_debug')) {
			echo "->read($n_chars) = ";
		}
		$result = fread($this->socket, $n_chars);
		if ($this->optionBool('read_debug')) {
			echo($result === false ? 'false' : strlen($result)) . " $result\n";
		}
		if (strlen($result) === 0) {
			throw new Exception_Protocol("fread returned empty: $result");
		}
		$this->log($result, '< ');
		return $result;
	}
}
