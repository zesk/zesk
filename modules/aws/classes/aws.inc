<?php

/**
 * Base class for inheriting options across all AWS classes
 * 
 * @author kent
 */
use zesk\Application;
use zesk\Options;

/**
 * 
 * @author kent
 *
 */
class AWS extends Options {
	/**
	 * 
	 * @var Application
	 */
	public $application = null;
	
	/**
	 * 
	 * @param Application $application
	 * @param array $options
	 */
	function __construct(Application $application, array $options = array()) {
		parent::__construct($options);
		$this->application = $application;
		$this->inherit_global_options($application);
	}
}
