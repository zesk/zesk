<?php
declare(strict_types=1);

/**
 * @package zesk
 * @subpackage core
 * @author kent
 * @copyright Copyright &copy; 2022, Market Acumen, Inc.
 */

namespace zesk;

use Closure;
use Psr\Cache\CacheItemPoolInterface;
use Throwable;
use zesk\Locale\Reader;
use zesk\ORM\User;
use zesk\Repository\Module as RepositoryModule;
use zesk\Router\Parser;
use zesk\Session\Module as SessionModule;
use function str_ends_with;

/**
 * Core web application object for Zesk.
 *
 * If you're doing something useful, it's probably a simple application.
 *
 * @author kent
 *
 * Methods below require you to actually load the modules for them to work.
 *
 * @method Widget widgetFactory(string $class, array $options = [])
 *
 * @method ORM\Module orm_module()
 * @method Module_Database database_module()
 * @method Module_Permission permission_module()
 * @method Module_Job job_module()
 * @method RepositoryModule repository_module()
 * @method zesk\Cron\Module cron_module()
 *
 * @method ORM\Module ormModule()
 * @method ORM\Class_Base class_ormRegistry(string $class)
 * @method ORM\ORMBase ormRegistry(string $class, mixed $mixed = null, array $options = [])
 * @method ORM\ORMBase ormFactory(string $class, mixed $mixed = null, array $options = [])
 *
 * @method Database database_registry(string $name = "", array $options = [])
 *
 * @method Database databaseRegistry(string $name = "", array $options = [])
 * @method Module_Database databaseModule()
 * @method Module_Permission permissionModule()
 * @method Module_Job jobModule()
 * @method Repository\Module repositoryModule()
 * @method zesk\Cron\Module cronModule()
 * @method Mail\Module mailModule()
 *
 * @method Interface_Session session_factory()
 * @method Interface_Session sessionFactory()
 * @method SessionModule sessionModule()
 */
class Application extends Hookable implements Interface_Member_Model_Factory, Interface_Factory {
	/**
	 * Default option to store application version - may be stored differently in overridden classes, use
	 *
	 * @see version()
	 * @var string
	 */
	public const OPTION_VERSION = 'version';

	/**
	 * Option with boolean value to enable theme debugging.
	 */
	public const OPTION_DEBUG_THEME = 'debugTheme';

	/**
	 * Debugging
	 */
	public const OPTION_DEBUG = 'debug';

	/**
	 * Value is a path where temporary files can be stored.
	 */
	public const OPTION_CACHE_PATH = 'cachePath';

	/**
	 *
	 */
	public const REQUEST_OPTION_SESSION = __CLASS__ . '::session';

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
	 * @var Hooks
	 */
	public Hooks $hooks;

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
	 * @var ?CacheItemPoolInterface
	 */
	public ?CacheItemPoolInterface $cache = null;

	/**
	 * Inherited directly from zesk\Kernel.
	 * Do not edit.
	 *
	 * @var Configuration
	 */
	public Configuration $configuration;

	/**
	 *
	 * @var Configuration_Loader
	 */
	public Configuration_Loader $loader;

	/**
	 * Inherited directly from zesk\Kernel.
	 * Do not edit.
	 *
	 * @var Logger
	 */
	public Logger $logger;

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
	 * @var ?Command
	 */
	public ?Command $command = null;

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
	 * Zesk singleton. Do not use anywhere but here.
	 *
	 * @var Kernel
	 */
	private Kernel $kernel;

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
	private array $request_stack = [];

	/**
	 * Array of calls to create stuff
	 *
	 * @var Closure[]
	 */
	private array $factories = [];

	/**
	 * Zesk Command paths for loading zesk-command.php commands
	 *
	 * @var array
	 */
	private array $zeskCommandPath = [];

	/**
	 * Paths to search for shared content
	 *
	 * @var string[]
	 */
	private array $sharePath = [];

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
	private string $document_prefix = '';

	/**
	 * Boolean
	 *
	 * @var boolean
	 */
	private bool $configuredWasRun = false;

	/**
	 *
	 * @var array
	 */
	private array $content_recursion = [];

	/**
	 * @var bool
	 */
	private bool $profile = false;

