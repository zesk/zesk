<?php

/**
 * $URL: https://code.marketacumen.com/zesk/trunk/classes/zesk/application.inc $
 * @package zesk
 * @subpackage core
 * @author $Author: kent $
 * @copyright Copyright &copy; 2011, Market Acumen, Inc.
 *            Created on Mon,Aug 1, 11 at 5:06 PM
 */
namespace zesk;

use \Template as Template;
use \Modules as Modules;
use \log as log;
use \zesk as zesk;
use \arr as arr;
use \Response as Response;
use \conf as conf;
use \url as url;
use \Database_Schema as Database_Schema;
use \Object as Object;
use \Exception as Exception;
use \Database as Database;
use \Template_Stack as Template_Stack;

/**
 * Core web application object for Zesk.
 * If you're doing something useful, it's probably a simple application.
 *
 * @author kent
 *        
 */
class Application extends \Hookable implements \Interface_Theme {
	
	/**
	 *
	 * @var Application
	 */
	private static $singleton = null;
	
	/**
	 *
	 * @var Request
	 */
	public $request = null;
	
	/**
	 *
	 * @var Router
	 */
	public $router = null;
	
	/**
	 *
	 * @var Route
	 */
	public $route = null;
	
	/**
	 *
	 * @var Response_HTML
	 */
	public $response = null;
	
	/**
	 * Registry of modules
	 *
	 * @var Modules
	 */
	public $module = null;
	
	/**
	 * Registry of modules
	 *
	 * @var Modules
	 */
	private $paths = null;
	
	/**
	 * File where the application class resides.
	 * Override this in subclasses with
	 * public $file = __FILE__;
	 *
	 * @var string
	 */
	public $file = null;
	
	/**
	 * Variables for templates
	 *
	 * @var unknown_type
	 */
	private $variables = array();
	
	/**
	 *
	 * @var string
	 */
	protected $page_theme = "page";
	
	/**
	 * Array of internal modules to load
	 *
	 * @var array of string
	 */
	protected $internal_modules = array();
	
	/**
	 * Array of classes to register hooks automatically
	 *
	 * @var array of string
	 */
	protected $hooks = array();
	
	/**
	 * Array of starting list of Objects which are a part of this application
	 *
	 * @var array of string
	 */
	protected $classes = array();
	
	/**
	 * Array of external modules to load
	 *
	 * @var array of string
	 */
	protected $modules = array();
	
	/**
	 * Configuration files to include
	 *
	 * @var array of string
	 */
	protected $includes = array();
	
	/**
	 * Configuration file paths to search
	 *
	 * @var array of string
	 */
	protected $include_paths = array();
	
	/**
	 * Configuration options
	 *
	 * @var array
	 */
	static $configuration_options = null;
	
	/**
	 * Configuration options
	 *
	 * @var array
	 */
	protected $template_variables = array();
	
	/**
	 * Paths to search for themes
	 *
	 * @var string $theme_path
	 */
	protected $theme_path = array();
	
	/**
	 *
	 * @var string
	 */
	protected $cache_path = null;
	
	/**
	 * Top template
	 *
	 * @var Template
	 */
	public $template = null;
	
	/**
	 * Template stack
	 *
	 * @var Template_Stack
	 */
	public $template_stack = null;
	
	/**
	 * Boolean
	 *
	 * @var boolean
	 */
	private $configured_was_run = false;
	public function __construct($options = null) {
		parent::__construct($options);
		$this->module = new Modules($this);
		$this->template_stack = new Template_Stack();
		$this->template = new Template($this);
		$this->template_stack->push($this->template);
	}
	protected function __clone() {
		if ($this->request) {
			$this->request = clone $this->request;
		}
		if ($this->response) {
			$this->response = clone $this->response;
		}
		if ($this->router) {
			$this->router = clone $this->router;
		}
		if ($this->route) {
			$this->route = clone $this->route;
		}
		if ($this->template) {
			$this->template = clone $this->template;
		}
		if ($this->template_stack) {
			$this->template_stack = clone $this->template_stack;
		}
	}
	/**
	 * Run preconfiguration setup
	 */
	protected function preconfigure(array $options) {
		return $options;
	}
	
	/**
	 * Loads a bunch of configuration files, in the following order:
	 * 1.
	 * application.conf
	 * 2. APPLICATION_NAME.conf
	 * 3. uname.conf
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
	final public function configure(array $options = array()) {
		if (self::$configuration_options !== null) {
			log::warning("Reconfiguring application {class}", array(
				"class" => get_class($this)
			));
		}
		self::$configuration_options = $options + zesk::geta('Application::configure_options');
		return $this->_configure(self::$configuration_options);
	}
	
	/**
	 * Run post configuration setup
	 */
	protected function postconfigure() {
	}
	
