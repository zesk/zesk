<?php declare(strict_types=1);

/**
 *
 */
namespace zesk;

use Psr\Cache\CacheItemInterface;

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
 * Note that variables are also extracted (using `extract`) into the local scope, so
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
	 * Stack of Template for begin/end
	 *
	 * @var array of Template
	 * @see Template::inherited_variables
	 */
	private $wrappers = [];

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
	private $_vars;

	/**
	 * Template variables which have changed
	 *
	 * @var array
	 */
	private $_vars_changed = [];

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
	 * @var CacheItemInterface
	 */
	protected $paths_cache = null;

	/**
	 * Template statistics
	 *
	 * @var array
	 */
	private static $_stats = [
		'counts' => [],
		'times' => [],
	];

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
	 * Debugging on
	 *
	 * @var boolean
	 */
	private static $debug = false;

	/**
	 * Set to true to debug the push/pop stack
	 *
	 * @var boolean
	 */
	public static $debug_stack = false;

	/**
	 * Construct a new template
	 *
	 * @param Application $app
	 * @param string $path
	 * @param array|Template| $variables
	 * @return self
	 */
	public static function factory(Application $app, $path = null, $variables = null) {
		return new self($app, $path, $variables);
	}

	/**
	 * Construct a new template
	 *
	 * @param ?string $path
	 *        	Relative or absolute path to template
	 * @param ?mixed $variables
	 *        	Name/Value pairs to be set in the template execution
	 * @param Application $app
	 */
	public function __construct(Application $app, string $path = null, mixed $variables = null) {
		$this->application = $app;
		$this->stack = $app->template_stack;

		$this->_vars = [];
		if ($variables instanceof Template) {
			$this->_vars = $variables->variables() + $this->_vars;
		} elseif (is_array($variables)) {
			foreach ($variables as $k => $v) {
				if (is_string($k) && str_starts_with($k, '_')) {
					continue;
				}
				$this->__set(strval($k), $v);
			}
			$this->_vars_changed = [];
		}
		assert(count($this->_vars_changed) === 0);
		if ($path) {
			$this->_original_path = $path;
			$this->setPath($path);
		}
		if (self::$profile) {
			ArrayTools::increment(self::$_stats['counts'], $this->_path);
		}
	}

	/**
	 * Begin output buffering for a \"theme\", push it on the stack.
	 *
	 * @param string $path
	 *        	Relative and absolute path to template
	 * @param ?mixed $variables
	 * @return self
	 */
	public function begin(string $path, mixed $variables = null): self {
		if (ends($path, ".tpl")) {
			$this->application->logger->warning("{method} {path} ends with .tpl - now deprecated, use theme names only, called from {calling_function}", [
				"method" => __METHOD__,
				"path" => $path,
				"calling_function" => calling_function(0),
			]);
		} else {
			$path .= ".tpl";
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
	public function end(array $variables = [], string $content_variable = "content"): string {
		if (count($this->wrappers) === 0) {
			throw new Exception_Semantics("Template::end when no template on the wrapper stack");
		}
		$t = array_pop($this->wrappers);
		/* @var $t Template */
		$t->pop();
		if (!$t->_path) {
			return "";
		}
		$variables[$content_variable] = ob_get_clean();
		$t->set($variables);
		return $t->render();
	}

	/**
	 * Push the variable stack
	 *
	 * @return Template
	 */
	public function push() {
		$top = $this->stack->top();
		$this->stack->push($this);
		$this->_vars += $top->variables();
		if (self::$debug_stack) {
			$this->application->logger->debug("Push {path}", [
				"path" => $this->_path,
			]);
		}
		$this->_running++;
		return $this;
	}

	/**
	 * Pop the variable stack
	 *
	 * @return $this
	 * @throws Exception_Semantics
	 */
	public function pop(): self {
		$stack = $this->stack;
		$top = $stack->pop();
		if (self::$debug_stack) {
			$this->application->logger->debug("Pop {path}", [
				"path" => $top->_path,
			]);
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
	public function variables(): array {
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
	 * @param string $path
	 * @param boolean $all Retrieve all valid paths
	 */
	public function find_path($path, $all = false) {
		if (empty($path)) {
			return null;
		}
		if (begins($path, "/")) {
			return $path;
		}
		return $this->_find_path($path, $all);
	}

	/**
	 * Find template path
	 *
	 * @param string $path
	 * @param boolean $all
	 *        	Return all possible paths as keys and whether the file exists as the value
	 * @return array|string
	 */
	private function _find_path($path, $all = false) {
		if (Directory::is_absolute($path)) {
			if ($all) {
				return [
					$path => file_exists($path),
				];
			}
			return $path;
		}
		$result = $this->application->theme_find($path, [
			"all" => $all,
			"no_extension" => true,
		]);
		if ($result === null || (is_array($result) && count($result) === 0)) {
			$theme_paths = $this->application->theme_path();
			if (self::$debug) {
				static $template_path = false;
				if (!$template_path) {
					$this->application->logger->debug("theme_path is\n\t" . JSON::encode_pretty($theme_paths));
					$template_path = true;
				}
				$this->application->logger->warning(__("Template::path(\"{path}\") not found in theme_path ({n_paths} paths).", [
					'path' => $path,
					'theme_paths' => $theme_paths,
					'n_paths' => count($theme_paths),
				]));
			}
			if (!$all) {
				return null;
			}
		}

		return $result;
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
	 * @return string
	 */
	public function path($set = null) {
		if ($set !== null) {
			$this->application->deprecated("setter/getter combo deprecated");
			return $this->setPath($set);
		}
		return $this->_path;
	}

	/**
	 * @param $set Value to set the path to
	 * @return $this
	 */
	public function setPath($set): self {
		$this->_path = $this->find_path($set);
		return $this;
	}

	/**
	 * Does this template exist?
	 *
	 * @return boolean
	 */
	public function exists(): bool {
		return is_string($this->_path) && file_exists($this->_path);
	}

	/**
	 *
	 * @return string
	 */
	public function className() {
		return "Template";
	}

	/**
	 *
	 * @return mixed
	 */
	public function result() {
		return $this->return;
	}

	public function object_name() {
		$contents = File::contents($this->_path, null);
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
	public function changed() {
		return $this->_vars_changed;
	}

	/**
	 * Is a variable set in this template (and non-null)?
	 *
	 * @param string|integer $k
	 * @return bool
	 */
	public function has($k) {
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
	public function set($k, $v = null): void {
		if (is_array($k)) {
			foreach ($k as $k0 => $v0) {
				$this->__set($k0, $v0);
			}
		} elseif ($k instanceof Template) {
			$this->inherit($k);
		} else {
			$this->__set($k, $v);
		}
	}

	/**
	 * Get a variable name, with a default
	 *
	 * @param string $k
	 * @param mixed $default
	 * @return mixed
	 */
	public function get($k, $default = null) {
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
	 * @param string $keys
	 * @param mixed $default
	 * @return mixed
	 */
	public function get1($keys, $default = null) {
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
	public function geti($k, $default = null) {
		return to_integer($this->__get($k), $default);
	}

	/**
	 * Get a value and convert it to an boolean, or return $default
	 *
	 * @param string $k
	 * @param mixed $default
	 * @return boolean
	 */
	public function getb(string $k, bool $default = false): bool {
		return to_bool($this->__get($k), $default);
	}

	/**
	 * Get a value if it's an array, or return $default
	 *
	 * @param string $k
	 * @param mixed $default
	 * @return boolean
	 */
	public function geta($k, $default = []) {
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
	 * @param string $delimiter How to split string lists
	 * @return boolean
	 */
	public function get_list($k, $default = [], $delimiter = ";") {
		return to_list($this->__get($k), $default, $delimiter);
	}

	/**
	 * Output
	 *
	 * @return string
	 * @throws \Exception
	 */
	public function render(): ?string {
		if (!$this->_path) {
			return null;
		}
		$__start = microtime(true);
		ob_start();
		$this->push();
		extract([
			"application" => $this->application,
		] + $this->_vars, EXTR_SKIP); // Avoid overwriting $this
		// This name is fairly unique to avoid conflicts with variables set in our include.
		$_template_exception = null;

		try {
			$this->return = include($this->_path);
		} catch (\Exception $_template_exception) {
			$this->application->hooks->call("exception", $_template_exception);
		}

		try {
			$this->pop();
		} catch (Exception_Semantics $e) {
			$this->application->logger->error("pop semantics error {path}", $this->variables());
		}
		$contents = ob_get_clean();
		if ($_template_exception) {
			throw $_template_exception;
		}
		if (self::$profile) {
			ArrayTools::increment(self::$_stats['times'], $this->_path, microtime(true) - $__start);
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

	/**
	 *
	 * @param Application $application
	 * @throws Exception_Lock
	 * @throws Exception_Semantics
	 */
	public static function configured(Application $application): void {
		$config = $application->configuration->path(__CLASS__);
		self::$profile = to_bool($config->profile);
		self::$wrap = to_bool($config->wrap);
		self::$debug = to_bool($config->debug);
		self::$debug_stack = to_bool($config->debug_stack);
		$application->hooks->add('</body>', [
			__CLASS__,
			'profile_output',
		]);
	}

	/**
	 * Implements ::hooks
	 * @param Application $application
	 */
	public static function hooks(Application $application): void {
		try {
			$application->hooks->add('configured', [
				__CLASS__,
				'configured',
			]);
		} catch (Exception_Semantics $e) {
		}
	}

	/**
	 *
	 * @return string
	 */
	public static function profile_output($_, Response $response) {
		if (!self::$profile) {
			return '';
		}
		$app = $response->application;
		return $app->theme('template/profile', self::$_stats);
	}

	/*
	 * ==== Functions Below Here have access to _vars by key ====
	 */

	/**
	 * Apply variables set and inherit to parents. This template will "set" all values of the
	 * passed in object. So if it's an array (name/value pairs), or a Template, it sets multiple
	 * values. If you pass in a string, and a value it's the same as __set
	 *
	 * @param Template|string|arary $mixed
	 * @param $value When $mixed is a string, the value to set it to
	 * @return $this
	 */
	public function inherit($mixed, $value = null) {
		if (is_array($mixed)) {
			foreach ($mixed as $k => $v) {
				$this->__set($k, $v);
			}
		} elseif ($mixed instanceof Template) {
			$this->inherit($mixed->variables());
		} else {
			$this->__set($mixed, $value);
		}
		return $this;
	}

	/**
	 *
	 * @param string $key Key
	 * @param mixed $value Value
	 *@see stdClass::__set
	 */
	public function __set($key, $value): void {
		$key = self::_template_key($key);
		if ($this->_running > 0) {
			$this->_vars_changed[$key] = $value;
		}
		$this->_vars[$key] = $value;
	}

	/**
	 *
	 * @param string $key
	 * @return mixed
	 * @see stdClass::__get
	 */
	public function __get($key) {
		$key = self::_template_key($key);
		if (array_key_exists($key, $this->_vars)) {
			return $this->_vars[$key];
		}
		return [
			'variables' => $this->_vars,
			'self' => $this,
		][$key] ?? null;
	}

	/**
	 *
	 * @param string $key
	 * @return bool
	 * @see stdClass::__isset
	 */
	public function __isset($key) {
		$key = self::_template_key($key);
		return isset($this->_vars[$key]);
	}

	/**
	 *
	 * @param string $key
	 *@see stdClass::__unset
	 */
	public function __unset($key): void {
		$key = self::_template_key($key);
		unset($this->_vars[$key]);
	}

	/**
	 * @return string
	 */
	public function __toString() {
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
	 * @return string
	 */
	final public function theme($types, $arguments = [], array $options = []) {
		return $this->application->theme($types, $arguments, $options);
	}

	/**
	 * Create a widget
	 *
	 * @param string $class
	 * @param array $options
	 * @return Widget
	 */
	final public function widget_factory($class, array $options = []) {
		return $this->application->widget_factory($class, $options);
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
	 * @return bool
	 */
	final public function theme_exists($types, $arguments = []) {
		return $this->application->theme_exists($types, $arguments);
	}
}
