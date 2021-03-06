<?php
namespace zesk;

use PHPUnit\Framework\TestCase;

class PHPUnit_TestCase extends TestCase {
	/**
	 *
	 * @var Application
	 */
	protected $application = null;

	/**
	 *
	 * @var array
	 */
	protected $load_modules = [];

	/**
	 *
	 * @var Configuration
	 */
	protected $configuration = null;

	/**
	 * Current object's configuration (better version than using Options superclass)
	 *
	 * @var Configuration
	 */
	protected $option = null;

	/**
	 * Ensures our zesk variables above are properly populated
	 */
	public function setUp() {

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
				"loaded",
				"name",
				"class",
				"object",
			], $result);
			$this->assertTrue($result['loaded'], "is not loaded");
		}
		if (!$this->configuration) {
			$this->configuration = $this->application->configuration;
		}
		if (!$this->option) {
			$this->option = Configuration::factory();
			foreach ($this->application->classes->hierarchy(get_class($this)) as $class) {
				$this->option = $this->option->merge($this->configuration->path($class), false); // traverses leaf to base. Leaf wins, do not overwrite.
			}
			$this->application->logger->debug("{class} options is {option}", array(
				"class" => get_class($this),
				"option" => $this->option->to_array(),
			));
		}
	}

	public function assertPreConditions() {
		$this->assertInstanceOf(Configuration::class, $this->configuration);
		$this->assertInstanceOf(Application::class, $this->application);
		file_put_contents($this->lastTestCaseFile(), JSON::encode_pretty(array(
			"class" => get_class($this),
			"hierarchy" => $this->application->classes->hierarchy(get_class($this)),
			"when" => date("Y-m-d H:i:s"),
			"debug" => $this->option->to_array(),
		)));
	}

	public function assertPostConditions() {
		File::unlink($this->lastTestCaseFile());
	}

	private function lastTestCaseFile() {
		return $this->application->path(".phpunit-testcase-last");
	}

	/**
	 *
	 * @param string $string
	 * @param unknown $message
	 * @return unknown
	 */
	public function assertStringIsURL($string, $message = null) {
		return $this->assertTrue(URL::valid($string), $message ?: "$string is not a URL");
	}

	/**
	 *
	 * @param array $keys List of keys
	 * @param \ArrayAccess $array
	 * @param string $message Optional message
	 */
	public function assertArrayHasKeys($keys, $array, $message = '') {
		$keys = to_list($keys);
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
	public function pathCatenator($path, array $suffixes) {
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
	public function assertDirectoriesExist(array $paths, $message = null) {
		if (!$message) {
			$message = "Path does not exist";
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
	public function assertDirectoriesNotExist(array $paths, $message = null) {
		if (!$message) {
			$message = "Path should not exist";
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
	public function assertIsInteger($expected, $message = null) {
		$this->assertTrue(is_integer($expected), $message ?? "Item expected to be an integer but is a " . type($expected));
	}

	/**
	 * PHPUnit "echo" and "print" are captured, so we use fprintf(STDERR) to output test debugging stuff
	 *
	 * Generally, you should use these during development and remove them before commiting your changes.
	 *
	 * @param string $contents
	 * @return void
	 */
	protected function debug($contents) {
		if (!is_string($contents)) {
			$contents = var_export($contents, true);
		}
		$contents = trim($contents, "\n");
		fprintf(STDERR, "\n$contents\n");
	}
}
