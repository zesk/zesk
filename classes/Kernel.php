<?php

/**
 * $URL: https://code.marketacumen.com/zesk/trunk/classes/zesk.php $
 * @package zesk
 * @subpackage kernel
 * @author kent
 * @copyright Copyright &copy; 2016, Market Acumen, Inc.
 */
namespace zesk;

use Psr\Cache\CacheItemPoolInterface;

/**
 * Stuff that should probably just be part of PHP, but isn't.
 */
require_once dirname(__FILE__) . "/functions.php";

/**
 * @todo self::reset is NOT production ready
 * @author kent
 *
 */
class Kernel {
	/**
	 * Throw an exception when a deprecated function is called.
	 * Useful during development only.
	 *
	 * @var string
	 */
	const deprecated_exception = "exception";

	/**
	 * Log all deprecated function calls.
	 * Useful for development or production environments.
	 *
	 * @var string
	 */
	const deprecated_log = "log";

	/**
	 * Terminate execution and output a backtrace when a deprecated function is called.
	 * Useful during development only.
	 *
	 * @var string
	 */
	const deprecated_backtrace = "backtrace";

	/**
	 * Do nothing when deprecated functions are called.
	 * Production only. Default setting.
	 *
	 * @var null
	 */
	const deprecated_ignore = null;

	/**
	 *
	 * @var \zesk\Kernel
	 */
	private static $zesk = null;

	/**
	 *
	 * @var string
	 */
	private $deprecated = null;
	/**
	 *
	 * @var array
	 */
	private $initialize_configuration = null;

	/**
	 * For storing profiling information
	 *
	 * @see self::profiler()
	 * @see self::profile_timer()
	 * @var \stdClass
	 */
	private $profiler = null;

	/**
	 *
	 * @var array
	 */
	public static $configuration_defaults = array(
		'zesk' => array(
			'paths' => array(),
			'timestamp' => array(),
			'date' => array(),
			'time' => array()
		)
	);
	public static $weight_specials = array(
		'zesk-first' => -1e300,
		'first' => -1e299,
		'last' => 1e299,
		'zesk-last' => 1e300
	);

	/**
	 *
	 * @var double
	 */
	public $initialization_time = null;

	/**
	 *
	 * @var CacheItemPoolInterface
	 */
	public $cache = null;
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
	 * @deprecated 2017-05
	 * @see self::console()
	 * @var boolean
	 */
	public $console = false;

	/**
	 *
	 * @var string
	 */
	public $newline = "\n";

	/**
	 *
	 * @var string
	 */
	protected $application_class = __NAMESPACE__ . "\\" . "Application";

	/**
	 *
	 * @var Application
	 */
	protected $application = null;

	/**
	 *
	 * @var boolean
	 */
	public $is_windows = false;

	/**
	 * Include related classes
	 */
	public static function includes() {
		$here = dirname(__FILE__);

		require_once $here . "/Process.php";
		require_once $here . "/Logger.php";

		require_once $here . "/Configuration.php";
		require_once $here . "/Options.php";
		require_once $here . "/Hookable.php";
		require_once $here . "/Hooks.php";
		require_once $here . "/Paths.php";
		require_once $here . "/Autoloader.php";
		require_once $here . "/Classes.php";
		require_once $here . "/Objects.php";

		require_once $here . "/Paths.php";
		require_once $here . "/Compatibility.php";
		require_once $here . "/CDN.php";
		require_once $here . "/PHP.php";

		require_once $here . "/CacheItemPool/NULL.php";
		require_once $here . "/CacheItem/NULL.php";
	}

	/**
	 *
	 * @return \zesk\Kernel
	 */
	static function zesk() {
		return self::$zesk;
	}

