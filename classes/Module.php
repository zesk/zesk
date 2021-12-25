<?php declare(strict_types=1);

/**
 * @package zesk
 * @subpackage system
 * @author kent
 * @copyright Copyright &copy; 2016, Market Acumen, Inc.
 */
namespace zesk;

/**
 * Module base class for all extensions to Zesk
 *
 * @see Modules
 * @author kent
 */
class Module extends Hookable {
	/**
	 *
	 * @var string
	 */
	protected string $application_class;

	/**
	 * Module code name
	 *
	 * @var string
	 */
	protected string $codename = "";

	/**
	 * Path to this module
	 *
	 * @var string
	 */
	protected string $path;

	/**
	 * List of associated model classes
	 *
	 * @var array
	 */
	protected array $model_classes = [];

	/**
	 * Array of old_class => new_class
	 *
	 * List of object aliases to automatically register.
	 *
	 * @var array
	 */
	protected array $class_aliases = [];

	/**
	 *
	 * @ignore
	 *
	 */
	public function __sleep() {
		return [
			"application_class",
			"codename",
			"path",
			"model_classes",
			"class_aliases",
		];
	}

	/**
	 * The path to the module root
	 *
	 * @return string
	 */
	final public function path($suffix = null) {
		return $suffix ? path($this->path, $suffix) : $this->path;
	}

	/**
	 *
	 * {@inheritDoc}
	 * @see \zesk\Hookable::__wakeup()
	 */
	public function __wakeup(): void {
		parent::__wakeup();
		$this->initialize();
	}

	/**
	 * Create Module
	 *
	 * @param string $options
	 */
	final public function __construct(Application $application, array $options = [], array $module_data = []) {
		parent::__construct($application, $options);
		$this->application_class = get_class($application);
		$this->path = avalue($module_data, 'path');
		if (!$this->codename) {
			$this->codename = avalue($module_data, 'name');
			if (!$this->codename) {
				// Code name used in JavaScript settings
				$this->codename = strtolower(StringTools::unprefix(PHP::parse_class(get_class($this)), "Module_"));
			}
		}
		if (isset($this->classes)) {
			$this->application->deprecated(get_class($this) . "->classes is deprecated, use ->model_classes");
		}
		$this->application->register_class($this->model_classes());
		if (count($this->class_aliases)) {
			$this->application->objects->map($this->class_aliases);
		}
		$this->call_hook("construct");
		$this->inherit_global_options();
	}

	/**
	 *
	 * @param string $pathm
	 */
	final public function register_paths($path) {
		return $this->application->modules->register_paths($path, $this->codename);
	}

	/**
	 *
	 * {@inheritDoc}
	 * @see \zesk\Options::__toString()
	 */
	public function __toString() {
		$php = new PHP();
		$php->settings_one();
		return "\$application, " . $php->render($this->options);
	}

	/**
	 * Override in subclasses - called upon load
	 */
	public function initialize(): void {
	}

	/**
	 *
	 * @return string
	 */
	final public function name() {
		return $this->option("name", $this->codename);
	}

	/**
	 * Retrieve the codename of this module
	 *
	 * @return string
	 */
	final public function codename() {
		return $this->codename;
	}

	/**
	 * Override in subclasses - called upon Application::classes
	 * @return string[]
	 */
	public function model_classes() {
		return $this->model_classes;
	}

	/**
	 *
	 * @param string $class
	 * @param mixed $mixed
	 * @param array $options
	 * @return \zesk\Model
	 */
	final public function model_factory(string $class, mixed $mixed = null, array $options = []): Model {
		return $this->application->model_factory($class, $mixed, $options);
	}

	/**
	 *
	 * @return mixed|string|array
	 */
	public function version() {
		return $this->application->modules->version($this->codename);
	}

	/**
	 * @deprecated 2018-01 Use ->model_classes instead
	 * Override in subclasses - called upon Application::classes
	 * @return string[]
	 */
	public function classes() {
		$this->application->deprecated();
		return $this->model_classes();
	}

	/**
	 * @deprecated 2017-12 Blame PHP 7.2
	 * @param string $class
	 * @param mixed $mixed
	 * @param array $options
	 * @return \zesk\Model
	 */
	final public function object_factory($class, $mixed = null, array $options = []) {
		$this->application->deprecated();
		return $this->model_factory($class, $mixed, $options);
	}
}