	/**
	 * Configure a file name to load (from path)
	 *
	 * @param mixed $includes        	
	 * @return Application
	 */
	final public function configure_include($includes) {
		$includes = to_list($includes);
		foreach ($includes as $include) {
			$this->includes[$include] = $include;
		}
		return $this;
	}
	
	/**
	 * Add a path to load configuration files from
	 *
	 * @param string $path        	
	 * @return Application
	 */
	final public function configure_include_path($path) {
		foreach (to_list($path) as $path) {
			if (!is_dir($path)) {
				log::error("{class}::{method}: {path} is not a valid directory, ignoring", array(
					"path" => $path,
					"class" => get_class($this),
					"method" => __METHOD__
				));
				continue;
			}
			$this->include_paths[$path] = $path;
		}
		return $this;
	}
	private function _configure(array $options) {
		$skip_configured_hook = avalue($options, 'skip_configured', false);
		
		// Load hooks
		zesk::hooks("log;Cache;Database;Settings");
		zesk::hooks($this->hooks);
		
		$this->hook('configure');
		
		$new_options = $this->preconfigure($options);
		if (is_array($new_options)) {
			$options = $new_options;
		}
		
		if (count($this->includes) === 0 || array_key_exists('file', $options)) {
			$this->configure_include(avalue($options, 'file', self::configuration_file()));
		}
		if (count($this->include_paths) === 0 || array_key_exists('path', $options)) {
			$this->configure_include_path(avalue($options, 'path', self::configuration_path()));
		}
		zesk::set('include');
		
		$uname = \system::uname();
		$globals_options = array(
			'files' => $this->includes,
			'overwrite' => true
		);
		/*
		 * Load the globals from the configuration file using file list
		 */
		$result = conf::globals($this->include_paths, $globals_options);
		
		$host_aliases = zesk::geta('host_aliases', array());
		$host_config = avalue($host_aliases, $uname, $uname);
		$host_config = strtolower($host_config) . ".conf";
		if (!array_key_exists($host_config, $this->includes)) {
			$this->configure_include($host_config);
			$result += conf::globals($this->include_paths, array(
				"files" => array(
					$host_config
				)
			) + $globals_options);
		}
		while (zesk::has('include', true)) {
			$new_includes = zesk::getl('include');
			$old_includes = $this->includes;
			$this->configure_include($new_includes);
			if ($this->includes === $old_includes) {
				break;
			}
			log::debug("Configuration include files updated to {includes}", array(
				"includes" => $new_includes
			));
			
			$result += conf::globals($this->include_paths, array(
				'files' => $new_includes
			));
		}
		zesk::set('include');
		
		\Module::internal($this->internal_modules);
		\Module::load($this->modules);
		$modules = zesk::getl('Application::modules');
		if (count($modules) > 0) {
			\Module::load($modules);
		}
		if (!$skip_configured_hook) {
			$this->configured();
		}
		return $result;
	}
	public function configured() {
		if (!$this->configured_was_run) {
			$this->_configured();
			return true;
		}
		return false;
	}
	private function _configured() {
		// Now run all configurations: System, Modules, then Application
		zesk::hook('configured', $this); // System level
		$this->module->all_hook("configured");
		$this->hook('configured'); // Application level
		$this->load_maintenance();
		$this->inherit_global_options();
		$this->postconfigure();
		$this->configure_paths();
		$this->configured_was_run = true;
	}
	private function configure_paths() {
		$cache_path = $this->option("cache_path", zesk::get("Cache::path", zesk::application_root("cache")));
		$this->cache_path = \dir::is_absolute($cache_path) ? $cache_path : zesk::application_root($cache_path);
	}
	
	/**
	 * Runs configuration again, using same options as previous configuration.
	 *
	 * @see Application::configure
	 */
	public function reconfigure() {
		$result = $this->_configure(to_array(self::$configuration_options));
		$this->_configured();
		return $result;
	}
	