	/**
	 *
	 * @param Kernel $kernel Zesk kernel for core functionality
	 * @param array $options Options passed in by zesk\Kernel::create_application($options)
	 * @throws Exception_System|Exception_Class_NotFound
	 */
	public function __construct(Kernel $kernel, array $options = []) {
		parent::__construct($this, $options);
		$this->_initialize($kernel); /* throws Exception_System */
		$this->_initialize_fixme();
		$this->setOption('maintenance', $this->_load_maintenance());
	}

	/**
	 *
	 * @param Kernel $kernel
	 * @throws Exception_System
	 * @throws Exception_Class_NotFound
	 */
	protected function _initialize(Kernel $kernel): void {
		// Pretty much just copy object references over
		$this->kernel = $kernel;
		$this->paths = $kernel->paths;
		$this->hooks = $kernel->hooks;
		$this->autoloader = $kernel->autoloader;
		$this->configuration = $kernel->configuration;
		$this->cache = $kernel->cache;
		$this->logger = $kernel->logger;
		$this->classes = $kernel->classes;
		$this->objects = $kernel->objects;
		$this->modules = new Modules($this);
		$this->includes = [];

		$this->themes = new Themes();
		$this->themes->setVariables(['application' => $this]);
		/*
		 * Configuration loader
		 */
		$this->loader = new Configuration_Loader([], new Adapter_Settings_Configuration($this->configuration));

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
		// Find share files for Controller_Share (move to internal module)
		$this->sharePath = [];
		// Where to store temporary files
		$this->cachePath = '';
		// Where our web server is pointing to
		$this->document = '';
		// Web server has a hard-coded prefix
		$this->document_prefix = '';

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
			$this->_initializeDocumentRoot();
			$this->addZeskCommandPath($this->defaultZeskCommandPath());
			$this->addModulePath($this->defaultModulesPath());
			$this->addThemePath($this->defaultThemePath());
			$this->addSharePath($this->defaultSharePath(), 'zesk');
			$this->addLocalePath($this->defaultLocalePath());
		} catch (Exception_Directory_NotFound $e) {
			throw new Exception_System('Default paths broken {message}', ['message' => $e->getMessage()], 0, $e);
		}
	}

	/**
	 * Create a new Locale
	 *
	 * @param string $code
	 * @param array $extensions
	 * @param array $options
	 * @return Locale
	 * @throws Exception_Class_NotFound
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
	 * @throws Exception_Class_NotFound
	 */
	public function factory(string $class): object {
		$arguments = func_get_args();
		$class = array_shift($arguments);
		return $this->factoryArguments($class, $arguments);
	}

	/**
	 * @param string $class
	 * @param array $arguments
	 * @return object
	 * @throws Exception_Class_NotFound
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
	 * @throws Exception_Directory_NotFound
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
	 * @throws Exception_Directory_NotFound
	 */
	final public function setDocumentRoot(string $set, string $prefix = ''): self {
		if (!is_dir($set)) {
			throw new Exception_Directory_NotFound($set);
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
		$this->document_prefix = rtrim($set, '/');
		return $this;
	}

	/**
	 * @param array|string $add A path or array of paths containing zesk command files (PHP files)
	 * @return self
	 * @throws Exception_Directory_NotFound
	 */
	final public function addZeskCommandPath(array|string $add): self {
		foreach (toList($add) as $path) {
			if (!is_dir($path)) {
				throw new Exception_Directory_NotFound($path);
			}
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
		return $this->paths->zesk('zesk/Command');
	}

	/**
	 * Set the module search path
	 *
	 * @param string $add
	 * @return $this
	 * @throws Exception_Directory_NotFound
	 */
	final public function addModulePath(string $add): self {
		if (!is_dir($add)) {
			throw new Exception_Directory_NotFound($add);
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
	 * @throws Exception_Directory_NotFound
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
	 * Add the share path for this application - used to serve
	 * shared content via Controller_Share as well as populate automatically with files within the
	 * system.
	 *
	 * By default, it's /share/
	 *
	 * @param string $add
	 * @param string $name
	 * @return self
	 * @throws Exception_Directory_NotFound
	 */
	final public function addSharePath(string $add, string $name): self {
		if (!is_dir($add)) {
			throw new Exception_Directory_NotFound($add);
		}
		$this->sharePath[$name] = $add;
		return $this;
	}

	/**
	 *
	 * @return string
	 */
	private function defaultSharePath(): string {
		return $this->paths->zesk('share');
	}

	/**
	 * Add or retrieve the locale path for this application - used to load locales
	 *
	 * By default, it's ./etc/language/
	 * Must exist in the file system
	 *
	 * @param string $add Locale path to add
	 * @return $this
	 * @throws Exception_Directory_NotFound
	 */
	final public function addLocalePath(string $add): self {
		$add = $this->paths->expand($add);
		if (!is_dir($add)) {
			throw new Exception_Directory_NotFound($add);
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
		$this->factories = [];
		$this->modules = new Modules($this);
	}

	/**
	 * Load the maintenance JSON file
	 *
	 * @return array
	 */
	private function _load_maintenance(): array {
		$file = $this->maintenanceFile();
		if (!file_exists($file)) {
			$result = [
				'enabled' => false,
			];
		} else {
			try {
				$result = JSON::decode(file_get_contents($file));
			} catch (Exception_Parse) {
				$result = [
					'error' => 'Unable to parse maintenance file',
				];
			}
			$result = [
				'enabled' => true,
			] + $result;
		}
		return $result;
	}

	/**
	 * Return file, which when exists, puts the site into maintenance mode.
	 *
	 * Always a JSON file
	 *
	 * @return string
	 */
	private function maintenanceFile(): string {
		return $this->option('maintenance_file', $this->path('etc/maintenance.json'));
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
		$this->modules->shutdown();
		$this->locale->shutdown();
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
			$this->command->callHook('replaced_with', $set);
		}
		$this->command = $set;
		$this->callHook('command', $set);
		return $this;
	}

	/**
	 * Settings are stateful and should persist across process and server boundaries.
	 *
	 * @return Interface_Settings
	 * @throws Exception_Class_NotFound
	 */
	public function settings(): Interface_Settings {
		$result = $this->modelSingleton($this->optionString('settingsClass', Settings_FileSystem::class));
		assert($result instanceof Interface_Settings);
		return $result;
	}

	/**
	 * Override this in child classes to manipulate creation of these objects.
	 * Create objects which take the application as the first parameter, and handles passing that on.
	 *
	 * Also, optionally calls `zesk\Application::singleton_$class`
	 *
	 * @param string $class
	 * @return Model
	 * @throws Exception_Class_NotFound
	 */
	final public function modelSingleton(string $class): Model {
		$args = func_get_args();
		$args[0] = $this;
		$desired_class = $this->objects->resolve($class);
		$suffix = strtolower(PHP::cleanFunction($desired_class));
		$object = $this->callHookArguments("singleton_$suffix", $args);
		if ($object instanceof $desired_class) {
			return $object;
		}
		return $this->objects->singletonArguments($desired_class, $args);
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
	 * @throws Exception_Configuration
	 * @throws Exception_NotFound
	 * @throws Exception_Unsupported
	 */
	final public function configure(): self {
		$this->_configure();
		$this->_configured();
		$this->initializeRouter();
		return $this;
	}

	/**
	 * Complete configuration process
	 * @throws Exception_Configuration
	 * @throws Exception_NotFound
	 * @throws Exception_Unsupported
	 */
	private function _configure(): void {
		// Load hooks
		$this->hooks->registerClass($this->register_hooks);

		$application = $this;
		$this->hooks->add(Hooks::HOOK_EXIT, function () use ($application): void {
			$application->cache?->commit();
		}, [
			'last' => true,
		]);

		$this->configureCachePaths(); // Initial cache paths are set up

		$this->beforeConfigure();

		if ($this->profile) {
			$mtime = microtime(true);
			$this->_configureFiles();
			$this->kernel->profileTimer('_configure_files', microtime(true) - $mtime);
		} else {
			$this->_configureFiles();
		}
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
		$this->hooks->registerClass($classes);
		$this->classes->register($classes);
		return $this;
	}

	/**
	 */
	private function configureCachePaths(): void {
		$cache_path = $this->option(self::OPTION_CACHE_PATH, $this->paths->cache());
		$this->cachePath = $this->paths->expand($cache_path);
	}

	/**
	 * Run before configuration setup
	 */
	protected function beforeConfigure(): void {
	}

	/**
	 * Load configuration files
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

	public function configureFiles(array $files): void {
		$this->loader->appendFiles($files);
		$this->loader->load();
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
		return include $__file__;
	}

	/**
	 * @return void
	 */
	protected function configuredFiles(): void {
		/* Function called after files are loaded */
	}

	/**
	 * @return void
	 * @throws Exception_Configuration
	 * @throws Exception_NotFound
	 * @throws Exception_Unsupported
	 */
	private function loadOptionModules(): void {
		$modules = $this->optionArray('modules');
		if (count($modules) > 0) {
			$this->modules->loadMultiple($modules);
		}
		$this->optionAppend('modules-loaded', $modules);
		$this->setOption('modules', []);
	}

	/**
	 * Run fini
	 * @param bool $force
	 * @return boolean
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
	 */
	private function _configured(): void {
		// Now run all configurations: System, Modules, then Application
		Template::configured($this);
		$this->inheritConfiguration();
		$this->configuredHooks();
		$this->configureCachePaths();
		$this->afterConfigure();
		$this->configuredWasRun = true;
	}

	/**
	 */
	private function configuredHooks(): void {
		foreach ([Hooks::HOOK_DATABASE_CONFIGURE, Hooks::HOOK_CONFIGURED] as $hook) {
			$this->hooks->callArguments($hook, [$this, ]);
			$this->modules->allHookArguments($hook); // Modules
			$this->callHookArguments($hook); // Application level
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
	 * @throws Exception_Class_NotFound
	 * @throws Exception_Configuration
	 * @throws Exception_Directory_NotFound
	 * @throws Exception_Invalid
	 * @throws Exception_NotFound
	 * @throws Exception_Unsupported
	 * @see Application::configure
	 */
	public function reconfigure(): self {
		$this->hooks->call(Hooks::HOOK_RESET, $this);
		$this->_configure();
		$this->modules->reload();
		$this->_configured();
		return $this;
	}

	/**
	 *
	 * @param CacheItemPoolInterface $interface
	 * @return self
	 */
	final public function setCache(CacheItemPoolInterface $interface): self {
		$this->cache = $interface;
		$this->callHook('setCache');
		return $this;
	}

	/**
	 * Clear application cache
	 * @throws Exception_Directory_Permission
	 * @throws Exception_File_Permission
	 * @throws Exception_Class_NotFound
	 */
	final public function cacheClear(): void {
		foreach (array_unique([
			$this->paths->cache(), $this->cachePath(),
		]) as $path) {
			if (empty($path)) {
				continue;
			}
			$size = Directory::size($path);
			if ($size > 0) {
				try {
					Directory::deleteContents($path);
				} catch (Exception_Directory_NotFound) {
					continue;
				}
				$this->logger->notice('Deleted {size} bytes in {path}', compact('size', 'path'));
			} else {
				$this->logger->notice('{path} is empty.', compact('size', 'path'));
			}
		}
		$this->callHook('cache_clear');
		$hooks = $this->modules->listAllHooks('cache_clear');
		$this->logger->notice('Running {cache_clear_hooks}', [
			'cache_clear_hooks' => $this->format_hooks($hooks),
		]);
		$this->modules->allHook('cache_clear', $this);
		$controllers = $this->controllers();
		foreach ($controllers as $controller) {
			$controller->callHook('cache_clear');
		}
	}

	/**
	 * Get the cache path for the application
	 *
	 * @param string|array $suffix
	 * @return string
	 */
	final public function cachePath(string|array $suffix = ''): string {
		return path($this->cachePath, $suffix);
	}

	private function format_hooks(array $hooks): array {
		$result = [];
		foreach ($hooks as $hook) {
			$result[] = $this->hooks->callable_string($hook);
		}
		return $result;
	}

	/**
	 * Return all known Controllers for the application.
	 *
	 * Potentially slow.
	 *
	 * @return Controller[]
	 * @throws Exception_Class_NotFound
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
		$this->callHook('router');
		$this->modules->allHook('routes', $this->router);
		$this->callHook('router_loaded', $this->router);
	}

	/**
	 * @return bool
	 */
	final public function maintenance(): bool {
		return toBool($this->optionPath(['maintenance', 'enabled'], false));
	}

	/**
	 * Set maintenance flag, this generally affects an application's interface
	 *
	 * @param bool $set
	 * @return boolean
	 */
	final public function setMaintenance(bool $set): bool {
		$maintenance_file = $this->maintenanceFile();
		$result = $this->callHookArguments('maintenance', [
			$set,
		], true);
		if (!$result) {
			$this->logger->error('Unable to set application {application_class} maintenance mode to {value}', [
				'application_class' => get_class($this), 'value' => $set ? 'true' : 'false',
			]);
			return false;
		}
		if ($set) {
			$context = [
				'time' => date('Y-m-d H:i:s'),
			] + toArray($this->callHookArguments('maintenance_context', [
				[
					'value' => true,
				],
			], []));

			try {
				file_put_contents($this->maintenanceFile(), JSON::encode($context));
			} catch (Exception_Semantics) {
			}
		} elseif (file_exists($maintenance_file)) {
			unlink($maintenance_file);
			clearstatcache(true, $maintenance_file);
		}
		return $result;
	}

	/**
	 * Utility for index.php file for all public-served content.
	 * @throws Exception_Semantics
	 */
	final public function content(string $path): string {
		if (isset($this->content_recursion[$path])) {
			return '';
		}
		$this->content_recursion[$path] = true;
		$this->callHook('content');

		$url = 'http://localhost/';

		try {
			$url = rtrim(URL::left_host($url), '/') . $path;
		} catch (Exception_Syntax) {
		}

		try {
			$request = $this->requestFactory();
		} catch (Exception_Parse $e) {
			throw new Exception_Semantics($e->getMessage(), $e->variables(), $e->getCode(), $e);
		}

		try {
			$request->initializeFromSettings([
				'url' => $url, 'method' => HTTP::METHOD_GET, 'data' => '', 'variables' => URL::queryParseURL($path),
			]);
		} catch (Exception_File_NotFound|Exception_Parameter|Exception_Parse) {
			/* No files passed, not ever thrown */
		}
		$response = $this->main($request);
		ob_start();

		try {
			$response->output([
				'skip_headers' => true,
			]);
		} catch (Exception_Semantics) {
		}
		$content = ob_get_clean();
		unset($this->content_recursion[$path]);
		return $content;
	}

	/**
	 * Creates a default request for the application. Useful in self::main
	 *
	 * Returns generic GET http://console/ when in the console.
	 *
	 * @param Request|null $inherit
	 * @return Request
	 * @throws Exception_Parse
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
		} catch (Exception_File_NotFound|Exception_Parameter) {
			// pass
		}
		return $request;
	}

	/**
	 * Console status getter
	 *
	 * @return boolean
	 */
	public function console(): bool {
		return $this->kernel->console();
	}

	/**
	 * @param Request $request
	 * @return Response
	 * @throws Exception_Semantics
	 */
	public function main(Request $request): Response {
		try {
			$response = $this->callHook('main', $request);
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
	 * @throws Exception_NotFound
	 */
	private function determineRoute(Request $request): Route {
		$router = $this->router();
		$this->logger->debug('App bootstrap took {seconds} seconds', [
			'seconds' => sprintf('%.3f', microtime(true) - $this->kernel->initialization_time),
		]);
		$this->themes->setVariables([
			'locale' => $this->locale, 'application' => $this, 'router' => $router, 'request' => $request,
		]);

		try {
			$route = $router->matchRequest($request);
		} catch (Exception_NotFound) {
			$this->routeNotFound($request);

			throw new Exception_NotFound('The resource does not exist on this server: {url}', $request->urlComponents(), HTTP::STATUS_FILE_NOT_FOUND);
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
		$this->callHookArguments('route_no_match', [$request]);
	}

	/**
	 * @param Request $request
	 * @param Route $route
	 * @return Route
	 */
	protected function routeFound(Request $request, Route $route): Route {
		$new_route = $this->callHookArguments('router_matched', [
			$request, $route,
		]);
		if ($new_route instanceof Route) {
			$route = $new_route;
		}
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
		$this->request_stack[] = $request;
		$request->setOption('stack_index', count($this->request_stack));
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
			if (!$exception instanceof Exception_Redirect) {
				$this->hooks->call('exception', $exception);
			}
			$this->callHook('mainException', $exception, $response);
		} catch (Exception_Redirect $e) {
			$response->redirect()->handleException($e);
		}
		return $response;
	}

	/**
	 *
	 * @param Request $request
	 * @param null|string $content_type
	 * @return Response
	 */
	final public function responseFactory(Request $request, string $content_type = null): Response {
		return Response::factory($this, $request, $content_type ? [
			'content_type' => $content_type,
		] : []);
	}

	/**
	 * @param Request $request
	 * @return self
	 * @throws Exception_Semantics
	 */
	final public function popRequest(Request $request): self {
		$starting_depth = $request->optionInt('stack_index');
		$ending_depth = count($this->request_stack);
		if ($ending_depth !== $starting_depth) {
			throw new Exception_Semantics('Request ending depth mismatch start {starting_depth} !== end {ending_depth}', [
				'starting_depth' => $starting_depth, 'ending_depth' => $ending_depth,
			]);
		}
		if ($ending_depth !== 0) {
			$popped = array_pop($this->request_stack);
			if ($popped !== $request) {
				throw new Exception_Semantics('Request changed between push and pop? {original} => {popped}', [
					'original' => $request->variables(), 'popped' => $popped->variables(),
				]);
			}
		}
		return $this;
	}

	/**
	 * Get a list of repositories for this application (dependencies)
	 *
	 * @return array
	 */
	public function repositories(): array {
		$repos = [
			'zesk' => $this->zeskHome(), get_class($this) => $this->path(),
		];
		return $this->callHookArguments('repositories', [
			$repos,
		], $repos);
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
	 * @throws Exception_Parse
	 * @throws Exception_Semantics
	 */
	public function index(): Response {
		$final_map = [];

		$request = $this->requestFactory();
		$this->callHook('request', $request);
		$options = [];
		if (($response = Response::cached($this->cache, $url = $request->url())) === null) {
			$response = $this->main($request);
			$response->cacheSave($this->cache, $url);
			$final_map['{page-is-cached}'] = '0';
		} else {
			$options['skip_hooks'] = true;
			$this->hooks->unhook('exit');
			$final_map['{page-is-cached}'] = '1';
		}
		$final_map += [
			'{page-render-time}' => sprintf('%.3f', microtime(true) - $this->kernel->initialization_time),
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

	/**
	 * @param Request $request
	 * @param Response $response
	 * @return void
	 */
	public function beforeOutput(Request $request, Response $response): void {
		// pass
	}

	/**
	 * Retrieve the share path for this application, a mapping of prefixes to paths
	 *
	 * By default, it's /share/
	 *
	 * @return array
	 *
	 * for example, returns a value:
	 *
	 *  `[ "home" => "/publish/app/api/modules/home/share/" ]`
	 */
	final public function sharePath(): array {
		return $this->sharePath;
	}

	/**
	 * Setter for locale - calls hook
	 *
	 * @param Locale $set
	 * @return self
	 */
	public function setLocale(Locale $set): self {
		$this->locale = $set;
		$this->callHook('set_locale', $set);
		return $this;
	}

	/**
	 * Create a `zesk\Locale` if it has not been encountered in this process and cache it as part of the `Application`
	 *
	 * @param string $code
	 * @param array $extensions
	 * @param array $options
	 * @return Locale
	 * @throws Exception_Class_NotFound
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
	 * @throws Exception_Directory_NotFound
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
	 * @throws Exception_Semantics
	 */
	public function command(): Command {
		if ($this->command) {
			return $this->command;
		}

		throw new Exception_Semantics('No command set');
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
		return $this->kernel->applicationClass();
	}

	/**
	 *
	 * @param string $path
	 * @return self
	 */
	final public function setApplicationRoot(string $path): self {
		$this->paths->setApplication($path);
		return $this;
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
		return path($this->document, $suffix);
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
		return $this->document_prefix;
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
		return $this->optionBool('development');
	}

	/**
	 * Set the development status of this application
	 *
	 * @param boolean $set Set value
	 * @return self
	 */
	public function setDevelopment(bool $set): self {
		return $this->setOption('development', toBool($set));
	}

	/**
	 * Create a model
	 *
	 * @param string $class
	 * @param mixed|null $mixed
	 * @param array $options
	 * @return Model
	 * @throws Exception_Class_NotFound
	 */
	public function modelFactory(string $class, mixed $mixed = null, array $options = []): Model {
		return Model::factory($this, $class, $mixed, $options);
	}

	/**
	 * Create a model
	 *
	 * @param string $member
	 * @param string $class
	 * @param mixed|null $mixed
	 * @param array $options
	 * @return Model
	 * @throws Exception_Class_NotFound
	 */
	public function memberModelFactory(string $member, string $class, mixed $mixed = null, array $options = []): Model {
		return Model::factory($this, $class, $mixed, [
			'_member' => $member,
		] + $options);
	}

	/**
	 *
	 * @param Request $request
	 * @param bool $require
	 * @return Interface_Session|null
	 */
	public function session(Request $request, bool $require = true): ?Interface_Session {
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
	 * @return Interface_Session
	 */
	public function requireSession(Request $request): Interface_Session {
		$session = $this->requestGetSession($request);
		return $session ?: $this->sessionRequestFactory($request);
	}

	/**
	 * @param Request $request
	 * @return Interface_Session|null
	 */
	private function requestGetSession(Request $request): ?Interface_Session {
		if ($request->hasOption(self::REQUEST_OPTION_SESSION)) {
			$session = $request->option(self::REQUEST_OPTION_SESSION);
			if ($session instanceof Interface_Session) {
				return $session;
			}
		}
		return null;
	}

	/**
	 * Create a session and attach it to the request
	 *
	 * @param Request $request
	 * @return Interface_Session
	 */
	private function sessionRequestFactory(Request $request): Interface_Session {
		$session = $this->sessionFactory();
		$session->initializeSession($request);
		$request->setOption(self::REQUEST_OPTION_SESSION, $session);
		return $session;
	}

	/**
	 * Get a session if it has been created already
	 *
	 * @param Request $request
	 * @return ?Interface_Session
	 */
	public function optionalSession(Request $request): ?Interface_Session {
		return $this->requestGetSession($request);
	}

	/**
	 * Throws exception if configured to do so; otherwise logs it or ignores it.
	 *
	 * @param string $message
	 * @param array $arguments
	 * @return void
	 * @throws Exception_Deprecated
	 */
	public function deprecated(string $message = '', array $arguments = []): void {
		$arguments['depth'] = toInteger($arguments['depth'] ?? 0) + 1;
		$this->kernel->deprecated($message, $arguments);
	}

	/**
	 * Console status getter/setter
	 *
	 * @param boolean $set
	 * @return self
	 */
	public function setConsole(bool $set): self {
		$this->kernel->setConsole($set);
		return $this;
	}

	/**
	 *
	 * @return float Microseconds initialization time
	 */
	final public function initializationTime(): float {
		return $this->kernel->initialization_time;
	}

	/**
	 *
	 * @return string
	 */
	final public function kernelCopyrightHolder(): string {
		return $this->kernel->copyrightHolder();
	}

	/**
	 * Register a factory function.
	 *
	 * @param string $code
	 * @param callable|Closure $callable $callable
	 * @return callable|Closure|null
	 */
	final public function registerFactory(string $code, callable|Closure $callable): null|callable|Closure {
		// Ideally this method will become deprecated
		$this->_registerFactory($code . '_factory', $callable);
		// camelCase Factory method
		return $this->_registerFactory($code . 'Factory', $callable);
	}

	/**
	 * Add support for generic extension calls
	 *
	 * @param string $code
	 * @param callable|Closure $callable
	 * @return null|callable|Closure
	 */
	private function _registerFactory(string $code, callable|Closure $callable): null|callable|Closure {
		$old_factory = $this->factories[$code] ?? null;
		$this->factories[$code] = $callable;
		$this->application->logger->debug('Adding factory for {code}', [
			'code' => $code,
		]);
		return $old_factory;
	}

	/**
	 * Register a factory function.
	 * Returns previous factory registered if you want to use it.
	 *
	 * @param string $code
	 * @param callable|Closure $callable $callable
	 * @return callable|Closure|null
	 */
	final public function registerRegistry(string $code, callable|Closure $callable): null|callable|Closure {
		$this->_registerFactory($code . '_registry', $callable);
		return $this->_registerFactory($code . 'Registry', $callable);
	}

	/**
	 * Support foo_factory and foo_registry calls
	 *
	 * @param string $name
	 *            Method called
	 * @param array $args
	 * @return object
	 * @throws Exception_NotFound
	 * @throws Exception_Unsupported
	 */
	final public function __call(string $name, array $args): mixed {
		if (isset($this->factories[$name])) {
			array_unshift($args, $this);
			return call_user_func_array($this->factories[$name], $args);
		}
		foreach (['_module', 'Module'] as $suffix) {
			if (str_ends_with($name, $suffix)) {
				return $this->modules->object(substr($name, 0, -strlen($suffix)));
			}
		}

		throw new Exception_Unsupported("Application call {method} is not supported.\n\n\tCalled from: {calling}\n\nDo you ned to register the module which adds this functionality?\n\nAvailable: {available}", [
			'method' => $name, 'calling' => calling_function(),
			'available' => implode(', ', array_keys($this->factories)),
		]);
	}

	/**
	 * Clone application
	 */
	protected function __clone() {
		$this->configuration = clone $this->configuration;
		$this->loader = new Configuration_Loader($this->includes(), new Adapter_Settings_Configuration($this->configuration));
		$this->router = clone $this->router;
	}

	/**
	 *
	 * @return void
	 * @throws Exception_NotFound
	 * @throws Exception_Syntax
	 */
	protected function hook_router(): void {
		$router_file = File::setExtension($this->file, 'router');
		$exists = is_file($router_file);
		$cache = $this->optionBool('cache_router');

		if (!$exists) {
			$this->logger->debug('No router file {router_file} to load - router is blank', [
				'router_file' => $router_file,
			]);
		} else {
			$mtime = strval(filemtime($router_file));

			$router = $this->router;

			try {
				$result = $router->cached($mtime);
			} catch (Exception_NotFound) {
				$parser = new Parser(file_get_contents($router_file), $router_file);
				$parser->execute($router, [
					'_source' => $router_file,
				]);
				if ($cache) {
					$router->cache($mtime);
				}
				$result = $router;
			}
			$this->router = $result;
		}
	}

	/**
	 * @param Request $request
	 * @return User|null
	 * @throws Exception_Authentication
	 */
	private function sessionUser(Request $request): ?User {
		$user = $this->requireSession($request)->user();
		assert($user instanceof User);
		return $user;
	}

	/**
	 * Uses current application Request for authentication if not supplied.
	 *
	 * @param ?Request $request Request to use for session
	 * @param boolean $require Force object creation if not found. May have side effect of creating a Session_Interface within the Request.
	 * @return User|null
	 * @throws Exception_Class_NotFound
	 */
	public function user(Request $request = null, bool $require = true): User|null {
		return $require ? $this->requireUser($request) : $this->optionalUser($request);
	}

	/**
	 * Uses current application Request for authentication if not supplied.
	 *
	 * @param ?Request $request Request to use for
	 * @return User
	 * @throws Exception_Class_NotFound
	 */
	public function requireUser(Request $request = null): User {
		try {
			if (!$request) {
				$request = $this->request();
			}
			assert($request instanceof Request);
		} catch (Exception_Semantics) {
			return $this->userFactory();
		}

		try {
			return $this->sessionUser($request);
		} catch (Exception_Authentication) {
			return $this->userFactory();
		}
	}

	/**
	 * Returns the current executing request. May be NULL if no request running.
	 *
	 * If performing sub-requests, this reflects the most-recent request state (a stack).
	 *
	 * @return Request
	 * @throws Exception_Semantics
	 */
	final public function request(): Request {
		$request = last($this->request_stack);
		if ($request) {
			return $request;
		}

		throw new Exception_Semantics('No request');
	}

	/**
	 * @return User
	 * @throws Exception_Class_NotFound
	 */
	public function userFactory(): User {
		$user = $this->modelSingleton(User::class);
		assert($user instanceof User);
		return $user;
	}

	/**
	 * Optionally fetch a user if authenticated
	 *
	 * @param Request|null $request
	 * @return User|null
	 */
	public function optionalUser(Request $request = null): ?User {
		try {
			$request = $request ?: $this->request();
		} catch (Exception_Semantics) {
			/* No session */
			return null;
		}
		$session = $this->optionalSession($request);

		try {
			$user = $session?->user();
			assert($user instanceof User);
			return $user;
		} catch (Exception_Authentication) {
			return null;
		}
	}
}
