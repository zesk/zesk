<?php

/**
 * $URL: https://code.marketacumen.com/zesk/trunk/classes/Module.php $
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
	 * @var zesk\Kernel
	 */
	protected $zesk = null;

	/**
	 *
	 * @var Application
	 */
	protected $application_class = null;

	/**
	 * Module code name
	 *
	 * @var string
	 */
	protected $codename = null;

	/**
	 * Path to this module
	 *
	 * @var string
	 */
	protected $path = null;

	/**
	 * List of associated model classes
	 *
	 * @var array
	 */
	protected $model_classes = array();

	/**
	 * Array of old_class => new_class
	 *
	 * List of object aliases to automatically register.
	 *
	 * @var array
	 */
	protected $class_aliases = array();

	/**
	 *
	 * @ignore
	 *
	 */
	function __sleep() {
		return array(
			"application_class",
			"codename",
			"path",
			"classes"
		);
	}

	/**
	 * The path to the module root
	 *
	 * @return string
	 */
	final public function path() {
		return $this->path;
	}

	/**
	 *
	 * {@inheritDoc}
	 * @see \zesk\Hookable::__wakeup()
	 */
	function __wakeup() {
		parent::__wakeup();
		$this->zesk = $this->application->zesk;
		$this->initialize();
	}

	/**
	 * Create Module
	 *
	 * @param string $options
	 */
	public final function __construct(Application $application, array $options = array(), array $module_data = array()) {
		parent::__construct($application, $options);
		$this->zesk = $application->zesk;
		$this->application_class = get_class($application);
		$this->path = avalue($module_data, 'path');
		if (!$this->codename) {
			$this->codename = avalue($module_data, 'name');
			if (!$this->codename) {
				// Code name used in JavaScript settings
				$this->codename = strtolower(str::unprefix(PHP::parse_class(get_class($this)), "Module_"));
			}
		}
		if (isset($this->classes)) {
			$this->application->deprecated(get_class($this) . "->classes is deprecated, use ->model_classes");
		}
		$this->application->register_class($this->classes());
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
	public final function register_paths($path) {
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
	public function initialize() {
	}

	/**
	 *
	 * @return string
	 */
	public final function name() {
		return $this->option("name", $this->codename);
	}
	/**
	 * Retrieve the codename of this module
	 *
	 * @return string
	 */
	public final function codename() {
		return $this->codename;
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
	final public function model_factory($class, $mixed = null, array $options = array()) {
		return $this->application->model_factory($class, $mixed, $options);
	}

	/**
	 * @deprecated 2017-12 Blame PHP 7.2
	 * @param string $class
	 * @param mixed $mixed
	 * @param array $options
	 * @return \zesk\Model
	 */
	final public function object_factory($class, $mixed = null, array $options = array()) {
		$this->application->deprecated();
		return $this->model_factory($class, $mixed, $options);
	}
}
