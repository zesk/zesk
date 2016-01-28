<?php
/**
 * Process abstraction
 * $URL: https://code.marketacumen.com/zesk/trunk/classes/process.inc $
 * @package zesk
 * @subpackage system
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2012, Market Acumen, Inc.
 */

/**
 *
 * @author kent
 */
class Process extends Options {

	/**
	 * Default size of the buffer to read
	 * @var integer
	 */
	const read_buffer_size = 10240;

	/**
	 * Default number of seconds/microseconds to wait for read data
	 * @var unknown_type
	 */
	const read_timeout = 5;

	/**
	 * Three pipes stdin (0), stdout (1), stderr (2)
	 * @var array
	 */
	protected $pipes = array();

	/**
	 * Our process handle
	 * @var resource
	 */
	protected $proc = null;

	/**
	 * Process output status
	 * @var array
	 */
	protected $status = null;

	/**
	 * The read buffer size, in bytes
	 * @var integer
	 */
	public $read_buffer_size = self::read_buffer_size;

	/**
	 * The read timeout, in seconds
	 * @var float
	 */
	public $read_timeout = self::read_timeout;

	/**
	 * Create a piped process controlled by PHP
	 * @param string $command Command to run. Resolved using zesk::which
	 * @param array $arguments Array of arguments to pass to the command. No escaping is done internally.
	 * @param array $options Options to control behavior of this calss
	 * @throws Exception_File_NotFound
	 */
	function __construct($command, array $arguments = array(), array $options = array()) {
		parent::__construct($options);
		$spec = array(
			0 => array(
				"pipe",
				"r"
			),
			1 => array(
				"pipe",
				"w"
			),
			2 => array(
				"pipe",
				"w"
			)
		);
		$this->pipes = array();
		$this->command = zesk::which($command);
		$this->arguments = $arguments;
		$this->log("command: $this->command\n");
		$this->proc = proc_open($this->command . implode("", arr::prefix($arguments, " ")), $spec, $this->pipes);
		if (!is_resource($this->proc)) {
			throw new Exception_File_NotFound($this->command);
		}
		$this->status = proc_get_status($this->proc);
		stream_set_blocking($this->pipes[0], true); // stdin
		stream_set_blocking($this->pipes[1], false); // stdout
		stream_set_blocking($this->pipes[2], false); // stderr
	}

	/**
	 * Destroy this process
	 */
	function __destruct() {
		$this->terminate();
	}

	/**
	 * Internal logging utility
	 * @param string $message
	 */
	private function log($message) {
		if (avalue($this->options, 'debug')) {
			log::debug($message);
		}
	}

	/**
	 * Read data from the pipe
	 * @return string
	 */
	function read($n_bytes = null) {
		$fp_read = array(
			$this->pipes[1],
			$this->pipes[2]
		);
		$fp_write = $fp_except = array();
		$fp_except = array(
			$this->pipes[0],
			$this->pipes[1],
			$this->pipes[2]
		);
		$data = "";
		$sec = intval($this->read_timeout);
		$usec = ($this->read_timeout - $sec) * 1000000;
		if (stream_select($fp_read, $fp_write, $fp_except, $sec, $usec) > 0) {
			if (count($fp_except) > 0) {
				throw new Exception_System("select exceptions: " . count($fp_except));
			}
			foreach ($fp_read as $fp) {
				$data .= $this->_read($fp, $n_bytes);
				if (strlen($data) >= $n_bytes) {
					break;
				}
			}
			$this->log("<<< $data\n");
			return $data;
		}
		$this->log("stream_select failed\n");
		return $data;
	}

	/**
	 * Write data to the pipe
	 * @param string $command
	 * @throws Exception
	 * @return number
	 */
	function write($command) {
		$n_written = 0;
		$n_remain = strlen($command);
		while ($n_remain > 0) {
			$n_write = fwrite($this->pipes[0], $command);
			if ($n_write === false) {
				throw new Exception_System("Can not write " . strlen($command) . " byets to $this->command process");
			}
			fflush($this->pipes[0]);
			$n_remain -= $n_write;
			$n_written += $n_write;
		}
		$this->log(">>> $command");
		return $n_written;
	}

	/**
	 * Terminate the connection to the process
	 */
	function terminate() {
		if ($this->proc) {
			$this->status = proc_get_status($this->proc);
			$running = avalue($this->status, 'running');
			if ($running) {
				proc_terminate($this->proc);
				$this->status = proc_get_status($this->proc);
			}
			proc_close($this->proc);
			$this->proc = null;
		}
		foreach ($this->pipes as $index => $pipe) {
			if (is_resource($pipe)) {
				fclose($pipe);
				$this->pipes[$index] = null;
			}
		}
		$this->pipes = array();
	}

	/**
	 * Read from the file handle until EOF or $n_bytes read
	 * @param unknown_type $resource
	 * @return string
	 */
	private function _read($resource, $n_bytes = null) {
		$data = "";
		$n_bytes = $n_bytes === null ? $this->read_buffer_size : $n_bytes;
		while (!feof($resource)) {
			$buffer = fgets($resource, $n_bytes);
			if (empty($buffer)) {
				break;
			}
			$data .= $buffer;
			if (strlen($data) >= $n_bytes) {
				break;
			}
		}
		return $data;
	}
}
