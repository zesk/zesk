<?php
/**
 *
 * @package zesk
 * @subpackage controller
 * @author $Author: kent $
 * @copyright Copyright &copy; 2017, Market Acumen, Inc.
 */
namespace zesk;

/**
 *
 * @author kent
 *
 */
abstract class Controller_ORM extends Controller_Authenticated {
	/**
	 * ORM class to control
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
			"Index",
		),
		"list" => array(
			"List",
			"Index",
		),
		"new" => array(
			"New",
			"Edit",
		),
		"edit" => "Edit",
		"delete" => array(
			"Delete",
		),
		"duplicate" => array(
			"Edit",
		),
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
	public function hook_actions() {
		return array_keys($this->actions);
	}

	/**
	 * Classes that are handled by this controller
	 *
	 * @return multitype:string
	 */
	public function hook_classes() {
		return array(
			$this->class,
		);
	}

	/**
	 * Initialize a Controller_ORM
	 *
	 */
	protected function initialize() {
		parent::initialize();
		if ($this->class === null) {
			$controller_class = get_class($this);
			list($ns, $cl) = pairr($controller_class, "\\", "", $controller_class);
			if ($ns) {
				$ns .= "\\";
			}
			$this->class = $ns . StringTools::unprefix($cl, "Controller_");
			$this->application->logger->debug("Automatically computed ORM class name {class} from {controller_class}", array(
				"controller_class" => $controller_class,
				"class" => $this->class,
			));
		}
		if (!$this->class_name) {
			/* @var $class Class_ORM */
			$class = $this->application->class_orm_registry($this->class);
			$this->class_name = $class->name;
		}
		if ($this->class_name !== null) {
			$this->class_name = __($this->class_name, $this->class_name_locale);
		}
	}

	/**
	 *
	 * @param unknown $action
	 * @param unknown $id
	 * @return \zesk\ORM
	 */
	private function _action_default_arguments($action = null, $id = null) {
		$args = func_get_args();
		if (!empty($id)) {
			$object = $this->controller_orm_factory($id);
			if ($object) {
				$args[1] = $object;
			}
		}
		return $args;
	}

	/**
	 *
	 * @param ORM $object
	 * @param unknown $option
	 * @param unknown $default_action
	 * @param unknown $default_url
	 * @return NULL|string|\zesk\Ambigous|mixed|array
	 */
	private function _compute_url(ORM $object, $option, $default_action = null, $default_url = null) {
		$class = get_class($object);
		$url = null;
		$action = $this->first_option("$class::${option}_action;${option}_action", $default_action);
		if ($action) {
			$url = $this->router->get_route($action, $object);
		}
		if (!$url) {
			$url = $this->first_option("$class::${option}_url;${option}_url", $default_url);
		}
		return $url;
	}

	/**
	 *
	 * @param unknown $redirect_url
	 * @param unknown $message
	 * @param array $options
	 */
	private function _redirect_response($redirect_url, $message, array $options) {
		$format = $this->request->get("format");
		if ($format === "json") {
			$this->auto_render = false;
			$this->response->json(array(
				"message" => $message,
				"redirect_url" => $redirect_url,
			) + $options);
			return;
		}
		$this->response->redirect_default($redirect_url, $message);
	}

	/**
	 *
	 * @param unknown $parameter
	 * @return \zesk\ORM[]
	 */
	private function _arguments_load($parameter) {
		if ($parameter instanceof ORM) {
			return array(
				$parameter,
			);
		}
		return array(
			$this->controller_orm_factory($parameter),
		);
	}

	/**
	 *
	 * @param unknown $parameter
	 * @return \zesk\ORM[]
	 */
	public function arguments_delete($parameter) {
		$result = $this->_arguments_load($parameter);
		if ($result[0] === null) {
			$this->response->redirect("/", __("No such object to delete"));
		}
		return $result;
	}

	/**
	 *
	 * @param unknown $parameter
	 * @return \zesk\ORM[]
	 */
	public function arguments_duplicate($parameter) {
		return $this->_arguments_load($parameter);
	}

