<?php
declare(strict_types=1);
/**
 * @copyright &copy; 2025, Market Acumen, Inc.
 */

namespace testZesk;

use zesk\Application;
use zesk\Directory;
use zesk\Exception\DirectoryCreate;
use zesk\Exception\DirectoryNotFound;
use zesk\Exception\DirectoryPermission;
use zesk\Exception\FilePermission;
use zesk\Exception\ParameterException;
use zesk\Exception\SyntaxException;
use zesk\PHPUnit\TestCase;
use zesk\StringTools;

/**
 * @coversDefaultClass \zesk\Directory
 * @uses Directory
 * @author kent
 *
 */
class DirectoryTest extends TestCase
{
	public static function data_path(): array
	{
		return [
			[
				'nothing',
				['nothing'],
			],
			[
				'a/b',
				[
					'a',
					'b',
				],
			],
			[
				'foo/.bar',
				[
					'foo',
					'.bar',
				],
			],
			[
				'foo/.bar/..dotted',
				[
					'foo',
					[[['.bar']]],
					'..dotted',
				],
			],
			[
				'a/b',
				[
					'a/',
					'b',
				],
			],
			[
				'a/b',
				[
					'a',
					'/b',
				],
			],
			[
				'a/b',
				[
					'a/',
					'/b',
				],
			],
			[
				'/a/b',
				[
					'/a/',
					'/b',
				],
			],
			[
				'/a/b/',
				[
					'/a/',
					'/b/',
				],
			],
			[
				'/a/b/',
				[
					'/a/',
					'/./',
					[
						'/./',
						'////',
						'/././',
					],
					'/b/',
				],
			],
			[
				'/what/monitor-services/control/ruler-reader',
				[
					'/what/monitor-services',
					[
						'control',
						'ruler-reader',
					],
				],
			],
		];
	}

	/**
	 * @return void
	 * @covers ::findAll
	 */
	public function test_find_directory(): void
	{
		$this->assertEquals(['/etc/apt'], Directory::findAll([
			'/',
			'/etc',
		], 'apt'));
	}

	/**
	 * @param string $result
	 * @param array $args
	 * @return void
	 * @dataProvider data_path
	 * @covers       ::path
	 */
	public function test_path(string $result, array $args): void
	{
		$this->assertEquals($result, call_user_func_array(Directory::path(...), $args));
		$this->assertEquals($result, (Directory::path($args)));
	}

	public static function data_isAbsolute(): array
	{
		return [
			[
				true,
				'/',
			],
			[
				true,
				'/a',
			],
			[
				true,
				'/a/b/c',
			],
			[
				false,
				'./',
			],
			[
				false,
				'./place/to/go',
			],
			[
				false,
				'../place/to/go',
			],
		];
	}

	/**
	 * @param bool $expected
	 * @param string $path
	 * @return void
	 * @dataProvider data_isAbsolute
	 * @covers       Directory::isAbsolute
	 */
	public function test_isAbsolute(bool $expected, string $path): void
	{
		$this->assertEquals($expected, Directory::isAbsolute($path));
	}

