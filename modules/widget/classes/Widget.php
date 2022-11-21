<?php
declare(strict_types=1);

/**
 *
 * @package zesk
 * @subpackage widget
 * @author kent
 * @copyright Copyright &copy; 2022, Market Acumen, Inc.
 */

namespace zesk;

/**
 *
 * @author kent
 *
 */
class Widget extends Hookable {
	/**
	 * @var integer
	 */
	public const INITIALIZED = 0;

	/**
	 *
	 * @var integer
	 */
	public const ready = 1;

	/**
	 *
	 * @var integer
	 */
	public const submit = 2;

	/**
	 *
	 * @var integer
	 */
	public const render = 3;

	/**
	 *
	 * @var string
	 */
	public const option_minimum_glyph_length = 'minimum_glyph_length';

	/**
	 *
	 * @var string
	 */
	public const option_maximum_glyph_length = 'maximum_glyph_length';

	/**
	 * List of widget => error or errors
	 */
	protected array $errors = [];

	/**
	 * List of widget => messages
	 */
	protected array $messages = [];

	/**
	 * Parent widget, if any
	 *
	 * @var ?Widget
	 */
	protected ?Widget $parent = null;

	/**
	 * Request associated with this widget
	 *
	 * @var Request
	 */
	protected Request $request;

	/**
	 * For multi-value forms, this index tells the widget which value to check in the request
	 *
	 * @var mixed
	 */
	protected int $request_index = 0;

	/**
	 * Respose associated with this widget. Inherited by children, stored only at root Widget.
	 *
	 * @var ?Response
	 */
	private ?Response $response = null;

	/**
	 * Current locale for this widget
	 *
	 * @var ?Locale
	 */
	protected ?Locale $locale = null;

	/**
	 * Theme hook to use for output
	 *
	 * @var array|string
	 */
	protected array|string $theme = '';

	/**
	 * Variables to pass, automatically, to the theme
	 *
	 * @var array
	 */
	protected array $theme_variables = [];

	/**
	 * Class for context of this widget (typically, the outside HTML tag)
	 *
	 * @var string
	 */
	private string $context_class = '';

	/**
	 * List of tag pairs to wrap this tag with, popped like a stack
	 *
	 * @var array
	 */
	protected array $wraps = [];

	/**
	 * Whether this has been wrapped
	 *
	 * @var boolean
	 */
	protected bool $wrapped = false;

	/**
	 * List of behaviors attached to this widget
	 *
	 * @var boolean
	 */
	private array $behaviors = [];

	/**
	 * Set to true in subclasses to render children and append to main render
	 *
	 * @var boolean
	 */
	protected bool $render_children = false;

	/**
	 * List of children widgets
	 *
	 * @var Widget[]
	 */
	public array $children = [];

	/**
	 * Whether this widget has been initialized
	 *
	 * @var boolean
	 */
	public bool $_initialize = false;

	/**
	 * Rendered content
	 */
	public ?string $content = null;

	/**
	 * String to output child nodes, set to blank to skip output
	 */
	public ?string $content_children = null;

	/**
	 * Widget column represents a particular class
	 *
	 * @var string
	 */
	protected string $class = '';

	/**
	 * Execution state
	 *
	 * @var mixed
	 */
	protected int $exec_state = self::INITIALIZED;

	/**
	 * Rendered state content, probably same as content but
	 * can not test now.
	 */
	private string $exec_render = '';

	/**
	 * When executing child, traverse the parent model
	 *
	 * @var boolean
	 */
	protected bool $traverse = false;

	/**
	 * Rename traversable children so input names are guaranteed unique.
	 * Generally should be handled by developer.
	 *
	 * @var boolean
	 */
	protected bool $traverse_rename = false;

	/**
	 * Hierarchy of classes up through Widget
	 *
	 * @var array
	 */
	protected array $hierarchy = [];

	/**
	 * What we're operating on
	 *
	 * @var Model
	 */
	protected Model $object;

	/**
	 * If request contains these values (strict), then ignore them
	 *
	 * @var array
	 */
	protected array $load_ignore_values = [
		null,
	];

	/**
	 * Create a new widget
	 *
	 * <code>
	 * $widget = new Widget($options);
	 * $widget = new Widget($request, $response, $options);
	 * $widget = new Widget($application, $options);
	 * </code>
	 *
	 * @param mixed $options
	 */
	public function __construct(Application $application, array $options = []) {
		parent::__construct($application, $options);
		$this->request = $application->request();
		$this->object = $this->model();
		$this->inheritConfiguration();

		$this->options += [
			'column' => $this->options['id'] ?? null,
		];
		$this->options += [
			'name' => $this->options['column'] ?? null,
		];

		if ($this->hasOption('locale')) {
			$this->locale($this->application->localeRegistry($this->option('locale')));
		}
		if (!$this->locale) {
			$this->locale = $application->locale;
		}
		$this->hierarchy = $application->classes->hierarchy($this, __CLASS__);
		if ($this->theme === '') {
			$this->theme = $this->default_theme();
		}
		if ($this->contextClass() === null) {
			$cl = get_class($this);
			$cl = StringTools::reverseRight($cl, '\\', $cl);
			$this->contextClass(strtr(strtolower($cl), '_', '-'));
		}
		$this->call_hook('construct');
	}

	/**
	 * Retrieve the default theme for this widget (includes reverse hierarchy of parents)
	 *
	 * @return array
	 */
	public function default_theme() {
		return ArrayTools::changeValueCase(tr($this->hierarchy, [
			'\\' => '/',
			'_' => '/',
		]));
	}

	/**
	 *
	 * @param array|string $theme
	 * @return Widget
	 */
	public function set_theme(array|string $theme): self {
		$this->theme = $theme;
		return $this;
	}

	/**
	 * Set/get title
	 *
	 * @param string $set
	 * @return string Control_Edit
	 */
	public function title($set = null) {
		return $set === null ? $this->option('title') : $this->setOption('title', $set);
	}

	/**
	 * Class applied to outer HTML tag for this widget
	 *
	 * Can pass in CSS-style class (.foo) or class name (foo)
	 *
	 * @return string
	 */
	public function contextClass(): string {
		return strval($this->option('context_class'));
	}

	/**
	 * Class applied to outer HTML tag for this widget
	 *
	 * Can pass in CSS-style class (.foo) or class name (foo)
	 *
	 * @return string
	 */
	public function setContextClass(string $set, bool $add = true): self {
		$set = trim($set, '.');
		return $this->setOption('context_class', $add ? CSS::addClass($this->contextClass(), $set) : $set);
	}

	/**
	 * Retrieve the class object for this widget
	 *
	 * @return Class_ORM
	 */
	public function class_orm() {
		return $this->application->class_ormRegistry($this->class);
	}

	/**
	 * Getter/setter for the ORM subclass associated with this widget
	 *
	 * @param string $set
	 * @return string|self
	 * @deprecated 2019-12
	 * @see $this->orm_class_name
	 */
	final public function orm_class($set = null) {
		zesk()->deprecated();
		return $this->ormClassName($set);
	}

	/**
	 * Getter/setter for orm class naem
	 *
	 * @param string $set
	 * @return \zesk\Widget|string
	 */
	final public function ormClassName($set = null) {
		if ($set !== null) {
			$this->class = $set;
			return $this;
		}
		return $this->class;
	}

	/**
	 * Getter/setter for orm class naem
	 *
	 * @param string $set
	 * @return \zesk\Widget|string
	 */
	final public function setORMClassName(string $set): self {
		$this->class = $set;
		return $this;
	}

	/**
	 * @return Class_ORM
	 */
	final public function find_parent_class_orm() {
		$parent = $this->parent();
		while ($parent) {
			if ($parent->ormClassName()) {
				return $parent->class_orm();
			}
			$parent = $parent->parent();
		}
		return null;
	}

	/**
	 *
	 * @paam string $class
	 * @return Widget
	 */
	final public function addClass(string $class): self {
		// Some widgets have protected variable called class - always update the options here
		$this->options = HTML::addClass($this->options, $class);
		return $this;
	}

