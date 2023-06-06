<?php
declare(strict_types=1);
/**
 *
 * @package zesk
 * @subpackage controller
 * @author $Author: kent $
 * @copyright Copyright &copy; 2023, Market Acumen, Inc.
 */

namespace zesk\ORM;

use zesk\ArrayTools;
use zesk\Exception\ClassNotFound;
use zesk\Exception\NotFoundException;
use zesk\Exception\ParameterException;
use zesk\Exception\PermissionDenied;
use zesk\Exception\Redirect;
use zesk\Exception\Semantics;
use zesk\HTML;
use zesk\HTTP;
use zesk\Model;
use zesk\Request;
use zesk\Response;
use zesk\StringTools;
use zesk\Types;

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
			[$ns, $cl] = StringTools::reversePair($controller_class, '\\', '', $controller_class);
			if ($ns) {
				$ns .= '\\';
			}
			$this->class = $ns . StringTools::removePrefix($cl, 'Controller_');
			$this->application->logger->debug('Automatically computed ORM class name {class} from {controller_class}', [
				'controller_class' => $controller_class, 'class' => $this->class,
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
			$object = $this->ormFactory($id);
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
	private function _compute_url(ORMBase $object, string $option, string $default_action = '', string $default_url = ''): string {
		$class = $object::class;
		$url = '';
		$action = $this->firstOption(["$class::{$option}_action", "{$option}_action"], $default_action);
		if ($action) {
			try {
				$url = $this->router->getRoute($action, $object);
			} catch (NotFoundException $e) {
			}
		}
		if (!$url) {
			$url = $this->firstOption(["$class::{$option}_url", "{$option}_url"], $default_url);
		}
		return $url;
	}

	/**
	 *
	 * @param string $redirect_url
	 * @param string $message
	 * @param array $options
	 * @throws Redirect
	 */
	private function _redirect_response(string $redirect_url, string $message, array $options): void {
		$format = $this->request->get('format');
		if ($format === 'json') {
			$this->setAutoRender(false);
			$this->response->json()->setData([
				'message' => $message, 'redirect_url' => $redirect_url,
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
			$this->ormFactory($parameter),
		];
	}

	/**
	 *
	 * @param mixed $parameter
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
	 * @param Request $request
	 * @param Response $response
	 * @param array $arguments
	 * @return array
	 * @throws ParameterException
	 * @see self::action_DELETE_index()
	 */
	public function arguments_DELETE_index(Request $request, Response $response, array $arguments): array {
		$first = ArrayTools::first($arguments);
		if (!$first instanceof ORMBase) {
			throw new ParameterException('Need ORMBase as first parameter {first} ({type})', [
				'type' => type($first), 'first' => $first,
			]);
		}
		return [$first, $response];
	}

	/**
	 *
	 * @param ORMBase $object
	 * @return mixed|void
	 * @throws ORMNotFound
	 * @throws Redirect
	 * @see self::arguments_DELETE_index()
	 */
	public function action_DELETE_index(ORMBase $object, Response $response): Response {
		$user = $this->user;
		if ($user->can('delete', $object)) {
			$words = $object->words();
			$this->callHook('delete_before', $object);

			try {
				$object->delete();
				$result = true;
				$message = $object::class . ':=Deleted {class_name-context-object-singular} "{displayName}".';
			} catch (Database\Exception\Duplicate|Database\Exception\TableNotFound|Database\Exception\SQLException|KeyNotFound|ORMEmpty $e) {
				$message = $object::class . ':=Unable to delete {class_name-context-object-singular} "{displayName}".';
				$result = false;
			}
		} else {
			$words = ArrayTools::filterKeyPrefixes($object->words(), 'class_');
			$message = $object::class . ':=You do not have permission to delete {class_name-context-object-singular}.';
			$result = false;
		}
		$localeMessage = $this->application->locale->__($message, $words);
		return $response->json()->setData([
			'message' => $localeMessage,
			'rawMessage' => $message,
			'words' => $words,
			'status' => $result,
		]);
	}

	/**
	 *
	 * @param ORMBase $object
	 * @throws Redirect
	 */
	public function action_POST_duplicate(ORMBase $object): void {
		$user = $this->user;
		$class = $object::class;
		if ($user->can('duplicate', $object)) {
			try {
				$new_object = $object->duplicate();
				$message = "$class:=Duplicated {class_name-context-object-singular} \"{displayName}\".";
				$result = true;
			} catch (Deprecated|KeyNotFound|Semantics $e) {
				$message = "$class:=Unable to duplicate {class_name-context-object-singular} \"{displayName}\".";
				$result = false;
			}
		} else {
			$message = "$class:=You do not have permission to duplicate {class_name-context-object-singular} \"{displayName}\".";
			$result = false;
		}
		$locale = $this->application->locale;
		$message = $locale->__($message, $object->words());
		$redirect_url = $this->_compute_url($object, $result ? 'duplicate_next' : 'duplicate_fail', 'list', $this->request->get('ref'));
		$walker = JSONWalker::factory();
		$this->_redirect_response($redirect_url, $message, [
			'status' => $result, 'original_object' => $object->json($walker), 'object' => $new_object->json($walker),
		]);
	}

	/**
	 * Override in subclasses to get unique factory behavior (say, dependent on other objects in the route)
	 *
	 * @param mixed $mixed
	 * @param array $options
	 * @return ORMBase
	 */
	protected function ormFactory(mixed $mixed = null, array $options = null): ORMBase {
		return $this->application->ormFactory($this->class, $mixed, Types::toArray($options))->fetch();
	}

	/**
	 * Fetch an ORM from an user input ID
	 *
	 * @param string $class
	 * @param mixed $id
	 * @return ORMBase
	 * @throws ParameterException
	 */
	protected function orm_from_id(string $class, int|string|array $id): ORMBase {
		$object = $this->application->ormFactory($class, $id);
		$name = $object->class_orm()->name;
		$__ = [
			'name' => $name,
		];
		if (empty($id)) {
			throw new ParameterException('Invalid {name} ID', $__);
		}
		if (!is_numeric($id) || $id < 0) {
			throw new ParameterException('Invalid {name} ID', $__);
		}

		try {
			return $object->fetch();
		} catch (ORM_NotFound $e) {
			throw new ParameterException('{name} not found', $__);
		} catch (ORMEmpty $e) {
			throw new ParameterException('{name} is empty', $__);
		} catch (\Exception $e) {
			throw new ParameterException('{name} unknown error {message}', $__ + [
				'message' => $e->getMessage(),
			]);
		}
	}

	/**
	 *
	 * {@inheritDoc}
	 * @throws ORMNotFound|Authentication|PermissionDenied
	 * @see \zesk\Controller::_action_default()
	 */
	public function _action_default_object(string $action = null, mixed $object = null): mixed {
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
					'action' => $action, 'actions' => $this->actions,
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
					throw new Authentication('Attempting to perform {action}', [
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
			} catch (PermissionDenied $e) {
				if ($ajax) {
					$this->error(HTTP::STATUS_UNAUTHORIZED);
					$this->json([
						'status' => false, 'message' => $e->getMessage(),
					]);
				} else {
					throw $e;
					//$this->error_404($e->getMessage());
				}
			}
			if (is_numeric($object)) {
				$object = $this->ormFactory($object);
				if ($object) {
					// Backwards compatibility: TODO is this needed anymore - corrupts truth of Request object
					$this->request->set($object->idColumn(), $object);
					if (!$this->user->can($this->actual_action, $object)) {
						throw new PermissionDenied($this->user, $this->actual_action, $object);
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
				'title' => $title, 'action' => $action, 'route_action' => $action,
			]);
		} catch (ClassNotFound $e) {
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
