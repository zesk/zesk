<?php

/**
 * $URL: https://code.marketacumen.com/zesk/trunk/classes/zesk.inc $
 * @package zesk
 * @subpackage kernel
 * @author kent
 * @copyright Copyright &copy; 2016, Market Acumen, Inc.
 */
namespace zesk;

use \zesk as zesk;
use \log as log;

/**
 * Stuff that should probably just be part of PHP, but isn't.
 */
require_once dirname(__FILE__) . "/functions.inc";

/**
 *
 * @author kent
 *        
 */
class Kernel {
	static $configuration_defaults = array(
		'zesk' => array(
			'paths' => array(),
			'timestamp' => array(),
			'date' => array(),
			'time' => array()
		)
	);
	
	/**
	 * 
	 * @var double
	 */
	public $initialization_time = null;
	
	/**
	 *
	 * @var Autoloader
	 */
	public $autoloader = null;
	
	/**
	 * 
	 * @var Process
	 */
	public $process = null;
	
	/**
	 * @deprecated 2016-08
	 * @see ->hooks->
	 * @var Hooks
	 */
	public $hook = null;
	
	/**
	 *
	 * @var Hooks
	 */
	public $hooks = null;
	
	/**
	 *
	 * @var Paths
	 */
	public $paths = null;
	
	/**
	 *
	 * @var Configuration
	 */
	public $configuration = null;
	
	/**
	 *
	 * @var Classes
	 */
	public $classes = null;
	
	/**
	 *
	 * @var Objects
	 */
	public $objects = null;
	
	/**
	 *
	 * @var Logger
	 */
	public $logger = null;
	
	/**
	 *
	 * @var boolean
	 */
	public $maintenance = false;
	
	/**
	 *
	 * @var boolean
	 */
	public $console = false;
	
	/**
	 * @var string
	 */
	public $newline = "\n";
	
	/**
	 * @var string
	 */
	public $application_class = "Application";
	
	/**
	 *
	 * @var boolean
	 */
	public $is_windows = false;
	
	/**
	 * Include related classes
	 */
	private function _include() {
		require_once ZESK_ROOT . "classes/psr/log/loggerinterface.inc";
		require_once ZESK_ROOT . "classes/psr/log/loglevel.inc";
		
		$here = dirname(__FILE__);
		
		require_once $here . "/process.inc";
		require_once $here . "/logger.inc";
		
		require_once $here . "/configuration.inc";
		require_once $here . "/hooks.inc";
		require_once $here . "/paths.inc";
		require_once $here . "/autoloader.inc";
		require_once $here . "/classes.inc";
		require_once $here . "/objects.inc";
		
		require_once $here . "/paths.inc";
		require_once $here . "/compatibility.inc";
		require_once $here . "/cdn.inc";
	}
	/**
	 * 
	 * @param array $configuration
	 */
	function __construct(array $configuration = array()) {
		$this->_include();
		
		/*
		 * Read-only. Is this a shell or console invokation of Zesk? 
		 */
		$this->console = PHP_SAPI === 'cli';
		/*
		 * Preferred newline character for line-based output
		 */
		$this->newline = $this->console ? "\n" : "<br />\n";
		/*
		 * Is this on Windows-based OS?
		 */
		$this->is_windows = PATH_SEPARATOR === '\\';
		/*
		 * Zesk's start time in microseconds 
		 */
		$this->initialization_time = isset($configuration['init']) ? $configuration['init'] : microtime(true);
		
		/*
		 * Current process interface
		 */
		$this->process = new Process($this);
		
		/*
		 * Configuration of components in the system
		 */
		$this->configuration = Configuration::factory(self::$configuration_defaults)->merge(Configuration::factory($configuration));
		
		/*
		 * Add default nodes to zesk globals
		 */
		$this->configuration->zesk = array(
			"paths" => array()
		);
		
		/*
		 * Create our hooks registry
		 */
		$this->hooks = new Hooks($this);
		
		/*
		 * Initialize system paths and set up default paths for interacting with the file system
		 */
		$this->paths = new Paths($this);
		
		/*
		 * Set up logger interface for central logging
		 */
		$this->logger = new Logger($this);
		
		/*
		 * Manage object creation, singletons, and object sharing
		 */
		$this->objects = new Objects($this);
	}
	
	/**
	 * 
	 */
	public final function bootstrap() {
		$this->autoloader = new Autoloader($this);
		
		$this->classes = Classes::instance($this);
		
		$this->initialize();
		
		Compatibility::install();
		
		if (PHP_VERSION_ID < 50000) {
			die("Zesk works in PHP 5 only.");
		}
	}
	/**
	 * Add configurated hook
	 */
	function initialize() {
		$this->hooks->add(Hooks::hook_configured, array(
			$this,
			"configured"
		));
	}
	
	/**
	 * 
	 * @return number
	 */
	public function process_id() {
		return intval(getmypid());
	}
	
	/**
	 * This loads an include without any variables defined, except super globals Handy when the file
	 * is meant to return
	 * a value, or has its own "internal" variables which may corrupt the global or current scope of
	 * a function, for
	 * example.
	 *
	 * @param string $__file__
	 *        	File to include
	 * @return mixed Whatever is returned by the include file
	 */
	public function load($__file__) {
		return include $__file__;
	}
	
	/**
	 * Load configuration
	 */
	public final function configured() {
		global $zesk;
		/* @var $zesk zesk\Kernel */
		$configuration = $this->configuration->zesk;
		if (isset($configuration->deprecated)) {
			$deprecated = $configuration->deprecated;
			$zesk->logger->debug("Setting deprecated handling to {deprecated}", compact("deprecated"));
			zesk::deprecated($deprecated);
		}
		if (isset($configuration->assert)) {
			$ass_settings = array(
				'active' => ASSERT_ACTIVE,
				'warning' => ASSERT_WARNING,
				'bail' => ASSERT_BAIL,
				'quiet' => ASSERT_QUIET_EVAL
			);
			foreach (array_values($ass_settings) as $what) {
				assert_options($what, 0);
			}
			$assopt = to_list($configuration->zesk->assert);
			foreach ($assopt as $code) {
				if (array_key_exists($code, $ass_settings)) {
					assert_options($ass_settings[$code], 1);
				} else {
					$this->logger->warning("Invalid assert option: {code}, valid options: {settings}", array(
						"code" => $code,
						"settings" => array_keys($ass_settings)
					));
				}
			}
		}
		if ($configuration->assert_callback) {
			assert_options(ASSERT_CALLBACK, $configuration->assert_callback);
		}
		// 		foreach (get_declared_classes() as $class) {
		// 			$this->classes->register($class);
		// 		}
	}
}