	final public function removeClass(string $class): self {
		// Some widgets have protected variable called class - always update the options here
		$this->options = HTML::removeClass($this->options, $class);
		return $this;
	}

	public function traverse_rename($set = null) {
		if ($set === null) {
			return $this->traverseRename();
		}
		$this->application->deprecated('setter');
		$this->setTraverseRename(toBool($set));
		return $this;
	}

	/**
	 * @return bool
	 */
	public function traverseRename(): bool {
		return $this->traverse_rename;
	}

	public function setTraverseRename(bool $traverse_rename_on = false): self {
		$this->traverse_rename = $traverse_rename_on;
		return $this;
	}

	/**
	 * Get or set the children of this widget
	 */
	public function children($set = null) {
		if (is_array($set)) {
			foreach ($set as $w) {
				/* @var $w Widget */
				if ($this->traverse && $this->traverse_rename) {
					$w->name($this->name() . '-' . $w->name());
				}
				$this->addChild($w);
			}
			return $this;
		}
		return $this->children;
	}

	/**
	 * @param string $name
	 * @return Widget
	 * @throws Exception_NotFound
	 */
	public function removeChild(string $name): Widget {
		foreach ($this->children as $k => $child) {
			if ($child->name() === $name) {
				unset($this->children[$k]);
				return $child;
			}
			$result = $child->remove_child($name);
			if ($result) {
				return $result;
			}
		}

		throw new Exception_NotFound($name);
	}

	/**
	 * Retrieve all children, indexed by input names (name)
	 */
	public function all_children(bool $include_this = false): array {
		$result = $include_this ? [
			$this,
		] : [];
		foreach ($this->children as $child) {
			$result = array_merge($result, $child->all_children(true));
		}
		return $result;
	}

	/**
	 * Add a child by name (searches grandchildren as well)
	 *
	 * $control = $widget->child("LoginEmail");
	 *
	 * Set a child by name:
	 * <code>
	 * $widget->child("LoginEmail", $email_widget);
	 * </code>
	 * Set a child:
	 * <code>
	 * $widget->addChild($email_widget);
	 * </code>
	 * Set a child first in the list:
	 * <code>
	 * $widget->addChild($email_widget, "first");
	 * </code>
	 * Set many children:
	 * <code>
	 * $widget->child(array("LoginEmail" => $login_email, "LoginPassword" =>
	 * </code>
	 *
	 *
	 * @param string $name
	 * @param Widget $widget
	 * @return self
	 */
	public function setChild(string $name, Widget $widget): self {
		$this->_setChild($name, $widget);
		return $this;
	}

	/**
	 * @param string $name
	 * @return Widget
	 * @throws Exception_Key
	 * @throws Exception_Semantics
	 */
	public function findChild(string $name): Widget {
		$result = $this->children[$name] ?? null;
		if ($result !== null) {
			return $result;
		}
		foreach ($this->children as $child) {
			try {
				return $child->findChild($name);
			} catch (Exception_Key) {
			}
		}

		throw new Exception_Key($name);
	}

	/**
	 * @param Widget $widget
	 * @param bool $first
	 * @return $this
	 * @throws Exception_Semantics
	 */
	public function addChild(Widget $widget, bool $first = false): self {
		$this->_setChild($widget->column(), $widget, $first);
		return $this;
	}

	/**
	 * @param string|Widget $name
	 * @param string|null $widget
	 * @return $this|Widget
	 * @throws Exception_Key
	 * @throws Exception_Semantics
	 * @deprecated 2022-05
	 */
	public function child(string $name): Widget {
		return $this->findChild($name);
	}

	/**
	 * @param string $id
	 * @param Widget $child
	 * @param bool $first
	 * @return void
	 * @throws Exception_Semantics
	 */
	private function _setChild(string $id, Widget $child, bool $first = false): void {
		$child->parent = $this;
		if ($first) {
			$this->children = array_merge([
				$id => $child,
			], $this->children);
		} else {
			$this->children[$id] = $child;
		}
		$this->call_hook_arguments('child', [$child], $id);
		if ($this->_initialize) {
			$child->initialize();
		}
	}

	public function addWrap(string $tag, array|string $attributes = [], string $prefix = '', string $suffix = ''): self {
		$this->wraps[] = [
			$tag,
			HTML::toAttributes($attributes),
			$prefix,
			$suffix,
		];
		return $this;
	}

	/**
	 * Remove all wraps from this Widget
	 *
	 * @return Widget
	 */
	public function clearWrap(): self {
		$this->wraps = [];
		return $this;
	}

	/**
	 * Unwrap a set of tags
	 *
	 * @param string $content
	 */
	private function unwrap(string $content): string {
		assert(count($this->wraps) > 0);
		$object = $this->object;
		[$tag, $mixed, $prefix, $suffix] = array_shift($this->wraps);
		return HTML::tag($object->applyMap($tag), $object->applyMap($mixed), $prefix . $content . $suffix);
	}

	/**
	 * Unwrap all wrapped items and return new markup
	 *
	 * @param string $content
	 * @return string
	 */
	protected function unwrap_all(string $content = ''): string {
		while (count($this->wraps) > 0) {
			$content = $this->unwrap($content);
		}
		return $content;
	}

	/**
	 * Create a model in the application context
	 *
	 * @param string $class
	 * @param mixed $mixed
	 * @param array $options
	 */
	public function modelFactory(string $class, mixed $mixed = null, array $options = []): Model {
		return $this->application->modelFactory($class, $mixed, $options);
	}

	/**
	 * Create a widget, tie it to the same response as this widget
	 *
	 * @param string $class
	 * @param array $options
	 * @return Widget
	 * @throws Exception_Semantics
	 */
	public function widgetFactory(string $class, array $options = []): Widget {
		$widget = self::factory($this->application, $class, $options);
		$response = $this->response();
		if ($response) {
			$widget->setResponse($response);
		}
		return $widget;
	}

	/**
	 * Create a widget
	 *
	 * @param $class string
	 * @param $options array
	 *            Optional added options for this widget
	 *
	 * @return Widget
	 */
	public static function factory(Application $application, string $class, array $options = []): object {
		$args = [
			$application,
			$options,
		];

		try {
			$widget = $application->factoryArguments($class, $args);
		} catch (Exception_Class_NotFound $e) {
			if (!str_contains($class, '\\') && class_exists("zesk\\$class")) {
				$widget = $application->factoryArguments('zesk\\' . $class, $args);
				if ($widget) {
					$application->deprecated('{method} called with removePrefixed class {class}', [
						'method' => __METHOD__,
						'class' => $class,
					]);
				}
			} else {
				throw $e;
			}
		}
		if (!$widget instanceof Widget) {
			throw new Exception_Semantics("$class is not a Widget");
		}
		return $widget;
	}

	/**
	 * Application associated with this widget
	 */
	public function application(): Application {
		return $this->application;
	}

	/**
	 * Application associated with this widget
	 *
	 * @param Application $set
	 * @return $this
	 */
	public function setApplication(Application $set): self {
		$this->application = $set;
		return $this;
	}

	/**
	 * Request associated with this widget
	 *
	 * @return Request
	 */
	public function request(): Request {
		return $this->request;
	}

	/**
	 * Request associated with this widget
	 *
	 * @param Request $set
	 * @return self
	 */
	public function setRequest(Request $set): self {
		$this->request = $set;
		return $this;
	}

	/**
	 * Response associated with this widget. NOT created if not set.
	 *
	 * @return ?Response
	 */
	public function response(): ?Response {
		if ($this->response instanceof Response) {
			return $this->response;
		}
		/*
		 * Inherit from the parent first!
		 */
		if ($this->parent) {
			return $this->parent->response();
		}
		return $this->response; // = new Response($this->application, $this->request);
	}

	/**
	 * Response associated with this widget. NOT created if not set.
	 *
	 * @param $set Response to set
	 * @return self
	 */
	public function setResponse(Response $set): self {
		$this->response = $set;
		return $this;
	}

	/**
	 * Retrieve the user, if any, associated with permissions for this control.
	 *
	 * @return ?User
	 */
	public function user(bool $require = true): ?User {
		return $this->application()->user($this->request, $require);
	}

