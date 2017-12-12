<?php

/**
 * $URL: https://code.marketacumen.com/zesk/trunk/classes/router.inc $
 * @package zesk
 * @subpackage system
 * @author kent
 * @copyright Copyright &copy; 2016, Market Acumen, Inc.
 */
namespace zesk;

/**
 * Router
 * Handles converting a URL Path to a method invokation, with a special case for Controller objects.
 * Support for permissions, automatic variable conversion as well.
 * Basic setup is pattern -> array of settings
 * Pattern is a URL path, excluding / prefix , with optional variables included, e.g.
 * /foo/bar/dee
 * You can specify optional sections using parenthesis ()
 * The settings available are:
 * controller Controller to invoke, can include variable name
 * controller prefixes A list of controller prefixes to use, overrides the default Router prefixes
 * controller options Array of options to set on the controller upon creation
 * action The action to invoke in the controller
 * arguments The arguments to the action. If values are numeric, specifies the URL part to pass
 * as an argument. Keys
 * may be specified, but are ignored.
 * For a template, it's:
 * template Template to load, may include variables names
 * arguments Name/value pairs passed to the template. If values are numeric, specifies the URL
 * part to pass as an
 * argument.
 * For a method it's:
 * method Name of method to invoke. For class methods, specify array("ClassName" =>
 * "method_name"), or
 * ClassName::method_name
 * arguments The arguments to the action. For numeric values, specifies the URL part to pass as
 * an argument.
 * For all routes
 * content type Set the response content type to this
 * status code Set the response status code
 * status message Set the response status message
 * redirect After invokation, redirect to here
 * The pattern syntax is:
 * {variable-name} An untyped variable, or string.
 * {type variable-name} Typed variable name. Type can be integer, double, string, array, list,
 * comma-list, dash-list, semicolon-list, option, or an object type.
 * {type } An unnamed variable, can be referenced by the URL path order
 * e.g.
 * You can also group parameters as option by specifying
 * The following "magic" URI will invoke Controller_{Controller}::action_{action} with optional
 * parameter:
 *
 * {controller}/{action}(/{ID:+})
 */
class Router extends Hookable {
	
	/**
	 * Debugging is enabled
	 *
	 * @var boolean
	 */
	public $debug = false;
	
	/**
	 *
	 * @var string
	 */
	protected $application_class = null;
	
	/**
	 *
	 * @var array of class => Route
	 */
	protected $reverse_routes = array();
	
	/**
	 *
	 * @var Route[]
	 */
	protected $routes = array();
	
	/**
	 *
	 * @var array of Route
	 */
	protected $by_id = array();
	
	/**
	 *
	 * @var string
	 */
	protected $prefix = "/";
	
	/**
	 * State variable - should be reset
	 *
	 * @var Route
	 */
	public $route = null;
	
	/**
	 * State variable - should be reset
	 *
	 * @var Request
	 */
	public $request = null;
	/**
	 *
	 * @var integer
	 */
	protected $default_route = 0;
	
	/**
	 *
	 * @var array
	 */
	protected $aliases = array();
	
	/**
	 * Whether the routes have been sorted by weight yet
	 *
	 * @var boolean
	 */
	private $sorted = false;
	
	/**
	 * Index to ensure routes are sorted by added order.
	 */
	protected $weight_index = 0;
	
	/**
	 *
	 * {@inheritdoc}
	 *
	 * @see Options::__sleep()
	 */
	function __sleep() {
		$result = array_merge(array(
			'application_class',
			'reverse_routes',
			'routes',
			'prefix',
			'default_route',
			"aliases"
		), parent::__sleep());
		return $result;
	}
	
	/**
	 */
	function __wakeup() {
		$this->by_id = array();
		$this->route = null;
		$this->application = Kernel::singleton()->application();
		foreach ($this->routes as $route) {
			$route->application = $this->application;
			$route->router = $this;
			$this->_add_route_id($route);
		}
		$this->request = $this->application->request();
		$this->sorted = false;
	}
	
	/**
	 * Create a new Router
	 * 
	 * @param Application $application
	 * @param array $options
	 * @return self
	 */
	static function factory(Application $application, array $options = array()) {
		return $application->factory(__CLASS__, $application, $options);
	}
	/**
	 *
	 * @param array $options
	 */
	function __construct(Application $application, array $options = array()) {
		parent::__construct($application, $options);
		$this->application_class = get_class($application);
		$this->call_hook("new");
	}
	
