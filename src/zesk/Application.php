<?php
declare(strict_types=1);
/**
 * @package zesk
 * @subpackage core
 * @author kent
 * @copyright Copyright &copy; 2023, Market Acumen, Inc.
 */

namespace zesk;

use Closure;
use Doctrine\ORM\EntityManager;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\LoggerTrait;
use Psr\Log\NullLogger;
use ReflectionException;
use Throwable;
use zesk\Adapter\SettingsConfiguration;
use zesk\Application\Classes;
use zesk\Application\Hooks;
use zesk\Application\Modules;
use zesk\Application\Objects;
use zesk\Application\Paths;
use zesk\Configuration\Loader;
use zesk\Cron\Module as CronModule;
use zesk\Doctrine\Module as DoctrineModule;
use zesk\Exception\AuthenticationException;
use zesk\Exception\ClassNotFound;
use zesk\Exception\ConfigurationException;
use zesk\Exception\Deprecated;
use zesk\Exception\DirectoryNotFound;
use zesk\Exception\DirectoryPermission;
use zesk\Exception\FileNotFound;
use zesk\Exception\FilePermission;
use zesk\Exception\SystemException;
use zesk\Exception\KeyNotFound;
use zesk\Exception\NotFoundException;
use zesk\Exception\ParameterException;
use zesk\Exception\ParseException;
use zesk\Exception\Redirect;
use zesk\Exception\SemanticsException;
use zesk\Exception\SyntaxException;
use zesk\Exception\UnsupportedException;
use zesk\Interface\ModelFactory;
use zesk\Interface\SessionInterface;
use zesk\Interface\SettingsInterface;
use zesk\Interface\Userlike;
use zesk\Job\Module as JobModule;
use zesk\Locale\Locale;
use zesk\Locale\Reader;
use zesk\Mail\Module as MailModule;
use zesk\Router\Parser;
use zesk\Session\Module as SessionModule;
use zesk\Settings\FileSystemSettings;
use function str_ends_with;

/**
 * Core web application object for Zesk.
 *
 * If you're doing something useful, it's probably a simple application.
 *
 * Methods below require you to actually load the modules for them to work.
 *
 * @method EntityManager entityManager(string $name = '');
 * @method DoctrineModule doctrineModule()
 *
 *
 * @method JobModule jobModule()
 *
 * @method Repository\Module repositoryModule()
 *
 * @method CronModule cronModule()
 *
 * @method MailModule mailModule()
 *
 * @method SessionInterface sessionFactory()
 * @method SessionModule sessionModule()
 */
class Application extends Hookable implements ModelFactory, HookSource, LoggerInterface {
	use LoggerTrait;

	public const HOOK_MAIN = __CLASS__ . '::main';

	public const HOOK_SECURITY = __CLASS__ . '::security';

	/**
	 * Called when setCommand called
	 */
	public const HOOK_LOCALE = __CLASS__ . '::setLocale';

	public const HOOK_COMMAND = __CLASS__ . '::command';

	/**
	 * Called when setCommand called
	 */
	public const FILTER_MAINTENANCE = __CLASS__ . '::maintenance';

	/**
	 * If you want to handle hooks for singleton handling of `zesk\User`, then do
	 *
	 * #[HookMethod(handles: Application::HOOK_SINGLETON_PREFIX . User::class)]
	 *
	 * To get your hook called.
	 */
	public const HOOK_SINGLETON_PREFIX = __CLASS__ . '::singleton::';

	/**
	 * Router was created
	 *
	 * @var string
	 */
	public const HOOK_ROUTER = __CLASS__ . '::router';

	/**
	 * Add routes to router
	 * @var string
	 */
	public const HOOK_ROUTES = __CLASS__ . '::routes';

	/**
	 * Run after routes are created
	 * @var string
	 */
	public const HOOK_ROUTES_POSTPROCESS = __CLASS__ . '::routesPostprocess';

	/**
	 *
	 */
	public const HOOK_ROUTE_NOT_FOUND = self::class . '::routeNotFound';

	/**
	 *
	 */
	public const HOOK_ROUTE_FOUND = self::class . '::routeFound';

	/**
	 * @desc Default option to store application version - may be stored differently in overridden classes, use
	 * @see self::version()
	 * @var string
	 * @copyright &copy; 2023 Market Acumen, Inc.
	 * @package zesk
	 */
	public const OPTION_VERSION = 'version';

	/**
	 * @desc Value used to instantiate the primary application
	 * @see Kernel::createApplication()
	 * @see self::applicationClass()
	 * @copyright &copy; 2023 Market Acumen, Inc.
	 */
	public const OPTION_APPLICATION_CLASS = 'applicationClass';

	/**
	 * Option value is boolean
	 *
	 * Debugging - bool
	 */
	public const OPTION_DEBUG = 'debug';

	/**
	 * Option value is string
	 *
	 * @see self::deprecated()
	 * @see self::setDeprecated()
	 * @see self::DEPRECATED_BACKTRACE
	 * @see self::DEPRECATED_EXCEPTION
	 * @see self::DEPRECATED_IGNORE
	 * @see self::DEPRECATED_LOG
	 * @see Kernel::createApplication()
	 * @var string
	 */
	public const OPTION_DEPRECATED = 'deprecated';

	/**
	 * @see self::configuredAssert()
	 * @var string
	 */
	public const OPTION_ASSERT = 'assert';

	/**
	 * @see self::path()
	 * @see Paths::application()
	 * @see Paths::setApplication()
	 */
	public const OPTION_PATH = 'path';

	/**
	 * Command paths are always appended to the path
	 *
	 * @see self::commandPath()
	 * @see Paths::command()
	 * @see Paths::addCommand()
	 */
	public const OPTION_COMMAND_PATH = 'commandPath';

	/**
	 *
	 */
	public const OPTION_ZESK_COMMAND_PATH = 'zeskCommandPath';

	/**
	 * Data paths are for persistent data storage which is persistent across application
	 * processes on all servers.
	 *
	 * @see self::dataPath()
	 * @see Paths::data()
	 * @see Paths::setData()
	 */
	public const OPTION_DATA_PATH = 'dataPath';

	/**
	 * Home paths is an operating system assigned home directory for the current user.
	 *
	 * Defaults to ~ which is $_SERVER['HOME']
	 *
	 * @see self::homePath()
	 * @see Paths::home()
	 * @see Paths::setHome()
	 */
	public const OPTION_HOME_PATH = 'homePath';

	/**
	 * User home paths is a private directory for storing local configuration files.
	 *
	 * Defaults to ~/.zesk but can be customized per-application.
	 *
	 * @see self::userHomePath()
	 * @see Paths::userHome()
	 * @see Paths::setUserHome()
	 */
	public const OPTION_USER_HOME_PATH = 'userHomePath';

	/**
	 * List of files for configureInclude
	 */
	public const OPTION_CONFIGURATION_FILES = 'configurationFiles';

	/**
	 * Contains a list of modules to load for this application;
	 */
	public const OPTION_MODULES = 'modules';

	/**
	 * Contains a list of modules which were loaded for this application
	 */
	public const OPTION_MODULES_LOADED = 'modulesLoaded';

	public const OPTION_CACHE_POOL = 'cachePool';

	public const OPTION_DEVELOPMENT = 'development';

	/**
	 * Value is a path where temporary files can be stored.
	 */
	public const OPTION_CACHE_PATH = 'cachePath';

	/**
	 * "Read-only" option which contains Zesk Root path.
	 */
	public const OPTION_ZESK_ROOT = 'zeskRoot';

	/**
	 * Path to a small local configuration file which is loaded to set the maintenance values for
	 * the current application.
	 */
	public const OPTION_MAINTENANCE_FILE = 'maintenanceFile';

	/**
	 * Value is bool.
	 *
	 * Cache the router file
	 */
	public const OPTION_CACHE_ROUTER = 'cacheRouter';

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
	 * @see self::configuredAssert()
	 * @see self::OPTION_ASSERT
	 * @var string
	 */
	public const ASSERT_ACTIVE = 'active';

