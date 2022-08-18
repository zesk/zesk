<?php
declare(strict_types=1);

/**
 * @package zesk
 * @subpackage core
 * @author kent
 * @copyright Copyright &copy; 2022, Market Acumen, Inc.
 */

namespace zesk;

use Psr\Cache\CacheItemPoolInterface;
use zesk\Locale\Reader;
use zesk\Router\Parser;

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
 * @method Module_ORM orm_module()
 * @method Class_ORM class_ormRegistry(string $class)
 * @method ORM ormRegistry(string $class, mixed $mixed = null, array $options = [])
 * @method ORM ormFactory(string $class, mixed $mixed = null, array $options = [])
 *
 * @method Database database_registry($name = null)
 * @method Module_Database database_module()
 * @method Module_Permission permission_module()
 * @method Module_Job job_module()
 * @method Module_Repository repository_module()
 * @method Cron\Module cron_module()
 *
 * @method Interface_Session session_factory()
 */
class Application extends Hookable implements Interface_Theme, Interface_Member_Model_Factory, Interface_Factory {
	/**
	 * Default option to store application version - may be stored differently in overridden classes, use
	 *
	 * @see version()
	 * @var string
	 */
	public const OPTION_VERSION = 'version';

	/**
	 * Zesk singleton. Do not use anywhere but here.
	 *
	 * @var Kernel
	 */
	private Kernel $kernel;

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
	 * @var ?Configuration_Loader
	 */
	public ?Configuration_Loader $loader = null;

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
	 * @var Locale[]
	 */
	private array $locales = [];

	/**
	 *
	 * @var ?Command
	 */
	public ?Command $command = null;

	/**
	 *
	 * @var ?Router
	 */
	public ?Router $router = null;

	/**
	 * List of search paths to find modules for loading
	 *
	 * @var string[]
	 */
	private array $module_path = [];

	/**
	 * Modules object interface
	 *
	 * @var Modules
	 */
	public Modules $modules;

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
	 * File where the application class resides.
	 * Override this in subclasses with
	 * public $file = __FILE__;
	 *
	 * @var string
	 */
	public string $file = '';

	/**
	 *
	 * @var Request[]
	 */
	private array $request_stack = [];

	/**
	 * @deprecated 2018-01
	 * @var ?Request
	 */
	protected ?Request $request = null;

	/**
	 *
	 * @deprecated 2018-01
	 * @var ?Response
	 */
	protected ?Response $response = null;

	/**
	 * @deprecated 2018-01
	 * @var ?Interface_Session
	 */
	public ?Interface_Session $session = null;

	/**
	 * @deprecated 2018-01
	 * @var ?User
	 */
	public ?User $user = null;

	/**
	 * Array of calls to create stuff
	 *
	 * @var Closure[]
	 */
	private array $factories = [];

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
	 * Configuration options
	 *
	 * @var array
	 */
	private array $configuration_options = [];

	/**
	 * Configuration options
	 *
	 * @var array
	 */
	protected array $template_variables = [];

	/**
	 * Zesk Command paths for loading zesk-command.php commands
	 *
	 * @var array
	 */
	protected array $zesk_command_path = [];

	/**
	 * Paths to search for themes
	 *
	 * @var array $theme_path
	 */
	protected array $theme_path = [];

	/**
	 * Paths to search for shared content
	 *
	 * @var string[]
	 */
	protected array $share_path = [];

	/**
	 * Paths to search for locale files
	 *
	 * @var string[]
	 */
	protected array $locale_path = [];

	/**
	 *
	 * @var string
	 */
	protected string $cache_path = '';

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
	 *
	 * @var string
	 */
	private string $document_cache = '';

	/**
	 * Top template
	 *
	 * @var Template
	 */
	public Template $template;

	/**
	 * Template stack. public so it can be copied in Template::__construct
	 *
	 * @see Template::__construct
	 * @var Template_Stack
	 */
	public Template_Stack $template_stack;

	/**
	 *
	 * @var string[]
	 */
	private array $theme_stack = [];

	/**
	 * Boolean
	 *
	 * @var boolean
	 */
	private bool $configured_was_run = false;

	/**
	 *
	 * @var array
	 */
	private array $content_recursion = [];

	/**
	 *
	 * @param Kernel $kernel Zesk kernel for core functionality
	 * @param array $options Options passed in by zesk\Kernel::create_application($options)
	 */
	public function __construct(Kernel $kernel, array $options = []) {
		parent::__construct($this, $options);
		$this->_initialize($kernel);
		$this->_initialize_fixme();
		$this->setOption('maintenance', $this->_load_maintenance());
	}

	/**
	 * The unique ID name for this application, used for cron or identifying multiple instances of a single application on a site.
	 *
	 * @return string
	 */
	public function id() {
		return get_class($this);
	}

	/**
	 *
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

		/*
		 * Current process interface. Depends on ->hooks
		 */
		$this->process = new Process($this);

		/*
		 * Speaka-da-language?
		 */
		$this->locale = $this->localeFactory();

		/*
		 * Where various things can be found
		 */
		// Find modules here
		$this->module_path = [];
		// Find Zesk commands here
		$this->zesk_command_path = [];
		// Find theme files here
		$this->theme_path = [];
		// Find share files for Controller_Share (move to internal module)
		$this->share_path = [];
		// Where to store temporary files
		$this->cache_path = '';
		// Where our web server is pointing to
		$this->document = '';
		// Web server has a hard-coded prefix
		$this->document_prefix = '';
		// Directory where we can store web-accessible resources
		$this->document_cache = '';

		$this->configured_was_run = false;

		$this->command = null;
		$this->router = null;

		// $this->load_modules is set in subclasses
		// $this->class_aliases is set in subclasses
		// $this->file is set in subclasses
		// $this->register_hooks is set in subclasses
		//

		// $this->includes is set in subclasses?
		// $this->template_variables is set in application itself?
		$this->template_variables = [];

		foreach ($this->class_aliases as $requested => $resolved) {
			$this->objects->map($requested, $resolved);
		}

		$this->_init_document_root();

		$this->zesk_command_path = [
			ZESK_ROOT . 'command' => 'zesk\Command_',
		];
		if (is_array($this->modules)) {
			throw new Exception_Unimplemented('Application::$modules no longer supported');
		}

		$this->module_path($this->path_module_default());

		// Variable state
		$this->template_stack = new Template_Stack();
		// Root template
		$this->template = new Template($this);
		$this->template_stack->push($this->template);
		// Stack of currently rendering themes
		$this->theme_stack = [];

