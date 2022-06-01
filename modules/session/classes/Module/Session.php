<?php declare(strict_types=1);
/**
 * @package zesk
 * @subpackage session
 * @author kent
 * @copyright &copy; 2022, Market Acumen, Inc.
 */
namespace zesk;

/**
 * @author kent
 */
class Module_Session extends Module {
	/**
	 * Provide simple aliases for
	 *
	 * @var array
	 */
	private static $aliases = [
		'db' => 'ORM',
		'database' => 'ORM',
	];

	/**
	 *
	 * @var Interface_Session[]
	 */
	private $instances = [];

	/**
	 *
	 * {@inheritDoc}
	 * @see \zesk\Module::initialize()
	 */
	public function initialize(): void {
		parent::initialize();
		$this->application->registerFactory('session', [
			$this,
			'session_factory',
		]);
		/**
		 * @deprecated 2018-01
		 */
		$this->application->objects->map('zesk\\Session_' . 'Database', Session_ORM::class);
		$this->application->configuration->deprecated('zesk\\Application::session_class', __CLASS__ . '::session_class');
	}

	/**
	 *
	 * @return string
	 */
	private function _implementation() {
		$get = $this->option('implementation');
		return self::$aliases[$get] ?? $get;
	}

	/**
	 *
	 * @param Application $application
	 * @return mixed|string|array
	 */
	private function session_class() {
		$default_class = $this->_implementation();
		if ($default_class) {
			$this->application->deprecated("Session::implementation configuration value is deprecated, use {class}::session_class instead (set to \"$default_class\")", [
				'class' => __CLASS__,
			]);
			$default_class = __NAMESPACE__ . '\\' . 'Session_' . $default_class;
		} else {
			$default_class = __NAMESPACE__ . '\\' . 'Session_PHP';
		}
		$class = $this->option('session_class', $this->application->option('session_class', $default_class));
		return $class;
	}

	/**
	 * Returns initialized session. You should call initializeSession on result (2018-01).
	 *
	 * @param Application $application
	 * @param string $class
	 * @throws Exception_Configuration
	 * @return \zesk\Interface_Session[string]|unknown
	 */
	public function session_factory(Application $application, $class = null) {
		if (empty($class)) {
			$class = $this->session_class();
			if (!$class) {
				throw new Exception_Configuration(__CLASS__ . '::session_class', 'Needs a class name value');
			}
		}
		if (array_key_exists($class, $this->instances)) {
			return $this->instances[$class];
		}
		return $this->instances[$class] = $this->application->factory($class, $application);
	}
}
