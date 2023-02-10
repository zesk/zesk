<?php
declare(strict_types=1);
/**
 *
 */

namespace zesk;

use function is_windows;

/**
 *
 * @author kent
 *
 */
class testdir {
	public static array $dirs = [];

	public static array $files = [];

	public static function collect_dirs($name): void {
		echo "DIR: $name\n";
		self::$dirs[] = $name;
	}

	public static function collect_files($name): void {
		echo "FILE: $name\n";
		self::$files[] = $name;
	}

	public static function dump(): array {
		return [
			self::$dirs, self::$files,
		];
	}
}

/**
 *
 * @author kent
 *
 */
class Directory_Test extends UnitTest {
	public static function data_isAbsolute(): array {
		return [
			[true, '/', ], [true, '/a', ], [true, '/a/b/c', ], [false, './'], [false, './place/to/go', ],
			[false, '../place/to/go', ], [is_windows(), '\\windows\\', ],
		];
	}

	/**
	 * @param bool $expected
	 * @param string $path
	 * @return void
	 * @dataProvider data_isAbsolute
	 */
	public function test_isAbsolute(bool $expected, string $path): void {
		$this->assertEquals($expected, Directory::isAbsolute($path));
	}

	public function test_delete(): void {
		$path = $this->sandbox('testdir');
		$this->assertFalse(is_dir($path));
		mkdir($path, 511 /* 0o777 */);
		$this->assertTrue(is_dir($path));
		for ($i = 0; $i < 100; $i++) {
			file_put_contents($path . "/$i.txt", str_repeat('.', $i));
		}
		Directory::delete($path);
		$this->assertFalse(is_dir($path));
	}

	public function test_delete_not(): void {
		$path = $this->sandbox('testdir');
		$this->assertFalse(is_dir($path));
		$this->expectException(Exception_Directory_NotFound::class);
		Directory::delete($path);
	}

	public function test_deleteContents(): void {
		$path = $this->sandbox('testdircontents');
		$this->assertFalse(is_dir($path));
		mkdir($path, 511 /* 0o777 */);
		$this->assertTrue(is_dir($path));
		for ($i = 0; $i < 100; $i++) {
			file_put_contents($path . "/$i.txt", str_repeat('.', $i));
		}
		$this->assertFalse(Directory::isEmpty($path));
		Directory::deleteContents($path);
		$this->assertTrue(is_dir($path));
		$this->assertTrue(Directory::isEmpty($path));
		Directory::delete($path);
	}

	public function undot_examples() {
		return [
			[
				'/path/to/foo', '/path/to/a/file/../../foo',
			], [
				'/path/to/..foo', '/path/to/a/file/../../..foo',
			], [
				null, '/path/to/a/file/../../../../../../foo',
			],
		];
	}

	/**
	 * @dataProvider undot_examples
	 */
	public function test_undot(?string $expect, string $name): void {
		if ($expect === null) {
			$this->expectException(Exception_Syntax::class);
			Directory::removeDots($name);
		} else {
			$this->assertEquals($expect, Directory::removeDots($name));
		}
	}

	public function test_list_recursive(): void {
		$path = $this->application->path();
		$options = [];
		Directory::listRecursive($path, $options);

		$options['rules_file'] = [
			'/.*_test\.php$/i' => true, false,
		];
		$options['rules_directory'] = [
			false,
		];
		$options['rules_directory_walk'] = [
			'/\.svn/' => false, true,
		];

		$results = Directory::listRecursive(ZESK_ROOT, $options);
		$this->assertTrue(in_array('test/core/Directory_Test.php', $results));
	}

	public function strip_slash_data(): array {
		return [
			['foo///', 'foo'], ['/a/way/to/go/to/the/place/', '/a/way/to/go/to/the/place'], ['quality', 'quality'],
			['', ''],
		];
	}

	/**
	 * @param string $string
	 * @param string $expected
	 * @return void
	 * @dataProvider strip_slash_data
	 */
	public function test_strip_slash(string $string, string $expected): void {
		$this->assertEquals($expected, Directory::stripSlash($string));
	}

