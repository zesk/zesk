<?php

/**
 *
 */
namespace zesk;

/**
 *
 * @see Router
 * @author kent
 */
abstract class Route extends Hookable {
	/**
	 *
	 * @var string
	 */
	const OPTION_CACHE = 'cache';

	/**
	 *
	 * @var string
	 */
	const OPTION_STATUS_MESSAGE = 'status_message';

	/**
	 *
	 * @var string
	 */
	const OPTION_STATUS_CODE = 'status_code';

	/**
	 *
	 * @var string
	 */
	const OPTION_OUTPUT_HANDLER = "output_handler";

	/**
	 *
	 * @var array
	 */
	private $map_variables = null;

	/**
	 * The request which resulted in this Route
	 *
	 * @var Request
	 */
	protected $request = null;

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
	 * HTTP Methods to match
	 *
	 * @var array
	 */
	protected $methods = null;

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
	public function __sleep() {
		return array_merge(parent::__sleep(), array(
			"original_options",
			"original_pattern",
			"clean_pattern",
			"pattern",
			"methods",
			"types",
			"url_args",
			"args",
			"named",
		));
	}

	/**
	 * Retrieve variables associated with Route for debugging, etc.
	 *
	 * @return string[]
	 */
	public function variables() {
		return array(
			"class" => get_class($this),
			"original_pattern" => $this->original_pattern,
			"clean_pattern" => $this->clean_pattern,
			"pattern" => $this->pattern,
			"methods" => array_keys($this->methods),
			"types" => $this->types,
			"url_args" => $this->url_args,
			"named" => $this->named,
			"options" => $this->options,
		);
	}

	public function __wakeup() {
		parent::__wakeup();
		$this->router = $this->application->router;
	}

	/**
	 * Getter/setter for request. Is set to non-null when matched.
	 *
	 * @param Request $set
	 * @return \zesk\Route|\zesk\Request
	 */
	public function request(Request $set = null) {
		if ($set) {
			$this->request = $set;
			return $this;
		}
		return $this->request;
	}

