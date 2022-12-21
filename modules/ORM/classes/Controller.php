<?php declare(strict_types=1);
/**
 *
 * @package zesk
 * @subpackage controller
 * @author $Author: kent $
 * @copyright Copyright &copy; 2022, Market Acumen, Inc.
 */
namespace zesk\ORM;

use zesk\Exception_Authentication;
use zesk\Exception_Configuration;
use zesk\Exception_Deprecated;
use zesk\Exception_Key;
use zesk\Exception_Parameter;
use zesk\Exception_Permission;
use zesk\Exception_Redirect;
use zesk\Exception_Semantics;
use zesk\Exception_Unimplemented;
use zesk\Exception_Class_NotFound;
use zesk\HTML;
use zesk\HTTP;
use zesk\Model;
use zesk\StringTools;
use zesk\ORM\Exception_ORMNotFound as Exception_ORM_NotFound;

/**
 *
 * @author kent
 *
 */
abstract class Controller extends Controller_Authenticated {
	/**
	 * ORM class to control
	 *
	 * @var string
	 */
	protected string $class = '';

	/**
	 * Class name (e.g. "Link", "Page", etc.)
	 *
	 * The untranslated string before it is used.
	 *
	 * @var string
	 */
	protected string $class_name = '';

	/**
	 * Locale-specific object class name (e.g. "Link", "Page", etc.)
	 *
	 * The localized string for this class.
	 *
	 * @var string
	 */
	protected string $locale_class_name = '';

	/**
	 * Locale of the class_name for translation
	 *
	 * @var string
	 */
	protected string $class_name_locale = '';

	/**
	 * URL to redirect to if Control_${this->class}_List
	 *
	 * @var string
	 */
	protected string $not_found_url = '';

	/**
	 * Message to pass to failed page
	 *
	 * @var string
	 */
	protected string $not_found_message = 'Page not found';

	/**
	 *
	 * @var string
	 */
	protected string $not_found_content = 'Page not found';

	/**
	 * Action default (override in subclasses)
	 *
	 * @var string
	 */
	protected string $action_default = '';

	protected ?string $method_default_action = '_default_action_object';

	/**
	 *
	 * @var array
	 */
	protected array $actions = [
		'index' => [
			'List',
			'Index',
		],
		'list' => [
			'List',
			'Index',
		],
		'new' => [
			'New',
			'Edit',
		],
		'edit' => 'Edit',
		'delete' => [
			'Delete',
		],
		'duplicate' => [
			'Edit',
		],
	];

	protected array $control_options = [];

	/**
	 * Action which was found from ->actions above
	 *
	 * @var string
	 */
	protected string $actual_action = '';

	/**
	 * Permissions which are required for this object to continue
	 *
	 * @var string
	 */
	protected string $permission_actions = '';

	/**
	 * List of widgets tried when loading controller widget
	 *
	 * @var array of string
	 */
	protected array $tried_widgets = [];

	/**
	 *
	 * @var Widget
	 */
	protected ?Widget $widget = null;

	/**
	 * Action related to above widget
	 *
	 * @var string
	 */
	protected string $widget_action = '';

	/**
	 *
	 * @return array:
	 */
	public function hook_actions(): array {
		return array_keys($this->actions);
	}

	/**
	 * Classes that are handled by this controller
	 *
	 * @return array
	 */
	public function hook_classes(): array {
		return [
			$this->class,
		];
	}

	/**
	 * Initialize a Controller_ORM
	 *
	 */
	protected function initialize(): void {
		parent::initialize();
		if ($this->class) {
			$controller_class = get_class($this);
			[$ns, $cl] = reversePair($controller_class, '\\', '', $controller_class);
			if ($ns) {
				$ns .= '\\';
			}
			$this->class = $ns . StringTools::removePrefix($cl, 'Controller_');
			$this->application->logger->debug('Automatically computed ORM class name {class} from {controller_class}', [
				'controller_class' => $controller_class,
				'class' => $this->class,
			]);
		}
		if (!$this->class_name) {
			$class = $this->application->class_ormRegistry($this->class);
			$this->class_name = $class->name;
		}
		if ($this->class_name && !$this->locale_class_name) {
			$locale = $this->application->localeRegistry($this->class_name_locale ?: $this->application->locale->id());
			$this->locale_class_name = $locale->__($this->class_name);
		}
	}

