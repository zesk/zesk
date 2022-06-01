<?php declare(strict_types=1);
/**
 * @package zesk
 * @subpackage webapp
 * @author kent
 * @copyright &copy; 2022, Market Acumen, Inc.
 */
namespace zesk\WebApp;

abstract class Class_ORM extends \zesk\Class_ORM {
	/**
	 *
	 * @var unknown
	 */
	public string $database_group = Instance::class;

	/**
	 * Configure a class prior to instantiation
	 *
	 * Only thing set is "$this->class"
	 */
	protected function configure(\zesk\ORM $object): void {
		$this->setOption('table_prefix', 'WebApp_');
		parent::configure($object);
	}
}
