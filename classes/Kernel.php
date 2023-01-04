<?php
declare(strict_types=1);

/**
 *
 * @package zesk
 * @subpackage kernel
 * @author kent
 * @copyright Copyright &copy; 2022, Market Acumen, Inc.
 */

namespace zesk;

use Psr\Cache\CacheItemPoolInterface;

/**
 * Stuff that should probably just be part of PHP, but isn't.
 */
require_once(__DIR__ . '/functions.php');

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
		$hooks->add('</body>', function (): void {
			echo $this->render();
		});
	}

	public function render(): string {
		$content = '<pre>';
		asort($this->calls);
		asort($this->times);
		$content .= print_r($this->calls, true);
		$content .= print_r($this->times, true);
		$content .= '</pre>';
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
	public const DEPRECATED_EXCEPTION = 'exception';

	/**
	 * Log all deprecated function calls.
	 * Useful for development or production environments.
	 *
	 * @var string
	 */
	public const DEPRECATED_LOG = 'log';

	/**
	 * Terminate execution and output a backtrace when a deprecated function is called.
	 * Useful during development only.
	 *
	 * @var string
	 */
	public const DEPRECATED_BACKTRACE = 'backtrace';

	/**
	 * Do nothing when deprecated functions are called.
	 * Production only. Default setting.
	 *
	 * @var string
	 */
	public const DEPRECATED_IGNORE = 'ignore';

	/**
	 *
	 * @var ?Kernel
	 */
	private static ?self $singleton = null;

	/**
	 *
	 * @var string
	 */
	private string $deprecated = 'ignore';

	/**
	 * For storing profiling information
	 *
	 * @see self::profiler()
	 * @see self::profileTimer()
	 */
	private ?Profiler $profiler = null;

	/**
	 *
	 * @var array
	 */
	public static array $configurationDefaults = [
		__CLASS__ => [
			'applicationClass' => Application::class,
		],
	];

	/**
	 *
	 * @var double
	 */
	public float $initialization_time;

	/**
	 *
	 * @var ?CacheItemPoolInterface
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
	private bool $console;

	/**
	 *
	 * @var string
	 */
	public string $newline = "\n";

	/**
	 *
	 * @var string
	 */
	protected string $applicationClass = '';

	/**
	 *
	 * @var ?Application
	 */
	protected ?Application $application = null;

	/**
	 * Include related classes
	 */
	public static function includes(): void {
		/* Order here matters */
		foreach ([
			'Exceptional.php', 'Exception.php', 'Process.php', 'Logger.php',

			'Configuration.php', 'Options.php', 'Hookable.php', 'Hooks.php', 'HookGroup.php', 'Paths.php',
			'Autoloader.php', 'Classes.php', 'Objects.php',

			'Compatibility.php', 'PHP.php',

			'CacheItem.php', 'CacheItemPool/Array.php',
		] as $include) {
			require_once __DIR__ . "/$include";
		}
	}

	/**
	 * Create a Kernel (once)
	 *
	 * @param array $configuration
	 * @return self
	 * @throws Exception_Unsupported
	 * @throws Exception_Directory_NotFound
	 */
	public static function factory(array $configuration = []): self {
		$zesk = new self($configuration);
		assert(self::$singleton !== null);
		$zesk->bootstrap();
		return $zesk;
	}

	public static function terminate(): void {
		$kernel = self::$singleton;
		self::$singleton = null;
		$kernel->shutdown();
		unset($kernel);
	}

	/**
	 * Fetch the kernel singleton. Avoid this call whenever possible.
	 *
	 * @return static
	 * @throws Exception_Semantics
	 */
	public static function singleton(): self {
		if (!self::$singleton) {
			throw new Exception_Semantics('Need to create singleton with {class}::factory first', ['class' => __CLASS__, ]);
		}
		return self::$singleton;
	}

	/**
	 *
	 * @param array $configuration
	 */
	public function __construct(array $configuration = []) {
		error_reporting(E_ALL | E_STRICT);

		self::$singleton = $this;

		/**
		 * Set default console
		 */
		$this->console = PHP_SAPI === 'cli';
		/*
		 * Preferred newline character for line-based output
		 */
		$this->newline = $this->console ? "\n" : "<br />\n";

		/*
		 * Zesk start time in microseconds
		 */
		$this->initialization_time = $configuration['init'] ?? microtime(true);

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
		$this->configuration = Configuration::factory(self::$configurationDefaults)->merge(Configuration::factory($configuration));

		$this->applicationClass = $this->configuration->getFirstPath([
			[__CLASS__, 'applicationClass', ], [__CLASS__, 'application_class', ],
		], __NAMESPACE__ . '\\' . 'Application');

		/*
		 * Initialize system paths and set up default paths for interacting with the file system
		 */
		$this->paths = new Paths($this);

		/*
		 * Manage object creation, singletons, and object sharing
		 */
		$this->objects = new Objects();
	}

	/**
	 * @return void
	 * @throws Exception_Unsupported
	 * @throws Exception_Directory_NotFound
	 */
	final public function bootstrap(): void {
		Compatibility::check();

		$this->autoloader = new Autoloader($this);
		$this->classes = Classes::instance($this);

		$this->initialize();
	}

	final public function __destruct() {
		$this->shutdown();
	}

	/**
	 * @return void
	 */
	private function shutdown(): void {
		if ($this->application) {
			$this->logger->debug(__METHOD__);
			$this->hooks->shutdown();
			$this->application?->shutdown();
			$this->objects->shutdown();
			$this->classes->saveClassesToCache($this);
			$this->autoloader->shutdown();
			$this->paths->shutdown();
			$this->cache?->commit();
			$this->application = null;
		}
	}

	/**
	 * Add configured hook
	 */
	public function initialize(): void {
		$this->hooks->add(Hooks::HOOK_CONFIGURED, [$this, 'configured', ]);
	}

	/**
	 * To disable deprecated function, call with boolean value "false"
	 *
	 * @param string $set
	 *            Value indicating how to handle deprecated functions: "exception" throws an
	 *            exception, "log" logs to application error log, "backtrace" to output backtrace
	 *            and exit immediately
	 * @return string Previous value
	 */
	public function setDeprecated(string $set): string {
		$old = $this->deprecated;
		$this->deprecated = [
			self::DEPRECATED_BACKTRACE => self::DEPRECATED_BACKTRACE,
			self::DEPRECATED_EXCEPTION => self::DEPRECATED_EXCEPTION,
			self::DEPRECATED_LOG => self::DEPRECATED_LOG,
		][$set] ?? self::DEPRECATED_IGNORE;
		return $old;
	}

	/**
	 * Enables a method to be tagged as "deprecated"
	 *
	 * @param string $reason
	 * @param array $arguments
	 * @return void
	 * @throws Exception_Deprecated
	 */
	public function deprecated(string $reason = '', array $arguments = []): void {
		if ($this->deprecated === self::DEPRECATED_IGNORE) {
			return;
		}
		$depth = $arguments['depth'] ?? 0;
		switch ($this->deprecated) {
			case self::DEPRECATED_EXCEPTION:
				throw new Exception_Deprecated("{reason} Deprecated: {calling_function}\n{backtrace}", [
					'reason' => $reason, 'calling_function' => calling_function(),
					'backtrace' => _backtrace(4 + $depth),
				] + $arguments);
			case self::DEPRECATED_LOG:
				$this->logger->error("{reason} Deprecated: {calling_function}\n{backtrace}", [
					'reason' => $reason ?: 'DEPRECATED', 'calling_function' => calling_function(),
					'backtrace' => _backtrace(4 + $depth),
				] + $arguments);
				break;
		}
	}

	/**
	 * For cordoning off old, dead code
	 * @codeCoverageIgnore
	 */
	public function obsolete(): void {
		$this->logger->alert('Obsolete function called {function}', ['function' => calling_function(2), ]);
		if ($this->application?->development()) {
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
	 * @throws Exception_Configuration
	 */
	final public function configured(): void {
		$configuration = $this->configuration->path(__CLASS__);
		if (isset($configuration->deprecated)) {
			$deprecated = $configuration->deprecated;
			$this->setDeprecated(strval($deprecated));
			$this->logger->debug('Setting deprecated handling to {deprecated} => {actual}', [
				'deprecated' => $deprecated, 'actual' => $this->deprecated,
			]);
		}
		if ($configuration->has('assert')) {
			$ass_settings = [
				'active' => ASSERT_ACTIVE, 'warning' => ASSERT_WARNING, 'bail' => ASSERT_BAIL,
			];
			foreach ($ass_settings as $what) {
				assert_options($what, 0);
			}
			$assertionOptions = toList($configuration->get('assert'));
			foreach ($assertionOptions as $code) {
				if (array_key_exists($code, $ass_settings)) {
					assert_options($ass_settings[$code], 1);
				} else {
					throw new Exception_Configuration([
						__CLASS__, 'assert',
					], 'Invalid assert option: {code}, valid options: {settings}', [
						'code' => $code, 'settings' => array_keys($ass_settings),
					]);
				}
			}
		}
		if ($configuration->has('assert_callback')) {
			assert_options(ASSERT_CALLBACK, $configuration->get('assert_callback'));
		}
		$logUTC = [Logger::class, 'utc_time'];
		if ($this->configuration->pathExists($logUTC)) {
			$this->logger->utc_time = toBool($this->configuration->getPath($logUTC));
		}
	}

	/**
	 * Internal call to initialize profiler structure
	 */
	private function _profiler(): Profiler {
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
	public function profileTimer(string $item, float $seconds): void {
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
	 * @param int $depth
	 */
	public function profiler(int $depth = 2): void {
		$profiler = $this->_profiler();
		$functionKey = calling_function($depth + 1);
		if (array_key_exists($functionKey, $this->profiler->calls)) {
			$profiler->calls[$functionKey]++;
		} else {
			$profiler->calls[$functionKey] = 1;
		}
	}

	/**
	 * Getter for console
	 *
	 * @return boolean
	 */
	public function console(): bool {
		return $this->console;
	}

	/**
	 * Setter for console
	 *
	 * @param boolean $set
	 * @return self
	 */
	public function setConsole(bool $set = false): self {
		$this->console = $set;
		return $this;
	}

	/**
	 * Getter for application class
	 *
	 * @return string
	 */
	public function applicationClass(): string {
		return $this->applicationClass;
	}

	/**
	 * Setter for application class
	 *
	 * @param string $set
	 * @return $this
	 * @throws Exception_Semantics
	 */
	public function setApplicationClass(string $set): self {
		if ($set === $this->applicationClass) {
			return $this;
		}
		if ($this->application !== null) {
			throw new Exception_Semantics('Changing application class to {class} when application already instantiated', ['class' => $set, ]);
		}
		$this->applicationClass = $set;
		return $this;
	}

	public const HOOK_CREATE_APPLICATION = __CLASS__ . '::create_application';

	/**
	 * Create an application
	 *
	 * @param array $options
	 * @return Application
	 * @throws Exception_Semantics
	 * @throws Exception_Class_NotFound
	 */
	public function createApplication(array $options = []): Application {
		if ($this->application !== null) {
			throw new Exception_Semantics('{method} application of type {class} was already created', [
				'method' => __METHOD__, 'class' => get_class($this->application),
			]);
		}
		$app = $this->objects->factory($this->applicationClass, $this, $options);
		assert($app instanceof Application);
		$this->application = $app;
		$this->paths->created($this->application);
		$this->application->hooks->call(self::HOOK_CREATE_APPLICATION, $this->application);
		return $this->application;
	}

	/**
	 *
	 * @param callable|null $callback
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
				throw new Exception_Semantics('Application must be created with {class}::create_application', ['class' => get_class($this), ]);
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
	public function path(string $suffix = ''): string {
		return $this->paths->zesk($suffix);
	}

	/**
	 * Who owns the copyright on the Zesk Application Framework for PHP
	 *
	 * @return string
	 */
	public function copyrightHolder(): string {
		return 'Market Acumen, Inc.';
	}
}

Kernel::includes();
