<?php declare(strict_types=1);
/**
 * @package zesk
 * @subpackage orm
 * @author kent
 * @copyright &copy; 2022, Market Acumen, Inc.
 */
namespace zesk;

/**
 * @see Meta
 * @author kent
 */
class Class_Meta extends Class_ORM {
	public array $primary_keys = [
		'parent',
		'name',
	];

	public array $column_types = [
		'parent' => self::type_object,
		'name' => self::type_string,
		'value' => self::type_serialize,
	];

	/**
	 * Overwrite this in subclasses to change stuff upon instantiation
	 */

	/**
	 * Configure a class prior to instantiation
	 *
	 * Only thing set is "$this->class"
	 */
	protected function configure(ORM $object): void {
		if (!$this->table) {
			$this->initialize_database($object);
			$this->table = $this->database()->tablePrefix() . PHP::parseClass($object::class);
		}
	}
}
