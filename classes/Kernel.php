<?php
declare(strict_types=1);

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

class Profiler {
	/**
	 * @var array
	 */
	public array $calls = [];

	/**
	 * @var array
	 */
	public array $times = [];

	/**
	 *
	 */
	public function __construct(Hooks $hooks) {
		$hooks->add("</body>", function (): void {
			echo $this->render();
		});
	}

	public function render(): string {
		$content = "<pre>";
		asort($this->calls);
		asort($this->times);
		$content .= print_r($this->calls, true);
		$content .= print_r($this->times, true);
		$content .= "</pre>";
		return $content;
	}
}

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
	public const DEPRECATED_EXCEPTION = "exception";

	/**
	 * Log all deprecated function calls.
	 * Useful for development or production environments.
	 *
	 * @var string
	 */
	public const DEPRECATED_LOG = "log";

	/**
	 * Terminate execution and output a backtrace when a deprecated function is called.
	 * Useful during development only.
	 *
	 * @var string
	 */
	public const DEPRECATED_BACKTRACE = "backtrace";

	/**
	 * Do nothing when deprecated functions are called.
	 * Production only. Default setting.
	 *
	 * @var null
	 */
	public const DEPRECATED_IGNORE = "ignore";

	/**
	 *
	 * @var \zesk\Kernel
	 */
	private static self $singleton;

	/**
	 *
	 * @var string
	 */
	private string $deprecated = self::DEPRECATED_IGNORE;

	/**
	 *
	 * @var array
	 */
	private array $initialize_configuration = [];

	/**
	 * For storing profiling information
	 *
	 * @see self::profiler()
	 * @see self::profile_timer()
	 * @var \stdClass
	 */
	private ?Profiler $profiler = null;

	/**
	 *
	 * @var array
	 */
	public static array $configuration_defaults = [__CLASS__ => ['application_class' => 'zesk\\Application', ], ];

	/**
	 *
	 * @var double
	 */
	public float $initialization_time;

	/**
	 *
	 * @var CacheItemPoolInterface
	 */
	public ?CacheItemPoolInterface $cache = null;

	/**
	 *
	 * @var Autoloader
	 */
	public Autoloader $autoloader;

	/**
	 *
	 * @var Process
	 */
	public Process $process;

	/**
	 *
	 * @var Hooks
	 */
	public Hooks $hooks;

	/**
	 *
	 * @var Paths
	 */
	public Paths $paths;

	/**
	 *
	 * @var Configuration
	 */
	public Configuration $configuration;

	/**
	 *
	 * @var Classes
	 */
	public Classes $classes;

	/**
	 *
	 * @var Objects
	 */
	public Objects $objects;

	/**
	 *
	 * @var Logger
	 */
	public Logger $logger;

	/**
	 *
	 * @see self::console()
	 * @var boolean
	 */
	private bool $console = false;

	/**
	 *
	 * @var string
	 */
	public string $newline = "\n";

	/**
	 *
	 * @var string
	 */
	protected string $application_class = "";

	/**
	 *
	 * @var ?Application
	 */
	protected ?Application $application = null;

	/**
	 * Include related classes
	 */
	public static function includes(): void {
		require_once __DIR__ . "/Exception.php";
		require_once __DIR__ . "/Process.php";
		require_once __DIR__ . "/Logger.php";

		require_once __DIR__ . "/Configuration.php";
		require_once __DIR__ . "/Options.php";
		require_once __DIR__ . "/Hookable.php";
		require_once __DIR__ . "/Hooks.php";
		require_once __DIR__ . "/HookGroup.php";
		require_once __DIR__ . "/Paths.php";
		require_once __DIR__ . "/Autoloader.php";
		require_once __DIR__ . "/Classes.php";
		require_once __DIR__ . "/Objects.php";

		require_once __DIR__ . "/Compatibility.php";
		require_once __DIR__ . "/PHP.php";

		require_once __DIR__ . "/CacheItem.php";
		require_once __DIR__ . "/CacheItemPool/Array.php";
	}

	/**
	 * Create a Kernel (once)
	 *
	 * @param array $configuration
	 * @return self
	 * @throws Exception
	 */
	public static function factory(array $configuration = []): self {
		$zesk = new self($configuration);
		assert(self::$singleton !== null);
		$zesk->bootstrap();
		return $zesk;
	}

	/**
	 * Fetch the kernel singleton. Avoid this call whenever possible.
	 *
	 * @param array $configuration parameter @deprecated 2017-10
	 * @return \zesk\Kernel
	 */
	public static function singleton(): self {
		if (!self::$singleton) {
			throw new Exception_Semantics("Need to create singleton with {class}::factory first", ["class" => __CLASS__, ]);
		}
		return self::$singleton;
	}

	/**
	 *
	 * @param array $configuration
	 */
	public function __construct(array $configuration = []) {
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
		$this->initialization_time = $configuration['init'] ?? microtime(true);

		/*
		 * Create our hooks registry
		 */
		$this->hooks = new Hooks($this);

		$this->construct($configuration);

		self::$singleton = $this;
	}

	/**
	 *
	 * @param array $configuration
	 */
	private function construct(array $configuration): void {
		if (isset($configuration['cache']) && $configuration['cache'] instanceof CacheItemPoolInterface) {
			$this->cache = $configuration['cache'];
		} else {
			$this->cache = new CacheItemPool_Array();
		}

		/*
		 * Set up logger interface for central logging
		 */
		$this->logger = new Logger();

		/*
		 * Configuration of components in the system
		 */
		$this->configuration = Configuration::factory(self::$configuration_defaults)->merge(Configuration::factory($configuration));

		$this->application_class = $this->configuration->path_get([__CLASS__, "application_class", ], __NAMESPACE__ . "\\" . "Application");

		/*
		 * Initialize system paths and set up default paths for interacting with the file system
		 */
		$this->paths = new Paths($this);

		/*
		 * Manage object creation, singletons, and object sharing
		 */
		$this->objects = new Objects($this);
	}

	/**
	 */
	final public function bootstrap(): void {
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
	public function initialize(): void {
		$this->hooks->add(Hooks::HOOK_CONFIGURED, [$this, "configured", ]);
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
	 *            Value indicating how to handle deprecated functions: "exception" throws an
	 *            exception, "log" logs to application error log, "backtrace" to output backtrace
	 *            and exit immediately
	 * @deprecated 2022-01
	 */
	public function set_deprecated($set) {
		$this->deprecated = is_string($set) ? strtolower($set) : self::DEPRECATED_IGNORE;
		$this->deprecated("use setDeprecated");
		return $this;
	}

	/**
	 * To disable deprecated function, call with boolean value "false"
	 *
	 * @param string $set
	 *            Value indicating how to handle deprecated functions: "exception" throws an
	 *            exception, "log" logs to application error log, "backtrace" to output backtrace
	 *            and exit immediately
	 */
	public function setDeprecated(string $set): self {
		$this->deprecated = [self::DEPRECATED_BACKTRACE => self::DEPRECATED_BACKTRACE, self::DEPRECATED_EXCEPTION => self::DEPRECATED_EXCEPTION, self::DEPRECATED_LOG => self::DEPRECATED_LOG][$set] ?? self::DEPRECATED_IGNORE;
		return $this;
	}

	/**
	 * Enables a method to be tagged as "deprecated"
	 *
	 * @param string $reason
	 * @return mixed Current value
	 * @throws Exception_Deprecated
	 */
	public function deprecated(string $reason = "", array $arguments = []): void {
		if ($this->deprecated === self::DEPRECATED_IGNORE) {
			return;
		}
		$depth = avalue($arguments, "depth", 0);
		switch ($this->deprecated) {
			case self::DEPRECATED_EXCEPTION:
				throw new Exception_Deprecated("${reason} Deprecated: {calling_function}\n{backtrace}", ["reason" => $reason, "calling_function" => calling_function(), "backtrace" => _backtrace(4 + $depth), ] + $arguments);
			case self::DEPRECATED_LOG:
				$this->logger->error("${reason} Deprecated: {calling_function}\n{backtrace}", ["reason" => $reason ? $reason : "DEPRECATED", "calling_function" => calling_function(), "backtrace" => _backtrace(4 + $depth), ] + $arguments);

				break;
		}
		backtrace();
		exit();
	}

	/**
	 * For cordoning off old, dead code
	 */
	public function obsolete(): void {
		$this->logger->alert("Obsolete function called {function}", ['function' => calling_function(2), ]);
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
	 *            File to include
	 * @return mixed Whatever is returned by the include file
	 */
	public function load(string $__file__): mixed {
		return include $__file__;
	}

	/**
	 * Load configuration
	 */
	final public function configured(): void {
		$configuration = $this->configuration->path(__CLASS__);
		if (isset($configuration->deprecated)) {
			$deprecated = $configuration->deprecated;
			$this->setDeprecated($deprecated);
			$this->logger->debug("Setting deprecated handling to {deprecated} => {actual}", ['deprecated' => $deprecated, 'actual' => $this->deprecated]);
		}
		if (isset($configuration->assert)) {
			$ass_settings = ['active' => ASSERT_ACTIVE, 'warning' => ASSERT_WARNING, 'bail' => ASSERT_BAIL, 'quiet' => ASSERT_QUIET_EVAL, ];
			foreach (array_values($ass_settings) as $what) {
				assert_options($what, 0);
			}
			$assopt = to_list($configuration->assert);
			foreach ($assopt as $code) {
				if (array_key_exists($code, $ass_settings)) {
					assert_options($ass_settings[$code], 1);
				} else {
					$this->logger->warning("Invalid assert option: {code}, valid options: {settings}", ["code" => $code, "settings" => array_keys($ass_settings), ]);
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
			$this->profiler = new Profiler($this->hooks);
		}
		return $this->profiler;
	}

	/**
	 * Time a function call
	 *
	 * @param string $item
	 *            Key
	 * @param float $seconds
	 *            How long it took
	 */
	public function profile_timer(string $item, float $seconds): void {
		$profiler = $this->_profiler();
		if (array_key_exists($item, $profiler->times)) {
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
	public function profiler($depth = 2): void {
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
			$this->deprecated("console -> setConsole");
			$this->setConsole(to_bool($set));
		}
		return $this->console;
	}

	/**
	 * Getter/setter for console
	 *
	 * @param boolean $set
	 * @return self
	 */
	public function setConsole(bool $set = false): self {
		$this->console = $set;
		return $this;
	}

	/**
	 * Getter/setter for application class
	 *
	 * @param string|null $set
	 * @return string
	 */
	public function application_class($set = null) {
		if ($set !== null) {
			$this->deprecated("setter");
			$this->setApplicationClass(strval($set));
		}
		return $this->applicationClass();
	}

	/**
	 * Getter for application class
	 *
	 * @return string
	 */
	public function applicationClass(): string {
		return $this->application_class;
	}

	/**
	 * Setter for application class
	 *
	 * @param string $set
	 * @return $this
	 * @throws Exception_Semantics
	 */
	public function setApplicationClass(string $set) {
		if ($set === $this->application_class) {
			return $this;
		}
		if ($this->application !== null) {
			throw new Exception_Semantics("Changing application class to {class} when application already instantiated", ["class" => $set, ]);
		}
		$this->application_class = $set;
		return $this;
	}

	public const HOOK_CREATE_APPLICATION = __CLASS__ . '::create_application';

	/**
	 *
	 * @param array $options
	 * @return Application
	 * @throws Exception_Semantics
	 */
	public function create_application(array $options = []) {
		if ($this->application !== null) {
			throw new Exception_Semantics("{method} application of type {class} was already created", ["method" => __METHOD__, "class" => get_class($this->application), ]);
		}
		$this->application = $this->objects->factory($this->application_class, $this, $options);
		$this->application->hooks->call(self::HOOK_CREATE_APPLICATION, $this->application);
		return $this->application;
	}

	/**
	 *
	 * @return ?Application
	 * @throws Exception_Semantics
	 */
	public function application(callable $callback = null): ?Application {
		if (!$this->application) {
			// TODO Test this
			if ($callback) {
				$this->hooks->add(self::HOOK_CREATE_APPLICATION, $callback);
				return null;
			} else {
				throw new Exception_Semantics("Application must be created with {class}::create_application", ["class" => get_class($this), ]);
			}
		} elseif (is_callable($callback)) {
			$callback($this->application);
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
	public function path(string $suffix = ""): string {
		return $this->paths->zesk($suffix);
	}

	/**
	 * Who owns the copyright on the Zesk Application Framework for PHP
	 *
	 * @return string
	 */
	public function copyright_holder(): string {
		return "Market Acumen, Inc.";
	}
}

Kernel::includes();