	/**
	 * Retrieve the account, if any, associated with permissions for this control.
	 *
	 * @return Account
	 */
	public function account(): Account {
		return $this->application->modelSingleton(__NAMESPACE__ . '\\Account');
	}

	/**
	 * Retrieve the session, if any, associated with permissions for this control.
	 *
	 * @return \zesk\Interface_Session
	 */
	public function session(bool $require = true): ?Interface_Session {
		return $this->application()->session($this->request, $require);
	}

	/**
	 * Equivalent of $this->user()->can($noun, $action, $object), but handles the case when no user
	 * exists.
	 * (Fails.)
	 *
	 * @param $noun mixed
	 *            String or object
	 * @param $action string
	 *            What you want to do to the object
	 * @param $object mixed
	 *            Optional target
	 */
	public function userCan(string $action, Model $object = null, array $options = []) {
		$user = $this->user();
		if (!$user instanceof User) {
			return false;
		}
		return $user->can($action, $object, $options);
	}

	/**
	 * Set/get file upload flag
	 *
	 * @param bool $set
	 */
	public function upload(): bool {
		return $this->optionBool('upload', $this->optionBool('is_upload'));
	}

	/**
	 * Set/get file upload flag
	 *
	 * @param boolean $set
	 */
	public function setUpload(bool $set): self {
		$this->options['upload'] = $set;
		if ($this->parent) {
			$this->parent->setUpload($set);
		}
		return $this;
	}

	/**
	 * Do not output this widget, save the rendered form and use it as a token in later widgets.
	 *
	 * @return string|bool
	 */
	public function saveRender(): string|bool {
		return $this->option('widget_save_result', false);
	}

	/**
	 * Do not output this widget, save the rendered form and use it as a token in later widgets.
	 *
	 * @param boolean|string $set Boolean turns it on/off, string turns it on and uses alternate token name
	 * @return self
	 */
	public function setSaveRender(bool|string $set): self {
		$this->setOption('widget_save_result', $set);
		return $this;
	}

	/**
	 * Parent Widget
	 *
	 * @return ?Widget
	 */
	public function parent(): ?Widget {
		return $this->parent;
	}

	/**
	 * set parent widget
	 *
	 * @param Widget $set
	 * @return $this
	 */
	public function setParent(Widget $set): self {
		$this->parent = $set;
		$this->application($set->application());
		$this->request($set->request());
		$this->response($set->response());
		return $this;
	}

	/**
	 * Topmost widget
	 *
	 * @return Widget
	 */
	public function top(): self {
		$depth = 0;
		$next = $this;
		do {
			$parent = $next;
			$next = $parent->parent();
			if (++$depth > 50) {
				throw new Exception_Semantics('Widgets are in a parent infinite loop - {class} {name}', [
					'class' => get_class($this),
					'name' => $this->name(),
				]);
			}
		} while ($next !== null);
		return $parent;
	}

	/**
	 * Get/set the value associated with this widget
	 *
	 * @return mixed
	 */
	public function value(): mixed {
		$column = $this->column();
		if ($column === null) {
			return null;
		}

		try {
			return $this->object->get($column, $this->default_value());
		} catch (Exception_ORM_NotFound $e) {
			return null;
		}
	}

	/**
	 * Get/set the value associated with this widget
	 *
	 * @param mixed $set
	 * @return self
	 */
	public function setValue(mixed $set): self {
		$this->object->set($this->column(), $set);
		return $this;
	}

	/**
	 * Return a JSON response via the response. Modifies response content type.
	 *
	 * @param mixed $set
	 * @return self
	 */
	public function json(array $set) {
		$this->response()->json()->setData($set);
		return $this;
	}

	/**
	 * Set names for this widget
	 *
	 * @param string $column
	 * @param string|bool $label
	 * @param string $name
	 */
	public function names(string $column, bool|string $label = true, string $name = ''): self {
		$this->setOption('column', $column);

		if ($label === false) {
			$this->setOption('nolabel', true);
		} elseif ($label === true) {
			$this->setOption('label', ucfirst($column));
			$this->clearOption('nolabel');
		} else {
			$this->setOption('label', $label);
			$this->clearOption('nolabel');
		}
		$name = !empty($name) ? $name : $column;
		$this->setOption('name', $name);
		$this->setOption('id', $name);
		return $this;
	}

	/**
	 * Get widget required
	 *
	 * @return bool
	 */
	public function required(): bool {
		return $this->optionBool('required');
	}

	/**
	 * Set widget required
	 *
	 * @param bool $set
	 * @return $this
	 */
	public function setRequired(bool $set): self {
		$this->setOption('required', $set);
		return $this;
	}

	/**
	 * Get widget required error message
	 *
	 * @return string
	 */
	public function requiredError(): string {
		return $this->option('error_required', '');
	}

	/**
	 * Set widget required error message
	 *
	 * @param string $set
	 * @return $this
	 */
	public function setRequiredError(string $set): self {
		return $this->setOption('error_required', $set);
	}

	/**
	 * Det the column name
	 *
	 * @param string $set
	 * @return $this
	 */
	public function setColumn(string $set) {
		$this->setOption('column', $set);
		return $this;
	}

	/**
	 * Get the column name
	 *
	 * @return string
	 */
	public function column(): string {
		return $this->option('column', '');
	}

	/**
	 * Get the input name
	 *
	 * @return string
	 */
	public function name(): string {
		return $this->option('name', '');
	}

	/**
	 * Set the input name
	 *
	 * @param string $set
	 * @return $this
	 */
	public function setName(string $set): self {
		$this->setOption('name', $set);
		return $this;
	}

	/**
	 * Get the no label option
	 *
	 * @return bool
	 */
	public function nolabel(): bool {
		return $this->optionBool('nolabel');
	}

	/**
	 * Set the no label option
	 *
	 * @param bool $set
	 * @return $this
	 */
	public function setNoLabel(bool $set): self {
		return $this->setOption('nolabel', $set);
	}

	/**
	 * Get the label
	 *
	 * @return string
	 */
	public function label(): string {
		return $this->option('label', '');
	}

	/**
	 * @param string $set
	 * @return $this
	 */
	public function setLabel(string $set): self {
		return $this->setOption('label', $set);
	}

	/**
	 * Get/set/append the suffix
	 *
	 * @param string $data
	 * @param boolean $append
	 * @return Widget|string
	 */
	public function suffix(): string {
		return $this->option('suffix', '');
	}

	/**
	 * Set/append the suffix
	 *
	 * @param string|null $data
	 * @param bool $append
	 * @return $this
	 */
	public function setSuffix(string $data = null, bool $append = false): self {
		if ($append) {
			return $this->setOption('suffix', $this->suffix() . $data);
		}
		return $this->setOption('suffix', $data);
	}

	/**
	 * Set/append the prefix
	 *
	 * @param string $data
	 * @param boolean $append
	 * @return self
	 */
	public function setPrefix(string $data, bool $append = false): self {
		if ($append) {
			return $this->setOption('prefix', $this->option('prefix') . $data);
		}
		return $this->setOption('prefix', $data);
	}

	/**
	 * Get the prefix
	 *
	 * @return string
	 */
	public function prefix(): string {
		return $this->option('prefix', '');
	}

	/**
	 * Get the form name, finds it from the parent if exists
	 *
	 * @return string
	 */
	public function formName(): string {
		if ($this->parent) {
			return $this->parent->formName();
		}
		return $this->option('form_name', '');
	}

	/**
	 * Get/set the form name, finds it from the parent if exists
	 *
	 * @param string $set
	 * @return $this
	 */
	public function setFormName(string $set): self {
		if ($this->parent) {
			return $this->parent->setFormName($set);
		}
		$this->setOption('form_name', $set);
		return $this;
	}

	/**
	 * Get the form ID
	 *
	 * @return string
	 */
	public function formID(): string {
		if ($this->parent) {
			return $this->parent->formID();
		}
		return $this->option('form_id', '');
	}

	/**
	 * @param string $set
	 * @return $this
	 */
	public function setFormID(string $set): self {
		if ($this->parent) {
			return $this->parent->setFormID($set);
		}
		$this->setOption('form_id', $set);
		return $this;
	}

