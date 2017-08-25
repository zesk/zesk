<?php

/**
 *
 */
namespace zesk;

/**
 * Simple template engine which uses PHP includes.
 * Supports variables passed into the template, returned from the template,
 * and inherited templates by setting up Application::theme_paths
 * Templates can be "stacked" to inherit parent variables settings.
 * Changing template values within the template (e.g. `$this->frame = "foo";`) will then bubble up
 * to parent templates.
 * Templates should be implemented by assuming "$this" is a Template within the template file, and
 * you can use:
 * `$this->has('account')` to determine if a variable has been set, and
 * `$this->account` to output it.
 * Note that variables are also extract'ed into the local scope, so
 * $this->account
 * $account
 * Are the same value.
 * @format markdown
 *
 * @package zesk
 * @subpackage system
 * @see Application::theme_paths
 * @author kent
 */
class Template implements Interface_Theme {
	/**
	 *
	 * @var Application
	 */
	public $application = null;

	/**
	 *
	 * @var Template_Stack
	 */
	public $stack = null;

	/**
	 * Set to true to debug the push/pop stack
	 *
	 * @var boolean
	 */
	public static $debug_stack = false;

	/**
	 * Stack of Template for begin/end
	 *
	 * @var array of Template
	 * @see Template::inherited_variables
	 */
	private $wrappers = array();

	/**
	 *
	 * @var string
	 */
	private $_original_path = null;

	/**
	 *
	 * @var string
	 */
	public $_path = null;

	/**
	 * Template variables
	 *
	 * @var array
	 */
	private $_vars = array();

	/**
	 * Template variables which have changed
	 *
	 * @var array
	 */
	private $_vars_changed = array();

	/**
	 * Number of pushes to this template
	 *
	 * @var integer
	 */
	private $_running = 0;

	/**
	 * Parent template
	 *
	 * @var Template
	 */
	public $_parent = null;

	/**
	 * The return value of the template
	 *
	 * @var mixed
	 */
	public $return = null;

	/**
	 *
	 * @var Cache
	 */
	protected $paths_cache = null;

	/**
	 * Template statistics
	 *
	 * @var array
	 */
	private static $_stats = array(
		'counts' => array(),
		'times' => array()
	);

	/**
	 * Whether to profile all templates.
	 * Set via global Template::profile
	 *
	 * @var boolean
	 */
	private static $profile = false;

	/**
	 * Whether to wrap all non-empty templates with HTML comments (caution!)
	 *
	 * @var boolean
	 */
	private static $wrap = false;

	/**
	 * Construct a new template
	 *
	 * @param string $path
	 *        	Relative or absolute path to template
	 * @param array $variables
	 *        	Name/Value pairs to be set in the template execution
	 */
	function __construct(Application $app, $path = null, $variables = null) {
		$this->application = $app;
		$this->stack = $app->template_stack;

		$this->_vars = array();
		if ($variables instanceof Template) {
			$this->_vars = $variables->variables() + $this->_vars;
		} else if (is_array($variables)) {
			foreach ($variables as $k => $v) {
				if (substr($k, 0, 1) === '_') {
					continue;
				}
				$this->__set($k, $v);
			}
			$this->_vars_changed = array();
		}
		assert(count($this->_vars_changed) === 0);
		if ($path) {
			$this->_original_path = $path;
			$this->path($path);
		}
		if (self::$profile) {
			arr::increment(self::$_stats['counts'], $this->_path);
		}
	}

	/**
	 * Begin output buffering for a \"theme\", push it on the stack.
	 *
	 * @param string $path
	 *        	Relative and absolute path to template
	 * @param array $variables
	 * @return Template
	 */
	public function begin($path, $variables = false) {
		if (!ends($path, ".tpl")) {
			$this->application->logger->warning("{method} {path} does not end with .tpl, called from {calling_function}", array(
				"method" => __METHOD__,
				"path" => $path,
				"calling_function" => calling_function(0)
			));
		}
		$this->wrappers[] = $t = new Template($this->application, $path, $variables);
		$t->push();
		if ($path) {
			ob_start();
		}
		return $t;
	}