	/**
	 *
	 * @param string $action
	 * @param string $id
	 * @return ORMBase
	 */
	private function _action_default_arguments(string $action = null, string $id = null): array {
		$args = func_get_args();
		if (!empty($id)) {
			$object = $this->controller_ormFactory($id);
			if ($object) {
				$args[1] = $object;
			}
		}
		return $args;
	}

	/**
	 *
	 * @param ORMBase $object
	 * @param string $option
	 * @param string $default_action
	 * @param string $default_url
	 * @return string
	 */
	private function _compute_url(ORMBase $object, string $option, string $default_action = '', string $default_url =
	''): string {
		$class = $object::class;
		$url = '';
		$action = $this->firstOption(["$class::${option}_action", "${option}_action"], $default_action);
		if ($action) {
			try {
				$url = $this->router->getRoute($action, $object);
			} catch (\zesk\Exception_NotFound $e) {
			}
		}
		if (!$url) {
			$url = $this->firstOption(["$class::${option}_url", "${option}_url"], $default_url);
		}
		return $url;
	}

	/**
	 *
	 * @param string $redirect_url
	 * @param string $message
	 * @param array $options
	 * @throws Exception_Redirect
	 */
	private function _redirect_response(string $redirect_url, string $message, array $options): void {
		$format = $this->request->get('format');
		if ($format === 'json') {
			$this->setAutoRender(false);
			$this->response->json()->setData([
				'message' => $message,
				'redirect_url' => $redirect_url,
			] + $options);
			return;
		}
		$this->response->redirectDefault($redirect_url, $message);
	}

	/**
	 *
	 * @param string|int|array|ORMBase $parameter
	 * @return array
	 */
	private function _arguments_load(string|int|array|ORMBase $parameter): array {
		if ($parameter instanceof ORMBase) {
			return [
				$parameter,
			];
		}
		return [
			$this->controller_ormFactory($parameter),
		];
	}

	/**
	 *
	 * @param unknown $parameter
	 * @return ORMBase[]
	 */
	public function arguments_delete(mixed $parameter): array {
		$result = $this->_arguments_load($parameter);
		if ($result[0] === null) {
			$this->response->redirect('/', $this->application->locale->__('No such object to delete'));
		}
		return $result;
	}

	/**
	 *
	 * @param string $parameter
	 * @return ORMBase[]
	 */
	public function arguments_duplicate(mixed $parameter): array {
		return $this->_arguments_load($parameter);
	}

	/**
	 *
	 * @param ORMBase $object
	 * @return mixed|void
	 * @throws Exception_ORMNotFound
	 * @throws Exception_Redirect
	 */
	public function action_delete(ORMBase $object) {
		$widget = $this->_actionFindWidget('delete');
		if ($widget) {
			return $this->_action_default('delete');
		}
		$user = $this->user;
		if ($user->can('delete', $object)) {
			$this->callHook('delete_before', $object);

			try {
				$object->delete();
				$message = $object::class . ':=Deleted {class_name-context-object-singular} "{display_name}".';
				$result = true;
			} catch (Exception_ORMNotFound|Exception_Configuration|Exception_Unimplemented|Exception_Deprecated
			|Exception_Key|Exception_Semantics) {
				$message = $object::class . ':=Unable to delete {class_name-context-object-singular} "{display_name}".';
				$result = false;
			}
		} else {
			$message = $object::class . ':=You do not have permission to delete {class_name-context-object-singular} "{display_name}".';
			$result = false;
		}
		$message = $this->application->locale->__($message, $object->words());
		$redirect_url = $this->_compute_url($object, $result ? 'delete_next' : 'delete_failed', '/', $this->request->get('ref') ?? '/');
		$format = $this->request->get('format');
		if ($format === 'json' || $this->request->preferJSON()) {
			$this->setAutoRender(false);
			$this->response->json()->setData([
				'message' => $message,
				'status' => $result,
				'redirect_url' => $redirect_url,
			]);
			return;
		}
		$this->response->redirectDefault($redirect_url, $message);
	}

