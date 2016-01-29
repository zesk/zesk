<?php
/**
 *
 * @version $URL: https://code.marketacumen.com/zesk/trunk/classes/controller/object.inc $
 * @package zesk
 * @subpackage controller
 * @author $Author: kent $
 * @copyright Copyright &copy; 2011, Market Acumen, Inc.
 *            Created on Mon,Aug 1, 11 at 5:19 PM
 */
abstract class Controller_Object extends Controller_Template_Login {

	/**
	 *
	 * @var boolean
	 */
	protected $is_ajax = null;

	/**
	 * Object class to control
	 *
	 * @var string
	 */
	protected $class = null;

	/**
	 * Locale-specific object class name (e.g. "Link", "Page", etc.)
	 *
	 * This string is translated before it's used
	 *
	 * @see $class_name_locale
	 * @var string
	 */
	protected $class_name = null;

	/**
	 * Locale of the class_name for translation
	 *
	 * @var string
	 */
	protected $class_name_locale = null;
	// /**
	// * @var Controller
	// */
	// protected $controller = null;


	/**
	 * URL to redirect to if Control_${this->class}_List
	 *
	 * @var string
	 */
	protected $not_found_url = null;

	/**
	 * Message to pass to failed page
	 *
	 * @var string
	 */
	protected $not_found_message = "Page not found";

	/**
	 *
	 * @var string
	 */
	protected $not_found_content = "Page not found";

	/**
	 * Action default (override in subclasses)
	 *
	 * @var string
	 */
	protected $action_default = '';

	/**
	 *
	 * @var array
	 */
	protected $actions = array(
		"index" => array(
			"List",
			"Index"
		),
		"list" => array(
			"List",
			"Index"
		),
		"new" => array(
			"New",
			"Edit"
		),
		"edit" => "Edit",
		"delete" => array(
			"Delete"
		),
		"duplicate" => array(
			"Edit"
		)
	);

	protected $control_options = array();

	/**
	 * Action which was found from ->actions above
	 *
	 * @var string
	 */
	protected $actual_action = null;

	/**
	 * Permissions which are required for this object to continue
	 *
	 * @var string
	 */
	protected $permission_actions = null;

	/**
	 * List of widgets tried when loading controller widget
	 *
	 * @var array of string
	 */
	protected $tried_widgets = array();

	/**
	 *
	 * @var Widget
	 */
	protected $widget = null;

	/**
	 * Action related to above widget
	 *
	 * @var string
	 */
	protected $widget_action = null;

	/**
	 *
	 * @return multitype:
	 */
	function hook_actions() {
		return array_keys($this->actions);
	}

	/**
	 * Classes that are handled by this controller
	 *
	 * @return multitype:string
	 */
	function hook_classes() {
		return array(
			$this->class
		);
	}

	/**
	 * Create a Controller_Object
	 *
	 * @param Application $app
	 * @param array $options
	 */
	function __construct(Application $app, $options = null) {
		if ($this->class === null) {
			$this->class = str::unprefix(get_class($this), "Controller_");
		}
		parent::__construct($app, $options);
		if (!$this->class_name) {
			/* @var $class Class_Object */
			$class = Object::cache_class($this->class, "class");
			$this->class_name = $class->name;
		}
		if ($this->class_name !== null) {
			$this->class_name = __($this->class_name, $this->class_name_locale);
		}
	}

	private function _action_default_arguments($action = null, $id = null) {
		$args = func_get_args();
		if (!empty($id)) {
			$object = $this->object_factory($id);
			if ($object) {
				$args[1] = $object;
			}
		}
		return $args;
	}

	private function _compute_url(Object $object, $option, $default_action = null, $default_url = null) {
		$class = get_class($object);
		$url = null;
		$action = $this->first_option("$class::${option}_action;${option}_action", $default_action);
		if ($action) {
			$url = $this->request->get_route($action, $object);
		}
		if (!$url) {
			$url = $this->first_option("$class::${option}_url;${option}_url", $default_url);
		}
		return $url;
	}

