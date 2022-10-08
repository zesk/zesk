<?php
declare(strict_types=1);

namespace zesk;

use PHPUnit\Framework\TestCase;

class PHPUnit_TestCase extends TestCase {
	/**
	 *
	 * @var Application
	 */
	protected ?Application $application = null;

	/**
	 *
	 * @var array
	 */
	protected array $load_modules = [];

	/**
	 *
	 * @var Configuration
	 */
	protected ?Configuration $configuration = null;

	/**
	 * Current object's configuration (better version than using Options superclass)
	 *
	 * @var Configuration
	 */
	protected ?Configuration $option = null;

	protected $cache_dir = '';

	/**
	 * Ensures our zesk variables above are properly populated
	 */
	public function setUp(): void {

		/*
		 * Set up our state
		 */
		if (!$this->application) {
			/* singleton ok */
			$this->application = Kernel::singleton()->application();
		}
		foreach ($this->load_modules as $module) {
			$result = $this->application->modules->load($module);
			$this->assertArrayHasKeys([
				'loaded',
				'name',
				'class',
				'object',
			], $result);
			$this->assertTrue($result['loaded'], 'is not loaded');
		}
		if (!$this->configuration) {
			$this->configuration = $this->application->configuration;
		}
		if (!$this->option) {
			$this->option = Configuration::factory();
			foreach ($this->application->classes->hierarchy(get_class($this)) as $class) {
				$this->option = $this->option->merge($this->configuration->path($class), false); // traverses leaf to base. Leaf wins, do not overwrite.
			}
			$this->application->logger->debug('{class} options is {option}', [
				'class' => get_class($this),
				'option' => $this->option->to_array(),
			]);
		}
	}

	public function tearDown(): void {
		parent::tearDown(); // TODO: Change the autogenerated stub
		$this->_test_sandbox_shutdown();
	}

	public function assertPreConditions(): void {
		$this->assertInstanceOf(Configuration::class, $this->configuration);
		$this->assertInstanceOf(Application::class, $this->application);
		file_put_contents($this->lastTestCaseFile(), JSON::encode_pretty([
			'class' => get_class($this),
			'hierarchy' => $this->application->classes->hierarchy(get_class($this)),
			'when' => date('Y-m-d H:i:s'),
			'debug' => $this->option->toArray(),
		]));
	}

	public function assertPostConditions(): void {
		try {
			File::unlink($this->lastTestCaseFile());
		} catch (Exception_File_Permission) {
		}
	}

	private function lastTestCaseFile(): string {
		return $this->application->path('.phpunit-testcase-last');
	}

	/**
	 *
	 * @param string $string
	 * @param string $message
	 * @return void
	 */
	public function assertStringIsURL(string $string, string $message = ''): void {
		$this->assertTrue(URL::valid($string), $message ?: "$string is not a URL");
	}

	/**
	 *
	 * @param array $keys List of keys
	 * @param \ArrayAccess $array
	 * @param string $message Optional message
	 */
	public function assertArrayHasKeys(string|array $keys, array $array, string $message = ''): void {
		$keys = toList($keys);
		foreach ($keys as $key) {
			$this->assertArrayHasKey($key, $array, "$key: $message");
		}
	}

	/**
	 * Generate a list of absolute paths
	 *
	 * @param string $path
	 * @param string[] $suffixes
	 * @return string[]
	 */
	public function pathCatenator(string $path, array $suffixes): array {
		$result = [];
		foreach ($suffixes as $suffix) {
			$result[] = path($path, $suffix);
		}
		return $result;
	}

	/**
	 * All of the passed in directories MUST exist to succeed
	 *
	 * @param array $paths
	 * @param unknown $message
	 */
	public function assertDirectoriesExist(array $paths, string $message = ''): void {
		if (!$message) {
			$message = 'Path does not exist';
		}
		foreach ($paths as $index => $path) {
			$this->assertDirectoryExists($path, "$index: $path $message");
		}
	}

	/**
	 * All of the passed in directories MUST exist to succeed
	 *
	 * @param array $paths
	 * @param unknown $message
	 */
	public function assertDirectoriesNotExist(array $paths, string $message = ''): void {
		if (!$message) {
			$message = 'Path should not exist';
		}
		foreach ($paths as $index => $path) {
			$this->assertDirectoryNotExists($path, "$index: $path $message");
		}
	}

	/**
	 * Assert that the expected value is an integer
	 *
	 * @param mixed $expected
	 * @param string|null $message
	 */
	public function assertIsInteger(mixed $expected, string $message = ''): void {
		$this->assertTrue(is_int($expected), $message ?: 'Item expected to be an integer but is a ' . type($expected));
	}

	/**
	 * PHPUnit "echo" and "print" are captured, so we use fprintf(STDERR) to output test debugging stuff
	 *
	 * Generally, you should use these during development and remove them before commiting your changes.
	 *
	 * @param string $contents
	 * @return void
	 */
	protected function debug(mixed $contents): void {
		if (!is_string($contents)) {
			$contents = var_export($contents, true);
		}
		$contents = trim($contents, "\n");
		fprintf(STDERR, "\n$contents\n");
	}

	/**
	 * @param int $count
	 * @return string
	 */
	protected function randomBytes(int $count = 64): string {
		try {
			return random_bytes($count);
		} catch (\Exception) {
		}

		try {
			$result = '';
			do {
				$result .= sha1(long2ip(random_int(0, 0xFFFFFFFF)), true);
			} while (strlen($result) < $count);
			return $result;
		} catch (\Exception) {
		}
		$result = '';
		do {
			$result .= sha1(microtime(false), true);
		} while (strlen($result) < $count);
		return $result;
	}

	/**
	 * @param int $min
	 * @param int $max
	 * @return int
	 */
	protected function randomInteger(int $min, int $max): int {
		try {
			return random_int($min, $max);
		} catch (\Exception) {
			return \random_int($min, $max);
		}
	}

	/**
	 * @param int $characters
	 * @return string
	 */
	protected function randomHex(int $characters = 32): string {
		$result = '';
		do {
			$result .= sha1($this->randomBytes());
		} while (strlen($result) < $characters);
		return substr($result, 0, $characters);
	}

	/**
	 * Create a sandbox folder to test with
	 *
	 * @param string $file
	 * @param bool $auto_delete
	 * @return string
	 * @see self::sandbox
	 */
	final protected function test_sandbox(string $file = '', bool $auto_delete = true): string {
		return $this->sandbox($file, $auto_delete);
	}

	/**
	 */
	final public function sandbox(string $file = '', bool $auto_delete = true): string {
		$cache_dir = $this->application->path('cache/test/' . $this->application->process->id());
		if (!is_dir($cache_dir)) {
			if (!mkdir($cache_dir, 0o777, true)) {
				$this->fail("test_sandbox: Can't create $cache_dir");
			}
			chmod($cache_dir, 0o770);
			if ($auto_delete) {
				$this->cache_dir = $cache_dir;
			}
		}
		return path($cache_dir, $file);
	}

	/**
	 * Delete cache dir after test runs
	 */
	final public function _test_sandbox_shutdown(): void {
		$cache_dir = $this->cache_dir;
		$this->cache_dir = '';
		if ($cache_dir === '') {
			return;
		}
		if (is_dir($cache_dir)) {
			Directory::delete($cache_dir);
		}
	}
}
