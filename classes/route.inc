<?php

/**
 *
 * @see Router
 * @author kent
 */
abstract class Route extends Hookable {
	
	/**
	 * The router
	 *
	 * @var Router
	 */
	public $router = null;
	
	/**
	 * Original options before being mapped
	 */
	protected $original_options = array();
	
	/**
	 * Original pattern passed in
	 *
	 * @var string
	 */
	public $original_pattern = null;
	
	/**
	 * Pattern with replaceable variables
	 *
	 * @var string
	 */
	public $clean_pattern = null;
	
	/**
	 * Pattern to match the URL, compiled
	 *
	 * @var string
	 */
	protected $pattern = null;
	
	/**
	 * Array of types indexed on URL part
	 *
	 * @var array
	 */
	protected $types = array();
	
	/**
	 * Parts of URL, with null values for unspecified settings
	 *
	 * @var array
	 */
	protected $url_args = array();
	
	/**
	 * Arguments to function/method call
	 *
	 * @var array
	 */
	protected $args = array();
	
	/**
	 * Named arguments (name => value pairs)
	 * Note unnamed arguments can be accessed as url0 url1 url2 etc.
	 *
	 * @var array
	 */
	protected $named = array();
	
	/**
	 * arguments by class (lowercase class => array("name" => object))
	 *
	 * @var array
	 */
	protected $by_class = array();
	
	/**
	 * Return list of members to save upon sleep
	 *
	 * @see Options::__sleep()
	 */
	function __sleep() {
		return array_merge(parent::__sleep(), array(
			"original_options",
			"original_pattern",
			"clean_pattern",
			"pattern",
			"types",
			"url_args",
			"args",
			"named"
		));
	}
	
	/**
	 * Create a route which matches $pattern with route options
	 *
	 * @param string $pattern
	 *        	A regular-expression style pattern to match the URL
	 * @param array $options        	
	 */
	function __construct(Router $router, $pattern, array $options) {
		parent::__construct($options);
		$this->router = $router;
		$this->original_pattern = $pattern;
		$this->compile_route_pattern($pattern);
		$this->clean_pattern = $this->clean_pattern($pattern);
		$this->inherit_global_options();
		$this->initialize();
	}
	
	/**
	 * Set up this route
	 */
	protected function initialize() {
		// Generic initial, override in subclasses
		// As a policy, always call parent::initialize();
	}
	
	/**
	 * Check that this route is valid. Throw exceptions if not.
	 * 
	 * @return self
	 */
	public function validate(Application $application) {
		return $this;
	}
	
	/**
	 * Log a message about this controller
	 *
	 * @param string $message        	
	 */
	function log($message) {
		return $this->router->log($message);
	}
	
	/**
	 * Take the input pattern and convert it into a simple pattern suitable for
	 * variable replacement using map().
	 * Removes all parenthesis and removes types from variables in brackets, so:
	 * "path/to/{action}(/{User ID})" => "path/to/{action}/{ID}"
	 *
	 * @param string $pattern        	
	 * @return string
	 * @see map
	 */
	private function clean_pattern($pattern) {
		// Clean optional parens
		$pattern = preg_replace('/[()]/', '', $pattern);
		// Clean value types
		$pattern = preg_replace('/\{[A-Za-z_]+\s+/', '{', $pattern);
		return $pattern;
	}
	
	/**
	 * Retrieve the weight of this route for ordering.
	 *
	 * @return double
	 */
	function weight() {
		return $this->option_double('weight', 0);
	}
	
	/**
	 * Convert to string
	 *
	 * @return string
	 */
	function __toString() {
		return strval($this->original_pattern);
	}
	
	/**
	 * Compare weights
	 *
	 * @param Route $a        	
	 * @param Route $b        	
	 * @return double
	 */
	public static function compare_weight(Route $a, Route $b) {
		return zesk::sort_weight_array($a->option(), $b->option());
	}
	
	/**
	 * Create a route from a set of options
	 *
	 * @param string $pattern        	
	 * @param array $options        	
	 * @return Route
	 */
	public static function factory(Router $router, $pattern, array $options) {
		foreach (array(
			"method" => "Route_Method",
			"controller" => "Route_Controller",
			"command" => "Route_Command",
			"theme" => "Route_Theme",
			"template" => "Route_Theme"
		) as $k => $class) {
			if (array_key_exists($k, $options)) {
				return zesk::factory($class, $router, $pattern, $options);
			}
		}
		return zesk::factory("Route_Content", $router, $pattern, $options);
	}
	
