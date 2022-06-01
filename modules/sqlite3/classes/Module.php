<?php declare(strict_types=1);
/**
 * @package zesk
 * @subpackage sqlite3
 * @author kent
 * @copyright &copy; 2022, Market Acumen, Inc.
 */
namespace sqlite3;

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
		$this->application->register_class(Database::class);

		$module = $this->application->database_module();

		$module->registerScheme('sqlite', Database::class);
		$module->registerScheme('sqlite3', Database::class);
	}
}
