<?php
declare(strict_types=1);
/**
 * @package zesk
 * @subpackage core
 * @author kent
 * @copyright Copyright &copy; 2022, Market Acumen, Inc.
 */

namespace zesk;

use Psr\Log\LoggerInterface;

/**
 * Everything theme-related
 */
class Themes implements Interface_Theme {
	/**
	 * @var bool
	 */
	public bool $debug = false;

	/**
	 * Top template
	 *
	 * @var Template
	 */
	public Template $template;

	/**
	 * Template stack. public so it can be copied in Template::__construct
	 *
	 * @see Template::__construct
	 * @var Template_Stack
	 */
	public Template_Stack $template_stack;

	/**
	 * Paths to search for themes
	 *
	 * @var array $themePath
	 */
	private array $themePath;

	/**
	 *
	 * @var string[]
	 */
	private array $theme_stack;

	public function __construct() {
		$this->themePath = [];
		$this->theme_stack = [];

		$this->template = new Template($this);
		$this->template_stack = new Template_Stack();

		$this->template_stack->push($this->template);
	}

	/**
	 * Clone application
	 */
	protected function __clone() {
		$this->template_stack = clone $this->template_stack;
		$this->template = $this->template_stack->bottom();
	}

	/**
	 * Add a path to be searched before existing paths
	 * (first in the list).
	 *
	 * @param array|string $paths
	 *            Path to add to the theme path. Pass in null to do nothing.
	 * @param string $prefix
	 *            (Optional) Handle theme requests which begin with this prefix. Saves having deep
	 *            directories.
	 * @return self
	 * @throws Exception_Directory_NotFound
	 */
	final public function addThemePath(array|string $paths, string $prefix = ''): self {
		if (is_array($paths)) {
			foreach ($paths as $k => $v) {
				if (is_numeric($k)) {
					$this->addThemePath($v);
				} else {
					$this->addThemePath($v, $k);
				}
			}
		} else {
			if (!isset($this->themePath[$prefix])) {
				$this->themePath[$prefix] = [];
			}
			Directory::must($paths);
			if (!in_array($paths, $this->themePath[$prefix])) {
				array_unshift($this->themePath[$prefix], $paths);
			}
		}
		return $this;
	}

	/**
	 * @param array $variables
	 * @return void
	 */
	final public function setVariables(array $variables): void {
		$this->template->set($variables);
	}

	/**
	 * Invoke a single theme type
	 *
	 * @param string $type
	 * @param array $args
	 * @param ?string $content Default content
	 * @param string $extension
	 * @return ?string
	 * @throws Exception_Redirect
	 */
	private function _themeArguments(string $type, array $args, string $content = null, string $extension = '.tpl'): ?string {
		$this->theme_stack[] = $type;
		$t = new Template($this, $this->cleanTemplatePath($type) . $extension, $args);
		if ($t->exists()) {
			$content = $t->render();
		}
		array_pop($this->theme_stack);
		return $content;
	}

	/**
	 * Convert from a theme name to a pathname
	 *
	 * @param string $path
	 * @return mixed
	 */
	private function cleanTemplatePath(string $path): string {
		return preg_replace('%[^-_./a-zA-Z0-9]%', '_', strtr($path, [
			'_' => '/', '\\' => '/',
		]));
	}

	/**
	 * @param $theme
	 * @param array $options
	 * @return string
	 * @throws Exception_NotFound
	 */
	final public function themeFind($theme, array $options = []): string {
		[$result] = $this->themeFindAll($theme, $options);
		if (count($result) > 0) {
			return $result[0];
		}

		throw new Exception_NotFound($theme);
	}

	/**
	 * Search the theme paths for a target file
	 *
	 * @param $theme
	 * @param array $options
	 * @return array[]
	 */
	final public function themeFindAll($theme, array $options = []): array {
		$extension = toBool($options['no_extension'] ?? false) ? '' : ($options['theme_extension'] ?? '.tpl');
		$all = toBool($options['all'] ?? true);
		$theme = $this->cleanTemplatePath($theme) . $extension;
		$themePath = $this->themePath();
		$prefixes = array_keys($themePath);
		usort($prefixes, fn ($a, $b) => strlen($b) - strlen($a));
		$result = [];
		$tried_path = [];
		foreach ($prefixes as $prefix) {
			if ($prefix === '' || str_starts_with($theme, $prefix)) {
				$suffix = substr($theme, strlen($prefix));
				foreach ($themePath[$prefix] as $path) {
					$path = path($path, $suffix);
					if (file_exists($path)) {
						$result[] = $path;
						if (!$all) {
							return [
								$result, $tried_path,
							];
						}
					} else {
						$tried_path[] = $path;
					}
				}
			}
		}
		return [
			$result, $tried_path,
		];
	}

	/**
	 * @return array
	 */
	final public function themePath(): array {
		return $this->themePath;
	}