	/**
	 * Clean a parameter type
	 *
	 * @param string $type        	
	 * @return string
	 */
	private static function clean_type($type) {
		return preg_replace('/[^\\\\0-9A-Za-z_]/i', "_", $type);
	}
	
	/**
	 * Take a pattern and convert it into a Perl REGular expression (PREG)
	 *
	 * @param string $pattern        	
	 * @return void
	 */
	private function compile_route_pattern($pattern) {
		$replace = array();
		$parameters = array();
		$parameter_names = array();
		$re_pattern = $pattern;
		$re_pattern = str_replace('\\*', chr(0x04), $re_pattern);
		$re_pattern = str_replace('(', chr(0x01), $re_pattern);
		$re_pattern = str_replace(')', chr(0x02), $re_pattern);
		$re_pattern = str_replace('*', chr(0x03), $re_pattern);
		
		$matches = false;
		$types = array();
		if (preg_match_all('/{([^ }]+ )?([^ }]+)}/', $re_pattern, $matches, PREG_SET_ORDER)) {
			$index = 1;
			foreach ($matches as $match) {
				$key = "#@$index@#";
				$re_pattern = implode($key, explode($match[0], $re_pattern, 2));
				$types[$key] = array(
					self::clean_type(trim($match[1])),
					$match[2]
				);
				$replace[$key] = "([^/]*)";
				++$index;
			}
		}
		$parts = explode("/", str_replace(array(
			chr(0x01),
			chr(0x02)
		), "", $re_pattern));
		
		foreach ($parts as $index => $part) {
			$this->types[$index] = array_key_exists($part, $types) ? $types[$part] : true;
		}
		
		$re_pattern = preg_quote($re_pattern);
		$re_pattern = strtr($re_pattern, $replace);
		$re_pattern = str_replace(chr(0x01), '(?:', $re_pattern);
		$re_pattern = str_replace(chr(0x02), ')?', $re_pattern);
		$re_pattern = str_replace(chr(0x03), '.*', $re_pattern);
		$re_pattern = str_replace(chr(0x04), '\\*', $re_pattern);
		
		$this->pattern = '%^' . $re_pattern . '$%';
	}
	
	/**
	 * Retrieve the arguments for this route which have explicit names
	 *
	 * @return array
	 */
	function arguments_named() {
		return $this->named;
	}
	/**
	 * Retrieve the arguments for this route based on class name
	 *
	 * @param $class Single
	 *        	class to retrieve
	 * @return array
	 */
	function arguments_by_class($class = null, $index = null) {
		if ($class === null) {
			return $this->by_class;
		}
		$result = avalue($this->by_class, strtolower($class), array());
		if ($index === null) {
			return $result;
		}
		if (is_numeric($index)) {
			return avalue(array_values($result), $index);
		}
		return avalue($result, $index);
	}
	
	/**
	 * Retrieve the arguments for this route based on position
	 *
	 * @return array
	 */
	function arguments_indexed() {
		return $this->url_args;
	}
	
	/**
	 * Retrieve the numbered arg
	 */
	function arg($index, $default = null) {
		return avalue($this->url_args, $index, $default);
	}
	/**
	 * Replace variables in the cleaned pattern
	 *
	 * @param string|array $name
	 *        	Input variables
	 * @param string $value
	 *        	Optional value
	 * @return string
	 */
	function url_replace($name, $value = null) {
		if (is_string($name)) {
			$named = array(
				$name => $value
			);
		} else if (is_array($name)) {
			$named = $name;
		} else {
			$named = array();
		}
		$url = map($this->clean_pattern, $named + $this->named);
		return $url;
	}
	
	/**
	 * Determine if a url matches this route.
	 * If it matches, configure arguments by index as $this->args, and by name as $this->named
	 * Enter description here ...
	 *
	 * @param unknown_type $url        	
	 * @throws Exception_NotFound
	 */
	final function match($url) {
		if (!preg_match($this->pattern, $url, $matches)) {
			return false;
		}
		/* Convert the arguments into any types specified */
		$this->url_args = explode("/", $url) + array_fill(0, count($this->types), null);
		$this->args = null;
		return true;
	}
	
