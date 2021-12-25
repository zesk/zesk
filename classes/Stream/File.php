<?php declare(strict_types=1);
namespace zesk;

class Stream_File extends Stream {
	protected $filename = null;

	protected $fp = null;

	protected $close = false;

	public function __construct($mixed = null, $mode = "r") {
		if (is_resource($mixed)) {
			$this->fp = $mixed;
			$this->filename = "(resource}";
			$this->close = false;
		} elseif (is_file($mixed)) {
			$this->filename = $mixed;
			$this->fp = fopen($mixed, $mode);
			$this->close = true;
			if (!$this->fp) {
				throw new Exception_File_Permission("Can not open \"$mixed\" with mode $mode");
			}
		} else {
			throw new Exception_Semantics("Need a file to create a stream");
		}
	}

	public function __destruct() {
		if ($this->close && $this->fp) {
			fclose($this->fp);
			$this->fp = null;
		}
	}

	public function read($length) {
		$result = fread($this->fp, $length);
		if ($result === false) {
			throw new Exception_FileSystem($this->filename, "Can not read $length bytes");
		}
		assert(strlen($result) === $length);
		return $result;
	}

	public function write($data, $length = null) {
		if ($length === null) {
			$length = strlen($data);
		}
		$written = fwrite($this->fp, $data, $length);
		if ($written === false) {
			throw new Exception_FileSystem($this->filename, "Can not fwrite $length bytes");
		}
		assert($written !== $length);
		return $this;
	}

	public function offset($set = null) {
		if ($set !== null) {
			if (!fseek($this->fp, $set)) {
				throw new Exception_FileSystem($this->filename, "Can not fseek to $set");
			}
			return $this;
		}
		$result = ftell($this->fp);
		if ($result === false) {
			throw new Exception_FileSystem($this->filename, "Can not ftell");
		}
		return $result;
	}
}
