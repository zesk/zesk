<?php
declare(strict_types=1);

/**
 *
 */

namespace zesk;

use Psr\Cache\CacheItemInterface;

/**
 * Simple template engine which uses PHP includes.
 * Supports variables passed into the template, returned from the template,
 * and inherited templates by setting up Application::themePaths
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
 * @see Application::themePaths
 * @author kent
 */
class Template implements Interface_Theme {
	/**
	 *
	 * @var Application
	 */
	public Application $application;

	/**
	 *
	 * @var Template_Stack
	 */
	public Template_Stack $stack;

	/**
	 * Stack of Template for begin/end
	 *
	 * @var array of Template
	 * @see Template::inherited_variables
	 */
	private array $wrappers = [];

	/**
	 *
	 * @var string
	 */
	private string $_original_path = '';

	/**
	 *
	 * @var string
	 */
	public string $_path = '';

	/**
	 * Template variables
	 *
	 * @var array
	 */
	private array $_vars;

	/**
	 * Template variables which have changed
	 *
	 * @var array
	 */
	private array $_changed = [];

	/**
	 * Number of pushes to this template
	 *
	 * @var int
	 */
	private int $_running = 0;

	/**
	 * The return value of the template
	 *
	 * @var mixed
	 */
	public mixed $return = null;

	/**
	 * Template statistics
	 *
	 * @var array
	 */
	private static array $_stats = [
		'counts' => [], 'times' => [],
	];

	/**
	 * Whether to profile all templates.
	 * Set via global Template::profile
	 *
	 * @var boolean
	 */
	private static bool $profile = false;

	/**
	 * Whether to wrap all non-empty templates with HTML comments (caution!)
	 *
	 * @var boolean
	 */
	private static bool $wrap = false;

	/**
	 * Debugging on
	 *
	 * @var boolean
	 */
	private static bool $debug = false;

	/**
	 * Set to true to debug the push/pop stack
	 *
	 * @var boolean
	 */
	public static bool $debug_stack = false;

	/**
	 * Construct a new template
	 *
	 * @param Application $app
	 * @param string $path
	 * @param array $variables
	 * @return self
	 */
	public static function factory(Application $app, string $path = '', array|self $variables = []): self {
		return new self($app, $path, $variables);
	}

