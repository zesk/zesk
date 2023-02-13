<?php
declare(strict_types=1);
/**
 * @package zesk
 * @subpackage session
 * @author kent
 * @copyright &copy; 2023, Market Acumen, Inc.
 */

namespace zesk\Session;

use zesk\Exception_Unsupported;
use zesk\Module as BaseModule;
use zesk\Application;
use zesk\Exception_Class_NotFound;
use zesk\Exception_Configuration;
use zesk\Interface_Session;

/**
 * @author kent
 */
class Module extends BaseModule {
	protected array $modelClasses = [SessionORM::class];

	/**
	 *
	 * @var Interface_Session[]
	 */
	private array $instances = [];

	/**
	 * @return void
	 * @throws Exception_Configuration
	 * @throws Exception_Unsupported
	 */
	public function initialize(): void {
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
	private function sessionClass(): string {
		return $this->option(self::OPTION_SESSION_CLASS, SessionORM::class);
	}

	/**
	 * Returns initialized session. You should call initializeSession on result (2018-01).
	 *
	 * @param Application $application
	 * @param string $class
	 * @return Interface_Session
	 * @throws Exception_Configuration
	 * @throws Exception_Class_NotFound
	 */
	public function sessionFactory(Application $application, string $class = ''): Interface_Session {
		if ($class === '') {
			$class = $this->sessionClass();
			if (!$class) {
				throw new Exception_Configuration(__CLASS__ . '::' . self::OPTION_SESSION_CLASS, 'Needs a class name value');
			}
		}
		if (array_key_exists($class, $this->instances)) {
			return $this->instances[$class];
		}
		$result = $this->instances[$class] = $this->application->factory($class, $application);
		assert($result instanceof Interface_Session);
		return $result;
	}
}
