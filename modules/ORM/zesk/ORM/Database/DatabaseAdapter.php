<?php
declare(strict_types=1);
/**
 * @package zesk
 * @subpackage ORM
 * @author kent
 * @copyright &copy; 2023, Market Acumen, Inc.
 */
namespace zesk\ORM\Database;

use zesk\Database\Column;

/**
 * Adapters to work with specific databases
 *
 * @author kent
 *
 */
abstract class DatabaseAdapter {
	abstract public function columnSetType(Column $column);
}