	private function _redirect_response($redirect_url, $message, array $options) {
		$format = $this->request->get("format");
		if ($format === "json") {
			$this->auto_render = false;
			$this->response->json(array(
				"message" => $message,
				"redirect_url" => $redirect_url
			) + $options);
			return;
		}
		$this->response->redirect_default($redirect_url, $message);
	}

	private function _arguments_load($parameter) {
		if ($parameter instanceof Object) {
			return array(
				$parameter
			);
		}
		return array(
			$this->object_factory($parameter)
		);
	}

	function arguments_delete($parameter) {
		$result = $this->_arguments_load($parameter);
		if ($result[0] === null) {
			$this->response->redirect("/", __("No such object to delete"));
		}
		return $result;
	}

	function arguments_duplicate($parameter) {
		return $this->_arguments_load($parameter);
	}

	function action_delete(Object $object) {
		$widget = $this->_action_find_widget("delete", $object);
		if ($widget) {
			return $this->_action_default("delete", $object);
		}
		$user = $this->user;
		if ($user->can($object, "delete")) {
			$this->hook('delete_before', $object);
			if (!$object->delete()) {
				$message = get_class($object) . ":=Unable to delete {class_name-context-object-singular} \"{display_name}\".";
				$result = false;
			} else {
				$message = get_class($object) . ":=Deleted {class_name-context-object-singular} \"{display_name}\".";
				$result = true;
			}
		} else {
			$message = get_class($object) . ":=You do not have permission to delete {class_name-context-object-singular} \"{display_name}\".";
			$result = false;
		}
		$message = $object->words(__($message));
		$redirect_url = $this->_compute_url($object, $result ? "delete_next" : "delete_failed", "list", $this->request->get("ref"));
		$format = $this->request->get("format");
		if ($format === "json" || $this->request->is_ajax()) {
			$this->auto_render = false;
			$this->response->json(array(
				"message" => $message,
				"status" => $result,
				"result" => $result,  // Deprecated
				"result-deprecated" => $result,  // Deprecated
				"redirect_url" => $redirect_url
			));
			return;
		}
		$this->response->redirect_default($redirect_url, $message);
	}

	function action_duplicate(Object $object) {
		$user = $this->user;
		$class = get_class($object);
		if ($user->can($object, "duplicate")) {
			$new_object = $object->duplicate();
			if ($new_object) {
				$message = "$class:=Duplicated {class_name-context-object-singular} \"{display_name}\".";
				$result = true;
			} else {
				$message = "$class:=Unable to duplicate {class_name-context-object-singular} \"{display_name}\".";
				$result = false;
			}
		} else {
			$message = "$class:=You do not have permission to duplicate {class_name-context-object-singular} \"{display_name}\".";
			$result = false;
		}
		$message = $object->words(__($message));
		$redirect_url = $this->_compute_url($object, $result ? "duplicate_next" : "duplicate_fail", "list", $this->request->get("ref"));
		return $this->_redirect_response($redirect_url, $message, array(
			"result" => $result,
			"result-deprecated" => $result,
			"status" => $result,
			"original_object" => $object->json(),
			"object" => $new_object->json()
		));
	}

	function before() {
		if ($this->request->getb('ajax')) {
			$this->is_ajax = true;
			$theme = $this->request->get("theme");
			if ($theme) {
				$this->template = $theme . ".tpl";
			}
		}
		parent::before();
	}

	function after($result = null, $output = null) {
		if ($this->is_ajax) {
			if (!$this->response->json()) {
				$content = $this->template->content;
				if (!empty($result)) {
					$content .= $result;
				}
				if ($output) {
					$content .= $output;
				}
				$json = array(
					'status' => true,
					'content' => $content,
					'microtime' => microtime(true)
				) + $this->response->to_json();

				$this->response->json($json);
			}
			$this->auto_render(false);
		} else if ($this->response->json()) {
			$this->auto_render(false);
		}
		parent::after();
	}

	protected function widget_control_classes($action) {
		$actual_actions = avalue($this->actions, $action);
		$actual_actions = to_list($actual_actions);
		/* @var $widget Widget */
		$this->tried_widgets = array();
		$controls = array();
		foreach ($actual_actions as $actual_action) {
			$controls["Control_" . $this->class . "_" . $actual_action] = $actual_action;
			$controls["Control_" . $actual_action . "_" . $this->class] = $actual_action;
		}
		return $controls;
	}