	/**
	 *
	 * @param Kernel $zesk
	 */
	public static function hooks(Kernel $zesk) {
		$zesk->hooks->add(Hooks::hook_configured, array(
			__CLASS__,
			"configured"
		));
	}
	
	/**
	 *
	 * @param Application $application
	 */
	public static function configured(Application $application) {
		$application->configuration->deprecated(("Router::debug"), "zesk\Router::debug");
	}
	
	/**
	 * Returns the cache path for this router
	 *
	 * @return string
	 */
	private function cache_path($cache_file = null) {
		$app = $this->application;
		$name = PHP::parse_class(get_class($app));
		if ($cache_file === null) {
			$cache_file = $app->configuration->path_get(__CLASS__ . "::cache_file", $app->configuration->path_get("Router::cache_file", "$name.cache"));
		}
		return $app->paths->cache(array(
			"routers",
			$cache_file
		));
	}
	
	/**
	 * Whether this router is cached; performance optimization
	 *
	 * @return Router or null if not cached
	 */
	function cached($mtime = null) {
		$path = $this->cache_path();
		if (!file_exists($path)) {
			return null;
		}
		if (filemtime($path) < $mtime) {
			return null;
		}
		if (filemtime($path) < filemtime(__FILE__)) {
			return null;
		}
		return unserialize(file_get_contents($path));
	}
	
	/**
	 *
	 * @param Route $a
	 * @param Route $b
	 * @return number
	 */
	static function compare_weight(Route $a, Route $b) {
		$a_weight = zesk_weight($a->option("weight"));
		$b_weight = zesk_weight($b->option("weight"));
		$a->set_option("computed_weight", $a_weight);
		$b->set_option("computed_weight", $b_weight);
		$delta = doubleval($a_weight) - doubleval($b_weight);
		if ($delta === 0) {
			return 0;
		}
		if ($delta < 0) {
			return -1;
		}
		return 1;
	}
	
	/**
	 */
	private function _sort() {
		uasort($this->routes, __CLASS__ . "::compare_weight");
	}
	
	/**
	 * Fetch a route by ID.
	 * ID is an attribute associated with each route, or the clean URL.
	 *
	 * @param string $id
	 * @return Route
	 */
	function route($id) {
		return avalue($this->by_id, strtolower($id));
	}
	
	/**
	 *
	 * @return Route[]
	 */
	function routes() {
		if (!$this->sorted) {
			$this->_sort();
			$this->sorted = true;
		}
		return $this->routes;
	}
	
	/**
	 * Cache this router, destroy old cache
	 */
	function cache() {
		$path = $this->cache_path();
		Directory::create(dirname($path));
		file_put_contents($path, serialize($this));
		return $this;
	}
	
	/**
	 *
	 * @param unknown $set
	 * @return string
	 */
	function prefix($set = null) {
		if ($set !== null) {
			$this->prefix = $set;
		}
		return $this->prefix;
	}
	public function add_alias($from, $to) {
		$this->alias[$from] = $to;
	}
	public function log($level, $message, array $arguments = array()) {
		if ($this->debug) {
			$this->application->logger->log($level, $message, $arguments);
		}
	}
	function reset() {
		$this->route = null;
		return $this;
	}
	function match(Request $request) {
		$this->request = $request;
		$path = strval($request->path());
		$method = strval($request->method());
		if ($this->prefix) {
			$path = str::unprefix($path, $this->prefix);
		}
		$path = avalue($this->aliases, $path, $path);
		$routes = $this->routes(); /* @var $route Route */
		foreach ($this->routes as $route) {
			if ($route->match($path, $method)) {
				$this->log("debug", "Matched {path} to {route}", compact("path", "route"));
				$this->route = $route;
				return $route;
			}
		}
		$this->log("warning", "No matches for {path}", compact("path"));
		return null;
	}
	