	/**
	 * Retrieve the language of this widget
	 *
	 * @return string
	 */
	public function language(): string {
		return $this->locale->language();
	}

	/**
	 * Get/set the locale of this widget
	 *
	 * @return Locale
	 */
	public function locale(): Locale {
		return $this->locale;
	}

	/**
	 * Get/set the locale of this widget
	 *
	 * @return self
	 */
	public function setLocale(Locale $set): self {
		$this->locale = $set;
		return $this;
	}

	/**
	 * Get the display size of this widget (usually how much text is visible)
	 *
	 * @return int
	 */
	public function showSize(): int {
		return $this->optionInt('show_size', -1);
	}

	/**
	 * Set the display size of this widget (usually how much text is visible)
	 *
	 * @param int $set
	 * @return $this
	 */
	public function setShowSize(int $set): self {
		$this->setOption('show_size', $set);
		return $this;
	}

	/**
	 * Retrieve whether this object is "new" or an existing object
	 *
	 * @return boolean
	 */
	protected function is_new() {
		if (method_exists($this->object, 'is_new')) {
			return $this->object->is_new();
		}
		return false;
	}

	/**
	 * Does this widget output a visible widget when output?
	 *
	 * Invokes "hook_visible" to determine result. Defaults to true.
	 *
	 * @return boolean
	 */
	public function is_visible() {
		if ($this->saveRender()) {
			return false;
		}
		return $this->call_hook_arguments('visible', [], true);
	}

	/**
	 * Clear errors
	 *
	 * @return Widget
	 */
	public function clear() {
		$this->errors = [];
		$this->messages = [];
		foreach ($this->children as $child) {
			$child->clear();
		}
		return $this;
	}

	/**
	 * Retrieve errors
	 *
	 * @param mixed $filter
	 * @return array
	 */
	public function errors($filter = null) {
		if ($filter !== null) {
			return ArrayTools::filter($this->errors, $filter);
		}
		return $this->errors;
	}

	/**
	 * Retrieve errors and children errors
	 *
	 * @return array
	 */
	public function children_errors() {
		$errors = $this->errors;
		if (is_array($this->children) && count($this->children) > 0) {
			foreach ($this->children as $name => $child) {
				$errors += $child->children_errors();
			}
		}
		return $errors;
	}

	/**
	 * Retrieve messages
	 *
	 * @param unknown $filter
	 */
	public function messages($filter = null) {
		if ($filter !== null) {
			return ArrayTools::filter($this->messages, $filter);
		}
		return $this->messages;
	}

	/**
	 * Does this widget have any errors?
	 */
	public function has_errors() {
		if (count($this->errors) !== 0) {
			return true;
		}
		$children_errors = $this->children_errors();
		return count($children_errors) !== 0;
	}

	/**
	 * Overwrite in subclasses to apply different values to errors
	 *
	 * @return array
	 */
	protected function error_map() {
		return $this->options;
	}

	/**
	 * Set errors for this widget
	 *
	 * @param mixed $message
	 *            String, array, or Widget
	 * @param mixed $col
	 *            Column for error, or null to use default
	 */
	public function error($message = null, $col = null) {
		if ($message === null) {
			$errors = $this->errors;
			foreach ($this->children as $child) {
				$errors = array_merge($errors, $child->error());
			}
			return $errors;
		}
		if (is_array($message)) {
			foreach ($message as $k => $v) {
				$this->error($v, $k);
			}
			return $this;
		} elseif ($message instanceof Widget) {
			$this->error($message->error());
			$this->message($message->message());
			return $this;
		} else {
			if ($col === null) {
				$col = $this->column();
			}
			if (is_array($this->children) && array_key_exists($col, $this->children)) {
				$this->children[$col]->error($message, $col);
			}
			$this->errors[$col] = map($message, $this->error_map());
			return $this;
		}
	}

	/**
	 * Set message for this widget
	 *
	 * @param mixed $message
	 *            String, array, or Widget
	 * @param mixed $col
	 *            Column for message, or null to use default
	 * @todo See if I can combine this with error somehow
	 */
	public function message($message = null, $col = null) {
		if ($message === null) {
			return $this->messages;
		}
		if (is_array($message)) {
			foreach ($message as $k => $v) {
				$this->message($v, $k);
			}
			return $this;
		} elseif ($message instanceof Widget) {
			$this->error($message->error());
			$this->message($message->message());
			return $this;
		} else {
			if ($col === null) {
				$col = $this->column();
			}
			if (is_array($this->children) && array_key_exists($col, $this->children)) {
				$this->children[$col]->message($message, $col);
			} else {
				$this->messages[$col] = map($message, $this->error_map());
			}
			return $this;
		}
	}

	/**
	 * Return a widget to call render on, or null
	 *
	 * @return \zesk\Widget|NULL
	 */
	final protected function _exec_submit() {
		$do_render = true;
		$valid = true;
		if ($this->exec_state === self::ready) {
			$this->exec_state = self::submit;
			if ($this instanceof Control) {
				/*
				 * Allow short-circuiting submit/render to allow individual widgets to handle controlling their aspect
				 * of a request without a separate controller.
				 */
				$target = $this->request->get('widget::target');
				if ($target) {
					$result = $this->_exec_controller($target);
				} elseif ($this->submitted()) {
					$this->load();
					if (($valid = $this->validate()) === true) {
						if (($valid = $this->call_hook_arguments('validate', [], true)) === true) {
							$do_render = $this->submit();
						}
					} else {
						$do_render = $this->call_hook_arguments('validate_failed', [], null);
					}
				}
			}
			$this->_update_child_state(self::submit);
		}
		// Truth table. Return value here controls whether widget renders or not.
		//
		// $result  true  true false false
		// $valid   false true false true
		// Render?  yes   yes  yes   no
		//
		// Basically, only time we don't render is when SUBMIT fails, and valid is TRUE
		// Probaby should check this for JSON responses as well, maybe turn into a "render" flag or something
		//
		if (!$valid) {
			$do_render = true;
		}
		return $do_render ? $this : null;
	}

	/**
	 * Execute child controller based on target name
	 *
	 * @param string $target
	 * @return boolean Like submit() - returns whether to continue (true) or stop processing (false)
	 */
	private function _exec_controller($target) {
		if ($this->name() === $target) {
			$this->controller();
			return false;
		}
		foreach ($this->children as $child) {
			$child->object($child->traverse ? $this->object->get($child->column()) : $this->object);
			if (!$child->_exec_controller($target)) {
				// Do not continue
				return false;
			}
		}
		// Continue
		return true;
	}