	/**
	 * Load the Application singleton
	 *
	 * @param array $options        	
	 * @throws Exception_Configuration
	 * @return Application
	 */
	public static function instance($options = null) {
		if (self::$singleton instanceof zesk\Application) {
			return self::$singleton;
		}
		$class = zesk::application_class();
		if (!$class) {
			throw new \Exception_Configuration("Application::instance() zesk::application_class() is empty");
		}
		self::hooks();
		$application = self::$singleton = zesk::factory($class, $options);
		$application->theme_path(zesk::theme_path_default());
		return $application;
	}
	final public function cache_clear() {
		foreach (array(
			zesk::cache_path(),
			zesk::document_cache()
		) as $path) {
			$size = \dir::size($path);
			if ($size > 0) {
				\dir::delete_contents($path);
				log::notice("Deleted {size} bytes in {path}", compact("size", "path"));
			} else {
				log::notice("{path} is empty.", compact("size", "path"));
			}
		}
		$this->hook('cache_clear');
		log::notice("Running: {cache_clear_hooks}", array(
			"cache_clear_hooks" => \Module::all_hook_list("cache_clear")
		));
		\Module::all_hook("cache_clear", $this);
		$controllers = $this->controllers();
		foreach ($controllers as $controller) {
			$controller->hook('cache_clear');
		}
	}
	final private function load_maintenance() {
		if (self::maintenance()) {
			zesk::set(conf::load($this->maintenance_file()));
		}
	}
	/**
	 * Get/set maintenance flag
	 *
	 * @param string $set        	
	 * @return boolean Ambigous array, string, number>
	 */
	final public function maintenance($set = null) {
		$maintenance_file = $this->maintenance_file();
		if ($set === null) {
			return file_exists($maintenance_file);
		}
		$result = $this->hook_array("maintenance", array(
			$set
		), true);
		if (!$result) {
			log::error("Unable to set application {application_class} maintenance mode to {value}", array(
				"application_class" => get_class($this),
				"value" => $set ? "true" : "false"
			));
			return null;
		}
		if ($set) {
			$context = array(
				"time" => date('Y-m-d H:i:s')
			) + $this->hook_array("maintenance_context", array(
				array(
					"value" => $set
				)
			), array());
			conf::edit($this->maintenance_file(), $context);
		} else if (file_exists($maintenance_file)) {
			unlink($maintenance_file);
			clearstatcache(true, $maintenance_file);
		}
		return $result;
	}
	
	/**
	 * Return file, which when exists, puts the site into maintenance mode
	 *
	 * @return string
	 */
	final private function maintenance_file() {
		return zesk::get("maintenance_file", zesk::application_root("etc/maintenance.conf"));
	}
	
	/**
	 * Override this in child classes to manipulate creation of these objects
	 *
	 * @param string $class        	
	 * @return Object
	 */
	final public function singleton($class) {
		$object = $this->hook("singleton_$class");
		if ($object instanceof $class) {
			return $object;
		}
		return zesk::singleton($class);
	}
	
	/**
	 * Retrieve the list of classes associated with an application
	 *
	 * @param mixed $add
	 *        	Class to add, or array of classes to add
	 * @return array
	 */
	private function _classes($add = null) {
		static $classes = null;
		if (!is_array($classes)) {
			$schema_file = \file::extension_change($this->file, ".classes");
			if (is_file($schema_file)) {
				$classes = arr::trim_clean(explode("\n", \text::remove_line_comments(file_get_contents($schema_file), '#', false)));
				$classes = arr::flip_copy($classes, true);
			} else {
				$classes = array();
			}
			$classes = $classes + arr::flip_copy($this->classes, true);
		}
		if ($add !== null) {
			$add = to_list($add);
			foreach ($add as $class) {
				$class = strtolower($class);
				if (!array_key_exists($class, $classes)) {
					$classes[$class] = $class;
				}
			}
			return $this;
		}
		$all_classes = $this->hook_array('classes', array(
			$classes
		), $classes);
		/* @var $module Module */
		foreach (\Module::all_modules() as $name => $module) {
			$module_classes = $module->classes();
			if (false) {
				log::debug("Looking at classes for module {name} = {value}", array(
					"name" => $name,
					"value" => $module_classes
				));
			}
			$all_classes = array_merge($all_classes, arr::flip_copy($module_classes, true));
		}
		zesk::register_class(array_keys($all_classes));
		return $all_classes;
	}
	final public function classes($add = null) {
		if ($add !== null) {
			return $this->_classes($add);
		}
		return array_keys($this->all_classes());
	}
	
