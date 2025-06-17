<?php
/**
 * @package    zesk
 * @subpackage test
 * @author     kent
 * @copyright  &copy; 2023, Market Acumen, Inc.
 */
declare(strict_types=1);

namespace testZesk;

use zesk\Exception\NotFoundException;
use zesk\PHPUnit\TestCase;

use zesk\Application;
use zesk\ArrayTools;
use zesk\Configuration;
use zesk\Directory;
use zesk\File;
use zesk\Kernel;
use zesk\Options;
use zesk\Types;
use zesk\Version;

/**
 * @coversDefaultClass \zesk\Kernel
 */
class KernelTestCase extends TestCase {
	
	/**
	 * @var bool
	 */
	protected bool $configureApplication = true;
	
	/**
	 * @var int
	 */
	public int $order = 0;
	
	/**
	 * @var bool
	 */
	public bool $_hook_was_called = false;
	
	public static function _test_hook_order_1st(KernelTestCase $test): void
	{
		$test->assertEquals(0, $test->order);
		$test->order++;
	}
	
	public static function _test_hook_order_2nd(KernelTestCase $test): void
	{
		$test->assertEquals(1, $test->order);
		$test->order++;
	}
	
	public function add_hook_was_called($arg): void
	{
		$this->assertEquals(2, $arg);
		$this->assertInstanceOf(Application::class, $this->application);
		$this->application->setOption('hook_was_called', true);
	}
	
	public function hook_was_called(): bool
	{
		return $this->application->optionBool('hook_was_called');
	}
	
	public function _backtraceLines(int $n): int
	{
		$content = Kernel::backtrace($n);
		return count(explode("\n", $content));
	}
	
	/**
	 * @return void
	 * @covers ::backtrace
	 */
	public function test_backtrace(): void
	{
		$this->assertGreaterThan(8, $this->_backtraceLines(-1));
		$this->assertGreaterThan(9, $this->_backtraceLines(0));
		$this->assertEquals(1, $this->_backtraceLines(1));
		$this->assertEquals(2, $this->_backtraceLines(2));
	}
	
	public function test_find_directory(): void
	{
		$this->assertEquals(['/etc/apt'], Directory::findAll([
			'/',
			'/etc',
		], 'apt'));
	}
	
	public function test_find_file(): void
	{
		$this->assertEquals('/etc/group', File::findFirst([
			'/',
			'/etc',
		], 'group'));
	}
	
	public function test_get(): void
	{
		$configuration = new Configuration();
		$configuration->a = 'b';
		$result = $configuration->toArray();
		$this->assertEquals('b', $result['a']);
	}
	
	/**
	 *
	 */
	public static function data_has(): array
	{
		return [
			[
				true,
				Application::class,
			],
			[
				true,
				[
					Application::class,
					'deprecated',
				],
			],
			[
				true,
				Kernel::class,
			],
			[
				true,
				[
					Application::class,
					Application::OPTION_USER_HOME,
				],
			],
			[
				true,
				Options::class,
			],
			[
				false,
				md5('HOME'),
			],
			[
				false,
				'HOME',
			],
			[
				false,
				'HoMe',
			],
			[
				false,
				'0192830128301283123',
			],
		];
	}
	
	/**
	 * @param $expected
	 * @param $key
	 * @return void
	 * @dataProvider data_has
	 */
	public function test_has($expected, $key): void
	{
		$this->assertEquals($expected, $this->application->configuration->pathExists($key), implode('::', Types::toList($key)));
	}
	
	public function test_load_globals(): void
	{
		$paths = [
			$this->test_sandbox(),
		];
		$file = $this->test_sandbox('test.conf');
		$this->application->configuration->DUDE = 'smooth';
		file_put_contents($file, "TEST_VAR=\"\$DUDE move\"\nVAR_2=\"Ha ha! \${TEST_VAR} ex-lax\"\nVAR_3=\"\${DUDE:-default value}\"\nVAR_4=\"\${DOOD:-default value}\"");
		$overwrite = false;
		$this->application->configureFiles([$file]);
		
		$globals = [
			'TEST_VAR' => 'smooth move',
			'VAR_2'    => 'Ha ha! smooth move ex-lax',
			'VAR_3'    => 'smooth',
			'VAR_4'    => 'default value',
		];
		
		foreach ($globals as $v => $result) {
			$g = $this->application->configuration->$v;
			$this->assertEquals($g, $result);
		}
	}
	
