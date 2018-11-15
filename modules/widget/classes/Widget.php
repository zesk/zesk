<?php

/**
 *
 * @package zesk
 * @subpackage widget
 * @author kent
 * @copyright Copyright &copy; 2017, Market Acumen, Inc.
 */
namespace zesk;

/**
 *
 * @author kent
 *
 */
class Widget extends Hookable {
    /**
     *
     * @var string
     */
    const option_minimum_glyph_length = 'minimum_glyph_length';

    /**
     *
     * @var string
     */
    const option_maximum_glyph_length = 'maximum_glyph_length';

    /**
     * List of widget => error or errors
     */
    protected $errors = array();

    /**
     * List of widget => messages
     */
    protected $messages = array();

    /**
     * Parent widget, if any
     *
     * @var Widget
     */
    protected $parent = null;

    /**
     * Request associated with this widget
     *
     * @var Request
     */
    protected $request = null;

    /**
     * For multi-value forms, this index tells the widget which value to check in the request
     *
     * @var mixed
     */
    protected $request_index = null;

    /**
     * Respose associated with this widget. Inherited by children, stored only at root Widget.
     *
     * @var Response
     */
    private $response = null;

    /**
     * Current locale for this widget
     *
     * @var Locale
     */
    protected $locale = null;

    /**
     * Theme hook to use for output
     *
     * @var string
     */
    protected $theme = null;

    /**
     * Variables to pass, automatically, to the theme
     *
     * @var array
     */
    protected $theme_variables = array();

    /**
     * Class for context of this widget (typically, the outside HTML tag)
     *
     * @var string
     */
    private $context_class = null;

    /**
     * List of tag pairs to wrap this tag with, popped like a stack
     *
     * @var array
     */
    protected $wraps = array();

    /**
     * Whether this has been wrapped
     *
     * @var boolean
     */
    protected $wrapped = false;

    /**
     * List of behaviors attached to this widget
     *
     * @var boolean
     */
    private $behaviors = array();

    /**
     * Set to true in subclasses to render children and append to main render
     *
     * @var boolean
     */
    protected $render_children = false;

    /**
     * List of children widgets
     *
     * @var array of column => Widget
     */
    public $children = array();

    /**
     * Whether this widget has been initialized
     *
     * @var boolean
     */
    public $_initialize = false;

    /**
     * Rendered content
     *
     * @var string
     */
    public $content = null;

    /**
     * String to output child nodes, set to blank to skip output
     *
     * @var string
     */
    public $content_children = null;

    /**
     * Widget column represents a particular class
     *
     * @var string
     */
    protected $class = null;

    /**
     * Execution state
     *
     * @var mixed
     */
    protected $exec_state = null;

    /**
     * When executing child, traverse the parent model
     *
     * @var boolean
     */
    protected $traverse = null;

    /**
     * Rename traversable children of so input names are guaranteed unique.
     * Generally should be handled by developer.
     *
     * @var boolean
     */
    protected $traverse_rename = false;

    /**
     * Hierarchy of classes up through Widget
     *
     * @var array
     */
    protected $hierarchy = array();

    /**
     * What we're operating on
     *
     * @var Model
     */
    protected $object = null;

    /**
     *
     * @var integer
     */
    const ready = 1;

    /**
     *
     * @var integer
     */
    const submit = 2;

    /**
     *
     * @var integer
     */
    const render = 3;

    /**
     * If request contains these values (strict), then ignore them
     *
     * @var array
     */
    protected $load_ignore_values = array(
        null,
    );

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
    public function __construct(Application $application, array $options = null) {
        $this->request = $application->request();

        if (!is_array($options)) {
            $options = array();
        }
        parent::__construct($application, $options);
        $this->inherit_global_options();

        $this->options += array(
            "column" => avalue($this->options, 'id'),
        );
        $this->options += array(
            'name' => avalue($this->options, 'column'),
        );

        if ($this->has_option("locale")) {
            $this->locale($this->application->locale_registry($this->option("locale")));
        }
        if (!$this->locale) {
            $this->locale = $application->locale;
        }
        $this->hierarchy = $application->classes->hierarchy($this, __CLASS__);
        if ($this->theme === null) {
            $this->theme = ArrayTools::change_value_case(tr($this->hierarchy, array(
                "\\" => "/",
                "_" => "/",
            )));
        }
        if ($this->context_class() === null) {
            $cl = get_class($this);
            $cl = StringTools::rright($cl, "\\", $cl);
            $this->context_class(strtr(strtolower($cl), '_', '-'));
        }
        $this->call_hook("construct");
    }

    /**
     * Retrieve the default theme for this widget (includes reverse hierarchy of parents)
     */
    protected function default_theme() {
        return ArrayTools::change_value_case(tr($this->hierarchy, array(
            "_" => "/",
        )));
    }

    /**
     *
     * @param list|string $theme
     * @return Widget
     */
    public function set_theme($theme) {
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
        return $set === null ? $this->option('title') : $this->set_option('title', $set);
    }

    /**
     * Class applied to outer HTML tag for this widget
     *
     * Can pass in CSS-style class (.foo) or class name (foo)
     *
     * @return string
     */
    public function context_class($set = null, $add = true) {
        if ($set !== null) {
            $set = trim($set, ".");
            return $this->set_option('context_class', $add ? CSS::add_class($this->context_class(), $set) : $set);
        }
        return $this->option('context_class');
    }

    /**
     * Retrieve the class object for this widget
     *
     * @return Class_ORM
     */
    public function class_orm() {
        return $this->application->class_orm_registry($this->class);
    }

