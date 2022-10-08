<?php
declare(strict_types=1);
/**
 *
 */

namespace zesk;

/**
 *
 * @author kent
 *
 */
class testdir {
	public static $dirs = [];

	public static $files = [];

	public static function collect_dirs($name): void {
		echo "DIR: $name\n";
		self::$dirs[] = $name;
	}

	public static function collect_files($name): void {
		echo "FILE: $name\n";
		self::$files[] = $name;
	}

	public static function dump() {
		return [
			self::$dirs,
			self::$files,
		];
	}
}

/**
 *
 * @author kent
 *
 */
class Directory_Test extends UnitTest {
	public function data_isAbsolute(): array {
		return [
			[true, '/',],
			[true, '/a',],
			[true, '/a/b/c',],
			[false, './'],
			[false, './place/to/go',],
			[false, '../place/to/go',],
			[\is_windows(), '\\windows\\',],
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
		$this->assert_false(is_dir($path));
		mkdir($path, 0o777);
		$this->assert_true(is_dir($path));
		for ($i = 0; $i < 100; $i++) {
			file_put_contents($path . "/$i.txt", str_repeat('.', $i));
		}
		Directory::delete($path);
		$this->assert_false(is_dir($path));
	}

	public function test_deleteContents(): void {
		$path = $this->sandbox('testdircontents');
		$this->assert_false(is_dir($path));
		mkdir($path, 0o777);
		$this->assert_true(is_dir($path));
		for ($i = 0; $i < 100; $i++) {
			file_put_contents($path . "/$i.txt", str_repeat('.', $i));
		}
		$this->assert_false(Directory::isEmpty($path));
		Directory::deleteContents($path);
		$this->assert_true(is_dir($path));
		$this->assert_true(Directory::isEmpty($path));
		Directory::delete($path);
	}

	public function undot_examples() {
		return [
			[
				'/path/to/foo',
				'/path/to/a/file/../../foo',
			],
			[
				'/path/to/..foo',
				'/path/to/a/file/../../..foo',
			],
			[
				null,
				'/path/to/a/file/../../../../../../foo',
			],
		];
	}

	/**
	 * @dataProvider undot_examples
	 */
	public function test_undot(?string $expect, string $name): void {
		if ($expect === null) {
			$this->expectException(Exception_Syntax::class);
			Directory::undot($name);
		} else {
			$this->assertEquals($expect, Directory::undot($name));
		}
	}

	public function test_list_recursive(): void {
		$path = $this->application->path();
		$options = [];
		Directory::list_recursive($path, $options);

		$options['rules_file'] = [
			'/.*_test\.php$/i' => true,
			false,
		];
		$options['rules_directory'] = [
			false,
		];
		$options['rules_directory_walk'] = [
			'/\.svn/' => false,
			true,
		];

		$results = Directory::list_recursive(ZESK_ROOT, $options);
		$this->assert_in_array($results, 'test/tests/Directory_Test.php');
	}

	public function strip_slash_data(): array {
		return [
			['foo///', 'foo'],
			['/a/way/to/go/to/the/place/', '/a/way/to/go/to/the/place'],
			['quality', 'quality'],
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
		$this->assertEquals($expected, Directory::strip_slash($string));
	}

	public function test_ls(): void {
		$path = $this->sandbox('testdir');
		$filter = '/.*/';
		$cat_path = false;
		$success = false;

		try {
			$this->assert(Directory::ls($path, $filter, $cat_path) === false);
		} catch (Exception_Directory_NotFound $e) {
			$success = true;
		}
		$this->assert($success);
		$this->assert(mkdir($path, 0o777) === true);
		$this->assert_arrays_equal(Directory::ls($path, $filter, $cat_path), []);
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
			__NAMESPACE__ . '\\testdir',
			'collect_dirs',
		], [
			__NAMESPACE__ . '\\testdir',
			'collect_files',
		]);
		$iterate_dump = ob_end_clean();

		[$dirs, $files] = testdir::dump();
		//		Debug::dump($files);

		$this->assert(in_array(ZESK_ROOT . 'autoload.php', $files));
		$this->assert(in_array(ZESK_ROOT . 'LICENSE.md', $files));
		$this->assert(!in_array(ZESK_ROOT . 'LICENSE.md', $dirs));

		$this->assert(in_array(ZESK_ROOT . 'classes', $dirs));
		$this->assert(!in_array(ZESK_ROOT . '.', $dirs));
		$this->assert(!in_array(ZESK_ROOT . '..', $dirs));
		$this->assert(!in_array(ZESK_ROOT . '.', $files));
		$this->assert(!in_array(ZESK_ROOT . '..', $files));
		$this->assert(in_array(__FILE__, $files));
	}

	public function test_isEmpty(): void {
		$path = $this->sandbox(__FUNCTION__);
		$this->assert(!is_dir($path));
		$this->assert(Directory::isEmpty($path) === true);
		mkdir($path, 0o777);
		$this->assert(Directory::isEmpty($path) === true);
		$filepath = $path . '/foo.txt';
		file_put_contents($filepath, 'hello?');
		$this->assert(Directory::isEmpty($path) === false);
		unlink($filepath);
		$this->assert(Directory::isEmpty($path) === true);
	}

	public function test_duplicate(): void {
		$rando = $this->randomHex(8);
		$source = ZESK_ROOT . 'cache/test-' . $rando;
		$destination = ZESK_ROOT . 'cache/test1-' . $rando;

		$this->assertFalse(is_dir($source));
		$this->assert(mkdir($source, 0o777));
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

	public function test_add_slash(): void {
		$p = null;
		Directory::add_slash($p);
	}
}