	/**
	 *
	 * @param array $configuration
	 * @return \zesk\Kernel
	 */
	public static function singleton(array $configuration = array()) {
		if (self::$zesk) {
			return self::$zesk;
		}

		global $zesk;

		self::$zesk = $zesk = new self($configuration);

		$zesk->bootstrap();

		return $zesk;
	}
	/**
	 *
	 * @param array $configuration
	 */
	function __construct(array $configuration = array()) {
		if (!defined('E_DEPRECATED')) {
			define('E_DEPRECATED', 0);
		}
		error_reporting(E_ALL | E_STRICT | E_DEPRECATED);

		$this->initialize_configuration = $configuration;

		/**
		 * Set default console
		 *
		 * @var boolean
		 */
		$this->console = PHP_SAPI === 'cli';
		/*
		 * Preferred newline character for line-based output
		 */
		$this->newline = $this->console ? "\n" : "<br />\n";
		/*
		 * Is this on Windows-based OS?
		 *
		 * @todo Is this true
		 */
		$this->is_windows = PATH_SEPARATOR === '\\';
		/*
		 * Zesk's start time in microseconds
		 */
		$this->initialization_time = isset($configuration['init']) ? $configuration['init'] : microtime(true);

		/*
		 * Create our hooks registry
		 */
		$this->hooks = new Hooks($this);

		$this->construct($configuration);
	}

	/**
	 * Reset entrie Zesk global state and start from scratch. 
	 * 
	 * @see Application::instance()->reset()
	 * @category DEVELOPMENT
	 * @deprecated 2017-08 Not sure if allowing this is really a good idea at all
	 */
	public function reset(array $configuration) {
		zesk()->deprecated();
		$this->objects->reset();
		$this->hooks->reset();
		$this->construct($configuration);
		$this->bootstrap();
	}
	/**
	 */
	private function construct(array $configuration) {
		Compatibility::install();

		if (isset($configuration['cache']) && $configuration['cache'] instanceof CacheItemPoolInterface) {
			$this->cache = $configuration['cache'];
		} else {
			$this->cache = new CacheItemPool_NULL();
		}

		/*
		 * Set up logger interface for central logging
		 */
		$this->logger = new Logger($this);

		/*
		 * Configuration of components in the system
		 */
		$this->configuration = Configuration::factory(self::$configuration_defaults)->merge(Configuration::factory($configuration));

		//$this->caches = new Caches();
		/*
		 * Add default nodes to zesk globals
		 */
		$this->configuration->zesk = array(
			"paths" => array()
		);

		/*
		 * Current process interface. Depends on ->hooks
		 */
		$this->process = new Process($this);

		/*
		 * Initialize system paths and set up default paths for interacting with the file system
		 */
		$this->paths = new Paths($this);

		/*
		 * Manage object creation, singletons, and object sharing
		 */
		$this->objects = new Objects($this);

		$this->application_class = $this->configuration->path_get(array(
			__CLASS__,
			"application_class"
		), $this->application_class);
	}
	/**
	 */
	public final function bootstrap() {
		$this->autoloader = new Autoloader($this);

		$this->classes = Classes::instance($this);

		$this->initialize();

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
		return $this->process->id();
	}

	/**
	 *
	 * @param string|null $set
	 */
	public function set_deprecated($set) {
		$this->deprecated = is_string($set) ? strtolower($set) : self::deprecated_ignore;
	}
	/**
	 * Enables a method to be tagged as "deprecated" To disabled deprecated function, call with
	 * boolean value "false"
	 *
	 * @param mixed $set
	 *        	Value indicating how to handle deprecated functions: "exception" throws an
	 *        	exception, "log"
	 *        	logs to php error log, "backtrace" to backtrace immediately
	 * @return mixed Current value
	 */
	public function deprecated($reason = null, array $arguments = array()) {
		if ($this->deprecated) {
			switch ($this->deprecated) {
				case self::deprecated_exception:
					throw new Exception_Deprecated("${reason} Deprecated: {calling_function}\n{backtrace}", array(
						"reason" => $reason,
						"calling_function" => calling_function(),
						"backtrace" => _backtrace(4)
					) + $arguments);
				case self::deprecated_log:
					$this->logger->error("${reason} Deprecated: {calling_function}\n{backtrace}", array(
						"reason" => $reason ? $reason : "DEPRECATED",
						"calling_function" => calling_function(),
						"backtrace" => _backtrace(4)
					) + $arguments);
					break;
				case self::deprecated_backtrace:
				default :
					backtrace();
					exit();
			}
		}
	}

	/**
	 * For cordoning off old, dead code
	 */
	public function obsolete() {
		$this->logger->alert("Obsolete function called {function}", array(
			'function' => calling_function(2)
		));
		if (Application::instance()->development()) {
			backtrace();
		}
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
			$this->deprecated = $configuration->deprecated;
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
			$assopt = to_list($configuration->assert);
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
		if ($this->configuration->path_exists("zesk\Logger::utc_time")) {
			$zesk->logger->utc_time = to_bool($this->configuration->path_get("zesk\Logger::utc_time"));
		}
	}

