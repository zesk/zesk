<?php
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
	static $dirs = array();
	static $files = array();
	public static function collect_dirs($name) {
		echo "DIR: $name\n";
		self::$dirs[] = $name;
	}
	public static function collect_files($name) {
		echo "FILE: $name\n";
		self::$files[] = $name;
	}
	public static function dump() {
		return array(
			self::$dirs,
			self::$files
		);
	}
}

/**
 *
 * @author kent
 *
 */
class Directory_Test extends Test_Unit {
	function test_is_absolute() {
		$f = null;
		$this->assert_false(Directory::is_absolute($f));
		$this->assert_true(Directory::is_absolute("/"));
		$this->assert_false(Directory::is_absolute("./"));
		$this->assert_false(Directory::is_absolute("./place/to/go"));
		$this->assert_false(Directory::is_absolute("../place/to/go"));
		if (zesk()->is_windows) {
			$this->assert_true(Directory::is_absolute("\\windows\\"));
		} else {
			$this->assert_false(Directory::is_absolute("\\windows\\"));
		}
	}
	function test_delete() {
		$path = $this->sandbox("testdir");
		$this->assert_false(is_dir($path));
		mkdir($path, 0777);
		$this->assert_true(is_dir($path));
		for ($i = 0; $i < 100; $i++) {
			file_put_contents($path . "/$i.txt", str_repeat(".", $i));
		}
		Directory::delete($path);
		$this->assert_false(is_dir($path));
	}
	function test_delete_contents() {
		$path = $this->sandbox("testdircontents");
		$this->assert_false(is_dir($path));
		mkdir($path, 0777);
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
	function undot_examples() {
		return array(
			array(
				"/path/to/a/file/../../foo",
				"/path/to/foo"
			),
			array(
				"/path/to/a/file/../../..foo",
				"/path/to/..foo"
			),
			array(
				"/path/to/a/file/../../../../../../foo",
				null
			)
		);
	}

	/**
	 * @data_provider undot_examples
	 */
	function test_undot($name, $expect) {
		$result = Directory::undot($name);
		$this->assert_equal($result, $expect);
	}
	function test_list_recursive() {
		$path = null;
		$options = false;
		Directory::list_recursive($path, $options);

		$options['file_include_pattern'] = '/.*_test\.inc$/';
		$options['file_exclude_pattern'] = false;
		$options['directory_include_pattern'] = false;
		$options['directory_walk_exclude_pattern'] = '/\.svn/';

		$results = Directory::list_recursive(ZESK_ROOT, $options);
		Debug::output($results);

		$this->assert(in_array("classes/test/dir_test.inc", $results));
	}
	function test_strip_slash() {
		$p = null;
		Directory::strip_slash($p);
	}
	function test_ls() {
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
		$this->assert(mkdir($path, 0777) === true);
		$this->assert_arrays_equal(Directory::ls($path, $filter, $cat_path), array());
	}
	function test_list_legacy() {
		$path = null;
		$recursive = false;
		$includePattern = true;
		$excludePattern = false;
		$addpath = true;
		$max_results = -1;
		Directory::list_legacy($path, $recursive, $includePattern, $excludePattern, $addpath, $max_results);
	}
	function test_iterate() {
		$source = ZESK_ROOT;
		$directory_function = null;
		$file_function = null;
		Directory::iterate($source, $directory_function, $file_function);

		$source = ZESK_ROOT;
		$directory_function = null;
		$file_function = null;
		ob_start();
		Directory::iterate($source, array(
			"testdir",
			"collect_dirs"
		), array(
			"testdir",
			"collect_files"
		));
		$iterate_dump = ob_end_clean();

		list($dirs, $files) = testDirectory::dump();
		//		Debug::dump($files);


		$this->assert(in_array(ZESK_ROOT . "zesk.inc", $files));
		$this->assert(in_array(ZESK_ROOT . "LICENSE.md", $files));
		$this->assert(!in_array(ZESK_ROOT . "LICENSE.md", $dirs));

		$this->assert(in_array(ZESK_ROOT . "classes", $dirs));
		$this->assert(!in_array(ZESK_ROOT . ".", $dirs));
		$this->assert(!in_array(ZESK_ROOT . "..", $dirs));
		$this->assert(!in_array(ZESK_ROOT . ".", $files));
		$this->assert(!in_array(ZESK_ROOT . "..", $files));
		$this->assert(in_array(__FILE__, $files));
	}
	function test_is_empty() {
		$path = $this->sandbox(__FUNCTION__);
		$this->assert(!is_dir($path));
		$this->assert(Directory::is_empty($path) === true);
		mkdir($path, 0777);
		$this->assert(Directory::is_empty($path) === true);
		$filepath = $path . "/foo.txt";
		file_put_contents($filepath, "hello?");
		$this->assert(Directory::is_empty($path) === false);
		unlink($filepath);
		$this->assert(Directory::is_empty($path) === true);
	}
	function test_duplicate() {
		$source = ZESK_ROOT . 'cache/test';
		$destination = ZESK_ROOT . 'cache/test1';

		$this->assert(Directory::delete($source));
		$this->assert(mkdir($source, 0777));

		$recursive = true;
		$file_copy_function = null;
		Directory::duplicate($source, $destination, $recursive, $file_copy_function);

		$this->assert(Directory::is_empty($destination));

		// TODO more tests here as necessary
	}
	function test_create() {
		$path = $this->sandbox('/testpath/');
		$mode = 504;
		Directory::create($path, $mode);
	}
	function test_add_slash() {
		$p = null;
		Directory::add_slash($p);
	}
}