	/**
	 * Retrieve all classes with additional fields
	 *
	 * @return array
	 */
	final public function all_classes() {
		$classes = $this->_classes();
		$objects_by_class = array();
		$is_table = false;
		$rows = array();
		while (count($classes) > 0) {
			$class = array_shift($classes);
			$lowclass = strtolower($class);
			if (array_key_exists($lowclass, $objects_by_class)) {
				continue;
			}
			$result = array();
			$result['class'] = $lowclass;
			try {
				$result['object'] = $object = $this->object_factory($class);
				$result['database'] = $object->database_name();
				$result['table'] = $object->table();
			} catch (\Exception $e) {
				$result['object'] = $object = null;
			}
			$objects_by_class[$lowclass] = $result;
			if ($object) {
				$dependencies = $object->dependencies();
				$requires = avalue($dependencies, 'requires', array());
				foreach ($requires as $require) {
					$require = strtolower($require);
					if (array_key_exists($require, $objects_by_class)) {
						continue;
					}
					$classes[] = $require;
				}
			}
		}
		return $objects_by_class;
	}
	/**
	 * Add an application configuration path, or get the path
	 *
	 * @param string $add        	
	 * @return array
	 */
	public static function configuration_path($add = null) {
		$list = array_unique(zesk::getl('Application::configuration_path', array(
			'/etc',
			zesk::root('etc'),
			zesk::application_root('etc')
		)));
		if ($add !== null) {
			zesk::add('Application::configuration_path', $add);
		}
		return $list;
	}
	
	/**
	 * Load the list of files to be loaded as part of this application configuration
	 *
	 * @return array
	 */
	private static function configuration_file() {
		$files_default = array();
		$files_default[] = 'application.conf';
		if (defined('APPLICATION_NAME')) {
			$files_default[] = APPLICATION_NAME . '.conf';
		}
		$files_default[] = strtolower(\system::uname()) . ".conf";
		return zesk::getl('Application::configuration_file', $files_default);
	}
	
	/**
	 *
	 * @return Request
	 */
	protected function hook_request() {
		$request = zesk::singleton('Request');
		return $request;
	}
	
	/**
	 *
	 * @return Request
	 */
	protected function hook_response(\Request $request) {
		return Response::instance($this);
	}
	
	/**
	 *
	 * @return Router
	 */
	function hook_router() {
		$router_file = \file::extension_change($this->file, 'router');
		$cache = zesk::getb("Router::cache");
		if ($cache) {
			$router = \Router::cached(filemtime($router_file));
			if ($router) {
				zesk::hook('Router::cached', $router);
				return $router;
			}
		}
		$router = new \Router($this);
		if (!is_file($router_file)) {
			log::debug("No router file {router_file} to load - creating blank router", array(
				array(
					"router_file",
					$router_file
				)
			));
		} else {
			$router->import(file_get_contents($router_file));
		}
		if ($cache) {
			$router->cache();
		}
		return $router;
	}
	
	/**
	 * When an exception happens in the main loop, generate content related to the exception.
	 *
	 * @param Exception $e        	
	 */
	final private function _main_exception(Exception $exception) {
		$content = $this->theme(zesk::class_hierarchy($exception), array(
			"exception" => $exception,
			"content" => $exception
		), array(
			"first" => true
		));
		if ($this->response && $this->response->content_type === "text/html") {
			$this->response->content = $this->theme('page', array(
				'content' => $content
			) + $this->template_variables);
		}
		$this->hook('main_exception', $exception);
		zesk::hook("exception", $exception);
	}
	final public function request() {
		$this->router();
		return $this->request;
	}
	final public function response() {
		$this->router();
		return $this->response;
	}
	/**
	 * Load router
	 *
	 * @return Router NULL
	 */
	final public function router() {
		if ($this->router instanceof \Router) {
			// KMD 2015-03-13 TODO Has side effects - why?
			return $this->router; // ->reset();
		}
		try {
			/* @var $request Request */
			$request = $this->request = $this->hook("request");
			// TODO Investigate creating response via Router instead of here
			/* @var $response Response_HTML */
			$response = $this->response = $this->hook("response", $request);
			/* @var $router Router */
			$router = $this->router = $request->router = $this->hook("router");
			
			return $router;
		} catch (\Exception $exception) {
			$this->_main_exception($exception);
			return null;
		}
	}
	
	/**
	 * Return all known/discerable Controllers for the application
	 *
	 * @return array of Controller
	 */
	final public function controllers() {
		$router = $this->router();
		return $router->controllers($this);
	}
	
	/**
	 * Initialize variables
	 *
	 * @return boolean
	 */
	private function _templates_initialize() {
		$variables = array();
		$variables['application'] = $this;
		$variables['request'] = $this->request;
		$variables['response'] = $this->response;
		$variables['router'] = $this->router;
		$variables['route'] = $this->route;
		$variables += $this->template_variables;
		$variables += $this->hook_array("template_defaults", array(
			$variables
		), array());
		$this->template->set($variables);
		return $this->template;
	}
	