	/**
	 * Handle arguments from URL
	 *
	 * @throws Exception_NotFound
	 * @return void boolean
	 */
	private function _process_arguments() {
		if ($this->args !== null) {
			return true;
		}
		$this->args = array();
		$this->named = array();
		// echo "<pre>"; var_dump($this->types); echo "</pre>";
		foreach ($this->types as $index => $type_name) {
			if ($type_name === true) {
				continue;
			}
			$object = null;
			$arg = $this->url_args[$index];
			list($type, $name) = $type_name;
			if ($arg !== null && !empty($type)) {
				$method = "convert_$type";
				if (method_exists($this, $method)) {
					$arg = $this->$method($arg, $name);
				} else {
					$object = zesk::factory($type);
					if ($object instanceof Object) {
						$object = $object->hook_array("router_argument", array(
							$this,
							$arg
						), $object);
						if (!$object) {
							throw new Exception_NotFound("Object $type $arg not found");
						}
					}
					$arg = $object;
				}
			}
			if ($name !== null) {
				if (is_object($object)) {
					global $zesk;
					foreach ($zesk->classes->hierarchy($object, "Model") as $class) {
						$this->by_class[strtolower($class)][$name] = $object;
					}
				}
				$this->named[$name] = $arg;
			}
			$this->named["uri$index"] = $this->url_args[$index] = $arg;
		}
		$arguments = $this->option_list("arguments", null);
		if (is_array($arguments)) {
			foreach ($arguments as $arg) {
				$this->args[] = is_numeric($arg) ? avalue($this->url_args, $arg, null) : $arg;
			}
		}
		// echo "<pre>"; var_dump($this->args); var_dump($this->named); echo "</pre>";
		

		return true;
	}
	
	/**
	 * Convert URL parameter to integer
	 *
	 * @param string $x        	
	 * @throws Exception_File_NotFound
	 * @return number
	 */
	final protected function convert_integer($x) {
		if (!is_numeric($x)) {
			throw new Exception_File_NotFound("Invalid integer format: $x");
		}
		return intval($x);
	}
	
	/**
	 * Convert URL parameter to double
	 *
	 * @param string $x        	
	 * @throws Exception_File_NotFound
	 * @return number
	 */
	final protected function convert_double($x) {
		if (!is_numeric($x)) {
			throw new Exception_File_NotFound("Invalid double format");
		}
		return intval($x);
	}
	
	/**
	 * Convert URL parameter to string
	 *
	 * @param string $x        	
	 * @throws Exception_File_NotFound
	 * @return string
	 */
	final protected function convert_string($x) {
		return $x;
	}
	
	/**
	 * Convert URL parameter to string
	 *
	 * @param string $x        	
	 * @throws Exception_File_NotFound
	 * @return string
	 */
	final protected function convert_option($x, $name) {
		$this->set_option($name, $x);
		return $x;
	}
	
	/**
	 * Convert URL parameter to array
	 *
	 * @param string $x        	
	 * @throws Exception_File_NotFound
	 * @return array
	 */
	final protected function convert_array($x) {
		return $this->convert_list($x);
	}
	
	/**
	 * Convert URL parameter to list
	 *
	 * @param string $x        	
	 * @throws Exception_File_NotFound
	 * @return array
	 */
	final protected function convert_list($x) {
		return to_list($x, array());
	}
	
	/**
	 * Convert URL parameter to list delimited by dashes
	 *
	 * @param string $x        	
	 * @throws Exception_File_NotFound
	 * @return array
	 */
	final protected function convert_dash_list($x) {
		return to_list($x, array(), "-");
	}
	
	/**
	 * Convert URL parameter to list delimited by semicolons
	 *
	 * @param string $x        	
	 * @throws Exception_File_NotFound
	 * @return array
	 */
	final protected function convert_semicolon_list($x) {
		return to_list($x, array(), ";");
	}
	
	/**
	 * Convert URL parameter to list delimited by commas
	 *
	 * @param string $x        	
	 * @throws Exception_File_NotFound
	 * @return array
	 */
	final protected function convert_comma_list($x) {
		return to_list($x, array(), ",");
	}
	private $map_variables = null;
	
	/**
	 * Convert array values to objects
	 *
	 * @param Application $app        	
	 * @param mixed $mixed        	
	 * @return mixed
	 */
	protected function _map_variables(Application $app, $mixed) {
		if (!is_array($this->map_variables)) {
			$response = $app->response;
			$request = $app->request;
			$this->map_variables = array(
				'{application}' => $app,
				'{request}' => $request,
				'{response}' => $response,
				'{route}' => $this,
				'{router}' => $request->router
			);
			$this->map_variables += arr::kwrap($app->request->variables(), '{request.', '}');
		}
		return arr::map_values($mixed, $this->map_variables);
	}
	