		$this->theme_path($this->path_theme_default());
		$this->share_path($this->path_share_default(), 'zesk');
		$this->locale_path($this->path_locale_default());
	}

	/**
	 * Initialize part 2
	 */
	protected function _initialize_fixme(): void {
		// These two calls mess up reconfigure and do not reset state correctly.
		// Need a robust globals monitor to ensure reconfigure resets state back to default
		// Diffiult issue is class loader modifies state
		$this->factories = [];
		$this->modules = new Modules($this);
	}

	/**
	 *
	 * @return string
	 */
	private function path_module_default() {
		return $this->paths->zesk('modules');
	}

	/**
	 *
	 * @return string
	 */
	private function path_theme_default() {
		return $this->paths->zesk('theme');
	}

	/**
	 *
	 * @return string
	 */
	private function path_share_default() {
		return $this->paths->zesk('share');
	}

	/**
	 *
	 * @return string
	 */
	private function path_locale_default() {
		return $this->paths->zesk('etc/language');
	}

	/**
	 * Clone application
	 */
	protected function __clone() {
		$this->configuration = clone $this->configuration;
		if ($this->router) {
			$this->router = clone $this->router;
		}
		$this->template = clone $this->template;
		$this->template_stack = clone $this->template_stack;
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
	 *
	 * @param Command $set
	 * @return self
	 */
	public function setCommand(Command $set): self {
		if ($this->command) {
			if ($set === $this->command) {
				return $this;
			}
			$this->command->call_hook('replaced_with', $set);
		}
		$this->command = $set;
		$this->call_hook('command', $set);
		return $this;
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
	 * @return self
	 */
	public function setVersion(string $set): self {
		$this->setOption(self::OPTION_VERSION, $set);
		return $this;
	}

	/**
	 * Expand a list of include files
	 *
	 * @param array $includes
	 * @return string[]
	 */
	private function expandIncludes(array $includes) {
		$result = [];
		foreach ($includes as $include) {
			$expand = $this->paths->expand($include);
			$result[$expand] = $expand;
		}
		return $result;
	}

	/**
	 * Getter/setter to configure a file name to load (from path)
	 *
	 * Configuration files can use values which are expanded:
	 *
	 *     /etc/app.json Is absolute
	 *     ./etc/app.json Is application-root relative
	 *     ~/.app/app.json Is user-home relative
	 *
	 * @param mixed $includes An iterator which generates a list of include files to load.
	 * @param boolean $reset When false, adds to the existing include list
	 * @return Application|array
	 */
	final public function configureInclude(array $includes, bool $overwrite = true) {
		$includes = $this->expandIncludes($includes);
		if ($overwrite) {
			$this->includes = $includes;
		} else {
			$this->includes += $includes;
		}
		return $this;
	}

	/**
	 * Loads a bunch of configuration files, in the following order:
	 * 1. application.conf
	 * 2. honst/*uname*.conf
	 *
	 * Configuration files are simple bash-style NAME=VALUE files with a few features:
	 * - You can use variables in values, like ${FOO} or $FOO; once loaded, the variable is replaced
	 * and no longer part
	 * of the value.
	 * - Values are unquoted automatically, and assumed to be strings
	 * - Unquoted values are coerced to an internal PHP type, if possible
	 * - Loads from the list of ZESK_CONFIG_PATH global
	 * - Loads the files defined in ZESK_CONFIG_FILE global
	 * - If ZESK_CONFIG_PATH changes or INCLUDE changes in a configuration file load sequence, it
	 * continues to load
	 */
	final public function configure(array $options = []): self {
		$this->configuration_options = $options + toArray($this->configuration->path(__CLASS__)->configure_options);
		$this->_configure($this->configuration_options);
		return $this;
	}

	/**
	 * Run preconfiguration setup
	 */
	protected function preconfigure(array $options) {
		return $options;
	}

	/**
	 * Run post configuration setup
	 */
	protected function postconfigure(): void {
	}

	/**
	 * Load configuration files
	 *
	 * @param array $options
	 */
	private function _configure_files(array $options): void {
		if (count($this->includes) === 0 || array_key_exists('file', $options)) {
			$this->configureInclude($options['includes'] ?? $options['file'] ?? $this->defaultConfigurationFiles());
		}
		$includes = $this->includes;
		$files = [];
		foreach ($includes as $index => $file) {
			$file = $this->paths->expand($file);
			if (File::isAbsolute($file)) {
				if (is_file($file)) {
					$files[] = $file;
				}
				unset($includes[$index]);
			}
		}

		$this->configureFiles($files);
	}

	public function configureFiles(array $files): void {
		if (!$this->loader) {
			$this->loader = new Configuration_Loader($files, new Adapter_Settings_Configuration($this->configuration));
		} else {
			$this->loader->append_files($files);
		}
		$this->loader->load();
	}

	/**
	 * Complete configuration process
	 *
	 * @param array $options
	 */
	private function _configure(array $options): void {
		$skip_configured_hook = $options['skip_configured'] ?? false;

		// Load hooks
		$this->hooks->registerClass($this->register_hooks);

		$application = $this;
		$this->hooks->add(Hooks::HOOK_EXIT, function () use ($application): void {
			if ($application->cache) {
				$application->cache->commit();
			}
		}, [
			'last' => true,
		]);

		$this->configureCachePaths(); // Initial cache paths are set up

		$new_options = $this->preconfigure($options);
		if (is_array($new_options)) {
			$options = $new_options;
		}

		$profile = $this->optionBool('profile');
		if ($profile) {
			$mtime = microtime(true);
			// Old conf:: version (PHP5)
			// 0.011868953704834
			// 0.012212038040161
			// New conf/PHP7
			// 0.0060791969299316
		}
		$this->_configure_files($options);
		if ($profile) {
			$this->kernel->profile_timer('_configure_files', microtime(true) - $mtime);
		}

		// Apply settings from loaded configuration to make settings available to hook_configured_files
		$this->inheritConfiguration();
		if ($this->has_hook('configured_files')) {
			$this->call_hook('configured_files');
			// Repopulate Application options after final configurations are loaded
			$this->inheritConfiguration();
		}
		$this->modules->load($this->load_modules);

		$this->loadOptionModules();

		// Final cache paths are set up from application options

		if (!$skip_configured_hook) {
			$this->configured();
		}
	}

	protected function loadOptionModules(): void {
		$modules = $this->optionArray('modules');
		if (count($modules) > 0) {
			$this->modules->load($modules);
		}
		$this->optionAppend('modules-loaded', $modules);
		$this->setOption('modules', []);
	}

	/**
	 *
	 * @return boolean
	 */
	public function isConfigured(): bool {
		return $this->configured_was_run;
	}

	/**
	 * Run fini
	 * @param bool $force
	 * @return boolean
	 */
	public function configured(bool $force = false): bool {
		if ($force || !$this->configured_was_run) {
			$this->_configured();
			return true;
		}
		return false;
	}

	protected array $options_inherit_append = ['modules'];

	private function _configured(): void {
		// Now run all configurations: System, Modules, then Application
		$this->inheritConfiguration();
		$this->loadOptionModules();
		$this->configured_hooks();
		$this->configureCachePaths();
		$this->postconfigure();

		$this->configured_was_run = true;
	}

	/**
	 */
	private function configureCachePaths(): void {
		$cache_path = $this->option('cache_path', $this->paths->cache());
		$this->cache_path = Directory::isAbsolute($cache_path) ? $cache_path : $this->path($cache_path);
		if ($this->hasOption('document_cache')) {
			$this->document_cache = $this->paths->expand($this->option('document_cache'));
		}
	}

	/**
	 */
	private function configured_hooks(): void {
		$hook_callback = $result_callback = null;
		foreach ([Hooks::HOOK_DATABASE_CONFIGURE, Hooks::HOOK_CONFIGURED] as $hook) {
			$this->hooks->call_arguments($hook, [
				$this,
			], null, $hook_callback, $result_callback);
			$this->modules->all_hook_arguments($hook, [], null, $hook_callback, $result_callback); // Modules
			$this->call_hook_arguments($hook, [], null, $hook_callback, $result_callback); // Application level
		}
	}

	/**
	 * Runs configuration again, using same options as previous configuration.
	 *
	 * @see Application::configure
	 */
	public function reconfigure() {
		$this->hooks->call(Hooks::HOOK_RESET, $this);
		$this->_configure(toArray($this->configuration_options));
		$this->modules->reload();
		$this->_configured();
		return $this;
	}

	/**
	 *
	 * @param CacheItemPoolInterface $interface
	 * @return \zesk\Application
	 */
	final public function setCache(CacheItemPoolInterface $interface) {
		$this->cache = $interface;
		$this->call_hook('set_cache');
		return $this;
	}

	/**
	 * Clear application cache
	 */
	final public function cacheClear(): void {
		foreach (array_unique([
			$this->paths->cache(),
			$this->cachePath(),
			$this->document_cache(),
		]) as $path) {
			if (empty($path)) {
				continue;
			}
			$size = Directory::size($path);
			if ($size > 0) {
				Directory::deleteContents($path);
				$this->logger->notice('Deleted {size} bytes in {path}', compact('size', 'path'));
			} else {
				$this->logger->notice('{path} is empty.', compact('size', 'path'));
			}
		}
		$this->call_hook('cache_clear');
		$hooks = $this->modules->all_hook_list('cache_clear');
		$this->logger->notice('Running {cache_clear_hooks}', [
			'cache_clear_hooks' => $this->format_hooks($hooks),
		]);
		$this->modules->all_hook('cache_clear', $this);
		$controllers = $this->controllers();
		foreach ($controllers as $controller) {
			$controller->call_hook('cache_clear');
		}
	}

	private function format_hooks(array $hooks) {
		$result = [];
		foreach ($hooks as $hook) {
			$result[] = $this->hooks->callable_string($hook);
		}
		return $result;
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
		$result = $this->call_hook_arguments('maintenance', [
			$set,
		], true);
		if (!$result) {
			$this->logger->error('Unable to set application {application_class} maintenance mode to {value}', [
				'application_class' => get_class($this),
				'value' => $set ? 'true' : 'false',
			]);
			return false;
		}
		if ($set) {
			$context = [
					'time' => date('Y-m-d H:i:s'),
				] + toArray($this->call_hook_arguments('maintenance_context', [
					[
						'value' => $set,
					],
				], []));
			file_put_contents($this->maintenanceFile(), JSON::encode($context));
		} elseif (file_exists($maintenance_file)) {
			unlink($maintenance_file);
			clearstatcache(true, $maintenance_file);
		}
		return $result;
	}

	/**
	 * Load the maintenance JSON file
	 *
	 * @return array
	 */
	private function _load_maintenance() {
		$file = $this->maintenanceFile();
		if (!file_exists($file)) {
			$result = [
				'enabled' => false,
			];
		} else {
			try {
				$result = JSON::decode(file_get_contents($file));
			} catch (Exception_Parse $e) {
				$result = [
					'error' => 'Unabe to parse maintenance file',
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
	private function maintenanceFile() {
		return $this->option('maintenance_file', $this->path('etc/maintenance.json'));
	}

	/**
	 *
	 * @param string $class
	 * @return object
	 * @deprecated 2017-12 use modelSingleton
	 */
	final public function objectSingleton($class) {
		$this->deprecated();
		$args = func_get_args();
		$args[0] = $this;
		$object = $this->call_hook_arguments("singleton_$class", $args, null);
		if ($object instanceof $class) {
			return $object;
		}
		return $this->objects->singletonArguments($class, $args);
	}

	/**
	 * Override this in child classes to manipulate creation of these objects.
	 * Creates objects which take the application
	 * as the first parameter, and handles passing that on.
	 *
	 * Also optionally calls `zesk\Application::singleton_$class`
	 *
	 * @param string $class
	 * @return Model
	 */
	final public function modelSingleton(string $class): Model {
		$args = func_get_args();
		$args[0] = $this;
		$suffix = strtolower(PHP::cleanFunction($desired_class = $this->objects->resolve($class)));
		$object = $this->call_hook_arguments("singleton_$suffix", $args, null);
		if ($object instanceof $desired_class) {
			return $object;
		}
		return $this->objects->singletonArguments($desired_class, $args);
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
	 * Creates a default reqeust for the application. Useful in self::main
	 *
	 * Returns generic GET http://console/ when in the console.
	 *
	 * @return Request
	 */
	public function requestFactory(Request $inherit = null): Request {
		$request = new Request($this);
		if ($inherit) {
			$request->initializeFromRequest($inherit);
		} elseif ($this->console()) {
			$request->initializeFromSettings('http://console/');
		} else {
			$request->initializeFromGlobals();
		}
		return $request;
	}

	/**
	 *
	 * @return Router
	 */
	protected function hook_router() {
		$router_file = File::extension_change($this->file, 'router');
		$exists = is_file($router_file);
		$cache = $this->option('cache_router', null);

		$router = Router::factory($this);
		if (!$exists) {
			$this->logger->debug('No router file {router_file} to load - creating blank router', [
				'router_file' => $router_file,
			]);
			$result = $router;
		} else {
			$mtime = filemtime($router_file);
			if (($result = $router->cached($mtime)) === null) {
				$parser = new Parser(file_get_contents($router_file), $router_file);
				$parser->execute($router, [
					'_source' => $router_file,
				]);
				if ($cache) {
					$router->cache($mtime);
				}
				$result = $router;
			}
		}
		$this->modules->all_hook('routes', $result);
		return $result;
	}

	/**
	 *
	 * @param Request $request
	 * @param string $content_type
	 * @return Response
	 */
	final public function responseFactory(Request $request, string $content_type = null): Response {
		return Response::factory($this, $request, $content_type ? [
			'content_type' => $content_type,
		] : []);
	}

	/**
	 *
	 * @param Request $request
	 * @param \Exception $exception
	 * @return Response
	 */
	private function mainException(Request $request, \Exception $exception): Response {
		$response = $this->responseFactory($request, Response::CONTENT_TYPE_HTML);

		try {
			$response->content = $this->theme($this->classes->hierarchy($exception), [
					'request' => $request,
					'response' => $response,
					'exception' => $exception,
					'content' => $exception,
				] + Exception::exceptionVariables($exception), [
				'first' => true,
			]);
			if (!$exception instanceof Exception_Redirect) {
				$this->hooks->call('exception', $exception);
			}
			$this->call_hook('mainException', $exception, $response);
		} catch (Exception_Redirect $e) {
			$response->redirect()->handle_exception($e);
		}
		return $response;
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
	 * @param Request $request
	 * @return self
	 */
	final public function pushRequest(Request $request): self {
		$this->request_stack[] = $request;
		$request->setOption('stack_index', count($this->request_stack));
		return $this;
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
				'starting_depth' => $starting_depth,
				'ending_depth' => $ending_depth,
			]);
		}
		if ($ending_depth !== 0) {
			$popped = array_pop($this->request_stack);
			if ($popped !== $request) {
				throw new Exception_Semantics('Request changed between push and pop? {origial} => {popped}', [
					'original' => $request->variables(),
					'popped' => $popped->variables(),
				]);
			}
		}
		return $this;
	}

	/**
	 * Load router
	 *
	 * @return Router NULL
	 */
	final public function router(): Router {
		if ($this->router instanceof Router) {
			return $this->router;
		}
		/* @var $router Router */
		$router = $this->router = $this->call_hook('router');
		$this->call_hook('router_loaded', $router);
		return $router;
	}

	/**
	 * Return all known/discerable Controllers for the application.
	 *
	 * Potentially slow.
	 *
	 * @return Controller[]
	 */
	final public function controllers(): array {
		return $this->router()->controllers($this);
	}

	/**
	 * Initialize variables
	 *
	 * @return void
	 */
	private function _templates_initialize(array $variables): void {
		$variables['application'] = $this;
		$variables += $this->template_variables;
		$variables += $this->call_hook_arguments('template_defaults', [
			$variables,
		], []);
		$this->template->set($variables);
	}

	/**
	 *
	 * @param Request $request
	 * @return Route
	 * @throws Exception_Key
	 * @throws Exception_NotFound
	 */
	private function determineRoute(Request $request): Route {
		$router = $this->router();
		$this->logger->debug('App bootstrap took {seconds} seconds', [
			'seconds' => sprintf('%.3f', microtime(true) - $this->kernel->initialization_time),
		]);
		$this->call_hook('router_prematch', $router, $request);
		$route = $router->match($request);
		$this->_templates_initialize([
			'router' => $router,
			'route' => $route,
			'request' => $request,
		]);
		if (!$route) {
			$this->call_hook('router_no_match', $request, $router);

			throw new Exception_NotFound('The resource does not exist on this server: {url}', $request->urlComponents(), Net_HTTP::STATUS_FILE_NOT_FOUND);
		}
		if ($this->optionBool('debug_route')) {
			$this->logger->debug('Matched route {class} Pattern: "{clean_pattern}" {options}', $route->variables());
		}
		$new_route = $this->call_hook_arguments('router_matched', [
			$request,
			$router,
			$route,
		], null);
		if ($new_route instanceof Route) {
			$route = $new_route;
		}
		return $route;
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
	 * @param Request $request
	 * @return Response
	 * @throws Exception_Semantics
	 */
	public function main(Request $request): Response {
		$starting_depth = count($this->request_stack);

		try {
			$response = $this->call_hook('main', $request);
			if ($response instanceof Response) {
				return $response;
			}
			$route = $this->pushRequest($request)->determineRoute($request);
			$response = $route->execute($request);
		} catch (\Exception $exception) {
			$response = $this->mainException($request, $exception);
		}
		$this->popRequest($request);
		return $response;
	}

	/**
	 * Utility for index.php file for all public-served content.
	 */
	final public function content(string $path): string {
		if (isset($this->content_recursion[$path])) {
			return '';
		}
		$this->content_recursion[$path] = true;
		$this->call_hook('content');

		$router = $this->router();
		$url = 'http://localhost/';
		$url = rtrim(URL::left_host($url), '/') . $path;
		$request = new Request($this);

		try {
			$request->initializeFromSettings([
				'url' => $url,
				'method' => Net_HTTP::METHOD_GET,
				'data' => '',
				'variables' => URL::queryParseURL($path),
			]);
		} catch (Exception_File_NotFound) {
			/* No files passed, not ever thrown */
		}
		$response = $this->main($request);
		ob_start();
		$response->output([
			'skip_headers' => true,
		]);
		$content = ob_get_clean();
		unset($this->content_recursion[$path]);
		return $content;
	}

	/**
	 * Get a list of repositories for this application (dependencies)
	 *
	 * @return array
	 */
	public function repositories() {
		$repos = [
			'zesk' => $this->zeskHome(),
			get_class($this) => $this->path(),
		];
		return $this->call_hook_arguments('repositories', [
			$repos,
		], $repos);
	}

	/**
	 * Utility for index.php file for all public-served content.
	 *
	 * KMD 2018-01 Made this more Response-centric and less content-centric
	 */
	public function index(): Response {
		$final_map = [];

		$request = $this->requestFactory();
		$this->call_hook('request', $request);
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
				'text/',
				'javascript',
			])) {
			$response->content = strtr($response->content, $final_map);
		}
		$response->output($options);
		return $response;
	}

	/**
	 * Template or logging variables
	 *
	 * @return Router
	 */
	public function variables(): array {
		$parameters['application'] = $this;
		$parameters['router'] = $this->router;
		// Do not include "request" in here as it may be NULL and it should NOT override subclass values
		return $parameters;
	}

	/**
	 * Retrieve the list of theme file paths, or add a path to be searched before existing paths
	 * (first in the list).
	 *
	 * @param string $add
	 *            (Optional) Path to add to the theme path. Pass in null to do nothing.
	 * @param string $prefix
	 *            (Optional) Handle theme requests which begin with this prefix. Saves having deep
	 *            directories.
	 * @return array The ordered list of paths to search for theme files as prefix => search list.
	 */
	final public function theme_path($add = null, $prefix = null) {
		if (is_bool($prefix)) {
			throw new Exception_Parameter('theme_path now takes a string as the 2nd parameter');
		}
		if (is_array($add)) {
			foreach ($add as $k => $v) {
				if (is_numeric($k)) {
					$this->theme_path($v);
				} else {
					$this->theme_path($v, $k);
				}
			}
			return $this->theme_path;
		}
		if ($add) {
			$prefix = strval($prefix);
			if (!isset($this->theme_path[$prefix])) {
				$this->theme_path[$prefix] = [];
			}
			if (!in_array($add, $this->theme_path[$prefix])) {
				array_unshift($this->theme_path[$prefix], $add);
			}
		}
		return $this->theme_path;
	}

	/**
	 * Search the theme paths for a target file
	 *
	 * @param string $file
	 * @param boolean $first
	 * @return mixed
	 */
	final public function theme_find($theme, array $options = []) {
		$extension = toBool($options['no_extension'] ?? false) ? '' : $this->option('theme_extension', '.tpl');
		$all = toBool($options['all'] ?? false);
		$theme = $this->cleanTemplatePath($theme) . $extension;
		$theme_path = $this->theme_path();
		$prefixes = array_keys($theme_path);
		usort($prefixes, fn ($a, $b) => strlen($b) - strlen($a));
		$result = [];
		foreach ($prefixes as $prefix) {
			if ($prefix === '' || str_starts_with($theme, $prefix)) {
				$suffix = substr($theme, strlen($prefix));
				foreach ($theme_path[$prefix] as $path) {
					$path = path($path, $suffix);
					if (file_exists($path)) {
						if (!$all) {
							return $path;
						}
						$result[] = $path;
					} else {
						$tried_path[] = $path;
					}
				}
			}
		}
		if (!$all && count($result) === 0) {
			return null;
		}
		return [
			$result,
			$tried_path,
		];
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
		return $this->share_path;
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
	 */
	final public function addSharePath(string $add, string $name): self {
		if (!is_dir($add)) {
			throw new Exception_Directory_NotFound($add);
		}
		$this->share_path[$name] = $add;
		return $this;
	}

	/**
	 * Setter for locale - calls hook
	 *
	 * @param Locale $set
	 * @return self
	 */
	public function setLocale(Locale $set) {
		$this->locale = $set;
		$this->call_hook('set_locale', $set);
		return $this;
	}

	/**
	 * Create a new `zesk\Locale`
	 *
	 * @param string $code
	 * @param array $extensions
	 * @param array $options
	 * @return self
	 */
	public function localeFactory(string $code = '', array $extensions = [], array $options = []): Locale {
		return Reader::factory($this->locale_path(), $code, $extensions)->locale($this, $options);
	}

	/**
	 * Create a `zesk\Locale` if it has not been encountered in this process and cache it as part of the `Application`
	 *
	 * @param string $code
	 * @param array $extensions
	 * @param array $options
	 * @return \zesk\Locale
	 */
	public function localeRegistry(string $code, array $extensions = [], array $options = []): Locale {
		$code = Locale::normalize($code);
		if (isset($this->locales[$code])) {
			return $this->locales[$code];
		}
		return $this->locales[$code] = $this->localeFactory($code, $extensions, $options);
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
		return $this->locale_path;
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
		$this->locale_path[] = $add;
		return $this;
	}

	/**
	 * Add or retrieve the data path for this application
	 *
	 * @param string $add
	 *            Value to set
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
	 * The default path is ZESK_ROOT 'classes/command', but applications can add their own tools
	 * upon initialization.
	 *
	 * This call always returns the complete path, even when adding. Note that adding a path which
	 * does not exist has no effect.
	 *
	 * @param mixed $add
	 *            A path or array of paths => prefixes to add. (Optional)
	 * @param string $prefix
	 *            The class prefix to add to found files in this directory (defaults to
	 *            "zesk\Command_")
	 * @return array
	 * @throws Exception_Directory_NotFound
	 * @global boolean debug.zesk_command_path Whether to log errors occurring during this call
	 */
	final public function zesk_command_path(string|array $add = null, string $prefix = null): array {
		if ($add !== null) {
			$this->application->deprecated('setter/getter');
			$this->append_zesk_command_path(toArray($add), $prefix === null ? "zesk\Command_" : $prefix);
		}
		return $this->zesk_command_path;
	}

	/**
	 * @param array $add
	 * @param string $prefix
	 * @return void
	 * @throws Exception_Directory_NotFound
	 */
	final public function append_zesk_command_path(array $add, string $prefix = "zesk\Command_"): void {
		$debug = $this->debug;
		foreach ($add as $path) {
			if (!is_dir($path)) {
				throw new Exception_Directory_NotFound($path);
			}
			if (!isset($this->zesk_command_path[$path])) {
				$this->zesk_command_path[$path] = $prefix;
			} elseif ($debug) {
				$this->logger->debug('{method}: did not add "{path}" (prefix {prefix}) because it already exists', [
					'method' => __METHOD__,
					'path' => $path,
					'prefix' => $prefix,
				]);
			}
		}
	}

	/**
	 *
	 * @return NULL|mixed
	 */
	final public function theme_current() {
		return last($this->theme_stack);
	}

	/**
	 * Get top theme variables state
	 *
	 * @return array
	 */
	final public function themeVariables(): array {
		return $this->template_stack->top()->variables();
	}

	/**
	 * Getter/setter for top theme variable
	 * @param string $name
	 * @return mixed
	 */
	final public function themeVariable(string $name): mixed {
		return $this->template_stack->top()->get($name);
	}

	/**
	 * Getter/setter for top theme variable
	 * @param string $name
	 * @param mixed $value
	 * @return self
	 */
	final public function setThemeVariable(string $name, mixed $value): self {
		$this->template_stack->top()->set($name, $value);
		return $this;
	}

	/**
	 * theme an element
	 *
	 * @param string|array $types
	 * @param array $arguments
	 * @param array $options
	 * @return string|null
	 * @throws Exception_Semantics
	 */
	final public function theme(string|array $types, array $arguments = [], array $options = []): ?string {
		if (!is_array($arguments)) {
			$arguments = [
				'content' => $arguments,
			];
		}
		$arguments['application'] = $this;
		$arguments['locale'] = $this->locale;

		$types = toList($types);
		$extension = ($options['no_extension'] ?? false) ? null : '.tpl';
		if (count($types) === 1) {
			$result = $this->_themeArguments($types[0], $arguments, null, $extension);
			if ($result === null) {
				$this->logger->warning('Theme {type} had no output', [
					'type' => $types[0],
				]);
			}
			return $result;
		}
		if (count($types) === 0) {
			return $option['default'] ?? null;
		}
		$type = array_shift($types);
		$arguments['content_previous'] = null;
		$has_output = false;
		$content = $this->_themeArguments($type, $arguments, null, $extension);
		if (!is_array($types)) {
			// Something's fucked.
			return $content;
		}
		if ($content !== null) {
			$arguments['content'] = $content;
			$has_output = true;
		}
		$first = $options['first'] ?? false;
		$concatenate = $options['concatenate'] ?? false;
		// 2019-01-15 PHP 7.2 $types converts to a string with value "[]" upon throwing a foreign Exception and rendering the theme
		while (is_countable($types) && count($types) > 0) {
			if ($first && !empty($content)) {
				break;
			}
			$type = array_shift($types);
			$content_previous = $content;
			$content_next = $this->_themeArguments($type, $arguments, $content, $extension);
			if ($content_next !== null) {
				$has_output = true;
			}
			$content = $concatenate ? $content . $content_next : $content_next;
			$arguments['content_previous'] = $content_previous;
			$arguments['content'] = $content;
		}
		if (!$has_output) {
			$this->logger->warning('Theme {types} had no output ({details})', [
				'types' => $types,
				'details' => _backtrace(),
			]);
		}
		return $content;
	}

	/**
	 * Convert from a theme name to a pathname
	 *
	 * @param string $path
	 * @return mixed
	 */
	private function cleanTemplatePath(string $path): string {
		return preg_replace('%[^-_./a-zA-Z0-9]%', '_', strtr(strtolower($path), [
			'_' => '/',
			'\\' => '/',
		]));
	}

	/**
	 * Invoke a single theme type
	 *
	 * @param string $type
	 * @param array $args
	 * @param string $content
	 *            Default content
	 * @return string
	 */
	private function _themeArguments(string $type, array $args, string $content = null, $extension = '.tpl'): ?string {
		if (!empty($extension) && $this->development() && ends($type, $extension)) {
			throw new Exception_Semantics('Theme called with .tpl suffix - not required {type}', compact('type'));
		}
		$type = strtolower($type);
		$this->theme_stack[] = $type;
		$t = new Template($this, $this->cleanTemplatePath($type) . $extension, $args);
		if ($t->exists()) {
			$content = $t->render();
		}
		array_pop($this->theme_stack);
		return $content;
	}

	/**
	 * Does one or more themes exist?
	 *
	 * @param mixed $types
	 *            List of themes
	 * @return boolean If all exist, returns true, otherwise false
	 */
	final public function theme_exists(array|string $types, array $args = []): bool {
		if (empty($types)) {
			return false;
		}
		foreach (toList($types) as $type) {
			if (!$this->_themeExists($type, $args)) {
				return false;
			}
		}
		return true;
	}

	/**
	 * Returns similar result as _theme_arguments except just tests to see if theme would
	 * possibly generate content
	 *
	 * @param mixed $type
	 * @param array $args
	 * @param array $options
	 * @return bool
	 */
	private function _themeExists(string $type, array $args) {
		$type = strtolower($type);
		$object = $args['content'] ?? null;
		if (is_object($object) && method_exists($object, 'hook_theme')) {
			return true;
		}
		// TODO is this called?
		if ($this->hooks->has("theme_${type}")) {
			return true;
		}
		if ($this->theme_find($type)) {
			return true;
		}
		return false;
	}

	/**
	 * Set autoload path for the application.
	 *
	 * @param mixed $add
	 * @param array $options
	 * @return array The ordered list of paths to search for class names
	 */
	final public function autoload_path($add = null, $options = true) {
		if ($add) {
			$this->application->deprecated(__METHOD__ . ' setter');
		}
		return $this->autoloader->path($add, $options);
	}

	/**
	 * Set autoload path for the application.
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
	 */
	final public function addAutoloadPath(string $add, array|bool|string $options = []): self {
		$this->autoloader->addPath($add, $options);
		return $this;
	}

	/**
	 * Get or set the command path for the application.
	 *
	 * @param mixed $add
	 * @param string $options
	 * @return array The ordered list of paths to search for class names
	 */
	final public function command_path($add = null) {
		return $this->paths->command($add);
	}

	/**
	 * Register a class with the application to make it discoverable and
	 * to register any hooks.
	 *
	 * @param string $class
	 * @return array This class name and parent classes
	 */
	final public function registerClass($class) {
		$this->hooks->registerClass($class);
		return $this->classes->register($class);
	}

	/**
	 * Return a path relative to the application root
	 */
	final public function path($suffix = null) {
		return $this->paths->application($suffix);
	}

	/**
	 * Return the application root path.
	 *
	 * @param string $suffix
	 *            Optional path to add to the application path
	 * @return string
	 */
	final public function applicationClass(): string {
		return $this->kernel->applicationClass();
	}

	/**
	 *
	 * @param string $path
	 * @return \zesk\Application
	 */
	final public function setApplicationRoot(string $path): self {
		$this->paths->setApplication($path);
		return $this;
	}

	/**
	 * Return the zesk home path, usually used to load built-in themes directly.
	 *
	 * @param string $suffix
	 *            Optional path to add to the application path
	 * @return string
	 */
	final public function zeskHome(string $suffix = ''): string {
		return $this->paths->zesk($suffix);
	}

	/**
	 * Get the cache path for the application
	 *
	 * @param string $suffix
	 * @return string
	 */
	final public function cachePath(string $suffix = ''): string {
		return path($this->cache_path, $suffix);
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
	private function _init_document_root(): void {
		$http_document_root = rtrim($_SERVER['DOCUMENT_ROOT'] ?? '', '/');
		if ($http_document_root) {
			$this->setDocumentRoot($http_document_root);
		}
		$this->document_cache = $this->document ? path($this->document, 'cache') : '';
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
	 * @param string $suffix ptionally append to web root
	 * @return string Path relative to document root
	 */
	final public function document_root($suffix = null) {
		return path($this->document, $suffix);
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
	 * @param string $set
	 * @return $this
	 */
	final public function setDocumentRootPrefix(string $set): self {
		$this->document_prefix = rtrim($set, '/');
		return $this;
	}

	/**
	 * Directory of the path to files which can be served from the webserver.
	 * Used for caching CSS or
	 * other resources. Should not serve any links to this path.
	 *
	 * Default document cache path is $this->document_root("cache")
	 *
	 * @param string $set
	 *            Set the document cache
	 * @return string
	 */
	final public function document_cache($suffix = null) {
		return path($this->document_cache, $suffix);
	}

	/**
	 * Get or set the module search path
	 *
	 * @param string $add
	 * @return string[] List of paths searched
	 * @deprecated 2022-05
	 */
	final public function module_path(string $add = null): array {
		if ($add !== null) {
			$this->addModulePath($add);
		}
		return $this->modulePath();
	}

	/**
	 * Get the module search path
	 *
	 * @return string[] List of paths searched
	 */
	final public function modulePath(): array {
		return $this->module_path;
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
		$this->module_path[] = $add;
		return $this;
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
	 * @param array $options
	 * @return Model
	 */
	public function modelFactory(string $class, mixed $mixed = null, array $options = []): Model {
		return Model::factory($this, $class, $mixed, $options);
	}

	/**
	 * Create a model
	 *
	 * @param string $class
	 * @param array $options
	 * @return ?Model
	 */
	public function member_model_factory(string $member, string $class, mixed $mixed = null, array $options = []): ?Model {
		return Model::factory($this, $class, $mixed, [
				'_member' => $member,
			] + $options);
	}

	/**
	 * Create a generic object
	 *
	 * @param string $class
	 * @return object
	 */
	public function factory(string $class): object {
		$arguments = func_get_args();
		$class = array_shift($arguments);
		return $this->objects->factoryArguments($class, $arguments);
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
	 * Create a generic object
	 *
	 * @param string $class
	 */
	/**
	 * @param string $class
	 * @param array $arguments
	 * @return object
	 * @throws Exception_Class_NotFound
	 * @throws Exception_Parameter
	 * @throws Exception_Semantics
	 */
	public function factoryArguments(string $class, array $arguments = []): object {
		return $this->objects->factoryArguments($class, $arguments);
	}

	/**
	 *
	 * @param boolean $require
	 *            Throw exception if no session found
	 * @param Request $request
	 * @param bool $require
	 * @return Interface_Session|null
	 */
	public function session(Request $request, bool $require = true): ?Interface_Session {
		if ($request->hasOption(__METHOD__)) {
			return $request->option(__METHOD__);
		}
		if (!$require) {
			return null;
		}
		$session = $this->session_factory();
		$session->initializeSession($request);
		$request->setOption(__METHOD__, $session);
		return $session;
	}

	/**
	 * Uses current application Request for authentication if not supplied.
	 *
	 * @param Request $request Request to use for
	 * @param boolean $require Force object creation if not found. May have side effect of creating a Session_Interface within the Request.
	 * @return ?User
	 */
	public function user(Request $request = null, bool $require = true): ?User {
		if ($request === null) {
			$request = $this->request();
		}
		if ($request) {
			$session = $this->session($request, $require);
			if ($session) {
				$user = $session->user();
				if ($user) {
					return $user;
				}
			}
		}
		if (!$require) {
			return null;
		}
		return $this->modelSingleton(User::class);
	}

	/**
	 *
	 * @param string $message
	 * @param array $arguments
	 * @return void
	 */
	public function deprecated(string $message = '', array $arguments = []): void {
		$arguments['depth'] = to_integer($arguments['depth'] ?? 0) + 1;
		$this->kernel->deprecated($message, $arguments);
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
	 * @return double Microseconds initialization time
	 */
	final public function initializationTime() {
		return $this->kernel->initialization_time;
	}

	/**
	 *
	 * @return string
	 */
	final public function kernelCopyrightHolder() {
		return $this->kernel->copyright_holder();
	}

	/**
	 * Add support for generic extension calls
	 *
	 * @param string $code
	 * @param callable $callable
	 * @return ?callable
	 */
	private function _registerFactory(string $code, callable $callable): ?callable {
		$old_factory = $this->factories[$code] ?? null;
		$this->factories[$code] = $callable;
		$this->application->logger->debug('Adding factory for {code}', [
			'code' => $code,
		]);
		return $old_factory;
	}

	/**
	 * Register a factory function.
	 *
	 * @param string $code
	 * @param callable $callable
	 * @return callable
	 */
	final public function registerFactory(string $code, callable $callable): ?callable {
		// Ideally this method will become deprecated
		$this->_registerFactory($code . '_factory', $callable);
		// camelCase Factory method
		return $this->_registerFactory($code . 'Factory', $callable);
	}

	/**
	 * Register a factory function.
	 * Returns previous factory registered if you want to use it.
	 *
	 * @param string $code
	 * @param callable $callable
	 * @return callable
	 */
	final public function registerRegistry(string $code, callable $callable): ?callable {
		$this->_registerFactory($code . '_registry', $callable);
		return $this->_registerFactory($code . 'Registry', $callable);
	}

	/**
	 * Support foo_factory and foo_registry calls
	 *
	 * @param string $name
	 *            Method called
	 * @return \object
	 */
	final public function __call(string $name, array $args): mixed {
		if (isset($this->factories[$name])) {
			array_unshift($args, $this);
			return call_user_func_array($this->factories[$name], $args);
		}
		$suffix = '_module';
		if (\str_ends_with($name, $suffix)) {
			return $this->modules->object(substr($name, 0, -strlen($suffix)));
		}

		throw new Exception_Unsupported("Application call {method} is not supported.\n\n\tCalled from: {calling}\n\nDo you ned to register the module which adds this functionality?\n\nAvailable: {available}", [
			'method' => $name,
			'calling' => calling_function(),
			'available' => implode(', ', array_keys($this->factories)),
		]);
	}

	/*---------------------------------------------------------------------------------------------------------*\
	  ---------------------------------------------------------------------------------------------------------
	  ---------------------------------------------------------------------------------------------------------
			 _                               _           _
		  __| | ___ _ __  _ __ ___  ___ __ _| |_ ___  __| |
		 / _` |/ _ \ '_ \| '__/ _ \/ __/ _` | __/ _ \/ _` |
		| (_| |  __/ |_) | | |  __/ (_| (_| | ||  __/ (_| |
		 \__,_|\___| .__/|_|  \___|\___\__,_|\__\___|\__,_|
				   |_|
	  ---------------------------------------------------------------------------------------------------------
	  ---------------------------------------------------------------------------------------------------------
	\*---------------------------------------------------------------------------------------------------------*/

	/**
	 *
	 * Access a class_object
	 *
	 * @return Class_ORM
	 * @deprecated 2017-12 use $this->class_ormRegistry($class)
	 */
	public function class_object($class) {
		$this->deprecated();
		return $this->class_ormRegistry($class);
	}

	/**
	 * Retrieve the database for a specific object class
	 *
	 * @param string $class
	 * @return \zesk\Database
	 * @deprecated 2017-12
	 */
	final public function class_object_database($class) {
		$this->deprecated();
		return $this->class_orm($class)->database();
	}

	/**
	 * Return the application root path.
	 *
	 * @param string $suffix
	 *            Optional path to add to the application path
	 * @return string
	 * @deprecated 2017-10
	 * @see self::path()
	 */
	final public function application_root($suffix = null) {
		$this->deprecated();
		return $this->paths->application($suffix);
	}

	/**
	 * Access an ORM by class name
	 *
	 * @return ORM
	 * @deprecated 2017-12 Use ->ormRegistry($class, $mixed, $options) instead.
	 *
	 */
	final public function object($class, $mixed = null, $options = null) {
		$this->deprecated();
		return $this->ormRegistry($class, $mixed, $options);
	}

	/**
	 * Determine object database based on class and optional initialization parameters
	 *
	 * @param string $class
	 * @param mixed $mixed
	 * @param array $options
	 * @return \zesk\Database
	 * @deprecated 2017-12 Use ->ormRegistry($class, $mixed, $options)->database() instead.
	 */
	final public function object_database($class, $mixed = null, $options = null) {
		$this->deprecated();
		return $this->object($class, $mixed, $options)->database();
	}

	/**
	 *
	 * @return Database_Query_Select
	 * @deprecated 2017-12 Use ->ormRegistry($class)->query_select($alias) instead.
	 */
	public function query_select($class, $alias = null) {
		$this->deprecated();
		return $this->object($class)->query_select($alias);
	}

	/**
	 *
	 * @return Database_Query_Update
	 * @deprecated 2017-12 Use ->ormRegistry($class)->query_update($alias) instead.
	 */
	public function query_update($class, $alias = null) {
		$this->deprecated();
		return $this->object($class)->query_update($alias);
	}

	/**
	 *
	 * @return Database_Query_Insert
	 * @deprecated 2017-12 Use ->ormRegistry($class)->query_insert() instead.
	 *
	 */
	public function query_insert($class) {
		$this->deprecated();
		return $this->object($class)->query_insert();
	}

	/**
	 *
	 * @return Database_Query_Insert
	 * @deprecated 2017-12 Use ->ormRegistry($class)->query_insert_select($alias) instead.
	 */
	public function query_insert_select($class, $alias = null) {
		$this->deprecated();
		return $this->object($class)->query_insert_select($alias);
	}

	/**
	 *
	 * @return Database_Query_Delete
	 * @deprecated 2017-12 Use ->ormRegistry($class)->query_delete() instead.
	 */
	public function query_delete($class) {
		$this->deprecated();
		return $this->object($class)->query_delete();
	}

	/**
	 * Access a Class_ORM
	 *
	 * @return Class_ORM
	 * @deprecated 2017-12 use class_orm_registry
	 */
	public function class_orm($class) {
		$this->deprecated();
		return $this->class_ormRegistry($class);
	}

	/**
	 * Retrieve the database for a specific object class
	 *
	 * @param string $class
	 * @return \zesk\Database
	 * @deprecated 2017-12 use orm_regsitry($class)->database()
	 */
	final public function class_orm_database($class) {
		$this->deprecated();
		return $this->ormRegistry($class)->database();
	}

	/**
	 *
	 * @param unknown $class
	 * @throws Exception_Parameter
	 * @deprecated 2017-12 use $this->ormRegistry()->clear_cache();
	 */
	public function clear_class_cache($class = null) {
		$this->deprecated();
		return $this->orm_module()->clear_cache($class);
	}

	/**
	 *
	 * @param unknown $add
	 * @see Module_ORM
	 * @deprecated 2017-12
	 */
	final public function orm_classes($add = null) {
		$this->deprecated();
		return $this->orm_module()->orm_classes($add);
	}

	/**
	 * Retrieve all classes with additional fields
	 *
	 * @return array
	 * @deprecated 2017-12
	 * @see Module_ORM
	 *
	 * @todo move ORM related to hooks
	 */
	final public function all_classes() {
		$this->deprecated();
		return $this->orm_module()->all_classes();
	}

	/**
	 * Synchronzie the schema.
	 * TODO move this elsewhere
	 *
	 * @return multitype:
	 * @see Module_ORM::schema_synchronize
	 * @deprecated 2017-12
	 */
	public function schema_synchronize(Database $db = null, array $classes = null, array $options = []) {
		$this->deprecated();
		return $this->orm_module()->schema_synchronize($db, $classes, $options);
	}

	/**
	 * Default include path
	 *
	 * @return array
	 * @deprecated 2018-01
	 */
	private function default_include_path() {
		$list = array_unique([
			'/etc',
			$this->zeskHome('etc'),
			$this->path('etc'),
		]);
		return $list;
	}

	/**
	 * Return the zesk root path.
	 *
	 * @param string $suffix
	 *            Optional path to add to the application path
	 * @return string
	 * @deprecated 2018-01 Use self::zesk_home
	 */
	final public function zesk_root($suffix = null) {
		zesk()->deprecated();
		return $this->zeskHome($suffix);
	}

	/**
	 *
	 * @param string $uri
	 * @return string
	 * @deprecated 2017-12
	 */
	public function url($uri) {
		$this->deprecated();
		// TODO Remove this
		return $uri;
	}

	/**
	 *
	 * @param mixed $add
	 * @deprecated 2017-12
	 * @see orm_classes
	 */
	final public function classes(string|array $add = null): array {
		$this->deprecated();
		return $this->orm_classes($add);
	}

	/**
	 * Add or retrieve the share path for this application - used to serve
	 * shared content via Controller_Share as well as populate automatically with files within the
	 * system.
	 *
	 * By default, it's /share/
	 *
	 * @param unknown $add
	 * @param unknown $name
	 * @return array
	 * @deprecated 2022-05
	 */
	final public function share_path(string $add = null, string $name = null) {
		$list = $this->share_path;
		if ($add) {
			$this->addSharePath($add, strval($name));
		}
		return $this->sharePath();
	}

	/**
	 * Add or retrieve the locale path for this application - used to load locales
	 *
	 * By default, it's ./etc/language/
	 * Must exist in the file system
	 *
	 * @param string $add Locale path to add
	 * @return array
	 * @deprecated 2022-05
	 */
	final public function locale_path(string $add = ''): array {
		if ($add) {
			$this->deprecated('use addLocalePath');
			$this->addLocalePath($add);
		}
		return $this->localePath();
	}

	/**
	 * Add or retrieve the data path for this application
	 *
	 * @param string $add
	 *            Value to set
	 * @return string Current data_path value
	 * @deprecated 2022-05
	 */
	final public function data_path($suffix = null) {
		$this->application->deprecated(__METHOD__);
		return $this->dataPath($suffix);
	}

	/**
	 * Get the cache path for the application
	 *
	 * @param string $suffix
	 * @return string
	 * @deprecated 2022-05
	 */
	final public function cache_path(string $suffix = ''): string {
		return $this->cachePath($suffix);
	}

	/**
	 *
	 * @param string $path
	 * @return \zesk\Application
	 * @deprecated 2022-05
	 */
	final public function set_application_root(string $path): self {
		$this->setApplicationRoot($path);
		return $this;
	}
}
