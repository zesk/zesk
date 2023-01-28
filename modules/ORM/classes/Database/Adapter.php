<?php declare(strict_types=1);
/**
 * @package zesk
 * @subpackage ORM
 * @author kent
 * @copyright &copy; 2023, Market Acumen, Inc.
 */
namespace zesk\ORM;

use zesk\Database_Column;

/**
 * Adapters to work with specific databases
 *
 * @author kent
 *
 */
abstract class Database_Adapter {
	abstract public function database_column_set_type(Database_Column $column);
}
