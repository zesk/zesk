<?php
declare(strict_types=1);
/**
 * @package zesk
 * @subpackage MySQL
 * @author kent
 * @copyright &copy; 2023, Market Acumen, Inc.
 */

namespace MySQL;

use zesk\Module as BaseModule;
use zesk\Exception_Class_NotFound;
use zesk\Exception_Configuration;
use zesk\Exception_Unsupported;

/**
 *
 * @author kent
 *
 */
class Module extends BaseModule {
	/**
	 * Register schemes with the database module
	 * @return void
	 * @throws Exception_Class_NotFound
	 * @throws Exception_Configuration
	 * @throws Exception_Unsupported
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
