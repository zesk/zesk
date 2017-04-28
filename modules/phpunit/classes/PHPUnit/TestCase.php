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
		parent::setUp();
	}
}