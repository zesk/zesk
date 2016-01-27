<?php
class test_file extends Test_Unit {

	private function _test_atomic_increment($path, $start) {
		$this->assert(file::atomic_put($path, "$start"), "Creating initial file");
		for($j = 0; $j < 100; $j++) {
			$this->assert(($result = file::atomic_increment($path)) === $start + $j + 1, "file::atomic_increment: $result !== " . ($start + $j + 1));
		}
		$this->assert(unlink($path), "Deleting $path at end");
	}

	function test_atomic_increment() {
		$path = $this->test_sandbox(__FUNCTION__);

		$this->assert(!file_exists($path) || unlink($path), "Deleting $path");
		$exception = false;
		try {
			file::atomic_increment($path);
		} catch (Exception $e) {
			$exception = true;
		}
		$this->assert($exception, "when file doesn't exist, an exception should occur");

		$this->_test_atomic_increment($path, 0);
		$this->_test_atomic_increment($path, 48123192);
	}

	function test_atomic_put() {
		$path = $this->test_sandbox("foo");
		$data = "hello";
		file::atomic_put($path, $data);
	}

	function test_base() {
		$filename = null;
		$lower = false;
		file::base($filename, $lower);
	}

	function test_checksum() {
		$path = null;
		file::checksum($path);
	}

	function test_chmod() {
		$file_name = null;
		$mode = 504;
		file::chmod($file_name, $mode);
	}

	function test_contents() {
		$filename = null;
		$default = null;
		file::contents($filename, $default);
	}

	function test_extension() {
		$filename = null;
		$default = false;
		$lower = true;
		file::extension($filename, $default, $lower);
	}

	function test_name_clean() {
		$x = null;
		$sep_char = "-";
		file::name_clean($x, $sep_char);
	}

	function test_path_check() {
		$x = null;
		file::path_check($x);
	}

	function test_temporary() {
		$ext = 'tmp';
		file::temporary($ext);
	}
}