	private function _action_find_widget($action) {
		if ($this->widget_action === $action && $this->widget) {
			return $this->widget;
		}
		$controls = $this->widget_control_classes($action);
		$widget = null;
		// TODO This can be sped up so the found Widget class is cached
		foreach ($controls as $control => $actual_action) {
			try {
				$this->tried_widgets[] = $control;
				$widget = Widget::factory($control, $this->control_options, $this->application);
				$this->actual_action = $actual_action;
				$this->permission_actions = $widget->option('permission_actions', $actual_action);
				$this->widget = $widget;
				$this->widget_action = $action;
				return $this->widget;
			} catch (Exception_Class_NotFound $e) {
			}
		}
		return null;
	}

	/**
	 * Override in subclasses to get unique factory behavior (say, dependent on other objects in the route)
	 *
	 * @param string $mixed
	 * @param string $options
	 * @return Object
	 */
	protected function object_factory($mixed = null, $options = null) {
		return Object::factory($this->class, $mixed, $options)->fetch();
	}

	function _action_default($action = null, $object = null, $options = array()) {
		$options = to_array($options);
		log::debug("Controller_Object::_action_default($action)");
		try {
			$request = $this->request;
			$router = $request->router;
			$route = $router->route;
			$action = strval($action);
			if (empty($action)) {
				$action = "index";
			}
			if (!array_key_exists($action, $this->actions)) {
				$url = rtrim($router->url_replace('action', $this->action_default), '/');
				$query = $this->request->query();
				if ($query) {
					$url .= "?$query";
				}
				log::debug("Action {action} not found in {actions}", array(
					"action" => $action,
					"actions" => $this->actions
				));
				$this->response->redirect($url);
				return;
			}
			$ajax = $this->request->getb("ajax");
			if ($ajax) {
				$this->control_options['ajax'] = true;
				$this->control_options['no-buttons'] = true;
			}
			$widget = $this->_action_find_widget($action);
			if ($widget === null) {
				throw new Exception_NotFound(__("No control found for action {action}: {tried}", array(
					"action" => $action,
					"tried" => $this->tried_widgets
				)));
			}
			try {
				$perm_action = $this->option('action', $this->actual_action);
				if ($object instanceof Model) {
					$perm_actions = $widget->option('permission_actions', $perm_action);
					$this->user->must($perm_actions, $object);
				} else {
					$perm_actions = $widget->option('permission_actions', $this->class . "::" . $perm_action);
					$this->user->must($perm_actions);
				}
			} catch (Exception_Permission $e) {
				if ($ajax) {
					$this->json(array(
						"status" => false,
						"message" => $e->getMessage()
					));
				} else {
					throw $e;
					//$this->error_404($e->getMessage());
				}
				return;
			}
			if (is_numeric($object)) {
				$object = $this->object_factory($object);
				if ($object) {
					// Backwards compatibility: TODO is this needed anymore - corrupts truth of Request object
					$this->request->set($object->id_column(), $object);
					if (!$this->user->can($this->actual_action, $object)) {
						throw new Exception_Permission($this->actual_action, $object);
					}
				}
			}
			if (!$object instanceof Model) {
				$object = null;
			}
			$action_prefix = __(ucfirst($action)) . " ";

			$title = $widget->option('title', $this->option('title', $this->route->option('title')));
			if ($title) {
				$title = map($title, array(
					"class_name" => $this->class_name
				));
			} else if ($object) {
				$title = $action_prefix . $this->class_name;
			}
			$widget->set_option("class_name", $this->class_name);
			$this->control($widget, $object, array(
				'title' => $title,
				"action" => $action,
				"route_action" => $action
			));
		} catch (Exception_Class_NotFound $e) {
			zesk::hook("exception", $e);
			if ($this->not_found_url) {
				$this->response->redirect($this->not_found_url, $this->not_found_message);
			} else {
				$this->template->content = $this->not_found_content . html::tag("div", ".error", $e->getMessage());
			}
		}
	}
}

