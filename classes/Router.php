<?php

/**
 * @package zesk
 * @subpackage system
 * @author kent
 * @copyright Copyright &copy; 2016, Market Acumen, Inc.
 */
namespace zesk;

use stdClass;
use zesk\Router\Parser;

/**
 * @see Route
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
	public function __sleep() {
		return array_merge(array(
			'application_class',
			'reverse_routes',
			'routes',
			'prefix',
			'default_route',
			"aliases",
		), parent::__sleep());
	}

	/**
	 */
	public function __wakeup() {
		$this->by_id = array();
		$this->application = __wakeup_application();
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
	public static function factory(Application $application, array $options = array()) {
		return $application->factory(__CLASS__, $application, $options);
	}

	/**
	 * Router constructor.
	 * @param Application $application
	 * @param array $options
	 */
	public function __construct(Application $application, array $options = array()) {
		parent::__construct($application, $options);
		$this->application_class = get_class($application);
		$this->call_hook("construct");
	}

	/**
	 *
	 * @param Application $kernel
	 * @throws Exception_Semantics
	 */
	public static function hooks(Application $kernel) {
		$kernel->hooks->add(Hooks::HOOK_CONFIGURED, array(
			__CLASS__,
			"configured",
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
	 * Cache this router
	 */
	public function cache($id) {
		$item = $this->application->cache->getItem(__CLASS__);
		$value = new stdClass();
		$value->id = $id;
		$value->router = $this;
		$this->application->cache->saveDeferred($item->set($value));
		return $this;
	}

	/**
	 * Whether this router is cached; performance optimization
	 *
	 * @return Router or null if not cached
	 * @param null $id
	 * @throws \Psr\Cache\InvalidArgumentException
	 */
	public function cached($id = null) {
		$item = $this->application->cache->getItem(__CLASS__);
		if (!$item->isHit()) {
			return null;
		}
		$value = $item->get();
		if ($id !== null) {
			if ($id !== $value->id) {
				return null;
			}
		}
		return $value->router;
	}

	/**
	 *
	 * @param Route $a
	 * @param Route $b
	 * @return number
	 */
	public static function compare_weight(Route $a, Route $b) {
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
	public function route($id) {
		return avalue($this->by_id, strtolower($id));
	}

	/**
	 *
	 * @return Route[]
	 */
	public function routes() {
		if (!$this->sorted) {
			$this->_sort();
			$this->sorted = true;
		}
		return $this->routes;
	}

	/**
	 *
	 * @param string $set
	 * @return string
	 */
	public function prefix($set = null) {
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

	/**
	 * Match a request to this router. Return a Route. Returned route will have ->request() set to this object.
	 *
	 * @param Request $request
	 * @return Route|null
	 * @todo make this not O(n)
	 * @throws Exception_NotFound
	 */
	public function match(Request $request) {
		$this->request = $request;
		$path = strval($request->path());
		$method = strval($request->method());
		if ($this->prefix) {
			$path = StringTools::unprefix($path, $this->prefix);
		}
		$path = avalue($this->aliases, $path, $path);
		foreach ($this->routes() as $route) {
			if ($route->match($path, $method)) {
				$route->request($request);
				$this->log("debug", "Matched {path} to {route}", compact("path", "route"));
				return $route;
			}
		}
		$this->log("warning", "No matches for {path}", compact("path"));
		return null;
	}

	/**
	 * @deprecated 2019-04 - route is not used in Router
	 * @param string $name
	 * @param string $value
	 * @return string
	 */
	public function url_replace($name, $value = null) {
		$this->application->deprecated("Does nothing");
		return null;
	}

	/**
	 * Add a route
	 *
	 * @param string $path
	 * @param array $options
	 * @return Route|Router
	 */
	public function add_route($path, array $options) {
		if ($path === "<default>") {
			$this->set_option($options);
			return $this;
		}
		if ($path === "index" || $path === ".") {
			$this->default_route = $path;
			$path = "";
		}
		if (!array_key_exists('weight', $options)) {
			$options['weight'] = ($this->weight_index++) / 1000;
		}
		$this->sorted = false;
		return $this->routes[$path] = $route = $this->_add_route_id($this->_register_route(Route::factory($this, $path, $options)));
	}

	/**
	 *
	 * @param Route $route
	 * @return \zesk\Route
	 */
	private function _add_route_id(Route $route) {
		$id = $route->option("id");
		if (!$id) {
			$id = $route->clean_pattern;
		}
		$this->by_id[strtolower($id)] = $route;
		return $route;
	}

	/**
	 * Load a Router file
	 *
	 * @see Parser
	 * @param string $contents
	 * @param array $add_options
	 * @return void
	 */
	public function import($contents, array $add_options = null) {
		$parser = new Parser($contents);
		$parser->execute($this, $add_options);
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
		$by_class = $object->call_hook_arguments("router_derived_classes", array(
			$by_class,
		), $by_class);
		return $by_class;
	}

	/**
	 * Retrieve a route to an object from the router.
	 * Uses current route's context to determine new route.
	 *
	 * @param string $action
	 * @param mixed $object An instance of a Model, a name of a model class, a name of a Controller class
	 * @param array $options
	 *        	"query" => (string or array). Append query string to URL
	 *        	"inherit_current_route" => (boolean). Use variables from current route when
	 *        	generating this route.
	 *
	 * @return string|null
	 */
	public function get_route($action, $object = null, $options = null) {
		$original_action = $action;
		$app = $this->application;
		if (is_string($options) && begins($options, "?")) {
			$options = array(
				'query' => URL::query_parse($options),
			);
		}
		$options = to_array($options);
		$route = $options['current_route'] ?? null;
		if ($route instanceof Route) {
			$options += $route->arguments_named();
		}
		if (is_object($object) && $object instanceof Hookable) {
			$try_classes = $app->classes->hierarchy($object, Model::class);
			$options += $object->call_hook_arguments("route_options", array(
				$this,
				$action,
			), array()) + array(
				"derived_classes" => array(),
			);
			if ($object instanceof Model) {
				$options['derived_classes'] += $this->derived_classes($object);
			}
		} elseif (is_string($object)) {
			$try_classes = array(
				$object,
			);
		} elseif (is_array($object)) {
			$try_classes = $object;
		} else {
			throw new Exception_Unsupported("Object of type {class} not supported in {method}", array(
				"class" => type($object),
				"method" => __METHOD__,
			));
		}
		$try_classes[] = "*";
		foreach ($try_classes as $try_class) {
			foreach (array(
				$action,
				"*",
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
					$url = $app->hooks->call_arguments(__CLASS__ . "::get_route_alter", array(
						$action,
						$object,
						$options,
					), $url);
					return $this->prefix . $url;
				}
			}
		}
		$url = $this->call_hook_arguments("get_route", array(
			$action,
			$object,
			$options,
		), null);
		if (empty($url)) {
			$app->logger->warning("No reverse route for {classes}->{action} {backtrace}", array(
				"classes" => $try_classes,
				"action" => $original_action,
				"backtrace" => _backtrace(),
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