    /**
     * Getter/setter for the ORM subclass associated with this widget
     *
     * @param unknown $set
     */
    public function orm_class($set = null) {
        if ($set !== null) {
            $this->class = $set;
            return $this;
        }
        return $this->class;
    }

    /**
     *
     * @param unknown $class
     * @return Widget
     */
    public function add_class($class) {
        // Some widgets have protected variable called class - always update the options here
        $this->options = HTML::add_class($this->options, $class);
        return $this;
    }

    public function remove_class($class) {
        // Some widgets have protected variable called class - always update the options here
        $this->options = HTML::remove_class($this->options, $class);
        return $this;
    }

    public function traverse_rename($set = null) {
        if ($set === null) {
            return $this->traverse_rename;
        }
        $this->traverse_rename = to_bool($set);
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
                    $w->name($this->name() . "-" . $w->name());
                }
                $this->child($w);
            }
            return $this;
        }
        return $this->children;
    }

    public function remove_child($name) {
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
        return null;
    }

    /**
     * Retrieve all children, indexed by input names (name)
     */
    public function all_children($include_this = false) {
        $result = $include_this ? array(
            $this,
        ) : array();
        foreach ($this->children as $child) {
            $result = array_merge($result, $child->all_children(true));
        }
        return $result;
    }

    /**
     * Find a child by name (searches grandchildren as well)
     *
     * $control = $widget->child("LoginEmail");
     *
     * Set a child by name:
     * <code>
     * $widget->child("LoginEmail", $email_widget);
     * </code>
     * Set a child:
     * <code>
     * $widget->child($email_widget);
     * </code>
     * Set a child first in the list:
     * <code>
     * $widget->child($email_widget, "first");
     * </code>
     * Set many children:
     * <code>
     * $widget->child(array("LoginEmail" => $login_email, "LoginPassword" =>
     * </code>
     *
     *
     * @param unknown $name
     * @param unknown $widget
     * @return Widget
     */
    public function child($name, $widget = null) {
        if (is_string($name)) {
            if ($widget instanceof Widget) {
                $this->_child($name, $widget);
                return $this;
            }
            if ($widget === null) {
                if (!is_array($this->children)) {
                    return null;
                }
                $result = avalue($this->children, $name);
                if ($result === null) {
                    foreach ($this->children as $child) {
                        $result = $child->child($name);
                        if ($result instanceof Widget) {
                            return $result;
                        }
                    }
                }
                return $result;
            }

            throw new Exception_Semantics("{class}::child({name}, {widget}) invalid parameters", array(
                "class" => get_class($this),
                "name" => _dump($name),
                "widget" => _dump($widget),
            ));
        }
        if ($name instanceof Widget) {
            /* @var $name Widget */
            $this->_child($name->column(), $name, $widget === 'first');
            return $this;
        }
        if (is_array($name)) {
            $this->children($name);
            return $this;
        }

        throw new Exception_Semantics("{class}::child({name}, {widget}) invalid parameters", array(
            "class" => get_class($this),
            "name" => _dump($name),
            "widget" => _dump($widget),
        ));
    }

    private function _child($id, Widget $child, $first = false) {
        $child->parent = $this;
        if ($first) {
            $this->children = array_merge(array(
                $id => $child,
            ), $this->children);
        } else {
            $this->children[$id] = $child;
        }
        $this->call_hook_arguments("child", $child, $id);
        if ($this->_initialize) {
            $child->initialize();
        }
    }

    public function wrap($tag = null, $mixed = null, $prefix = "", $suffix = "") {
        $args = func_get_args();
        if (count($args) === 0) {
            return $this->wraps;
        }
        $this->wraps[] = array(
            $tag,
            HTML::to_attributes($mixed),
            $prefix,
            $suffix,
        );
        return $this;
    }

    /**
     * Remove all wraps from this Widget
     *
     * @return Widget
     */
    public function nowrap() {
        $this->wraps = array();
        return $this;
    }

    /**
     * Unwrap a set of tags
     *
     * @param unknown $content
     */
    private function unwrap($content = null) {
        if (count($this->wraps) === 0) {
            throw new Exception_Semantics(get_class($this) . "::unwrap: Nothing more to unwrap");
        }
        $object = $this->object;
        list($tag, $mixed, $prefix, $suffix) = array_shift($this->wraps);
        return HTML::tag($object->apply_map($tag), $object->apply_map($mixed), $prefix . $content . $suffix);
    }

    /**
     * Unwrap all wrapped items and return new markup
     *
     * @param string $content
     * @return string
     */
    protected function unwrap_all($content = "") {
        while (count($this->wraps) > 0) {
            $content = $this->unwrap($content);
        }
        return $content;
    }

    /**
     * Inherit options
     *
     * @param $options unknown_type
     * @param $class unknown_type
     */
    public static function inherit_options(Application $application, $options, $class) {
        if (!is_array($options)) {
            $options = array();
        }
        return $options + self::default_options($application, $class);
    }

    /**
     * Create an object in the application context
     *
     * @deprecated 2017-12
     * @param string $class
     * @param mixed $mixed
     * @param array $options
     */
    public function object_factory($class, $mixed = null, array $options = array()) {
        $this->application->deprecated();
        return $this->model_factory($class, $mixed, $options);
    }

    /**
     * Create a model in the application context
     *
     * @param string $class
     * @param mixed $mixed
     * @param array $options
     */
    public function model_factory($class, $mixed = null, array $options = array()) {
        return $this->application->model_factory($class, $mixed, $options);
    }

    /**
     * Create a widget, tie it to the same response as this widget
     *
     * @param string $class
     * @param array $options
     * @throws Exception_Semantics
     * @return Widget
     */
    public function widget_factory($class, array $options = null) {
        $widget = self::factory($this->application, $class, $options);
        $response = $this->response();
        if ($response) {
            $widget->response($response);
        }
        return $widget;
    }

    /**
     * Create a widget
     *
     * @param $class string
     * @param $options array
     *        	Optional added options for this widget
     *
     * @return Widget
     */
    public static function factory(Application $application, $class, $options = null) {
        $args = array(
            $application,
            $options,
        );
        $widget = null;

        try {
            $widget = $application->factory_arguments($class, $args);
        } catch (Exception_Class_NotFound $e) {
            if (strpos($class, "\\") === false && class_exists("zesk\\$class")) {
                $widget = $application->factory_arguments("zesk\\" . $class, $args);
                if ($widget) {
                    $application->deprecated("{method} called with unprefixed class {class}", array(
                        "method" => __METHOD__,
                        "class" => $class,
                    ));
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
     *
     * @param $set Application
     *        	to set
     * @return \zesk\Application
     */
    public function application(Application $set = null) {
        if ($set !== null) {
            $this->application = $set;
            return $this;
        }
        return $this->application;
    }

    /**
     * Request associated with this widget
     *
     * @param $set \zesk\Request
     *        	to set
     * @return \zesk\Request
     */
    public function request(Request $set = null) {
        if ($set !== null) {
            $this->request = $set;
            return $this;
        }
        return $this->request;
    }

    /**
     * Response associated with this widget. NOT created if not set.
     *
     * @param $set Response to set
     * @return \zesk\Response
     */
    public function response(Response $set = null) {
        if ($set !== null) {
            $this->response = $set;
            return $this;
        }
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
     * Retrieve the user, if any, associated with permissions for this control.
     *
     * @return \zesk\User
     */
    public function user($require = true) {
        return $this->application()->user($this->request, $require);
    }

    /**
     * Retrieve the account, if any, associated with permissions for this control.
     *
     * @return \zesk\Account
     */
    public function account() {
        return $this->application->model_singleton("zesk\\Account");
    }

    /**
     * Retrieve the session, if any, associated with permissions for this control.
     *
     * @return \zesk\Interface_Session
     */
    public function session($require = true) {
        return $this->application()->session($this->request, $require);
    }

    /**
     * Equivalent of $this->user()->can($noun, $action, $object), but handles the case when no user
     * exists.
     * (Fails.)
     *
     * @param $noun mixed
     *        	String or object
     * @param $action string
     *        	What you want to do to the object
     * @param $object mixed
     *        	Optional target
     */
    public function user_can($action, Model $object = null) {
        $user = $this->user();
        if (!$user instanceof User) {
            return false;
        }
        return $user->can($action, $object);
    }

    /**
     * Set/get file upload flag
     *
     * @param boolean $set
     */
    public function upload($set = null) {
        if (is_bool($set)) {
            $this->options['upload'] = $set;
            if ($this->parent) {
                $this->parent->upload($set);
            }
            return $this;
        }
        return $this->option_bool('upload', $this->option_bool('is_upload'));
    }

    /**
     * Do not output this widget, save the rendered form and use it as a token in later widgets.
     *
     * @param boolean|string $set
     *        	Boolean turns it on/off, string turns it on and uses alternate token name
     * @return mixed Widget
     */
    public function save_render($set = null) {
        if ($set) {
            $this->set_option('widget_save_result', $set);
            return $this;
        }
        return $this->option('widget_save_result');
    }

    /**
     * Parent Widget
     *
     * @return Widget
     */
    public function parent(Widget $set = null) {
        if ($set !== null) {
            $this->parent = $set;
            $this->application($set->application());
            $this->request($set->request());
            $this->response($set->response());
            return $this;
        }
        return $this->parent;
    }

    /**
     * Topmost widget
     *
     * @return Widget
     */
    public function top() {
        $depth = 0;
        $next = $this;
        do {
            $parent = $next;
            $next = $parent->parent();
            if (++$depth > 50) {
                throw new Exception_Semantics("Widgets are in a parent infinite loop - {class} {name}", array(
                    "class" => get_class($this),
                    "name" => $this->name(),
                ));
            }
        } while ($next !== null);
        return $parent;
    }

    /**
     * Get/set the value associated with this widget
     *
     * @param mixed $set
     * @return mixed|Widget
     */
    public function value($set = null) {
        if (!$this->object instanceof Model) {
            throw new Exception_Semantics("Retrieving value when object is not initialized {class} {name}", array(
                "class" => get_class($this),
                "name" => $this->name(),
            ));
        }
        if ($set !== null) {
            $this->object->set($this->column(), $set);
            return $this;
        }
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
     * Return a JSON response via the response. Modifies response content type.
     *
     * @param mixed $set
     * @return self
     */
    public function json($set) {
        $this->response()->json()->data($set);
        return $this;
    }

    /**
     * Set names for this widget
     *
     * @param string $column
     * @param string $label
     * @param string $name
     */
    public function names($column, $label = true, $name = false) {
        $this->set_option("column", $column);
        if ($label === false) {
            $this->set_option("nolabel", true);
        } elseif ($label === true) {
            $this->set_option("label", ucfirst($column));
        } else {
            $this->set_option("label", $label);
        }
        $name = !empty($name) ? $name : $column;
        $this->set_option("name", $name);
        $this->set_option("id", $name);
        return $this;
    }

    /**
     * Set/get widget required
     *
     * @return boolean|Widget
     */
    public function required($set = null) {
        if (is_bool($set)) {
            $this->set_option('required', $set);
            return $this;
        }
        return $this->option_bool("required", false);
    }

    /**
     * Set/get widget required error message
     *
     * @return boolean
     */
    public function required_error($set = null) {
        if ($set !== null) {
            return $this->set_option('error_required', $set);
        }
        return $this->option_bool("error_required", false);
    }

    /**
     * Get or set the column name
     *
     * @param $set Value
     *        	to set it to
     * @return mixed Column name for get, Widget for set
     */
    public function column($set = null) {
        if ($set !== null) {
            $this->set_option('column', $set);
            return $this;
        }
        return $this->option("column");
    }

    /**
     * Get or set the input name
     *
     * @param $set Value
     *        	to set it to
     * @return mixed Input name for get, Widget for set
     */
    public function name($set = null) {
        if ($set !== null) {
            $this->set_option('name', $set);
            return $this;
        }
        return $this->option('name');
    }

    /**
     * Get/set the nolabel option
     *
     * @param boolean $set
     * @return Widget|boolean
     */
    public function nolabel($set = null) {
        return ($set !== null) ? $this->set_option('nolabel', $set) : $this->option_bool('nolabel');
    }

    /**
     * Get/set the label
     *
     * @param string $set
     * @return Widget|string
     */
    public function label($set = null) {
        return ($set !== null) ? $this->set_option('label', $set) : $this->option("label");
    }

    /**
     * Get/set/append the suffix
     *
     * @param string $data
     * @param boolean $append
     * @return Widget|string
     */
    public function suffix($data = null, $append = false) {
        if ($data !== null) {
            if ($append) {
                return $this->set_option('suffix', $this->option('suffix') . $data);
            }
            return $this->set_option('suffix', $data);
        }
        return $this->option('suffix');
    }

    /**
     * Get/set/append the prefix
     *
     * @param string $data
     * @param boolean $append
     * @return Widget string
     */
    public function prefix($data = null, $append = false) {
        if ($data !== null) {
            if ($append) {
                return $this->set_option('prefix', $this->option('prefix') . $data);
            }
            return $this->set_option('prefix', $data);
        }
        return $this->option('prefix');
    }

    /**
     * Get/set the form name, finds it from the parent if exists
     *
     * @param string $data
     * @param boolean $append
     * @return Widget string
     */
    public function form_name($set = null) {
        if ($this->parent) {
            return $this->parent->form_name($set);
        }
        if ($set !== null) {
            $this->set_option('form_name', $set);
            return $this;
        }
        return $this->option('form_name');
    }

    /**
     * Get/set the form ID
     *
     * @param string $data
     * @param boolean $append
     * @return Widget string
     */
    public function form_id($set = null) {
        if ($this->parent) {
            return $this->parent->form_id($set);
        }
        if ($set !== null) {
            $this->set_option('form_id', $set);
            return $this;
        }
        return $this->option('form_id');
    }

    /**
     * Retrieve the language of this widget
     *
     * @return string
     */
    public function language() {
        return $this->locale->language();
    }

    /**
     * Get/set the locale of this widget
     *
     * @return Locale|self
     */
    public function locale(Locale $set = null) {
        if ($set) {
            $this->locale = $set;
            return $this;
        }
        return $this->locale;
    }

    /**
     * Get/set the display size of this widget (usually how much text is visible)
     *
     * @param $set integer
     *        	Valid of the size to display for inputs
     */
    public function show_size($set = null) {
        if ($set !== null) {
            if (is_numeric($set)) {
                $this->set_option("show_size", intval($set));
            }
            return $this;
        }
        return $this->option("show_size", null);
    }

    /**
     * Retrieve whether this object is "new" or an existing object
     *
     * @return boolean
     */
    protected function is_new() {
        if (method_exists($this->object, "is_new")) {
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
        if ($this->save_render()) {
            return false;
        }
        return $this->call_hook_arguments("visible", array(), true);
    }

    /**
     * Clear errors
     *
     * @return Widget
     */
    public function clear() {
        $this->errors = array();
        $this->messages = array();
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
        if (count($this->errors) + count($this->messages) !== 0) {
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
     *        	String, array, or Widget
     * @param mixed $col
     *        	Column for error, or null to use default
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
     * @todo See if I can combine this with error somehow
     * @param mixed $message
     *        	String, array, or Widget
     * @param mixed $col
     *        	Column for message, or null to use default
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

    final protected function _exec_submit() {
        $result = true;
        $valid = true;
        if ($this->exec_state === self::ready) {
            $this->exec_state = self::submit;
            if ($this instanceof Control) {
                /*
                 * Allow short-circuiting submit/render to allow individual widgets to handle controlling their aspect
                 * of a request without a separate controller.
                 */
                $target = $this->request->get("widget::target");
                if ($target) {
                    $result = $this->_exec_controller($target);
                } elseif ($this->submitted()) {
                    $this->load();
                    if (($valid = $this->validate()) === true) {
                        if (($valid = $this->call_hook_arguments('validate', array(), true)) === true) {
                            $result = $this->submit();
                        }
                    } else {
                        $result = $this->call_hook_arguments("validate_failed", array(), null);
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
        return $result ? $this : ($valid ? null : $this);
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
        $this->error($this->first_option("error_required;required_error", ($this->application->locale)("{label} is a required field.")));
        return $this;
    }

    /**
     * Validate our object as being ready for submission
     *
     * @return boolean
     */
    protected function validate() {
        $result = true;
        foreach ($this->children as $child) {
            if (!$child->validate()) {
                $this->application->logger->warning("{class}::validate() {child_class} named {name} did not validate", array(
                    "class" => get_class($this),
                    "name" => $child->name(),
                    "child_class" => get_class($child),
                ));
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
            if ($this->option_bool('trim')) {
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
            return array(
                $this->option_integer('minlength'),
                $this->option_integer('maxlength'),
            );
        } elseif ($max === null) {
            $this->set_option('maxlength', $mixed);
            return $this;
        } else {
            $this->set_option('minlength', $mixed);
            $this->set_option('maxlength', $max);
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
     * @param integer $mixed
     * @param integer $max
     * @return array|Widget
     */
    public function glyphs($mixed = null, $max = null) {
        if ($mixed === null && $max === null) {
            return array(
                $this->option_integer(self::option_minimum_glyph_length),
                $this->option_integer(self::option_maximum_glyph_length),
            );
        } elseif ($max === null) {
            $this->set_option(self::option_maximum_glyph_length, $mixed);
            return $this;
        } else {
            $this->set_option(self::option_minimum_glyph_length, $mixed);
            $this->set_option(self::option_maximum_glyph_length, $max);
            return $this;
        }
    }

    /**
     *
     * @param string $message
     * @param integer $size
     * @param integer $entered_size
     * @return boolean
     */
    private function _character_error($message, $size, $entered_size = null) {
        $locale = $this->application->locale;
        $this->error($locale($message, array(
            "label" => $this->label(),
            "length" => $size,
            "entered_length" => $entered_size,
            "characters" => $locale->plural($locale("character"), $size),
        )));
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
        $glyph_length = StringTools::length($v, $this->option("encoding"));

        list($min_byte_length, $max_byte_length) = $this->size();
        list($min_glyph_length, $max_glyph_length) = $this->glyphs();

        /* Old style - useful to ensure we don't exceed database string sizes */
        if (($min_byte_length > 0) && ($byte_length < $min_byte_length)) {
            return $this->_character_error("{label} must be at least {length} {characters} long.", $min_byte_length, $byte_length);
        }
        if (($max_byte_length > 0) && ($byte_length > $max_byte_length)) {
            return $this->_character_error("{label} must be at most {length} {characters} long.", $max_byte_length, $byte_length);
            $this->value(substr($v, 0, $max_byte_length));
            return false;
        }
        if (($min_glyph_length > 0) && ($glyph_length < $min_glyph_length)) {
            return $this->_character_error("{label} must be at least {length} {characters} long.", $min_glyph_length, $glyph_length);
        }
        if (($max_glyph_length > 0) && ($glyph_length > $max_glyph_length)) {
            return $this->_character_error("{label} must be at most {length} {characters} long (You entered {entered_length}).", $max_glyph_length, $glyph_length);
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
        return $this->request->is_post();
    }

    /**
     * Set option for all children
     *
     * @param string $name
     * @param mixed $value
     * @param boolean $recurse
     *        	Recurse on each child
     */
    final protected function children_set_option($name, $value = null, $recurse = true) {
        if (!is_array($this->children)) {
            return;
        }
        foreach ($this->children as $child) {
            /* @var $child Widget */
            $child->set_option($name, $value);
            if ($recurse) {
                $child->children_set_option($name, $value, $recurse);
            }
        }
    }

    /**
     * Calls a hook on this Widget, and on all children of this widget
     *
     * @param mixed $hooks
     *        	String, list of hooks (;-separated), or array of hook names
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
     *        	String, list of hooks (;-separated), or array of hook names
     * @return void
     */
    final public function children_hook_array($hooks, array $arguments) {
        $this->call_hook_arguments($hooks, $arguments);
        $children = $this->all_children();
        $locale = $this->application->locale;
        foreach ($children as $child) {
            /* @var $child Widget */
            if (!$child instanceof Widget) {
                throw new Exception_Semantics($locale("Child of {0} not a widget, but a {1}", get_class($this), gettype($child)));
            }
            $child->call_hook_arguments($hooks, $arguments);
        }
    }

    /**
     * Update execution state for all children
     *
     * @param string $state
     */
    private function _update_child_state($state) {
        if (!is_array($this->children)) {
            return;
        }
        foreach ($this->children as $child) {
            $child->_update_child_state($state);
            $child->exec_state = $state;
        }
    }

    /**
     * Run the ready step for this widget and all children
     *
     * @throws Exception_Semantics
     * @return Widget
     */
    private function _exec_ready() {
        if ($this->exec_state === null) {
            $this->exec_state = self::ready;
            $this->response();
            // Create model for parent object to have something to examine state in
            if (!$this->object instanceof Model) {
                $model = $this->model();
                if (!$model instanceof Model) {
                    throw new Exception_Semantics("Object required for class " . get_class($this));
                }
                $this->object($model);
                $this->defaults();
            }
            $this->initialize();
            // Apply model settings to the children we just created
            $this->children_model();
            $this->children_hook("initialized");
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
    protected function load() {
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
            $input_name = $object->apply_map($this->name());
            if ($this->request_index !== null) {
                $input_name = StringTools::unsuffix($input_name, '[]');
                if ($this->request->has($input_name, false)) {
                    $new_value = $this->request->geta($input_name);
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
    protected function children_load() {
        if (is_array($this->children) && count($this->children) !== 0) {
            foreach ($this->children as $widget) {
                /* @var $widget Widget */
                $widget->object($widget->traverse ? $this->object->get($widget->column()) : $this->object);
                $widget->load();
            }
        }
    }

    private function save_new_value($new_value) {
        if ($this->option_bool("trim", true) && is_scalar($new_value)) {
            $new_value = trim($new_value);
        }
        $this->_save_default_value($new_value);
        $this->value($new_value);
        $this->call_hook("loaded;model_changed");
        if ($this->object && $this->traverse) {
            $this->object->call_hook("control_loaded", $this);
        }
    }

    /**
     * Initialize subobjects by traversing the model and initializing the sub-models
     */
    private function children_model() {
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
            return $this->exec_state = $this->unwrap_all($output);
        }
        return $this->exec_state;
    }

    /**
     * Get/set the request index
     *
     * @param integer $set
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
        $this->response()->json()->data(array(
            "status" => false,
            "message" => $this->application->locale->__("{class} does not implement controller method", array(
                "class" => get_class($this),
            )),
        ));
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
            throw new Exception_Semantics("Requires request to be set");
        }
        if ($reset) {
            $this->exec_state = $this->content = $this->content_children = null;
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
    public function data_attributes() {
        return HTML::data_attributes($this->options);
    }

    /**
     * Retrieve the attributes in this Widget related to INPUT tags
     *
     * @param unknown $types
     * @return array
     */
    public function input_attributes($types = false) {
        return $this->options_include(HTML::input_attribute_names($types));
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
            $this->set_option($option, $set);
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
            return $this->set_option('help', ($append ? $this->option('help', '') : '') . $set);
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
     *        	Attributes to manipulate
     * @param array $add
     *        	Set of name value pairs on how to manipulate the attributes
     */
    public static function attributes_inherit(array $attributes, array $inherit) {
        foreach ($inherit as $k => $v) {
            $act = substr($k, 0, 1);
            if ($act === '+') {
                $k = substr($k, 1);
                $value = avalue($attributes, $k);
                if (empty($value)) {
                    $attributes[$k] = $v;
                } elseif (is_string($value)) {
                    $attributes[$k] = CSS::add_class($value, $v);
                } elseif (is_array($value)) {
                    $attributes[$k][] = $value;
                }

                continue;
            }
            if ($act === '*') {
                $k = substr($k, 1);
                $value = avalue($attributes, $k);
                if (empty($value)) {
                    $attributes[$k] = $v;
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
     */
    private static function _attributes_names($type) {
        static $types = array(
            "default" => "id;class;style",
            "input" => "id;class;style;title;placeholder;onclick;ondblclick;onmousedown;onmouseup;onmouseover;onmousemove;onmouseout;onkeypress;onkeydown;onkeyup;type;name;value;checked;disabled;readonly;size;maxlength;src;alt;usemap;ismap;tabindex;accesskey;onfocus;onblur;onselect;onchange;accept",
            "textarea" => "id;class;style;title;placeholder;onclick;ondblclick;onmousedown;onmouseup;onmouseover;onmousemove;onmouseout;onkeypress;onkeydown;onkeyup;type;name;checked;disabled;readonly;size;maxlength;src;alt;tabindex;accesskey;onfocus;onblur;onselect;onchange;accept",
        );
        return avalue($types, $type, $types['default']);
    }

    /**
     * Retrieve attributes associated with this widget (including those from "type")
     *
     * @param array $inherit
     * @param string $type
     */
    public function attributes(array $inherit = array(), $type = "") {
        $options_list = self::_attributes_names($type);
        $attributes = $this->options_include($options_list) + $this->data_attributes();
        return self::attributes_inherit($attributes, $inherit);
    }

    private function empty_condition_apply() {
        if (!$this->has_option("empty_condition")) {
            return false;
        }
        $this->set_option("condition", $this->option("empty_condition"));
        return true;
    }

    private function validate_empty_condition() {
        $value = $this->value();
        if (empty($value)) {
            return true;
        }
        return true;
    }

    protected function _default_value($default = null, $column = null) {
        $column = ($column === null) ? $this->column() : $column;
        $sess_variable_name = $this->option("session_default");
        $default = $default !== null ? $default : $this->default_value();
        if (!$sess_variable_name) {
            return $default;
        }
        if ($sess_variable_name === true) {
            $sess_variable_name = $this->option("session_default_prefix", "") . $column;
        }
        $session = $this->request->session();
        if (!$session) {
            return $default;
        }
        return $session->get($sess_variable_name, $default);
    }

    protected function _save_default_value($value, $column = null) {
        $sess_variable_name = $this->option("session_default");
        if (!$sess_variable_name) {
            return;
        }
        $column = ($column === null) ? $this->column() : $column;
        if ($sess_variable_name === true) {
            $sess_variable_name = $this->option("session_default_prefix", "") . $column;
        }
        $session = $this->request->session();
        if (!$session) {
            return;
        }
        $this->session->set($sess_variable_name, $value);
    }

    /**
     * When executing child, traverse the parent model
     *
     * @var boolean
     */
    public function traverse($set = null) {
        if ($set !== null) {
            $this->traverse = to_bool($set);
            return $this;
        }
        return $this->traverse;
    }

    /**
     * Returns the model for this widget.
     * If no model returned, then this widget must be invoked with an existing model.
     *
     * @return Model
     */
    protected function model() {
        $model = $this->call_hook("model_new", $this);
        if (!$model instanceof Model) {
            $model = $this->application->factory(__NAMESPACE__ . "\\" . "Model", $this->application);
            $model = $this->call_hook('model_alter', $model);
        }
        return $model;
    }

    /**
     *
     * @param Model $object
     * @return Widget
     */
    public function ready(Model &$object = null) {
        if ($object) {
            $this->object = $object;
        }
        $this->_exec_ready();
        return $this;
    }

    /**
     * Initialize widgets before any other execution function
     *
     * @param $object Model
     * @return void
     */
    protected function initialize() {
        $response = $this->response();
        if (!$response) {
            throw new Exception_Semantics("Widget {class} must be set up with a response prior to initialization", array(
                "class" => get_class($this),
            ));
        }
        if (!$this->has_option('column', true)) {
            $class = get_class($this);
            $column = strtolower(strtr($class, "_", "-")) . '-' . $this->response()->id_counter();
            $this->column($column);
            $this->application->logger->notice("{class} was given a default column name \"{column}\"", array(
                "class" => $class,
                "column" => $column,
            ));
        }
        if (!$this->has_option('name')) {
            $this->name($this->column());
        }
        if (!$this->has_option('id')) {
            $this->id($this->name());
        }
        if (!$this->_initialize) {
            if (is_array($this->children)) {
                $this->children_initialize();
            }
        }
        if ($this->has_option("theme_variables")) {
            $this->theme_variables += $this->option_array("theme_variables");
        }
        $this->_initialize = true;
    }

    /**
     * Initialize all of my children, if any
     *
     * @param unknown $object
     */
    protected function children_initialize() {
        if (is_array($this->children)) {
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
    }

    /**
     * Initialize a form which has not been submitted.
     *
     * Set default values in object, if needed. Uses the ->inited() method of $this->object to
     * determine if we are dealing with a
     * pre-loaded (initialized) object, or a blank model.
     */
    protected function defaults() {
        $this->children_defaults();
        if (to_bool(avalue($this->options, 'disabled'))) {
            return;
        }
        $this->value($this->_default_value(null));
    }

    /**
     * Run defaults on children
     */
    protected function children_defaults() {
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
    protected function child_css_class() {
        return strtr(strtolower(get_class($this)), "_", "-");
    }

    /**
     * Render the children of this widget
     *
     * @return string
     */
    protected function render_children() {
        if ($this->content_children !== null) {
            return $this->content_children;
        }
        $this->content_children = "";
        if (count($this->children) === 0) {
            return $this->content_children;
        }
        $content = $suffix = array();
        foreach ($this->children as $key => $child) {
            $child->object = $child->traverse ? $this->object->get($child->column()) : $this->object;
            $child->content = $child->render();
            if (empty($child->content)) {
                continue;
            }
            if ($child->is_visible()) {
                $child_tag = $this->option('child_tag', "div");
                if ($child_tag) {
                    $child_attributes = CSS::add_class($this->option('child_attributes', '.child'), array(
                        $child->child_css_class(),
                        $child->context_class(),
                    ));
                    $content[] = HTML::tag($child_tag, $child_attributes, $child->content);
                } else {
                    $content[] = $child->content;
                }
            } else {
                $suffix[] = $child->content;
            }
        }
        $this->content_children = HTML::tag($this->option('children_tag', 'div'), $this->option('children_attributes', '.children'), implode("", $content)) . implode("", $suffix);
        return $this->content_children;
    }

    /**
     * Set theme variables for this widget
     *
     * @param array $set
     * @param boolean $append
     * @return Widget
     */
    public function set_theme_variables(array $set, $append = true) {
        $this->theme_variables = $append ? $set : $set + $this->theme_variables;
        return $this;
    }

    /**
     * Return array of variables to pass to the theme
     *
     * @return array
     */
    public function theme_variables() {
        return $this->application->variables() + array(
            'request' => $this->request(),
            'response' => $this->response(),
            'widget' => $this,
            'input_attributes' => $input_attributes = $this->input_attributes(),
            'data_attributes' => $data_attributes = $this->data_attributes(),
            'attributes' => $input_attributes + $data_attributes,
            'required' => $this->required(),
            'name' => $this->name(),
            'column' => $this->column(),
            'label' => $this->label(),
            'id' => $this->id(),
            'context_class' => $this->context_class(),
            'empty_string' => $this->empty_string(),
            'show_size' => $this->show_size(),
            'object' => $this->object,
            'model' => $this->object,
            'value' => $this->value(),
            'parent' => $this->parent,
            'children' => $this->children(),
            'all_children' => $this->all_children(false),
            'errors' => $this->errors(),
            'messages' => $this->messages(),
            'content_children' => $this->content_children,
        ) + $this->theme_variables + $this->options;
    }

    /**
     * Getter/setter for object
     *
     * @param Model $set
     *
     * @return Widget Model
     */
    public function object(Model $set = null) {
        if ($set !== null) {
            $this->object = $set;
            $this->call_hook("object", $set);
            return $this;
        }
        return $this->object;
    }

    /**
     * Render this widget
     *
     * @return string
     */
    public function render() {
        if ($this->content !== null) {
            return $this->content;
        }
        $this->children_hook('render');
        if ($this->render_children) {
            $this->render_children();
        }
        $this->content = "";
        if ($this->theme) {
            $this->content .= $theme_content = $this->application->theme($this->theme, $this->theme_variables(), array(
                "first" => true,
            ));
        }
        $this->content .= $this->content_children;
        $this->content = $this->render_finish($this->content);
        $this->content = $this->call_hook_arguments("render_alter", array(
            $this->content,
        ), $this->content);
        return $this->content;
    }

    /**
     * Render structure
     *
     * @return array
     */
    public function render_structure() {
        $children_structure = array();
        foreach ($this->children as $child) {
            $children_structure = array_merge($children_structure, $child->render_structure());
        }
        $result[$this->name() . ":" . get_class($this)] = array(
            "children" => $children_structure,
            "render_children" => $this->render_children,
        );
        return $result;
    }

    /**
     * Get/set the URL to redirect to upon submit
     *
     * @param string $set
     * @param string $message
     * @return string|Widget
     */
    public function url_submit_redirect($set = null, $message = null) {
        if ($message !== null) {
            $this->set_option('submit_redirect_message', $message);
        }
        return $set === null ? $this->option('submit_redirect') : $this->set_option('submit_redirect', $set);
    }

    /**
     * Submit children and do final storage/action for form
     *
     * Return true to continue and render, false to stop processing now and render nothing
     *
     * @return boolean
     */
    public function submit() {
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
                        $child->error(__("{label} failed to submit."), $child->column());
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
        return $set === null ? $this->option('submit_message') : $this->set_option('submit_message', $append ? $this->submit_message() . ' ' . $set : $set);
    }

    /**
     * Getter/Setter for submit_url - URL redirected to upon any submit
     *
     * @todo rename submit_redirect to submit_url at some point.
     *
     * @param string $set
     * @return Widget string
     */
    public function submit_url($set = null) {
        return $set === null ? $this->option('submit_redirect') : $this->set_option('submit_redirect', $set);
    }

    /**
     * Getter/Setter for submit_url_default - URL redirected to upon submit when no ref is found in
     * form/query string
     *
     * @param string $set
     * @return Widget string
     */
    public function submit_url_default($set = null) {
        return $set === null ? $this->option('submit_url_default') : $this->set_option('submit_url_default', $set);
    }

    /**
     * Handle final submission of form and redirect/respond
     *
     * @return boolean
     */
    protected function submit_redirect() {
        $response = $this->response();
        if ($this->parent && $this->parent->submit_url() !== null) {
            // Continue
            return true;
        }
        $ref = $this->request->get_not_empty('ref');
        $submit_url = $this->submit_url();
        $submit_url_default = $this->submit_url_default();
        if ($this->option_bool('submit_skip_ref')) {
            $submit_url = $ref ? URL::query_format($submit_url, array(
                "ref" => $ref,
            )) : $submit_url;
        }
        $vars = ArrayTools::kprefix($this->object->variables(), "object.") + ArrayTools::kprefix($this->request->variables(), "request.");
        $url = null;
        if ($submit_url) {
            $submit_url = map($submit_url, $vars);
        } elseif ($ref && !URL::is($ref)) {
            $submit_url = map($ref, $vars);
        } elseif ($submit_url_default) {
            $submit_url = map($submit_url_default, $vars);
        }
        if ($submit_url) {
            $url = URL::query_format($submit_url, "ref", $this->request->get("ref", $this->request->url()));
        }
        $message = map($this->first_option("submit_message;submit_redirect_message;onstore_message", ""), $vars);

        $status = $this->status();
        if (!$status) {
            $message = array_values($this->errors());
        }
        if ($this->prefer_json()) {
            $json = array(
                "status" => $status,
                "message" => $message,
                "redirect" => $url,
                "object" => $this->object->json(),
            );
            if ($this->has_option('submit_theme', true)) {
                $json['content'] = $this->application->theme($this->option('submit_theme'), $this->theme_variables(), array(
                    "first" => true,
                ));
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
    protected function render_finish($content) {
        $content = $this->render_behavior($content);
        if ($this->wrapped) {
            return $content;
        }
        $this->wrapped = true;
        if ($this->option_bool("debug")) {
            dump($this->object);
        }
        $prefix = $this->prefix();
        $suffix = $this->suffix();

        $this->prefix("")->suffix("");
        if ($this->option_bool("wrap_map_disabled")) {
            return $prefix . $content . $suffix;
        }
        return $this->unwrap_all($this->object->apply_map($prefix) . $content . $this->object->apply_map($suffix));
    }

    /**
     * Alter this query to work with the widgets
     *
     * @param Database_Query_Select $query
     * @return void
     */
    public function query_alter(Database_Query_Select $query) {
        $this->children_hook("query", $query);
    }

    /**
     * Specify a behavior attached to this widget
     *
     * @param string $type
     * @param array $options
     * @return Widget
     */
    public function behavior($type, array $options = array()) {
        $this->behaviors[] = array(
            $type,
            $options,
        );
        return $this;
    }

    /**
     * Render behavior items for this widget
     *
     * @param string $content
     * @return string
     */
    private function render_behavior($content) {
        //		$content .= HTML::tag("pre", _dump($this->behaviors));
        foreach ($this->behaviors as $item) {
            list($theme, $options) = $item;
            $content .= $this->application->theme($theme, $options + array(
                "widget" => $this,
                "object" => $this->object,
                "content" => $content,
                "request" => $this->request,
                "response" => $this->response(),
            ));
        }
        return $content;
    }

    /**
     * Return the jQuery expression to determine the value of this widget
     */
    public function jquery_value_expression() {
        if ($this->has_option('jquery_value_expression')) {
            return $this->option('jquery_value_expression');
        }
        if ($this->has_option('value_expression')) {
            return $this->option('value_expression');
        }

        $id = $this->id();
        if (!$id) {
            return null;
        }
        return "\$(\"#$id\").val()";
    }

    public function jquery_value_selected_expression() {
        if ($this->has_option('jquery_value_selected_expression')) {
            return $this->option('jquery_value_selected_expression');
        }
        if ($this->has_option('value_selected_expression')) {
            return $this->option('value_selected_expression');
        }

        $id = $this->id();
        if (!$id) {
            return null;
        }
        return "\$(\"#$id\")";
    }

    /**
     * Return the jQuery expression to determine the items to be watched for a widget (related to
     * behavior)
     */
    public function jquery_target_expression() {
        if ($this->has_option('jquery_target_expression')) {
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
    public function prefer_json() {
        return $this->request->prefer_json();
    }

    /**
     * Retrieve the class object for this widget
     *
     * @deprecated 2018-01
     * @return zesk\Class_ORM
     */
    public function class_object() {
        zesk()->deprecated();
        return $this->class_orm();
    }

    /**
     * @deprecated 2018-01
     * @param unknown $set
     * @return \zesk\Widget|string
     */
    public function object_class($set = null) {
        zesk()->deprecated();
        return $this->orm_class($set);
    }
}
