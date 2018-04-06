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
	function setUp() {

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
				"object"
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
				"option" => $this->option->to_array()
			));
		}
	}
	function assertPreConditions() {
		$this->assertInstanceOf(Configuration::class, $this->configuration);
		$this->assertInstanceOf(Application::class, $this->application);
		file_put_contents($this->lastTestCaseFile(), JSON::encode_pretty(array(
			"class" => get_class($this),
			"hierarchy" => $this->application->classes->hierarchy(get_class($this)),
			"when" => date("Y-m-d H:i:s"),
			"debug" => $this->option->to_array()
		)));
	}
	function assertPostConditions() {
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
	function assertStringIsURL($string, $message = null) {
		return $this->assertTrue(URL::valid($string), $message ?: "$string is not a URL");
	}

	/**
	 *
	 * @param array $keys List of keys
	 * @param \ArrayAccess $array
	 * @param string $message Optional message
	 */
	function assertArrayHasKeys($keys, $array, $message = '') {
		$keys = to_list($keys);
		foreach ($keys as $key) {
			$this->assertArrayHasKey($key, $array, "$key: $message");
		}
	}
}