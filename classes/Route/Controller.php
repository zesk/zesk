<?php

/**
 *
 */
namespace zesk;

use \ReflectionClass;

/**
 *
 * @author kent
 *
 */
class Route_Controller extends Route {

	/**
	 *
	 * @var ReflectionClass
	 */
	protected $class = null;

	/**
	 *
	 * @var string The class which was instantiated.
	 */
	protected $class_name = null;

	/**
	 *
	 * @var Controller
	 */
	protected $controller = null;
	/**
	 *
	 * @var string
	 */
	protected $controller_action = null;
	protected function initialize() {
		$action = $this->option("action", "");
		if (!is_string($action)) {
			throw new Exception_Parameter("Action must be a string: {type}", array(
				"type" => type($action)
			));
		}
	}

	/**
	 *
	 * {@inheritdoc}
	 *
	 * @see Route::__sleep()
	 */
	public function __sleep() {
		return array_merge(array(), parent::__sleep());
	}

	/**
	 *
	 * {@inheritdoc}
	 *
	 * @see Route::__wakeup()
	 */
	public function __wakeup() {
		parent::__wakeup();
		$this->class = null;
		$this->class_name = null;
		$this->controller = null;
		$this->controller_action = null;
	}

	/**
	 *
	 * @return multitype:Controller string
	 */
	private function _init_controller(Response $response = null) {
		if ($this->controller instanceof Controller) {
			return array(
				$this->controller,
				$this->controller_action
			);
		}

		list($class, $this->controller_action) = $this->_determine_class_action();

		/* @var $controller Controller */
		$this->controller = $class->newInstance($this->application, $this, $response, $this->option_array("controller options") + $this->options);

		return array(
			$this->controller,
			$this->controller_action
		);
	}

	/**
	 *
	 * @return string
	 */
	function action_method_prefix() {
		return $this->option('method prefix', 'action_');
	}
	/**
	 * Execute this route
	 *
	 * @see Route::_execute()
	 */
	function _execute(Response $response) {
		$app = $this->application;
		list($controller, $action) = $this->_init_controller($response);
		$__ = array(
			'class' => get_class($controller),
			'action' => $action
		);
		$app->logger->debug("Controller {class} running action {action}", $__);
		$action_method = str_replace("-", "_", $action);
		try {
			$controller->optional_method(array(
				'before_' . $action_method,
				"before"
			), array());

			$arguments_method = $this->option('arguments method', $this->option('arguments method prefix', 'arguments_') . $action_method);
			$method = $this->option('method', $this->action_method_prefix() . $action_method);
			$method = map($method, array(
				"method" => $this->request->method()
			));

			if ($response->status_code === Net_HTTP::Status_OK) {
				ob_start();
				if ($controller->has_method($method)) {
					$args = $controller->optional_method($arguments_method, $this->args);
					$result = $controller->invoke_method($method, $args);
				} else {
					if ($action !== "index") {
						$app->logger->warning("No such method {method} in {class}", array(
							"method" => $method,
							"class" => get_class($controller)
						));
					}
					$result = $controller->invoke_default_method(array_merge(array(
						$action
					), $this->args));
				}
				$contents = ob_get_clean();
				$controller->optional_method(array(
					'after_' . $action_method,
					"after"
				), array(
					$result,
					$contents
				));
			}
		} catch (Exception_Redirect $e) {
			throw $e;
		} catch (Exception $e) {
			$app->hooks->call("exception", $e);
			$controller->optional_method(array(
				'exception_' . $action_method,
				"exception"
			), array(
				$e
			));
			throw $e;
		}
		return $response;
	}

	/**
	 * Determine the class of the controller and the action to run
	 *
	 * @throws Exception_Class_NotFound
	 * @return array
	 */
	private function _determine_class_action() {
		$class_name = $this->option("controller");
		$options = $this->named + $this->options;

		$this->class = $reflectionClass = null;
		try {
			$this->class = $reflectionClass = new ReflectionClass($class_name);
			$this->class_name = $class_name;
			$this->log("Controller {class_name} created", array(
				"class_name" => $class_name
			));
		} catch (\ReflectionException $e) {
		} catch (Exception_Class_NotFound $e) {
		}
		if (!$reflectionClass) {
			throw new Exception_Class_NotFound($class_name, __("Controller {controller} not found", array(
				'controller' => $class_name
			)));
		}
		if ($reflectionClass->isAbstract()) {
			throw new Exception_Class_NotFound('Class {class_name} is abstract, can not instantiate', array(
				'class_name' => $class_name
			));
		}
		$action = aevalue($options, "action", $this->option("default action", "index"));
		return array(
			$reflectionClass,
			$action
		);
	}

	/**
	 *
	 * {@inheritDoc}
	 * @see \zesk\Route::class_actions()
	 */
	function class_actions() {
		$actions = parent::class_actions();
		list($reflection) = $this->_determine_class_action();
		$action_prefix = $this->action_method_prefix();
		$action_list = array();
		/* @var $reflection \ReflectionClass */
		foreach ($reflection->getMethods(\ReflectionMethod::IS_PROTECTED | \ReflectionMethod::IS_PUBLIC) as $method) {
			/* @var $method \ReflectionMethod */
			$name = $method->getName();
			if (begins($name, $action_prefix)) {
				$action_list[] = StringTools::unprefix($name, $action_prefix);
			}
		}
		$actions[strtolower($reflection->getName())] = $action_list;
		return $actions;
	}
	/**
	 *
	 * @param unknown $action
	 * @param unknown $object
	 * @param unknown $options
	 * @return number
	 */
	protected function get_route_map($action, $object = null, $options = null) {
		$map = parent::get_route_map($action, $object, $options);
		$url = map($this->clean_pattern, $map);
		if (!$this->match($url)) {
			die("pattern didn't match");
		}
		$this->_map_options();
		list($controller) = $this->_init_controller($this->response);
		if ($controller) {
			$map = $controller->get_route_map($action, $object, $options) + $map;
		}
		$this->_unmap_options();
		return $map;
	}
}