	/**
	 * Overridable method before execution
	 *
	 * @param Application $app        	
	 */
	protected function _before(Application $app) {
		$response = $app->response;
		if ($this->has_option('cache')) {
			$cache = $this->option('cache');
			if (is_scalar($cache)) {
				if (to_bool($cache)) {
					$response->cache_forever();
				}
			} else if (is_array($cache)) {
				$response->cache($cache);
			} else {
				log::warning("Invalid cache setting for route {route}: {cache}", array(
					"route" => $this->clean_pattern,
					"cache" => _dump($cache)
				));
			}
		}
		foreach (to_list("content_type;status_code;status_message") as $k) {
			$v = $this->option($k);
			if ($v) {
				$response->$k = $v;
			}
		}
		$this->args = $this->_map_variables($app, $this->args);
	}
	
	/**
	 * Execute the route
	 *
	 * @param Application $app        	
	 */
	abstract protected function _execute(Application $app);
	
	/**
	 * Overridable method after execution
	 *
	 * @param Request $request        	
	 * @param Response $response        	
	 */
	protected function _after(Application $app) {
		if (array_key_exists("redirect", $this->options)) {
			$app->response->redirect($this->options['redirect']);
		}
	}
	protected function _permissions(Application $app) {
		$permission = $this->option("permission");
		$permissions = array();
		if ($permission) {
			$permissions[] = array(
				"action" => $this->option("permission"),
				"context" => $this->option("permission context"),
				"options" => $this->option_array("permission options")
			);
		}
		$permissions = array_merge($permissions, $this->option_array("permissions", array()));
		$permissions = $this->_map_variables($app, $permissions);
		foreach ($permissions as $permission) {
			$action = $context = $options = null;
			extract($permission, EXTR_IF_EXISTS);
			if (!$context instanceof Model && $context !== null) {
				log::warning("Invalid permission context in route {url}, permission {action}, type={type}, value={value}", array(
					"url" => $this->clean_pattern,
					"action" => $action,
					"type" => type($context),
					"value" => strval($context)
				));
				$context = null;
			}
			$app->request->user()->must($action, $context, $options);
		}
	}
	final protected function _map_options() {
		$this->_process_arguments();
		$this->original_options = $this->options;
		$this->options = map($this->options, $this->url_args + $this->named);
		return $this;
	}
	final protected function _unmap_options() {
		$this->options = $this->original_options + $this->options;
		return $this;
	}
	/**
	 * Execute the route
	 *
	 * @param Request $request        	
	 * @param Response $response        	
	 */
	final function execute(Application $app) {
		$this->router = $app->router;
		$this->_map_options();
		$this->_permissions($app);
		$this->_before($app);
		$this->_execute($app);
		$this->_after($app);
		$this->_unmap_options();
	}
	
	/**
	 * Return array of class => array(action1, action2, action3)
	 *
	 * @return Ambigous <mixed, multitype:>
	 */
	function class_actions() {
		if ($this->has_option("class_actions")) {
			return $this->option_array("class_actions");
		}
		// $class_actions =
		$classes = $this->option_list("classes");
		$actions = $this->option_list("actions");
		if (!$classes) {
			return null;
		}
		if (!$actions) {
			$action = $this->option('action');
			if (!empty($action) && $action !== '{action}') {
				$actions = array(
					$action
				);
			} else {
				$actions = array(
					"*"
				);
			}
		}
		$result = array();
		foreach ($classes as $class) {
			$result[$class] = $actions;
		}
		return $result;
	}
	protected function get_route_map($action, $object = null, $options = null) {
		global $zesk;
		$classes = $zesk->classes->hierarchy($object, "Object");
		$options = to_array($options, array());
		$map = array(
			"action" => $action
		) + $options;
		
		if (count($classes) > 0) {
			foreach ($this->types as $type) {
				if (!is_array($type)) {
					continue;
				}
				list($part_class, $part_name) = $type;
				if (in_array($part_class, $classes)) {
					$map[$part_name] = $object instanceof Object ? $object->id() : avalue($options, $part_name, "");
				} else {
					$option = avalue($options, $part_name);
					if ($option instanceof Object) {
						$id = $option->id();
						if (is_scalar($id)) {
							$map[$part_name] = $id;
						}
					}
				}
			}
			if ($object instanceof Object && !array_key_exists("id", $map)) {
				$map['id'] = $object->id();
			}
		}
		return $map;
	}
	/**
	 * Retrieve the reverse route for a particular action
	 *
	 * @param Request $request        	
	 * @param Response $response        	
	 */
	function get_route($action, $object = null, $options = null) {
		$route_map = $this->get_route_map($action, $object, $options);
		$map = $this->hook_array("get_route_map", array(
			$route_map
		), $route_map);
		$result = map($this->clean_pattern, $map);
		return $result;
	}
}