	/**
	 * Initialize template variables
	 *
	 * Execute route
	 *
	 * @param Router $router        	
	 */
	private function _main_route(\Router $router) {
		$this->hook("router_prematch", $router);
		$this->route = $router->match($this->request);
		$this->_templates_initialize();
		if (!$this->route) {
			$this->hook("main-router_no_match");
			$this->response->status(\Net_HTTP::Status_File_Not_Found, "No route found");
			$this->_main_exception(new \Exception_NotFound("The resource does not exist on this server: {url}", $this->request->variables()));
			return null;
		}
		$this->hook("router_matched", $router, $this->route);
		$this->route = null;
		$this->route = $router->execute($this);
		$this->hook('router_postprocess', $router);
		return $this->template;
	}
	
	/**
	 * Application main execution:
	 *
	 * - Load the router
	 * - Find a matched route
	 * - Execute it
	 * - Render response
	 */
	public function main() {
		$this->hook("main");
		
		$this->variables = array();
		
		$template = null;
		if (($router = $this->router()) !== null) {
			try {
				log::debug("App bootstrap took {seconds} seconds", array(
					"seconds" => sprintf("%.3f", microtime(true) - zesk::get("init"))
				));
				$template = $this->_main_route($router);
			} catch (Exception $exception) {
				$this->_main_exception($exception);
			}
			if ($template) {
				$this->_output_content($template);
			} else {
				$this->response->output();
			}
		} else if ($this->response) {
			$this->response->output();
		}
	}
	private function _output_content(\Template $template, array $options = array()) {
		if ($this->response->content_type() === "text/html") {
			$template->set(array(
				'content' => $this->response->content,
				'page_theme' => $page_theme = $this->page_theme
			));
			
			$this->response->content = $template->theme($page_theme);
			$this->response->content = $this->hook("content_postprocess", $this->response->content);
		}
		$this->response->output($options);
	}
	/**
	 * Utility for index.php file for all public-served content.
	 */
	final public function content($path) {
		$this->hook("content");
		
		$old_request = $this->request;
		$old_response = $this->response;
		$old_route = $this->router->route;
		
		$url = $old_request->url();
		$url = rtrim(url::left_host($url), "/") . $path;
		$this->request = new \Request(array(
			"url" => $url,
			"method" => \Net_HTTP::Method_GET,
			"data" => "",
			"variables" => url::query_parse_url($path)
		));
		$this->response = Response::factory($this);
		
		ob_start();
		$template = $this->_main_route($this->router->reset());
		$this->_output_content($template, (array(
			'skip-headers' => true
		)));
		$content = ob_get_clean();
		
		$this->route = $this->router->route = $old_route;
		$this->request = $old_request;
		$this->response = $old_response;
		
		return $content;
	}
	
	/**
	 * Hook for taking old .
	 *
	 *
	 *
	 *
	 * php URLs and converting to router-based URLs
	 *
	 * @param Model_URL $state        	
	 * @return true
	 */
	public static function hook_url_php(\Model_URL $state) {
		if (url::valid($state->url)) {
			return true;
		}
		list($u, $qs) = pair($state->url, '?', $state->url, '');
		if (!\str::ends($u, "/")) {
			$u .= ".php";
		}
		if ($u[0] !== '/') {
			$u = "/$u";
		}
		$state->url = url::query_append($u, $qs);
		return true;
	}
	
	/**
	 * While developing, check schema every minute
	 */
	public static function cron_cluster_minute() {
		if (Application::instance()->development()) {
			self::_schema_check();
		}
	}
	
	/**
	 * While an out-of-sync schema may cause issues, it often does not.
	 * Check hourly on production to avoid
	 * checking the database incessantly.
	 */
	public static function cron_cluster_hour() {
		if (!Application::instance()->development()) {
			self::_schema_check();
		}
	}
	
	/**
	 * Internal function - check the schema and notify someone
	 */
	private static function _schema_check() {
		$application = zesk::singleton('Application');
		$results = $application->schema_synchronize();
		if (count($results) > 0) {
			if (zesk::getb('Application::schema_sync')) {
				$db = \Database::instance();
				log::warning("The database schema was out of sync, updating: {sql}", array(
					"sql" => implode(";\n", $results) . ";\n"
				));
				$db->query($results);
			} else {
				log::warning("The database schema is out of sync, please update: {sql}", array(
					"sql" => implode(";\n", $results) . ";\n"
				));
				$router = zesk::singleton('Router');
				$url = $router->get_route("schema_synchronize", $application);
				$message = $url ? __("The database schema is out of sync, please <a href=\"{url}\">update it immediately.</a>") : __("The database schema is out of sync, please update it immediately.");
				Response::instance($application)->redirect_message($message, array(
					"url" => $url
				));
			}
		}
	}
	