	public function test_maintenance(): void
	{
		$this->application->maintenance();
	}
	
	public function test_set(): void
	{
		$this->assertEquals(null, $this->application->configuration->getPath('a::b::c'));
		$this->application->configuration->setPath('a::b::c', 9876);
		$this->assertEquals(9876, $this->application->configuration->getPath('a::b::c'));
	}
	
	public function test_site_root(): void
	{
		$this->application->documentRoot($this->test_sandbox());
	}
	
	public function test_version(): void
	{
		$version = Version::release();
		$this->assertIsString($version);
	}
	
	public function test_documentRoot(): void
	{
		$this->application->documentRoot();
	}
	
	public static function data_document_root_prefix(): array
	{
		return [
			[
				'/foobar',
				'/foobar',
			],
			[
				'foobar',
				'foobar',
			],
			[
				'',
				'',
			],
			[
				'antidis',
				'antidis/',
			],
			[
				'antidis',
				'antidis////',
			],
		];
	}
	
	/**
	 * @param $expected
	 * @param $set
	 * @return void
	 * @dataProvider data_document_root_prefix
	 */
	public function test_document_root_prefix($expected, $set): void
	{
		$this->application->setDocumentRootPrefix($set);
		$this->assertEquals($expected, $this->application->documentRootPrefix());
	}
	
	public function test_which(): void
	{
		$command = 'ls';
		$this->assertFileExists($this->application->paths->which($command));
	}
	
	public function test_which_fail(): void
	{
		$command = 'notacommandtofind';
		$this->expectException(NotFoundException::class);
		$this->application->paths->which($command);
	}
	
	/**
	 * @return void
	 * @covers ::singleton
	 */
	public function test_kernel_singleton(): void
	{
		$kernel = Kernel::singleton();
		$this->assertInstanceOf(Kernel::class, $kernel);
	}
	
	/**
	 * @return void
	 * @covers ::caller ::callingFunction
	 */
	public function test_calling(): void
	{
		$this->assertEquals(__FILE__ . ' ' . __CLASS__ . '->test_calling:' . __LINE__, Kernel::callingFunction(0));
		$this->assertEquals([
			'file'           => __FILE__,
			'type'           => '->',
			'function'       => __FUNCTION__,
			'args'           => [],
			'method'         => __CLASS__ . '->' . __FUNCTION__,
			'class'          => __CLASS__,
			'object'         => $this,
			'fileMethod'     => __FILE__ . ' ' . __CLASS__ . '->' . __FUNCTION__,
			'lineSuffix'     => ':' . (__LINE__ + 4),
			'methodLine'     => __CLASS__ . '->' . __FUNCTION__ . ':' . (__LINE__ + 3),
			'fileMethodLine' => __FILE__ . ' ' . __CLASS__ . '->' . __FUNCTION__ . ':' . (__LINE__ + 2),
			'line'           => __LINE__ + 1,
		], ArrayTools::filterKeys(Kernel::caller(0), null, [
			'callingLine',
			'callingFile',
		]));
		
		for ($i = 0; $i < 4; $i++) {
			$this->assertEquals(Kernel::callingFunction($i, true), Kernel::caller($i)['fileMethodLine']);
			$this->assertEquals(Kernel::callingFunction($i, false), Kernel::caller($i)['fileMethod']);
		}
	}
	
	/**
	 * @return void
	 * @covers \zesk\Kernel::callingFunction
	 */
	public function test_calling_neg(): void
	{
		$this->assertEquals($this->application->zeskHome('src/Kernel.php') . ' zesk\Kernel::callingFunction', Kernel::callingFunction(-1, false));
	}
}
