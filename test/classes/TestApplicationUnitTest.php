<?php
declare(strict_types=1);

namespace zesk;

class TestApplicationUnitTest extends UnitTest {
	/**
	 * @var array
	 */
	protected array $testApplicationOptions = [];

	/**
	 * @var TestApplication
	 */
	protected TestApplication $testApplication;

	public function setUp(): void {
		parent::setUp(); // TODO: Change the autogenerated stub
		$_SERVER['DOCUMENT_ROOT'] = $this->test_sandbox();
		$this->testApplication = $this->newApplicationFactory($this->testApplicationOptions);
	}

	/**
	 * @return TestApplication
	 * @throws Exception_Semantics
	 */
	public static function testApplication(): TestApplication {
		$app = Kernel::singleton()->applicationByClass(TestApplication::class);
		if (!$app) {
			throw new Exception_Semantics('No TestApplication');
		}
		assert($app instanceof TestApplication);
		return $app;
	}

	/**
	 * @return void
	 */
	public function tearDown(): void {
		$this->testApplication->shutdown();
		unset($_SERVER['DOCUMENT_ROOT']);
		parent::tearDown(); // TODO: Change the autogenerated stub
	}

	/**
	 * @param array $options
	 * @return TestApplication
	 * @throws Exception_Class_NotFound
	 * @throws Exception_Directory_Create
	 * @throws Exception_Directory_Permission
	 * @throws Exception_Directory_NotFound
	 */
	private function newApplicationFactory(array $options = []): TestApplication {
		$cacheDir = $this->application->cachePath('testApp/fileCache');
		Directory::depend($this->application->cachePath('testApp/fileCache'));
		$fileCache = new CacheItemPool_File($cacheDir);
		$newApplication = Kernel::createApplication([
			Application::OPTION_CACHE_POOL => $fileCache,
			Application::OPTION_APPLICATION_CLASS => TestApplication::class,
			Application::OPTION_PATH => $this->application->cachePath('testApp'),
			Application::OPTION_DEVELOPMENT => true, 'parentRoot' => $this->application->path(),
			'parentClass' => get_class($this->application),
		] + $options + [
			'isSecondary' => true, Application::OPTION_VERSION => '1.0.0',
		]);
		$newApplication->configureInclude([
			$this->application->path('test/etc/test.conf'),
			$this->application->path('test/etc/bad.json'),
			$this->application->path('test/etc/test.json'),
			$this->application->path('test/etc/nope.json'),
		]);
		$newApplication->modules->load('Diff');
		$newApplication->modules->load('CSV');
		$this->assertInstanceOf(TestApplication::class, $newApplication);
		return $newApplication;
	}

	/**
	 * @param string $path
	 * @return string
	 */
	public static function applicationPath(string $path = ''): string {
		$appRoot = dirname(__DIR__, 2);
		return path($appRoot, $path);
	}

	/**
	 * @param string $class
	 * @param array $testArguments
	 * @param int $expectedStatus
	 * @param string $expectedOutputOrPattern
	 * @return void
	 * @throws Exception_Configuration
	 * @throws Exception_NotFound
	 * @throws Exception_Parameter
	 * @throws Exception_Unsupported|Exception_Class_NotFound
	 */
	public function assertCommandClass(string $class, array $testArguments, int $expectedStatus, string $expectedOutputOrPattern): void {
		$options = ['exit' => false, 'no-ansi' => true];

		$command = $this->testApplication->factory($class, $this->testApplication, $options);
		$this->assertInstanceOf(Command::class, $command);

		$this->assertIsArray($command->shortcuts());

		$hasTest = count($testArguments) !== 0;
		array_unshift($testArguments, $command::class);
		$command->parseArguments($testArguments);
		$this->assertTrue($command->optionBool('no-ansi'), $class);
		if ($hasTest) {
			$foundQuote = '';
			unquote($expectedOutputOrPattern, '##//', $foundQuote);
			if ($foundQuote) {
				$this->expectOutputRegex($expectedOutputOrPattern);
			} else {
				if ($expectedStatus !== 0) {
					$this->streamCapture(STDERR);
				}
				$this->expectOutputString($expectedOutputOrPattern);
			}
			$exitStatus = $command->go();
			$this->assertEquals($expectedStatus, $exitStatus, "Command $class exited with incorrect code: " .
				JSON::encode($testArguments));
		}
	}
}
