<?php
declare(strict_types=1);
/**
 *
 */

namespace zesk;

use Throwable;
use zesk\Exception\ClassNotFound;
use zesk\Exception\NotFoundException;
use zesk\Exception\PermissionDenied;
use zesk\Exception\Redirect;
use zesk\Exception\SemanticsException;
use zesk\Exception\SyntaxException;
use zesk\Route\Command as CommandRoute;
use zesk\Route\Content as ContentRoute;
use zesk\Route\Controller as ControllerRoute;
use zesk\Route\Method as MethodRoute;
use zesk\Route\Redirect as RedirectRoute;
use zesk\Route\Theme as ThemeRoute;

/**
 *
 * @see Router
 * @author kent
 */
abstract class Route extends Hookable {
	/**
	 *
	 */
	public const OPTION_REDIRECT = 'redirect';

	public const DELIMITER_SEMICOLON = ';';

	public const DELIMITER_COMMA = ',';

	public const DELIMITER_DASH = ',';

	/**
	 * A unique ID for this route
	 */
	public const OPTION_ID = 'id';

	/**
	 * Value is an associative array or a list
	 *
	 * @var string
	 */
	public const OPTION_ALIASES = 'aliases';

	/**
	 *
	 */
	public const OPTION_CONTENT_TYPE = 'contentType';

	/**
	 * When a list, the alias value
	 */
	public const OPTION_ALIAS_TARGET = 'aliasTarget';

	/**
	 *
	 * @var string
	 */
	public const OPTION_DEBUG = 'debug';

	/**
	 *
	 * @var string
	 */
	public const OPTION_CACHE = 'cache';

	/**
	 * Arguments for route. Option value is an array of tokens. Integers indicate URL path parts
	 * starting from 0-index for the first component. Components are loaded and converted internally.
	 *
	 * You can use magic tokens for arguments to pass object values:
	 *
	 * {request} {response} {route} {application}
	 *
	 * @var string
	 */
	public const OPTION_ARGUMENTS = 'arguments';

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
	 * Option value type is bool
	 *
	 * @var string
	 */
	public const OPTION_JSON = 'json';

	/**
	 * Option value type is bool
	 *
	 * @var string
	 */
	public const OPTION_HTML = 'html';

	/**
	 * The router
	 *
	 * @var Router
	 */
	private Router $router;

	/**
	 * Original options before being mapped
	 */
	protected array $originalOptions = [];

	/**
	 * Original pattern passed in
	 *
	 * @var string
	 */
	protected string $originalPattern;

	/**
	 * Pattern with replaceable variables
	 *
	 * @var string
	 */
	protected string $cleanPattern;

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
	protected bool $argsValid = false;

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
	 * Create a route which matches $pattern with route options
	 *
	 * @param Router $router
	 * @param string $pattern
	 *            A regular-expression style pattern to match the URL
	 * @param array $options
	 */
	public function __construct(Router $router, string $pattern, array $options) {
		parent::__construct($router->application, $options);
		$this->router = $router;
		$this->originalPattern = $pattern;

		$this->compileRoutePattern($pattern);
		$this->cleanPattern = $this->cleanPattern($pattern);
		$this->inheritConfiguration();
		$this->initialize();
	}

	/**
	 * @return Router
	 */
	public function router(): Router {
		return $this->router;
	}

	/**
	 * @return string
	 */
	public function id(): string {
		return $this->optionString(self::OPTION_ID);
	}

	/**
	 * @param string $id
	 * @return $this
	 */
	public function setId(string $id): self {
		return $this->setOption(self::OPTION_ID, $id);
	}

	/**
	 * Return the cleaned pattern for PREG
	 *
	 * @return string
	 */
	public function getPattern(): string {
		return $this->cleanPattern;
	}

	/**
	 * Return list of members to save upon sleep
	 */
	public function __serialize(): array {
		return parent::__serialize() + [
				'originalOptions' => $this->originalOptions, 'originalPattern' => $this->originalPattern,
				'cleanPattern' => $this->cleanPattern, 'methods' => $this->methods, 'pattern' => $this->pattern,
				'types' => $this->types, 'urlParts' => $this->urlParts, 'args' => $this->args,
				'argsValid' => $this->argsValid, 'named' => $this->named, 'byClass' => $this->byClass,
			];
	}

