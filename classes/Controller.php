<?php
declare(strict_types=1);

/**
 *
 */

namespace zesk;

use Psr\Cache\InvalidArgumentException;
use \ReflectionClass;
use \ReflectionException;
use stdClass;

/**
 *
 * @package zesk
 * @subpackage objects
 * @author kent
 * @copyright Copyright &copy; 2022, Market Acumen, Inc.
 *            Created on Fri Apr 02 21:04:09 EDT 2010 21:04:09
 */
class Controller extends Hookable implements Interface_Theme {
	/**
	 * Method to use as default action in this Controller.
	 * Must be a valid method name. If not specified, can be overridden by calling Route.
	 */
	protected ?string $method_default_action = null;

	/**
	 * Method to use as default action argument collector in this Controller.
	 * Must be a valid method name. Method itself returns an array (arguments)
	 * to be passed to the above function.
	 *
	 * If not specified, can be overridden by calling Route.
	 */
	protected ?string $method_default_arguments = null;

	/**
	 * Default content type for Response generated upon instantiation.
	 *
	 * Can be overridden by setting global "default_content_type" option for this class
	 */
	protected ?string $default_content_type = null;

	/**
	 * Request associated with this controller
	 */
	public ?Request $request = null;

	/**
	 * Response associated with this controller
	 */
	public ?Response $response = null;

	/**
	 * Router associated with this controller
	 */
	public ?Router $router = null;

	/**
	 * Route which brought us here
	 *
	 */
	public ?Route $route = null;

	/**
	 * Controller constructor.
	 *
	 * @param Application $app
	 * @param Route|null $route
	 * @param Response|null $response
	 * @param array $options
	 * @throws Exception_Lock
	 */
	final public function __construct(Application $app, Route $route = null, Response $response = null, array $options = []) {
		parent::__construct($app, $options);

		$this->inheritConfiguration();

		$this->router = $app->router;
		$this->route = $route;
		$this->request = $route ? $route->request() : null;
		$this->response = $response;

		if ($response) {
			$this->application->logger->debug('{class}::__construct Response ID {id}', [
				'class' => get_class($this),
				'id' => $response->id(),
			]);
		}

		$this->initialize();
		$this->callHook('initialize');
	}

	/**
	 * Shortcut for subclass methods
	 *
	 * @param array|string $types
	 * @param array $arguments
	 * @param array $options
	 * @return string
	 */
	public function theme(array|string $types, array $arguments = [], array $options = []): string {
		return $this->application->theme($types, $arguments, $options);
	}

	/**
	 * Getter/Setter for theme variables. Affects the current TOP template only by default.
	 *
	 * @param string $name
	 * @return mixed
	 */
	public function themeVariable(string $name): mixed {
		return $this->application->themeVariable($name);
	}

	/**
	 */
	public function class_actions() {
		return [];
	}

	/**
	 */
	protected function hook_classes() {
		return $this->optionIterable('classes', []);
	}

	/**
	 * Stub for override - initialize the controller - called after __construct is done but before hook_initialize
	 * Note that:
	 * <code>
	 * $this->request
	 * $this->route
	 * $this->response
	 * </code>
	 * May all possibly be NULL upon this function called.
	 */
	protected function initialize(): void {
	}

	/**
	 * Get/set request
	 *
	 * @param Request $set
	 * @return Controller|Request
	 */
	public function request(Request $set = null) {
		if ($set) {
			$this->request = $set;
			return $this;
		}
		return $this->request;
	}

	/**
	 * Executed before the controller action
	 *
	 * @return void
	 */
	public function before(): void {
	}

	/**
	 * @param string $action
	 */
	public function _action_default(string $action = ''): mixed {
		$this->error_404($action ? "Action $action" : 'default action');
	}

	/**
	 * Executed after the controller action
	 *
	 * @return void
	 */
	public function after(string $result = null, string $output = null): void {
		// pass
	}

	/**
	 * Returns an array of name/value pairs for a template
	 */
	public function variables(): array {
		return [
			'application' => $this->application,
			'controller' => $this,
			'request' => $this->request,
			'response' => $this->response,
		];
	}

	/**
	 * Update all settings to return a JSON response
	 *
	 * @param mixed $mixed
	 * @return self
	 */
	public function json(mixed $mixed = null): self {
		$mixed = $this->callHookArguments('json', [
			$mixed,
		], $mixed);
		$this->response->json()->setData($mixed);
		return $this;
	}

	/**
	 * Page not found error
	 *
	 * @param string $message
	 * @return self
	 */
	public function error_404(string $message = ''): self {
		$this->error(Net_HTTP::STATUS_FILE_NOT_FOUND, trim("Page not found $message"));
		return $this;
	}

	/**
	 * Generic page error
	 *
	 * @param int $code Net_HTTP::Status_XXX
	 * @param string $message Message
	 * @return self
	 */
	public function error(int $code, string $message = ''): self {
		$this->response->status($code);
		$this->response->content_type('text/html');
		$this->response->content = $message;
		return $this;
	}

