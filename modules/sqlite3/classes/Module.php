<?php
/**
 * @package zesk
 * @subpackage sqlite3
 * @author kent
 * @copyright &copy; 2018 Market Acumen, Inc.
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
	function initialize() {
		$this->application->register_class(Database::class);
		
		$module = $this->application->database_module();
		
		$module->register_scheme("sqlite", Database::class);
		$module->register_scheme("sqlite3", Database::class);
	}
}
