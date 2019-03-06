<?php
namespace zesk\WebApp;

use zesk\Exception_Directory_NotFound;
use zesk\Application;
use zesk\Directory;
use zesk\StringTools;

abstract class Type {
	/**
	 *
	 * @var Application
	 */
	protected $application = null;

	/**
	 *
	 * @var string
	 */
	protected $path = null;

	/**
	 * Ascending ordering -MAX_INTEGER=most important, MAX_INTEGER=least
	 *
	 * @var integer
	 */
	protected $priority = null;

	/**
	 *
	 * @var \Exception
	 */
	protected $exception = null;

	/**
	 *
	 * @param string $path
	 * @throws Exception_Directory_NotFound
	 */
	public function __construct(Application $application, $path) {
		$this->application = $application;
		if (!is_dir($path)) {
			throw new Exception_Directory_NotFound($path);
		}
		$this->path = $path;
		$this->priority = $application->configuration->path_get(get_class($this), "priority", $this->priority);
	}

	/**
	 * Create all types of web applications to check if they are valid
	 *
	 * @param Application $application
	 * @param unknown $path
	 * @return object[]|\zesk\stdClass[]
	 */
	public static function factory_all_types(Application $application, $path) {
		$type_names = Directory::ls(__DIR__ . "/Type", '/.*\.php$/', false);
		$types = array();
		foreach ($type_names as $type_name) {
			$type_name = "\\Type_" . StringTools::unsuffix(ltrim($type_name, "./"), ".php");
			$class_name = __NAMESPACE__ . $type_name;
			$type = $application->factory($class_name, $application, $path);
			$types[] = $type;
		}
		return $types;
	}

	/**
	 *
	 * @return integer
	 */
	public function priority() {
		return $this->priority;
	}

	/**
	 * Does this look like my type of application?
	 *
	 * @return boolean
	 */
	abstract public function valid();

	/**
	 * if self::check() return false, this function MUST return null
	 *
	 * Otherwise, fetch the version number of the application
	 */
	abstract public function version();

	/**
	 * @return \Exception
	 */
	final public function last_exception() {
		return $this->exception;
	}
}