	/**
	 * Sort an array based on the weight array index
	 * Support special terms such as "first" and "last"
	 *
	 * use like:
	 *
	 * `usort` does not maintain index association:
	 *
	 * usort($this->links_sorted, array(zesk(), "sort_weight_array"));
	 *
	 * `uasort` DOES maintain index association:
	 *
	 * uasort($this->links_sorted, array(zesk(), "sort_weight_array"));
	 *
	 * @param array $a
	 * @param array $b
	 * @see usort
	 * @see uasort
	 * @return integer
	 */
	public function sort_weight_array(array $a, array $b) {
		// Get weight a, convert to double
		$aw = array_key_exists('weight', $a) ? $a['weight'] : 0;
		$aw = doubleval(array_key_exists("$aw", self::$weight_specials) ? self::$weight_specials[$aw] : $aw);

		// Get weight b, convert to double
		$bw = array_key_exists('weight', $b) ? $b['weight'] : 0;
		$bw = doubleval(array_key_exists("$bw", self::$weight_specials) ? self::$weight_specials[$bw] : $bw);

		// a < b -> -1
		// a > b -> 1
		// a === b -> 0
		return $aw < $bw ? -1 : ($aw > $bw ? 1 : 0);
	}

	/**
	 * Same as sort_weight_array but highest values are FIRST, not LAST.
	 *
	 * @param array $a
	 * @param array $b
	 * @return integer
	 */
	public function sort_weight_array_reverse(array $a, array $b) {
		return $this->sort_weight_array($b, $a);
	}
	/**
	 * Internal call to initialize profiler structure
	 */
	private function _profiler() {
		if ($this->profiler === null) {
			$this->profiler = new \stdClass();
			$this->profiler->calls = array();
			$this->profiler->times = array();
			zesk()->hooks->add("</body>", function () {
				echo "<pre>";
				asort($this->profiler->calls);
				asort($this->profiler->times);
				print_r($this->profiler);
				echo "</pre>";
			});
		}
		return $this->profiler;
	}

	/**
	 * Time a function call
	 *
	 * @param string $item
	 *        	Key
	 * @param double $seconds
	 *        	How long it took
	 */
	public function profile_timer($item, $seconds) {
		$profiler = $this->_profiler();
		if (array_key_exists($item, $this->profiler)) {
			$profiler->times[$item] = $profiler->times[$item] + $seconds;
		} else {
			$profiler->times[$item] = $seconds;
		}
	}

	/**
	 * Internal profiler to determine who is calling what function how often.
	 * Debugging only
	 *
	 * @param numeric $depth
	 */
	public function profiler($depth = 2) {
		$profiler = $this->_profiler();
		$fkey = calling_function($depth + 1, true);
		if (array_key_exists($fkey, $this->profiler->calls)) {
			$profiler->calls[$fkey]++;
		} else {
			$profiler->calls[$fkey] = 1;
		}
	}

	/**
	 * Getter/setter for console
	 *
	 * @param boolean $set
	 * @return boolean
	 */
	public function console($set = null) {
		if (is_bool($set)) {
			$this->console = $set;
			return $set;
		}
		return $this->console;
	}

	/**
	 * Getter/setter for application class
	 *
	 * @param string|null $set
	 * @return string|self
	 */
	public function application_class($set = null) {
		if ($set !== null) {
			if ($set === $this->application_class) {
				return $this;
			}
			if ($this->application !== null) {
				throw new Exception_Semantics("Changing application class to {class} when application already instantiated", array(
					"class" => $set
				));
			}
			$this->application_class = $set;
			return $this;
		}
		return $this->application_class;
	}

	/**
	 *
	 * @param array $options
	 * @throws Exception_Semantics
	 * @return Application
	 */
	public function create_application(array $options) {
		if ($this->application !== null) {
			throw new Exception_Semantics("{method} application of type {class} was already created", array(
				"method" => __METHOD__,
				"class" => get_class($this->application)
			));
		}
		return $this->application = $this->objects->factory($this, $options);
	}

	/**
	 *
	 * @return Application
	 */
	public function application() {
		if (!$this->application) {
			throw new Exception_Semantics("Application must be created with {class}::create_application", array(
				"class" => get_class($this)
			));
		}
		return $this->application;
	}
}

Kernel::includes();
