<?php declare(strict_types=1);
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
class Directory_Test extends Test_Unit {
	/**
	 * @expectedException zesk\Exception_Parameter
	 */
	public function test_is_absolute_param(): void {
		$f = null;
		$this->assert_false(Directory::is_absolute($f));
	}

	public function test_is_absolute(): void {
		$this->assert_true(Directory::is_absolute("/"));
		$this->assert_false(Directory::is_absolute("./"));
		$this->assert_false(Directory::is_absolute("./place/to/go"));
		$this->assert_false(Directory::is_absolute("../place/to/go"));
		if (\is_windows()) {
			$this->assert_true(Directory::is_absolute("\\windows\\"));
		} else {
			$this->assert_false(Directory::is_absolute("\\windows\\"));
		}
	}

	public function test_delete(): void {
		$path = $this->sandbox("testdir");
		$this->assert_false(is_dir($path));
		mkdir($path, 0o777);
		$this->assert_true(is_dir($path));
		for ($i = 0; $i < 100; $i++) {
			file_put_contents($path . "/$i.txt", str_repeat(".", $i));
		}
		Directory::delete($path);
		$this->assert_false(is_dir($path));
	}

	public function test_delete_contents(): void {
		$path = $this->sandbox("testdircontents");
		$this->assert_false(is_dir($path));
		mkdir($path, 0o777);
		$this->assert_true(is_dir($path));
		for ($i = 0; $i < 100; $i++) {
			file_put_contents($path . "/$i.txt", str_repeat(".", $i));
		}
		$this->assert_false(Directory::is_empty($path));
		Directory::delete_contents($path);
		$this->assert_true(is_dir($path));
		$this->assert_true(Directory::is_empty($path));
		Directory::delete($path);
	}

	public function undot_examples() {
		return [
			[
				"/path/to/a/file/../../foo",
				"/path/to/foo",
			],
			[
				"/path/to/a/file/../../..foo",
				"/path/to/..foo",
			],
			[
				"/path/to/a/file/../../../../../../foo",
				null,
			],
		];
	}

	/**
	 * @data_provider undot_examples
	 */
	public function test_undot($name, $expect): void {
		$result = Directory::undot($name);
		$this->assert_equal($result, $expect);
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
		$this->log($results);

		$this->assert_in_array($results, "test/tests/Directory_Test.php");
	}

	public function test_strip_slash(): void {
		$p = null;
		Directory::strip_slash($p);
	}

	public function test_ls(): void {
		$path = $this->sandbox("testdir");
		$filter = "/.*/";
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
			__NAMESPACE__ . "\\testdir",
			"collect_dirs",
		], [
			__NAMESPACE__ . "\\testdir",
			"collect_files",
		]);
		$iterate_dump = ob_end_clean();

		[$dirs, $files] = testdir::dump();
		//		Debug::dump($files);

		$this->assert(in_array(ZESK_ROOT . "autoload.php", $files));
		$this->assert(in_array(ZESK_ROOT . "LICENSE.md", $files));
		$this->assert(!in_array(ZESK_ROOT . "LICENSE.md", $dirs));

		$this->assert(in_array(ZESK_ROOT . "classes", $dirs));
		$this->assert(!in_array(ZESK_ROOT . ".", $dirs));
		$this->assert(!in_array(ZESK_ROOT . "..", $dirs));
		$this->assert(!in_array(ZESK_ROOT . ".", $files));
		$this->assert(!in_array(ZESK_ROOT . "..", $files));
		$this->assert(in_array(__FILE__, $files));
	}

	public function test_is_empty(): void {
		$path = $this->sandbox(__FUNCTION__);
		$this->assert(!is_dir($path));
		$this->assert(Directory::is_empty($path) === true);
		mkdir($path, 0o777);
		$this->assert(Directory::is_empty($path) === true);
		$filepath = $path . "/foo.txt";
		file_put_contents($filepath, "hello?");
		$this->assert(Directory::is_empty($path) === false);
		unlink($filepath);
		$this->assert(Directory::is_empty($path) === true);
	}

	public function test_duplicate(): void {
		$source = ZESK_ROOT . 'cache/test';
		$destination = ZESK_ROOT . 'cache/test1';

		$this->assert(Directory::delete($source));
		$this->assert(mkdir($source, 0o777));

		$recursive = true;
		$file_copy_function = null;
		Directory::duplicate($source, $destination, $recursive, $file_copy_function);

		$this->assert(Directory::is_empty($destination));

		// TODO more tests here as necessary
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
