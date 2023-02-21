<?php
declare(strict_types=1);
/**
 * @package zesk
 * @subpackage orm
 * @author kent
 * @copyright &copy; 2023, Market Acumen, Inc.
 */

namespace zesk\ORM;

use zesk\Exception\ConfigurationException;
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
	 * @throws ORMNotFound
	 */
	protected function configure(ORMBase $object): void {
		if (!$this->table) {
			try {
				$this->initializeDatabase($object);
			} catch (ConfigurationException $e) {
				throw new ORMNotFound(self::class, __METHOD__, $e->variables(), $e);
			}
			$this->table = $this->database()->tablePrefix() . PHP::parseClass($object::class);
		}
	}
}
