<?php
declare(strict_types=1);
/**
 * @package zesk
 * @subpackage sqlite3
 * @author kent
 * @copyright &copy; 2023, Market Acumen, Inc.
 */
namespace zesk\SQLite3;

use zesk\Module as BaseModule;

/**
 *
 * @author kent
 *
 */
class Module extends BaseModule {
	/**
	 * Register schemes with the database module
	 *
	 * @see BaseModule::initialize()
	 */
	public function initialize(): void {
		$this->application->registerClass(self::class);

		$module = $this->application->databaseModule();

		$module->registerScheme('sqlite', self::class);
		$module->registerScheme('sqlite3', self::class);
	}
}
