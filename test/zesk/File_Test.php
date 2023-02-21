<?php
declare(strict_types=1);

namespace zesk;

use zesk\Exception\DirectoryCreate;
use zesk\Exception\DirectoryPermission;
use zesk\Exception\FileNotFound;
use zesk\Exception\FilePermission;

class File_Test extends UnitTest {
	private function _test_atomic_increment(string $path, $start): void {
		$this->assertTrue(File::atomicPut($path, "$start"), 'Creating initial file');
		for ($j = 0; $j < 100; $j++) {
			$this->assertTrue(($result = File::atomicIncrement($path)) === $start + $j + 1, "File::atomic_increment: $result !== " . ($start + $j + 1));
		}
		$this->assertTrue(unlink($path), "Deleting $path at end");
	}

	/**
	 * @return array
	 */
	public static function data_absolute_path(): array {
		return [
			['/whatever', '/whatever', 'does not matter'],
			['/whatever', '/whatever', null],
			['/var/dude', 'dude', '/var'],
			['etc/hosts/dude/er/ino', 'dude/er/ino', 'etc/hosts'],
		];
	}

	/**
	 * @param $expected
	 * @param $path
	 * @param $cwd
	 * @return void
	 * @dataProvider data_absolute_path
	 */
	public function test_absolute_path($expected, $path, $cwd): void {
		$this->assertEquals($expected, File::absolutePath($path, $cwd));
	}

	public function test_atomic_increment(): void {
		$path = $this->test_sandbox(__FUNCTION__);

		$this->assertTrue(!file_exists($path) || unlink($path), "Deleting $path");
		$exception = false;

		try {
			File::atomicIncrement($path);
		} catch (Exception $e) {
			$exception = true;
		}
		$this->assertTrue($exception, 'when file doesn\'t exist, an exception should occur');

		$this->_test_atomic_increment($path, 0);
		$this->_test_atomic_increment($path, 48123192);
	}

	public function test_atomic_put(): void {
		$path = $this->test_sandbox('randomFile.txt');
		$data = 'hello';
		File::atomicPut($path, $data);
	}

	public static function data_base(): array {
		return [
			['foo/bar/dee.inc', 'dee'],
			['foo/bar/dee', 'dee'],
			['C:/Users/Volumes/Places/foo.xlsx', 'foo'],
		];
	}

	/**
	 * @param string $filename
	 * @param string $expected
	 * @return void
	 * @dataProvider data_base
	 */
	public function test_base(string $filename, string $expected): void {
		$this->assertEquals($expected, File::base($filename));
	}

	public static function data_checksum(): array {
		return [
			[
				md5(''),
				'',
			],
			[
				md5('123'),
				'123',
			],
		];
	}

	/**
	 * @param string $expected
	 * @param string $path
	 * @return void
	 * @dataProvider data_checksum
	 */
	public function test_checksum(string $expected, string $content): void {
		$path = $this->sandbox($this->randomHex(8) . '.checksum');
		file_put_contents($path, $content);
		$this->assertEquals($expected, File::checksum($path));
	}

	public function test_chmod(): void {
		$file_name = $this->sandbox('chmod-test');
		file_put_contents($file_name, 'abc');
		$mode = 504;
		File::chmod($file_name, $mode);

		$this->expectException(FileNotFound::class);
		File::chmod($file_name . '.notthere', $mode);
	}

	public function test_contents(): void {
		$file_name = $this->sandbox('chmod-test');
		$data = md5(microtime(false));
		file_put_contents($file_name, $data);
		$this->assertEquals($data, File::contents($file_name));
	}

	public function test_contents_no(): void {
		$file_name = $this->sandbox('notthere');
		$this->expectException(FileNotFound::class);
		File::contents($file_name);
	}

	public function test_contents_noperm(): void {
		$this->expectException(FilePermission::class);
		File::contents('/root/');
	}

	public static function data_extension() {
		return [
			['foo.xLSx', 'xLSx'],
			['foo.xlsx', 'xlsx'],
			['foo.', ''],
			['foo-bar-none', ''],
			['foo.XlsX', 'XlsX'],
			['foo.xlsx', 'xlsx'],
			['/path/to/a/filename.XLSX', 'XLSX'],
			['/path/to/a/../b/filename.xlsx', 'xlsx'],
			['/path/t.o/a/filename.XLSX', 'XLSX'],
			['/p.ath/to/a/filename.xlsx', 'xlsx'],
		];
	}

	/**
	 * @param string $filename
	 * @param string $default
	 * @param bool $lower
	 * @param string $expected
	 * @return void
	 * @dataProvider data_extension
	 */
	public function test_extension(string $filename, string $expected): void {
		$this->assertEquals($expected, File::extension($filename));
	}

	/**
	 * @return array
	 */
	public static function data_nameClean(): array {
		return [
			['yer!@#$the%^&*(()_+{}|dude.xml', '-', 'yer-the-_-dude.xml'],
			['yer!@#$the%^&*(()_+{}|dude.xml', '_', 'yer_the_dude.xml'],
		];
	}

	/**
	 * @param string $path
	 * @param string $sep_char
	 * @param string $expected
	 * @return void
	 * @dataProvider data_nameClean
	 */
	public function test_name_clean(string $path, string $sep_char, string $expected): void {
		$this->assertEquals($expected, File::nameClean($path, $sep_char));
	}

	/**
	 * @return array[]
	 */
	public static function data_pathCheck(): array {
		return [
			['foo/' . chr(194) . 'bar/doo.xlsx', false],
			['foo/../bar/.././doo.xlsx', false],
			['normalish.xlsx', true],
			['a/very/normalish.xlsx', true],
		];
	}

	/**
	 * @param string $path_to_check
	 * @param bool $is_valid
	 * @return void
	 * @dataProvider data_pathCheck
	 */
	public function test_path_check(string $path_to_check, bool $is_valid): void {
		$this->assertEquals($is_valid, File::pathCheck($path_to_check));
	}

	/**
	 * @return void
	 * @throws DirectoryCreate
	 * @throws DirectoryPermission
	 */
	public function test_temporary(): void {
		$ext = 'tmp';
		$filename = File::temporary($this->application->paths->temporary(), $ext);
		$this->assertFalse(file_exists($filename));
	}

	public static function data_stripExtension(): array {
		return [
			['C:/path/to/a/place/whatever.fii', 'C:/path/to/a/place/whatever.fii.sucka'],
			['//path/to/a/place/whatever.fii', '//path/to/a/place/whatever.fii.sucka'],
			['/path/to/a/place/whatever.fii', '/path/to/a/place/whatever.fii.sucka'],
			['path/to/a/place/whatever.fii', 'path/to/a/place/whatever.fii.sucka'],
			['./path/to/a/place/whatever.fii', './path/to/a/place/whatever.fii.sucka'],
			['./whatever.fii', './whatever.fii.sucka'],
			['whatever.fii', 'whatever.fii.sucka'],
			['whats......', 'whats.......goingon'],
			['things/in/front/of/it./with.dots/whats......', 'things/in/front/of/it./with.dots/whats.......goingon'],
		];
	}

	/**
	 * @param string $expected
	 * @param string $thingToStrip
	 * @return void
	 * @dataProvider data_stripExtension
	 */
	public function test_stripExtension(string $expected, string $thingToStrip): void {
		$this->assertEquals($expected, File::stripExtension($thingToStrip));
	}
}
