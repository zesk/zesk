<?php
declare(strict_types=1);

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
	public const OPTION_CACHE = 'cache';

	/**
	 *
	 * @var string
	 */
	public const OPTION_STATUS_MESSAGE = 'status_message';

	/**
	 *
	 * @var string
	 */
	public const OPTION_STATUS_CODE = 'status_code';

	/**
	 *
	 * @var string
	 */
	public const OPTION_OUTPUT_HANDLER = 'output_handler';

	/**
	 * The request which resulted in this Route
	 *
	 * @var Request
	 */
	protected Request $request;

	/**
	 * The router
	 *
	 * @var Router
	 */
	public Router $router;

	/**
	 * Original options before being mapped
	 */
	protected array $original_options = [];

	/**
	 * Original pattern passed in
	 *
	 * @var string
	 */
	public string $original_pattern;

	/**
	 * Pattern with replaceable variables
	 *
	 * @var string
	 */
	public string $clean_pattern;

	/**
	 * HTTP Methods to match
	 *
	 * @var array
	 */
	protected array $methods = [];

	/**
	 * Pattern to match the URL, compiled
	 *
	 * @var string
	 */
	public string $pattern;

	/**
	 * Array of types indexed on URL part
	 *
	 * @var array
	 */
	protected array $types = [];

	/**
	 * Parts of URL, with null values for unspecified settings
	 *
	 * @var array
	 */
	protected array $urlParts = [];

	/**
	 * Arguments to function/method call
	 *
	 * @var array
	 */
	protected array $args = [];

	/**
	 * @var bool
	 */
	private bool $_arguments_processed = false;

	/**
	 * Named arguments (name => value pairs)
	 * Note unnamed arguments can be accessed as url0 url1 url2 etc.
	 *
	 * @var array
	 */
	protected array $named = [];

	/**
	 * arguments by class (lowercase class => array("name" => object))
	 *
	 * @var array
	 */
	protected array $byClass = [];

	/**
	 * Return list of members to save upon sleep
	 *
	 * @see Options::__sleep()
	 */
	public function __sleep() {
		return array_merge(parent::__sleep(), [
			'original_options', 'original_pattern', 'clean_pattern', 'pattern', 'methods', 'types', 'url_args', 'args',
			'named', 'byClass',
		]);
	}

	/**
	 * Retrieve variables associated with Route for debugging, etc.
	 *
	 * @return string[]
	 */
	public function variables(): array {
		return [
			'class' => get_class($this), 'original_pattern' => $this->original_pattern,
			'clean_pattern' => $this->clean_pattern, 'pattern' => $this->pattern,
			'methods' => array_keys($this->methods), 'types' => $this->types, 'url_args' => $this->urlParts,
			'named' => $this->named, 'byClass' => $this->byClass, 'options' => $this->options,
		];
	}

	public function __wakeup(): void {
		parent::__wakeup();
		$this->router = $this->application->router;
	}

	/**
	 * Getter/setter for request. Is set to non-null when matched.
	 * @return Request
	 */
	public function request(): Request {
		return $this->request;
	}

	/**
	 * Getter/setter for request. Is set to non-null when matched.
	 *
	 * @param Request $set
	 * @return self
	 */
	public function setRequest(Request $set): self {
		$this->request = $set;
		return $this;
	}

	/**
	 * Create a route which matches $pattern with route options
	 *
	 * @param string $pattern
	 *            A regular-expression style pattern to match the URL
	 * @param array $options
	 */
	public function __construct(Router $router, $pattern, array $options) {
		parent::__construct($router->application, $options);
		$this->router = $router;
		$this->original_pattern = $pattern;

		$this->compileRoutePattern($pattern);
		$this->clean_pattern = $this->cleanPattern($pattern);
		$this->inheritConfiguration();
		$this->initialize();
	}

	/**
	 * Set up this route
	 */
	protected function initialize(): void {
		// Generic initial, override in subclasses
		// As a policy, always call parent::initialize();
	}

	/**
	 * Check that this route is valid.
	 * Throw exceptions if not.
	 *
	 * @return self
	 */
	public function validate(): bool {
		return false;
	}

	/**
	 * Log a message about this Route
	 *
	 * @param string $message
	 */
	public function log(string $message, array $arguments = []): void {
		$this->router->log($arguments['level'] ?? 'info', $message, $arguments);
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
	private function cleanPattern(string $pattern): string {
		// Clean optional parens
		$pattern = preg_replace('/[()]/', '', $pattern);
		// Clean value types
		$pattern = preg_replace('/\{[a-z][\\a-z0-9_]*\s+/i', '{', $pattern);
		return $pattern;
	}

	/**
	 * Retrieve the weight of this route for ordering.
	 *
	 * @return float
	 */
	public function weight(): float {
		return $this->optionFloat('weight', 0);
	}

	/**
	 * Convert to string
	 *
	 * @return string
	 */
	public function __toString(): string {
		return $this->original_pattern;
	}

	/**
	 * Compare weights
	 *
	 * @param Route $a
	 * @param Route $b
	 * @return double
	 */
	public static function compareWeight(Route $a, Route $b) {
		return zesk_sort_weight_array($a->options(), $b->options());
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
		$parts = explode('/', strval(strtr($pattern, [
			'(' => '', ')' => '',
		])));
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
	public static function factory(Router $router, string $pattern, array $options): Route {
		/**
		 * Ordering of this array is important. "method" is a parameter to "controller" and others, so it should go last.
		 * Similarly, "file" may be an argument for other `Route`s so it goes last as well.
		 * @var array $types
		 */
		$types = [
			'controller' => Route_Controller::class, 'command' => Route_Command::class, 'theme' => Route_Theme::class,
			'method' => Route_Method::class, 'file' => Route_Content::class,
		];
		$options = self::preprocess_options($pattern, $options);
		foreach ($types as $k => $class) {
			if (array_key_exists($k, $options)) {
				$route = $router->application->factory($class, $router, $pattern, $options);
				assert($route instanceof Route);
				return $route;
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
	private static function cleanType(string $type): string {
		return preg_replace('/[^\\\\0-9A-Za-z_]/i', '_', $type);
	}

	/**
	 * Take a pattern and convert it into a Perl REGular expression (PREG)
	 *
	 * @param string $pattern
	 * @return void
	 */
	public function compileRoutePattern(string $pattern) {
		$C_PAREN_OPEN = chr(0x01);
		$C_PAREN_CLOSE = chr(0x02);
		$C_WILDCARD = chr(0x03);
		$C_QUOTED_WILDCARD = chr(0x04);

		[$methods, $pattern] = pair($pattern, ':', 'GET|POST', $pattern);
		$this->methods = ArrayTools::keysFromValues(to_list($methods, [], '|'), true);
		$replace = [];
		$parameters = [];
		$parameter_names = [];
		$re_pattern = $pattern;
		$re_pattern = str_replace('\\*', $C_QUOTED_WILDCARD, $re_pattern);
		$re_pattern = str_replace('(', $C_PAREN_OPEN, $re_pattern);
		$re_pattern = str_replace(')', $C_PAREN_CLOSE, $re_pattern);
		$re_pattern = str_replace('*', $C_WILDCARD, $re_pattern);

		$matches = [];
		$types = [];
		if (preg_match_all('/{([^ }]+ )?([^ }]+)}/', $re_pattern, $matches, PREG_SET_ORDER)) {
			$index = 1;
			foreach ($matches as $match) {
				$key = "___$index@@@";
				$re_pattern = implode($key, explode($match[0], $re_pattern, 2));
				$types[$key] = [
					self::cleanType(trim($match[1])), $match[2],
				];
				$replace[$key] = '([^/]*)';
				++$index;
			}
		}
		$parts = explode('/', str_replace([
			$C_PAREN_OPEN, $C_PAREN_CLOSE, $C_WILDCARD, $C_QUOTED_WILDCARD,
		], '', $re_pattern));

		foreach ($parts as $index => $part) {
			$this->types[$index] = array_key_exists($part, $types) ? $types[$part] : true;
		}

		$re_pattern = preg_quote($re_pattern, '%');
		$re_pattern = strtr($re_pattern, $replace);
		$re_pattern = str_replace($C_PAREN_OPEN, '(?:', $re_pattern);
		$re_pattern = str_replace($C_PAREN_CLOSE, ')?', $re_pattern);
		$re_pattern = str_replace($C_WILDCARD, '.*', $re_pattern);
		$re_pattern = str_replace($C_QUOTED_WILDCARD, '\\*', $re_pattern);

		$this->pattern = '%^' . $re_pattern . '$%';

		return $this->pattern;
	}

	/**
	 * Retrieve the arguments for this route which have explicit names
	 *
	 * @return array
	 */
	public function argumentsNamed(): array {
		return $this->named;
	}

	/**
	 * Retrieve the arguments for this route based on class name
	 *
	 * @param string $class Single class to retrieve
	 * @param null|string|index $index Optional index of argument to fetch
	 * @return array
	 */
	public function argumentsByClass(string $class = null, int|string $index = null): array {
		$this->_processArguments();
		if ($class === null) {
			return $this->byClass;
		}
		$result = $this->byClass[$class] ?? [];
		if ($index === null) {
			return $result;
		}
		if (is_numeric($index)) {
			$result = array_values($result);
		}
		return $result[$index] ?? [];
	}

	/**
	 * Retrieve the arguments for this route based on position
	 *
	 * @return array
	 */
	public function arguments_indexed() {
		return $this->urlParts;
	}

	/**
	 * Retrieve the numbered arg
	 */
	public function arg($index, $default = null) {
		return avalue($this->urlParts, $index, $default);
	}

	/**
	 * Replace variables in the cleaned pattern
	 *
	 * @param string|array $name
	 *            Input variables
	 * @param string $value
	 *            Optional value
	 * @return string
	 */
	public function url_replace($name, $value = null) {
		if (is_string($name)) {
			$named = [
				$name => $value,
			];
		} elseif (is_array($name)) {
			$named = $name;
		} else {
			$named = [];
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
	final public function match(string $url, string $method = 'GET'): bool {
		if (!isset($this->methods[$method]) || !$this->methods[$method]) {
			return false;
		}
		if (!preg_match($this->pattern, $url, $matches)) {
			return false;
		}
		/* Convert the arguments into any types specified */
		$this->urlParts = explode('/', $url) + array_fill(0, count($this->types), null);
		$this->args = [];
		$this->_arguments_processed = false;
		return true;
	}

	/**
	 * @param int $index
	 * @param array $type_name
	 * @return void
	 * @throws Exception_NotFound
	 * @throws Exception_Syntax
	 */
	private function _handleArgument(int $index, array $type_name): void {
		$object = null;
		$arg = $this->urlParts[$index];
		$application = $this->application;

		[$type, $name] = $type_name;
		if ($arg !== null && !empty($type)) {
			switch ($type) {
				case 'option':
					$arg = $this->convert_option($arg, $name);
					break;
				case 'list':
				case 'semicolon_list':
				case 'array':
					$arg = $this->convert_semicolon_list($arg, $name);
					break;
				case 'comma_list':
					$arg = $this->convert_comma_list($arg, $name);
					break;
				case 'dash_list':
					$arg = $this->convert_dash_list($arg, $name);
					break;
				case 'float':
				case 'double':
					$arg = $this->convert_float($arg, $name);
					break;
				case 'int':
				case 'integer':
					$arg = $this->convertInteger($arg, $name);
					break;
				default:
					$arg = $this->convertModel($type, $arg, $name);
					foreach ($application->classes->hierarchy($arg, Model::class) as $class) {
						$this->byClass[strtolower($class)][$name] = $object;
					}
					break;
			}
		}
		if ($name !== null) {
			$this->named[$name] = $arg;
		}
		$this->named["uri$index"] = $this->urlParts[$index] = $arg;
	}

	/**
	 * Handle arguments from URL
	 *
	 * @return void
	 * @throws Exception_Syntax
	 * @throws Exception_NotFound
	 */
	private function _processArguments(): void {
		if ($this->_arguments_processed) {
			return;
		}
		$this->_arguments_processed = true;
		$this->args = [];
		$this->named = [];
		foreach ($this->types as $index => $type_name) {
			if ($type_name === true) {
				continue;
			}
			$this->_handleArgument($index, $type_name);
		}
		$arguments = $this->optionIterable('arguments', null);
		if (is_array($arguments)) {
			foreach ($arguments as $arg) {
				$this->args[] = is_numeric($arg) ? ($this->urlParts[$arg] ?? null) : $arg;
			}
		}
	}

	/**
	 * Convert URL parameter to integer
	 *
	 * @param string $x
	 * @return number
	 * @throws Exception_Syntax
	 */
	final protected function convertInteger(string $x): int {
		if (!is_numeric($x)) {
			throw new Exception_Syntax('Invalid integer format {value}', ['value' => $x]);
		}
		return intval($x);
	}

	/**
	 * @param string $type
	 * @param string $arg
	 * @param string $name
	 * @throws Exception_NotFound
	 */
	final protected function convertModel(string $type, string $arg, string $name): Model {
		$save = null;

		try {
			$object = $this->application->modelFactory($type);
			$object = $object->callHookArguments('router_argument', [
				$this, $arg,
			], $object);
		} catch (\Exception $e) {
			$object = null;
			$save = $e;
		}
		if (!$object instanceof Model) {
			throw new Exception_NotFound('{name} ({type}) model not found with value "{arg}"', [
				'type' => $type, 'arg' => $arg, 'name' => $name,
			], 0, $save);
		}
		return $object;
	}

	/**
	 * Convert URL parameter to float
	 *
	 * @param string $x
	 * @return number
	 * @throws Exception_Syntax
	 */
	final protected function convert_float(string $x): float {
		if (!is_numeric($x)) {
			throw new Exception_Syntax('Invalid float format {value}', ['value' => $x]);
		}
		return floatval($x);
	}

	/**
	 * Convert URL parameter to string
	 *
	 * @param string $x
	 * @return string
	 * @throws Exception_File_NotFound
	 */
	final protected function convert_string(string $x) {
		return $x;
	}

	/**
	 * Convert URL parameter to string
	 *
	 * @param string $x
	 * @return string
	 */
	final protected function convert_option(string $x, string $name): string {
		$this->setOption($name, $x);
		return $x;
	}

	/**
	 * Convert URL parameter to list delimited by dashes
	 *
	 * @param string $argument
	 * @return array
	 */
	final protected function convert_dash_list(string $argument): array {
		return toList($argument, [], '-');
	}

	/**
	 * Convert URL parameter to list delimited by semicolons
	 *
	 * @param string $argument
	 * @return array
	 */
	final protected function convert_semicolon_list(string $argument): array {
		return toList($argument, [], ';');
	}

	/**
	 * Convert URL parameter to list delimited by commas
	 *
	 * @param string $x
	 * @return array
	 */
	final protected function convert_comma_list($x): array {
		return toList($x, [], ',');
	}

	private function _computeVariablesMap(Response $response): array {
		$request = $this->request;
		$result = [
			'{application}' => $this->application, '{request}' => $request, '{response}' => $response,
			'{route}' => $this, '{router}' => $this->router,
		];
		$result += ArrayTools::wrapKeys($request->variables(), '{request.', '}');
		$result += ArrayTools::wrapKeys($request->urlComponents(), '{url.', '}');
		return $result;
	}

	/**
	 * Convert array values to objects
	 *
	 * @param mixed $mixed
	 * @return mixed
	 */
	protected function _mapVariables(mixed $mixed, Response $response = null): mixed {
		if (!is_array($mixed)) {
			return $mixed;
		}
		return ArrayTools::valuesMap($mixed, $this->_computeVariablesMap($response));
	}

	private function guess_content_type(): void {
		$content_type = $this->option('content_type');
	}

	/**
	 * Overridable method before execution
	 *
	 * @return Response
	 */
	protected function _before() {
		$application = $this->application;
		$response = $application->responseFactory($this->request, $this->guess_content_type());
		if ($this->hasOption('cache')) {
			$cache = $this->option('cache');
			if (is_scalar($cache)) {
				if (toBool($cache)) {
					$response->setCacheForever();
				}
			} elseif (is_array($cache)) {
				$response->setCache($cache);
			} else {
				$application->logger->warning('Invalid cache setting for route {route}: {cache}', [
					'route' => $this->clean_pattern, 'cache' => _dump($cache),
				]);
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
		if ($this->optionBool('json')) {
			$response->makeJSON();
		} elseif ($this->optionBool('html')) {
			$response->makeHTML();
		}
		$this->args = $this->_mapVariables($this->args, $response);
		$this->_arguments_processed = false;
		return $response;
	}

	/**
	 * Execute the route
	 *
	 * @param Response $response
	 * @return Response
	 * @throws Exception_NotFound
	 * @throws Exception_Redirect
	 * @throws Exception_System
	 */
	abstract protected function _execute(Response $response): Response;

	/**
	 * Overridable method after execution
	 *
	 */
	protected function _after(Response $response): void {
		if (array_key_exists('redirect', $this->options)) {
			$response->redirect($this->options['redirect']);
		}
	}

	/**
	 */
	protected function _permissions(Response $response): void {
		$permission = $this->option('permission');
		$permissions = [];
		if ($permission) {
			$permissions[] = [
				'action' => $this->option('permission'), 'context' => $this->option('permission context'),
				'options' => $this->optionArray('permission options'),
			];
		}
		$permissions = array_merge($permissions, $this->optionArray('permissions', []));
		$permissions = $this->_mapVariables($permissions, $response);
		$app = $this->router->application;
		foreach ($permissions as $permission) {
			$action = $context = null;
			$options = [];
			if (is_array($permission)) {
				extract($permission, EXTR_IF_EXISTS);
			} elseif (is_string($permission)) {
				$action = $permission;
			}
			if (!$context instanceof Model && $context !== null) {
				$app->logger->warning('Invalid permission context in route {url}, permission {action}, type={type}, value={value}', [
					'url' => $this->clean_pattern, 'action' => $action, 'type' => type($context),
					'value' => strval($context),
				]);
				$context = null;
			}
			$app->user($response->request, true)->must($action, $context, $options);
		}
	}

	/**
	 *
	 * @return self
	 */
	final protected function _mapOptions(): self {
		$this->original_options = $this->options;
		$this->options = map($this->options, $this->urlParts + ($this->named ?? []));
		return $this;
	}

	/**
	 *
	 * @return self
	 */
	final protected function _unmapOptions(): self {
		$this->options = $this->original_options + $this->options;
		return $this;
	}

	/**
	 * Execute the route and return a Response
	 *
	 * @return Response
	 * @throws Exception_NotFound
	 * @throws Exception_Redirect
	 * @throws Exception_Semantics
	 * @throws Exception_System
	 */
	final public function execute(): Response {
		$this->_processArguments();
		$this->_mapOptions();
		$response = $this->_before();
		$this->_permissions($response);
		$template = Template::factory($this->application, '', [
			'response' => $response, 'route' => $this,
		])->push();
		$exec_response = $this->_execute($response);
		if ($exec_response instanceof Response) {
			$response = $exec_response;
		}
		$this->_after($response);
		$this->_unmapOptions();
		$template->pop();
		return $response;
	}

	/**
	 * Return array of class => array(action1, action2, action3)
	 *
	 * @return array
	 */
	public function classActions(): array {
		if ($this->hasOption('class_actions')) {
			return $this->optionArray('class_actions');
		}
		$classes = $this->optionIterable('classes');
		$actions = $this->optionIterable('actions');
		if (!$classes) {
			return [];
		}
		if (!$actions) {
			$action = $this->option('action');
			if (!empty($action) && $action !== '{action}') {
				$actions = [
					$action,
				];
			} else {
				$actions = [
					'*',
				];
			}
		}
		$result = [];
		foreach ($classes as $class) {
			$result[$class] = $actions;
		}
		return $result;
	}

	/**
	 *
	 * @param string $action
	 * @param ?Model $object
	 * @param array $options
	 *            Optional options relating to the requested route
	 * @return array
	 */
	protected function getRouteMap(string $action, Model $object = null, array $options = []): array {
		$object_hierarchy = is_object($object) ? $this->application->classes->hierarchy($object, Model::class) : [];
		$derived_classes = toArray($options['derived_classes'] ?? []);
		$map = [
			'action' => $action,
		] + $options;

		if (count($object_hierarchy) > 0) {
			foreach ($this->types as $type) {
				if (!is_array($type)) {
					continue;
				}
				[$part_class, $part_name] = $type;
				if (in_array($part_class, $object_hierarchy)) {
					$map[$part_name] = $object instanceof Model ? $object->id() : avalue($options, $part_name, '');
				} elseif (array_key_exists($part_class, $derived_classes)) {
					$map[$part_name] = $derived_classes[$part_class];
				} else {
					$option = $options[$part_name] ?? null;
					if ($option instanceof Model) {
						$id = $option->id();
						if (is_scalar($id)) {
							$map[$part_name] = $id;
						}
					}
				}
			}
			if ($object instanceof Model && !array_key_exists('id', $map)) {
				$map['id'] = $object->id();
			}
		}
		return $map;
	}

	/**
	 * Retrieve the reverse route for a particular action
	 *
	 * @param string $action
	 * @param Model|null $object
	 * @param array $options
	 * @return string
	 */
	public function getRoute(string $action, Model $object = null, array $options = []): string {
		$route_map = $this->getRouteMap($action, $object, $options);
		$map = $this->callHookArguments('getRouteMap', [
			$route_map,
		], $route_map);
		$result = rtrim(map_clean(map($this->clean_pattern, $map), '/'));
		return $result;
	}
}