	/**
	 * Create a route which matches $pattern with route options
	 *
	 * @param string $pattern
	 *        	A regular-expression style pattern to match the URL
	 * @param array $options
	 */
	public function __construct(Router $router, $pattern, array $options) {
		parent::__construct($router->application, $options);
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
	 * Check that this route is valid.
	 * Throw exceptions if not.
	 *
	 * @return self
	 */
	public function validate() {
		return $this;
	}

	/**
	 * Log a message about this Route
	 *
	 * @param string $message
	 */
	public function log($message, array $arguments = array()) {
		return $this->router->log("info", $message, $arguments);
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
		$pattern = preg_replace('/\{[a-z][\\a-z0-9_]*\s+/i', '{', $pattern);
		return $pattern;
	}

	/**
	 * Retrieve the weight of this route for ordering.
	 *
	 * @return double
	 */
	public function weight() {
		return $this->option_double('weight', 0);
	}

	/**
	 * Convert to string
	 *
	 * @return string
	 */
	public function __toString() {
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
		return zesk_sort_weight_array($a->option(), $b->option());
	}

	/**
	 * Replace components in the $options which are constant and will not change
	 *
	 * @param string $pattern
	 * @param array $options
	 * @return array
	 */
	public static function preprocess_options($pattern, array $options) {
		// Replace parts which do not have variables set
		$parts = explode("/", strval(strtr($pattern, array(
			'(' => '',
			')' => '',
		))));
		foreach ($parts as $index => $part) {
			if (!count(map_tokens($part)) === 0) {
				unset($parts[$index]);
			}
		}
		$options = map($options, $parts);
		return $options;
	}

	/**
	 * Create a route from a set of options
	 *
	 * @param string $pattern
	 * @param array $options
	 * @return Route
	 */
	public static function factory(Router $router, $pattern, array $options) {
		/**
		 * Ordering of this array is important. "method" is a parameter to "controller" and others, so it should go last.
		 * Similarly, "file" may be an argument for other `Route`s so it goes last as well.
		 * @var array $types
		 */
		$types = array(
			'controller' => Route_Controller::class,
			'command' => Route_Command::class,
			'theme' => Route_Theme::class,
			'method' => Route_Method::class,
			'file' => Route_Content::class,
		);
		$options = self::preprocess_options($pattern, $options);
		foreach ($types as $k => $class) {
			if (array_key_exists($k, $options)) {
				return $router->application->factory($class, $router, $pattern, $options);
			}
		}
		return $router->application->factory(Route_Content::class, $router, $pattern, $options);
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
		list($methods, $pattern) = pair($pattern, ":", "GET|POST", $pattern);
		$this->methods = ArrayTools::flip_assign(to_list($methods, array(), "|"), true);
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
					$match[2],
				);
				$replace[$key] = "([^/]*)";
				++$index;
			}
		}
		$parts = explode("/", str_replace(array(
			chr(0x01),
			chr(0x02),
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
	public function arguments_named() {
		return $this->named;
	}

	/**
	 * Retrieve the arguments for this route based on class name
	 *
	 * @param $class Single
	 *        	class to retrieve
	 * @return array
	 */
	public function arguments_by_class($class = null, $index = null) {
		$this->_process_arguments();
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
	public function arguments_indexed() {
		return $this->url_args;
	}

	/**
	 * Retrieve the numbered arg
	 */
	public function arg($index, $default = null) {
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
	public function url_replace($name, $value = null) {
		if (is_string($name)) {
			$named = array(
				$name => $value,
			);
		} elseif (is_array($name)) {
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
	final public function match($url, $method = "GET") {
		if (!isset($this->methods[$method]) || !$this->methods[$method]) {
			return false;
		}
		if (!preg_match($this->pattern, $url, $matches)) {
			return false;
		}
		/* Convert the arguments into any types specified */
		$this->url_args = explode("/", $url) + array_fill(0, count($this->types), null);
		$this->args = null;
		return true;
	}

	private function _handle_argument($index, $type_name) {
		$object = null;
		$arg = $this->url_args[$index];
		$application = $this->application;

		list($type, $name) = $type_name;
		if ($arg !== null && !empty($type)) {
			$method = "convert_$type";
			if (method_exists($this, $method)) {
				$arg = $this->$method($arg, $name);
			} else {
				$object = $application->model_factory($type);
				if ($object instanceof Model) {
					$object = $object->call_hook_arguments("router_argument", array(
						$this,
						$arg,
					), $object);
					if (!$object instanceof Model) {
						throw new Exception_NotFound("Model $type $arg not found");
					}
				}
				$arg = $object;
			}
		}
		if ($name !== null) {
			if (is_object($object)) {
				foreach ($application->classes->hierarchy($object, "Model") as $class) {
					$this->by_class[strtolower($class)][$name] = $object;
				}
			}
			$this->named[$name] = $arg;
		}
		$this->named["uri$index"] = $this->url_args[$index] = $arg;
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
			$this->_handle_argument($index, $type_name);
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

	/**
	 * Convert array values to objects
	 *
	 * @param mixed $mixed
	 * @return mixed
	 */
	protected function _map_variables($mixed, Response $response = null) {
		if (!is_array($mixed)) {
			return $mixed;
		}
		$app = $this->router->application;
		if (!is_array($this->map_variables)) {
			$request = $this->request;
			$this->map_variables = array(
				'{application}' => $app,
				'{request}' => $request,
				'{response}' => $response,
				'{route}' => $this,
				'{router}' => $this->router,
			);
			$this->map_variables += ArrayTools::kwrap($request->variables(), '{request.', '}');
			$this->map_variables += ArrayTools::kwrap($request->url_variables(), '{url.', '}');
		}
		return ArrayTools::map_values($mixed, $this->map_variables);
	}

	private function guess_content_type() {
		$content_type = $this->option("content_type");
	}

	/**
	 * Overridable method before execution
	 *
	 * @return Response
	 */
	protected function _before() {
		$application = $this->application;
		$response = $application->response_factory($this->request, $this->guess_content_type());
		if ($this->has_option('cache')) {
			$cache = $this->option('cache');
			if (is_scalar($cache)) {
				if (to_bool($cache)) {
					$response->cache_forever();
				}
			} elseif (is_array($cache)) {
				$response->cache($cache);
			} else {
				$application->logger->warning("Invalid cache setting for route {route}: {cache}", array(
					"route" => $this->clean_pattern,
					"cache" => _dump($cache),
				));
			}
		}
		$v = $this->option(self::OPTION_STATUS_CODE);
		if ($v) {
			$response->status($v);
		}
		$v = $this->option(self::OPTION_STATUS_MESSAGE);
		if ($v) {
			$response->status($response->status_code, $v);
		}
		$v = $this->option(self::OPTION_OUTPUT_HANDLER);
		if ($v) {
			$response->output_handler($v);
		}
		if ($this->option_bool("json")) {
			$response->is_json(true);
		} elseif ($this->option_bool("html")) {
			$response->is_html(true);
		}
		$this->args = $this->_map_variables($this->args, $response);
		return $response;
	}

	/**
	 * Execute the route
	 *
	 * @param Application $app
	 */
	abstract protected function _execute(Response $response);

	/**
	 * Overridable method after execution
	 *
	 */
	protected function _after(Response $response) {
		if (array_key_exists("redirect", $this->options)) {
			$response->redirect($this->options['redirect']);
		}
	}

	/**
	 */
	protected function _permissions(Response $response) {
		$permission = $this->option("permission");
		$permissions = array();
		if ($permission) {
			$permissions[] = array(
				"action" => $this->option("permission"),
				"context" => $this->option("permission context"),
				"options" => $this->option_array("permission options"),
			);
		}
		$permissions = array_merge($permissions, $this->option_array("permissions", array()));
		$permissions = $this->_map_variables($permissions, $response);
		$app = $this->router->application;
		foreach ($permissions as $permission) {
			$action = $context = null;
			$options = array();
			if (is_array($permission)) {
				extract($permission, EXTR_IF_EXISTS);
			} elseif (is_string($permission)) {
				$action = $permission;
			}
			if (!$context instanceof Model && $context !== null) {
				$app->logger->warning("Invalid permission context in route {url}, permission {action}, type={type}, value={value}", array(
					"url" => $this->clean_pattern,
					"action" => $action,
					"type" => type($context),
					"value" => strval($context),
				));
				$context = null;
			}
			$app->user($response->request, true)->must($action, $context, $options);
		}
	}

	/**
	 *
	 * @return \zesk\Route
	 */
	final protected function _map_options() {
		$this->original_options = $this->options;
		$this->options = map($this->options, $this->url_args + $this->named);
		return $this;
	}

	/**
	 *
	 * @return \zesk\Route
	 */
	final protected function _unmap_options() {
		$this->options = $this->original_options + $this->options;
		return $this;
	}

	/**
	 * Execute the route and return a Response
	 *
	 * @param Request $request
	 * @return Response
	 */
	final public function execute() {
		$this->_process_arguments();
		$this->_map_options();
		$response = $this->_before();
		$this->_permissions($response);
		$template = Template::factory($this->application, null, array(
			"response" => $response,
			"route" => $this,
		))->push();
		$exec_response = $this->_execute($response);
		if ($exec_response instanceof Response) {
			$response = $exec_response;
		}
		$this->_after($response);
		$this->_unmap_options();
		$template->pop();
		return $response;
	}

	/**
	 * Return array of class => array(action1, action2, action3)
	 *
	 * @return Ambigous <mixed, multitype:>
	 */
	public function class_actions() {
		if ($this->has_option("class_actions")) {
			return $this->option_array("class_actions");
		}
		// $class_actions =
		$classes = $this->option_list("classes");
		$actions = $this->option_list("actions");
		if (!$classes) {
			return array();
		}
		if (!$actions) {
			$action = $this->option('action');
			if (!empty($action) && $action !== '{action}') {
				$actions = array(
					$action,
				);
			} else {
				$actions = array(
					"*",
				);
			}
		}
		$result = array();
		foreach ($classes as $class) {
			$result[$class] = $actions;
		}
		return $result;
	}

	/**
	 *
	 * @param string $action
	 * @param string|object $object
	 * @param array $options
	 *        	Optional options relating to the requested route
	 * @return array
	 */
	protected function get_route_map($action, $object = null, $options = null) {
		$object_hierarchy = is_object($object) ? $this->application->classes->hierarchy($object, Model::class) : array();
		$derived_classes = avalue($options, 'derived_classes', array());
		$options = to_array($options, array());
		$map = array(
			"action" => $action,
		) + $options;

		if (count($object_hierarchy) > 0) {
			foreach ($this->types as $type) {
				if (!is_array($type)) {
					continue;
				}
				list($part_class, $part_name) = $type;
				if (in_array($part_class, $object_hierarchy)) {
					$map[$part_name] = $object instanceof Model ? $object->id() : avalue($options, $part_name, "");
				} elseif (array_key_exists($part_class, $derived_classes)) {
					$map[$part_name] = $derived_classes[$part_class];
				} else {
					$option = avalue($options, $part_name);
					if ($option instanceof Model) {
						$id = $option->id();
						if (is_scalar($id)) {
							$map[$part_name] = $id;
						}
					}
				}
			}
			if ($object instanceof Model && !array_key_exists("id", $map)) {
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
	public function get_route($action, $object = null, $options = null) {
		$route_map = $this->get_route_map($action, $object, $options);
		$map = $this->call_hook_arguments("get_route_map", array(
			$route_map,
		), $route_map);
		$result = rtrim(map_clean(map($this->clean_pattern, $map), "/"));
		return $result;
	}
}
