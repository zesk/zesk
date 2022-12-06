<?php
declare(strict_types=1);

/**
 *
 */

namespace zesk;

/**
 *
 * @author kent
 *
 */
class Model extends Hookable implements \ArrayAccess, Interface_Factory {
	/**
	 * Option for theme path prefix for themes associated with this model
	 *
	 * @var string
	 */
	public const option_theme_path_prefix = 'theme_path_prefix';

	/**
	 *
	 * @var boolean
	 */
	protected $_inited = false;

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
				$this->__set($k, $v);
			}
		}
		$this->callHook('construct');
	}

	/**
	 *
	 * @param string $class
	 * @param mixed $value
	 * @return Model
	 */
	public static function factory(Application $application, string $class, mixed $value = null, array $options = []): Model {
		$object = $application->factory($class, $application, $value, $options);
		assert($object instanceof Model);
		return $object->polymorphicChild();
	}

	/**
	 * Create a model in the context of the current model
	 *
	 * @param $class string
	 *            Class to create
	 * @param $mixed mixed
	 *            ID or array to intialize object
	 * @param $options array
	 *            Additional options for object
	 * @return self
	 */
	public function modelFactory(string $class, mixed $mixed = null, array $options = []): Model {
		return self::factory($this->application, $class, $mixed, $options);
	}

	/**
	 * Convert to true form, should be subclass of current class.
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
	 * @return mixed
	 */
	public function id(): mixed {
		return null;
	}

	/**
	 * Get/set the ID for this model
	 *
	 * @param mixed $set
	 * @return self
	 */
	public function setId(mixed $set): self {
		throw new Exception_Unimplemented('Model of {class} does not support setting ID', [
			'class' => get_class($this),
		]);
	}

	/**
	 * Is this a new object?
	 *
	 * @return boolean
	 */
	public function isNew(): bool {
		return $this->inited();
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
		$result['_parent_class'] = get_parent_class($this);
		return $result;
	}

	/**
	 * Convert values in this object with map
	 */
	public function map(array $map): self {
		$this->set(map($this->variables(), $map));
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
		if (is_bool($mixed) || is_null($mixed) || is_numeric($mixed) || is_resource($mixed)) {
			return $mixed;
		}
		return map($mixed, $this->variables());
	}

	/**
	 * ArrayAccess offsetExists
	 *
	 * @param mixed $offset
	 * @return boolean
	 */
	public function offsetExists($offset): bool {
		return $this->__isset($offset);
	}

	/**
	 * ArrayAccess offsetGet
	 *
	 * @param mixed $offset
	 * @return int
	 */
	public function offsetGet($offset): int {
		return $this->__get($offset);
	}

	/**
	 * ArrayAccess offsetSet
	 *
	 * @param mixed $offset
	 * @param mixed $value
	 * @return void
	 */
	public function offsetSet($offset, mixed $value): void {
		$this->__set($offset, $value);
	}

	/**
	 * ArrayAccess offsetUnset
	 *
	 * @param mixed $offset
	 * @return void
	 */
	public function offsetUnset($offset): void {
		$this->__unset($offset);
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
	 * @return self Or null if can not be found
	 */
	public function fetch(array $mixed = []): self {
		return $this;
	}

	/**
	 *
	 * @param mixed $mixed
	 *            Model value to retrieve
	 * @param mixed $default
	 *            Value to return if not found
	 * @return mixed
	 */
	public function getMultiple(array $mixed) {
		$result = [];
		foreach ($mixed as $k => $v) {
			$result[$k] = $this->__get($k, $v);
		}
		return $result;
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
	 * @param iterable $mixed
	 * @return bool
	 */
	public function hasAny(iterable $mixed): bool {
		foreach ($mixed as $k) {
			if ($this->has($k)) {
				return true;
			}
		}
		return false;
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
	public function inited(): bool {
		return $this->_inited;
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
		$this->$key = $value;
		$this->_inited = true;
	}

	/**
	 *
	 * @param string $key
	 */
	public function __unset(string $key): void {
		unset($this->$key);
	}

	/**
	 * Is a member set?
	 *
	 * @param string $key
	 * @return boolean
	 */
	public function __isset(string $key): bool {
		return isset($this->$key);
	}

	/**
	 * Convert a theme name (or names) into clean paths for finding theme templates
	 *
	 * @param string $name
	 * @return string
	 */
	private static function _clean_theme_name(string $name): string {
		return strtr(strtolower($name), [
			'_' => '/',
			'\\' => '/',
		]);
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
	 * @param string|array|null $theme_name
	 * @return string
	 */
	public function theme_paths(string|array $theme_names = null): array {
		if ($theme_names === null) {
			$theme_names = [
				$this->option('default_theme', 'view'),
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
		} elseif (!is_array($theme_names)) {
			return [];
		}
		$result = [];
		foreach ($this->application->classes->hierarchy(get_class($this), __CLASS__) as $class) {
			$result = array_merge($result, ArrayTools::prefixValues($theme_names, $class . '/'));
		}
		if ($this->hasOption(self::option_theme_path_prefix)) {
			$result_prefix = ArrayTools::prefixValues($result, rtrim($this->option(self::option_theme_path_prefix), '/') . '/');
			$result = array_merge($result_prefix, $result);
		}
		return array_map([__CLASS__, '_clean_theme_name'], $result);
	}

	/**
	 * Output this
	 *
	 * @param string $theme_name
	 *            Theme or list of themes to invoke (first found is used)
	 * @param array $variables
	 *            Variables to be passed to the template
	 * @param string $default
	 *            Default value if no theme is found
	 * @return string
	 */
	public function theme(array|string $theme_name = null, string|array $variables = null, string $default = '') {
		$variables = is_string($variables) ? [
			'content' => $variables,
		] : (array) $variables;
		$variables += [
			'object' => $this,
			strtolower(get_class($this)) => $this,
		];
		return $this->application->theme($this->theme_paths($theme_name), $variables, [
			'default' => $default,
			'first' => true,
		]);
	}

	/**
	 * Convert a variable to an ID. IDs may be arrays.
	 *
	 * @param $mixed mixed
	 * @return mixed
	 */
	public static function mixed_to_id(mixed $mixed): mixed {
		if ($mixed instanceof Model) {
			return $mixed->id();
		}
		return to_integer($mixed);
	}

	/**
	 * Is this a new object?
	 *
	 * @return boolean
	 * @deprecated 2022-05
	 */
	public function is_new(): bool {
		return $this->isNew();
	}
}