	/**
	 * Route the URL and return the response
	 *
	 * @throws Exception_NotFound
	 * @return \zesk\Route
	 */
	function execute(Request $request = null) {
		if (!$this->route) {
			if (!$request) {
				$request = $this->request;
				if (!$request) {
					throw new Exception_Semantics("Need a request to execute");
				}
			}
			if (!$this->match($request)) {
				$this->route = avalue($this->routes, $this->default_route);
				if (!$this->route) {
					throw new Exception_NotFound("No route found");
				}
			}
		}
		$this->route->execute();
		return $this->route;
	}
	function url_replace($name, $value = null) {
		if (!$this->route) {
			return null;
		}
		return $this->prefix . $this->route->url_replace($name, $value);
	}
	public function add_route($path, array $options) {
		if ($path === "<default>") {
			$this->set_option($options);
			return;
		}
		if ($path === "index" || $path === ".") {
			$this->default_route = $path;
			$path = "";
		}
		if (!array_key_exists('weight', $options)) {
			$options['weight'] = ($this->weight_index++) / 1000;
		}
		$this->routes[$path] = $this->_add_route_id($this->_register_route(Route::factory($this, $path, $options)));
		$this->sorted = false;
	}
	private function _add_route_id(Route $route) {
		$id = $route->option("id");
		if (!$id) {
			$id = $route->clean_pattern;
		}
		$this->by_id[strtolower($id)] = $route;
		return $route;
	}
	
	/**
	 * TODO move to zesk\Router\Parser
	 *
	 * @param string $contents
	 * @param array $add_options
	 * @return void
	 */
	function import($contents, array $add_options = null) {
		$app = $this->application;
		$lines = explode("\n", $contents);
		$paths = array();
		$options = array();
		$whites = to_list(" ;\t");
		$tr = array(
			'$zesk_root' => $app->zesk_root(),
			'$zesk_application_root' => $app->path()
		);
		foreach ($lines as $lineno => $line) {
			$firstc = substr($line, 0, 1);
			$line = trim($line);
			if (empty($line) || $line[0] === '#') {
				continue;
			}
			if (in_array($firstc, $whites)) {
				if (count($paths) === 0) {
					$app->logger->warning("Line " . ($lineno + 1) . " of router has setting without path");
				} else {
					list($name, $value) = pair($line, "=", $line, null);
					if ($value === null) {
						$app->logger->warning("Line " . ($lineno + 1) . " of router has no value ($line)");
					} else {
						$trimvalue = trim($value);
						if ($trimvalue === "null") {
							$value = null;
						} else if ($trimvalue === "true" || $trimvalue === "false") {
							$value = to_bool($trimvalue);
						} else if (str::begins($trimvalue, str_split("\"'{[", 1))) {
							try {
								$decoded = JSON::decode($value, null);
								$value = $decoded;
							} catch (Exception_Parse $e) {
								$app->hooks->call("exception", $e);
							}
						}
						if (is_string($value) || is_array($value)) {
							$value = tr($value, $tr);
						}
						if (ends($name, "[]")) {
							$options[strtolower(substr($name, 0, -2))][] = $value;
						} else {
							$options[strtolower($name)] = $value;
						}
					}
				}
			} else {
				// Transition to new tag
				if (count($options) === 0 || count($paths) === 0) {
					$paths[] = unquote($line);
				} else {
					if ($add_options) {
						$options += $add_options;
					}
					foreach ($paths as $path) {
						$this->add_route($path, $options);
					}
					$options = array();
					$paths = array(
						unquote($line)
					);
				}
			}
		}
		if (count($paths) > 0 && count($options) === 0) {
			$this->application->logger->error("Router {path} has no valid options {options_string}", array(
				"path" => $path,
				"options" => $options,
				"options_string" => json_encode($options)
			));
		} else {
			foreach ($paths as $path) {
				$this->add_route($path, $options);
			}
		}
	}
	private function _register_route(Route $route) {
		$class_actions = $route->class_actions();
		if (!$class_actions) {
			return $route;
		}
		foreach ($class_actions as $class => $actions) {
			$class = $this->application->objects->resolve($class);
			foreach ($actions as $action) {
				$this->reverse_routes[strtolower($class)][strtolower($action)][] = $route;
			}
		}
		return $route;
	}
	private function _find_route(array $routes, $action = null, $object = null, $options = null) {
		$options = to_array($options, array());
		if ($this->route) {
			$options += $this->route->option();
		}
		foreach ($routes as $route) {
			assert($route instanceof Route);
			$url = $route->get_route($action, $object, $options);
			if ($url) {
				if (array_key_exists('query', $options)) {
					return URL::query_append($url, $options['query']);
				}
				return $url;
			}
		}
		return null;
	}
	
