<?php
declare(strict_types=1);
/**
 * @package zesk
 * @subpackage orm
 * @author kent
 * @copyright &copy; 2022, Market Acumen, Inc.
 */

namespace zesk\ORM;

use zesk\Exception_Configuration;
use zesk\PHP;

/**
 * @see Meta
 * @author kent
 */
class Class_Meta extends Class_Base {
	public array $primary_keys = [
		'parent', 'name',
	];

	public array $column_types = [
		'parent' => self::TYPE_OBJECT,
		'name' => self::TYPE_STRING,
		'value' => self::TYPE_SERIALIZE,
	];

	/**
	 * Overwrite this in subclasses to change stuff upon instantiation
	 */

	/**
	 * Configure a class prior to instantiation
	 *
	 * Only thing set is "$this->class"
	 * @throws Exception_ORMNotFound
	 */
	protected function configure(ORMBase $object): void {
		if (!$this->table) {
			try {
				$this->initialize_database($object);
			} catch (Exception_Configuration $e) {
				throw new Exception_ORMNotFound(self::class, __METHOD__, $e->variables(), $e);
			}
			$this->table = $this->database()->tablePrefix() . PHP::parseClass($object::class);
		}
	}
}
