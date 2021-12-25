<?php declare(strict_types=1);
/**
 * @package zesk
 * @subpackage ORM
 * @author kent
 * @copyright &copy; 2018 Market Acumen, Inc.
 */
namespace zesk;

/**
 * Adapters to work with specific databases
 *
 * @author kent
 *
 */
abstract class ORM_Database_Adapter {
	abstract public function database_column_set_type(Database_Column $column);
}