	/**
	 * When assert fails, warning
	 *
	 * @see self::configuredAssert()
	 * @see self::OPTION_ASSERT
	 * @var string
	 */
	public const ASSERT_WARNING = 'warning';

	/**
	 * When assert fails, bail (exit)
	 * @see self::configuredAssert()
	 * @see self::OPTION_ASSERT
	 * @var string
	 */
	public const ASSERT_BAIL = 'bail';

	/**
	 * Called when $application->cacheClear is called
	 */
	public const HOOK_CACHE_CLEAR = 'cacheClear';

	/**
	 * Called when $application->setCache is called
	 */
	public const HOOK_SET_CACHE = 'setCache';

	/**
	 * Default maintenance file name
	 *
	 * @var string
	 */
	public const DEFAULT_MAINTENANCE_FILE = './etc/maintenance.json';

	/**
	 *
	 */
	public const REQUEST_OPTION_SESSION = __CLASS__ . '::session';

	/**
	 *
	 * @var double
	 */
	public float $initializationMicrotime;

	/**
	 *
	 * @see self::console()
	 * @var boolean
	 */
	private bool $console;

	/**
	 * For storing profiling information
	 *
	 * @see self::profiler()
	 * @see self::profileTimer()
	 */
	private ?Profiler $profiler = null;

	/**
	 *
	 * @var string
	 */
	private string $deprecated = 'ignore';

	/**
	 * Inherited directly from zesk\Kernel.
	 * Do not edit the value here.
	 *
	 * @var Paths
	 */
	public Paths $paths;

	/**
	 * Inherited directly from zesk\Kernel.
	 * Do not edit.
	 *
	 * @var Autoloader
	 */
	public Autoloader $autoloader;

	/**
	 * Inherited directly from zesk\Kernel.
	 * Do not edit.
	 *
	 * @var Hooks
	 */
	public Hooks $hooks;

	/**
	 * Inherited directly from zesk\Kernel.
	 * Do not edit.
	 *
	 * @var CacheItemPoolInterface
	 */
	protected CacheItemPoolInterface $pool;

	/**
	 * Inherited directly from zesk\Kernel.
	 * Do not edit.
	 *
	 * @var Configuration
	 */
	public Configuration $configuration;

	/**
	 *
	 * @var Loader
	 */
	public Loader $loader;

	/**
	 * Primary logger for the application.
	 * If you copy a reference to this, check it before
	 * using it. It can change at any time.
	 *
	 * @var LoggerInterface
	 */
	protected LoggerInterface $logger;

	/**
	 * Inherited directly from zesk\Kernel.
	 * Do not edit.
	 *
	 * @var Classes
	 */
	public Classes $classes;

	/**
	 * Inherited directly from zesk\Kernel.
	 * Do not edit.
	 *
	 * @var Objects
	 */
	public Objects $objects;

	/**
	 * Inherited directly from zesk\Kernel.
	 * Do not edit.
	 *
	 * @var Process
	 */
	public Process $process;

	/**
	 *
	 * @var Locale
	 */
	public Locale $locale;

	/**
	 *
	 * @var null|Command
	 */
	public null|Command $command = null;

	/**
	 * Modules object interface
	 *
	 * @var Modules
	 */
	public Modules $modules;

	/**
	 *
	 * @var Router
	 */
	public Router $router;

	/**
	 * @var Themes
	 */
	public Themes $themes;

	/**
	 * File where the application class resides.
	 * Override this in subclasses with
	 * public $file = __FILE__;
	 *
	 * @var string
	 */
	public string $file = '';

	/**
	 * Array of external modules to load
	 *
	 * @var string[]
	 * @see $this->load_modules
	 */
	protected array $load_modules = [];

	/**
	 * Array of parent => child mappings for model creation/instantiation.
	 *
	 * Allows you to set your own user class which extends \zesk\User, for example.
	 *
	 * @var array
	 */
	protected array $class_aliases = [];

	/**
	 * Array of classes to register hooks automatically
	 *
	 * @var array of string
	 */
	protected array $register_hooks = [];

	/**
	 * Configuration files to include
	 *
	 * @var array of string
	 */
	protected array $includes = [];

	/**
	 *
	 * @var Locale[]
	 */
	private array $locales = [];

	/**
	 * List of search paths to find modules for loading
	 *
	 * @var string[]
	 */
	private array $modulePaths = [];

	/**
	 *
	 * @var Request[]
	 */
	private array $requestStack = [];

	/**
	 * Array of calls to create stuff
	 *
	 * @var Closure[]
	 */
	private array $callables = [];

	/**
	 * Zesk Command paths for loading zesk-command.php commands
	 *
	 * @var array
	 */
	private array $zeskCommandPath = [];

	/**
	 * Paths to search for locale files
	 *
	 * @var string[]
	 */
	private array $localePath = [];

	/**
	 *
	 * @var string
	 */
	private string $cachePath = '';

	/**
	 *
	 * @var string
	 */
	private string $document = '';

	/**
	 *
	 * @var string
	 */
	private string $documentPrefix = '';

	/**
	 * Boolean
	 *
	 * @var boolean
	 */
	private bool $configuredWasRun = false;

	/**
	 * Set to true after shutdown
	 *
	 * @var bool
	 */
	private bool $applicationShutdown = false;

	/**
	 *
	 * @var array
	 */
	private array $contentRecursion = [];

	/**
	 *
	 * @param Configuration $configuration
	 * @param CacheItemPoolInterface $cacheItemPool
	 * @throws ClassNotFound
	 * @throws DirectoryNotFound
	 * @throws SemanticsException
	 */
	public function __construct(Configuration $configuration, CacheItemPoolInterface $cacheItemPool) {
		/*
		 * Zesk start time in microseconds
		 */
		$this->initializationMicrotime = $configuration['init'] ?? microtime(true);
		parent::__construct($this, $configuration->path(self::class)->toArray());
		$this->setOption(self::OPTION_APPLICATION_CLASS, self::class);
		$this->_initialize($configuration, $cacheItemPool); /* throws SystemException */
		$this->_initialize_fixme();
		$this->_loadMaintenance();
	}

	/**
	 * To disable deprecated function, call with blank value ""
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
	 * @throws Deprecated
	 * @see self::obsolete()
	 */
	public function deprecated(string $reason = '', array $arguments = []): void {
		if ($this->deprecated === self::DEPRECATED_IGNORE) {
			return;
		}
		$depth = $arguments['depth'] ?? 0;
		switch ($this->deprecated) {
			case self::DEPRECATED_EXCEPTION:
				throw new Deprecated("{reason} Deprecated: {calling_function}\n{backtrace}", [
					'reason' => $reason, 'calling_function' => Kernel::callingFunction(),
					'backtrace' => Kernel::backtrace(4 + $depth),
				] + $arguments);
			case self::DEPRECATED_LOG:
				$this->logger->error("{reason} Deprecated: {calling_function}\n{backtrace}", [
					'reason' => $reason ?: 'DEPRECATED', 'calling_function' => Kernel::callingFunction(),
					'backtrace' => Kernel::backtrace(4 + $depth),
				] + $arguments);
				break;
			case self::DEPRECATED_BACKTRACE:
				echo Kernel::backtrace();
				exit(1);
		}
	}