	/**
	 * Synchronzie the schema
	 *
	 * @return multitype:
	 */
	public function schema_synchronize(\Database $db = null, array $classes = null, array $options = array()) {
		if (!$db) {
			$db = $this->database_factory();
		}
		if ($classes === null) {
			$classes = $this->classes();
		} else {
			$options['follow'] = avalue($options, 'follow', false);
		}
		log::debug($classes);
		$results = array();
		$objects_by_class = array();
		$other_updates = array();
		$follow = avalue($options, 'follow', true);
		while (count($classes) > 0) {
			$class = array_shift($classes);
			$lowclass = strtolower($class);
			if (avalue($objects_by_class, $lowclass)) {
				continue;
			}
			log::debug("Parsing $class");
			$objects_by_class[$lowclass] = true;
			try {
				$object = $this->object_factory($class);
				$object_db_name = $object->database()->code_name();
				$updates = Database_Schema::update_object($object);
			} catch (Exception $e) {
				log::error("Unable to synchronize {class} because of {exception_class} {message}", array(
					"class" => $class,
					"message" => $e->getMessage(),
					"exception_class" => get_class($e),
					"exception" => $e
				));
				throw $e;
				continue;
			}
			if (count($updates) > 0 && $object_db_name !== $db->code_name()) {
				$other_updates[$object_db_name] = true;
				$updates = array();
			}
			$results = array_merge($results, $updates);
			if ($follow) {
				$dependencies = $object->dependencies();
				$requires = avalue($dependencies, 'requires', array());
				foreach ($requires as $require) {
					if (avalue($objects_by_class, $require)) {
						continue;
					}
					log::debug("$class: Adding dependent class $require");
					$classes[] = $require;
				}
			}
		}
		if (count($other_updates) > 0) {
			$results[] = "-- Other database updates:\n" . arr::join_wrap(array_keys($other_updates), "-- zesk database-schema --name ", " --update;\n");
		}
		return $results;
	}
	
	/**
	 * Get a list of repositories for this application (dependencies)
	 *
	 * @return array
	 */
	public function repositories() {
		$repos = array(
			'zesk' => zesk::root(),
			get_class($this) => zesk::application_root()
		);
		return $this->hook_array("repositories", array(
			$repos
		), $repos);
	}
	
	/**
	 * Utility for index.php file for all public-served content.
	 */
	public function index() {
		$final_map = array();
		zesk::document_init();
		if (($content = Response::cached(url::current())) === null) {
			ob_start();
			$this->main();
			$content = ob_get_clean();
			$final_map['{page-is-cached}'] = '0';
		} else {
			zesk::unhook('exit');
			$final_map['{page-is-cached}'] = '1';
		}
		$final_map += array(
			"{page-render-time}" => sprintf("%.3f", microtime(true) - zesk::get('init'))
		);
		if ($this->response) {
			$this->response->cache_save($content);
		}
		if (!$this->response || $this->response->is_content_type(array(
			"text/",
			"javascript"
		))) {
			$content = strtr($content, $final_map);
		}
		echo $content;
	}
	
	/**
	 * Called once before a hook_all("Object::method");
	 */
	public static final function object_register_all_hooks() {
		$classes = Application::instance()->all_classes();
		zesk::register_class(arr::collapse($classes, "class"));
	}
	
	/**
	 * When zesk::all_hook is called, this is called first to collect all objects
	 * in the system.
	 */
	public static function hooks() {
		zesk::add_hook('Object::register_all_hooks', __CLASS__ . "::object_register_all_hooks");
	}
	
	/**
	 * Template or logging variables
	 *
	 * @return Router
	 */
	public function variables() {
		$parameters['application'] = $this;
		$parameters['request'] = $this->request;
		$parameters['response'] = $this->response;
		$parameters['router'] = $this->request->router;
		$parameters['route'] = $this->route;
		$parameters['url'] = $this->request->url();
		return $parameters;
	}
	