	/**
	 *
	 * @param ORMBase $object
	 * @throws Exception_Redirect
	 */
	public function action_duplicate(ORMBase $object): void {
		$user = $this->user;
		$class = $object::class;
		if ($user->can('duplicate', $object)) {
			try {
				$new_object = $object->duplicate();
				$message = "$class:=Duplicated {class_name-context-object-singular} \"{display_name}\".";
				$result = true;
			} catch (Exception_Deprecated|Exception_Key|Exception_Semantics $e) {
				$message = "$class:=Unable to duplicate {class_name-context-object-singular} \"{display_name}\".";
				$result = false;
			}
		} else {
			$message = "$class:=You do not have permission to duplicate {class_name-context-object-singular} \"{display_name}\".";
			$result = false;
		}
		$locale = $this->application->locale;
		$message = $locale->__($message, $object->words());
		$redirect_url = $this->_compute_url($object, $result ? 'duplicate_next' : 'duplicate_fail', 'list', $this->request->get('ref'));
		$walker = JSONWalker::factory();
		$this->_redirect_response($redirect_url, $message, [
			'status' => $result,
			'original_object' => $object->json($walker),
			'object' => $new_object->json($walker),
		]);
	}

	/**
	 *
	 * {@inheritDoc}
	 * @see \zesk\Controller_Template::after()
	 */
	public function after(string $result = null, string $output = null): void {
		if ($this->request->preferJSON()) {
			/**
			 * @var $response Response
			 */
			$response = $this->response;
			if (!$response->isJSON()) {
				$content = $response->content;
				if (!empty($result)) {
					$content .= $result;
				}
				if ($output) {
					$content .= $output;
				}
				$output_json = $response->isHTML() ? $response->html()->toJSON() : $response->toJSON();
				$json = $response->response_data() + [
					'status' => true,
					'content' => $content,
					'microtime' => microtime(true),
				] + $output_json;

				$this->json($json);
			}
			$this->autoRender(false);
		} elseif ($this->response->isJSON()) {
			$this->autoRender(false);
		}
		parent::after($result, $output);
	}

	/**
	 *
	 * @param string $action
	 * @return string[]
	 */
	protected function widgetControlClasses(string $action): array {
		$actual_actions = $this->actions[$action] ?? null;
		$actual_actions = toList($actual_actions);
		$this->tried_widgets = [];
		$controls = [];
		[$namespace, $class] = reversePair($this->class, '\\', '', $this->class);
		foreach ($actual_actions as $actual_action) {
			$controls[$namespace . '\\Control_' . $actual_action . '_' . $class] = $actual_action;
		}
		return $controls;
	}

	/**
	 *
	 * @param string $action
	 * @return Widget
	 * @throws Exception_ORMNotFound
	 */
	private function _actionFindWidget(string $action): Widget {
		if ($this->widget_action === $action && $this->widget) {
			return $this->widget;
		}
		$controls = $this->widgetControlClasses($action);
		foreach ($controls as $control => $actual_action) {
			try {
				$this->tried_widgets[] = $control;
				$widget = $this->widgetFactory($control, $this->control_options);
				$this->actual_action = $actual_action;
				$this->permission_actions = $widget->option('permission_actions', $actual_action);
				$this->widget = $widget;
				$this->widget_action = $action;
				return $this->widget;
			} catch (Exception_Class_NotFound $e) {
			}
		}

		throw new Exception_ORMNotFound('Action not found {action}', ['action' => $action]);
	}

	/**
	 * Override in subclasses to get unique factory behavior (say, dependent on other objects in the route)
	 *
	 * @param string $mixed
	 * @param string $options
	 * @return ORMBase
	 */
	protected function controller_ormFactory($mixed = null, $options = null) {
		return $this->application->ormFactory($this->class, $mixed, toArray($options))->fetch();
	}

