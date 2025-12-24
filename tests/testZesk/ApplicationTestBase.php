<?php
declare(strict_types=1);
/**
 * @copyright &copy; 2025, Market Acumen, Inc.
 */

namespace testZesk;

use zesk\Application;
use zesk\CacheItemPool\FileCacheItemPool;
use zesk\Command;
use zesk\Directory;
use zesk\Exception\ClassNotFound;
use zesk\Exception\ConfigurationException;
use zesk\Exception\DirectoryCreate;
use zesk\Exception\DirectoryNotFound;
use zesk\Exception\DirectoryPermission;
use zesk\Exception\NotFoundException;
use zesk\Exception\ParameterException;
use zesk\Exception\SemanticsException;
use zesk\JSON;
use zesk\Kernel;
use zesk\PHPUnit\TestCase;
use zesk\StringTools;

class ApplicationTestBase extends TestCase
{
	/**
	 * @var array
	 */
	protected array $testApplicationOptions = [];

	/**
	 * @var TestApplication
	 */
	protected null|TestApplication $testApplication = null;

	/**
	 * @throws
	 */
	public function initialize(): void
	{
		$_SERVER['DOCUMENT_ROOT'] = $this->test_sandbox();
		$this->testApplication = $this->newApplicationFactory($this->testApplicationOptions);
	}

	//	/**
	//	 * @return TestApplication
	//	 * @throws SemanticsException
	//	 */
	//	public static function getTestApplication(): TestApplication
	//	{
	//		$app = ArrayTools::last(Kernel::singleton()->applicationByClass(TestApplication::class) ?? []);
	//		if (!$app) {
	//			throw new SemanticsException('No TestApplication available via singleton');
	//		}
	//		assert($app instanceof TestApplication);
	//		return $app;
	//	}

	/**
	 * @return void
	 */
	public function shutdown(): void
	{
		if ($this->testApplication) {
			$this->testApplication->shutdown();
			$this->testApplication = null;
		}
		unset($_SERVER['DOCUMENT_ROOT']);
	}

	public const FILE_CACHE_HOME_NAME = 'testAppFileCache';

	public const APPLICATION_HOME_NAME = 'testApp';

	public const APPLICATION_CACHE_HOME_NAME = 'testAppCache';

	/**
	 * @param array $options
	 * @return TestApplication
	 * @throws ClassNotFound
	 * @throws ConfigurationException
	 * @throws DirectoryCreate
	 * @throws DirectoryNotFound
	 * @throws DirectoryPermission
	 * @throws NotFoundException|SemanticsException
	 */
	private function newApplicationFactory(array $options = []): TestApplication
	{
		$cacheDir = $this->application->cachePath(self::FILE_CACHE_HOME_NAME);
		foreach ([
			self::FILE_CACHE_HOME_NAME,
			self::APPLICATION_HOME_NAME,
			self::APPLICATION_CACHE_HOME_NAME,
		] as $subdirectory) {
			Directory::depend($this->application->cachePath($subdirectory));
		}
		$fileCache = new FileCacheItemPool($cacheDir);
		$newApplication = Kernel::createApplication([
			Application::OPTION_CACHE_POOL => $fileCache,
			Application::OPTION_APPLICATION_CLASS => TestApplication::class,
			Application::OPTION_PATH => $this->application->cachePath(self::APPLICATION_HOME_NAME),
			Application::OPTION_CACHE_HOME => $this->application->cachePath(self::APPLICATION_CACHE_HOME_NAME),
			Application::OPTION_DEVELOPMENT => true,
			'parentRoot' => $this->application->path(),
			'parentClass' => get_class($this->application),
		] + $options + [
			'isSecondary' => true,
			Application::OPTION_VERSION => '1.0.0',
		]);
		$newApplication->configureInclude([
			$this->application->path('tests/etc/test.conf'),
			$this->application->path('tests/etc/bad.json'),
			$this->application->path('tests/etc/test.json'),
			$this->application->path('tests/etc/nope.json'),
		]);
		$newApplication->modules->load('Diff');
		$newApplication->modules->load('CSV');
		$this->assertInstanceOf(TestApplication::class, $newApplication);
		$this->assertTrue($newApplication->option('isSecondary'), '$newApplication->option(\'isSecondary\')');
		return $newApplication;
	}

	/**
	 * @param string $path
	 * @return string
	 */
	public static function applicationPath(string $path = ''): string
	{
		$appRoot = dirname(__DIR__, 2);
		return Directory::path($appRoot, $path);
	}

	/**
	 * @param string $class
	 * @param array $testArguments
	 * @param int $expectedStatus
	 * @param string $expectedOutputOrPattern
	 * @return void
	 * @throws ClassNotFound
	 * @throws ConfigurationException
	 * @throws NotFoundException
	 * @throws ParameterException
	 * @throws SemanticsException
	 */
	public function assertCommandClass(string $class, array $testArguments, int $expectedStatus, string $expectedOutputOrPattern): void
	{
		$options = [
			'exit' => false,
			'no-ansi' => true,
		];

		$command = $this->testApplication->factory($class, $this->testApplication, $options);
		$this->assertInstanceOf(Command::class, $command);

		$this->assertIsArray($command->shortcuts());

		$hasTest = count($testArguments) !== 0;
		array_unshift($testArguments, $command::class);
		$command->parseArguments($testArguments);
		$this->assertTrue($command->optionBool('no-ansi'), $class);
		if ($hasTest) {
			$foundQuote = '';
			StringTools::unquote($expectedOutputOrPattern, '##//', $foundQuote);
			$this->streamCapture(STDOUT);
			$this->streamCapture(STDERR);
			if ($foundQuote) {
				$this->expectOutputRegex($expectedOutputOrPattern);
			} else {
				$this->expectOutputString($expectedOutputOrPattern);
			}
			$exitStatus = $command->go();
			$this->assertEquals($expectedStatus, $exitStatus, "Command $class exited with incorrect code: " . JSON::encode($testArguments));
		}
	}
}