	public function test_ls(): void {
		$path = $this->sandbox('testdir');
		$filter = '/.*/';
		$cat_path = false;
		$success = false;

		try {
			$this->assertFalse(Directory::ls($path, $filter, $cat_path));
		} catch (Exception_Directory_NotFound $e) {
			$success = true;
		}
		$this->assertTrue($success);
		$this->assertTrue(mkdir($path, 0o777));
		$this->assertEquals(Directory::ls($path, $filter, $cat_path), []);
	}

	public function test_iterate(): void {
		$source = ZESK_ROOT;
		$directory_function = null;
		$file_function = null;
		Directory::iterate($source, $directory_function, $file_function);

		$source = ZESK_ROOT;
		$directory_function = null;
		$file_function = null;
		ob_start();
		Directory::iterate($source, [
			__NAMESPACE__ . '\\testdir', 'collect_dirs',
		], [
			__NAMESPACE__ . '\\testdir', 'collect_files',
		]);
		$iterate_dump = ob_end_clean();

		[$dirs, $files] = testdir::dump();
		//		Debug::dump($files);

		$this->assertTrue(in_array(ZESK_ROOT . 'autoload.php', $files));
		$this->assertTrue(in_array(ZESK_ROOT . 'LICENSE.md', $files));
		$this->assertFalse(in_array(ZESK_ROOT . 'LICENSE.md', $dirs));

		$this->assertTrue(in_array(ZESK_ROOT . 'zesk', $dirs));
		$this->assertFalse(in_array(ZESK_ROOT . '.', $dirs));
		$this->assertFalse(in_array(ZESK_ROOT . '..', $dirs));
		$this->assertFalse(in_array(ZESK_ROOT . '.', $files));
		$this->assertFalse(in_array(ZESK_ROOT . '..', $files));
		$this->assertTrue(in_array(__FILE__, $files));
	}

	public function test_isEmpty(): void {
		$path = $this->sandbox(__FUNCTION__);
		$this->assertTrue(!is_dir($path));
		$this->expectException(Exception_Directory_NotFound::class);
		$this->assertTrue(Directory::isEmpty($path));
	}

	public function test_isEmpty2(): void {
		$path = $this->sandbox(__FUNCTION__);
		$this->assertTrue(!is_dir($path));
		mkdir($path, 511 /* 0o777 */);

		$this->assertTrue(Directory::isEmpty($path));
		$filepath = $path . '/foo.txt';
		file_put_contents($filepath, 'hello?');
		$this->assertFalse(Directory::isEmpty($path));
		unlink($filepath);
		$this->assertTrue(Directory::isEmpty($path));
	}

	public function test_duplicate(): void {
		$rando = $this->randomHex(8);
		$source = ZESK_ROOT . 'cache/test-' . $rando;
		$destination = ZESK_ROOT . 'cache/test1-' . $rando;

		$this->assertFalse(is_dir($source));
		$this->assertTrue(mkdir($source, 511 /* 0o777 */));
		$nfiles = 8;
		for ($i = 0; $i < $nfiles; $i++) {
			$name = path($source, $this->randomHex(16) . '.txt');
			$content = $this->randomBytes(1024 * $this->randomInteger(4, 20));
			file_put_contents($name, $content);
		}
		$this->assertFalse(is_dir($destination));

		$recursive = true;
		$file_copy_function = null;
		Directory::duplicate($source, $destination, $recursive, $file_copy_function);

		$this->assertTrue(is_dir($destination));
		$this->assertFalse(Directory::isEmpty($destination));

		Directory::deleteContents($destination);
		$this->assertTrue(is_dir($destination));
		$this->assertTrue(Directory::isEmpty($destination));
		Directory::delete($destination);
		$this->assertFalse(is_dir($destination));
	}

	public function test_create(): void {
		$path = $this->sandbox('/testpath/');
		$mode = 504;
		Directory::create($path, $mode);
	}

	/**
	 * @return void
	 * @dataProvider data_add_slash
	 */
	public function test_add_slash(string $expected, string $test): void {
		$this->assertEquals($expected, Directory::addSlash($test));
	}

	public static function data_add_slash(): array {
		return [
			['foo/', 'foo'],
			['foo/', 'foo/'],
			['./whatever/not/', './whatever/not'],
			['./whatever/not//', './whatever/not//'],
		];
	}
}
