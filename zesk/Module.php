<?php
declare(strict_types=1);

/**
 * @package zesk
 * @subpackage system
 * @author kent
 * @copyright Copyright &copy; 2023, Market Acumen, Inc.
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
	 * Module code name
	 *
	 * @var string
	 */
	private string $name = '';

	/**
	 * Path to this module
	 *
	 * @var string
	 */
	private string $path;

	/**
	 * @var string
	 */
	private string $configurationFile;

	/**
	 * @var array
	 */
	private array $configuration;

	/**
	 * List of associated model classes
	 *
	 * @var array
	 */
	protected array $modelClasses = [];

	/**
	 * Array of old_class => new_class
	 *
	 * List of object aliases to automatically register.
	 *
	 * @var array
	 */
	protected array $classAliases = [];

	/**
	 */
	public function __sleep() {
		return [
			'application_class', 'name', 'path', 'model_classes', 'class_aliases', 'class', 'path', 'configuration',
			'configurationFile', 'configurationData',
		];
	}

	/**
	 * The path to the module root
	 *
	 * @param string $suffix
	 * @return string
	 */
	final public function path(string $suffix = ''): string {
		return path($this->path, $suffix);
	}

	/**
	 * @return void
	 * @throws Exception_Configuration
	 * @throws Exception_Unsupported
	 */
	public function __wakeup(): void {
		parent::__wakeup();
		$this->initialize();
	}

	/**
	 * @return string
	 */
	private function _defaultCodeName(): string {
		return strtolower(StringTools::removePrefix(PHP::parseClass(get_class($this)), [
			'Module_', 'Module',
		]));
	}

	/**
	 * Create Module
	 *
	 * @param Application $application
	 * @param array $options
	 * @param array $moduleFactoryState
	 * @throws Exception_Unsupported
	 */
	final public function __construct(Application $application, array $options = [], array $moduleFactoryState = []) {
		parent::__construct($application, $options);
		$this->path = $moduleFactoryState['path'];
		$this->name = $this->name ?: $moduleFactoryState['name'] ?? $this->_defaultCodeName();
		$this->configuration = $moduleFactoryState['configuration'];
		$this->configurationFile = $moduleFactoryState['configurationFile'] ?? '';
		$moduleFactoryState = ArrayTools::filterKeys($moduleFactoryState, null, [
			'class', 'path', 'name', 'configuration', 'configurationFile', 'configurationData', 'optionsPath',
		]);
		if (count($moduleFactoryState)) {
			throw new Exception_Unsupported('Need to support module fields: {keys}', [
				'keys' => array_keys($moduleFactoryState),
			]);
		}
		$this->application->registerClass($this->modelClasses());
		if (count($this->classAliases)) {
			$this->application->objects->setMap($this->classAliases);
		}
		$this->callHook('construct');
		$this->inheritConfiguration();
	}

	final public function moduleConfiguration(): array {
		return $this->configuration;
	}

	final public function moduleConfigurationFile(): array {
		return $this->configurationFile;
	}

	/**
	 * Clean a module name
	 *
	 * @param string $module
	 * @return string
	 */
	public static function cleanName(string $module): string {
		return trim(File::name_clean($module), '- ');
	}

	/**
	 * @return string
	 */
	public function baseName(): string {
		return basename($this->name);
	}

	final public function moduleData(): array {
		return [
			'path' => $this->path, 'base' => $this->baseName(), 'name' => $this->name,
			'configuration' => $this->configuration, 'configurationFile' => $this->configurationFile,
		];
	}

	/**
	 *
	 * @see Options::__toString()
	 */
	public function __toString() {
		$php = new PHP();
		$php->settings_one();
		return '$application, ' . $php->render($this->options);
	}

	/**
	 * Override in subclasses - called upon load
	 * @throws Exception_Configuration
	 * @throws Exception_Unsupported
	 */
	public function initialize(): void {
		if ($this->optionBool('fakeConfigurationException')) {
			throw new Exception_Configuration([$this::class, 'fake'], 'Fake exception for testing');
		}
		if ($this->optionBool('fakeUnsupportedException')) {
			throw new Exception_Unsupported(__METHOD__);
		}
	}

	/**
	 * @return void
	 */
	public function shutdown(): void {
		if ($this->optionBool('debugShutdown')) {
			$this->application->logger->debug($this::class . '::shutdown');
		}
	}

	/**
	 * Retrieve the display name for UI for this module
	 *
	 * @return string
	 */
	final public function name(): string {
		return $this->option('name', $this->name);
	}

	/**
	 * Retrieve the codename of this module
	 *
	 * @return string
	 */
	final public function codeName(): string {
		return $this->name;
	}

	/**
	 * Override in subclasses - called upon Application::classes
	 * @return string[]
	 */
	public function modelClasses(): array {
		return $this->modelClasses;
	}

	/**
	 *
	 * @param string $class
	 * @param mixed $mixed
	 * @param array $options
	 * @return Model
	 */
	final public function modelFactory(string $class, mixed $mixed = null, array $options = []): Model {
		return $this->application->modelFactory($class, $mixed, $options);
	}

	/**
	 *
	 * @return string
	 */
	public function version(): string {
		try {
			$version = $this->option('version') ?? $this->configuration['version'] ?? \zesk\Module\Version::extractVersion($this->configuration);
		} catch (Exception_NotFound|Exception_File_NotFound) {
			return '';
		}
		return $version;
	}
}
