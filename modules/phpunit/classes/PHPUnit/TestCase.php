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
	 * @var Configuration
	 */
	protected $configuration = null;
	
	/**
	 * Ensures our zesk variables above are properly populated
	 */
	function assertPreConditions() {
		/*
		 * Set up our state
		 */
		if (!$this->application) {
			/* singleton ok */
			$this->application = Kernel::singleton()->application();
		}
		if (!$this->configuration) {
			$this->configuration = $this->application->configuration;
		}
		$this->assertInstanceOf(Configuration::class, $this->configuration);
		$this->assertInstanceOf(Application::class, $this->application);
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
}