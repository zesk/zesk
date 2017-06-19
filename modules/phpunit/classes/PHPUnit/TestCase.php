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
	 * 
	 * {@inheritDoc}
	 * @see \PHPUnit\Framework\TestCase::setUp()
	 */
	function setUp() {
		/*
		 * Set up our state 
		 */
		if (!$this->application) {
			$this->application = app();
		}
		if (!$this->configuration) {
			$this->configuration = $this->application->configuration;
		}
		parent::setUp();
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