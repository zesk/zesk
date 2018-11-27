<?php
/**
 * @package zesk
 * @subpackage MySQL
 * @author kent
 * @copyright &copy; 2018 Market Acumen, Inc.
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
	public function initialize() {
		$this->application->register_class(Database::class);

		$module = $this->application->database_module();

		$module->register_scheme("mysql", Database::class);
		$module->register_scheme("mysqli", Database::class);
	}
}
