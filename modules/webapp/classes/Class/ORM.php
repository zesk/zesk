<?php
/**
 * @package zesk
 * @subpackage webapp
 * @author kent
 * @copyright &copy; 2019 Market Acumen, Inc.
 */
namespace zesk\WebApp;

abstract class Class_ORM extends \zesk\Class_ORM {
	/**
	 *
	 * @var unknown
	 */
	public $database_group = Instance::class;

	/**
	 * Configure a class prior to instantiation
	 *
	 * Only thing set is "$this->class"
	 */
	protected function configure(\zesk\ORM $object) {
		$this->set_option('table_prefix', 'WebApp_');
		parent::configure($object);
	}
}