	/**
	 * Flag this widget as required failed
	 *
	 * @return Widget
	 */
	public function error_required() {
		$this->error($this->firstOption(['error_required', 'required_error'], $this->locale->__('{label} is a
		required field.')));
		return $this;
	}

	/**
	 * Validate our object as being ready for submission
	 *
	 * @return boolean
	 */
	protected function validate(): bool {
		$result = true;
		foreach ($this->children as $child) {
			if (!$child->validate()) {
				$this->application->logger->warning('{class}::validate() {child_class} named {name} did not validate', [
					'class' => get_class($this),
					'name' => $child->name(),
					'child_class' => $child::class,
				]);
				$result = false;
			}
		}
		if ($result === false) {
			return false;
		}
		if ($this->column() === null) {
			return $result;
		}
		if (!$this->validate_required()) {
			$this->empty_condition_apply();
			return false;
		}
		return $result && !$this->has_errors();
	}

	/**
	 * Check required status (default)
	 *
	 * @return boolean
	 */
	protected function validate_required() {
		if (!$this->required()) {
			return true;
		}
		$v = $this->value();
		if (is_array($v)) {
			if (count($v) > 0) {
				return true;
			}
		} elseif (is_string($v)) {
			if ($this->optionBool('trim')) {
				$v = trim($v);
			}
			if (strlen($v) > 0) {
				return true;
			}
		} elseif (is_numeric($v)) {
			return true;
		} elseif ($v instanceof \Iterator) {
			foreach ($v as $item) {
				return true;
			}
		} elseif (is_object($v)) {
			if ($v instanceof Object) {
				return !$v->is_new();
			}
			return !empty($v);
		}
		$this->error_required();
		return false;
	}

	/**
	 * Set the size limits for this widget (min/max byte length)
	 *
	 * Getter/setter. Call as follows:
	 *
	 * <pre>
	 * ->size() (returns array(min/max))
	 * ->size(max) sets max length
	 * ->size(min,max) sets min and max length
	 * </pre>
	 *
	 * @param unknown_type $mixed
	 * @param unknown_type $max
	 */
	public function size($mixed = null, $max = null) {
		if ($mixed === null && $max === null) {
			return [
				$this->optionInt('minlength'),
				$this->optionInt('maxlength'),
			];
		} elseif ($max === null) {
			$this->setOption('maxlength', $mixed);
			return $this;
		} else {
			$this->setOption('minlength', $mixed);
			$this->setOption('maxlength', $max);
			return $this;
		}
	}

	/**
	 * Set the size limits for this widget (min/max glyphs size)
	 *
	 * Getter/setter. Call as follows:
	 *
	 * <pre>
	 * $widget->glyphs() (returns array(min/max))
	 * $widget->glyphs(max) sets max glyphs
	 * ->glyphs(min,max) sets min and max number of glyphs
	 * </pre>
	 *
	 * @param int $mixed
	 * @param int $max
	 * @return array|Widget
	 */
	public function glyphs($mixed = null, $max = null) {
		if ($mixed === null && $max === null) {
			return [
				$this->optionInt(self::option_minimum_glyph_length),
				$this->optionInt(self::option_maximum_glyph_length),
			];
		} elseif ($max === null) {
			$this->setOption(self::option_maximum_glyph_length, $mixed);
			return $this;
		} else {
			$this->setOption(self::option_minimum_glyph_length, $mixed);
			$this->setOption(self::option_maximum_glyph_length, $max);
			return $this;
		}
	}

	/**
	 *
	 * @param string $message
	 * @param int $size
	 * @param int $entered_size
	 * @return boolean
	 */
	private function _character_error($message, $size, $entered_size = null) {
		$locale = $this->application->locale;
		$this->error($locale($message, [
			'label' => $this->label(),
			'length' => $size,
			'entered_length' => $entered_size,
			'characters' => $locale->plural($locale('character'), $size),
		]));
		return false;
	}

	/**
	 * Validate widget value size
	 */
	protected function validate_size() {
		if (!$this->validate_required()) {
			return false;
		}
		$v = $this->value();
		if (empty($v) && !$this->required()) {
			return true;
		}
		$byte_length = strlen($v);
		$glyph_length = StringTools::length($v, $this->option('encoding'));

		[$min_byte_length, $max_byte_length] = $this->size();
		[$min_glyph_length, $max_glyph_length] = $this->glyphs();

		/* Old style - useful to ensure we don't exceed database string sizes */
		if (($min_byte_length > 0) && ($byte_length < $min_byte_length)) {
			return $this->_character_error('{label} must be at least {length} {characters} long.', $min_byte_length, $byte_length);
		}
		if (($max_byte_length > 0) && ($byte_length > $max_byte_length)) {
			return $this->_character_error('{label} must be at most {length} {characters} long.', $max_byte_length, $byte_length);
			$this->value(substr($v, 0, $max_byte_length));
			return false;
		}
		if (($min_glyph_length > 0) && ($glyph_length < $min_glyph_length)) {
			return $this->_character_error('{label} must be at least {length} {characters} long.', $min_glyph_length, $glyph_length);
		}
		if (($max_glyph_length > 0) && ($glyph_length > $max_glyph_length)) {
			return $this->_character_error('{label} must be at most {length} {characters} long (You entered {entered_length}).', $max_glyph_length, $glyph_length);
		}
		return true;
	}

	/**
	 * Has this widget been submitted?
	 */
	public function submitted() {
		$name = $this->name();
		if ($name && $this->request->has($name)) {
			return true;
		}
		return $this->request->isPost();
	}

	/**
	 * Set option for all children
	 *
	 * @param string $name
	 * @param mixed $value
	 * @param boolean $recurse
	 *            Recurse on each child
	 */
	final protected function children_set_option($name, $value = null, $recurse = true): void {
		if (!is_array($this->children)) {
			return;
		}
		foreach ($this->children as $child) {
			/* @var $child Widget */
			$child->setOption($name, $value);
			if ($recurse) {
				$child->children_set_option($name, $value, $recurse);
			}
		}
	}

	/**
	 * Calls a hook on this Widget, and on all children of this widget
	 *
	 * @param mixed $hooks
	 *            String, list of hooks (;-separated), or array of hook names
	 * @return void
	 */
	final public function children_hook($hooks) {
		$arguments = func_get_args();
		array_shift($arguments);
		return $this->children_hook_array($hooks, $arguments);
	}

	/**
	 * Calls a hook on this Widget, and on all children of this widget
	 *
	 * @param mixed $hooks
	 *            String, list of hooks (;-separated), or array of hook names
	 * @return void
	 */
	final public function children_hook_array($hooks, array $arguments): void {
		$this->call_hook_arguments($hooks, $arguments);
		$children = $this->all_children();
		$locale = $this->application->locale;
		foreach ($children as $child) {
			/* @var $child Widget */
			if (!$child instanceof Widget) {
				throw new Exception_Semantics($locale('Child of {0} not a widget, but a {1}', get_class($this), gettype($child)));
			}
			$child->call_hook_arguments($hooks, $arguments);
		}
	}

	/**
	 * Update execution state for all children
	 *
	 * @param int $state
	 * @return void
	 */
	private function _update_child_state(int $state): void {
		foreach ($this->children as $child) {
			$child->_update_child_state($state);
			$child->exec_state = $state;
		}
	}

	/**
	 * Run the ready step for this widget and all children
	 *
	 * @return Widget
	 * @throws Exception_Semantics
	 */
	private function _exec_ready() {
		if ($this->exec_state === self::INITIALIZED) {
			$this->exec_state = self::ready;
			$this->response();
			// Create model for parent object to have something to examine state in
			$model = $this->model();
			$this->object($model);
			$this->defaults();
			$this->initialize();
			// Apply model settings to the children we just created
			$this->children_model();
			$this->children_hook('initialized');
			$this->_update_child_state(self::ready);
		}
		return $this;
	}

	/**
	 * Getter/setter.
	 * When the value matches one of these, do not load it into our model
	 *
	 * @param list $set
	 * @return Widget|list
	 */
	public function load_ignore_values($set = null) {
		if ($set === null) {
			return $this->load_ignore_values;
		}
		$this->load_ignore_values = to_list($set);
		return $this;
	}

	/**
	 * Load a Model from a Request, usually during a submit
	 *
	 * @param $object Model
	 * @return void
	 */
	protected function load(): void {
		$column = $this->column();
		if (!empty($column)) {
			/*
			 * Load request value based on name which may be dynamic based on existing object If no
			 * request value, use existing object value If no object value, use default value
			 */
			$object = $this->object;
			if (!$object instanceof Model) {
				backtrace();
			}
			$input_name = $object->applyMap($this->name());
			if ($this->request_index !== null) {
				$input_name = StringTools::removeSuffix($input_name, '[]');
				if ($this->request->has($input_name, false)) {
					$new_value = $this->request->getArray($input_name);
					$new_value = $this->sanitize($new_value);
					$new_value = avalue($new_value, $this->request_index);
					if ($new_value !== null && $new_value !== '') {
						$this->save_new_value($new_value);
					}
				}
			} elseif ($input_name && $this->request->has($input_name, false)) {
				$new_value = $this->request->get($input_name);
				$new_value = $this->sanitize($new_value);
				if (!in_array($new_value, $this->load_ignore_values, true)) {
					$this->save_new_value($new_value);
				}
			}
		}
		$this->children_load();
	}

	/**
	 * Sanitize a text value, removing any unwanted HTML or entities
	 *
	 * @param string|array|Iterator $value
	 * @return string|array|Iterator
	 */
	public function sanitize($value) {
		return $value;
	}

	/**
	 * Invoke load on all children
	 */
	protected function children_load(): void {
		if (is_array($this->children) && count($this->children) !== 0) {
			foreach ($this->children as $widget) {
				/* @var $widget Widget */
				$widget->object($widget->traverse ? $this->object->get($widget->column()) : $this->object);
				$widget->load();
			}
		}
	}

	private function save_new_value($new_value): void {
		if ($this->optionBool('trim', true) && is_scalar($new_value)) {
			$new_value = trim($new_value);
		}
		$this->_save_default_value($new_value);
		$this->value($new_value);
		$this->call_hook('loaded;model_changed');
		if ($this->object && $this->traverse) {
			$this->object->call_hook('control_loaded', $this);
		}
	}

	/**
	 * Initialize subobjects by traversing the model and initializing the sub-models
	 */
	private function children_model(): void {
		foreach ($this->children as $child) {
			/* @var $child Widget */
			$column = $child->column();
			if ($child->traverse) {
				$child->object = $this->object->get($column);
				if (!$child->object instanceof Model) {
					$child->object = $child->model();
					$this->object->set($column, $child->object);
					$child->defaults();
				}
			} else {
				$child->object = $this->object;
			}
			$child->children_model();
		}
	}

	private function _exec_render() {
		if ($this->exec_state === self::submit) {
			$this->exec_state = self::render;

			// We do this first so anything needed to be computed prior to rendering
			// is generated.
			$output = $this->render();
			$this->_update_child_state(self::render);
			return $this->exec_render = $this->unwrap_all($output);
		}
		return $this->exec_render;
	}

	/**
	 * Get/set the request index
	 *
	 * @param int $set
	 * @return Widget integer
	 */
	final public function request_index($set = null) {
		if ($set !== null) {
			$this->request_index = $set;
			return $this;
		}
		return $this->request_index;
	}

	/**
	 * When Widget::target specified, this is called for Widgets to serve partial content back to
	 * parent control
	 *
	 * @return mixed
	 */
	public function controller() {
		$this->response()->json()->data([
			'status' => false,
			'message' => $this->application->locale->__('{class} does not implement controller method', [
				'class' => get_class($this),
			]),
		]);
		return null;
	}

	/**
	 * Was the execution successful?
	 *
	 * @return boolean
	 */
	final public function status() {
		return $this->has_errors() ? false : true;
	}

	/**
	 * Run the widget on a model and return content
	 *
	 * @param Model $object
	 * @param string $reset
	 * @return mixed|NULL|string
	 */
	final public function execute(Model &$object = null, $reset = false) {
		if (!$this->request) {
			throw new Exception_Semantics('Requires request to be set');
		}
		if ($reset) {
			$this->exec_state = self::INITIALIZED;
			$this->content = $this->content_children = '';
		} elseif (is_string($this->exec_state)) {
			return $this->exec_state;
		}
		// Ensure our response is created
		$this->response();
		if ($object !== null) {
			$this->object($object);
		}
		$this->_exec_ready();
		$widget = $this->_exec_submit();
		$content = $widget ? $widget->_exec_render() : null;

		// Return object
		$object = $this->object;

		return $content;
	}

	/**
	 * Retrieve attributes in this Widget related to data- tags
	 *
	 * @return array
	 */
	public function dataAttributes() {
		return HTML::data_attributes($this->options);
	}

	/**
	 * Retrieve the attributes in this Widget related to INPUT tags
	 *
	 * @param array $types
	 * @return array
	 */
	public function inputAttributes(array $types = []): array {
		return $this->options(HTML::inputAttributeNames($types));
	}

	/**
	 * Utility option getter/setter
	 *
	 * @param string $option
	 * @param mixed $set
	 * @return mixed Widget
	 */
	private function _getset_opt($option, $set = null) {
		if ($set !== null) {
			$this->setOption($option, $set);
			return $this;
		}
		return $this->option($option);
	}

	/**
	 * Get/set this name used to store thie value in the session
	 *
	 * @param string $set
	 * @return Widget string
	 */
	public function session_default($set = null) {
		return $this->_getset_opt('session_default', $set);
	}

	/**
	 * Get/set the help string for this widget
	 *
	 * @param string $set
	 * @return Widget string
	 */
	public function help($set = null, $append = false) {
		if ($set !== null) {
			return $this->setOption('help', ($append ? $this->option('help', '') : '') . $set);
		}
		return $this->option('help');
	}

	/**
	 * Get/set the string used to output an empty value
	 *
	 * @param string $set
	 * @return Widget string
	 */
	public function empty_string($set = null) {
		return $this->_getset_opt('empty_string', $set);
	}

	/**
	 * Get/set the ID string associated with this widget
	 *
	 * @param string $set
	 * @return Widget string
	 */
	public function id($set = null) {
		return $this->_getset_opt('id', $set);
	}

	/**
	 * Get/set the default value associated with this widget
	 *
	 * @param string $set
	 * @return Widget string
	 */
	public function default_value($set = null) {
		return $this->_getset_opt('default', $set);
	}

	/**
	 * Get/set the onchange value for this widget
	 *
	 * @param string $set
	 * @return Widget string
	 */
	public function change($set = null) {
		return $this->_getset_opt('onchange', $set);
	}

	/**
	 * Easy way to combine attributes for a control.
	 *
	 * In the $add array, you can specify:
	 *
	 * array("+name" => "value")
	 *
	 * To append a value to an existing one. You can use:
	 *
	 * array("*name" => "value")
	 *
	 * To set a value ONLY if it doesn't exist yet, and:
	 *
	 * array('name' => 'value')
	 *
	 * To set a value always.
	 *
	 * @param array $attributes
	 *            Attributes to manipulate
	 * @param array $add
	 *            Set of name value pairs on how to manipulate the attributes
	 */
	public static function attributes_inherit(array $attributes, array $inherit) {
		foreach ($inherit as $k => $v) {
			if (is_string($k) && str_starts_with($k, '+')) {
				$k = substr($k, 1);
				$value = $attributes[$k] ?? null;
				if (empty($value)) {
					$attributes[$k] = $v;
				} elseif (is_string($value)) {
					$attributes[$k] = CSS::addClass($value, $v);
				} elseif (is_array($value)) {
					$attributes[$k][] = $value;
				}

				continue;
			}
			$attributes[$k] = $v;
		}
		return $attributes;
	}

	/**
	 * List of attributes associate with HTML tags
	 *
	 * @param string $type
	 * @return array
	 */
	private static function _attributesNames(string $type): array {
		static $types = [
			'default' => 'id;class;style',
			'input' => 'id;class;style;title;placeholder;onclick;ondblclick;onmousedown;onmouseup;onmouseover;onmousemove;onmouseout;onkeypress;onkeydown;onkeyup;type;name;value;checked;disabled;readonly;size;maxlength;src;alt;usemap;ismap;tabindex;accesskey;onfocus;onblur;onselect;onchange;accept',
			'textarea' => 'id;class;style;title;placeholder;onclick;ondblclick;onmousedown;onmouseup;onmouseover;onmousemove;onmouseout;onkeypress;onkeydown;onkeyup;type;name;checked;disabled;readonly;size;maxlength;src;alt;tabindex;accesskey;onfocus;onblur;onselect;onchange;accept',
		];
		return toList($types[$type] ?? $types['default']);
	}

	/**
	 * Retrieve attributes associated with this widget (including those from "type")
	 *
	 * @param array $inherit
	 * @param string $type
	 */
	public function attributes(array $inherit = [], string $type = ''): array {
		$attributes = $this->options(self::_attributesNames($type)) + $this->dataAttributes();
		return self::attributes_inherit($attributes, $inherit);
	}

	/**
	 * @return bool
	 */
	private function empty_condition_apply(): bool {
		if (!$this->hasOption('empty_condition')) {
			return false;
		}
		$this->setOption('condition', $this->option('empty_condition'));
		return true;
	}

	/**
	 * @return bool
	 */
	private function validate_empty_condition(): bool {
		$value = $this->value();
		if (empty($value)) {
			return true;
		}
		return true;
	}

	/**
	 * @param mixed|null $default
	 * @param string|null $column
	 * @return mixed
	 */
	protected function _default_value(mixed $default = null, string $column = null): mixed {
		$column = ($column === null) ? $this->column() : $column;
		$sess_variable_name = $this->option('session_default');
		$default = $default !== null ? $default : $this->default_value();
		if (!$sess_variable_name) {
			return $default;
		}
		if ($sess_variable_name === true) {
			$sess_variable_name = $this->option('session_default_prefix', '') . $column;
		}
		$session = $this->request->session();
		if (!$session) {
			return $default;
		}
		return $session->get($sess_variable_name, $default);
	}

	protected function _save_default_value($value, $column = null): void {
		$sess_variable_name = $this->option('session_default');
		if (!$sess_variable_name) {
			return;
		}
		$column = ($column === null) ? $this->column() : $column;
		if ($sess_variable_name === true) {
			$sess_variable_name = $this->option('session_default_prefix', '') . $column;
		}
		$session = $this->request->session();
		if (!$session) {
			return;
		}
		$session->set($sess_variable_name, $value);
	}

	/**
	 * When executing child, traverse the parent model
	 *
	 * @var boolean
	 */
	public function traverse(): bool {
		return $this->traverse;
	}

	/**
	 * When executing child, traverse the parent model
	 *
	 * @var boolean
	 */
	public function setTraverse(bool $set): self {
		$this->traverse = toBool($set);
		return $this;
	}

	/**
	 * Returns the model for this widget.
	 * If no model returned, then this widget must be invoked with an existing model.
	 *
	 * @return Model
	 */
	protected function model(): Model {
		$model = $this->call_hook('model_new', $this);
		if (!$model instanceof Model) {
			$model = $this->application->factory(__NAMESPACE__ . '\\' . 'ORM', $this->application);
			$model = $this->call_hook('model_alter', $model);
		}
		return $model;
	}

	/**
	 *
	 * @param Model $object
	 * @return self
	 */
	public function ready(Model $object): self {
		$this->object = $object;
		$this->_exec_ready();
		return $this;
	}

	/**
	 * Initialize widgets before any other execution function
	 *
	 * @return void
	 */
	protected function initialize(): void {
		$response = $this->response();
		if (!$response) {
			throw new Exception_Semantics('Widget {class} must be set up with a response prior to initialization', [
				'class' => get_class($this),
			]);
		}
		if (!$this->hasOption('column', true)) {
			$class = get_class($this);
			$column = strtolower(strtr($class, '_', '-')) . '-' . $this->response()->id_counter();
			$this->column($column);
			$this->application->logger->notice('{class} was given a default column name "{column}"', [
				'class' => $class,
				'column' => $column,
			]);
		}
		if (!$this->hasOption('name')) {
			$this->name($this->column());
		}
		if (!$this->hasOption('id')) {
			$this->id($this->name());
		}
		if (!$this->_initialize) {
			$this->childrenInitialize();
		}
		if ($this->hasOption('theme_variables')) {
			$this->theme_variables += $this->optionArray('theme_variables');
		}
		$this->_initialize = true;
	}

	/**
	 * Initialize all of my children, if any
	 */
	protected function childrenInitialize(): void {
		do {
			$n_children = count($this->children);
			foreach ($this->children as $widget) {
				if (!$widget->_initialize) {
					/* @var $w Widget */
					$widget->initialize();
					$widget->_initialize = true;
				}
			}
		} while ($n_children !== count($this->children));
	}

	/**
	 * Initialize a form which has not been submitted.
	 *
	 * Set default values in object, if needed. Uses the ->inited() method of $this->object to
	 * determine if we are dealing with a
	 * pre-loaded (initialized) object, or a blank model.
	 */
	protected function defaults(): void {
		$this->childrenDefaults();
		if (toBool($this->options['disabled'] ?? false)) {
			return;
		}
		$this->value($this->_default_value(null));
	}

	/**
	 * Run defaults on children
	 */
	protected function childrenDefaults(): void {
		$children = $this->children();
		foreach ($children as $child) {
			$child->object = $child->traverse ? $this->object->get($child->column()) : $this->object;
			$child->defaults();
		}
	}

	/**
	 * Default child CSS class
	 *
	 * @return string
	 */
	protected function childCSSClass(): string {
		return strtr(strtolower(get_class($this)), '_', '-');
	}

	/**
	 * Render the children of this widget
	 *
	 * @return string
	 */
	protected function renderChildren(): string {
		if ($this->content_children !== null) {
			return $this->content_children;
		}
		$this->content_children = '';
		if (count($this->children) === 0) {
			return $this->content_children;
		}
		$content = $suffix = [];
		foreach ($this->children as $key => $child) {
			/* @var $child Widget */
			$child->object = $child->traverse ? $this->object->get($child->column()) : $this->object;
			$child->content = $child->render();
			if (empty($child->content)) {
				continue;
			}
			if ($child->is_visible()) {
				$child_tag = $this->option('child_tag', 'div');
				if ($child_tag) {
					$child_attributes = CSS::addClass($this->option('child_attributes', '.child'), [
						$child->childCSSClass(),
						$child->contextClass(),
					]);
					$content[] = HTML::tag($child_tag, $child_attributes, $child->content);
				} else {
					$content[] = $child->content;
				}
			} else {
				$suffix[] = $child->content;
			}
		}
		$this->content_children = HTML::tag($this->option('children_tag', 'div'), $this->option('children_attributes', '.children'), implode('', $content)) . implode('', $suffix);
		return $this->content_children;
	}

	/**
	 * Set theme variables for this widget
	 *
	 * @param array $set
	 * @param boolean $append
	 * @return self
	 */
	public function setThemeVariables(array $set, bool $append = true): self {
		$this->theme_variables = $append ? $set : $set + $this->theme_variables;
		return $this;
	}

	/**
	 * Return array of variables to pass to the theme
	 *
	 * @return array
	 */
	public function themeVariables(): array {
		return $this->application->variables() + [
			'request' => $this->request(),
			'response' => $this->response(),
			'widget' => $this,
			'input_attributes' => $input_attributes = $this->inputAttributes(),
			'data_attributes' => $data_attributes = $this->dataAttributes(),
			'attributes' => $input_attributes + $data_attributes,
			'required' => $this->required(),
			'name' => $this->name(),
			'column' => $this->column(),
			'label' => $this->label(),
			'id' => $this->id(),
			'context_class' => $this->contextClass(),
			'empty_string' => $this->empty_string(),
			'show_size' => $this->showSize(),
			'object' => $this->object,
			'model' => $this->object,
			'value' => $this->value(),
			'parent' => $this->parent,
			'children' => $this->children(),
			'all_children' => $this->all_children(false),
			'errors' => $this->errors(),
			'messages' => $this->messages(),
			'content_children' => $this->content_children,
		] + $this->theme_variables + $this->options;
	}

	/**
	 * Getter for object
	 *
	 * @return Model
	 */
	public function object(): Model {
		return $this->object;
	}

	/**
	 * Setter for object
	 *
	 * @param Model $set
	 * @return $this
	 */
	public function setObject(Model $set): self {
		$this->object = $set;
		$this->call_hook('object', $set);
		return $this;
	}

	/**
	 * Render this widget
	 *
	 * @return string
	 */
	public function render(): string {
		if ($this->content !== null) {
			return $this->content;
		}
		$this->children_hook('render');
		if ($this->render_children) {
			$this->renderChildren();
		}
		$this->content = '';
		if ($this->theme) {
			$this->content .= $this->application->theme($this->theme, $this->themeVariables(), [
				'first' => true,
			]);
		}
		$this->content .= $this->content_children;
		$this->content = $this->render_finish($this->content);
		$this->content = $this->call_hook_arguments('render_alter', [
			$this->content,
		], $this->content);
		return $this->content;
	}

	/**
	 * Render structure
	 *
	 * @return array
	 */
	public function render_structure(): array {
		$children_structure = [];
		foreach ($this->children as $child) {
			$children_structure = array_merge($children_structure, $child->render_structure());
		}
		$result[$this->name() . ':' . get_class($this)] = [
			'children' => $children_structure,
			'render_children' => $this->render_children,
		];
		return $result;
	}

	/**
	 * Get/set the URL to redirect to upon submit
	 *
	 * @return string
	 */
	public function submitRedirect(): string {
		return $this->option('submit_redirect', '');
	}

	/**
	 * Set the URL to redirect to upon submit
	 *
	 * @param string $url
	 * @param string|null $message
	 * @return $this
	 */
	public function setSubmitRedirect(string $url, string $message = null): self {
		if ($message !== null) {
			$this->setOption('submit_redirect_message', $message);
		}
		return $this->setOption('submit_redirect', $url);
	}

	/**
	 * Submit children and do final storage/action for form
	 *
	 * Return true to continue and render, false to stop processing now and render nothing
	 *
	 * @return boolean Do render
	 */
	public function submit(): bool {
		if (!$this->submit_children()) {
			return false;
		}
		if ($this->parent() === null) {
			$this->object->store();
			return $this->submit_redirect();
		}
		return true;
	}

	/**
	 * Execute the submit method for each child
	 *
	 * @return boolean
	 */
	protected function submit_children() {
		$result = true; // Continue;
		if (is_array($this->children) && count($this->children) > 0) {
			foreach ($this->children as $child) {
				$child->object = $child->traverse ? $this->object->get($child->column()) : $this->object;
				if ($child->submit() === false && $child->required()) {
					if (!$child->has_errors()) {
						$child->error(__('{label} failed to submit.'), $child->column());
					}
					$result = false;
				}
			}
		}
		return $result;
	}

	/**
	 * Getter/Setter for submit_message - displayed on successful submit
	 *
	 * @param string $set
	 * @return Widget string
	 */
	public function submit_message($set = null, $append = false) {
		return $set === null ? $this->option('submit_message') : $this->setOption('submit_message', $append ? $this->submit_message() . ' ' . $set : $set);
	}

	/**
	 * Getter/Setter for submit_url - URL redirected to upon any submit
	 *
	 * @param string $set
	 * @return Widget string
	 * @todo rename submit_redirect to submit_url at some point.
	 *
	 */
	public function submit_url($set = null) {
		return $set === null ? $this->option('submit_redirect') : $this->setOption('submit_redirect', $set);
	}

	/**
	 * @return string
	 */
	public function submitUrl(): string {
		return $this->option('submit_redirect') ?? '';
	}

	/**
	 * @param string $set
	 * @return $this
	 */
	public function setSubmitUrl(string $set): self {
		return $this->setOption('submit_redirect', $set);
	}

	/**
	 * Getter/Setter for submit_url_default - URL redirected to upon submit when no ref is found in
	 * form/query string
	 *
	 * @param string $set
	 * @return Widget string
	 */
	public function submit_url_default($set = null) {
		return $set === null ? $this->option('submit_url_default') : $this->setOption('submit_url_default', $set);
	}

	/**
	 * Setter for submit_url_default - URL redirected to upon submit when no ref is found in
	 * form/query string
	 *
	 * @param string $set
	 */
	public function setSubmitIUrlDefault(string $set) {
		return $this->setOption('submit_url_default', $set);
	}

	/**
	 * Getter for submit_url_default - URL redirected to upon submit when no ref is found in
	 * form/query string
	 *
	 * @return string
	 */
	public function submitUrlDefault(): string {
		return $this->option('submit_url_default') ?? '';
	}

	/**
	 * Handle final submission of form and redirect/respond
	 *
	 * @return boolean
	 */
	protected function submit_redirect(): bool {
		$response = $this->response();
		if ($this->parent && $this->parent->submit_url() !== null) {
			// Continue
			return true;
		}
		$ref = $this->request->get_not_empty('ref');
		$submit_url = $this->submitUrl();
		$submit_url_default = $this->submitUrlDefault();
		if ($this->optionBool('submit_skip_ref')) {
			$submit_url = $ref ? URL::queryFormat($submit_url, [
				'ref' => $ref,
			]) : $submit_url;
		}
		$vars = ArrayTools::prefixKeys($this->object->variables(), 'object.') + ArrayTools::prefixKeys($this->request->variables(), 'request.');
		$url = null;
		if ($submit_url) {
			$submit_url = map($submit_url, $vars);
		} elseif ($ref && !URL::is($ref)) {
			$submit_url = map($ref, $vars);
		} elseif ($submit_url_default) {
			$submit_url = map($submit_url_default, $vars);
		}
		if ($submit_url) {
			$url = URL::queryFormat($submit_url, 'ref', $this->request->get('ref', $this->request->url()));
		}
		$message = map($this->firstOption(['submit_message', 'submit_redirect_message', 'onstore_message'], ''), $vars);

		$status = $this->status();
		if (!$status) {
			$message = array_values($this->errors());
		}
		if ($this->preferJSON()) {
			$json = [
				'status' => $status,
				'message' => $message,
				'redirect' => $url,
			];
			if ($this->hasOption('submit_theme', true)) {
				$json['content'] = $this->application->theme($this->option('submit_theme'), $this->themeVariables(), [
					'first' => true,
				]);
			}
			$json += $response->html()->to_json();
			$response->json()->data($json);
			// Stop processing
			return false;
		}
		if (!$submit_url) {
			if ($message) {
				$response->redirect()->message($message);
			}
			// Continue processing
			return true;
		}

		throw new Exception_Redirect($url, $message);
	}

	/**
	 * Decorate render with final markup, wrap
	 *
	 * @param string $content
	 * @return string
	 */
	protected function render_finish(string $content): string {
		$content = $this->renderBehavior($content);
		if ($this->wrapped) {
			return $content;
		}
		$this->wrapped = true;
		if ($this->optionBool('debug')) {
			dump($this->object);
		}
		$prefix = $this->prefix();
		$suffix = $this->suffix();

		$this->setPrefix('')->setSuffix('');
		if ($this->optionBool('wrap_map_disabled')) {
			return $prefix . $content . $suffix;
		}
		return $this->unwrap_all($this->object->applyMap($prefix) . $content . $this->object->applyMap($suffix));
	}

	/**
	 * Alter this query to work with the widgets
	 *
	 * @param Database_Query_Select $query
	 * @return void
	 */
	public function query_alter(Database_Query_Select $query): void {
		$this->children_hook('query', $query);
	}

	/**
	 * Specify a behavior attached to this widget
	 *
	 * @param string $type
	 * @param array $options
	 * @return Widget
	 */
	public function behavior(string $type, array $options = []): self {
		$this->behaviors[] = [
			$type,
			$options,
		];
		return $this;
	}

	/**
	 * Render behavior items for this widget
	 *
	 * @param string $content
	 * @return string
	 */
	private function renderBehavior(string $content): string {
		foreach ($this->behaviors as $item) {
			[$theme, $options] = $item;
			$content .= $this->application->theme($theme, $options + [
				'widget' => $this,
				'object' => $this->object,
				'content' => $content,
				'request' => $this->request,
				'response' => $this->response(),
			]);
		}
		return $content;
	}

	/**
	 * Return the jQuery expression to determine the value of this widget
	 */
	public function jquery_value_expression() {
		if ($this->hasOption('jquery_value_expression')) {
			return $this->option('jquery_value_expression');
		}
		if ($this->hasOption('value_expression')) {
			return $this->option('value_expression');
		}

		$id = $this->id();
		if (!$id) {
			return null;
		}
		return "\$(\"#$id\").val()";
	}

	public function jquery_value_selected_expression(): string {
		if ($this->hasOption('jquery_value_selected_expression')) {
			return $this->option('jquery_value_selected_expression');
		}
		if ($this->hasOption('value_selected_expression')) {
			return $this->option('value_selected_expression');
		}

		$id = $this->id();
		if (!$id) {
			return '';
		}
		return "\$(\"#$id\")";
	}

	/**
	 * Return the jQuery expression to determine the items to be watched for a widget (related to
	 * behavior)
	 */
	public function jquery_target_expression() {
		if ($this->hasOption('jquery_target_expression')) {
			return $this->option('jquery_target_expression');
		}
		$id = $this->id();
		if (!$id) {
			return null;
		}
		return "\$(\"#$id\")";
	}

	/**
	 * Does the current request prefer a JSON response?
	 *
	 * @return boolean
	 */
	public function preferJSON(): bool {
		return $this->request->preferJSON();
	}
}