	/**
	 * Complete output buffering, passing additional variables to be added to the template,
	 * and pass an optional variable name for the output content to be applied to the template.
	 *
	 * @param array $variables
	 *        	Optional variables to apply to the template
	 * @param string $content_variable
	 * @throws Exception_Semantics
	 */
	public function end($variables = array(), $content_variable = "content") {
		if (count($this->wrappers) === 0) {
			throw new Exception_Semantics("Template::end when no template on the wrapper stack");
		}
		$t = array_pop($this->wrappers);
		/* @var $t Template */
		$t->pop();
		if (!$t->_path) {
			return null;
		}
		$variables[$content_variable] = ob_get_clean();
		$t->set($variables);
		return $t->render();
	}

	/**
	 *
	 * @return Template
	 */
	public function top() {
		return $this->stack->top();
	}

	/**
	 * Push the variable stack
	 *
	 * @return Template
	 */
	public function push() {
		$this->_parent = $this->stack->top();
		$this->stack->push($this);
		$this->_vars += $this->_parent->variables();
		if (self::$debug_stack) {
			$this->application->logger->debug("Push {path}", array(
				"path" => $this->_path
			));
		}
		$this->_running++;
		return $this;
	}

	/**
	 * Pop the variable stack
	 *
	 * @return Template
	 */
	public function pop() {
		$stack = $this->stack;
		$top = $stack->pop();
		if (self::$debug_stack) {
			$this->application->logger->debug("Pop {path}", array(
				"path" => $top->_path
			));
		}
		if ($top !== $this) {
			if ($top === null) {
				throw new Exception_Semantics("Template::pop: Popped beyond the top!");
			}
			throw new Exception_Semantics("Popped template ($top->_path) not this ($this->_path)");
		}
		if (--$this->_running < 0) {
			throw new Exception_Semantics("Template::pop negative running");
		}
		/*
		 * If we have a stack and variables changed
		 */
		if (count($this->_vars_changed) === 0) {
			return $this;
		}
		$stack->variables($this->_vars_changed);
		return $this;
	}

	/**
	 * Retrieve the current Template's variables
	 *
	 * @return array
	 */
	public function variables() {
		return $this->_vars;
	}

	/**
	 * Retrieve the current Template's values
	 *
	 * @return array
	 */
	public function values() {
		return $this->_vars;
	}

	/**
	 *
	 * @return Cache
	 */
	private function _paths_cache() {
		if ($this->paths_cache instanceof Cache) {
			return $this->paths_cache;
		}
		$this->paths_cache = $cache = Cache::register(get_class($this->application) . "-Template::paths");
		$path = implode("|", $this->application->theme_path());
		if ($cache->has("theme_path")) {
			if ($cache->theme_path !== $path) {
				$cache->erase();
				$cache->theme_path = $path;
			}
		} else {
			$cache->theme_path = $path;
		}
		return $cache;
	}

	/**
	 *
	 * @param unknown $path
	 * @param string $all
	 */
	public function find_path($path, $all = false) {
		if (empty($path)) {
			return null;
		}
		if (begins($path, "/")) {
			return $path;
		}
		$paths = $this->_paths_cache();
		$cache_path = array(
			$path,
			$all ? "all" : "first"
		);
		$not_found = 'not-found';
		$result = $paths->path_get($cache_path, $not_found);
		if ($result !== $not_found) {
			return $result;
		}
		$result = $this->_find_path($path, $all);
		$paths->path_set($cache_path, $result);
		return $result;
	}
	/**
	 * Find template path
	 *
	 * @param string $path
	 * @param boolean $all
	 *        	Return all possible paths as keys and whether the file exists as the value
	 * @return array
	 */
	private function _find_path($path, $all = false) {
		if (Directory::is_absolute($path)) {
			if ($all) {
				return array(
					$path => file_exists($path)
				);
			}
			return $path;
		}
		$paths = array();
		$found = false;
		$theme_paths = $this->application->theme_path();
		foreach ($theme_paths as $prefix) {
			$temp = path($prefix, $path);
			$paths[$temp] = $exists = file_exists($temp);
			if (!$all && $exists) {
				return $temp;
			}
			if ($exists) {
				$found = true;
			}
		}
		if (!$found) {
			static $template_path = false;
			if (!$template_path) {
				$this->application->logger->notice("theme_path is " . newline() . implode(newline(), $theme_paths));
				$template_path = true;
			}
			$this->application->logger->notice(__("Template::path(\"{path}\") not found in theme_path ({n_paths} paths).", array(
				'path' => $path,
				'theme_paths' => $theme_paths,
				'n_paths' => count($paths)
			)));
			if (!$all) {
				return null;
			}
		}
		return $paths;
	}