	public function __unserialize(array $data): void {
		parent::__unserialize($data);
		$this->originalOptions = $data['originalOptions'];
		$this->originalPattern = $data['originalPattern'];
		$this->cleanPattern = $data['cleanPattern'];
		$this->methods = $data['methods'];
		$this->pattern = $data['pattern'];
		$this->types = $data['types'];
		$this->urlParts = $data['urlParts'];
		$this->args = $data['args'];
		$this->argsValid = $data['argsValid'];
		$this->named = $data['named'];
		$this->byClass = $data['byClass'];
	}

	public function wakeupConnect(Router $router): void {
		$this->router = $router;
	}

	/**
	 * Retrieve variables associated with Route for debugging, etc.
	 *
	 * @return string[]
	 */
	public function variables(): array {
		return [
			'class' => get_class($this), 'originalOptions' => $this->originalOptions,
			'originalPattern' => $this->originalPattern, 'cleanPattern' => $this->cleanPattern,
			'pattern' => $this->pattern, 'methods' => array_keys($this->methods), 'types' => $this->types,
			'urlParts' => $this->urlParts, 'named' => $this->named, 'byClass' => $this->byClass,
			'options' => $this->options(),
		];
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
	 * @return bool
	 */
	public function validate(): bool {
		return false;
	}

	/**
	 * Log a message about this Route
	 *
	 * @param string $message
	 * @param array $arguments
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
		return preg_replace('/\{[a-z][\\\\a-z0-9_]*\s+/i', '{', $pattern);
	}

	/**
	 * Retrieve the weight of this route for ordering.
	 *
	 * @return float
	 */
	public function weight(): float {
		return $this->optionFloat('weight');
	}

	/**
	 * Convert to string
	 *
	 * @return string
	 */
	public function __toString(): string {
		return $this->originalPattern;
	}

	/**
	 * Compare weights
	 *
	 * @param Route $a
	 * @param Route $b
	 * @return int
	 */
	public static function compareWeight(Route $a, Route $b): int {
		return Types::weightCompare($a->options(), $b->options());
	}

	/**
	 * Replace components in the $options which are constant and will not change
	 *
	 * @param string $pattern
	 * @param array $options
	 * @return array
	 */
	public static function preprocessOptions(string $pattern, array $options): array {
		// Replace parts which do not have variables set
		$parts = explode('/', strtr($pattern, [
			'(' => '', ')' => '',
		]));
		foreach ($parts as $index => $part) {
			/* If it has variables, remove it */
			if (count(StringTools::extractTokens($part)) !== 0) {
				unset($parts[$index]);
			}
		}
		/* Map our {0} {1} {2} fields to options once for items which are static in the pattern */
		return ArrayTools::map($options, $parts);
	}

	/**
	 * Create a route from a set of options
	 *
	 * @param Router $router
	 * @param string $pattern
	 * @param array $options
	 * @return Route
	 * @throws ClassNotFound - Means code has been deleted on disk, I assume.
	 */
	public static function factory(Router $router, string $pattern, array $options): Route {
		/**
		 * Ordering of this array is important. "method" is a parameter to "controller" and others, so it should go last.
		 * Similarly, "file" may be an argument for other Routes; so it goes last as well.
		 *
		 * @var array $types
		 */
		$types = [
			'controller' => ControllerRoute::class, 'command' => CommandRoute::class, 'theme' => ThemeRoute::class,
			'method' => MethodRoute::class, 'file' => ContentRoute::class, 'redirect' => RedirectRoute::class,
		];
		$options = self::preprocessOptions($pattern, $options);
		foreach ($types as $k => $class) {
			if (array_key_exists($k, $options)) {
				$route = $router->application->factory($class, $router, $pattern, $options);
				assert($route instanceof Route);
				return $route;
			}
		}
		return new ContentRoute($router, $pattern, $options);
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
	 * Take a pattern and convert it into a Perl Regular Expression (PREG)
	 *
	 * @param string $pattern
	 * @return string
	 */
	public function compileRoutePattern(string $pattern): string {
		$C_PAREN_OPEN = chr(0x01);
		$C_PAREN_CLOSE = chr(0x02);
		$C_WILDCARD = chr(0x03);
		$C_QUOTED_WILDCARD = chr(0x04);

		[$methods, $pattern] = StringTools::pair($pattern, ':', 'OPTIONS|GET|POST', $pattern);
		$this->methods = ArrayTools::keysFromValues(Types::toList($methods, [], '|'), true);
		$replace = [];
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
	 * @param null|string $class Single class to retrieve
	 * @param null|string|int $index Optional index of argument to fetch
	 * @return array
	 * @throws NotFoundException
	 * @throws SyntaxException
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
	public function argumentsIndexed(): array {
		return $this->urlParts;
	}

	/**
	 * Retrieve the numbered arg
	 */
	public function arg(int $index, string $default = ''): string {
		return $this->urlParts[$index] ?? $default;
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
	public function urlReplace(string|array $name, string $value = ''): string {
		if (is_string($name)) {
			$named = [
				$name => $value,
			];
		} else if (is_array($name)) {
			$named = $name;
		} else {
			$named = [];
		}
		return ArrayTools::map($this->cleanPattern, $named + $this->named);
	}

	/**
	 * Determine if an url matches this route.
	 * If it matches, configure arguments by index as $this->args, and by name as $this->named
	 *
	 * @param string $url
	 * @param string $method
	 * @return bool
	 */
	final public function match(string $url, string $method = 'GET'): bool {
		if (!isset($this->methods[$method]) || !$this->methods[$method]) {
			return false;
		}
		if (!preg_match($this->pattern, $url)) {
			return false;
		}
		/* Convert the arguments into any types specified */
		$this->urlParts = explode('/', $url) + array_fill(0, count($this->types), null);
		$this->args = [];
		$this->argsValid = false;
		return true;
	}

	/**
	 * @param int $index
	 * @param array $type_name
	 * @return void
	 * @throws NotFoundException
	 * @throws SyntaxException
	 */
	private function _handleArgument(int $index, array $type_name): void {
		$object = null;
		$arg = $this->urlParts[$index];
		$application = $this->application;

		[$type, $name] = $type_name;
		if ($arg !== null && !empty($type)) {
			switch ($type) {
				case 'option':
					$arg = $this->convertOption($arg, $name);
					break;
				case 'list':
				case 'semicolon_list':
				case 'array':
					$arg = Types::toList($arg, [], self::DELIMITER_SEMICOLON);
					break;
				case 'comma_list':
					$arg = Types::toList($arg, [], self::DELIMITER_COMMA);
					break;
				case 'dash_list':
					$arg = Types::toList($arg, [], self::DELIMITER_DASH);
					break;
				case 'float':
				case 'double':
					$arg = $this->convertFloat($arg);
					break;
				case 'int':
				case 'integer':
					$arg = $this->convertInteger($arg);
					break;
				default:
					$arg = $this->convertModel($type, $arg, $name);
					foreach ($application->classes->hierarchy($arg, Model::class) as $class) {
						$this->byClass[$class][$name] = $object;
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
	 * @throws SyntaxException
	 * @throws NotFoundException
	 */
	private function _processArguments(): void {
		if ($this->argsValid) {
			return;
		}
		$this->argsValid = true;
		$this->args = [];
		$this->named = [];
		foreach ($this->types as $index => $type_name) {
			if ($type_name === true) {
				continue;
			}
			$this->_handleArgument($index, $type_name);
		}
		$arguments = $this->optionIterable(self::OPTION_ARGUMENTS, null);
		if (is_array($arguments)) {
			foreach ($arguments as $arg) {
				$this->args[] = is_numeric($arg) ? ($this->urlParts[$arg] ?? '') : $arg;
			}
		}
	}

	/**
	 * Convert URL parameter to integer
	 *
	 * @param string $x
	 * @return number
	 * @throws SyntaxException
	 */
	final protected function convertInteger(string $x): int {
		if (!is_numeric($x)) {
			throw new SyntaxException('Invalid integer format {value}', ['value' => $x]);
		}
		return intval($x);
	}

	public const FILTER_ROUTER_ARGUMENT = self::class . '::routerArgument';

	/**
	 * @param string $entityName
	 * @param string $arg
	 * @param string $name
	 * @return Model
	 * @throws NotFoundException
	 */
	final protected function convertModel(string $entityName, string $arg, string $name): Model {
		try {
			$object = new $entityName($this->application);
			return $object->invokeTypedFilters(self::FILTER_ROUTER_ARGUMENT, $object, [$this, $arg]);
		} catch (Throwable $e) {
			throw new NotFoundException('{name} ({type}) model not found with value "{arg}"', [
				'type' => $entityName, 'arg' => $arg, 'name' => $name,
			], 0, $e);
		}
	}

	/**
	 * Convert URL parameter to float
	 *
	 * @param string $x
	 * @return number
	 * @throws SyntaxException
	 */
	final protected function convertFloat(string $x): float {
		if (!is_numeric($x)) {
			throw new SyntaxException('Invalid float format {value}', ['value' => $x]);
		}
		return floatval($x);
	}

	/**
	 * Convert URL parameter to string
	 *
	 * @param string $x
	 * @param string $name
	 * @return string
	 */
	final protected function convertOption(string $x, string $name): string {
		$this->setOption($name, $x);
		return $x;
	}

	private function _stringToObjectMapping(Request $request): array {
		return [
			'{application}' => $this->application, '{request}' => $request, '{route}' => $this,
			'{router}' => $this->router,
		];
	}

	protected function _requestMap(Request $request): array {
		return ArrayTools::prefixKeys($request->variables(), 'request.') + ArrayTools::prefixKeys($request->urlComponents(), 'url.');
	}

	/**
	 * Convert array values to objects
	 *
	 * @param mixed $mixed
	 * @param Request $request
	 * @return string|array
	 */
	protected function _mapVariables(Request $request, string|array $mixed): string|array {
		if (is_array($mixed)) {
			/**
			 * Replace the tokens found here with the objects if the strings match exactly
			 */
			$mixed = ArrayTools::valuesMap($mixed, $this->_stringToObjectMapping($request));
		}
		/**
		 * Replace tokens in the strings with variables here
		 */
		return ArrayTools::map($mixed, $this->_requestMap($request));
	}

	/**
	 * @return string
	 */
	private function guessContentType(): string {
		return $this->optionString(self::OPTION_CONTENT_TYPE);
	}

	/**
	 * @param Request $request
	 * @return Response
	 */
	private function responseFactory(Request $request): Response {
		$application = $this->application;
		$response = $application->responseFactory($request, $this->guessContentType());
		if ($this->hasOption(self::OPTION_CACHE)) {
			$cache = $this->option(self::OPTION_CACHE);
			if (is_scalar($cache)) {
				if (Types::toBool($cache)) {
					$response->setCacheForever();
				}
			} else if (is_array($cache)) {
				$response->setCache($cache);
			} else {
				$application->warning('Invalid cache setting for route {route}: {cache}', [
					'route' => $this->cleanPattern, 'cache' => Debug::dump($cache),
				]);
			}
		}
		$v = $this->option(self::OPTION_STATUS_CODE);
		if ($v) {
			$response->setStatus($v);
		}
		$v = $this->option(self::OPTION_STATUS_MESSAGE);
		if ($v) {
			$response->setStatus($response->status_code, $v);
		}
		$v = $this->option(self::OPTION_OUTPUT_HANDLER);
		if ($v) {
			$response->setOutputHandler($v);
		}
		if ($this->optionBool(self::OPTION_JSON)) {
			$response->makeJSON();
		} else if ($this->optionBool(self::OPTION_HTML)) {
			$response->makeHTML();
		}
		return $response;
	}

	/**
	 * Overridable method before execution
	 *
	 * @param Request $request
	 * @return void
	 * @throws NotFoundException
	 * @throws SyntaxException
	 */
	protected function _before(Request $request): void {
		$this->_processArguments();
		$this->args = $this->_mapVariables($request, $this->args);
	}

	/**
	 * Execute the route
	 *
	 * @param Request $request
	 * @return Response
	 * @throws NotFoundException
	 * @throws Redirect
	 */
	abstract protected function internalExecute(Request $request): Response;

	/**
	 * Overridable method after execution
	 * @throws Redirect
	 */
	protected function _after(Response $response): void {
		if ($this->hasOption(self::OPTION_REDIRECT)) {
			$response->redirect()->url($this->optionString(self::OPTION_REDIRECT));
		}
	}

	/**
	 * @throws PermissionDenied
	 * @throws ClassNotFound
	 */
	protected function _permissions(Request $request): void {
		$permission = $this->option('permission');
		$permissions = [];
		if ($permission) {
			$permissions[] = [
				'action' => $this->option('permission'), 'context' => $this->option('permission context'),
				'options' => $this->optionArray('permission options'),
			];
		}
		$permissions = array_merge($permissions, $this->optionArray('permissions'));
		$permissions = $this->_mapVariables($request, $permissions);
		$app = $this->router->application;
		foreach ($permissions as $permission) {
			$action = '';
			$context = null;
			$options = [];
			if (is_array($permission)) {
				$context = $permission['context'] ?? null;
				$action = $permission['action'] ?? '';
				$options = Types::toArray($permission['options'] ?? []);
			} else if (is_string($permission)) {
				$action = $permission;
			}
			if (!$context instanceof Model && $context !== null) {
				$app->warning('Invalid permission context in route {url}, permission {action}, type={type}, value={value}', [
					'url' => $this->cleanPattern, 'action' => $action, 'type' => Types::type($context),
					'value' => strval($context),
				]);
				$context = null;
			}
			$app->requireUser($request)->must($action, $context, $options);
		}
	}

	/**
	 *
	 * @return self
	 */
	final protected function _mapOptions(): self {
		$this->originalOptions = $this->options();
		$this->setOptions(ArrayTools::map($this->options(), $this->urlParts + ($this->named ?? [])));
		return $this;
	}

	/**
	 *
	 * @return self
	 */
	final protected function _unmapOptions(): self {
		$this->setOptions($this->originalOptions + $this->options());
		$this->originalOptions = [];
		return $this;
	}

	/**
	 * Execute the route and return a Response
	 *
	 * @param Request $request
	 * @return Response
	 * @throws PermissionDenied
	 * @throws Redirect
	 * @throws ClassNotFound
	 */
	final public function execute(Request $request): Response {
		$this->_mapOptions();

		try {
			$this->_before($request);
			$this->_processArguments();
		} catch (NotFoundException|SyntaxException $e) {
			return $this->responseFactory($request)->setStatus(HTTP::STATUS_FILE_NOT_FOUND, $e->codeName());
		}
		$this->_permissions($request);
		$template = Theme::factory($this->application->themes, '', [
			'route' => $this, 'request' => $request, 'routeVariables' => $this->variables(),
		])->push();

		try {
			$response = $this->internalExecute($request);
		} catch (NotFoundException $e) {
			$response = $this->responseFactory($request)->setStatus(HTTP::STATUS_FILE_NOT_FOUND, $e->getMessage());
		} catch (Exception $e) {
			$response = $this->responseFactory($request)->setStatus($e->getCode() ?: HTTP::STATUS_INTERNAL_SERVER_ERROR, $e->getMessage());
		}
		$this->_after($response);
		$this->_unmapOptions();

		try {
			$template->pop();
		} catch (SemanticsException) {
			$response->setStatus(HTTP::STATUS_INTERNAL_SERVER_ERROR, 'Template pop');
		}
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
		$derived_classes = Types::toArray($options['derivedClasses'] ?? []);
		$map = [
				'action' => $action,
			] + $options;

		if (count($object_hierarchy) === 0) {
			return $map;
		}
		foreach ($this->types as $type) {
			if (!is_array($type)) {
				continue;
			}
			[$part_class, $part_name] = $type;
			if (in_array($part_class, $object_hierarchy)) {
				$map[$part_name] = $object instanceof Model ? $object->id() : $options[$part_name] ?? '';
			} else if (array_key_exists($part_class, $derived_classes)) {
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
		return $map;
	}

	/**
	 *
	 */
	public const FILTER_ROUTE_MAP = self::class . '::routeMap';

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
		$map = $this->invokeTypedFilters(self::FILTER_ROUTE_MAP, $route_map);
		return rtrim(StringTools::cleanTokens(ArrayTools::map($this->cleanPattern, $map), '/'));
	}

	/**
	 * @param Router $router
	 * @return $this
	 * @throws SemanticsException
	 */
	public function wasAdded(Router $router): self {
		if (!$this->hasOption(self::OPTION_ALIASES)) {
			return $this;
		}
		$defaultAlias = $this->optionString(self::OPTION_ALIAS_TARGET, $this->getPattern());
		foreach ($this->optionArray(self::OPTION_ALIASES) as $alias => $aliasId) {
			if (!is_string($aliasId)) {
				throw new SemanticsException('{option} in route must be an object with string keys and values {alias} and {id} provided', [
					'option' => self::OPTION_ALIASES, 'alias' => gettype($alias), 'id' => gettype($aliasId),
				]);
			}
			if (is_numeric($alias)) {
				$alias = $aliasId;
				$aliasId = $defaultAlias;
			}
			$router->addAlias($alias, $aliasId);
		}
		return $this;
	}
}
