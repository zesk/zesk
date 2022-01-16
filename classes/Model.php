<?php declare(strict_types=1);

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
	public const option_theme_path_prefix = "theme_path_prefix";

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
		$this->call_hook("construct");
	}

	/**
	 *
	 * @param string $class
	 * @param mixed $value
	 * @throws Exception_Semantics
	 * @return self
	 */
	public static function factory(Application $application, string $class, mixed $value = null, array $options = []):
	?Model {
		$object = $application->factory($class, $application, $value, $options);
		if (!$object instanceof Model) {
			throw new Exception_Semantics("{method}({class}) is not a subclass of {object_class}", [
				"method" => __METHOD__,
				"class" => $class,
				"object_class" => __CLASS__,
			]);
		}
		return $object->polymorphic_child();
	}

	/**
	 * Create a model in the context of the current model
	 *
	 * @param $class string
	 *        	Class to create
	 * @param $mixed mixed
	 *        	ID or array to intialize object
	 * @param $options array
	 *        	Additional options for object
	 * @return self
	 */
	public function model_factory(string $class, mixed $mixed = null, array $options = []): self {
		return self::factory($this->application, $class, $mixed, $options);
	}

	/**
	 * Convert to true form, should be subclass of current class.
	 *
	 * Override in subclasses to get custom polymorphic behavior.
	 *
	 * @return self
	 */
	protected function polymorphic_child(): self {
		return $this;
	}

	/**
	 * Get/set the ID for this model
	 *
	 * @param mixed $set
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
		throw new Exception_Unimplemented("Model of {class} does not support setting ID", [
			"class" => get_class($this),
		]);
	}

	/**
	 * Is this a new object?
	 *
	 * @return boolean
	 */
	public function is_new(): bool {
		return $this->isNew();
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
	final public function apply_map(mixed $mixed): mixed {
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
	public function offsetSet($offset, $value): void {
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
	 *        	Settings to retrieve a model from somewhere
	 * @return self Or null if can not be found
	 */
	public function fetch(array $mixed = []): self {
		return $this;
	}

	/**
	 *
	 * @param mixed $mixed
	 *        	Model value to retrieve
	 * @param mixed $default
	 *        	Value to return if not found
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
	 * @param mixed|null $mixed
	 * @param mixed|null $default
	 * @return array|mixed|null
	 * @throws Exception_Parameter
	 */
	public function get(mixed $mixed = null, mixed $default = null) {
		if (is_array($mixed)) {
			$this->application->deprecated("getMultiple instead");
			return $this->getMultiple($mixed);
		}
		if (!is_scalar($mixed)) {
			throw new Exception_Parameter("Not sure how to handle type " . gettype($mixed));
		}
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
	 * @param list|string $mixed
	 * @return boolean For a list, if ANY member exists, returns true.
	 */
	public function has(mixed $mixed = null): bool {
		if (is_array($mixed)) {
			$this->application->deprecated("hasAny instead");
			return $this->hasAny($mixed);
		}
		if (is_scalar($mixed)) {
			return $this->__isset($mixed);
		}
		return false;
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
	 *
	 * @param mixed $mixed
	 *        	Model value to set
	 * @param mixed $value
	 *        	Value to set
	 * @return self $this
	 */
	public function set($mixed, $value = null) {
		if (is_array($mixed)) {
			foreach ($mixed as $k => $v) {
				$this->set($k, $v);
			}
		} elseif (is_object($mixed)) {
			$this->set(get_class($mixed), $mixed);
		} elseif (is_scalar($mixed) && !empty($mixed)) {
			$this->__set($mixed, $value);
		} else {
			throw new Exception_Parameter("Model::set({mixed}, {value}) Not sure how to handle 1st parameter", [
				"mixed" => JSON::encode($mixed),
				"value" => PHP::dump($value),
			]);
		}
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

	/*
	 * Only place to access ->$name is here
	 */
	public function __get($name): mixed {
		return $this->$name ?? null;
	}

	/**
	 *
	 * {@inheritdoc}
	 *
	 * @see Options::__set()
	 */
	public function __set(string $name, mixed $value): void {
		$this->$name = $value;
		$this->_inited = true;
	}

	/**
	 *
	 * @param string $name
	 */
	public function __unset($name): void {
		unset($this->$name);
	}

	/**
	 * Is a member set?
	 *
	 * @param string $name
	 * @return boolean
	 */
	public function __isset($name): bool {
		return isset($this->$name);
	}

	/**
	 * Convert a theme name (or names) into clean paths for finding theme templates
	 *
	 * @param list|string $name
	 * @return list|string Cleaned theme names
	 */
	private static function _clean_theme_name($name) {
		if (is_array($name)) {
			foreach ($name as $k => $n) {
				$name[$k] = self::_clean_theme_name($n);
			}
			return $name;
		}
		return strtr(strtolower($name), [
			"_" => "/",
			"\\" => "/",
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
	 * @param string $theme_name
	 * @return string
	 */
	public function theme_paths($theme_names) {
		if ($theme_names === null) {
			$theme_names = [
				$this->option("default_theme", "view"),
			];
		} elseif (is_string($theme_names)) {
			if ($theme_names[0] === "/" || $theme_names[0] === ".") {
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
		$hier = $this->application->classes->hierarchy(get_class($this), __CLASS__);
		foreach ($hier as $class) {
			$result = array_merge($result, ArrayTools::prefix($theme_names, $class . "/"));
		}
		if ($this->hasOption(self::option_theme_path_prefix)) {
			$result_prefix = ArrayTools::prefix($result, rtrim($this->option(self::option_theme_path_prefix), "/") . "/");
			$result = array_merge($result_prefix, $result);
		}

		return self::_clean_theme_name($result);
	}

	/**
	 * Output this
	 *
	 * @param string $theme_name
	 *        	Theme or list of themes to invoke (first found is used)
	 * @param array $variables
	 *        	Variables to be passed to the template
	 * @param string $default
	 *        	Default value if no theme is found
	 * @return string
	 */
	public function theme($theme_name = null, $variables = null, $default = "") {
		$variables = is_string($variables) ? [
			'content' => $variables,
		] : (array) $variables;
		$variables += [
			'object' => $this,
			strtolower(get_class($this)) => $this,
		];
		return $this->application->theme($this->theme_paths($theme_name), $variables, [
			"default" => $default,
			"first" => true,
		]);
	}

	/**
	 * Convert a variable to an ID
	 *
	 * @param $mixed mixed
	 * @return integer or null if can't be converted to integer
	 */
	public static function mixed_to_id($mixed) {
		if ($mixed instanceof Model) {
			return $mixed->id();
		}
		return to_integer($mixed, null);
	}
}