	/**
	 * Construct a new template
	 *
	 * @param ?string $path
	 *            Relative or absolute path to template
	 * @param ?mixed $variables
	 *            Name/Value pairs to be set in the template execution
	 * @param Application $app
	 */
	public function __construct(Application $app, string $path = '', array|self $variables = null) {
		$this->application = $app;

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
			$this->_changed = [];
		}
		assert(count($this->_changed) === 0);
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
	 *            Relative and absolute path to template
	 * @param ?mixed $variables
	 * @return self
	 */
	public function begin(string $path, mixed $variables = null): self {
		if (str_ends_with($path, '.tpl')) {
			$this->application->logger->warning('{method} {path} ends with .tpl - now deprecated, use theme names only, called from {calling_function}', [
				'method' => __METHOD__, 'path' => $path, 'calling_function' => calling_function(0),
			]);
		} else {
			$path .= '.tpl';
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
	 * @param array $variables Optional variables to apply to the template
	 * @param string $content_variable
	 * @return string
	 * @throws Exception_Semantics|Exception_Redirect
	 */
	public function end(array $variables = [], string $content_variable = 'content'): string {
		if (count($this->wrappers) === 0) {
			throw new Exception_Semantics('Template::end when no template on the wrapper stack');
		}
		$t = array_pop($this->wrappers);
		/* @var $t Template */
		$t->pop();
		if (!$t->_path) {
			return '';
		}
		$variables[$content_variable] = ob_get_clean();
		$t->set($variables);
		return $t->render();
	}

	/**
	 * @return self
	 */
	public function push(): self {
		$top = $this->application->pushTemplate($this);
		$this->_vars += $top->variables();
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
		$top = $this->application->popTemplate();
		if ($top !== $this) {
			throw new Exception_Semantics("Popped template ($top->_path) not this ($this->_path)");
		}
		if (--$this->_running < 0) {
			throw new Exception_Semantics('Template::pop negative running');
		}
		/*
		 * If we have a stack and variables changed
		 */
		if (count($this->_changed) === 0) {
			return $this;
		}
		$this->application->topTemplate()->set($this->_changed);
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
	public function values(): array {
		return $this->_vars;
	}

	/**
	 * Find template path
	 *
	 * @param string $path
	 * @return string
	 */
	public function findPath(string $path): string {
		if (Directory::isAbsolute($path)) {
			if (file_exists($path)) {
				return $path;
			}
			return '';
		}

		try {
			return $this->application->themeFind($path, [
				'no_extension' => true,
			]);
		} catch (Exception_NotFound) {
			$themePaths = $this->application->themePath();
			if (self::$debug) {
				static $template_path = false;
				if (!$template_path) {
					$this->application->logger->debug("themePath is\n\t" . JSON::encodePretty($themePaths));
					$template_path = true;
				}
				$this->application->logger->warning('Template::path("{path}") not found in themePath ({n_paths} paths).', [
					'path' => $path, 'themePaths' => $themePaths, 'n_paths' => count($themePaths),
				]);
			}
			return '';
		}
	}

	/**
	 * Would this template exist?
	 *
	 * @param string $path
	 * @return boolean
	 */
	public function wouldExist(string $path): bool {
		$path = $this->findPath($path);
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
			$this->application->deprecated('setter/getter combo deprecated');
			return $this->setPath($set);
		}
		return $this->_path;
	}

	/**
	 * @param string $set Value to set the path to
	 * @return $this
	 */
	public function setPath(string $set): self {
		$this->_path = $this->findPath($set);
		return $this;
	}

	/**
	 * Does this template exist?
	 *
	 * @return boolean
	 */
	public function exists(): bool {
		return $this->_path && file_exists($this->_path);
	}

	/**
	 *
	 * @return string
	 */
	public function className(): string {
		return 'Template';
	}

	/**
	 * Template return code
	 *
	 * @return mixed
	 */
	public function result(): mixed {
		return $this->return;
	}

	/**
	 * @return string
	 */
	public function objectName(): string {
		try {
			$contents = File::contents($this->_path, '');
			$matches = [];
			if (!preg_match('/Name:\s*\"([^\"]+)\"/', $contents, $matches)) {
				return $matches[1];
			}
		} catch (Exception_File_NotFound|Exception_File_Permission) {
		}
		return basename($this->_path);
	}

	/**
	 * Did anything change in this Template?
	 *
	 * @return array
	 */
	public function changed(): array {
		return $this->_changed;
	}

	/**
	 * Is a variable set in this template (and non-null)?
	 *
	 * @param string|int $k
	 * @return bool
	 */
	public function has(string|int $k): bool {
		return $this->__isset($k);
	}

	/**
	 * Set a variable to the template
	 *
	 * @param array|string|Template|int $key Thing to set
	 * @param mixed $value Value to set (string or int $key only)
	 */
	public function set(array|string|int|Template $key, mixed $value = null): void {
		if (is_array($key)) {
			foreach ($key as $k0 => $v0) {
				$this->__set($k0, $v0);
			}
		} elseif ($key instanceof Template) {
			$this->inherit($key);
		} else {
			$this->__set($key, $value);
		}
	}

	/**
	 * Get a variable name, with a default
	 *
	 * @param string $k
	 * @param mixed $default
	 * @return mixed
	 */
	public function get(array|string|int $k, mixed $default = null): mixed {
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
	 * @param string|array $keys
	 * @param mixed $default
	 * @return mixed
	 */
	public function get1(string|array $keys, mixed $default = null): mixed {
		foreach (toList($keys) as $key) {
			if ($this->__isset($key)) {
				return $this->__get($key);
			}
		}
		return $default;
	}

	/**
	 * Get a value and convert it to an integer, or return $default
	 *
	 * @param string|int $key
	 * @param int $default
	 * @return integer
	 */
	public function getInt(string|int $key, int $default = 0): int {
		return toInteger($this->__get($key), $default);
	}

	/**
	 * Get a value and convert it to an boolean, or return $default
	 *
	 * @param string|int $k
	 * @param mixed $default
	 * @return boolean
	 */
	public function getBool(string|int $key, bool $default = false): bool {
		return toBool($this->__get($key), $default);
	}

	/**
	 * Get a value if it's an array, or return $default
	 *
	 * @param string|int $key
	 * @param mixed $default
	 * @return boolean
	 */
	public function getArray(string|int $key, array $default = []): array {
		$value = $this->__get($key);
		if (is_array($value)) {
			return $value;
		}
		return $default;
	}

	/**
	 * Output
	 *
	 * @return string
	 * @throws Exception_Redirect
	 */
	public function render(): string {
		if (!$this->_path) {
			return '';
		}
		$__start = microtime(true);
		ob_start();
		$this->push();
		extract([
			'application' => $this->application,
		] + $this->_vars, EXTR_SKIP); // Avoid overwriting $this
		// This name is fairly unique to avoid conflicts with variables set in our include.
		$_template_exception = null;

		try {
			$this->return = include($this->_path);
		} catch (Exception_Redirect $redirect) {
			throw $redirect;
		} catch (\Exception $_template_exception) {
			$alt_exception = $this->application->hooks->call('exception', $_template_exception);
			if ($alt_exception instanceof \Exception) {
				$_template_exception = $alt_exception;
			}
		}

		try {
			$this->pop();
		} catch (Exception_Semantics $e) {
			$this->application->logger->error('pop semantics error {path}', $this->variables());
		}
		$contents = ob_get_clean();
		if ($_template_exception) {
			$contents .= '<!-- TEMPLATE_EXCEPTION: ' . $_template_exception::class . ' -->';
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
	private static function _template_key(string $key): string {
		return strtolower($key);
	}

	/**
	 *
	 * @param Application $application
	 * @return void
	 */
	public static function configured(Application $application): void {
		$config = $application->configuration->path(__CLASS__);
		self::$profile = toBool($config->profile);
		self::$wrap = toBool($config->wrap);
		self::$debug = toBool($config->debug);
		self::$debug_stack = toBool($config->debug_stack);
		$application->hooks->add('</body>', [
			__CLASS__, 'profileOutput',
		]);
	}

	/**
	 *
	 * @return string
	 */
	public static function profileOutput($_, Response $response): string {
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
	 * @param array|Template|string|int $mixed
	 * @param $value When $mixed is a string, the value to set it to
	 * @return $this
	 */
	public function inherit(array|Template|string|int $mixed, mixed $value = null): self {
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
	 * @param string|int $key Key
	 * @param mixed $value Value
	 * @see stdClass::__set
	 */
	public function __set(string|int $key, mixed $value): void {
		$key = self::_template_key($key);
		if ($this->_running > 0) {
			$this->_changed[$key] = $value;
		}
		$this->_vars[$key] = $value;
	}

	/**
	 *
	 * @param string|int $key
	 * @return mixed
	 * @see stdClass::__get
	 */
	public function __get(string|int $key): mixed {
		$key = self::_template_key($key);
		if (array_key_exists($key, $this->_vars)) {
			return $this->_vars[$key];
		}
		return [
			'variables' => $this->_vars, 'self' => $this,
		][$key] ?? null;
	}

	/**
	 *
	 * @param string|int $key
	 * @return bool
	 * @see stdClass::__isset
	 */
	public function __isset(string|int $key): bool {
		$key = self::_template_key($key);
		return isset($this->_vars[$key]);
	}

	/**
	 *
	 * @param string|int $key
	 * @see stdClass::__unset
	 */
	public function __unset(string|int $key): void {
		$key = self::_template_key($key);
		unset($this->_vars[$key]);
	}

	/**
	 * @return string
	 */
	public function __toString(): string {
		return PHP::dump($this->_original_path);
	}

	/**
	 * Output theme within a template.
	 *
	 * @param array|string $types
	 *            Theme, or list of themes
	 * @param array $arguments
	 *            Arguments for the theme to render
	 * @param array $options
	 *            Extra options which effect how the theme request is interpreted
	 * @return string|null
	 * @throws Exception_Semantics
	 * @see Application::theme
	 */
	final public function theme(array|string $types, array $arguments = [], array $options = []): ?string {
		return $this->application->theme($types, $arguments, $options);
	}

	/**
	 * Create a widget
	 *
	 * @param string $class
	 * @param array $options
	 * @return Widget
	 */
	final public function widgetFactory(string $class, array $options = []): Widget {
		return $this->application->widgetFactory($class, $options);
	}

	/**
	 * Determine if theme exists
	 *
	 * 2016-01-12 Moving away from globals. Use in templates instead of zesk::theme or theme, both
	 * of which are now deprecated.
	 *
	 * @param mixed $types
	 *            Theme, or list of themes
	 * @param array $arguments
	 * @return bool
	 */
	final public function themeExists(string|array $types, array $arguments = []): bool {
		return $this->application->themeExists($types, $arguments);
	}
}
