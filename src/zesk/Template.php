<?php
declare(strict_types=1);

/**
 *
 */

namespace zesk;

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
	use GetTyped;

	/**
	 *
	 * @var Themes
	 */
	public Themes $themes;

	/**
	 *
	 * @var Template_Stack
	 */
	protected Template_Stack $stack;

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
	protected string $_id = '';

	/**
	 *
	 * @var string
	 */
	protected string $_path = '';

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
	 * @param Themes $app
	 * @param string $path
	 * @param array|Template $variables
	 * @return self
	 */
	public static function factory(Themes $app, string $path = '', array|self $variables = []): self {
		return new self($app, $path, $variables);
	}

	/**
	 * Construct a new template
	 *
	 * @param Themes $app
	 * @param string $path
	 *            Relative or absolute path to template
	 * @param ?mixed $variables
	 *            Name/Value pairs to be set in the template execution
	 */
	public function __construct(Themes $app, string $path = '', array|self $variables = null) {
		$this->themes = $app;

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
		if ($path !== '') {
			$this->_original_path = $path;
			$this->setPath($path);
		}
		if (self::$profile) {
			ArrayTools::increment(self::$_stats['counts'], $this->_path);
		}
	}

	/**
	 * The short ID for the template, uniquely identifies it as requested
	 *
	 * @return string
	 */
	public function id(): string {
		return $this->_id;
	}

	/**
	 * Start output buffering for a \"theme\", push it on the stack.
	 *
	 * @param string $path
	 *            Relative and absolute path to template
	 * @param ?mixed $variables
	 * @return self
	 */
	public function begin(string $path, mixed $variables = null): self {
		$path .= '.tpl';
		$this->wrappers[] = $t = new Template($this->themes, $path, $variables);
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
		$top = $this->themes->pushTemplate($this);
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
		$top = $this->themes->popTemplate();
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
		$this->themes->topTemplate()->set($this->_changed);
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
	 * @return string Found path
	 */
	public function findPath(string $path): string {
		[$path] = $this->_findPath($path);
		return $path;
	}

	/**
	 * Find template path
	 *
	 * @param string $path
	 * @return array In the form `["path", "id"]`
	 */
	protected function _findPath(string $path): array {
		if (Directory::isAbsolute($path)) {
			$id = '';
			if (file_exists($path)) {
				return [$path, $id];
			}
			return ['', $id];
		}

		try {
			$result = $this->themes->themeFind($path, [
				'no_extension' => true,
			]);
			return [$result, $path];
		} catch (Exception_NotFound) {
			$themePaths = $this->themes->themePath();
			if (self::$debug) {
				static $template_path = false;
				if (!$template_path) {
					$this->themes->logger?->debug("themePath is\n\t" . JSON::encodePretty($themePaths));
					$template_path = true;
				}
				$this->themes->logger?->warning('Template::path("{path}") not found in themePath ({n_paths} paths).', [
					'path' => $path, 'themePaths' => $themePaths, 'n_paths' => count($themePaths),
				]);
			}
			return ['', ''];
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
	 * @return string
	 */
	public function path(): string {
		return $this->_path;
	}

	/**
	 * @param string $set Value to set the path to
	 * @return $this
	 */
	public function setPath(string $set): self {
		[$this->_path, $this->_id] = $this->_findPath($set);
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
			$contents = File::contents($this->_path);
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
			'themes' => $this->themes,
		] + $this->_vars, EXTR_SKIP); // Avoid overwriting $this
		// This name is fairly unique to avoid conflicts with variables set in our include.
		$_template_exception = null;

		try {
			$this->return = include($this->_path);
		} catch (Exception_Redirect $redirect) {
			throw $redirect;
		} catch (\Exception $alt_exception) {
			$_template_exception = $alt_exception;
		}

		try {
			$this->pop();
		} catch (Exception_Semantics) {
			$this->themes->logger->error('pop semantics error {path}', $this->variables());
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
		], ['overwrite' => true, 'id' => __METHOD__]);
	}

	/**
	 *
	 * @param Request $request
	 * @param Response $response
	 * @return string
	 * @throws Exception_Redirect
	 */
	public static function profileOutput(Request $request, Response $response): string {
		if (!self::$profile) {
			return '';
		}
		$app = $response->application;
		return $app->themes->theme('template/profile', self::$_stats + ['request' => $request]);
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
	 * @param mixed $value When $mixed is a string or int, the value to set
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
	 * @throws Exception_Redirect
	 * @see Application::theme
	 */
	final public function theme(array|string $types, array $arguments = [], array $options = []): ?string {
		return $this->themes->theme($types, $arguments, $options);
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
	 * @throws Exception_NotFound
	 */
	final public function themeExists(string|array $types, array $arguments = []): bool {
		return $this->themes->themeExists($types, $arguments);
	}
}
