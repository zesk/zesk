<?php

/**
 *
 * @package zesk
 * @subpackage kernel
 * @author kent
 * @copyright Copyright &copy; 2017, Market Acumen, Inc.
 */
namespace zesk;

use Psr\Cache\CacheItemPoolInterface;

/**
 * Stuff that should probably just be part of PHP, but isn't.
 */
require_once __DIR__ . "/functions.php";

/**
 *
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
	const DEPRECATED_EXCEPTION = "exception";

	/**
	 * Log all deprecated function calls.
	 * Useful for development or production environments.
	 *
	 * @var string
	 */
	const DEPRECATED_LOG = "log";

	/**
	 * Terminate execution and output a backtrace when a deprecated function is called.
	 * Useful during development only.
	 *
	 * @var string
	 */
	const DEPRECATED_BACKTRACE = "backtrace";

	/**
	 * Do nothing when deprecated functions are called.
	 * Production only. Default setting.
	 *
	 * @var null
	 */
	const DEPRECATED_IGNORE = null;

	/**
	 *
	 * @var \zesk\Kernel
	 */
	private static $singleton = null;

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
		__CLASS__ => array(
			'application_class' => 'zesk\\Application'
		)
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
	 * @see self::console()
	 * @var boolean
	 */
	private $console = false;

	/**
	 *
	 * @var string
	 */
	public $newline = "\n";

	/**
	 *
	 * @var string
	 */
	protected $application_class = null;

	/**
	 *
	 * @var Application
	 */
	protected $application = null;

	/**
	 * Include related classes
	 */
	public static function includes() {
		$here = __DIR__;

		require_once $here . "/Exception.php";
		require_once $here . "/Process.php";
		require_once $here . "/Logger.php";

		require_once $here . "/Configuration.php";
		require_once $here . "/Options.php";
		require_once $here . "/Hookable.php";
		require_once $here . "/Hooks.php";
		require_once $here . "/HookGroup.php";
		require_once $here . "/Paths.php";
		require_once $here . "/Autoloader.php";
		require_once $here . "/Classes.php";
		require_once $here . "/Objects.php";

		require_once $here . "/Compatibility.php";
		require_once $here . "/PHP.php";

		require_once $here . "/CacheItem.php";
		require_once $here . "/CacheItemPool/Array.php";
	}

	/**
	 * Create a Kernel (once)
	 *
	 * @param array $configuration
	 * @throws Exception
	 * @return \zesk\Kernel
	 */
	public static function factory(array $configuration = array()) {
		if (self::$singleton) {
			throw new Exception("{method} should only be called once {backtrace}", array(
				"method" => __METHOD__,
				"backtrace" => _backtrace()
			));
		}
		global $zesk; // TODO @deprecated 2017-11
		self::$singleton = $zesk = new self($configuration);
		self::$singleton->bootstrap();
		return self::$singleton;
	}
	/**
	 * Fetch the kernel singleton. Avoid this call whenever possible.
	 *
	 * @param array $configuration parameter @deprecated 2017-10
	 * @return \zesk\Kernel
	 */
	public static function singleton() {
		if (!self::$singleton) {
			throw new Exception_Semantics("Need to create singleton with {class}::factory first", array(
				"class" => __CLASS__
			));
		}
		return self::$singleton;
	}
	/**
	 *
	 * @param array $configuration
	 */
	function __construct(array $configuration = array()) {
		error_reporting(E_ALL | E_STRICT);

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
	 *
	 * @param array $configuration
	 */
	private function construct(array $configuration) {
		if (isset($configuration['cache']) && $configuration['cache'] instanceof CacheItemPoolInterface) {
			$this->cache = $configuration['cache'];
		} else {
			$this->cache = new CacheItemPool_Array();
		}

		/*
		 * Set up logger interface for central logging
		 */
		$this->logger = new Logger($this);

		/*
		 * Configuration of components in the system
		 */
		$this->configuration = Configuration::factory(self::$configuration_defaults)->merge(Configuration::factory($configuration));

		$this->application_class = $this->configuration->path_get(array(
			__CLASS__,
			"application_class"
		), __NAMESPACE__ . "\\" . "Application");

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
		return $this->process->id();
	}

	/**
	 * To disable deprecated function, call with boolean value "false"
	 *
	 * @param string|null $set
	 *        	Value indicating how to handle deprecated functions: "exception" throws an
	 *        	exception, "log" logs to application error log, "backtrace" to output backtrace
	 *        	and exit immediately
	 */
	public function set_deprecated($set) {
		$this->deprecated = is_string($set) ? strtolower($set) : self::DEPRECATED_IGNORE;
		return $this;
	}
	/**
	 * Enables a method to be tagged as "deprecated"
	 *
	 * @param mixed $set
	 * @return mixed Current value
	 */
	public function deprecated($reason = null, array $arguments = array()) {
		if ($this->deprecated) {
			$depth = avalue($arguments, "depth", 0);
			switch ($this->deprecated) {
				case self::DEPRECATED_EXCEPTION:
					throw new Exception_Deprecated("${reason} Deprecated: {calling_function}\n{backtrace}", array(
						"reason" => $reason,
						"calling_function" => calling_function(),
						"backtrace" => _backtrace(4 + $depth)
					) + $arguments);
				case self::DEPRECATED_LOG:
					$this->logger->error("${reason} Deprecated: {calling_function}\n{backtrace}", array(
						"reason" => $reason ? $reason : "DEPRECATED",
						"calling_function" => calling_function(),
						"backtrace" => _backtrace(4 + $depth)
					) + $arguments);
					break;
				case self::DEPRECATED_BACKTRACE:
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
		if ($this->application()->development()) {
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
		$configuration = $this->configuration->path(__CLASS__);
		if (isset($configuration->deprecated)) {
			$deprecated = $configuration->deprecated;
			$this->logger->debug("Setting deprecated handling to {deprecated}", compact("deprecated"));
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
			$this->logger->utc_time = to_bool($this->configuration->path_get("zesk\Logger::utc_time"));
		}
	}

	/**
	 * Internal call to initialize profiler structure
	 */
	private function _profiler() {
		if ($this->profiler === null) {
			$this->profiler = new \stdClass();
			$this->profiler->calls = array();
			$this->profiler->times = array();
			$this->hooks->add("</body>", function () {
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
	public function create_application(array $options = array()) {
		if ($this->application !== null) {
			throw new Exception_Semantics("{method} application of type {class} was already created", array(
				"method" => __METHOD__,
				"class" => get_class($this->application)
			));
		}
		return $this->application = $this->objects->factory($this->application_class, $this, $options);
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

	/**
	 * Return zesk home directory
	 *
	 * Returns path to Zesk root
	 *
	 * @param string $suffix
	 * @return string
	 */
	public function path($suffix = null) {
		return $this->paths->zesk($suffix);
	}
	/**
	 * Who owns the copyright on the Zesk Application Framework for PHP
	 *
	 * @return string
	 */
	public function copyright_holder() {
		return "Market Acumen, Inc.";
	}
}

Kernel::includes();
