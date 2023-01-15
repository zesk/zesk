<?php
declare(strict_types=1);
/**
 * @copyright &copy; 2017 Zesk Foundation
 * @author kent
 * @category Management
 */

namespace zesk;

use aws\classes\Hookable;

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
	private mixed $r = null;

	/**
	 * FP to fifo: Writer
	 *
	 * @var resource
	 */
	private mixed $w = null;

	/**
	 * Path to fifo
	 *
	 * @var string
	 */
	private string $path;

	/**
	 * Whether this object created the FIFO (and therefore should destroy it!)
	 *
	 * @var boolean
	 */
	private bool $created = false;

	/**
	 * Create the FIFO
	 *
	 * @param string $path Full path name
	 * @param bool $create Create the FIFO if it doesn't exist (assumes READER)
	 * @param int $mode File mode to create the FIFO (uses umask)
	 * @throws Exception_Directory_NotFound
	 * @throws Exception_File_Permission
	 */
	public function __construct(string $path, bool $create = false, int $mode = 384 /* 0o600 */) {
		$this->path = $path;
		if (!$create) {
			return;
		}
		$dir = dirname($this->path);
		if (!is_dir($dir)) {
			throw new Exception_Directory_NotFound($dir, 'Creating fifo {path}', [
				'path' => $this->path,
			]);
		}
		File::unlink($this->path);
		if (!posix_mkfifo($this->path, $mode)) {
			throw new Exception_File_Permission($this->path, 'posix_mkfifo {path}');
		}
		$this->created = true;
		$this->_beforeRead();
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
	public function path(): string {
		return $this->path;
	}

	/**
	 * Send a message to parent process
	 *
	 * @param mixed $message
	 * @return bool
	 * @throws Exception_File_NotFound
	 * @throws Exception_File_Permission
	 */
	public function write(mixed $message = null): bool {
		$this->_beforeWrite();
		$data = serialize($message);
		$n = strlen($data);
		fwrite($this->w, "$n\n$data");
		fflush($this->w);
		return true;
	}

	/**
	 * Read a message from client process
	 *
	 * @param float $timeout in seconds
	 * @return mixed
	 * @throws Exception_File_NotFound
	 * @throws Exception_Syntax
	 */
	public function read(float $timeout): mixed {
		$readers = [
			$this->r,
		];
		$writers = [];
		$sec = intval($timeout);
		$microseconds = intval(($timeout - $sec) * 1000000.0);
		if (stream_select($readers, $writers, $except, $sec, $microseconds)) {
			$n = intval(fgets($this->r));
			return PHP::unserialize(fread($this->r, $n));
		}

		throw new Exception_File_NotFound($this->path, 'FIFO closed');
	}

	/**
	 * Open write FIFO
	 *
	 * @throws Exception_File_NotFound
	 * @throws Exception_File_Permission
	 */
	private function _beforeWrite(): void {
		if (!file_exists($this->path)) {
			throw new Exception_File_NotFound($this->path, __METHOD__);
		}
		$this->w = File::open($this->path, 'wb');
	}

	/**
	 * Open read FIFO (used by parent process only)
	 *
	 * @throws Exception_File_Permission
	 */
	private function _beforeRead(): void {
		$this->r = fopen($this->path, 'r+b');
		if (!$this->r) {
			throw new Exception_File_Permission($this->path, 'fopen(\'{path}\', \'r\')');
		}
	}

	/**
	 * Close read FIFO
	 */
	private function _closeRead(): void {
		if ($this->r) {
			fclose($this->r);
			$this->r = null;
		}
	}

	/**
	 * Close write FIFO
	 */
	private function _closeWrite(): void {
		if ($this->w) {
			fclose($this->w);
			$this->w = null;
		}
	}

	/**
	 * Close all FIFOs
	 */
	public function close(): void {
		$this->_closeRead();
		$this->_closeWrite();
	}
}