	/**
	 * Would this template exist?
	 *
	 * @param string $path
	 * @return boolean
	 */
	public function would_exist($path) {
		$path = $this->find_path($path);
		return file_exists($path);
	}

	/**
	 * Set or get the template path.
	 * If setting, finds it in the file system and returns $this.
	 *
	 * @param string $set
	 * @return Template string
	 */
	function path($set = null) {
		if ($set !== null) {
			$this->_path = self::find_path($set);
			return $this;
		}
		return $this->_path;
	}

	/**
	 * Does this template exist?
	 *
	 * @return boolean
	 */
	function exists() {
		return file_exists($this->_path);
	}

	/**
	 *
	 * @return string
	 */
	function className() {
		return "Template";
	}

	/**
	 *
	 * @return mixed
	 */
	function result() {
		return $this->return;
	}
	function object_name() {
		$contents = file::contents($this->_path, null);
		$matches = null;
		if (!preg_match('/Name:\s*\"([^\"]+)\"/', $contents, $matches)) {
			return basename($this->_path);
		}
		return $matches[1];
	}

	/**
	 * Did anything change in this Template?
	 *
	 * @return array
	 */
	function changed() {
		return $this->_vars_changed;
	}

	/**
	 * Is a variable set in this template (and non-null)?
	 *
	 * @param string|numeric $k
	 */
	function has($k) {
		return $this->__isset($k);
	}

	/**
	 * Set a variable to the template
	 *
	 * @param string|array $k
	 *        	Name to set (or array)
	 * @param mixed $v
	 *        	Value to set
	 */
	function set($k, $v = null) {
		if (is_array($k)) {
			foreach ($k as $k0 => $v0) {
				$this->__set($k0, $v0);
			}
		} else if ($k instanceof Template) {
			$this->inherit($k);
		} else {
			$this->__set($k, $v);
		}
	}

	/**
	 * Get a variable name, with a default
	 *
	 * @param unknown_type $k
	 * @param unknown_type $default
	 * @return Ambigous <multitype:, mixed, array>|unknown
	 */
	function get($k, $default = null) {
		if (is_array($k)) {
			foreach ($k as $key => $default) {
				$k[$key] = $this->get($key, $default);
			}
			return $k;
		}
		$r = $this->__get($k);
		if ($r !== null) {
			return $r;
		}
		return $default;
	}

	/**
	 * Get first key value matching, or default
	 *
	 * @param unknown_type $keys
	 * @param unknown_type $default
	 * @return Ambigous <multitype:, mixed, array>|unknown
	 */
	function get1($keys, $default = null) {
		foreach (to_list($keys) as $key) {
			if ($this->__isset($key)) {
				return $this->__get($key);
			}
		}
		return $default;
	}

	/**
	 * Get a value and convert it to an integer, or return $default
	 *
	 * @param string $k
	 * @param mixed $default
	 * @return integer
	 */
	function geti($k, $default = null) {
		return to_integer($this->__get($k), $default);
	}

	/**
	 * Get a value and convert it to an boolean, or return $default
	 *
	 * @param string $k
	 * @param mixed $default
	 * @return boolean
	 */
	function getb($k, $default = null) {
		return to_bool($this->__get($k), $default);
	}

	/**
	 * Get a value if it's an array, or return $default
	 *
	 * @param string $k
	 * @param mixed $default
	 * @return boolean
	 */
	function geta($k, $default = array()) {
		$value = $this->__get($k);
		if (is_array($value)) {
			return $value;
		}
		return $default;
	}

	/**
	 * Get a value if it's an array, or return $default
	 *
	 * @param string $k
	 * @param mixed $default
	 * @return boolean
	 */
	function get_list($k, $default = array(), $delimiter = ";") {
		return to_list($this->__get($k), $default, $delimiter);
	}