	/**
	 * For cordoning off old, dead code
	 * @codeCoverageIgnore
	 */
	public function obsolete(): void {
		$this->logger->alert('Obsolete function called {function}', ['function' => Kernel::callingFunction(2), ]);
		if ($this->application->development()) {
			echo Kernel::backtrace();
			exit(1);
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
		$functionKey = Kernel::callingFunction($depth + 1);
		if (array_key_exists($functionKey, $this->profiler->calls)) {
			$profiler->calls[$functionKey]++;
		} else {
			$profiler->calls[$functionKey] = 1;
		}
	}

	/**
	 * Returns a list of PHP source code for the application, used to scan for Attributes
	 *
	 * @return array
	 */
	public function hookSources(): array {
		$sources = [$this->application->zeskHome('src')];
		foreach ($this->modules->all() as $module) {
			/* @var $module Module */
			$sources = array_merge($sources, $module->hookSources());
		}
		return $sources;
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
	 * Are we attached to a console?
	 *
	 * @return boolean
	 */
	public function console(): bool {
		return $this->console;
	}

	/**
	 * @param Configuration $configuration
	 * @param CacheItemPoolInterface $pool
	 * @return void
	 * @throws ClassNotFound
	 * @throws DirectoryNotFound
	 * @throws SemanticsException
	 */
	protected function _initialize(Configuration $configuration, CacheItemPoolInterface $pool): void {
		$this->configuration = $configuration;
		$this->classes = new Classes();

		$this->inheritConfiguration();

		$this->pool = $pool;

		$this->console = PHP_SAPI === 'cli';

		$this->autoloader = new Autoloader($pool);

		$this->paths = new Paths($configuration);

		$this->includes = [];
		if ($this->hasOption(Application::OPTION_CONFIGURATION_FILES)) {
			$this->configureInclude($this->optionIterable(Application::OPTION_CONFIGURATION_FILES));
		}

		$this->hooks = new Hooks($this);
		$this->logger = new NullLogger();
		$this->objects = new Objects();
		$this->modules = new Modules($this);

		$this->themes = new Themes();
		$this->themes->setVariables(['application' => $this]);
		/*
		 * Configuration loader
		 */
		$this->loader = new Loader([], new SettingsConfiguration($this->configuration));

		/*
		 * Current process interface. Depends on ->hooks
		 */
		$this->process = new Process($this);

		/*
		 * Locale
		 */
		$this->locale = $this->localeFactory();

		/**
		 * Creates a basic, uninitialized Router object
		 */
		$this->router = $this->routerFactory();

		/*
		 * Where various things can be found
		 */
		// Find modules here
		$this->modulePaths = [];
		// Find Zesk commands here
		$this->zeskCommandPath = [];
		// Where to store temporary files
		$this->cachePath = '';
		// Where our web server is pointing to
		$this->document = '';
		// Web server has a hard-coded prefix
		$this->documentPrefix = '';

		$this->configuredWasRun = false;

		$this->command = null;

		// $this->load_modules is set in subclasses
		// $this->class_aliases is set in subclasses
		// $this->file is set in subclasses
		// $this->register_hooks is set in subclasses
		//


		foreach ($this->class_aliases as $requested => $resolved) {
			$this->objects->map($requested, $resolved);
		}

		// Variable state
		// Root template
		// Stack of currently rendering themes

		try {
			$function = '_initializeDocumentRoot';
			$this->_initializeDocumentRoot();
			$function = 'addZeskCommandPath';
			$this->addZeskCommandPath($this->defaultZeskCommandPath());
			$function = 'addModulePath';
			$this->addModulePath($this->defaultModulesPath());
			$function = 'addThemePath';
			$this->addThemePath($this->defaultThemePath());
			$function = 'addLocalePath';
			$this->addLocalePath($this->defaultLocalePath());
		} catch (DirectoryNotFound $e) {
			throw new DirectoryNotFound($e->path(), 'Default {function} paths broken {message}', [
				'message' => $e->getMessage(), 'function' => $function,
			], $e->getCode(), $e);
		}
	}

	/**
	 * Create a new Locale
	 *
	 * @param string $code
	 * @param array $extensions
	 * @param array $options
	 * @return Locale
	 * @throws ClassNotFound
	 */
	public function localeFactory(string $code = '', array $extensions = [], array $options = []): Locale {
		return Reader::factory($this->localePath(), $code, $extensions)->locale($this, $options);
	}

	/**
	 *
	 * @param array $options
	 * @return Router
	 */
	protected function routerFactory(array $options = []): Router {
		return new Router($this, $options);
	}

	/**
	 * Create a generic object
	 *
	 * @param string $class
	 * @return object
	 * @throws ClassNotFound
	 */
	public function factory(string $class): object {
		$arguments = func_get_args();
		$class = array_shift($arguments);
		return $this->factoryArguments($class, $arguments);
	}

	/**
	 * @param LoggerInterface $logger
	 * @return $this
	 */
	public function setLogger(LoggerInterface $logger): self {
		$this->logger = $logger;
		return $this;
	}

	/**
	 * @return LoggerInterface
	 */
	public function logger(): LoggerInterface {
		return $this->logger;
	}

	/**
	 * @param $level
	 * @param $message
	 * @param array $context
	 * @return void
	 */
	public function log($level, $message, array $context = []): void {
		$this->logger->log($level, $message, $context);
	}

	/**
	 * @param string $class
	 * @param array $arguments
	 * @return object
	 * @throws ClassNotFound
	 */
	public function factoryArguments(string $class, array $arguments = []): object {
		return $this->objects->factoryArguments($class, $arguments);
	}

	/**
	 * Retrieve the locale path for this application - used to load locales
	 *
	 * By default, it's ./etc/language/
	 * Must exist in the file system
	 *
	 * @return array
	 */
	final public function localePath(): array {
		return $this->localePath;
	}

	/**
	 * Initialize web root to enable non-rooted web sites.
	 * This should be called from any script which interacts with
	 * files on the web path or any script which is invoked from the web. Ideally, it should be in
	 * your application initialization code. It determines the web root from
	 * $_SERVER['DOCUMENT_ROOT'] so if your web
	 * server doesn't support setting this or you are invoking it from a script (which, for example,
	 * manipulates
	 * files which depend on this being set correctly) then you should initialize it with
	 *
	 * $application->setDocumentRoot(...)
	 *
	 * Currently things which use this are: TODO
	 *
	 * @throws DirectoryNotFound
	 *
	 */
	private function _initializeDocumentRoot(): void {
		$http_document_root = rtrim($_SERVER['DOCUMENT_ROOT'] ?? '', '/');
		if ($http_document_root) {
			$this->setDocumentRoot($http_document_root);
		}
	}

	/**
	 * Your web root is the directory in the file system which contains our application and other
	 * files.
	 *
	 * It may be served from an aliased or shared directory and as such may not appear at the web
	 * server's root.
	 *
	 * To ensure all URLs are generated correctly, you can set $this->documentRootPrefix(string) to set
	 * a portion of the URL which is always prefixed to any generated url.
	 *
	 * @param string $set
	 *            Optionally set the web root
	 * @param string $prefix
	 * @return self
	 * @throws DirectoryNotFound
	 */
	final public function setDocumentRoot(string $set, string $prefix = ''): self {
		if (!is_dir($set)) {
			throw new DirectoryNotFound($set);
		}
		$set = rtrim($set, '/');
		$this->document = $set;
		if ($prefix !== '') {
			$this->setDocumentRootPrefix($prefix);
		}
		return $this;
	}

	/**
	 * @param string $set
	 * @return $this
	 */
	final public function setDocumentRootPrefix(string $set): self {
		$this->documentPrefix = rtrim($set, '/');
		return $this;
	}

	/**
	 * @param array|string $add A path or array of paths containing zesk command files (PHP files)
	 * @return self
	 * @throws DirectoryNotFound
	 */
	final public function addZeskCommandPath(array|string $add): self {
		foreach (Types::toList($add) as $path) {
			if (!is_dir($path)) {
				throw new DirectoryNotFound($path);
			}
		}
		foreach (Types::toList($add) as $path) {
			if (!in_array($path, $this->zeskCommandPath)) {
				$this->zeskCommandPath[] = $path;
			} else {
				$this->logger->debug('{method}: did not add "{path}" because it already exists', [
					'method' => __METHOD__, 'path' => $path,
				]);
			}
		}
		return $this;
	}

	/**
	 * @return string
	 */
	private function defaultZeskCommandPath(): string {
		return $this->paths->zesk('src/zesk/Command');
	}

	/**
	 * Set the module search path
	 *
	 * @param string $add
	 * @return $this
	 * @throws DirectoryNotFound
	 */
	final public function addModulePath(string $add): self {
		if (!is_dir($add)) {
			throw new DirectoryNotFound($add);
		}
		$this->modulePaths[] = $add;
		return $this;
	}

	/**
	 *
	 * @return string
	 */
	private function defaultModulesPath(): string {
		return $this->paths->zesk('modules');
	}

	/**
	 * Add a path to be searched before existing paths
	 * (first in the list).
	 *
	 * @param array|string $paths
	 *            Path to add to the theme path. Pass in null to do nothing.
	 * @param string $prefix
	 *            (Optional) Handle theme requests which begin with this prefix. Saves having deep
	 *            directories.
	 * @return self
	 * @throws DirectoryNotFound
	 */
	final public function addThemePath(array|string $paths, string $prefix = ''): self {
		$this->themes->addThemePath($paths, $prefix);
		return $this;
	}

	/**
	 *
	 * @return string
	 */
	private function defaultThemePath(): string {
		return $this->paths->zesk('theme');
	}

	/**
	 * Add or retrieve the locale path for this application - used to load locales
	 *
	 * By default, it's ./etc/language/
	 * Must exist in the file system
	 *
	 * @param string $add Locale path to add
	 * @return $this
	 * @throws DirectoryNotFound
	 */
	final public function addLocalePath(string $add): self {
		$add = $this->paths->expand($add);
		if (!is_dir($add)) {
			throw new DirectoryNotFound($add);
		}
		$this->localePath[] = $add;
		return $this;
	}

	/**
	 *
	 * @return string
	 */
	private function defaultLocalePath(): string {
		return $this->paths->zesk('etc/language');
	}

	/**
	 * Initialize part 2
	 */
	protected function _initialize_fixme(): void {
		// These two calls mess up reconfigure and do not reset state correctly.
		// Need a robust globals monitor to ensure reconfigure resets state back to default
		// Difficult issue is that the class loader modifies state (sort of)
		$this->callables = [];
		$this->modules = new Modules($this);
	}

	/**
	 * Load the maintenance JSON file
	 *
	 * @return void
	 */
	private function _loadMaintenance(): void {
		$file = $this->maintenanceFile();
		if (!file_exists($file)) {
			$result = [
				'enabled' => false,
			];
		} else {
			try {
				$result = JSON::decode(file_get_contents($file));
			} catch (ParseException) {
				$result = [
					'error' => 'Unable to parse maintenance file',
				];
			}
			/* File can set to false */
			$result += [
				'enabled' => true,
			];
		}
		$this->setOption(self::OPTION_MAINTENANCE, $result);
	}

	/**
	 *
	 */
	public const OPTION_MAINTENANCE = 'maintenance';

	/**
	 * Return file, which when exists, puts the site into maintenance mode.
	 *
	 * Always a JSON file
	 *
	 * @return string
	 */
	private function maintenanceFile(): string {
		return $this->paths->expand($this->option(self::OPTION_MAINTENANCE_FILE, self::DEFAULT_MAINTENANCE_FILE));
	}

	/**
	 * Return a path relative to the application root
	 */
	final public function path(string|array $suffix = ''): string {
		return $this->paths->application($suffix);
	}

	/**
	 * The unique ID name for this application, used for cron or identifying multiple instances of a single application on a site.
	 *
	 * @return string
	 */
	public function id(): string {
		return get_class($this);
	}

	/**
	 * @return void
	 */
	public function shutdown(): void {
		if (!$this->applicationShutdown) {
			/* Ordering here matters */
			/* Hooks - notify all other code first that we are shutting down */
			$this->hooks->shutdown();
			/* Shut down modules */
			$this->modules->shutdown();
			/* Shut down singleton objects */
			$this->objects->shutdown();
			/* Shut down localization and language options */
			$this->locale->shutdown();
			/* Shut down the autoloader, caching stuff */
			$this->autoloader->shutdown();
			/* Shut down file system connections */
			$this->paths->shutdown();
			/* Done */
			$this->applicationShutdown = true;
		}
	}

	/**
	 *
	 * @param Command $set
	 * @return self
	 */
	public function setCommand(Command $set): self {
		if ($this->command) {
			if ($set === $this->command) {
				return $this;
			}
		}
		$this->command = $set;
		$this->invokeHooks(self::HOOK_COMMAND, [$set]);
		return $this;
	}

	/**
	 * Settings are stateful and should persist across process and server boundaries.
	 *
	 * @return SettingsInterface
	 * @throws ClassNotFound
	 */
	public function settings(): SettingsInterface {
		$settingsClass = $this->optionString('settingsClass', FileSystemSettings::class);
		$settingsClassStaticMethods = $this->optionIterable('settingsClassStaticMethods');
		if ($settingsClassStaticMethods) {
			$result = $this->objects->singletonArgumentsStatic($settingsClass, [$this], $settingsClassStaticMethods);
		} else {
			$result = $this->objects->singletonArguments($settingsClass, [$this]);
		}
		assert($result instanceof SettingsInterface);
		return $result;
	}

	/**
	 * @param string $class
	 * @param array $arguments
	 * @return object
	 * @throws ClassNotFound
	 * @throws SemanticsException
	 */
	final public function singletonArguments(string $class, array $arguments = []): object {
		$desiredClass = $this->objects->resolve($class);
		$hookName = self::HOOK_SINGLETON_PREFIX . $desiredClass;
		$object = $this->invokeHooksUntil($hookName, $arguments);
		if ($object instanceof $desiredClass) {
			return $object;
		}
		if ($object !== null) {
			throw new SemanticsException('Singleton hook {hookName} returned type {objectType} expecting {expectedType}', [
				'hookName' => $hookName, 'objectType' => $object::class, 'expectedType' => $desiredClass,
			]);
		}
		return $this->objects->singletonArguments($desiredClass, $arguments);
	}

	/**
	 * @param string $class
	 * @param array $arguments
	 * @param array $staticMethods
	 * @return object
	 * @throws ClassNotFound
	 */
	final public function singletonArgumentsStatic(string $class, array $arguments = [], array $staticMethods = ['singleton']): object {
		$desiredClass = $this->objects->resolve($class);
		return $this->objects->singletonArgumentsStatic($desiredClass, $arguments, $staticMethods);
	}

	/**
	 * Override this in child classes to manipulate creation of these objects.
	 * Create objects which take the application as the first parameter, and handles passing that on.
	 *
	 * Also, optionally calls `zesk\Application::singleton_{$class}`
	 *
	 * for $class "zesk\ORM\User" calls `zesk\Application::singleton_zesk_ORM_User`
	 *
	 * @param string $class
	 * @return Model
	 * @throws ClassNotFound
	 * @throws SemanticsException
	 */
	final public function modelSingleton(string $class): Model {
		$args = func_get_args();
		$args[0] = $this;
		$result = $this->singletonArguments($class, $args);
		assert($result instanceof Model);
		return $result;
	}

	/**
	 * Override in subclasses if it is stored in a different way.
	 *
	 * @return string
	 */
	public function version(): string {
		return strval($this->option(self::OPTION_VERSION));
	}

	/**
	 * Override in subclasses if it is stored in a different way.
	 *
	 * @param string $set
	 * @return self
	 */
	public function setVersion(string $set): self {
		$this->setOption(self::OPTION_VERSION, $set);
		return $this;
	}

	/**
	 * @return array
	 */
	final public function includes(): array {
		return array_keys($this->includes);
	}

	/**
	 * Configuration files are simple bash-style NAME=VALUE files with a few features or JSON.
	 *
	 * - You can use variables in values, like ${FOO} or $FOO; once loaded, the variable is replaced
	 *   and no longer part of the value.
	 * - Values are unquoted automatically, and assumed to be strings
	 * - Unquoted values are coerced to an internal PHP type, if possible
	 * @return Application
	 * @throws ConfigurationException
	 * @throws NotFoundException
	 * @throws ParseException
	 * @throws SystemException
	 * @throws UnsupportedException
	 */
	final public function configure(): self {
		$this->applicationShutdown = false;
		$this->_configure();
		$this->_configured();
		$this->initializeRouter();
		return $this;
	}

	/**
	 * Complete configuration process
	 * @throws ConfigurationException
	 * @throws NotFoundException
	 * @throws ParseException
	 * @throws UnsupportedException
	 * @throws SystemException
	 */
	private function _configure(): void {
		$application = $this;
		$this->hooks->registerHook(Hooks::HOOK_EXIT, function () use ($application): void {
			$application->cacheItemPool()->commit();
		});

		$this->paths->configure($this->options([
			self::OPTION_COMMAND_PATH, self::OPTION_CACHE_PATH, self::OPTION_DATA_PATH, self::OPTION_HOME_PATH,
			self::OPTION_USER_HOME_PATH,
		]));

		$this->configureCachePath(); // Initial cache paths are set up
		$this->beforeConfigure();
		$this->_configureFiles();
		$this->configuredFiles();
		/* Load any settings from configuration for module load */
		$this->inheritConfiguration();

		/* Load modules etc. */
		$this->modules->loadMultiple($this->load_modules);
		$this->loadOptionModules();
	}

	/**
	 * Register a class with the application to make it discoverable and
	 * to register any hooks.
	 *
	 * @param array|string $classes
	 * @return self
	 */
	final public function registerClass(array|string $classes): self {
		$this->classes->register($classes);
		return $this;
	}

	/**
	 */
	private function configureCachePath(): void {
		if ($this->cachePath === '') {
			$this->cachePath = $this->paths->expand($this->optionString(self::OPTION_CACHE_PATH, './cache'));
		}
	}

	/**
	 * Run before configuration setup
	 */
	protected function beforeConfigure(): void {
	}

	/**
	 * Load configuration files
	 * @throws SystemException
	 */
	private function _configureFiles(): void {
		if (count($this->includes) === 0) {
			$this->configureInclude($this->defaultConfigurationFiles());
		}
		$this->configureFiles($this->includes());
	}

	/**
	 * List files for configuration of the application.
	 *
	 * Configuration files can use values which are expanded:
	 *
	 *     /etc/app.json Is absolute
	 *     ./etc/app.json Is application-root relative
	 *     ~/.app/app.json Is user-home relative
	 *
	 * @param mixed $includes An iterator which generates a list of include files to load.
	 * @param bool $overwrite Replace existing include list (otherwise appends)
	 * @return self
	 */
	final public function configureInclude(array $includes, bool $overwrite = true): self {
		$includes = $this->expandIncludes($includes);
		if ($overwrite) {
			$this->includes = $includes;
		} else {
			$this->includes += $includes;
		}
		return $this;
	}

	/**
	 * Expand a list of include files
	 *
	 * @param array $includes
	 * @return string[]
	 */
	private function expandIncludes(array $includes): array {
		$result = [];
		foreach ($includes as $include) {
			$expand = $this->paths->expand($include);
			$result[$expand] = true;
		}
		return $result;
	}

	/**
	 * Default list of files to be loaded as part of this application configuration
	 *
	 * @return array
	 */
	private function defaultConfigurationFiles(): array {
		$files_default = [];
		$files_default[] = $this->path('etc/application.json');
		$files_default[] = $this->path('etc/host/' . strtolower(System::uname()) . '.json');
		return $files_default;
	}

	/**
	 * @param array $files
	 * @return void
	 * @throws SystemException
	 */
	public function configureFiles(array $files): void {
		$this->loader->appendFiles($files);
		$this->loader->load();
		$this->_loadMaintenance();
	}

	/**
	 * This loads an include without the $application variable defined, and $this which is also an Application.
	 * is meant to return a value, or has its own "internal" variables which may corrupt the global or current scope of
	 * a function, for example.
	 *
	 * @param string $__file__
	 *            File to include
	 * @return mixed Whatever is returned by the include file
	 */
	public function load(string $__file__): mixed {
		$application = $this;
		return include $this->application->paths->expand($__file__);
	}

	/**
	 * @return void
	 */
	protected function configuredFiles(): void {
		/* Function called after files are loaded */
	}

	/**
	 * @return void
	 * @throws ConfigurationException
	 * @throws NotFoundException
	 * @throws ParseException
	 * @throws UnsupportedException
	 */
	private function loadOptionModules(): void {
		$modules = $this->optionArray(self::OPTION_MODULES);
		if (count($modules) > 0) {
			$this->modules->loadMultiple($modules);
			$loaded = array_flip($this->optionArray(self::OPTION_MODULES_LOADED));
			$loaded += array_flip($modules);
			$this->setOption(self::OPTION_MODULES_LOADED, array_keys($loaded));
		}
	}

	/**
	 * Run fini
	 * @param bool $force
	 * @return boolean
	 * @throws ConfigurationException
	 */
	public function configured(bool $force = false): bool {
		if ($force || !$this->configuredWasRun) {
			$this->_configured();
			return true;
		}
		return false;
	}

	/**
	 * @return void
	 * @throws ConfigurationException
	 */
	private function _configured(): void {
		// Now run all configurations: System, Modules, then Application
		Theme::configured($this);
		$this->inheritConfiguration();
		if ($this->hasOption(self::OPTION_DEPRECATED)) {
			$this->setDeprecated($this->optionString(self::OPTION_DEPRECATED));
		}
		$this->configuredAssert();
		$this->configuredHooks();
		$this->configureCachePath();
		$this->afterConfigure();
		$this->configuredWasRun = true;
	}

	/**
	 * Load configuration
	 * @throws ConfigurationException
	 */
	final public function configuredAssert(): void {
		if (!$this->hasOption(self::OPTION_ASSERT)) {
			return;
		}
		$ass_settings = [
			self::ASSERT_ACTIVE => ASSERT_ACTIVE, self::ASSERT_WARNING => ASSERT_WARNING,
			self::ASSERT_BAIL => ASSERT_BAIL,
		];
		foreach ($ass_settings as $what) {
			assert_options($what, 0);
		}

		$anyActive = 0;
		foreach ($this->optionIterable(self::OPTION_ASSERT) as $code) {
			if (array_key_exists($code, $ass_settings)) {
				assert_options($ass_settings[$code], 1);
				$anyActive = 1;
			} else {
				throw new ConfigurationException([
					__CLASS__, 'assert',
				], 'Invalid assert option: {code}, valid options: {settings}', [
					'code' => $code, 'settings' => array_keys($ass_settings),
				]);
			}
		}
		assert_options(ASSERT_ACTIVE, $anyActive);
	}

	/**
	 */
	private function configuredHooks(): void {
		foreach ([Hooks::HOOK_DATABASE_CONFIGURE, Hooks::HOOK_CONFIGURED] as $hook) {
			$this->invokeHooks($hook, [$this]);
			$this->modules->addLoadHook($hook);
		}
	}

	/**
	 * Run post configuration setup
	 */
	protected function afterConfigure(): void {
	}

	/**
	 *
	 * @return boolean
	 */
	public function isConfigured(): bool {
		return $this->configuredWasRun;
	}

	/**
	 * Runs configuration again
	 *
	 * @return $this
	 * @throws ClassNotFound
	 * @throws ConfigurationException
	 * @throws DirectoryNotFound
	 * @throws NotFoundException
	 * @throws ParseException
	 * @throws SystemException
	 * @throws UnsupportedException
	 * @see Application::configure
	 */
	public function reconfigure(): self {
		$this->applicationShutdown = false;
		$this->invokeHooks(Hooks::HOOK_RESET, [$this]);
		$this->_configure();
		$this->modules->reload();
		$this->_configured();
		return $this;
	}

	/**
	 *
	 * @param CacheItemPoolInterface $pool
	 * @return self
	 */
	final public function setCacheItemPool(CacheItemPoolInterface $pool): self {
		$this->pool = $pool;
		$this->autoloader->setCache($pool);
		$this->invokeHooks(self::HOOK_SET_CACHE);
		return $this;
	}

	/**
	 * @return CacheItemPoolInterface
	 */
	final public function cacheItemPool(): CacheItemPoolInterface {
		return $this->pool;
	}

	/**
	 * Clear application cache
	 * @return void
	 * @throws ClassNotFound
	 * @throws DirectoryPermission
	 * @throws FilePermission
	 * @throws ParameterException
	 */
	final public function cacheClear(): void {
		$this->pool->clear();
		foreach (array_unique([
			$this->cachePath(),
		]) as $path) {
			if (empty($path)) {
				continue;
			}
			$size = Directory::size($path);
			if ($size > 0) {
				try {
					Directory::deleteContents($path);
				} catch (DirectoryNotFound) {
					continue;
				}
				$this->logger->notice('Deleted {size} bytes in {path}', compact('size', 'path'));
			} else {
				$this->logger->notice('{path} is empty.', compact('size', 'path'));
			}
		}
		$this->invokeHooks(self::HOOK_CACHE_CLEAR, [$this]);
		foreach (Hookable::objectHookMethods($this->controllers(), self::HOOK_CACHE_CLEAR) as $method) {
			/* @var $method HookMethod */
			$method->run([$this]);
		}
	}

	/**
	 * Get the cache path for the application
	 *
	 * @param string|array $suffix
	 * @return string
	 */
	final public function cachePath(string|array $suffix = ''): string {
		return Directory::path($this->cachePath, $suffix);
	}

	/**
	 * Return all known Controllers for the application.
	 *
	 * Potentially slow.
	 *
	 * @return Controller[]
	 * @throws ClassNotFound
	 */
	final public function controllers(): array {
		return $this->router()->controllers();
	}
	/**
	 * Application main execution:
	 *
	 * - Load the router
	 * - Find a matched route
	 * - Execute it
	 * - Return response
	 */

	/**
	 * Load router
	 *
	 * @return Router NULL
	 */
	final public function router(): Router {
		return $this->router;
	}

	/**
	 * @return void
	 */
	private function initializeRouter(): void {
		$this->invokeHooks(self::HOOK_ROUTER, [$this->router]);
		$this->invokeHooks(self::HOOK_ROUTES, [$this->router]);
		$this->invokeHooks(self::HOOK_ROUTES_POSTPROCESS, [$this->router]);
	}

	/**
	 * @return bool
	 */
	final public function maintenance(): bool {
		return Types::toBool($this->optionPath(['maintenance', 'enabled'], false));
	}

	/**
	 * Set maintenance flag, this generally affects an application's interface
	 *
	 * @param bool $set
	 * @return void
	 * @throws SemanticsException
	 */
	final public function setMaintenance(bool $set): void {
		try {
			$result = $this->invokeFilters(self::FILTER_MAINTENANCE, ['maintenance' => $set], [$this]);
			if (($result['maintenance'] ?? null) !== $set) {
				throw new SemanticsException('Filters prevented {applicationClass}::setMaintenance({value})', [
					'applicationClass' => get_class($this), Types::toText($set),
				]);
			}
		} catch (Throwable $t) {
			throw new SemanticsException('{applicationClass}::setMaintenance({value}) hook threw {exceptionClass} {message}', [
				'applicationClass' => get_class($this), 'value' => $set ? 'true' : 'false',
			] + Exception::phpExceptionVariables($t), 0, $t);
		}

		if ($set) {
			$this->_maintenanceEnabled($result);
			$this->setOptionPath(['maintenance', 'enabled'], true);
		} else {
			$this->unsetOptionPath(['maintenance', 'enabled']);
			$this->_disableMaintenance($result);
		}
	}

	private function _maintenanceEnabled(array $context): void {
		$context['time'] = date('Y-m-d H:i:s');

		try {
			file_put_contents($this->maintenanceFile(), JSON::encode($context));
		} catch (SemanticsException) {
		}
	}

	private function _disableMaintenance(array $context): void {
		$maintenance_file = $this->maintenanceFile();
		if (file_exists($maintenance_file)) {
			unlink($maintenance_file);
			clearstatcache(false, $maintenance_file);
		}
	}

	public const HOOK_CONTENT = __CLASS__ . '::content';

	/**
	 * Utility for index.php file for all public-served content.
	 * @throws SemanticsException
	 */
	final public function content(string $path): string {
		if (isset($this->contentRecursion[$path])) {
			return '';
		}
		$this->contentRecursion[$path] = true;
		$this->invokeHooks(self::HOOK_CONTENT);

		$url = 'http://localhost/';

		try {
			$url = rtrim(URL::leftHost($url), '/') . $path;
		} catch (SyntaxException) {
		}

		$request = $this->requestFactory();

		try {
			$request->initializeFromSettings([
				'url' => $url, 'method' => HTTP::METHOD_GET, 'data' => '', 'variables' => URL::queryParseURL($path),
			]);
		} catch (FileNotFound) {
			/* No files passed, not ever thrown */
		}
		$response = $this->main($request);
		ob_start();

		try {
			$response->output([
				'skip_headers' => true,
			]);
		} catch (SemanticsException) {
		}
		$content = ob_get_clean();
		unset($this->contentRecursion[$path]);
		return $content;
	}

	/**
	 * Creates a default request for the application. Useful in self::main
	 *
	 * Returns generic GET http://console/ when in the console.
	 *
	 * @param Request|null $inherit
	 * @return Request
	 */
	public function requestFactory(Request $inherit = null): Request {
		$request = new Request($this);

		try {
			if ($inherit) {
				$request->initializeFromRequest($inherit);
			} elseif ($this->console()) {
				$request->initializeFromSettings('http://console/');
			} else {
				$request->initializeFromGlobals();
			}
		} catch (FileNotFound) {
			// pass
		}
		return $request;
	}

	/**
	 * @param Request $request
	 * @return Response
	 * @throws SemanticsException
	 */
	public function main(Request $request): Response {
		try {
			$response = $this->invokeFilters(self::HOOK_MAIN, null, [$request]);
			if ($response instanceof Response) {
				return $response;
			}
			$route = $this->pushRequest($request)->determineRoute($request);
			$response = $route->execute($request);
		} catch (Throwable $exception) {
			$response = $this->mainException($request, $exception);
		}
		$this->popRequest($request);
		return $response;
	}

	/**
	 *
	 * @param Request $request
	 * @return Route
	 * @throws NotFoundException
	 * @throws ParameterException
	 * @throws ReflectionException
	 */
	private function determineRoute(Request $request): Route {
		$router = $this->router();
		$this->logger->debug('App bootstrap took {seconds} seconds', [
			'seconds' => sprintf('%.3f', microtime(true) - $this->initializationMicrotime),
		]);
		$this->themes->setVariables([
			'locale' => $this->locale, 'application' => $this, 'router' => $router, 'request' => $request,
		]);

		try {
			$route = $router->matchRequest($request);
		} catch (NotFoundException) {
			$this->routeNotFound($request);

			throw new NotFoundException('The resource does not exist on this server: {url}', $request->urlComponents(), HTTP::STATUS_FILE_NOT_FOUND);
		}
		$this->themes->setVariables([
			'route' => $route,
		]);
		return $this->routeFound($request, $route);
	}

	/**
	 * @param Request $request
	 * @return void
	 */
	protected function routeNotFound(Request $request): void {
		$this->invokeHooks(self::HOOK_ROUTE_NOT_FOUND, [$request]);
	}

	/**
	 * @param Request $request
	 * @param Route $route
	 * @return Route
	 * @throws ParameterException
	 * @throws ReflectionException
	 */
	protected function routeFound(Request $request, Route $route): Route {
		$route = $this->invokeTypedFilters(self::HOOK_ROUTE_FOUND, $route, [$request]);
		if ($this->optionBool('debug_route')) {
			$this->logger->debug('Matched route {class} Pattern: "{clean_pattern}" {options}', $route->variables());
		}
		$this->logger->debug('{uri} matched to {route}', [
			'uri' => $request->uri(), 'route' => $route->getPattern(),
		]);
		return $route;
	}

	/**
	 * Template or logging variables
	 *
	 * @return array
	 */
	public function variables(): array {
		$parameters['application'] = $this;
		$parameters['router'] = $this->router;
		// Do not include "request" in here as it may be NULL; and it should NOT override subclass values
		return $parameters;
	}

	/**
	 * @param Request $request
	 * @return self
	 */
	final public function pushRequest(Request $request): self {
		$this->requestStack[] = $request;
		$request->setOption('stack_index', count($this->requestStack));
		$this->beforeRequest($request);
		return $this;
	}

	/**
	 * Called when a request is pushed.
	 *
	 * @param Request $request
	 * @return void
	 */
	public function beforeRequest(Request $request): void {
	}

	/**
	 *
	 * @param Request $request
	 * @param \Exception $exception
	 * @return Response
	 */
	private function mainException(Request $request, Throwable $exception): Response {
		$response = $this->responseFactory($request, Response::CONTENT_TYPE_HTML);

		try {
			$response->content = $this->themes->theme($this->classes->hierarchy($exception), [
				'application' => $this, 'request' => $request, 'response' => $response, 'exception' => $exception,
				'content' => $exception,
			] + Exception::exceptionVariables($exception), [
				'first' => true,
			]);
			if (!$exception instanceof Redirect) {
				$this->invokeHooks(self::HOOK_EXCEPTION, [
					'exception' => $exception, 'application' => $this, 'response' => $response,
				]);
			}
		} catch (Redirect $e) {
			$response->redirect()->handleException($e);
		}
		return $response;
	}

	public const HOOK_EXCEPTION = __CLASS__ . '::exception';

	/**
	 *
	 * @param Request $request
	 * @param string $content_type
	 * @return Response
	 */
	final public function responseFactory(Request $request, string $content_type = ''): Response {
		return Response::factory($this, $request, $content_type ? [
			Response::OPTION_CONTENT_TYPE => $content_type,
		] : []);
	}

	/**
	 * @param Request $request
	 * @return self
	 * @throws SemanticsException
	 */
	final public function popRequest(Request $request): self {
		$starting_depth = $request->optionInt('stack_index');
		$ending_depth = count($this->requestStack);
		if ($ending_depth === 0) {
			throw new SemanticsException('Nothing to pop (attempted to pop {url})', [
				'request' => $request->url(),
			]);
		}
		if ($ending_depth !== $starting_depth) {
			throw new SemanticsException('Request ending depth mismatch start {starting_depth} !== end {ending_depth}', [
				'starting_depth' => $starting_depth, 'ending_depth' => $ending_depth,
			]);
		}
		$popped = ArrayTools::last($this->requestStack);
		if ($popped !== $request) {
			throw new SemanticsException('Request changed between push and pop? {original} => {popped}', [
				'original' => $request->variables(), 'popped' => $popped->variables(),
			]);
		}
		array_pop($this->requestStack);
		return $this;
	}

	/**
	 * Return the zesk home path, usually used to load built-in themes directly.
	 *
	 * @param string|array $suffix
	 *            Optional path to add to the application path
	 * @return string
	 */
	final public function zeskHome(string|array $suffix = ''): string {
		return $this->paths->zesk($suffix);
	}

	/**
	 * Utility for index.php file for all public-served content.
	 *
	 * KMD 2018-01 Made this more Response-centric and less content-centric
	 * @throws SemanticsException
	 */
	public function index(): Response {
		$final_map = [];

		$request = $this->requestFactory();
		$request = $this->invokeFilters(self::HOOK_REQUEST_PREPROCESS, $request);
		$options = [];
		if (($response = Response::cached($this->pool, $url = $request->url())) === null) {
			$response = $this->main($request);
			$response->cacheSave($this->pool, $url);
			$final_map['{page-is-cached}'] = '0';
		} else {
			$options['skip_hooks'] = true;
			$this->hooks->hooksDequeue(Hooks::HOOK_EXIT);
			$final_map['{page-is-cached}'] = '1';
		}
		$final_map += [
			'{page-render-time}' => sprintf('%.3f', microtime(true) - $this->initializationMicrotime),
		];
		if (!$response || $response->isContentType([
			'text/', 'javascript',
		])) {
			if ($response->content !== null) {
				$response->content = strtr($response->content, $final_map);
			}
		}
		$this->beforeOutput($request, $response);
		$response->output($options);
		return $response;
	}

	public const HOOK_REQUEST_PREPROCESS = __CLASS__ . '::requestPreprocess';

	/**
	 * @param Request $request
	 * @param Response $response
	 * @return void
	 */
	public function beforeOutput(Request $request, Response $response): void {
		// pass
	}

	/**
	 * Setter for locale - calls hook
	 *
	 * @param Locale $set
	 * @return self
	 */
	public function setLocale(Locale $set): self {
		$this->locale = $set;
		$this->invokeHooks(self::HOOK_LOCALE, [$this, $set]);
		return $this;
	}

	/**
	 * Create a `zesk\Locale` if it has not been encountered in this process and cache it as part of the `Application`
	 *
	 * @param string $code
	 * @param array $extensions
	 * @param array $options
	 * @return Locale
	 * @throws ClassNotFound
	 */
	public function localeRegistry(string $code, array $extensions = [], array $options = []): Locale {
		$code = Locale::normalize($code);
		if (isset($this->locales[$code])) {
			return $this->locales[$code];
		}
		return $this->locales[$code] = $this->localeFactory($code, $extensions, $options);
	}

	/**
	 * Add or retrieve the data path for this application
	 *
	 * @param string $suffix
	 * @return string Current data_path value
	 */
	final public function dataPath(string $suffix = ''): string {
		return $this->paths->data($suffix);
	}

	/**
	 * Get or set the zesk command path, which is where Zesk searches for commands from the
	 * command-line tool.
	 *
	 * As of 0.8.2, command paths store a class prefix
	 *
	 * The default path is ZESK_ROOT 'zesk/Command', but applications can add their own tools
	 * upon initialization.
	 *
	 * This call always returns the complete path, even when adding. Note that adding a path which
	 * does not exist has no effect.
	 *
	 * @return array
	 */
	final public function zeskCommandPath(): array {
		return $this->zeskCommandPath;
	}

	/**
	 * Get autoload paths for the application.
	 *
	 * @return array
	 */
	final public function autoloadPath(): array {
		return $this->autoloader->path();
	}

	/**
	 * Set autoload path for the application.
	 *
	 * @param mixed $add
	 * @param array|bool|string $options
	 * @return self
	 * @throws DirectoryNotFound
	 */
	final public function addAutoloadPath(string $add, array|bool|string $options = []): self {
		$this->autoloader->addPath($add, $options);
		return $this;
	}

	/**
	 * Get or set the command path for the application.
	 *
	 * @return array The ordered list of paths to search for system commands
	 */
	final public function commandPath(): array {
		return $this->paths->command();
	}

	/**
	 *
	 * @return Command
	 * @throws SemanticsException
	 */
	public function command(): Command {
		if ($this->command) {
			return $this->command;
		}

		throw new SemanticsException('No command set');
	}

	/**
	 * Add a path
	 *
	 * @param string $path
	 * @return self
	 */
	final public function addCommandPath(string $path): self {
		$this->paths->addCommand($path);
		return $this;
	}

	/**
	 * Return the application PHP class
	 *
	 * @return string
	 */
	final public function applicationClass(): string {
		return $this->optionString(self::OPTION_APPLICATION_CLASS);
	}

	/**
	 * Your web root is the directory in the file system which contains our application and other
	 * files.
	 *
	 * It may be served from an aliased or shared directory and as such may not appear at the web
	 * server's root.
	 *
	 * To ensure all URLs are generated correctly, you can set $this->set_document_root_prefix() to
	 * set
	 * a portion of the URL which is always prefixed to any generated url.
	 *
	 * @param null|string|array $suffix Optionally append to web root
	 * @return string Path relative to document root
	 */
	final public function documentRoot(null|string|array $suffix = ''): string {
		return Directory::path($this->document, $suffix);
	}

	/**
	 * Your web root may be served from an aliased or shared directory and as such may not appear at
	 * the web server's root.
	 *
	 * To ensure all URLs are generated correctly, you can set a portion of the URL which is always
	 * prefixed to any generated url.
	 * @return string
	 */
	final public function documentRootPrefix(): string {
		return $this->documentPrefix;
	}

	/**
	 * Get the module search path
	 *
	 * @return string[] List of paths searched
	 */
	final public function modulePath(): array {
		return $this->modulePaths;
	}

	/**
	 * Return the development status of this application
	 *
	 * @return boolean
	 */
	public function development(): bool {
		return $this->optionBool(self::OPTION_DEVELOPMENT);
	}

	/**
	 * Set the development status of this application
	 *
	 * @param boolean $set Set value
	 * @return self
	 */
	public function setDevelopment(bool $set): self {
		return $this->setOption(self::OPTION_DEVELOPMENT, $set);
	}

	/**
	 * Create a model
	 *
	 * @param string $class
	 * @param mixed|null $value
	 * @param array $options
	 * @return Model
	 * @throws ClassNotFound
	 * @see ModelFactory
	 */
	public function modelFactory(string $class, array $value = [], array $options = []): Model {
		$model = $this->objects->factoryArguments($class, [$this, $options]);
		assert($model instanceof Model);
		return $model->initializeFromArray($value);
	}

	/**
	 *
	 * @param Request $request
	 * @param bool $require
	 * @return SessionInterface|null
	 */
	public function session(Request $request, bool $require = true): ?SessionInterface {
		return $require ? $this->requireSession($request) : $this->optionalSession($request);
	}

	/**
	 * Create a generic object
	 *
	 * @param string $class
	 */

	/**
	 * Require a session
	 * @param Request $request
	 * @return SessionInterface
	 */
	public function requireSession(Request $request): SessionInterface {
		$session = $this->requestGetSession($request);
		return $session ?: $this->sessionRequestFactory($request);
	}

	/**
	 * @param Request $request
	 * @return SessionInterface|null
	 */
	private function requestGetSession(Request $request): ?SessionInterface {
		if ($request->hasOption(self::REQUEST_OPTION_SESSION)) {
			$session = $request->option(self::REQUEST_OPTION_SESSION);
			if ($session instanceof SessionInterface) {
				return $session;
			}
		}
		return null;
	}

	/**
	 * Create a session and attach it to the request
	 *
	 * @param Request $request
	 * @return SessionInterface
	 */
	private function sessionRequestFactory(Request $request): SessionInterface {
		$session = $this->sessionFactory();
		$session->initializeSession($request);
		$request->setOption(self::REQUEST_OPTION_SESSION, $session);
		return $session;
	}

	/**
	 * Get a session if it has been created already
	 *
	 * @param Request $request
	 * @return ?SessionInterface
	 */
	public function optionalSession(Request $request): ?SessionInterface {
		return $this->requestGetSession($request);
	}

	/**
	 *
	 * @return float Microseconds initialization time
	 */
	final public function initializationTime(): float {
		return $this->initializationMicrotime;
	}

	/**
	 *
	 * @return string
	 */
	final public function copyrightHolder(): string {
		return Kernel::copyrightHolder();
	}

	/**
	 * Register a factory function.
	 *
	 * @param string $code
	 * @param callable|Closure $callable $callable
	 * @return callable|Closure|null
	 */
	final public function registerFactory(string $code, callable|Closure $callable): null|callable|Closure {
		return $this->_registerCallable($code . 'Factory', $callable);
	}

	/**
	 * Register a registry function.
	 * Returns previous factory registered if you want to use it.
	 *
	 * @param string $code
	 * @param callable|Closure $callable $callable
	 * @return callable|Closure|null
	 */
	final public function registerRegistry(string $code, callable|Closure $callable): null|callable|Closure {
		return $this->_registerCallable($code . 'Registry', $callable);
	}

	/**
	 * Register a manager function.
	 * Returns previous manager registered if you want to use it.
	 *
	 * @param string $code Function will be "${code}Manager"
	 * @param callable|Closure $callable $callable
	 * @return callable|Closure|null
	 */
	final public function registerManager(string $code, callable|Closure $callable): null|callable|Closure {
		return $this->_registerCallable($code . 'Manager', $callable);
	}

	/**
	 * Add support for generic extension calls
	 *
	 * @param string $code
	 * @param callable|Closure $callable
	 * @return null|callable|Closure
	 */
	private function _registerCallable(string $code, callable|Closure $callable): null|callable|Closure {
		$old_factory = $this->callables[$code] ?? null;
		$this->callables[$code] = $callable;
		$this->logger->debug('Adding factory for {code}', [
			'code' => $code,
		]);
		return $old_factory;
	}

	/**
	 * Support foo_factory and foo_registry calls
	 *
	 * @param string $name
	 *            Method called
	 * @param array $args
	 * @return object
	 * @throws NotFoundException
	 * @throws UnsupportedException
	 */
	final public function __call(string $name, array $args): mixed {
		if (isset($this->callables[$name])) {
			return call_user_func_array($this->callables[$name], $args);
		}
		foreach (['_module', 'Module'] as $suffix) {
			if (str_ends_with($name, $suffix)) {
				return $this->modules->object(substr($name, 0, -strlen($suffix)));
			}
		}

		throw new UnsupportedException("Application call {method} is not supported.\n\n\tCalled from: {calling}\n\nDo you ned to register the module which adds this functionality?\n\nAvailable: {available}", [
			'method' => $name, 'calling' => Kernel::callingFunction(),
			'available' => implode(', ', array_keys($this->callables)),
		]);
	}

	/**
	 * Clone application
	 */
	protected function __clone() {
		$this->configuration = clone $this->configuration;
		$this->loader = new Loader($this->includes(), new SettingsConfiguration($this->configuration));
		$this->router = clone $this->router;
	}

	/**
	 * @param Request $request
	 * @return Userlike
	 * @throws AuthenticationException
	 */
	private function sessionUser(Request $request): Userlike {
		$user = $this->requireSession($request)->user();
		assert($user instanceof Userlike);
		return $user;
	}

	/**
	 * Uses current application Request for authentication if not supplied.
	 *
	 * @param ?Request $request Request to use for session
	 * @param boolean $require Force object creation if not found. May have side effect of creating a Session_Interface within the Request.
	 * @return Userlike|null
	 * @throws ClassNotFound
	 * @throws KeyNotFound
	 * @throws SemanticsException
	 */
	public function user(Request $request = null, bool $require = true): Userlike|null {
		return $require ? $this->requireUser($request) : $this->optionalUser($request);
	}

	/**
	 * Uses current application Request for authentication if not supplied.
	 *
	 * @param ?Request $request Request to use for
	 * @return Userlike
	 * @throws ClassNotFound
	 * @throws KeyNotFound
	 * @throws SemanticsException
	 */
	public function requireUser(Request $request = null): Userlike {
		try {
			if (!$request) {
				$request = $this->request();
			}
			assert($request instanceof Request);
		} catch (SemanticsException) {
			return $this->userFactory();
		}

		try {
			return $this->sessionUser($request);
		} catch (AuthenticationException) {
			return $this->userFactory();
		}
	}

	/**
	 * Returns the current executing request. May be NULL if no request running.
	 *
	 * If performing sub-requests, this reflects the most-recent request state (a stack).
	 *
	 * @return Request
	 * @throws SemanticsException
	 */
	final public function request(): Request {
		$request = ArrayTools::last($this->requestStack);
		if ($request) {
			return $request;
		}

		throw new SemanticsException('No request');
	}

	/**
	 * The user class for the application. Should implement `zesk\Interface\Userlike`
	 */
	public const OPTION_USER_CLASS = 'userClass';

	/**
	 * @return Userlike
	 * @throws ClassNotFound
	 * @throws KeyNotFound
	 * @throws SemanticsException
	 */
	public function userFactory(): Userlike {
		$className = $this->optionString(self::OPTION_USER_CLASS);
		if (empty($className)) {
			throw new KeyNotFound('No userClass configured');
		}
		$user = $this->modelSingleton($className);
		assert($user instanceof Userlike);
		return $user;
	}

	/**
	 * Optionally fetch a user if authenticated
	 *
	 * @param Request|null $request
	 * @return Userlike|null
	 */
	public function optionalUser(Request $request = null): ?Userlike {
		try {
			$request = $request ?: $this->request();
		} catch (SemanticsException) {
			/* No session */
			return null;
		}
		$session = $this->optionalSession($request);

		try {
			$user = $session?->user();
			assert($user instanceof Userlike);
			return $user;
		} catch (AuthenticationException) {
			return null;
		}
	}
}
