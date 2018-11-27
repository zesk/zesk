<?php
namespace zesk;

class File_Test extends Test_Unit {
	private function _test_atomic_increment($path, $start) {
		$this->assert(File::atomic_put($path, "$start"), "Creating initial file");
		for ($j = 0; $j < 100; $j++) {
			$this->assert(($result = File::atomic_increment($path)) === $start + $j + 1, "File::atomic_increment: $result !== " . ($start + $j + 1));
		}
		$this->assert(unlink($path), "Deleting $path at end");
	}

	public function test_atomic_increment() {
		$path = $this->test_sandbox(__FUNCTION__);

		$this->assert(!file_exists($path) || unlink($path), "Deleting $path");
		$exception = false;

		try {
			File::atomic_increment($path);
		} catch (Exception $e) {
			$exception = true;
		}
		$this->assert($exception, "when file doesn't exist, an exception should occur");

		$this->_test_atomic_increment($path, 0);
		$this->_test_atomic_increment($path, 48123192);
	}

	public function test_atomic_put() {
		$path = $this->test_sandbox("foo");
		$data = "hello";
		File::atomic_put($path, $data);
	}

	public function test_base() {
		$filename = null;
		$lower = false;
		File::base($filename, $lower);
	}

	public function test_checksum() {
		$path = null;
		File::checksum($path);
	}

	public function test_chmod() {
		$file_name = null;
		$mode = 504;
		File::chmod($file_name, $mode);
	}

	public function test_contents() {
		$filename = null;
		$default = null;
		File::contents($filename, $default);
	}

	public function test_extension() {
		$filename = null;
		$default = false;
		$lower = true;
		File::extension($filename, $default, $lower);
	}

	public function test_name_clean() {
		$x = null;
		$sep_char = "-";
		File::name_clean($x, $sep_char);
	}

	public function test_path_check() {
		$x = null;
		File::path_check($x);
	}

	public function test_temporary() {
		$ext = 'tmp';
		File::temporary($this->application->paths->temporary(), $ext);
	}
}
