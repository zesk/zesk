<?php

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
 * @copyright Copyright &copy; 2009, Market Acumen, Inc.
 *            Created on Fri Apr 02 21:04:09 EDT 2010 21:04:09
 */
class Controller extends Hookable implements Interface_Theme {
	/**
	 * Method to use as default action in this Controller.
	 * Must be a valid method name.
	 *
	 * @var string
	 */
	protected $method_default_action = null;

	/**
	 * Method to use as default action in this Controller.
	 * Must be a valid method name.
	 *
	 * @var string
	 */
	protected $method_default_arguments = null;

	/**
	 * Default content type for Response generated upon instantiation.
	 *
	 * Can be overridden by setting global "default_content_type" option for this class
	 *
	 * @var string
	 */
	protected $default_content_type = null;

	/**
	 * Request associated with this controller
	 *
	 * @var Request
	 */
	public $request = null;

	/**
	 * Response associated with this controller
	 *
	 * @var Response
	 */
	public $response = null;

	/**
	 * Router associated with this controller
	 *
	 * @var Router
	 */
	public $router = null;

	/**
	 * Route which brought us here
	 *
	 * @var Route
	 */
	public $route = null;

	/**
	 * Controller constructor.
	 *
	 * @param Application $app
	 * @param Route|null $route
	 * @param Response|null $response
	 * @param array $options
	 * @throws Exception_Lock
	 */
	final public function __construct(Application $app, Route $route = null, Response $response = null, array $options = array()) {
		parent::__construct($app, $options);

		$this->inherit_global_options();

		$this->router = $app->router;
		$this->route = $route;
		$this->request = $route ? $route->request() : null;
		$this->response = $response;

		if ($response) {
			$this->application->logger->debug("{class}::__construct Response ID {id}", array(
				"class" => get_class($this),
				"id" => $response->id(),
			));
		}

		$this->initialize();
		$this->call_hook("initialize");
	}

	/**
	 * Shortcut for subclass methods
	 *
	 * @param array|string $types
	 * @param array $arguments
	 * @param array $options
	 * @return string|null
	 */
	public function theme($types, $arguments = array(), array $options = array()) {
		return $this->application->theme($types, $arguments, $options);
	}

	/**
	 * Getter/Setter for theme variables. Affects the current TOP template only by default.
	 *
	 * @param string $name
	 * @param mixed $value
	 * @return mixed
	 */
	public function theme_variable($name = null, $value = null) {
		return $this->application->theme_variable($name, $value);
	}

	/**
	 */
	public function class_actions() {
		return array();
	}

	/**
	 */
	protected function hook_classes() {
		return $this->option_list("classes", array());
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
	protected function initialize() {
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
	public function before() {
	}

	/**
	 * @param string $action
	 */
	public function _action_default($action = null) {
		$this->error_404($action ? "Action $action" : "default action");
	}

	/**
	 * Executed after the controller action
	 *
	 * @return void
	 */
	public function after() {
	}

	/**
	 * Returns an array of name/value pairs for a template
	 */
	public function variables() {
		return array(
			'application' => $this->application,
			'controller' => $this,
			'request' => $this->request,
			'response' => $this->response,
		);
	}

	/**
	 * Update all settings to return a JSON response
	 *
	 * @param mixed $mixed
	 * @return self
	 */
	public function json($mixed = null) {
		$mixed = $this->call_hook_arguments("json", array(
			$mixed,
		), $mixed);
		$this->response->json()->data($mixed);
		return $this;
	}

	/**
	 * Page not found error
	 *
	 * @param string $message
	 * @return self
	 */
	public function error_404($message = null) {
		$this->error(Net_HTTP::STATUS_FILE_NOT_FOUND, "Page not found $message");
		return $this;
	}

	/**
	 * Generic page error
	 *
	 * @param integer $code Net_HTTP::Status_XXX
	 * @param string $message Message
	 * @return self
	 */
	public function error($code, $message = null) {
		$this->response->status($code);
		$this->response->content_type("text/html");
		$this->response->content = $message;
		return $this;
	}

	/**
	 * Execute an optional method
	 *
	 * @param string $name
	 * @param array $arguments
	 * @return mixed
	 */

	/**
	 * @param $name
	 * @param array $arguments
	 * @return array|mixed
	 */
	final public function optional_method($name, array $arguments) {
		$names = to_list($name);
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
	final public function has_method($name) {
		return method_exists($this, $name);
	}

	/**
	 *
	 * @param string $name
	 * @param array $arguments
	 * @return mixed
	 */
	final public function invoke_method($name, array $arguments) {
		return call_user_func_array(array(
			$this,
			$name,
		), $arguments);
	}

	/**
	 *
	 * @param array $arguments
	 * @return mixed
	 */
	final public function invoke_default_method(array $arguments) {
		if (empty($this->method_default_action)) {
			$this->method_default_action = $this->route->option('method default', '_action_default');
		}
		if (empty($this->method_default_arguments)) {
			$this->method_default_arguments = $this->option('arguments method default', '_arguments_default');
		}
		$arguments = $this->optional_method($this->method_default_arguments, $arguments);
		return call_user_func_array(array(
			$this,
			$this->method_default_action,
		), $arguments);
	}

	/**
	 *
	 * @param string $action
	 * @param string $object
	 * @param string $options
	 * @return array:
	 */
	public function get_route_map($action = null, $object = null, $options = null) {
		return array();
	}

	/**
	 * Create a widget, and inherit this Controller's response
	 *
	 * @param string $class Widget class to create
	 * @param array $options
	 * @return Widget
	 */
	public function widget_factory($class, array $options = array()) {
		$widget = $this->application->widget_factory($class, $options);
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
	public function model_factory($class, $mixed = null, array $options = array()) {
		return $this->application->model_factory($class, $mixed, $options);
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
	final public static function all(Application $application) {
		$paths = $application->autoloader->path();
		$item = $application->cache->getItem(__CLASS__);
		if ($item->isHit()) {
			$value = $item->get();
			if (count($paths) === $value->n_paths) {
				return $value->all;
			}
		}
		$list_options = array(
			'file_include_pattern' => '/\.(inc|php)$/',
			'directory_default' => false,
		);
		$found = array();
		foreach ($paths as $path => $options) {
			$controller_path = path($path, "controller");
			if (is_dir($controller_path)) {
				$class_prefix = avalue($options, "class_prefix", "");
				$controller_incs = Directory::list_recursive($controller_path, $list_options);
				foreach ($controller_incs as $controller_inc) {
					if (strpos("/$controller_inc", '/.') !== false) {
						continue;
					}
					$application->logger->debug("Found controller {controller_inc}", compact("controller_inc"));

					try {
						$controller_inc = File::extension_change($controller_inc, null);
						$class_name = $class_prefix . 'Controller_' . strtr($controller_inc, '/', '_');
						$application->logger->debug("class name is {class_name}", compact("class_name"));
						$reflectionClass = new ReflectionClass($class_name);
						if (!$reflectionClass->isAbstract()) {
							/* @var $controller Controller */
							$controller = $reflectionClass->newInstance($application);
							$found[$reflectionClass->getName()] = array(
								'path' => path($controller_path, $controller_inc),
								'classes' => $controller->call_hook('classes', array(), array()),
							);
						}
					} catch (ReflectionException $e) {
					} catch (\Exception $e) {
						$application->logger->error("Exception creating controller {controller_inc} {e}", compact("controller_inc", "e"));
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
