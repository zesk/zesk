<?php
declare(strict_types=1);

namespace zesk;

class File_Test extends Test_Unit {
	private function _test_atomic_increment(string $path, $start): void {
		$this->assert(File::atomic_put($path, "$start"), "Creating initial file");
		for ($j = 0; $j < 100; $j++) {
			$this->assert(($result = File::atomic_increment($path)) === $start + $j + 1, "File::atomic_increment: $result !== " . ($start + $j + 1));
		}
		$this->assert(unlink($path), "Deleting $path at end");
	}

	public function test_atomic_increment(): void {
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

	public function test_atomic_put(): void {
		$path = $this->test_sandbox("foo");
		$data = "hello";
		File::atomic_put($path, $data);
	}

	public function base_data(): array {
		return [
			["foo/bar/dee.inc", "dee"],
			["foo/bar/dee", "dee"],
			["C:/Users/Volumes/Places/foo.xlsx", "foo"],
		];
	}

	/**
	 * @param string $filename
	 * @param string $expected
	 * @return void
	 * @dataProvider base_data
	 */
	public function test_base(string $filename, string $expected): void {
		$this->assertEquals($expected, File::base($filename));
	}

	public function test_checksum(): void {
		$path = null;
		File::checksum($path);
	}

	public function test_chmod(): void {
		$file_name = $this->test_sandbox("chmod-test");
		file_put_contents($file_name, "abc");
		$mode = 504;
		$this->assertTrue(File::chmod($file_name, $mode));
		$this->assertFalse(File::chmod($file_name . ".notthere", $mode));
	}

	public function test_contents(): void {
		$file_name = $this->test_sandbox("chmod-test");
		$data = md5(microtime(false));
		file_put_contents($file_name, $data);
		$this->assertEquals($data, File::contents($file_name, ""));
		$this->assertEquals($data, File::contents($file_name . ".notthere") ?? $data);
		$this->assertEquals(null, File::contents($file_name . ".notthere"));
	}

	public function extension_data() {
		return [
			["foo.XLSX", "def", true, "xlsx"],
			["foo.xlsx", "def", true, "xlsx"],
			["foo.XLSX", "def", false, "XLSX"],
			["foo.xlsx", "def", false, "xlsx"],
			["/path/to/a/filename.XLSX", "def", true, "xlsx"],
			["/path/to/a/filename.xlsx", "def", true, "xlsx"],
			["/path/to/a/filename.XLSX", "def", false, "XLSX"],
			["/path/to/a/filename.xlsx", "def", false, "xlsx"],
		];
	}

	/**
	 * @param string $filename
	 * @param string $default
	 * @param bool $lower
	 * @param string $expected
	 * @return void
	 * @dataProvider extension_data
	 */
	public function test_extension(string $filename, string $default, bool $lower, string $expected): void {
		$this->assertEquals($expected, File::extension($filename, $default, $lower));
	}

	/**
	 * @return array
	 */
	public function name_clean_data(): array {
		return [
			["yer!@#\$the%^&*(()_+{}|dude.xml", "-", "yer-the-_-dude.xml"],
			["yer!@#\$the%^&*(()_+{}|dude.xml", "_", "yer_the_dude.xml"],
		];
	}

	/**
	 * @param string $path
	 * @param string $sep_char
	 * @param string $expected
	 * @return void
	 * @dataProvider name_clean_data
	 */
	public function test_name_clean(string $path, string $sep_char, string $expected): void {
		$this->assertEquals($expected, File::name_clean($path, $sep_char));
	}

	/**
	 * @return array[]
	 */
	public function path_check_data(): array {
		return [
			["foo/" . chr(194) . "bar/doo.xlsx", false],
			["foo/../bar/.././doo.xlsx", false],
			["normalish.xlsx", true],
			["a/very/normalish.xlsx", true],
		];
	}

	/**
	 * @param string $path_to_check
	 * @param bool $is_valid
	 * @return void
	 * @dataProvider path_check_data
	 */
	public function test_path_check(string $path_to_check, bool $is_valid): void {
		$this->assertEquals($is_valid, File::path_check($path_to_check));
	}

	/**
	 * @return void
	 * @throws Exception_Directory_Create
	 * @throws Exception_Directory_Permission
	 */
	public function test_temporary(): void {
		$ext = 'tmp';
		$filename = File::temporary($this->application->paths->temporary(), $ext);
		$this->assertFalse(file_exists($filename));
	}
}