	/**
	 * Execute an optional method
	 *
	 * @param $name
	 * @param array $arguments
	 * @return array|mixed
	 */
	final public function optional_method(array|string $name, array $arguments) {
		$names = toList($name);
		foreach ($names as $name) {
			if ($this->has_method($name)) {
				return $this->invoke_method($name, $arguments);
			}
		}
		return $arguments;
	}

	/**
	 *
	 * @param string $name
	 * @return boolean
	 */
	final public function has_method(string $name): bool {
		return method_exists($this, $name);
	}

	/**
	 *
	 * @param string $name
	 * @param array $arguments
	 * @return mixed
	 */
	final public function invoke_method(string $name, array $arguments): mixed {
		return call_user_func_array([
			$this,
			$name,
		], $arguments);
	}

	/**
	 *
	 * @param array $arguments
	 * @return mixed
	 */
	final public function invoke_default_method(array $arguments): mixed {
		if (empty($this->method_default_action)) {
			$this->method_default_action = $this->route->option('method default', '_action_default');
		}
		if (empty($this->method_default_arguments)) {
			$this->method_default_arguments = $this->option('arguments method default', '_arguments_default');
		}
		$arguments = $this->optional_method($this->method_default_arguments, $arguments);
		return call_user_func_array([
			$this,
			$this->method_default_action,
		], $arguments);
	}

	/**
	 *
	 * @param string $action
	 * @param mixed $object
	 * @param array $options
	 * @return array:
	 */
	public function getRouteMap(string $action = '', mixed $object = null, array $options = []): array {
		return [];
	}

	/**
	 * Create a widget, and inherit this Controller's response
	 *
	 * @param string $class Widget class to create
	 * @param array $options
	 * @return Widget
	 */
	public function widgetFactory(string $class, array $options = []): Widget {
		$widget = $this->application->widgetFactory($class, $options);
		if ($this->response) {
			$widget->response($this->response);
		}
		return $widget;
	}

	/**
	 * Create a model
	 *
	 * @return Model
	 */

	/**
	 * @param string $class Model to create
	 * @param mixed $mixed Model initialization parameter (id, array, etc.)
	 * @param array $options Creation options and initial options for model
	 * @return Model
	 */
	public function modelFactory(string $class, mixed $mixed = null, array $options = []): Model {
		return $this->application->modelFactory($class, $mixed, $options);
	}

	/**
	 * Collect a list of all controllers found in this application. This loads all PHP
	 * within the autoloader paths of the application at time of first invocation.
	 *
	 * Possibly very slow.
	 *
	 * @param Application $application
	 * @return array
	 *
	 * @throws InvalidArgumentException
	 */
	final public static function all(Application $application): array {
		$paths = $application->autoloader->path();
		$item = $application->cache->getItem(__CLASS__);
		if ($item->isHit()) {
			$value = $item->get();
			if (count($paths) === $value->n_paths) {
				return $value->all;
			}
		}
		$list_options = [
			'file_include_pattern' => '/\.(inc|php)$/',
			'directory_default' => false,
		];
		$found = [];
		foreach ($paths as $path => $options) {
			$controller_path = path($path, 'controller');
			if (is_dir($controller_path)) {
				$class_prefix = avalue($options, 'class_prefix', '');
				$controller_incs = Directory::list_recursive($controller_path, $list_options);
				foreach ($controller_incs as $controller_inc) {
					if (str_contains("/$controller_inc", '/.')) {
						continue;
					}
					$application->logger->debug('Found controller {controller_inc}', compact('controller_inc'));

					try {
						$controller_inc = File::setExtension($controller_inc, '');
						$class_name = $class_prefix . 'Controller_' . strtr($controller_inc, '/', '_');
						$application->logger->debug('class name is {class_name}', compact('class_name'));
						$reflectionClass = new ReflectionClass($class_name);
						if (!$reflectionClass->isAbstract()) {
							/* @var $controller Controller */
							$controller = $reflectionClass->newInstance($application);
							$found[$reflectionClass->getName()] = [
								'path' => path($controller_path, $controller_inc),
								'classes' => $controller->callHook('classes', [], []),
							];
						}
					} catch (ReflectionException $e) {
					} catch (\Exception $e) {
						$application->logger->error('Exception creating controller {controller_inc} {e}', compact('controller_inc', 'e'));
					}
				}
			}
		}
		ksort($found);
		$value = new stdClass();
		$value->all = $found;
		$value->n_paths = count($paths);
		$application->cache->saveDeferred($item->set($value));
		return $found;
	}

	/**
	 * Output to a PHP constructor parameters
	 *
	 * @return string
	 */
	public function _to_php() {
		return '$application, ' . PHP::dump($this->options);
	}
}
