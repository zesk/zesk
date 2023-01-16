<?php
declare(strict_types=1);

/**
 *
 */

namespace zesk;

use ArrayAccess;
use TypeError;

/**
 *
 * @author kent
 *
 */
class Model extends Hookable implements ArrayAccess, Interface_Factory {
	public const OPTION_DEFAULT_THEME = 'default_theme';

	/**
	 *
	 */
	public const DEFAULT_OPTION_DEFAULT_THEME = 'view';

	/**
	 * Option for theme path prefix for themes associated with this model
	 *
	 * @var string
	 */
	public const OPTION_THEME_PATH_PREFIX = 'themePathPrefix';

	/**
	 *
	 * @var boolean
	 */
	protected bool $_initialized = false;

	/**
	 *
	 * @param mixed $mixed
	 * @param array $options
	 * @param Application $application
	 */
	public function __construct(Application $application, $mixed = null, array $options = []) {
		parent::__construct($application, $options);
		if (is_array($mixed)) {
			foreach ($mixed as $k => $v) {
				if (!str_starts_with($k, '_')) {
					$this->__set($k, $v);
				}
			}
		}
		$this->callHook('construct');
	}

	/**
	 *
	 * @param Application $application
	 * @param string $class
	 * @param mixed $value
	 * @param array $options
	 * @return Model
	 * @throws Exception_Class_NotFound
	 */
	public static function factory(Application $application, string $class, mixed $value = null, array $options = []): Model {
		$object = $application->factoryArguments($class, [$application, $value, $options]);
		assert($object instanceof Model);
		return $object->polymorphicChild();
	}

	/**
	 * Create a model in the context of the current model
	 *
	 * @param $class string
	 *            Class to create
	 * @param $mixed mixed
	 *            ID or array to initialize object
	 * @param $options array
	 *            Additional options for object
	 * @return self
	 * @throws Exception_Class_NotFound
	 */
	public function modelFactory(string $class, mixed $mixed = null, array $options = []): Model {
		return self::factory($this->application, $class, $mixed, $options);
	}

	/**
	 * Convert to true form, MUST BE a subclass of current class
	 *
	 * Override in subclasses to get custom polymorphic behavior.
	 *
	 * @return self
	 */
	protected function polymorphicChild(): self {
		return $this;
	}

	/**
	 * Get/set the ID for this model
	 *
	 * @return int|string|array
	 */
	public function id(): int|string|array {
		return 0;
	}

	/**
	 * Get/set the ID for this model
	 *
	 * @param int|string|array $set
	 * @return self
	 */
	public function setId(int|string|array $set): self {
		return $this;
	}

	/**
	 * Is this a new object?
	 *
	 * @return boolean
	 */
	public function isNew(): bool {
		return $this->initialized();
	}

	/**
	 * Variables to apply to a template, for example
	 *
	 * @return array
	 */
	public function variables(): array {
		$result = [];
		foreach (get_object_vars($this) as $k => $v) {
			if ($k[0] !== '_') {
				$result[$k] = $v;
			}
		}
		$result['_class'] = get_class($this);
		$result['_parentClass'] = get_parent_class($this);
		return $result;
	}

	/**
	 * Convert values in this object with map
	 */
	public function map(array $map): self {
		foreach (map($this->variables(), $map) as $key => $value) {
			if (!str_starts_with($key, '_')) {
				$this->__set($key, $value);
			}
		}
		return $this;
	}

	/**
	 * Convert other values using this Model as the map
	 *
	 * Returns type passed in
	 */
	final public function applyMap(mixed $mixed): mixed {
		if ($mixed instanceof Model) {
			return $mixed->map($this->variables());
		}
		if (is_string($mixed) || is_array($mixed)) {
			return map($mixed, $this->variables());
		}
		return $mixed;
	}

	/**
	 * ArrayAccess offsetExists
	 *
	 * @param mixed $offset
	 * @return boolean
	 */
	public function offsetExists($offset): bool {
		return $this->__isset(toKey($offset));
	}

	/**
	 * ArrayAccess offsetGet
	 *
	 * @param mixed $offset
	 * @return mixed
	 */
	public function offsetGet($offset): mixed {
		return $this->__get(toKey($offset));
	}

	/**
	 * ArrayAccess offsetSet
	 *
	 * @param mixed $offset
	 * @param mixed $value
	 * @return void
	 */
	public function offsetSet($offset, mixed $value): void {
		$this->__set(toKey($offset), $value);
	}

	/**
	 * ArrayAccess offsetUnset
	 *
	 * @param mixed $offset
	 * @return void
	 */
	public function offsetUnset($offset): void {
		$this->__unset(toKey($offset));
	}

	/**
	 *
	 * @return self
	 */
	public function store(): self {
		return $this;
	}

	/**
	 *
	 * @param mixed $mixed
	 *            Settings to retrieve a model from somewhere
	 * @return self
	 */
	public function fetch(array $mixed = []): self {
		return $this;
	}

	/**
	 * @param string $mixed
	 * @param mixed|null $default
	 * @return mixed
	 */
	public function get(string $mixed, mixed $default = null): mixed {
		if (!$this->__isset($mixed)) {
			return $default;
		}
		return $this->__get($mixed);
	}