	/**
	 * Retrieve the list of theme file paths, or add one.
	 *
	 * @param string $add
	 *        	(Optional) Path to add to the theme path. Pass in null to do nothing.
	 * @return array The ordered list of paths to search for theme files.
	 */
	public function theme_path($add = null, $first = true) {
		if (is_array($add)) {
			foreach ($add as $a) {
				$this->theme_path($a, $first);
			}
			return $this->theme_path;
		}
		if ($add && !in_array($add, $this->theme_path)) {
			if ($first) {
				array_unshift($this->theme_path, $add);
			} else {
				$this->theme_path[] = $add;
			}
		}
		return $this->theme_path;
	}
	
	/**
	 * Add or retrieve the share path for this application - used to serve
	 * shared content via Controller_Share as well as populate automatically with files within the
	 * system.
	 * By default, it's /share/
	 *
	 * @todo Deprecate zesk::share_path
	 * @param unknown $add        	
	 * @param unknown $name        	
	 * @return array
	 */
	public function share_path($add = null, $name = null) {
		return zesk::share_path($add, $name);
	}
	
	/**
	 * Add or retrieve the data path for this application
	 *
	 * @todo Deprecate zesk::data_path
	 * @param string $add Value to set        	
	 * @return string Current data_path value
	 */
	public function data_path($add = null) {
		return zesk::data_path($add);
	}
	
	/**
	 * Add or retrieve the share path for this application
	 *
	 * @todo Deprecate zesk::zesk_command_path
	 * @param unknown $add        	
	 * @param unknown $name        	
	 * @return array
	 */
	public function zesk_command_path($add = null) {
		return zesk::zesk_command_path($add);
	}
	
	/**
	 * theme an element
	 *
	 * @param string $type        	
	 * @return string
	 */
	public function theme($types, $arguments = array(), array $options = array()) {
		if (!is_array($arguments)) {
			$arguments = array(
				"content" => $arguments
			);
		}
		$types = to_list($types);
		if (count($types) === 1) {
			return $this->_theme_array($types[0], $arguments);
		}
		if (!is_array($types)) {
			throw new \Exception_Parameter("Application::theme: \$types is " . gettype($types));
		}
		if (count($types) === 0) {
			return avalue($options, 'default', null);
		}
		$type = array_shift($types);
		$arguments['content_previous'] = null;
		$content = $this->_theme_array($type, $arguments, null);
		if ($content !== null) {
			$arguments['content'] = $content;
		}
		$first = avalue($options, 'first', false);
		$concatenate = avalue($options, 'concatenate', false);
		while (count($types) > 0) {
			if ($first && !empty($content)) {
				break;
			}
			$type = array_shift($types);
			$content_previous = $content;
			$content_next = $this->_theme_array($type, $arguments, $content);
			$content = $concatenate ? $content . $content_next : $content_next;
			$arguments['content_previous'] = $content_previous;
			$arguments['content'] = $content;
		}
		return $content;
	}
	
	/**
	 * Custom arguments format for a theme? Is this used?
	 *
	 * @todo Is this used
	 * @param string $type
	 *        	Theme codename
	 * @param array $set
	 *        	Arguments for theme
	 * @return mixed
	 */
	public function theme_arguments($type = null, array $set = null) {
		if ($type === null) {
			return $this->theme_arguments;
		}
		$type = strtolower($type);
		if ($set !== null) {
			$this->theme_arguments[$type] = $set;
			return $set;
		}
		return avalue($this->theme_arguments, $type, null);
	}
	
	/**
	 * Invoke a single theme type
	 *
	 * @param string $type        	
	 * @param array $args        	
	 * @param string $content
	 *        	Default content
	 * @return string
	 */
	private function _theme_array($type, array $args, $content = null) {
		if ($this->development() && ends($type, ".tpl")) {
			throw new \Exception_Semantics("Theme called with .tpl suffix - not required {type}", compact("type"));
		}
		$type = strtolower($type);
		$object = avalue($args, "content");
		if (is_object($object)) {
			if (method_exists($object, "hook_theme")) {
				return $object->hook_array("theme", array(
					$args,
					$content
				), $content);
			}
			if (method_exists($object, 'variables')) {
				$args += $object->variables();
			}
		} else {
			$object = null;
		}
		$template_name = zesk::clean_path($type) . ".tpl";
		$t = new Template($this, $template_name, $args);
		if (!$t->exists()) {
			return $content;
		}
		$content = $t->render();
		$content = zesk::hook_array("theme_${type}_alter", array(
			$content
		), $content);
		return $content;
	}
	
	/**
	 * Does one or more themes exist?
	 *
	 * @param mixed $types
	 *        	List of themes
	 * @return boolean If all exist, returns true, otherwise false
	 */
	public function theme_exists($types, $args = array()) {
		if (empty($types)) {
			return false;
		}
		$types = to_list($types);
		foreach ($types as $type) {
			if (!$this->_theme_exists($type, $args)) {
				return false;
			}
		}
		return true;
	}
	