	public null|LoggerInterface $logger = null;

	/**
	 * theme an element
	 *
	 * @param string|array $types
	 * @param array $arguments
	 * @param array $options
	 * @return string|null
	 * @throws Exception_Redirect
	 */
	final public function theme(string|array $types, array $arguments = [], array $options = []): ?string {
		if (!is_array($arguments)) {
			$arguments = [
				'content' => $arguments,
			];
		} elseif (ArrayTools::isList($arguments) && count($arguments) > 0) {
			$arguments['content'] = first($arguments);
		}

		$types = toList($types);
		$extension = ($options['no_extension'] ?? false) ? null : '.tpl';
		if (count($types) === 1) {
			$result = $this->_themeArguments($types[0], $arguments, null, $extension);
			if ($result === null) {
				$this->logger?->warning('Theme {type} had no output', [
					'type' => $types[0],
				]);
			}
			return $result;
		}
		if (count($types) === 0) {
			return $option['default'] ?? null;
		}
		$type = array_shift($types);
		$arguments['content_previous'] = null;
		$has_output = false;
		$content = $this->_themeArguments($type, $arguments, null, $extension);
		if (!is_array($types)) {
			// Something's fucked.
			return $content;
		}
		if ($content !== null) {
			$arguments['content'] = $content;
			$has_output = true;
		}
		$first = $options['first'] ?? false;
		$concatenate = $options['concatenate'] ?? false;
		// 2019-01-15 PHP 7.2 $types converts to a string with value "[]" upon throwing a foreign Exception and rendering the theme
		while (is_countable($types) && count($types) > 0) {
			if ($first && !empty($content)) {
				break;
			}
			$type = array_shift($types);
			$content_previous = $content;
			$content_next = $this->_themeArguments($type, $arguments, $content, $extension);
			if ($content_next !== null) {
				$has_output = true;
			}
			$content = $concatenate ? $content . $content_next : $content_next;
			$arguments['content_previous'] = $content_previous;
			$arguments['content'] = $content;
		}
		if (!$has_output) {
			$this->logger?->warning('Theme {types} had no output ({details})', [
				'types' => $types, 'details' => _backtrace(),
			]);
		}
		return $content;
	}

	/**
	 *
	 * @return ?string
	 */
	final public function themeCurrent(): ?string {
		return last($this->theme_stack);
	}

	/**
	 * @param Template $t
	 * @return Template parent template
	 */
	public function pushTemplate(Template $t): Template {
		$top = $this->template_stack->top();
		if ($this->debug) {
			$this->logger?->debug('Push {path}', [
				'path' => $t->path(),
			]);
		}
		$this->template_stack->push($t);
		return $top;
	}

	/**
	 * @return Template
	 */
	public function topTemplate(): Template {
		return $this->template_stack->top();
	}

	/**
	 * @return Template
	 * @throws Exception_Semantics
	 */
	public function popTemplate(): Template {
		$top = $this->template_stack->pop();
		if ($this->debug) {
			$this->logger?->debug('Pop {path}', [
				'path' => $top->path(),
			]);
		}
		return $top;
	}

	/**
	 * Get top theme variables state
	 *
	 * @return array
	 */
	final public function themeVariables(): array {
		return $this->template_stack->top()->variables();
	}

	/**
	 * Getter/setter for top theme variable
	 * @param string $name
	 * @return mixed
	 */
	final public function themeVariable(string $name): mixed {
		return $this->template_stack->top()->get($name);
	}

	/**
	 * Setter for top theme variable
	 *
	 * @param array|string|int|Template $key
	 * @param mixed|null $value
	 * @return $this
	 */
	final public function setThemeVariable(array|string|int|Template $key, mixed $value = null): self {
		$this->template_stack->top()->set($key, $value);
		return $this;
	}

	/**
	 * Does one or more themes exist?
	 *
	 * @param mixed $types
	 *            List of themes
	 * @return boolean If all exist, returns true, otherwise false
	 */
	final public function themeExists(array|string $types, array $args = []): bool {
		if (empty($types)) {
			return false;
		}
		foreach (toList($types) as $type) {
			if (!$this->_themeExists($type, $args)) {
				return false;
			}
		}
		return true;
	}

	/**
	 * Returns similar result as _theme_arguments except just tests to see if theme would
	 * possibly generate content
	 *
	 * @param mixed $type
	 * @param array $args
	 * @return bool
	 */
	private function _themeExists(string $type, array $args): bool {
		$type = strtolower($type);
		$object = $args['content'] ?? null;
		if (is_object($object) && method_exists($object, 'hook_theme')) {
			return true;
		}
		// TODO is this called?
		try {
			if ($this->themeFind($type)) {
				return true;
			}
		} catch (Exception_NotFound) {
		}
		return false;
	}
}