	/**
	 *
	 * @param array $by_class
	 * @param Model $add
	 * @param string $stop_class
	 * @return \zesk\Model|mixed
	 */
	public static function add_derived_classes(array $by_class, Model $add, $stop_class = null) {
		$id = $add->id();
		foreach ($add->application->classes->hierarchy($add, $stop_class ? $stop_class : "zesk\\Model") as $class) {
			$by_class[$class] = $id;
		}
		return $by_class;
	}
	private function derived_classes(Model $object) {
		$by_class = array();
		$class_object = $object->class_object();
		if (is_array($class_object->has_one) && $class_object->id_column) {
			foreach ($class_object->has_one as $member => $class) {
				$member_object = $object->__get($member);
				if (!$member_object instanceof Model) {
					$this->application->logger->error("Member {member} of object {class} should be an object of {expected_class}, returned {type} with value {value}", array(
						"member" => $member,
						"class" => get_class($object),
						"expected_class" => $class,
						"type" => type($member_object),
						"value" => strval($member_object)
					));
					continue;
				}
				if ($member_object) {
					$id = $member_object->id();
					foreach ($this->application->classes->hierarchy($member_object, "zesk\\Model") as $class) {
						$by_class[$class] = $id;
					}
				}
			}
		}
		$by_class = $object->call_hook_arguments("router_derived_classes", array(
			$by_class
		), $by_class);
		return $by_class;
	}
	/**
	 * Retrieve a route to an object from the router.
	 * Uses current route's context to determine new route.
	 *
	 * @param string $action
	 * @param mixed $object
	 * @param array $options
	 *        	"query" => (string or array). Append query string to URL
	 *        	"inherit_current_route" => (boolean). Use variables from current route when
	 *        	generating this route.
	 *
	 * @return string|null
	 */
	function get_route($action, $object = null, $options = null) {
		$app = $this->application;
		if (is_string($options) && begins($options, "?")) {
			$options = array(
				'query' => URL::query_parse($options)
			);
		}
		$options = to_array($options);
		if ($this->route && to_bool(avalue($options, 'inherit_current_route'))) {
			$options += $this->route->arguments_named();
		}
		if (is_object($object)) {
			$try_classes = $app->classes->hierarchy($object, "zesk\\Model");
			$options += $object->call_hook_arguments("route_options", array(
				$this,
				$action
			), array()) + array(
				"derived_classes" => array()
			);
			if ($object instanceof Model) {
				$options['derived_classes'] += $this->derived_classes($object);
			}
		} else if (is_string($object)) {
			$try_classes = array(
				$object
			);
		} else if (is_array($object)) {
			$try_classes = $object;
		}
		$try_classes[] = "*";
		foreach ($try_classes as $try_class) {
			foreach (array(
				$action,
				"*"
			) as $try_action) {
				if ($try_class !== "*") {
					$try_class = strtolower($app->objects->resolve($try_class));
				}
				$try_actions = avalue($this->reverse_routes, $try_class);
				if (!is_array($try_actions)) {
					continue;
				}
				$try_action = strtolower($try_action);
				$try_routes = avalue($try_actions, $try_action);
				if (!is_array($try_routes)) {
					continue;
				}
				$url = $this->_find_route($try_routes, $action, $object, $options);
				if ($url) {
					$url = $app->hooks->call_arguments("Router::get_route_alter", array(
						$action,
						$object,
						$options
					), $url);
					return $this->prefix . $url;
				}
			}
		}
		$url = $this->call_hook("get_route", $action, $object, $options);
		if (empty($url)) {
			$app->logger->warning("No reverse route for {classes}->{action}", array(
				"classes" => $try_classes,
				"action" => $action
			));
			return null;
		}
		return URL::query_append($this->prefix . $url, avalue($options, 'query', array()));
	}
	
	/**
	 * Retrieve a list of all known controllers
	 *
	 * @return array
	 */
	public function controllers() {
		$controllers = Controller::all($this->application);
		$result = array();
		$objects = $this->application->objects;
		foreach ($controllers as $controller => $settings) {
			$result[$controller] = $objects->factory($controller, $this->application);
		}
		return $result;
	}
}
