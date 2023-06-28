<?php
declare(strict_types=1);
/**
 * @package zesk
 * @subpackage session
 * @author kent
 * @copyright &copy; 2023, Market Acumen, Inc.
 */

namespace zesk\Session;

use zesk\Application;
use zesk\Exception\ClassNotFound;
use zesk\Exception\ConfigurationException;
use zesk\Exception\UnsupportedException;
use zesk\Interface\SessionInterface;
use zesk\Module as BaseModule;

/**
 * @author kent
 */
class Module extends BaseModule
{
	protected array $modelClasses = [Session::class];

	/**
	 *
	 * @var SessionInterface[]
	 */
	private array $instances = [];

	/**
	 * @return void
	 * @throws ConfigurationException
	 * @throws UnsupportedException
	 */
	public function initialize(): void
	{
		parent::initialize();
		$this->application->registerFactory('session', [
			$this, 'sessionFactory',
		]);
	}

	/**
	 *
	 */
	public const OPTION_SESSION_CLASS = 'sessionClass';

	/**
	 * @return string
	 */
	private function sessionClass(): string
	{
		return $this->option(self::OPTION_SESSION_CLASS, Session::class);
	}

	/**
	 * Returns initialized session. You should call initializeSession on result (2018-01).
	 *
	 * @param string $class
	 * @return SessionInterface
	 * @throws ConfigurationException
	 * @throws ClassNotFound
	 */
	public function sessionFactory(string $class = ''): SessionInterface
	{
		if ($class === '') {
			$class = $this->sessionClass();
			if (!$class) {
				throw new ConfigurationException(__CLASS__ . '::' . self::OPTION_SESSION_CLASS, 'Needs a class name value');
			}
		}
		if (array_key_exists($class, $this->instances)) {
			return $this->instances[$class];
		}
		$result = $this->instances[$class] = $this->application->factory($class, $this->application);
		assert($result instanceof SessionInterface);
		return $result;
	}
}
