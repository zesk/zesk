<?php

/**
 * Base class for inheriting options across all AWS classes
 *
 * @author kent
 */
use zesk\Application;

/**
 *
 * @author kent
 *
 */
class Hookable extends \zesk\Hookable {
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
		parent::__construct($application, $options);
		$this->inherit_global_options();
	}
}