	/**
	 * @return void
	 * @throws DirectoryNotFound
	 * @throws DirectoryPermission
	 * @throws FilePermission
	 * @covers       Directory::delete
	 */
	public function test_delete(): void
	{
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

	/**
	 * @return void
	 * @throws DirectoryNotFound
	 * @throws DirectoryPermission
	 * @throws FilePermission
	 * @covers       Directory::delete
	 */
	public function test_delete_not(): void
	{
		$path = $this->sandbox('testdir');
		$this->assertFalse(is_dir($path));
		$this->expectException(DirectoryNotFound::class);
		Directory::delete($path);
	}

	/**
	 * @return void
	 * @throws DirectoryNotFound
	 * @throws DirectoryPermission
	 * @throws FilePermission
	 * @covers       Directory::deleteContents
	 */
	public function test_deleteContents(): void
	{
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

	public static function data_undot_examples(): array
	{
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
	 * @dataProvider data_undot_examples
	 * @covers       Directory::removeDots
	 */
	public function test_undot(?string $expect, string $name): void
	{
		if ($expect === null) {
			$this->expectException(SyntaxException::class);
			Directory::removeDots($name);
		} else {
			$this->assertEquals($expect, Directory::removeDots($name));
		}
	}

	/**
	 * @return void
	 * @covers Directory::listRecursive
	 */
	public function test_list_recursive(): void
	{
		$results = Directory::listRecursive($this->application->zeskHome(), [
			'/\/\..*/' => false,
			true,
		], [
			'/.*Test\.php$/' => true,
			false,
		], [false]);
		$fileStub = StringTools::removePrefix(__FILE__, $this->application->zeskHome());
		$this->assertInArray($fileStub, $results);
	}

	public static function data_stripSlash(): array
	{
		return [
			[
				'foo///',
				'foo',
			],
			[
				'/a/way/to/go/to/the/place/',
				'/a/way/to/go/to/the/place',
			],
			[
				'quality',
				'quality',
			],
			[
				'',
				'',
			],
		];
	}

	/**
	 * @param string $string
	 * @param string $expected
	 * @return void
	 * @dataProvider data_stripSlash
	 * @covers       Directory::stripSlash
	 */
	public function test_strip_slash(string $string, string $expected): void
	{
		$this->assertEquals($expected, Directory::stripSlash($string));
	}

	/**
	 * @return void
	 * @throws DirectoryNotFound
	 * @throws DirectoryPermission
	 * @covers Directory::ls
	 */
	public function test_ls(): void
	{
		$path = $this->sandbox('testdir');
		$filter = '/.*/';
		$success = false;

		try {
			$this->assertFalse(Directory::ls($path, $filter, false));
		} catch (DirectoryNotFound $e) {
			$success = true;
		}
		$this->assertTrue($success);
		$this->assertTrue(mkdir($path, 0o777));
		$this->assertEquals([], Directory::ls($path, $filter, false));
	}

	/**
	 * @throws DirectoryNotFound
	 * @covers Directory::iterate
	 */
	public function test_iterate(): void
	{
		$source = Application::zeskPath();
		$directory_function = null;
		$file_function = null;
		Directory::iterate($source, $directory_function, $file_function);

		$source = Application::zeskPath();
		ob_start();
		Directory::iterate($source, [
			DirectoryTestDumper::class,
			'collectDirectories',
		], [
			DirectoryTestDumper::class,
			'collectFiles',
		]);
		$iterate_dump = ob_get_clean();

		$root = Application::zeskPath();
		$this->assertStringContainsString($root . 'autoload.php', $iterate_dump);
		[
			$dirs,
			$files,
		] = DirectoryTestDumper::dump();
		//		Debug::dump($files);

		$this->assertInArray($root . 'autoload.php', $files);
		$this->assertInArray($root . 'LICENSE.md', $files);
		$this->assertNotInArray($root . 'LICENSE.md', $dirs);

		$this->assertInArray($root . 'src/Exception', $dirs);
		$this->assertNotInArray($root . '.', $dirs);
		$this->assertNotInArray($root . '..', $dirs);
		$this->assertNotInArray($root . '.', $files);
		$this->assertNotInArray($root . '..', $files);
		$this->assertInArray(__FILE__, $files);
	}

	/**
	 * @return void
	 * @throws DirectoryNotFound
	 * @throws DirectoryPermission
	 * @covers Directory::isEmpty
	 */
	public function test_isEmpty(): void
	{
		$path = $this->sandbox(__FUNCTION__);
		$this->assertTrue(!is_dir($path));
		$this->expectException(DirectoryNotFound::class);
		$this->assertTrue(Directory::isEmpty($path));
	}

	/**
	 * @return void
	 * @throws DirectoryNotFound
	 * @throws DirectoryPermission
	 * @covers Directory::isEmpty
	 */
	public function test_isEmpty2(): void
	{
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

	/**
	 * @return void
	 * @covers Directory::duplicate
	 * @covers Directory::deleteContents
	 * @covers Directory::isEmpty
	 * @throws DirectoryNotFound
	 * @throws DirectoryPermission
	 * @throws FilePermission
	 * @throws ParameterException
	 */
	public function test_duplicate(): void
	{
		$root = Application::zeskPath();
		$rando = $this->randomHex(8);
		$source = $root . 'cache/test-' . $rando;
		$destination = $root . 'cache/test1-' . $rando;

		$this->assertFalse(is_dir($source));
		$this->assertTrue(mkdir($source, 0o777 /* 0o777 */));
		$fileCount = 8;
		for ($i = 0; $i < $fileCount; $i++) {
			$name = Directory::path($source, $this->randomHex(16) . '.txt');
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

	/**
	 * @throws DirectoryCreate
	 * @covers Directory::create
	 */
	public function test_create(): void
	{
		$path = $this->sandbox('/testpath/');
		$mode = 504;
		Directory::create($path, $mode);
	}

	/**
	 * @param string $expected
	 * @param string $test
	 * @return void
	 * @dataProvider data_add_slash
	 * @covers       Directory::addSlash
	 */
	public function test_add_slash(string $expected, string $test): void
	{
		$this->assertEquals($expected, Directory::addSlash($test));
	}

	public static function data_add_slash(): array
	{
		return [
			[
				'foo/',
				'foo',
			],
			[
				'foo/',
				'foo/',
			],
			[
				'./whatever/not/',
				'./whatever/not',
			],
			[
				'./whatever/not//',
				'./whatever/not//',
			],
		];
	}
}