	/**
	 * Output
	 *
	 * @return string
	 */
	function render() {
		global $zesk;
		if (!$this->_path) {
			return null;
		}
		$__start = microtime(true);
		ob_start();
		$this->push();
		extract(array(
			"zesk" => $zesk
		) + $this->_vars, EXTR_SKIP); // Avoid overwriting $this
		// This name is fairly unique to avoid conflicts with variables set in our include.
		$_template_exception = null;
		$variables = $this->_vars;
		try {
			$this->return = include $this->_path;
		} catch (Exception $_template_exception) {
			$this->application->hooks->call("exception", $_template_exception);
		}
		$this->pop();
		$contents = ob_get_clean();
		if ($_template_exception) {
			throw $_template_exception;
		}
		if (self::$profile) {
			arr::increment(self::$_stats['times'], $this->_path, microtime(true) - $__start);
		}
		if (self::$wrap && !empty($contents)) {
			$contents = "<!-- $this->_path { -->" . $contents . "<!-- } $this->_path -->";
		}
		return $contents;
	}

	/**
	 * Convert a key to a template key
	 *
	 * @param string $key
	 * @return string
	 */
	private static function _template_key($key) {
		return strtolower($key);
	}
	public static function configured(Application $application) {
		global $zesk;
		$config = $zesk->configuration->path("template");
		/* @var $zesk Kernel */
		self::$profile = to_bool($config->profile);
		self::$wrap = to_bool($config->wrap);
	}
	/**
	 * Implements ::hooks
	 */
	public static function hooks(Kernel $zesk) {
		$zesk->hooks->add('</html>', 'Template::profile_output');
		$zesk->hooks->add('configured', 'Template::configured');
	}
	public static function profile_output() {
		if (!self::$profile) {
			return '';
		}
		echo zesk()->application()->theme('template/profile', self::$_stats);
	}

	/*
	 * ==== Functions Below Here have access to _vars by key ====
	 */

	/**
	 * Apply variables set and inherit to parents
	 *
	 * @param Template $t
	 */
	function inherit($mixed, $value = null) {
		if (is_array($mixed)) {
			foreach ($mixed as $k => $v) {
				$this->inherit($k, $v);
			}
		} else if ($mixed instanceof Template) {
			$this->inherit($mixed->variables());
		} else {
			$k = self::_template_key($mixed);
			$this->_vars[$k] = $value;
			$this->_vars_changed[$k] = $value;
		}
	}

	/**
	 *
	 * @see stdClass::__set
	 * @param string|numeric $k
	 *        	Key
	 * @param mixed $v
	 *        	Value
	 */
	function __set($k, $v) {
		$k = self::_template_key($k);
		if ($this->_running > 0) {
			$this->_vars_changed[$k] = $v;
		}
		$this->_vars[$k] = $v;
	}

	/**
	 *
	 * @see stdClass::__get
	 * @param string|numeric $k
	 * @return mixed
	 */
	function __get($k) {
		$k = self::_template_key($k);
		if (array_key_exists($k, $this->_vars)) {
			return $this->_vars[$k];
		}
		return avalue(array(
			'variables' => $this->_vars,
			'self' => $this
		), $k, null);
	}

	/**
	 *
	 * @see stdClass::__isset
	 * @param string|numeric $k
	 */
	function __isset($k) {
		$k = self::_template_key($k);
		return isset($this->_vars[$k]);
	}
	/**
	 *
	 * @see stdClass::__unset
	 * @param string|numeric $k
	 */
	function __unset($k) {
		$k = self::_template_key($k);
		unset($this->_vars[$k]);
	}
	function __toString() {
		return PHP::dump($this->_original_path);
	}

	/**
	 * Output theme within a template.
	 *
	 * 2016-01-12 Moving away from globals. Use in templates instead of zesk::theme or theme, both
	 * of which are now deprecated.
	 *
	 * @param mixed $types
	 *        	Theme, or list of themes
	 * @param array $arguments
	 *        	Arguments for the theme to render
	 * @param array $options
	 *        	Extra options which effect how the theme request is interpreted
	 * @see Application::theme
	 */
	final function theme($types, $arguments = array(), array $options = array()) {
		return $this->application->theme($types, $arguments, $options);
	}

	/**
	 * Create a widget
	 *
	 * @param string $class
	 * @param array $options
	 */
	final function widget_factory($class, array $options = array()) {
		return $this->application->widget_factory($class, $options, $this->application);
	}
	/**
	 * Determine if theme exists
	 *
	 * 2016-01-12 Moving away from globals. Use in templates instead of zesk::theme or theme, both
	 * of which are now deprecated.
	 *
	 * @param mixed $types
	 *        	Theme, or list of themes
	 * @param array $arguments
	 * @param array $options
	 */
	final function theme_exists($types, $arguments = array()) {
		return $this->application->theme_exists($types, $arguments);
	}
}
