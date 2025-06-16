<?php
declare(strict_types=1);
/**
 * @package zesk
 * @subpackage system
 * @author kent
 * @copyright Copyright &copy; 2025, Market Acumen, Inc.
 */

namespace zesk;

use zesk\Application\Modules;
use zesk\Exception\ClassNotFound;
use zesk\Exception\ConfigurationException;
use zesk\Exception\FileNotFound;
use zesk\Exception\NotFoundException;
use zesk\Exception\UnsupportedException;

/**
 * Module base class for all extensions to Zesk
 *
 * @see Modules
 * @author kent
 */
class Module extends Hookable
{
	/**
	 * Module code name
	 *
	 * @var string
	 */
	protected string $name = '';

	/**
	 * @var string
	 */
	private string $configurationFile;

	/**
	 * @var Configuration
	 */
	private Configuration $configuration;

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
	public function __serialize(): array
	{
		return [
			'name'              => $this->name,
			'path'              => $this->path,
			'configuration'     => $this->configuration,
			'configurationFile' => $this->configurationFile,
			'configurationData' => $this->configuration,
			'modelClasses'      => $this->modelClasses,
			'classAliases'      => $this->classAliases,
		] + parent::__serialize();
	}

	/**
	 * @return void
	 * @throws ConfigurationException
	 */
	public function checkDependencies(): void
	{
		// pass
	}

	public function __unserialize(array $data): void
	{
		parent::__unserialize($data);
		$this->name = $data['name'];
		$this->path = $data['path'];
		$this->configuration = $data['configuration'];
		$this->configurationFile = $data['configurationFile'];
		$this->configurationData = $data['configurationData'];
		$this->modelClasses = $data['modelClasses'];
		$this->classAliases = $data['classAliases'];
	}

	/**
	 * Create Module
	 *
	 * @param Application $application
	 * @param array $options
	 */
	final public function __construct(Application $application, array $options = [])
	{
		parent::__construct($application, $options);
		$this->configuration = $application->configuration->path(self::class);

		$this->application->registerClass($this->modelClasses());
		if (count($this->classAliases)) {
			$this->application->objects->setMap($this->classAliases);
		}
		$this->invokeHooks(self::HOOK_MODULE_INITIALIZE, [$this]);
		$this->inheritConfiguration();
	}

	public const HOOK_MODULE_INITIALIZE = __CLASS__ . '::initialize';

	final public function moduleConfiguration(): Configuration
	{
		return $this->configuration;
	}

	/**
	 * Clean a module name
	 *
	 * @param string $module
	 * @return string
	 */
	public static function cleanName(string $module): string
	{
		return trim(File::nameClean($module), '- ');
	}

	/**
	 * @return string
	 */
	public function baseName(): string
	{
		return basename($this->name);
	}

	final public function moduleData(): array
	{
		return [
			'name'          => $this->name,
			'configuration' => $this->configuration,
		];
	}

	/**
	 *
	 * @see Options::__toString()
	 */
	public function __toString()
	{
		$php = new PHP();
		$php->settingsOneLine();
		return '$application, ' . $php->render($this->options());
	}

	/**
	 * Override in subclasses - called upon load
	 * @throws ConfigurationException
	 * @throws UnsupportedException
	 */
	public function initialize(): void
	{
		if ($this->optionBool('fakeConfigurationException')) {
			throw new ConfigurationException([
				$this::class,
				'fake',
			], 'Fake exception for testing');
		}
		if ($this->optionBool('fakeUnsupportedException')) {
			throw new UnsupportedException(__METHOD__);
		}
	}

	/**
	 * Override in subclasses - called upon configure
	 * @throws ConfigurationException
	 * @throws UnsupportedException
	 */
	public function configure(): void
	{
	}

	/**
	 * @return void
	 */
	public function shutdown(): void
	{
		if ($this->optionBool('debugShutdown')) {
			$this->application->debug($this::class . '::shutdown');
		}
	}

	/**
	 * Retrieve the display name for UI for this module
	 *
	 * @return string
	 */
	final public function name(): string
	{
		return $this->option('name', $this->name);
	}

	/**
	 * Retrieve the codename of this module
	 *
	 * @return string
	 */
	final public function codeName(): string
	{
		return $this->name;
	}

	/**
	 * Override in subclasses - called upon Application::classes
	 * @return string[]
	 */
	public function modelClasses(): array
	{
		return $this->modelClasses;
	}

	/**
	 *
	 * @param string $class
	 * @param mixed $mixed
	 * @param array $options
	 * @return Model
	 * @throws ClassNotFound
	 */
	final public function modelFactory(string $class, mixed $mixed = null, array $options = []): Model
	{
		return $this->application->modelFactory($class, $mixed, $options);
	}

	/**
	 *
	 * @return string
	 */
	public function version(): string
	{
		try {
			$version = $this->option('version') ?? $this->configuration->getString('version') ?? \zesk\Module\Version::extractVersion($this->configuration->toArray());
		} catch (NotFoundException|FileNotFound) {
			return '';
		}
		return $version;
	}
}
