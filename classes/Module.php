<?php

/**
 * $URL: https://code.marketacumen.com/zesk/trunk/classes/Module.php $
 * @package zesk
 * @subpackage system
 * @author kent
 * @copyright Copyright &copy; 2016, Market Acumen, Inc.
 */
namespace zesk;

/**
 * /**
 * Module loading and management
 *
 * @see Modules
 * @author kent
 */
abstract class Module extends Hookable {
	/**
	 *
	 * @var zesk\Kernel
	 */
	protected $zesk = null;
	/**
	 *
	 * @var Application
	 */
	protected $application = null;
	
	/**
	 *
	 * @var Application
	 */
	protected $application_class = null;
	
	/**
	 * Module code name
	 *
	 * @var string
	 */
	protected $codename = null;
	
	/**
	 * Path to this module
	 *
	 * @var string
	 */
	protected $path = null;
	
	/**
	 * List of associated classes
	 *
	 * @var array
	 */
	protected $classes = array();
	
	/**
	 *
	 * @ignore
	 *
	 */
	function __sleep() {
		return array(
			"application_class",
			"codename",
			"path",
			"classes"
		);
	}
	function __wakeup() {
		$this->zesk = zesk();
		$this->application = app();
		$this->initialize();
	}
	
	/**
	 * Create Module
	 *
	 * @param string $options        	
	 */
	public final function __construct(Application $application, $options = null, array $module_data = array()) {
		parent::__construct($options);
		$this->zesk = $application->zesk;
		$this->application = $application;
		$this->application_class = get_class($application);
		$this->inherit_global_options();
		$this->path = avalue($module_data, 'path');
		if (!$this->codename) {
			$codename = str::unprefix(get_class($this), array(
				"Module_",
				__CLASS__ . "_"
			));
			// Code name used in JavaScript settings
			$this->codename = strtolower($codename);
		}
		$this->application->register_class($this->classes());
	}
	
	/**
	 * Override in subclasses - called upon load
	 */
	public function initialize() {
	}
	public final function codename() {
		return $this->codename;
	}
	/**
	 * Override in subclasses - called upon Application::classes
	 */
	public function classes() {
		return $this->classes;
	}
	
	/**
	 *
	 * @param string $class        	
	 * @param mixed $mixed        	
	 * @param array $options        	
	 * @return \zesk\Object
	 */
	final public function object_factory($class, $mixed = null, array $options = array()) {
		return $this->application->object_factory($class, $mixed, $options);
	}
}