	/**
	 *
	 * @param ORM $object
	 */
	public function action_delete(ORM $object) {
		$widget = $this->_action_find_widget("delete", $object);
		if ($widget) {
			return $this->_action_default("delete", $object);
		}
		$user = $this->user;
		if ($user->can($object, "delete")) {
			$this->call_hook('delete_before', $object);
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
		$redirect_url = $this->_compute_url($object, $result ? "delete_next" : "delete_failed", "/", $this->request->get("ref"));
		$format = $this->request->get("format");
		if ($format === "json" || $this->request->prefer_json()) {
			$this->auto_render = false;
			$this->response->json(array(
				"message" => $message,
				"status" => $result,
				"redirect_url" => $redirect_url,
			));
			return;
		}
		$this->response->redirect_default($redirect_url, $message);
	}

	/**
	 *
	 * @param ORM $object
	 */
	public function action_duplicate(ORM $object) {
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
			"status" => $result,
			"original_object" => $object->json(array()),
			"object" => $new_object->json(array()),
		));
	}

	/**
	 *
	 * {@inheritDoc}
	 * @see \zesk\Controller_Template::after()
	 */
	public function after($result = null, $output = null) {
		if ($this->request->prefer_json()) {
			/**
			 * @var $response Response
			 */
			$response = $this->response;
			if (!$response->is_json()) {
				$content = $response->content;
				if (!empty($result)) {
					$content .= $result;
				}
				if ($output) {
					$content .= $output;
				}
				$output_json = $response->is_html() ? $response->html()->to_json() : $response->to_json();
				$json = $response->response_data() + array(
					'status' => true,
					'content' => $content,
					'microtime' => microtime(true),
				) + $output_json;

				$this->json($json);
			}
			$this->auto_render(false);
		} elseif ($this->response->is_json()) {
			$this->auto_render(false);
		}
		parent::after($result, $output);
	}

	/**
	 *
	 * @param string $action
	 * @return string[]
	 */
	protected function widget_control_classes($action) {
		$actual_actions = avalue($this->actions, $action);
		$actual_actions = to_list($actual_actions);
		/* @var $widget Widget */
		$this->tried_widgets = array();
		$controls = array();
		list($namespace, $class) = pair($this->class, "\\", "", $this->class);
		foreach ($actual_actions as $actual_action) {
			//			$controls[$namespace "\\Control_" . $class . "_" . $actual_action] = $actual_action;
			$controls[$namespace . "\\Control_" . $actual_action . "_" . $class] = $actual_action;
		}
		return $controls;
	}

	/**
	 *
	 * @param unknown $action
	 * @return \zesk\Widget|NULL
	 */
	private function _action_find_widget($action) {
		if ($this->widget_action === $action && $this->widget) {
			return $this->widget;
		}
		$controls = $this->widget_control_classes($action);
		$widget = null;
		foreach ($controls as $control => $actual_action) {
			try {
				$this->tried_widgets[] = $control;
				$widget = $this->widget_factory($control, $this->control_options);
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
	 * @return ORM
	 */
	protected function controller_orm_factory($mixed = null, $options = null) {
		return $this->application->orm_factory($this->class, $mixed, to_array($options))->fetch();
	}

	/**
	 * Fetch an ORM from an user input ID
	 *
	 * @param string $class
	 * @param mixed $id
	 * @throws Exception_Parameter
	 * @return ORM
	 */
	protected function orm_from_id($class, $id) {
		$locale = $this->application->locale;
		$object = $this->application->orm_factory($class, $id);
		$name = $object->class_orm()->name;
		$__ = array(
			"name" => $name,
		);
		if (empty($id)) {
			throw new Exception_Parameter("Invalid {name} ID", $__);
		}
		if (!is_numeric($id) || $id < 0) {
			throw new Exception_Parameter("Invalid {name} ID", $__);
		}

		try {
			return $object->fetch();
		} catch (Exception_ORM_NotFound $e) {
			throw new Exception_Parameter("{name} not found", $__);
		} catch (Exception_ORM_NotFound $e) {
			throw new Exception_Parameter("{name} is empty", $__);
		} catch (\Exception $e) {
			throw new Exception_Parameter("{name} unknown error {message}", $__ + array(
				"message" => $e->getMessage(),
			));
		}
	}

	/**
	 *
	 * {@inheritDoc}
	 * @see \zesk\Controller::_action_default()
	 */
	public function _action_default($action = null, $object = null, $options = array()) {
		$options = to_array($options);
		$this->application->logger->debug("Controller_ORM::_action_default($action)");

		try {
			$request = $this->request;
			$router = $this->router;
			$route = $this->route;
			$action = strval($action);
			if (empty($action)) {
				$action = "index";
			}
			if (!array_key_exists($action, $this->actions)) {
				$url = rtrim($route->url_replace('action', $this->action_default), '/');
				$query = $this->request->query();
				if ($query) {
					$url .= "?$query";
				}
				$this->application->logger->debug("Action {action} not found in {actions}", array(
					"action" => $action,
					"actions" => $this->actions,
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
					"tried" => $this->tried_widgets,
				)));
			}

			try {
				$perm_action = firstarg($this->option('action'), $this->actual_action);
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
						"message" => $e->getMessage(),
					));
				} else {
					throw $e;
					//$this->error_404($e->getMessage());
				}
				return;
			}
			if (is_numeric($object)) {
				$object = $this->controller_orm_factory($object);
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
			$action_prefix = $this->application->locale->__(ucfirst($action)) . " ";

			$title = $widget->option('title', $this->option('title', $this->route->option('title')));
			if ($title) {
				$title = map($title, array(
					"class_name" => $this->class_name,
				));
			} elseif ($object) {
				$title = $action_prefix . $this->class_name;
			}
			$widget->set_option("class_name", $this->class_name);
			return $this->control($widget, $object, array(
				'title' => $title,
				"action" => $action,
				"route_action" => $action,
			));
		} catch (Exception_Class_NotFound $e) {
			$this->application->hooks->call("exception", $e);
			if ($this->not_found_url) {
				$this->response->redirect($this->not_found_url, $this->not_found_message);
			} else {
				return $this->not_found_content . HTML::tag("div", ".error", $e->getMessage());
			}
		}
	}
}
