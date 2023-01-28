<?php
declare(strict_types=1);
/**
 * @package zesk
 * @subpackage system
 * @author kent
 * @copyright Copyright &copy; 2023, Market Acumen, Inc.
 */

namespace zesk\Net;

use zesk\Exception_Semantics;
use zesk\Exception_Connect;
use zesk\Exception_Protocol;
use zesk\Exception_Timeout;

class SocketClient extends Client {
	/**
	 * Option to enable debugging of read calls.
	 *
	 * Option value is type boolean.
	 */
	public const  OPTION_DEBUG_READ = 'read_debug';

	/**
	 * Set the EOL marker for this connection.
	 *
	 * Option value is type string.
	 */
	public const OPTION_END_OF_LINE = 'eol';

	/**
	 * Default End of Line marker for this socket connection
	 */
	public const DEFAULT_OPTION_END_OF_LINE = "\r\n";

	/**
	 * Option to modify the read buffer size in bytes
	 */
	public const OPTION_READ_BUFFER_SIZE = 'read_buffer_size';

	/**
	 * Default bytes to read from remote
	 *
	 * @var int
	 */
	public const DEFAULT_OPTION_READ_BUFFER_SIZE = 10240;

	/**
	 * Whether this socket should behave as blocking or not.
	 *
	 * Value is boolean
	 */
	public const OPTION_SOCKET_BLOCKING = 'blocking';

	/**
	 * Default option value for blocking.
	 *
	 * @var bool
	 */
	public const DEFAULT_OPTION_SOCKET_BLOCKING = true;

	protected string $EOL = self::DEFAULT_OPTION_END_OF_LINE;

	/**
	 * Connected socket
	 *
	 * @var ?resource
	 */
	private mixed $socket;

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
	 * @return self
	 * @throws Exception_Connect
	 */
	public function connect(): self {
		$this->EOL = strval($this->option(self::OPTION_END_OF_LINE, $this->EOL));

		if ($this->isConnected()) {
			return $this;
		}

		$host = $this->url_parts ['host'] ?? 'localhost';
		$port = $this->url_parts ['port'] ?? $this->default_port;
		$timeout = $this->optionFloat('timeout', 30);
		$microseconds = (intval($timeout) - $timeout) * 1000000;
		$timeout = intval($timeout);

		$errorNumber = 0;
		$errorString = '';

		$this->log("Connecting to $host:$port");
		$this->socket = @fsockopen($host, $port, $errorNumber, $errorString, $timeout);
		if (!is_resource($this->socket)) {
			throw new Exception_Connect("$host:$port", "Could not connect to $host:$port $errorString");
		}
		stream_set_timeout($this->socket, $timeout, $microseconds);
		stream_set_blocking($this->socket, $this->optionBool(self::OPTION_SOCKET_BLOCKING, self::DEFAULT_OPTION_SOCKET_BLOCKING));
		$this->log('Connected.');
		return $this;
	}

	/**
	 * @return string
	 * @throws Exception_Semantics
	 * @throws Exception_Connect
	 * @throws Exception_Protocol
	 */
	public function connectGreeting(): string {
		$this->greeting = $this->connect()->read();
		return $this->greeting;
	}

	/**
	 * Disconnect
	 *
	 * @return void
	 */
	public function disconnect(): void {
		if ($this->socket) {
			fclose($this->socket);
			$this->socket = null;
		}
	}

	/**
	 *
	 * @see Net_Client::isConnected()
	 */
	public function isConnected(): bool {
		return $this->socket !== null;
	}

	/**
	 * Make sure we're connected
	 *
	 * @throws Exception_Connect
	 */
	protected function _check(): void {
		if (!$this->isConnected()) {
			throw new Exception_Connect('Not connected to server');
		}
	}

	/**
	 * Execute a command
	 *
	 * @param string $command Command to run
	 * @param string $expect String to expect from the other side as a response
	 * @return string
	 * @throws Exception_Protocol
	 * @throws Exception_Connect
	 */
	protected function command(string $command, string $expect = ''): string {
		$this->write(trim($command));
		if ($expect === '') {
			return '';
		}
		return $this->expect($expect, $command);
	}

	/**
	 * Expect some data back
	 *
	 * @param string $expect
	 *            String to match (beginning only)
	 * @param string $command
	 *            Debugging command (just logged)
	 * @return string
	 * @throws Exception_Connect
	 * @throws Exception_Protocol
	 */
	protected function expect(string $expect, string $command): string {
		$result = [];
		do {
			$line = $this->read();
			$result[] = $line;
		} while (substr($line, 3, 1) === '-');
		$result = implode("\n", $result);
		if (!str_starts_with(trim($result), $expect)) {
			throw new Exception_Protocol('{command} expected {expect}, received {result}', [
				'command' => $command, 'expect' => $expect, 'result' => $result,
			]);
		}
		return $result;
	}

	/**
	 * Write data to socket, appending EOL to end.
	 *
	 * @param string $data
	 * @return number of bytes written
	 * @throws Exception_Connect
	 */
	public function write(string $data): int {
		$this->_check();
		$this->log("> $data");
		return $this->writeData($data . $this->EOL);
	}

	/**
	 * @param string $data
	 * @return int
	 * @throws Exception_Connect
	 */
	public function writeData(string $data): int {
		$bytes = strlen($data);
		$result = fwrite($this->socket, $data, $bytes);
		if ($result !== $bytes) {
			throw new Exception_Connect('Disconnected');
		}
		return $result;
	}

	public const DEFAULT_READ_TIMEOUT_MILLISECONDS = 600000;

	/**
	 * @param int $milliseconds
	 * @return string
	 * @throws Exception_Protocol
	 * @throws Exception_Semantics
	 * @throws Exception_Timeout
	 */
	public function readWait(int $milliseconds = self::DEFAULT_READ_TIMEOUT_MILLISECONDS): string {
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
	 * @throws Exception_Connect
	 * @throws Exception_Protocol
	 */
	public function read(): string {
		$this->_check();
		if (($pos = strpos($this->buffer, $this->EOL)) === false) {
			$this->buffer .= $this->_read($this->optionInt(self::OPTION_READ_BUFFER_SIZE, self::DEFAULT_OPTION_READ_BUFFER_SIZE));
			$pos = strpos($this->buffer, $this->EOL);
			if ($pos === false) {
				throw new Exception_Protocol("Server returned a line not ending in EOL: $this->buffer");
			}
		}
		$eol_len = strlen($this->EOL);
		$line = substr($this->buffer, 0, $pos + $eol_len);
		$this->buffer = substr($this->buffer, $pos + $eol_len);
		return $line;
	}

	/**
	 * @param int $length
	 * @return string
	 * @throws Exception_Semantics
	 * @throws Exception_Protocol
	 */
	public function readData(int $length): string {
		$this->_check();
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
	 * @param int $characterCount
	 * @return string
	 * @throws Exception_Protocol
	 */
	protected function _read(int $characterCount): string {
		$result = fread($this->socket, $characterCount);
		if ($this->optionBool(self::OPTION_DEBUG_READ)) {
			$this->_log('->read({count}) = {result}', [
				'count' => $characterCount, 'result' => $result === false ? 'false' : strlen($result) . ' bytes',
			]);
		}
		if (strlen($result) === 0) {
			throw new Exception_Protocol("fread returned empty: $result");
		}
		$this->log("< $result");
		return $result;
	}
}
