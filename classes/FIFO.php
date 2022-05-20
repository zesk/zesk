<?php declare(strict_types=1);
/**
 * @copyright &copy; 2017 Zesk Foundation
 * @author kent
 * @category Management
 */
namespace zesk;

/**
 * FIFO is a simple mechanism to support inter-process communication
 *
 * This particular one allows the passing of arbitrary PHP data between processes, without a lot of extra baggage.
 *
 * You can also pass back "wakeup" messages which wake up the server.
 *
 * @author kent
 * @category Management
 */
class FIFO {
	/**
	 * FP to fifo: Reader
	 *
	 * @var resource
	 */
	private $r = null;

	/**
	 * FP to fifo: Writer
	 *
	 * @var resource
	 */
	private $w = null;

	/**
	 * Path to fifo
	 *
	 * @var string
	 */
	private $path;

	/**
	 * Whether this object created the FIFO (and therefore should destroy it!)
	 *
	 * @var boolean
	 */
	private $created = false;

	/**
	 * Create the FIFO
	 *
	 * @param string $path Full path name
	 * @param bool $create Create the FIFO if it doesn't exist (assumes READER)
	 * @param integer $mode File mode to create the FIFO (uses umask)
	 * @throws Exception_Directory_NotFound
	 * @throws Exception_File_Permission
	 */
	public function __construct($path, $create = false, $mode = 0o600) {
		$this->path = $path;
		if ($create) {
			$dir = dirname($this->path);
			if (!is_dir($dir)) {
				throw new Exception_Directory_NotFound($dir, 'Creating fifo {path}', [
					'path' => $this->path,
				]);
			}
			if (file_exists($this->path)) {
				if (!unlink($this->path)) {
					throw new Exception_File_Permission($this->path, 'unlink(\'{filename}\')');
				}
			}
			if (!posix_mkfifo($this->path, $mode)) {
				throw new Exception_File_Permission($this->path, 'posix_mkfifo {filename}');
			}
			$this->created = true;
			$this->_before_read();
		}
	}

	/**
	 * Delete the FIFO
	 *
	 * @see Hookable::__destruct()
	 */
	public function __destruct() {
		$this->close();
		if ($this->created && file_exists($this->path)) {
			unlink($this->path);
		}
	}

	/**
	 * FIFO path
	 *
	 * @return string
	 */
	public function path() {
		return $this->path;
	}

	/**
	 * Send a message to parent process
	 *
	 * @param mixed $message
	 * @return bool
	 * @throws Exception_File_Permission
	 */
	public function write($message = null) {
		if (!$this->_before_write()) {
			return false;
		}
		if ($message === null) {
			$n = 0;
			$data = '';
		} else {
			$data = serialize($message);
			$n = strlen($data);
		}
		fwrite($this->w, "$n\n$data");
		fflush($this->w);
		return true;
	}

	/**
	 * Read a message from client process
	 *
	 * @param integer $timeout
	 *        	in seconds
	 * @return mixed
	 */
	public function read($timeout) {
		$readers = [
			$this->r,
		];
		$writers = [];
		$sec = intval($timeout);
		$usec = ($timeout - $sec) * 1000000;
		if (@stream_select($readers, $writers, $except, $sec, $usec)) {
			$n = intval(fgets($this->r));
			if ($n === 0) {
				return [];
			}
			return unserialize(fread($this->r, $n));
		}
		return null;
	}

	/**
	 * Open write FIFO
	 *
	 * @throws Exception_File_Permission
	 */
	private function _before_write() {
		if (!file_exists($this->path)) {
			error_log(map('FIFO does not exist at {path}', [
				'path' => $this->path,
			]));
			return false;
		}
		$this->w = fopen($this->path, 'wb');
		if (!$this->w) {
			throw new Exception_File_Permission($this->path, 'fopen(\'{filename}\', \'w\')');
		}
		return true;
	}

	/**
	 * Open read FIFO (used by parent process only)
	 *
	 * @throws Exception_File_Permission
	 */
	private function _before_read(): void {
		$this->r = fopen($this->path, 'r+b');
		if (!$this->r) {
			throw new Exception_File_Permission($this->path, 'fopen(\'{filename}\', \'r\')');
		}
	}

	/**
	 * Close read FIFO
	 */
	private function _close_read(): void {
		if ($this->r) {
			fclose($this->r);
			$this->r = null;
		}
	}

	/**
	 * Close write FIFO
	 */
	private function _close_write(): void {
		if ($this->w) {
			fclose($this->w);
			$this->w = null;
		}
	}

	/**
	 * Close all FIFOs
	 */
	public function close(): void {
		$this->_close_read();
		$this->_close_write();
	}
}
