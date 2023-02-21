<?php
declare(strict_types=1);
/**
 * @package zesk
 * @subpackage MySQL
 * @author kent
 * @copyright &copy; 2023, Market Acumen, Inc.
 */

namespace zesk\MySQL;

use zesk\Exception\ClassNotFound;
use zesk\Exception\ConfigurationException;
use zesk\Exception\Unsupported;
use zesk\Module as BaseModule;

/**
 *
 * @author kent
 *
 */
class Module extends BaseModule {
	/**
	 * Register schemes with the database module
	 * @return void
	 * @throws ClassNotFound
	 * @throws ConfigurationException
	 * @throws Unsupported
	 */
	public function initialize(): void {
		parent::initialize();

		$this->application->registerClass(Database::class);

		$module = $this->application->databaseModule();

		$module->registerScheme('mysql', Database::class)
			->registerScheme('mysqli', Database::class)
			->registerScheme('mariadb', Database::class);
	}
}
