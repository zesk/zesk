<?php
declare(strict_types=1);
/**
 * @package zesk
 * @subpackage MySQL
 * @author kent
 * @copyright &copy; 2022, Market Acumen, Inc.
 */

namespace MySQL;

/**
 *
 * @author kent
 *
 */
class Module extends \zesk\Module {
	/**
	 * Register schemes with the database module
	 *
	 * {@inheritDoc}
	 * @see \zesk\Module::initialize()
	 */
	public function initialize(): void {
		$this->application->registerClass(Database::class);

		$module = $this->application->database_module();

		$module->registerScheme('mysql', Database::class);
		$module->registerScheme('mysqli', Database::class);
	}
}
