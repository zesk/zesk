<?php
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

	/**
	 *
	 * @param Application $app
	 * @return multitype:Controller string
	 */
	private function _init_controller(Application $app) {
		if ($this->controller instanceof Controller) {
			return array(
				$this->controller,
				$this->controller_action
			);
		}

		list($class, $this->controller_action) = $this->_determine_class_action();

		/* @var $controller Controller */
		$this->controller = $class->newInstance($app, $this->option_array("controller options") + $this->options);

		return array(
			$this->controller,
			$this->controller_action
		);
	}

	/**
	 * Execute this route
	 *
	 * @see Route::_execute()
	 */
	function _execute(Application $app) {
		list($controller, $action) = $this->_init_controller($app);
		$__ = array(
			'class' => get_class($controller),
			'action' => $action
		);
		log::debug("Controller {class} running action {action}", $__);
		$action_method = str_replace("-", "_", $action);
		try {
			$controller->optional_method(array(
				'before_' . $action_method,
				"before"
			), array());

			$arguments_method = $this->option('arguments method', $this->option('arguments method prefix', 'arguments_') . $action_method);
			$method = $this->option('method', $this->option('method prefix', 'action_') . $action_method);

			$try_default = false;
			ob_start();
			if ($controller->has_method($method)) {
				$args = $controller->optional_method($arguments_method, $this->args);
				$result = $controller->invoke_method($method, $args);
			} else {
				log::warning("No such method {method} in {class}", array(
					"method" => $method,
					"class" => get_class($controller)
				));
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
		} catch (Exception $e) {
			zesk::hook("exception", $e);
			$controller->optional_method(array(
				'exception_' . $action_method,
				"exception"
			), array(
				$e
			));
			throw $e;
		}
	}

	/**
	 * Determine the class of the controller and the action to run
	 *
	 * @param Application $app
	 * @throws Exception_Class_NotFound
	 * @return array
	 */
	private function _determine_class_action() {
		$router = $this->router;
		if (!$router instanceof Router) {
			backtrace();
		}
		if ($this->has_option('controller prefix')) {
			$prefixes = array(
				$this->option('controller prefix')
			);
		} else {
			$prefixes = $this->option_list('controller prefixes');
			if (!$prefixes) {
				$prefixes = $router->controller_prefixes();
			}
		}
		$options = $this->named + $this->options;
		$default_controller = avalue($options, "default controller", "index");
		$controller = ucfirst(aevalue($options, "controller", $default_controller));

		$try_classes = array();
		$default_classes = array();
		foreach ($prefixes as $prefix) {
			$try_classes[] = begins($controller, $prefix) ? $controller : $prefix . $controller;
			$default_classes[] = $prefix . $default_controller;
		}
		$try_classes = array_unique(array_merge($try_classes, $default_classes));
		$this->class = $class = null;
		foreach ($try_classes as $class_name) {
			try {
				$this->class = $class = new ReflectionClass($class_name);
				$this->class_name = $class_name;
				$this->log("Controller $class_name created");
				break;
			} catch (Exception_Class_NotFound $e) {
			}
		}
		if (!$class) {
			$classes = implode(", ", $try_classes);
			throw new Exception_Class_NotFound($classes, __("Controller {controller} not found: {classes}", array(
				'controller' => $controller,
				'classes' => $classes
			)));
		}
		if ($class->isAbstract()) {
			throw new Exception_Class_NotFound('Class {class_name} is abstract, can not instantiate', array(
				'class_name' => $class_name
			));
		}
		$action = aevalue($options, "action", $this->option("default action", "index"));

		return array(
			$class,
			$action
		);
	}
	protected function get_route_map($action, $object = null, $options = null) {
		$map = parent::get_route_map($action, $object, $options);
		$url = map($this->clean_pattern, $map);
		if (!$this->match($url)) {
			die("pattern didn't match");
		}
		$this->_map_options();
		list($controller) = $this->_init_controller(Application::instance(), $map);
		if ($controller) {
			$map = $controller->get_route_map($action, $object, $options) + $map;
		}
		$this->_unmap_options();
		return $map;
	}

	protected function hook_controllers(Application $app) {
		$controller = $this->option('controller');
		if (map_clean($controller) !== $controller) {
			return array();
		}
		return array(
			$controller => zesk::factory("Controller_" . $controller, $app)
		);
	}
}