	/**
	 * Fetch an ORM from an user input ID
	 *
	 * @param string $class
	 * @param mixed $id
	 * @return ORMBase
	 *@throws Exception_Parameter
	 */
	protected function orm_from_id($class, $id) {
		$locale = $this->application->locale;
		$object = $this->application->ormFactory($class, $id);
		$name = $object->class_orm()->name;
		$__ = [
			'name' => $name,
		];
		if (empty($id)) {
			throw new Exception_Parameter('Invalid {name} ID', $__);
		}
		if (!is_numeric($id) || $id < 0) {
			throw new Exception_Parameter('Invalid {name} ID', $__);
		}

		try {
			return $object->fetch();
		} catch (Exception_ORM_NotFound $e) {
			throw new Exception_Parameter('{name} not found', $__);
		} catch (Exception_ORM_NotFound $e) {
			throw new Exception_Parameter('{name} is empty', $__);
		} catch (\Exception $e) {
			throw new Exception_Parameter('{name} unknown error {message}', $__ + [
				'message' => $e->getMessage(),
			]);
		}
	}

	/**
	 *
	 * {@inheritDoc}
	 * @throws Exception_ORMNotFound|Exception_Authentication|Exception_Permission
	 *@see \zesk\Controller::_action_default()
	 */
	public function _action_default_object(string $action = null, mixed $object = null): mixed {
		$this->application->logger->debug("Controller_ORM::_action_default($action)");

		try {
			$router = $this->router;
			$route = $this->route;
			$action = strval($action);
			if (empty($action)) {
				$action = 'index';
			}
			if (!array_key_exists($action, $this->actions)) {
				$url = rtrim($router->prefix() . $route->urlReplace('action', $this->action_default), '/');
				$query = $this->request->query();
				if ($query) {
					$url .= "?$query";
				}
				$this->application->logger->debug('Action {action} not found in {actions}', [
					'action' => $action,
					'actions' => $this->actions,
				]);
				return $this->response->redirect($url);
			}
			$ajax = $this->request->getBool('ajax');
			if ($ajax) {
				$this->control_options['ajax'] = true;
				$this->control_options['no-buttons'] = true;
			}
			$widget = $this->_actionFindWidget($action);

			try {
				$perm_action = $this->option('action');
				if (!$perm_action) {
					$perm_action = $this->actual_action;
				}
				if (!$this->user) {
					throw new Exception_Authentication('Attempting to perform {action}', [
						'action' => $perm_action,
					]);
				}
				if ($object instanceof Model) {
					$perm_actions = $widget->option('permission_actions', $perm_action);
					$this->user->must($perm_actions, $object);
				} else {
					$perm_actions = $widget->option('permission_actions', $this->class . '::' . $perm_action);
					$this->user->must($perm_actions);
				}
			} catch (Exception_Permission $e) {
				if ($ajax) {
					$this->error(HTTP::STATUS_UNAUTHORIZED);
					$this->json([
						'status' => false,
						'message' => $e->getMessage(),
					]);
				} else {
					throw $e;
					//$this->error_404($e->getMessage());
				}
			}
			if (is_numeric($object)) {
				$object = $this->controller_ormFactory($object);
				if ($object) {
					// Backwards compatibility: TODO is this needed anymore - corrupts truth of Request object
					$this->request->set($object->idColumn(), $object);
					if (!$this->user->can($this->actual_action, $object)) {
						throw new Exception_Permission($this->user, $this->actual_action, $object);
					}
				}
			}
			if (!$object instanceof Model) {
				$object = null;
			}
			$action_prefix = $this->application->locale->__(ucfirst($action)) . ' Controller.php';

			$title = $widget->option('title', $this->option('title', $this->route->option('title')));
			if ($title) {
				$title = map($title, [
					'class_name' => $this->class_name,
				]);
			} elseif ($object) {
				$title = $action_prefix . $this->class_name;
			}
			$widget->setOption('class_name', $this->class_name);
			return $this->control($widget, $object, [
				'title' => $title,
				'action' => $action,
				'route_action' => $action,
			]);
		} catch (Exception_Class_NotFound $e) {
			$this->application->hooks->call('exception', $e);
			if ($this->not_found_url) {
				$this->response->redirect()->url($this->not_found_url, $this->not_found_message);
			} else {
				return $this->not_found_content . HTML::tag('div', '.error', $e->getMessage());
			}
		}
		return null;
	}
}