	/**
	 * Does this model have any of the members requested?
	 *
	 * @param string|iterable $mixed
	 * @return bool
	 */
	public function hasAny(string|iterable $mixed): bool {
		foreach (toIterable($mixed) as $k) {
			if ($this->__isset(toKey($k))) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Does this have all members?
	 *
	 * @param string|iterable $mixed
	 * @return bool
	 */
	public function hasAll(string|iterable $mixed): bool {
		foreach (toIterable($mixed) as $k) {
			if (!$this->__isset(toKey($k))) {
				return false;
			}
		}
		return true;
	}

	/**
	 * Does this model have a member?
	 *
	 * @param string $member
	 * @return boolean For a list, if ANY member exists, returns true.
	 */
	public function has(string $member): bool {
		return $this->__isset($member);
	}

	/**
	 * Returns true if model has been initialized with valid values
	 *
	 * @return boolean
	 */
	public function initialized(): bool {
		return $this->_initialized;
	}

	/**
	 * @param string $mixed
	 * @param mixed|null $value
	 * @return $this
	 */
	public function set(string $mixed, mixed $value = null): self {
		$this->__set($mixed, $value);
		return $this;
	}

	/**
	 * Convert to string
	 *
	 * {@inheritdoc}
	 *
	 * @see Options::__toString()
	 */
	public function __toString(): string {
		return PHP::dump($this->options);
	}

	/**
	 * @param string $key
	 * @return mixed
	 */
	public function __get(string $key): mixed {
		return $this->$key ?? null;
	}

	/**
	 *
	 * {@inheritdoc}
	 *
	 * @see Options::__set()
	 */
	public function __set(string $key, mixed $value): void {
		try {
			$this->$key = $value;
			$this->_initialized = true;
		} catch (TypeError) {
			PHP::log('Unable to set {class}::{key} to type {type}', [
				'class' => $this::class, 'key' => $key, 'type' => type($value),
			]);
		}
	}

	/**
	 *
	 * @param string $key
	 */
	public function __unset(string $key): void {
		try {
			unset($this->$key);
		} catch (TypeError) {
			PHP::log('Unable to unset {class}::{key} to null', [
				'class' => $this::class, 'key' => $key,
			]);
		}
	}

	/**
	 * Is a member set?
	 *
	 * @param int|string $key
	 * @return boolean
	 */
	public function __isset(int|string $key): bool {
		return isset($this->$key);
	}

	/**
	 * Given a theme name, return the theme paths which are checked.
	 *
	 * Uses the class name and extrapolates within the theme search path, so:
	 *
	 * User_Role and $theme_name = "view" then searches for
	 *
	 * <code>
	 * user/role/view.tpl
	 * </code>
	 *
	 * In the theme path. If the theme_name begins with a slash or a period, no conversion is done.
	 *
	 * @param string|array $theme_names
	 * @return array
	 */
	public function themePaths(string|array $theme_names = ''): array {
		if ($theme_names === '') {
			$theme_names = [
				$this->option(self::OPTION_DEFAULT_THEME, self::DEFAULT_OPTION_DEFAULT_THEME),
			];
		} elseif (is_string($theme_names)) {
			if ($theme_names[0] === '/' || $theme_names[0] === '.') {
				return [
					$theme_names,
				];
			}
			$theme_names = [
				$theme_names,
			];
		}
		$result = [];
		foreach ($this->application->classes->hierarchy(get_class($this), __CLASS__) as $class) {
			$result = array_merge($result, ArrayTools::prefixValues($theme_names, $class . '/'));
		}
		if ($this->hasOption(self::OPTION_THEME_PATH_PREFIX)) {
			$result_prefix = ArrayTools::prefixValues($result, rtrim($this->option(self::OPTION_THEME_PATH_PREFIX), '/') . '/');
			$result = array_merge($result_prefix, $result);
		}
		return array_map(function ($name) {
			return strtr($name, [
				'_' => '/', '\\' => '/',
			]);
		}, $result);
	}

	/**
	 * Output this
	 *
	 * @param array|string $theme_names
	 *            Theme or list of themes to invoke (first found is used)
	 * @param array|string $variables
	 *            Variables to be passed to the template.
	 * @param string $default
	 *            Default value if no theme is found
	 * @return ?string
	 * @throws Exception_Redirect
	 */
	public function theme(array|string $theme_names = '', string|array $variables = [], string $default = ''): ?string {
		$variables = is_string($variables) ? [
			'content' => $variables,
		] : $variables;
		$variables += [
			'object' => $this, strtolower(get_class($this)) => $this,
		];
		return $this->application->themes->theme($this->themePaths($theme_names), $variables, [
			'default' => $default, 'first' => true,
		]);
	}

	/**
	 * Convert a variable to an ID. IDs may be arrays.
	 *
	 * @param $mixed mixed
	 * @return int|string|array
	 * @throws Exception_Convert
	 */
	public static function mixedToID(mixed $mixed): int|string|array {
		if ($mixed instanceof Model) {
			return $mixed->id();
		}
		if (is_numeric($mixed)) {
			return toInteger($mixed);
		}
		if (is_string($mixed)) {
			return $mixed;
		}

		throw new Exception_Convert('Unable to convert {mixed} ({type}) to ID', [
			'mixed' => $mixed, 'type' => type($mixed),
		]);
	}
}