	/**
	 * Returns similar result as _theme_array except just tests to see if any content would
	 * be returned
	 *
	 * @param mixed $type        	
	 * @return boolean
	 */
	private function _theme_exists($type, array $args) {
		$type = strtolower($type);
		$object = avalue($args, "content");
		if (is_object($object) && method_exists($object, "hook_theme")) {
			return true;
		}
		if (zesk::has_hook("theme_${type}")) {
			return true;
		}
		if ($this->template->would_exist(zesk::clean_path($type) . ".tpl")) {
			return true;
		}
		return false;
	}
	
	/**
	 * Set autoload path for the application.
	 *
	 * @param mixed $add        	
	 * @param string $options        	
	 * @return array The ordered list of paths to search for class names
	 */
	public function autoload_path($add = null, $options = true) {
		return zesk::autoload_path($add, $options);
	}
	
	/**
	 * Set command path for the application.
	 *
	 * @param mixed $add        	
	 * @param string $options        	
	 * @return array The ordered list of paths to search for class names
	 */
	public function command_path($add = null) {
		return zesk::command_path($add);
	}
	
	/**
	 * Register a class with the application
	 *
	 * @param string $class        	
	 * @return array This class name and parent classes
	 */
	public function register_class($class) {
		return zesk::register_class($class);
	}
	
	/**
	 * Return the application root path.
	 * Do not store this unless you know what you are doing.
	 *
	 * @param string $path
	 *        	Optional path to add to the application path
	 * @return string
	 */
	public function application_root($path = null) {
		return zesk::application_root($path);
	}
	
	/**
	 * Return the application class.
	 * Do not store this unless you know what you are doing.
	 *
	 * @param string $path
	 *        	Optional path to add to the application path
	 * @return string
	 */
	public static function application_class($class = null) {
		return zesk::application_class($class);
	}
	
	/**
	 * Get the cache path for the application
	 *
	 * @return string
	 * @param unknown $add        	
	 */
	public function cache_path($add = null) {
		return $this->cache_path;
	}
	/**
	 * Your web root is the directory in the file system which contains our application and other
	 * files.
	 *
	 * It may be served from an aliased or shared directory and as such may not appear at the web
	 * server's root.
	 *
	 * To ensure all URLs are generated correctly, you can set $this->web_root_prefix(string) to set
	 * a portion of the URL which is always prefixed to any generated url.
	 *
	 * @param string $set
	 *        	Optionally set the web root
	 * @throws Exception_Directory_NotFound
	 * @return string The directory
	 */
	public function document_root($set = null, $prefix = null) {
		return zesk::document_root($set, $prefix);
	}
	
	/**
	 * Your web root may be served from an aliased or shared directory and as such may not appear at
	 * the web server's root.
	 * To ensure all URLs are generated correctly, you can set zesk::web_root_prefix(string) to set
	 * a portion of the URL which is always prefixed to any generated url.
	 *
	 * @param string $set
	 *        	Optionally set the web root
	 * @throws Exception_Directory_NotFound
	 * @return string The directory
	 * @todo should this be urlescpaed by web_root_prefix function to avoid & and + to be set?
	 */
	public static function document_root_prefix($set = null) {
		return zesk::document_root_prefix($set);
	}
	
	/**
	 * Return the development status of this application
	 *
	 * @param boolean $set
	 *        	Optionally set value
	 * @return boolean
	 */
	public function development($set = null) {
		if (is_bool($set)) {
			zesk::set('development', $set);
		}
		return zesk::getb('development');
	}
	
	/**
	 * Retrieve the database for this application. This call is meant to deprecate the global Database::factory eventually.
	 * 
	 * @param string $mixed Name or URL
	 * @param array $options Options for the database
	 * @return Database
	 */
	public function database_factory($mixed = null, array $options = array()) {
		return \Database::factory($mixed, $options);
	}
	/**
	 * Create a widget
	 * 
	 * @param string $class
	 * @param array $options
	 * @return Widget
	 */
	public function widget_factory($class, array $options = array()) {
		return \Widget::factory($class, $options, $this);
	}
	
	/**
	 * Create a widget
	 * 
	 * @param string $class
	 * @param array $options
	 * @todo Pass application as part of creation call
	 * @return Object
	 */
	public function object_factory($class, $mixed = null, array $options = array()) {
		return \Object::factory($class, $mixed, $options, $this);
	}
}

